<?php
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
Artisan::command('admin:create', function () {
    $name = $this->ask('Full name');
    $email = $this->ask('Email address');
    $password = $this->secret('Password (12+ characters, upper/lowercase and a number)');
    $confirmation = $this->secret('Confirm password');
    $validator = Validator::make(['name' => $name, 'email' => $email, 'password' => $password, 'password_confirmation' => $confirmation], [
        'name' => ['required','string','max:100'], 'email' => ['required','email','max:255','unique:users,email'],
        'password' => ['required','confirmed',Password::min(12)->mixedCase()->numbers()],
    ]);
    if ($validator->fails()) { foreach ($validator->errors()->all() as $error) { $this->error($error); } return 1; }
    $user = new User(['name' => $name, 'email' => $email, 'password' => $password]);
    $user->is_admin = true; $user->save();
    $this->info('Administrator created. Sign in at /admin/login.');
    return 0;
})->purpose('Provision an administrator securely, including in production');

Artisan::command('admin:invite-bootstrap {email} {--minutes=30}', function () {
    if (! app()->environment('production') || ! config('admin.bootstrap_enabled')) {
        $this->error('Production bootstrap is not enabled.'); return 1;
    }
    $email = mb_strtolower(trim($this->argument('email')));
    $minutes = filter_var($this->option('minutes'), FILTER_VALIDATE_INT);
    if (! filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255 || $minutes < 1 || $minutes > 60) {
        $this->error('Use a valid email and a lifetime of 1–60 minutes.'); return 1;
    }
    return \Illuminate\Support\Facades\DB::transaction(function () use ($email, $minutes) {
        $db = \Illuminate\Support\Facades\DB::table('admin_bootstrap_invitations');
        $db->insertOrIgnore(['id' => 1, 'created_at' => now(), 'updated_at' => now()]);
        $invite = $db->where('id', 1)->lockForUpdate()->first();
        if ($invite->consumed_at || User::where('is_admin', true)->exists() || User::where('email', $email)->exists()) {
            $this->error('Bootstrap is closed or that email is already registered.'); return 1;
        }
        $token = bin2hex(random_bytes(32));
        $path = storage_path('app/private/admin-bootstrap-token');
        if (! is_dir(dirname($path))) mkdir(dirname($path), 0700, true);
        if (is_link($path)) throw new \RuntimeException('Unexpected token-file symlink.');
        $oldUmask = umask(0077);
        try {
            if (file_put_contents($path, $token, LOCK_EX) === false || ! chmod($path, 0600)) {
                throw new \RuntimeException('Could not write protected invitation file.');
            }
        } finally { umask($oldUmask); }
        $db->where('id', 1)->update(['email' => $email, 'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addMinutes($minutes), 'updated_at' => now()]);
        $this->info('Single-use invitation saved to private server storage; no token printed.');
        return 0;
    }, 3);
})->purpose('Issue an email-bound first-admin invitation without printing its secret');
