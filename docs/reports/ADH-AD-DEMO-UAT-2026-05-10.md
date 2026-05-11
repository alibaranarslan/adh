# ADH Reklam Demo UAT - 2026-05-10

## Karar

Durum: **PASS - demo reklam kayıtları public yüzeyde çalışıyor.**

Bu turda gerçek reklam veren verisi olmadığı için `ADH-DEMO-REKLAM-*` prefix'iyle ayırt edilebilir demo banner kayıtları oluşturuldu. Amaç, admin kaynaklı reklam kayıtlarının public sitede slotlara doğru yerleştiğini müşteri sunumu öncesi görsel olarak doğrulamaktır.

## Oluşturulan Demo Kayıtlar

- `ADH-DEMO-REKLAM-Header` -> `header`
- `ADH-DEMO-REKLAM-Haberler-Arasi` -> `between-news`
- `ADH-DEMO-REKLAM-Sidebar-Ust` -> `sidebar-top`
- `ADH-DEMO-REKLAM-Sidebar-Alt` -> `sidebar-bottom`
- `ADH-DEMO-REKLAM-Haber-Ustu` -> `article-top`
- `ADH-DEMO-REKLAM-Haber-Alti` -> `article-bottom`
- `ADH-DEMO-REKLAM-Footer` -> `footer`

Görseller:

- `storage/app/public/advertisements/adh-demo-header.png`
- `storage/app/public/advertisements/adh-demo-between-news.png`
- `storage/app/public/advertisements/adh-demo-sidebar-top.png`
- `storage/app/public/advertisements/adh-demo-sidebar-bottom.png`
- `storage/app/public/advertisements/adh-demo-article-top.png`
- `storage/app/public/advertisements/adh-demo-article-bottom.png`
- `storage/app/public/advertisements/adh-demo-footer.png`

## HTTP Kanıtları

- `GET /`: `200`
- `GET /mardinde-sampiyonluk-gecesinin-adresi-mardian-mall-oldu`: `200`
- `GET /storage/advertisements/adh-demo-header.png`: `200`, `image/png`

Ana sayfa slotları:

- `header`: present, image present
- `between-news`: present, image present
- `sidebar-top`: present, image present
- `sidebar-bottom`: present, image present
- `footer`: present, image present

Haber detay slotları:

- `article-top`: present, image present
- `article-bottom`: present, image present

## Browser Kanıtları

Desktop in-app browser:

- Ana sayfa title: `Adıyaman Dijital Haber`
- `header`, `sidebar-top`, `sidebar-bottom` görünür.
- `between-news` ve `footer` DOM/HTML içinde doğru image path ile mevcut; viewport dışında kaldığı için ilk ekran görüntüsünde görünmez.
- Browser console ilgili hata/uyarı: `0`

Haber detay desktop:

- `header` ve `article-top` görünür.
- `article-bottom` ve `footer` DOM/HTML içinde doğru image path ile mevcut; sayfanın alt bölümünde kalır.
- Browser console ilgili hata/uyarı: `0`

Mobil headless Chrome, `390x1400`:

- Ana sayfada `header` reklam banner'ı mobil genişliğe sığıyor.
- Haber detayda `header` reklam banner'ı başlık alanı öncesinde mobilde okunur.
- Cookie consent banner mobil alt alanı kapatıyor; bu reklam sisteminden bağımsız mevcut davranıştır.

## Admin Panel Notu

Reklam kayıtları admin resource modeline doğrudan DB üzerinden eklendiği için admin panelde şu adreste listelenebilir:

- `/admin/advertisements`

Bu turda reklam admin ekranındaki görünür Türkçe label'lar da düzeltildi:

- `Reklam Adı`
- `Tür`
- `Banner Görseli`
- `Tıklama Linki`
- `Başlangıç Tarihi`
- `Bitiş Tarihi`
- `Gösterim`
- `Tıklama`

Gerçek reklam veren bilgisi geldiğinde bu demo kayıtları silinip aynı pozisyonlara gerçek görsel/link veya AdSense slot değerleri girilmelidir.

## Admin CRUD Kontrolü

Tarayıcıda `/admin/login` sayfası `200` döndü ve form görünür doğrulandı. Browser runtime, `type="email"` input'a yazı girerken hata verdiği için UI login tamamlanamadı. Bu nedenle CRUD güvence testi Filament Livewire katmanında gerçek admin yetkisiyle çalıştırıldı.

Eklenen test:

- `tests/Feature/Filament/AdvertisementResourceCrudTest.php`

Kapsanan akış:

- Reklam oluşturma
- Reklam düzenleme
- Reklam silme
- Banner türü, pozisyon, link, aktiflik ve sıra alanlarının persist kontrolü

Geçici QA admin hesabı:

- `adh-admin-qa@example.test` oluşturuldu.
- Test sonrası silindi.

## Demo Kayıtlarını Kaldırma Komutu

```bash
php artisan tinker --execute "App\Models\Advertisement::query()->where('name', 'like', 'ADH-DEMO-REKLAM-%')->delete();"
```

Demo görsellerini kaldırmak için:

```powershell
Remove-Item -LiteralPath storage\app\public\advertisements\adh-demo-*.png -Force
```

## Kalan Risk

- Gerçek reklam veren tasarımları gelmeden nihai ticari görsel uyum kararı verilemez.
- Mobil alt reklamlar cookie banner kapatıldıktan ve sayfa aşağı kaydırıldıktan sonra manuel olarak tekrar kontrol edilmeli.
- Reklam paket/fiyatlandırma ve pozisyon satış stratejisi operasyonel karardır; kod tarafında engel görünmüyor.

## Son Test

```bash
php artisan test tests\Feature\Filament\AdvertisementResourceCrudTest.php tests\Feature\Public\PublicPagesTest.php tests\Unit\Services\HomeModuleDataServiceTest.php tests\Feature\Filament\AdminMediaUploadHardeningTest.php tests\Feature\Filament\ContentOperationsAndLayoutTest.php
```

Sonuç:

- `29 passed`
- `180 assertions`
