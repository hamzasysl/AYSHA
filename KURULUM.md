# cPanel'e Git ile Kurulum (terminal gerektirmez)

## 1. PHP sürümü
cPanel > **MultiPHP Manager** > alan adını seçin > **PHP 8.3** (veya üstü). Gerekli eklentiler genelde açıktır:
`mbstring, openssl, pdo_mysql, bcmath, ctype, fileinfo, tokenizer, xml, zip, gd`. Eksikse **Select PHP Version / PHP Extensions**'tan açın.

## 2. Veritabanı
cPanel > **MySQL Veritabanları**: bir veritabanı (`kullanici_ttb`) ve bir kullanıcı oluşturun, kullanıcıyı veritabanına **ALL PRIVILEGES** ile ekleyin. Adı, kullanıcıyı ve şifreyi not alın.

## 3. Depoyu bağlayın
cPanel > **Git™ Version Control** > **Create**:
- Clone URL: `https://github.com/hamzasysl/AYSHA.git`
- Repository Path: `repositories/aysha`
- **Create** → sonra **Manage** > **Pull or Deploy** > **Deploy HEAD Commit**.

Bu işlem `.cpanel.yml` sayesinde uygulamayı `/home/KULLANICI/personel` klasörüne kopyalar (vendor ve derlenmiş asset'ler repoda hazır; composer/npm gerekmez).

## 4. Alan adı / alt alan adı
cPanel > **Domains** (veya Subdomains) > `personel.alanadiniz.com` oluşturun ve **Document Root** olarak `personel/public` yazın.
Ana alan adının belge kökü değiştirilemiyorsa: `.cpanel.yml` içindeki `DEPLOYPATH` satırını `$HOME/public_html` yapın; kökteki `.htaccess` istekleri `public/` altına yönlendirir.

## 5. Tarayıcıdan kurulum
`https://personel.alanadiniz.com/kurulum.php` adresini açın. Site adresi ve veritabanı bilgilerini girin. **Veri paketi şifresi** alanına size verilen `TTB-...` şifresini yazın: bilgisayardaki tüm veriler (personel, bordro, ayarlar, kullanıcılar ve şifreleri) aynen sunucuya gelir; yönetici alanlarını boş bırakabilirsiniz. **Kur**'a basın: `.env` yazılır, veriler yüklenir, sayfa kendini kapatır.

Veri paketi `database/veri.enc` dosyasıdır (AES-256, repoda şifreli durur). Yerel veriyi yeniden paketlemek için: `php artisan aysha:veri-paketle --sifre="TTB-..."` ve commit.

## 6. Güncelleme (terminal gerekmez)
1. cPanel > Git™ Version Control > Manage > **Update from Remote** > **Deploy HEAD Commit**.
2. Uygulamaya girip **Ayarlar > Sistem > Güncellemeleri uygula** düğmesine basın.

İkinci adım veritabanına eklenen yeni alanları uygular ve önbelleği temizler. Bekleyen güncelleme varsa aynı sayfada sarı uyarı olarak görünür. Sadece görünüm değiştiyse "Önbelleği temizle" yeterlidir.

Sunucuda `composer` ve `npm` gerekmez: `vendor/` ve derlenmiş `public/build/` dosyaları depoda hazır gelir. `.env` ve `storage` klasörü deploy sırasında korunur.
## Notlar
- `.env` asla repoya girmez; sunucuda kurulum sayfası oluşturur.
- Sorun olursa `personel/storage/logs/laravel.log` dosyasına bakın.

## Güvenlik
Kurulum bittikten sonra `public/kurulum.php` dosyasını sunucudan **silin** (cPanel > File Manager). Dosya `.env` varken kendini kapatır, ama depo herkese açık olduğu için tamamen kaldırmak en güvenlisidir. Yeniden kurulum gerekirse dosyayı GitHub'dan tekrar indirip yükleyebilirsiniz.
