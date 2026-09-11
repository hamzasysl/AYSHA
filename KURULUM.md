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
`https://personel.alanadiniz.com/kurulum.php` adresini açın. Site adresi, veritabanı bilgileri ve yönetici hesabını girin. Bilgisayardaki verileri taşımak için `.sql` yedeğini seçin (bu repoda yedek yoktur; `mysqldump` ile alınır). **Kur**'a basın: `.env` yazılır, tablolar kurulur, yönetici açılır, sayfa kendini kapatır.

## 6. Güncelleme
GitHub'a push edildikçe cPanel > Git Version Control > Manage > **Update from Remote** > **Deploy HEAD Commit**. `.env` ve `storage` korunur.

## Notlar
- `.env` asla repoya girmez; sunucuda kurulum sayfası oluşturur.
- Sorun olursa `personel/storage/logs/laravel.log` dosyasına bakın.
