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
