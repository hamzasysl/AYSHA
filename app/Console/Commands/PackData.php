<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

/**
 * Yerel veritabanını (tüm tablolar + veriler) AES-256 ile şifreleyip database/veri.enc dosyasına yazar.
 * Repo herkese açık olduğu için düz SQL asla commit edilmez; kurulum sayfası aynı şifreyle açar.
 *   php artisan aysha:veri-paketle --sifre="..."
 */
class PackData extends Command
{
    protected $signature = 'aysha:veri-paketle {--sifre= : Paket şifresi (kurulumda sorulacak)}';
    protected $description = 'Yerel veritabanını şifreli veri paketi (database/veri.enc) olarak dışa aktarır';

    public function handle(): int
    {
        $pass = (string) $this->option('sifre');
        if (strlen($pass) < 8) {
            $this->error('--sifre en az 8 karakter olmalı.');

            return self::FAILURE;
        }

        $db = config('database.connections.mysql');
        $cmd = ['mysqldump', '-h', $db['host'], '-P', (string) $db['port'], '-u', $db['username'], '-p'.$db['password'],
            '--single-transaction', '--skip-lock-tables', '--no-tablespaces', '--default-character-set=utf8mb4', '--add-drop-table',
            '--ignore-table='.$db['database'].'.sessions', '--ignore-table='.$db['database'].'.cache', '--ignore-table='.$db['database'].'.cache_locks',
            '--ignore-table='.$db['database'].'.jobs', '--ignore-table='.$db['database'].'.failed_jobs', '--ignore-table='.$db['database'].'.job_batches',
            $db['database']];
        $p = new Process($cmd);
        $p->setTimeout(120)->run();
        if (! $p->isSuccessful()) {
            $this->error('mysqldump başarısız: '.$p->getErrorOutput());

            return self::FAILURE;
        }
        $sql = preg_replace('/^\/\*!50013 DEFINER.*$/m', '', $p->getOutput());

        $salt = random_bytes(16);
        $iv = random_bytes(16);
        $key = hash_pbkdf2('sha256', $pass, $salt, 100000, 32, true);
        $cipher = openssl_encrypt(gzencode($sql, 9), 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        $hmac = hash_hmac('sha256', $iv.$cipher, $key, true);
        $out = base_path('database/veri.enc');
        file_put_contents($out, 'TTBV1'.$salt.$iv.$hmac.$cipher);

        $this->info(sprintf('Paket yazıldı: %s (%s, %d tablo satırı)', $out, round(filesize($out) / 1024).' KB', substr_count($sql, 'INSERT INTO')));

        return self::SUCCESS;
    }
}
