<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class SettingController extends Controller
{
    public function edit()
    {
        $values = collect(Setting::DEFAULTS)->map(fn ($meta, $key) => Setting::get($key));

        $users = \App\Models\User::with(['loginLogs' => fn ($q) => $q->limit(25)])->withCount(['loginLogs as successful_logins_count' => fn ($q) => $q->where('successful', true)])->orderBy('name')->get();
        $categories = \App\Models\ExpenseCategory::orderBy('sort')->orderBy('id')->get();
        $categoryUsage = \App\Models\Expense::selectRaw('category, COUNT(*) as c')->groupBy('category')->pluck('c', 'category');

        $lists = collect(\App\Models\ListItem::TYPES)->map(fn ($meta, $type) => \App\Models\ListItem::where('type', $type)->orderBy('sort')->orderBy('id')->get());
        $listUsage = [
            'position' => \App\Models\Employee::withTrashed()->selectRaw('position as k, COUNT(*) as c')->groupBy('position')->pluck('c', 'k'),
            'bank' => \App\Models\Employee::withTrashed()->selectRaw('bank_name as k, COUNT(*) as c')->groupBy('bank_name')->pluck('c', 'k'),
            'leave_type' => \App\Models\Leave::selectRaw('type as k, COUNT(*) as c')->groupBy('type')->pluck('c', 'k'),
        ];

        return view('settings.edit', compact('values', 'users', 'categories', 'categoryUsage', 'lists', 'listUsage'));
    }

    public function update(Request $request): RedirectResponse
    {
        $money = fn ($v) => $v === null || trim((string) $v) === '' ? null : str_replace(['.', ','], ['', '.'], preg_replace('/[^\d,.]/', '', (string) $v));

        $data = $request->validate([
            'meal_allowance' => ['required', 'string'],
            'meal_allowance_retired' => ['required', 'string'],
            'travel_allowance' => ['required', 'string'],
            'travel_allowance_retired' => ['required', 'string'],
            'company_name' => ['nullable', 'string', 'max:100'],
            'bank_corp_code' => ['nullable', 'string', 'max:20'],
            'bank_branch_code' => ['nullable', 'string', 'max:20'],
            'bank_account' => ['nullable', 'string', 'max:30'],
        ]);

        foreach (['meal_allowance', 'meal_allowance_retired', 'travel_allowance', 'travel_allowance_retired'] as $k) {
            $v = $money($data[$k]);
            abort_if($v === null || ! is_numeric($v) || $v < 0, 422, 'Geçersiz tutar.');
            Setting::set($k, $v);
        }
        foreach (['company_name', 'bank_corp_code', 'bank_branch_code', 'bank_account'] as $k) {
            Setting::set($k, $data[$k] ?? null);
        }

        return redirect()->route('settings.edit', ['tab' => $request->input('_tab', 'allowances')])->with('success', 'Ayarlar kaydedildi.');
    }

    /** Hesap: ad, kullanıcı adı, e-posta, isteğe bağlı şifre. */
    public function account(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9._-]+$/i', Rule::unique('users', 'username')->ignore($user->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'current_password' => ['nullable', 'required_with:password', 'current_password'],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
        ], [
            'username.regex' => 'Kullanıcı adında sadece harf, rakam, nokta, tire ve alt çizgi olabilir.',
            'current_password.current_password' => 'Mevcut şifre yanlış.',
            'password.confirmed' => 'Yeni şifreler birbiriyle uyuşmuyor.',
            'current_password.required_with' => 'Şifre değiştirmek için mevcut şifrenizi girin.',
        ], ['name' => 'ad soyad', 'username' => 'kullanıcı adı', 'email' => 'e-posta', 'current_password' => 'mevcut şifre', 'password' => 'yeni şifre']);

        $user->name = $data['name'];
        $user->username = mb_strtolower($data['username']);
        $user->email = mb_strtolower($data['email']);
        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        $user->save();

        return redirect()->route('settings.edit', ['tab' => 'account'])->with('success', ! empty($data['password']) ? 'Hesap bilgileri ve şifre güncellendi.' : 'Hesap bilgileri güncellendi.');
    }
}
