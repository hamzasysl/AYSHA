# TTB Turizm – Personel Yönetimi (proje kodu: AYSHA)

Temizlik şirketleri için hafif bir personel yönetim / CRM uygulaması.

## Özellikler
- **Personel**: kimlik, iletişim, görev, işe giriş/çıkış, maaş, banka & IBAN (tek tıkla kopyala), acil durum kişisi, notlar. Arama ve aktif/pasif filtresi.
- **Maaş Ödemeleri**: ay seçip tek tıkla bordro oluştur (tüm aktif personel), prim / avans / kesinti ile net hesaplama, tam veya kısmi ödeme, ödeme yöntemi ve tarihi, "tümünü ödendi işaretle".
- **Muhasebe**: yemek ücreti, yol parası, temizlik gideri ve diğer giderler tek sayfada; ay seçimi, kategori/personel filtresi, toplu kayıt (tüm ekibe yemek ücreti gibi), ödendi/bekliyor, personel bazlı aylık özet ve aylık toplam maliyet (bordro + giderler).
- **Fazla ödeme takibi**: elden fazla verilen tutar (örn. 6.500 verildi, maaş 6.485) otomatik "fazla ödeme · iade bekleniyor" olur, personel getirince "İade alındı" ile kapatılır. Maaş, yemek ve yol için aynı.
- **Notlar**: personel ve gider kayıtlarına sınırsız not; her not yazar, tarih ve saat ile zaman çizelgesinde.
- **Emekli / ödenekler**: personelde emekli etiketi; aylık yemek (7.800 ₺) ve yol (3.628 ₺) standartları Ayarlar'da, emekli için ayrı tutar; personel bazında özel tutar girilebilir. Muhasebe'de tek tıkla tüm ekibe aylık yemek & yol kaydı.
- **Garanti banka dosyası**: maaş sayfasından bankanın "TGB Yeni Maaş Dosyası" Excel'ini indir, Garanti internet şubesine yükle. Aynı formattaki dosyadan personel ve aylık maaş aktarımı: `php artisan aysha:import-garanti dosya.xlsx --period=2026-07`.
- **Performans**: 3 kriter (devam, iş kalitesi, tutum) + genel puan (1-10), güçlü yönler / gelişim alanları, personel sıralaması.
- **Raporlar**: yıllık aylık bordro özeti, personel bazlı yıllık özet, en iyi / dikkat gerektiren personel, yazdırma.
- **Genel Bakış**: aylık maaş yükü, bu ay ödenen / bekleyen, son 6 ay trendi, son değerlendirme ve notlar.

## Kurulum
```bash
composer install
npm install && npm run build
cp .env.example .env && php artisan key:generate   # .env içinde DB bilgilerini düzenle
php artisan migrate --seed                          # yönetici: admin@aysha.test / password
```
İlk girişten sonra şifreyi değiştirmek için `.env` içine `ADMIN_EMAIL` / `ADMIN_PASSWORD` yazıp seeder'ı tekrar çalıştırabilir
ya da `php artisan tinker` ile `User::first()->update(['password' => 'yeni'])` diyebilirsiniz.

Demo verisi (20 personel, 3 aylık bordro, değerlendirmeler):
```bash
php artisan db:seed --class=DemoSeeder
```

Testler:
```bash
php artisan test
```

## Teknoloji
Laravel 13 · MySQL · Blade · Alpine.js · Tailwind v4 · Tom Select · Flatpickr · Vite
