<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public const ROLES = ['admin' => 'Tam yetkili', 'owner' => 'Patron', 'editor' => 'Düzenleyici', 'viewer' => 'Çalışan'];

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        User::create([
            'name' => $data['name'],
            'username' => mb_strtolower($data['username']),
            'email' => mb_strtolower($data['email']),
            'role' => $data['role'],
            'password' => Hash::make($data['password']),
            'email_verified_at' => now(),
        ]);

        return redirect()->route('settings.edit', ['tab' => 'users'])
            ->with('success', $data['name'].' kullanıcısı oluşturuldu.')
            ->with('shown_password', ['name' => $data['name'], 'username' => mb_strtolower($data['username']), 'password' => $data['password']]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $this->validated($request, $user);

        $user->name = $data['name'];
        $user->username = mb_strtolower($data['username']);
        $user->email = mb_strtolower($data['email']);
        $user->role = $data['role'];
        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        $user->save();

        $r = redirect()->route('settings.edit', ['tab' => 'users'])->with('success', $user->name.' güncellendi.');
        if (! empty($data['password'])) {
            $r->with('shown_password', ['name' => $user->name, 'username' => $user->username, 'password' => $data['password']]);
        }

        return $r;
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_if($user->id === $request->user()->id, 422, 'Kendi hesabınızı silemezsiniz.');
        abort_if(User::count() <= 1, 422, 'Son kullanıcı silinemez.');

        $user->delete();

        return redirect()->route('settings.edit', ['tab' => 'users'])->with('success', $user->name.' silindi.');
    }

    private function validated(Request $request, ?User $ignore = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9._-]+$/i', Rule::unique('users', 'username')->ignore($ignore?->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($ignore?->id)],
            'role' => ['required', Rule::in(array_keys(self::ROLES))],
            'password' => [$ignore ? 'nullable' : 'required', 'string', 'min:6'],
        ], [
            'username.regex' => 'Kullanıcı adında sadece harf, rakam, nokta, tire ve alt çizgi olabilir.',
        ], ['name' => 'ad soyad', 'username' => 'kullanıcı adı', 'email' => 'e-posta', 'role' => 'yetki', 'password' => 'şifre']);
    }
}
