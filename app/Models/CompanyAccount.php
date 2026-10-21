<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/** Şirketin banka hesabı. Bir tanesi maaş dosyasının üretildiği hesaptır. */
class CompanyAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'label', 'bank_name', 'account_holder', 'iban', 'account_no',
        'branch_code', 'corp_code', 'is_payroll', 'note', 'sort',
    ];

    protected function casts(): array
    {
        return ['is_payroll' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::saved(function (CompanyAccount $a) {
            // Maaş dosyası hesabı tek olabilir
            if ($a->is_payroll) {
                static::where('id', '!=', $a->id)->where('is_payroll', true)->update(['is_payroll' => false]);
            }
        });
    }

    /** Maaş dosyasının üretileceği hesap. */
    public static function payroll(): ?self
    {
        return static::where('is_payroll', true)->first();
    }

    /** "TR12 3456 ..." biçiminde okunur IBAN. */
    public function getIbanPrettyAttribute(): ?string
    {
        $iban = preg_replace('/\s+/', '', (string) $this->iban);

        return $iban === '' ? null : trim(chunk_split($iban, 4, ' '));
    }
}
