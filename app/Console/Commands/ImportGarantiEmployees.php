<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\SalaryPayment;
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Garanti "TGB Yeni Maaş Dosyası" (Aktif Elemanlar / aylık maaş ödeme) Excel'inden personel aktarır.
 * Eşleşme: TCKN, yoksa IBAN, yoksa ad soyad. Var olan personel güncellenir, yeni olan eklenir.
 * --period=YYYY-MM verilirse "Tutar" sütunu o ay için maaş kaydı (ödendi) olarak da işlenir ve
 * tutar > 0 ise personelin maaşı olarak yazılır.
 */
class ImportGarantiEmployees extends Command
{
    protected $signature = 'aysha:import-garanti {file : Excel dosyası yolu}
                            {--period= : Tutar sütununu bu dönemin bordrosu olarak işle (örn. 2026-07)}
                            {--paid-at= : Bordro ödeme tarihi (Y-m-d), varsayılan dönemin 11\'i}
                            {--dry-run : Yazmadan sadece göster}';

    protected $description = 'Garanti maaş dosyasından personel (ve isteğe bağlı aylık maaş) aktarır';

    public function handle(): int
    {
        $path = $this->argument('file');
        if (! is_file($path)) {
            $this->error("Dosya bulunamadı: {$path}");

            return self::FAILURE;
        }

        $ws = IOFactory::load($path)->getActiveSheet();
        $rows = $ws->toArray(null, true, false, false);

        // Başlık satırını bul ("İsim" ile başlayan)
        $headerIdx = null;
        foreach ($rows as $i => $r) {
            if (isset($r[0]) && trim((string) $r[0]) === 'İsim') {
                $headerIdx = $i;
                break;
            }
        }
        if ($headerIdx === null) {
            $this->error('"İsim" başlık satırı bulunamadı; bu bir Garanti maaş dosyası değil.');

            return self::FAILURE;
        }

        $period = $this->option('period');
        [$py, $pm] = $period ? array_map('intval', explode('-', $period)) : [null, null];
        $paidAt = $this->option('paid-at') ?: ($period ? sprintf('%04d-%02d-11', $py, $pm) : null);
        $dry = (bool) $this->option('dry-run');

        $created = $updated = $payroll = 0;
        foreach (array_slice($rows, $headerIdx + 1) as $r) {
            $name = trim((string) ($r[0] ?? ''));
            if ($name === '') {
                continue;
            }
            $tc = preg_replace('/\D/', '', (string) ($r[1] ?? '')) ?: null;
            $iban = strtoupper(preg_replace('/\s+/', '', (string) ($r[5] ?? ''))) ?: null;
            $amount = isset($r[6]) && $r[6] !== null && $r[6] !== '' ? (float) $r[6] : null;

            [$first, $last] = $this->splitName($name);

            $employee = ($tc ? Employee::withTrashed()->where('tc_no', $tc)->first() : null)
                ?? ($iban ? Employee::withTrashed()->where('iban', $iban)->first() : null)
                ?? Employee::withTrashed()->whereRaw('UPPER(first_name) = ? AND UPPER(last_name) = ?', [mb_strtoupper($first, 'UTF-8'), mb_strtoupper($last, 'UTF-8')])->first();

            $attrs = [
                'first_name' => $first,
                'last_name' => $last,
                'tc_no' => $tc && strlen($tc) === 11 ? $tc : ($employee?->tc_no),
                'bank_name' => 'Garanti BBVA',
                'bank_code' => trim((string) ($r[2] ?? '')) ?: '62',
                'branch_code' => trim((string) ($r[3] ?? '')) ?: null,
                'account_no' => trim((string) ($r[4] ?? '')) ?: null,
                'iban' => $iban,
                'account_holder' => $name,
                'status' => 'active',
            ];
            if ($amount !== null && $amount > 0) {
                $attrs['salary'] = $amount;
            }

            $this->line(sprintf('%-28s TC:%-12s IBAN:%-27s Tutar:%s  → %s', $name, $tc ?? '-', $iban ?? '-', $amount !== null ? number_format($amount, 2, ',', '.') : '-', $employee ? 'güncelle' : 'yeni'));

            if ($dry) {
                continue;
            }

            if ($employee) {
                if ($employee->trashed()) {
                    $employee->restore();
                }
                $employee->fill($attrs)->save();
                $updated++;
            } else {
                $attrs['position'] = 'Temizlik Personeli';
                // İşe giriş tarihi dosyada yok; elle girilene kadar boş kalır.
                $employee = Employee::create($attrs + ['salary' => $amount ?? 0]);
                $created++;
            }

            if ($period && $amount !== null) {
                $p = SalaryPayment::firstOrNew(['employee_id' => $employee->id, 'period_year' => $py, 'period_month' => $pm]);
                $p->base_salary = $amount > 0 ? $amount : ($p->base_salary ?: $employee->salary);
                $p->paid_amount = $amount;
                $p->payment_method = 'transfer';
                $p->paid_at = $amount > 0 ? $paidAt : null;
                $p->note = $p->note ?: 'Garanti maaş dosyasından aktarıldı';
                $p->save();
                $payroll++;
            }
        }

        $this->info($dry ? 'Kuru çalıştırma: hiçbir şey yazılmadı.' : "Tamamlandı: {$created} yeni, {$updated} güncellendi".($period ? ", {$payroll} bordro satırı ({$period})" : '').'.');

        return self::SUCCESS;
    }

    /** "GÜLDANE GÜRBULAK" -> ["Güldane", "Gürbulak"]; son kelime soyad. */
    private function splitName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name));
        $last = array_pop($parts);
        $first = implode(' ', $parts) ?: $last;
        // Türkçe baş harf: "İBRAHİM" -> "İbrahim", "ISPARTA" -> "Isparta", "GÜLÜZAR" -> "Gülüzar"
        $tc = function (string $w): string {
            $lower = mb_strtolower(str_replace(['İ', 'I'], ['i', 'ı'], $w), 'UTF-8');
            $first = mb_substr($lower, 0, 1, 'UTF-8');
            $firstUpper = $first === 'i' ? 'İ' : ($first === 'ı' ? 'I' : mb_strtoupper($first, 'UTF-8'));

            return $firstUpper.mb_substr($lower, 1, null, 'UTF-8');
        };
        $fix = fn ($s) => implode(' ', array_map($tc, explode(' ', $s)));

        return [$fix($first), $fix($last)];
    }
}
