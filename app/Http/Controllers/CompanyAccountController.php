<?php

namespace App\Http\Controllers;

use App\Models\CompanyAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Ayarlar > Şirket & Marka altındaki banka hesapları. */
class CompanyAccountController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['sort'] = (int) (CompanyAccount::max('sort') ?? 0) + 10;
        CompanyAccount::create($data);

        return $this->back($data['label'].' eklendi.');
    }

    public function update(Request $request, CompanyAccount $account): RedirectResponse
    {
        $account->update($this->validated($request));

        return $this->back($account->label.' güncellendi.');
    }

    public function destroy(CompanyAccount $account): RedirectResponse
    {
        $label = $account->label;
        $account->delete();

        return $this->back($label.' silindi.');
    }

    private function validated(Request $request): array
    {
        $request->merge(['iban' => strtoupper(preg_replace('/\s+/', '', (string) $request->input('iban')))]);

        return $request->validate([
            'label' => ['required', 'string', 'max:120'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'account_holder' => ['nullable', 'string', 'max:120'],
            'iban' => ['nullable', 'string', 'max:34', 'regex:/^TR[0-9]{24}$/'],
            'account_no' => ['nullable', 'string', 'max:30'],
            'branch_code' => ['nullable', 'string', 'max:10'],
            'corp_code' => ['nullable', 'string', 'max:20'],
            'note' => ['nullable', 'string', 'max:255'],
        ], [
            'iban.regex' => 'IBAN TR ile başlamalı ve 26 karakter olmalı.',
        ], [
            'label' => 'kullanım amacı', 'bank_name' => 'banka', 'account_holder' => 'hesap sahibi',
            'account_no' => 'hesap no', 'branch_code' => 'şube kodu', 'corp_code' => 'kurum kodu',
        ]) + ['is_payroll' => $request->boolean('is_payroll')];
    }

    private function back(string $message): RedirectResponse
    {
        return redirect()->route('settings.edit', ['tab' => 'company'])->with('success', $message);
    }
}
