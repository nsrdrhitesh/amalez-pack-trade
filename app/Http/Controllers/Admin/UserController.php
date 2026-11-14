<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Cms;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index(Request $r)
    {
        Gate::authorize('cms-users');

        return view('admin.cms.users', ['records' => User::when($r->filled('q'), fn ($q) => $q->where('name', 'like', '%'.$r->string('q').'%'))->orderBy('name')->paginate(20)->withQueryString()]);
    }

    public function save(Request $r, ?User $user = null)
    {
        Gate::authorize('cms-users');
        $user ??= new User;
        $data = $r->validate(['name' => 'required|string|max:100', 'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)], 'password' => [$user->exists ? 'nullable' : 'required', 'confirmed', Password::min(12)->mixedCase()->numbers()], 'cms_role' => ['required', Rule::in(['owner', 'manager', 'editor', 'visitor'])]]);
        DB::transaction(function () use ($data, $user) {
            $owners = User::where('is_admin', true)->where('cms_role', 'owner')->lockForUpdate()->get();
            abort_if($user->id === auth()->id() && $data['cms_role'] !== 'owner', 422, 'You cannot remove your own owner access.');
            abort_if($user->cms_role === 'owner' && $data['cms_role'] !== 'owner' && $owners->count() <= 1, 422, 'Keep at least one owner.');
            $user->fill(collect($data)->only(['name', 'email'])->all());
            if (! empty($data['password'])) {
                $user->password = $data['password'];
            }$user->cms_role = $data['cms_role'] === 'visitor' ? null : $data['cms_role'];
            $user->is_admin = $data['cms_role'] !== 'visitor';
            $user->save();
            Cms::log('user.saved', $user, ['role' => $data['cms_role']]);
        });

        return back()->with('status', 'User saved.');
    }

    public function destroy(User $user)
    {
        Gate::authorize('cms-users');
        DB::transaction(function () use ($user) {
            $owners = User::where('is_admin', true)->where('cms_role', 'owner')->lockForUpdate()->get();
            abort_if($user->id === auth()->id() || ($user->cms_role === 'owner' && $owners->count() <= 1), 422, 'Your account and the last owner cannot be deleted.');
            Cms::log('user.deleted', $user);
            $user->delete();
        });

        return back()->with('status', 'User deleted.');
    }
}
