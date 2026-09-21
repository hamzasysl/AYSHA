# TTB Turizm – Personel Yönetimi (proje kodu: AYSHA)

Temizlik şirketi için küçük bir personel/CRM uygulaması: personel kartları, maaş & banka bilgileri,
aylık bordro takibi (ödendi / kısmi / bekliyor), performans değerlendirmeleri, notlar ve raporlar.

## Stack
- Laravel 13 (PHP 8.4), MySQL (`aysha` veritabanı), Valet: http://aysha.test
- URL'ler TÜRKÇE (/personel, /muhasebe, /maas, /izinler, /performans, /raporlar, /ayarlar, /notlar, /giris, /genel-bakis; create→olustur, edit→duzenle via `Route::resourceVerbs`), rota ADLARI İngilizce (`employees.index` vb.). Testlerde ve JS'de Türkçe yolları kullan; not tipleri (`/notlar/employees/{id}`) İngilizce kalır.
- Blade + Alpine.js + Tailwind v4 (Vite), Font Awesome CDN. Livewire/Inertia yok.
- Auth: kendi yazdığımız `LoginController` (sadece giriş/çıkış, kayıt yok). Form alanı `login`: e-posta VEYA kullanıcı adı (`users.username`, küçük harfe çevrilir). Kullanıcılar seeder ile oluşturulur (`ADMIN_EMAIL`, `ADMIN_USERNAME`, `ADMIN_PASSWORD`).
- Testler: PHPUnit, SQLite in-memory (`php artisan test`).
- Saat dilimi `Europe/Istanbul` (`APP_TIMEZONE`); tarih/saatler DB'de Türkiye saatiyle tutulur.
- Personelde yemek/yol alanı formda hep dolu görünür; girilen tutar geçerli varsayılana eşitse `null` saklanır (Ayarlar'ı takip eder), farklıysa özel değer olur.

## Dağıtım (cPanel, Git, terminal yok)
- Bkz. `KURULUM.md`. `vendor/` ve `public/build/` REPOYA DAHİL (sunucuda composer/npm yok): kod değişince `npm run build` yap ve vendor değişiklikleriyle birlikte commit et. `composer.json` platform php 8.3.0 (sunucu PHP 8.3); 8.4 isteyen paket ekleme.
- Gerçek veri sunucuya `database/veri.enc` ile taşınır (AES-256-CBC + PBKDF2 + HMAC, `php artisan aysha:veri-paketle --sifre=...`); şifre kullanıcıda, repoya/memory'ye yazma. Veri değişince yeniden paketle ve commit et.
- Sunucuda terminal yok: deploy sonrası migration ve önbellek temizliği **Ayarlar > Sistem** sekmesinden ya da doğrudan `/ayarlar/sistem` sayfasından (`system.index`, kartlar `settings/_system.blade.php` partial'ı) yapılır (`SystemController`, `can:manage`). Yeni migration eklediğinde kullanıcıya bu düğmeye basmasını söyle.
- ÖNEMLİ: Ayarlar sayfası yeni bir tabloya/kolona bağımlı OLMAMALI — migration çalıştırma düğmesi orada olduğu için sayfa patlarsa kullanıcı kilitleniyor. Yeni tablo okuyacaksan `Schema::hasTable()` ile koru (örn. `login_logs`), ayrıca `/ayarlar/sistem` yedek sayfası her zaman ayakta kalmalı. Bekleyen migration varsa ayarlar sayfasının üstünde sarı uyarı çıkar.
- Blade dosyasına yeni bir Tailwind sınıfı yazdıysan `npm run build` ZORUNLU: Tailwind v4 sınıfları derleme anında blade'lerden tarar, build almazsan sınıf CSS'te olmaz (sessizce çalışmaz).
- `.cpanel.yml` deploy görevleri, `public/kurulum.php` tek seferlik tarayıcı kurulumu (.env yoksa çalışır, bitince `.tamam` olur). `.env` ve `*.sql` asla commit edilmez. Repo PUBLIC: gerçek veri (dump) repoya girmez.

## Komutlar
```bash
composer install && npm install
php artisan migrate --seed            # sadece yönetici hesabı (ADMIN_* env'den; canlıda kullanıcı adı: ayse)
php artisan db:seed --class=DemoSeeder # 20 kişilik demo ekip + 3 aylık bordro + değerlendirmeler
npm run build                          # veya npm run dev
php artisan test
```

## Yapı
- `app/Models`: `Employee` (personel, soft delete), `SalaryPayment` (dönem bazlı bordro satırı),
  `Expense` (muhasebe: yemek / yol / temizlik / diğer), `PerformanceReview`, `Note` (polimorfik, `HasNotes` trait'i).
- `SalaryPayment::saving` ve `Expense::saving` içinde `net_amount`, `status`, `paid_at`, iade alanları otomatik hesaplanır; elle set etme.
- Performans aylık: `performance_reviews.period_year/period_month` = değerlendirilen ay, personel+dönem unique. Varsayılan dönem `PerformanceReview::defaultPeriod()` (geçen ay). Performans sayfası dönem tablosudur (tüm aktif personel, değerlendirildi/bekliyor); `review_date` sadece giriş tarihi.
- Fazla ödeme (para üstü): `paid_amount > net/amount` ise `overpaid`, `refund_amount` ile `refund_pending` hesaplanır; "İade alındı" aksiyonları `payments.refund` / `expenses.refund`.
- Not tasarımı mezeSoft'tan: kart, büyük metin, altında mavi yazar · 'x ay önce' · tarih-saat, sağda gri kalem / kırmızı çöp kutusu; altta 'Yeni not yaz...' + '+' butonu. `x-notes-modal` (başlıklı pencere) muhasebe ve izin satırlarında; personel Notlar sekmesinde panel doğrudan.
- İzinler sayfası sadece kayıt listesi (bakiye tablosu YOK; yıllık izin bakiyesi personel listesinde ve personel detayında).
- Notlar: `POST /notlar/{type}/{id}` (`employees`, `expenses`, `leaves`), `PATCH/DELETE /notes/{id}`; JSON isteğinde JSON döner. `x-notes-panel` AJAX'lı Alpine bileşeni (`window.notesPanel` app.js'de): ekle/düzenle/sil sayfa yenilemeden, pencere kapanmaz; `key` prop'u ile `notes-count` olayı yayınlar (satır sayaçları için). Tek "genel not" alanı yok.
- UI bileşenleri: `x-ui-select` (Tom Select, `data-create="true"` ile serbest giriş), `x-date-input` (flatpickr, Türkçe, gönderilen Y-m-d).
  Ham `<select>` veya `type="date"` kullanma. Satır başına modallar `<template x-if>` ile tembel yüklenir (performans).
- Blade: iki direktifi BİTİŞİK yazma (`@endif@if(...)`) — Blade'in `\B@` kuralı yüzünden ikincisi direktif sayılmaz, "unexpected endif" parse hatası verir; araya boşluk/satır sonu koy. Görünüm değiştirince `php artisan view:clear && php artisan test` ile derlenmesini doğrula.
- Blade: HTML tag'inin İÇİNDE `@if/@can/@cannot` ile attribute ekleme (`<div ... @cannot('x') style=... @endcannot>`) KULLANMA; `class="... {{ $cond ? '' : 'hidden' }}"` yaz. Yeni görünüm/partial yazınca `SCRATCH/dump.php` ile render edip tag dengesini kontrol et (bir eksik `>` tüm sayfayı kaydırır).
- Modal yüksekliği: form içeren pencerelerde iç panele `max-h-[calc(100vh-2rem)] overflow-y-auto` ver (overlay'i kaydırılabilir yapmak veya `my-auto` YETMEZ — auto margin scrollHeight'a girmiyor, Kaydet düğmesi küçük ekranda erişilemez kalıyordu). Tom Select `dropdownParent: body` olduğu için açılır menüler panel taşmasından etkilenmez.
- Modal kapatma: overlay'e `@click.self="open = false"`. ASLA `@click.outside` kullanma: gerçek (trusted) tıklamada Alpine efektleri listener'lar arasında flush olduğu için açılan modal aynı tıklamayla anında kapanıyor (JS `.click()` ile test edilince görünmez, sadece gerçek fare tıklamasında çıkar).
- Sidebar beyaz, daraltılabilir (localStorage `aysha_sidebar`); logo düz kalın "AYSHA." yazısı (nokta brand mavisi).
- Tasarım dili (2026-09-11, MEZ Sosyal ekran görüntülerinden): Plus Jakarta Sans, mavi #1e6ff2 (`brand-600`), zemin #f4f6fb. Sol menü: her madde kart (rounded-2xl, border), solda renkli ikon kutusu, kalın başlık + gri alt yazı; aktif madde dolu mavi. Logo: yuvarlak 'TTB' amblem + aralıklı büyük harf 'TTB TURİZM'.
  Her sayfa başlığı ayrı kart: küçük mavi bölüm adı, 3xl kalın başlık, gri açıklama, sağda aksiyonlar (`x-app-layout` `title/subtitle/section` + `actions` slot). Kart başlıklarındaki `<h2><i>` ikonu CSS ile mavi kutu olur. Stat kartları renkli çerçeveli (`x-stat-card color=`). Butonlar h-11 rounded-xl, ikon butonları h-10 rounded-xl çerçeveli.
- Tasarım sistemi `resources/css/app.css` içinde (`brand` 50–950, `shadow-card / shadow-pop`). Boyutlar kompakt: butonlar/inputlar h-10, ikon butonları h-9, sayfa başlığı text-xl, stat kart değeri text-lg, menü yazıları 13/11px.
- Karanlık mod: `<html class="dark">`, localStorage `aysha_theme`; `window.applyTheme/toggleTheme` (app.js), head'de erken uygulama script'i, sidebar altında 'Karanlık mod' kartı. Dark stiller `app.css` sonunda katman dışı `.dark .sınıf` ezmeleri (yeni bir açık renk sınıfı kullanınca oraya karşılığını ekle). Kart başlık ikonu kuralı (`.card h2 > i:first-child`) katman DIŞINDA olmalı, yoksa Font Awesome ezer.
  Renk sınıfları: `brand-*` (asla indigo/blue), gri için `slate-*` (gray değil).
- Rozetler açık renkli zemin (`.badge-ok` yeşil, `-warn` sarı, `-danger` kırmızı, `-info` mor), filtreler `.chip` / `.chip-active` (aktif mavi), satır işlemleri ikon butonları.
- Para alanları formdan Türkçe biçimde gelir ("25.000,00"); FormRequest `prepareForValidation` normalize eder.
- Blade direktifleri: `@money($x)` → `1.234,50 ₺`, `@date($x)` → `04.09.2026`.
- Türkçe validation mesajları `lang/tr/validation.php` içinde; yeni kural kullanınca oraya ekle.
- Tailwind v4: Blade'de dinamik üretilen renk sınıfları `resources/css/app.css` içindeki `@source inline(...)` ile safelist'lenir.

## Muhasebe = tek sayfa
- `/expenses` tek muhasebe sayfası: sekmeler Tüm Giderler / Maaşlar (`category=salary`) / Yemek / Yol / Temizlik / Diğer. Üstte dönem toplamları: toplam maliyet, toplam giden (ödenen), toplam kalan (maaş + giderler).
- Maaş sekmesi `resources/views/payments/_section.blade.php` partial'ı; veri `app/Support/PayrollPeriod::data()`. `/payments` rotası `/expenses?category=salary`'ye yönlendirir, aksiyon rotaları (`payments.*`) aynen duruyor. Menüde ayrı "Maaş Ödemeleri" YOK.
- Toplu işlemler: hem gider hem maaş tablosunda ilk kolon seçim kutusu (`.row-check`, başlıkta tümünü seç), seçim olunca ekranın altında sabit çubuk (Ödendi yap / Bekliyor yap / Toplu düzenle / Sil). Rotalar `expenses.bulk-status|bulk-update|bulk-destroy` ve `payments.bulk-status|bulk-update|bulk-destroy` (`can:edit` / `can:delete`). Toplu düzenlemede BOŞ alan "değiştirme" demektir; tutarlar kişi başıdır. Toplu silme model olayları çalışsın diye tek tek `delete()` eder (notlar da silinsin). Seçim listesi filtreye uyan satırlardır (`$expenses`/`$payments`), sayfalama yok.
- Tabloya kolon eklerken `colspan`'leri de güncelle (boş satır, tfoot); seçim kolonu `@can('edit')` ile geldiği için colspan'ler `auth()->user()->can('edit') ? n+1 : n` biçimindedir. Tarayıcıda `thead th` / satır hücre / `tfoot` colspan toplamlarının eşit olduğunu doğrula.
- Satır işlemleri ikon-only butonlardır (`.btn-icon` düzenle/notlar, `.btn-icon-danger` sil, `.btn-icon-primary` iade), her birinde `title`. Sil ve iade `x-confirm-form` ile onay penceresi açar (`method`, `variant`, `title`, `message`, `button`).
- Durum (Bekliyor/Ödendi) satırdaki kompakt açılır menüden değiştirilir (`x-ui-select` + `:submit`, `.status-select` sınıfı renkli nokta verir): `POST /expenses/{id}/status`, `POST /payments/{id}/status`. Ayrı "Ödendi" butonu yok.
- Renk semantiği: birincil mavi #0284c7 (`brand-600`), onay/kaydet/ödendi YEŞİL (`.btn-success`, `.btn-icon-primary`), sil KIRMIZI (`.btn-danger`, `.btn-icon-danger`), düzenle koyu gri (`.btn-icon`).

## m² Hesaplayıcı
- `/m2-hesaplayici` (`calculator`, `Route::view`, tüm roller görür): ofis temizlik fiyatı. Tutarlar Ayarlar > **m² Fiyatları** sekmesinden (`settings.pricing`, `Setting` anahtarları `m2_rate` 100, `m2_large_rate` 80, `m2_large_limit` 100, `m2_small_limit` 50; migration gerekmez). Kural `App\Support\M2Pricing::rates()/calculate()`: sınıra kadar standart fiyat, sınır üstü büyük alan fiyatı (101 m² → 8.080 ₺; kullanıcı böyle istedi), küçük alan sınırı altı yine standart fiyat ama "küçük alan" rozeti. Sayfada "m² başı fiyat" elle doldurulursa kuralı ezer. Sayfadaki sınır/örnek metinleri ayarlardan üretilir; sabit sayı yazma.

## İzin & devamsızlık
- `leaves` tablosu: type (annual, unpaid, marriage, birth, death, sick, absence), leave_year, start/end/return_date, days (iş günü, hafta sonu hariç otomatik ama elle değiştirilebilir), deduct_annual.
- Yıllık hak `employees.annual_leave_days` (varsayılan 14, kişi bazında değişir). Bakiye `Employee::leaveBalance($year)` = hak − yıllık izinden düşülen günler. Hak yetmezse `leave_warning` ile geri döner; `force=1` ile "yine de ver".
- `/leaves/{leave}/print` TTB Grup İzin Formu'nu doldurulmuş basar. Rota parametresi `leave` (`->parameters(['leaves' => 'leave'])`, yoksa Laravel `leaf` yapar!).

- Ayarlar sayfası: sol sekmeli düzen (Yemek & Yol / Şirket & Banka / Hesap). Hesap sekmesi `PUT /ayarlar/hesap` (`settings.account`): ad, kullanıcı adı, e-posta, mevcut şifre ile şifre değişimi.

- Yönetilen listeler (`list_items`, Ayarlar > Listeler, `ListItemController`): type = position (görevler), bank (bankalar), leave_type (izin türleri; meta: deduct, icon). `Employee::positions()`, `ListItem::labels('bank')`, `Leave::types()` buradan okur; kodda sabit görev/banka/izin türü listesi KULLANMA. Görev/banka adı değişince personel kartları da güncellenir. Önbellek düz DİZİ tutar (Eloquent model cache'lenmez: dosya cache'inde `__PHP_Incomplete_Class` hatası verir).
- Gider kategorileri VERİTABANINDA (`expense_categories`, Ayarlar > Gider Kategorileri, `ExpenseCategoryController`): slug, label, icon (FA), color, employee_based, is_system. `Expense::categories()` = `ExpenseCategory::map()` (önbellekli, kayıtta forget). Varsayılanlar migration'da: meal, travel, health (Sağlık Raporu), extra, cleaning, other. Kod içinde sabit kategori listesi KULLANMA; raporlar `employee_based` bayrağına göre Ekstra (meal/travel dışı personele bağlı) ve Genel (personelsiz) olarak gruplar. Yeni Kayıt modalında `employee_ids[]` çoklu seçim → her personele ayrı kayıt (`ExpenseController@store`, `array_merge` ile employee_id atanır). Raporlarda Ekstra ayrı kolon.
- Elden yuvarlak ödeme: gider düzenleme penceresinde "Fiilen ödenen" alanına tutardan fazlası girilirse (3.628 yerine 3.650) kayıt Durum "Bekliyor" bırakılsa bile otomatik **ödendi** olur (`Expense::saving`, `isDirty('paid_amount')`); fark `refund_pending` olarak izlenir, para üstü gelince satırdaki iade düğmesiyle kapatılır. Alanı boşaltıp Bekliyor seçilerek geri alınabilir.
- Ödeme yöntemi giderlerde `Expense::METHODS` (Havale/EFT, Nakit, Yemek Kartı); ödendiye çekilince boşsa `Expense::defaultMethod()` atar: yol → nakit (elden), yemek → yemek kartı, kalanı EFT. Bekliyora dönünce yöntem temizlenir. Maaşta `SalaryPayment::METHODS` (EFT/nakit) kalır, yemek kartı yok.
- Sahipsiz kayıt bırakma: `Expense`/`Leave` silinince `deleteNotes()` (HasNotes) notları da siler, `Employee` kalıcı silinince not + değerlendirme gider. Panoda son notlar/değerlendirmeler silinmiş (soft delete dâhil) hedefleri göstermez — yoksa link 404 veriyordu.
- Giderlerde `deduction` (kesinti) + `deduction_note`: `net_amount = amount − deduction`; ödenen/fazla ödeme/toplamlar hep NET üzerinden (`->sum(fn ($x) => $x->net_amount)`, `sum('amount')` kullanma).
- Raporlar: aylık tablo maaş + yemek + yol + temizlik + diğer + kesinti + toplam + ödenen + oran + bekleyen (kategori bazında sayı), yıl maliyet dağılım çubuğu, personel bazlı yıllık maliyet.
- Giriş geçmişi: `login_logs` (`LoginLog::record()` LoginController'da hem başarılı hem hatalı denemeleri yazar; user-agent'tan cihaz/platform/tarayıcı çıkarılır). Ayarlar > Kullanıcılar'da "Son giriş" kolonu ve saat ikonuyla açılan geçmiş penceresi (son 25 kayıt, IP + cihaz + durum).
- Kullanıcılar: Ayarlar > Kullanıcılar (`UserController`, `/ayarlar/kullanicilar`). Roller `User::ROLES`: admin (Tam yetkili) | owner (Patron) = her şey; editor (Düzenleyici) = ekler/düzenler, silemez, ayarlar/kullanıcılar kapalı; viewer (Çalışan) = sadece görüntüler.
  Yetki Gate'leri `edit` / `delete` / `manage` (AppServiceProvider), rotalar `can:edit`, `can:delete`, `can:manage` gruplarında; görünümlerde `@can('edit')` / `@can('delete')` ile butonlar gizlenir, durum select'i yerine rozet. Yeni yazma rotası eklerken doğru gruba koy. UserFactory role=admin. Şifreler hash'li: mevcut şifre gösterilemez, yeni şifre kaydedilince bir kez `shown_password` flash'ı ile ekranda gösterilir; şifre alanlarında göz ikonu.

## Gerçek veri ve banka entegrasyonu
- Gerçek personel Garanti "TGB Yeni Maaş Dosyası" Excel'inden aktarılır: `php artisan aysha:import-garanti dosya.xlsx [--period=2026-07] [--dry-run]`
  (TC → IBAN → ad-soyad ile eşler, var olanı günceller; `--period` verilirse Tutar sütunu o ayın bordrosu olarak işlenir, 0 = bekliyor).
- Muhasebe > Maaşlar sekmesi > "Banka Dosyası": aynı formatta xlsx üretir (`app/Support/GarantiPayrollFile.php`); kurum/şube/hesap `Setting` tablosundan.
- Personelde `is_retired` (emekli), `meal_allowance` / `travel_allowance` (boşsa `Setting` varsayılanları: standart 7.800 / 3.628, emekli ayrı),
  `bank_code` / `branch_code` / `account_no`. Muhasebe > "Yemek & Yol Oluştur" tüm aktif personele aylık kayıt açar (`effective_*_allowance`).
- DemoSeeder sahte veri üretir; canlı DB'de gerçek 25 personel var — demo'yu gerçek verinin üstüne çalıştırma.

## Kurallar
- UI dili Türkçe. Görsel dil: beyaz zemin (#f4f7fb), brand mavisi vurgular, rounded-full filtre chip'leri, Font Awesome ikonlar; her buton/başlıkta ikon kullan.
- SQLite'ta da çalışan sorgular yaz (testler SQLite): `HAVING` without `GROUP BY`, `CONCAT` vb. kullanma.
