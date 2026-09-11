<?php
/**
 * TTB Turizm – tek seferlik tarayıcı kurulumu (terminal gerektirmez).
 * Çalışma şartı: uygulama kökünde .env DOSYASI YOK. Kurulum bitince kendini kurulum.php.tamam olarak yeniden adlandırır.
 * Adres: https://alanadiniz/kurulum.php
 */
declare(strict_types=1);
mb_internal_encoding('UTF-8');
$root = dirname(__DIR__);
$envFile = $root.'/.env';
$self = __FILE__;

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
function layout(string $title, string $body): void {
    echo '<!doctype html><html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.h($title).'</title>
<style>body{font-family:-apple-system,Segoe UI,Roboto,Arial,sans-serif;background:#f4f6fb;color:#0f172a;margin:0;padding:32px 16px}.box{max-width:720px;margin:0 auto;background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:28px}h1{font-size:20px;margin:0 0 6px}p{color:#475569;line-height:1.5}label{display:block;font-size:12px;font-weight:600;color:#475569;margin:14px 0 4px}input{width:100%;box-sizing:border-box;border:1px solid #cbd5e1;border-radius:10px;padding:10px 12px;font-size:14px}.row{display:grid;grid-template-columns:1fr 1fr;gap:14px}.btn{background:#1e6ff2;color:#fff;border:0;border-radius:10px;padding:12px 18px;font-weight:600;font-size:14px;cursor:pointer;margin-top:20px}.ok{background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46;padding:12px 14px;border-radius:10px}.err{background:#fef2f2;border:1px solid #fecaca;color:#991b1b;padding:12px 14px;border-radius:10px;white-space:pre-wrap}h2{font-size:14px;margin:26px 0 0;color:#1e6ff2;text-transform:uppercase;letter-spacing:.08em}code{background:#f1f5f9;padding:2px 6px;border-radius:6px}small{color:#64748b}</style></head><body><div class="box">'.$body.'</div></body></html>';
}

if (is_file($envFile) && ! isset($_GET['zorla'])) {
    layout('Kurulum tamamlanmış', '<h1>Kurulum zaten yapılmış</h1><p><code>.env</code> dosyası mevcut. Güvenlik için bu sayfa devre dışı. Yeniden kurmak için sunucudaki <code>.env</code> dosyasını silin.</p><p><a href="/">Uygulamaya git →</a></p>');
    exit;
}

$errors = [];
$log = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $f = fn ($k, $d = '') => trim((string) ($_POST[$k] ?? $d));
    $appUrl = rtrim($f('app_url'), '/');
    $dbHost = $f('db_host', 'localhost'); $dbName = $f('db_name'); $dbUser = $f('db_user'); $dbPass = (string) ($_POST['db_pass'] ?? '');
    $adminName = $f('admin_name', 'Yönetici'); $adminUser = mb_strtolower($f('admin_user', 'admin')); $adminEmail = mb_strtolower($f('admin_email')); $adminPass = (string) ($_POST['admin_pass'] ?? '');

    if (! preg_match('#^https?://#', $appUrl)) $errors[] = 'Site adresi https:// ile başlamalı.';
    if ($dbName === '' || $dbUser === '') $errors[] = 'Veritabanı adı ve kullanıcısı zorunlu.';
    if ($adminEmail === '' || strlen($adminPass) < 6) $errors[] = 'Yönetici e-postası ve en az 6 karakterli şifre zorunlu.';

    $pdo = null;
    if (! $errors) {
        try {
            $pdo = new PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $log[] = 'Veritabanı bağlantısı başarılı.';
        } catch (Throwable $e) {
            $errors[] = 'Veritabanına bağlanılamadı: '.$e->getMessage();
        }
    }

    if (! $errors) {
        // .env yaz
        $key = 'base64:'.base64_encode(random_bytes(32));
        $tpl = is_file($root.'/.env.example') ? file_get_contents($root.'/.env.example') : '';
        $set = function (string $env, string $k, string $v): string {
            $v = preg_match('/[\s#"\']/', $v) ? '"'.addslashes($v).'"' : $v;
            return preg_match("/^{$k}=.*$/m", $env) ? preg_replace("/^{$k}=.*$/m", "{$k}={$v}", $env) : $env."\n{$k}={$v}";
        };
        $env = $tpl;
        foreach (['APP_NAME' => 'TTB Turizm Personel Yönetimi', 'APP_ENV' => 'production', 'APP_KEY' => $key, 'APP_DEBUG' => 'false', 'APP_URL' => $appUrl, 'APP_TIMEZONE' => 'Europe/Istanbul', 'APP_LOCALE' => 'tr',
                 'LOG_CHANNEL' => 'single', 'LOG_LEVEL' => 'error', 'DB_CONNECTION' => 'mysql', 'DB_HOST' => $dbHost, 'DB_PORT' => '3306', 'DB_DATABASE' => $dbName, 'DB_USERNAME' => $dbUser, 'DB_PASSWORD' => $dbPass,
                 'SESSION_DRIVER' => 'file', 'CACHE_STORE' => 'file', 'QUEUE_CONNECTION' => 'sync', 'SESSION_SECURE_COOKIE' => str_starts_with($appUrl, 'https') ? 'true' : 'false'] as $k => $v) {
            $env = $set($env, $k, $v);
        }
        if (file_put_contents($envFile, $env) === false) {
            $errors[] = '.env yazılamadı (klasör yazma izni?).';
        } else {
            $log[] = '.env oluşturuldu.';
        }
    }

    if (! $errors) {
        // İsteğe bağlı SQL yedeği (yerel verilerin taşınması)
        if (! empty($_FILES['sql']['tmp_name']) && is_uploaded_file($_FILES['sql']['tmp_name'])) {
            try {
                $sql = file_get_contents($_FILES['sql']['tmp_name']);
                $my = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
                $my->set_charset('utf8mb4');
                if (! $my->multi_query($sql)) throw new RuntimeException($my->error);
                do { if ($r = $my->store_result()) $r->free(); } while ($my->more_results() && $my->next_result());
                if ($my->error) throw new RuntimeException($my->error);
                $log[] = 'SQL yedeği içe aktarıldı.';
            } catch (Throwable $e) {
                $errors[] = 'SQL yedeği yüklenemedi: '.$e->getMessage();
            }
        }
    }

    if (! $errors) {
        // Laravel'i yükle: migrate + yönetici + ayarlar
        try {
            foreach (['storage/framework/cache/data', 'storage/framework/sessions', 'storage/framework/views', 'storage/logs', 'bootstrap/cache'] as $d) {
                if (! is_dir("$root/$d")) @mkdir("$root/$d", 0775, true);
            }
            require $root.'/vendor/autoload.php';
            $app = require $root.'/bootstrap/app.php';
            $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
            $kernel->call('migrate', ['--force' => true]);
            $log[] = 'Veritabanı tabloları hazır. '.trim($kernel->output());
            $user = App\Models\User::firstOrNew(['username' => $adminUser]);
            $user->name = $adminName; $user->email = $adminEmail; $user->role = 'admin';
            $user->password = Illuminate\Support\Facades\Hash::make($adminPass); $user->email_verified_at = now();
            $user->save();
            $log[] = "Yönetici hesabı hazır: {$adminUser}";
            $kernel->call('config:clear'); $kernel->call('view:clear');
        } catch (Throwable $e) {
            $errors[] = 'Kurulum adımı başarısız: '.$e->getMessage();
        }
    }

    if (! $errors) {
        @rename($self, $self.'.tamam');
        layout('Kurulum tamamlandı', '<h1>Kurulum tamamlandı 🎉</h1><div class="ok">'.implode('<br>', array_map('h', $log)).'</div><p>Bu sayfa devre dışı bırakıldı (<code>kurulum.php.tamam</code>). Giriş için kullanıcı adı: <b>'.h($adminUser).'</b></p><p><a class="btn" style="display:inline-block;text-decoration:none" href="'.h($appUrl).'/giris">Giriş sayfasına git →</a></p>');
        exit;
    }
    if (is_file($envFile) && $errors) { @unlink($envFile); }
}

