# ADH Layout Studio Recheck - 2026-05-13

## Karar

Durum: **PASS - Layout Studio draft/publish modeli ve legacy kapatma davranışı korunuyor.**

Bu turda anasayfa yerleşim yönetimi, draft-publish ayrımı, preview güvenliği ve legacy
LayoutManager yüzeyi tekrar kontrol edildi. Yeni fonksiyonel bug görülmedi; iki kritik
güvence test kapsamına eklendi.

## Doğrulanan Davranışlar

- Layout Studio admin route'u mevcut: `admin/layout-studio`
- Legacy LayoutManager route'u mevcut fakat 410 disabled stub olarak çalışıyor:
  `admin/layout-manager-legacy`
- Legacy LayoutManager mutasyon metodları içermiyor.
- Draft kaydetmek public base state'i ve canlı homepage görünümünü değiştirmiyor.
- Publish işlemi yalnız yayın yetkisi olan kullanıcıda canlı state'e uygulanıyor.
- Editor rolü layout taslağını kaydedebiliyor, fakat canlıya alamıyor.
- Preview URL signed middleware arkasında; unsigned preview erişimi açılmıyor.
- Restore revision yalnız taslağa alıyor; canlıya etkisi publish ile gerçekleşiyor.
- Public homepage layout smoke testleri geçiyor.
- Admin layout kaynaklarında mojibake kalite kapısı temiz.

## Eklenen Test Güvenceleri

Dosya:

- `tests/Feature/Filament/ContentOperationsAndLayoutTest.php`

Eklenen senaryolar:

- `test_editor_can_save_layout_draft_but_cannot_publish_live_state`
  - `editor` rolü draft kaydeder.
  - `editor` publish çağırsa bile `appearance` live state değişmez.
  - `super_admin` tarafından yayınlanmış state korunur.

- `test_layout_preview_requires_signed_url`
  - Normal unsigned preview route 403 döner.
  - `LayoutConfigService::getPreviewUrl()` ile üretilen signed URL 200 döner.

## Test Kanıtı

Komut:

```bash
php artisan test tests/Feature/Filament/ContentOperationsAndLayoutTest.php tests/Unit/Services/LayoutConfigServiceTest.php tests/Feature/Filament/AdminGuideModeTest.php tests/Unit/Support/AdminPrivilegesTest.php
```

Sonuç:

- `17 passed`
- `82 assertions`

Ek public/admin smoke:

```bash
php artisan test tests/Feature/Public/PublicPagesTest.php tests/Feature/Filament/AdminLanguageQualityTest.php
```

Sonuç:

- `21 passed`
- `233 assertions`

Diff kontrolü:

```bash
git diff --check
```

Sonuç:

- whitespace/diff hatası yok

## Production Notu

Layout Studio kod tarafında local predeploy için blocker yok. Canlıda yine de şu manuel
kontroller yapılmalıdır:

- Super admin ile küçük bir taslak değişikliği yap, preview et, sonra publish et.
- Editor rolüyle publish butonunun kısıtlı kaldığını doğrula.
- Publish sonrası homepage görsel bütünlüğünü desktop ve mobilde kontrol et.
- Legacy `admin/layout-manager-legacy` URL'sinin 410 döndüğünü doğrula.

