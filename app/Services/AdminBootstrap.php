<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AdminBootstrap
{
    public function available(): bool
    {
        return config('admin.bootstrap_enabled')
            && DB::table('admin_bootstrap_invitations')->where('id', 1)
                ->whereNull('consumed_at')->where('expires_at', '>', now())->exists()
            && ! User::where('is_admin', true)->exists();
    }

    public function register(Request $request): User
    {
        abort_unless(config('admin.bootstrap_enabled'), 404);

        return DB::transaction(function () use ($request) {
            // One permanent singleton row serializes all bootstrap attempts.
            // Consumption and user creation commit together; validation failure rolls back both.
            $invite = DB::table('admin_bootstrap_invitations')->where('id', 1)->lockForUpdate()->first();
            abort_unless($invite && ! $invite->consumed_at && $invite->expires_at
                && now()->lt($invite->expires_at) && ! User::where('is_admin', true)->exists(), 404);

            $token = $request->input('provisioning_token');
            if (! is_string($token) || strlen($token) !== 64
                || ! hash_equals($invite->token_hash, hash('sha256', $token))
                || ! is_string($request->input('email'))
                || $invite->email !== mb_strtolower(trim($request->input('email')))) {
                throw ValidationException::withMessages(['provisioning_token' => 'The provisioning invitation is invalid or unavailable.']);
            }

            $data = $request->validate([
                'name' => ['required', 'string', 'max:100'],
                'email' => ['required', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()],
            ]);
            $user = new User($data);
            $user->email = $invite->email;
            $user->is_admin = true;
            $user->save();
            DB::table('admin_bootstrap_invitations')->where('id', 1)->update([
                'token_hash' => null, 'consumed_at' => now(), 'updated_at' => now(),
            ]);

            return $user;
        }, 3);
    }
}