$v = fn ($k, $d = '') => h((string) ($_POST[$k] ?? $d));
$guessUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http').'://'.($_SERVER['HTTP_HOST'] ?? 'alanadiniz.com');
layout('TTB Turizm Kurulum', '<h1>TTB Turizm – Kurulum</h1><p>Bilgileri doldurun, tek tıkla kurulsun. cPanel &gt; MySQL Veritabanları bölümünden bir veritabanı ve kullanıcı oluşturup <b>tüm yetkileri</b> verin.</p>'
 .($errors ? '<div class="err">'.h(implode("\n", $errors)).'</div>' : '')
 .'<form method="post" enctype="multipart/form-data">
 <h2>Site</h2>
 <label>Site adresi</label><input name="app_url" value="'.$v('app_url', $guessUrl).'" placeholder="https://personel.ttbturizm.com">
 <h2>Veritabanı (cPanel &gt; MySQL Veritabanları)</h2>
 <div class="row"><div><label>Sunucu</label><input name="db_host" value="'.$v('db_host', 'localhost').'"></div><div><label>Veritabanı adı</label><input name="db_name" value="'.$v('db_name').'" placeholder="kullanici_ttb"></div></div>
 <div class="row"><div><label>Kullanıcı</label><input name="db_user" value="'.$v('db_user').'" placeholder="kullanici_ttb"></div><div><label>Şifre</label><input name="db_pass" type="password"></div></div>
 <h2>Yönetici hesabı</h2>
 <div class="row"><div><label>Ad Soyad</label><input name="admin_name" value="'.$v('admin_name', 'Ayşenur Miran').'"></div><div><label>Kullanıcı adı</label><input name="admin_user" value="'.$v('admin_user', 'ayse').'"></div></div>
 <div class="row"><div><label>E-posta</label><input name="admin_email" type="email" value="'.$v('admin_email').'"></div><div><label>Şifre</label><input name="admin_pass" type="password"></div></div>
 <h2>Veri taşıma (isteğe bağlı)</h2>
 <label>Yerel veritabanı yedeği (.sql)</label><input name="sql" type="file" accept=".sql"><small>Bilgisayarınızdaki personel, bordro ve ayar verilerini taşımak için yedek dosyasını seçin. Boş bırakırsanız sistem boş kurulur; yedekteki kullanıcılar da gelir, yukarıdaki yönetici hesabı üzerine yazılır/eklenir.</small>
 <br><button class="btn" type="submit">Kur</button></form>');
