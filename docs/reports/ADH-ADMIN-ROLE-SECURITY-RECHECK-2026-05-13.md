# ADH Admin Role Security Recheck - 2026-05-13

## Karar

Durum: **PASS - admin rol matrisi, protected user ve IHA içerik bütünlüğü korumaları çalışıyor.**

Bu turda super admin, editor ve writer sınırları; kullanıcı yönetimi güvenliği; IHA haber
kataloğu koruması ve admin route erişimleri tekrar kontrol edildi. Yeni blocker bulunmadı.
İki bypass ihtimaline karşı regresyon test kapsamı güçlendirildi.

## Doğrulanan Rol Matrisi

### Super Admin

- Admin panelin tüm kritik yüzeylerine erişebilir.
- Sistem ayarları, kullanıcılar, IHA operasyon ekranları, Layout publish ve entegrasyon
  ayarlarını yönetebilir.
- Manuel haber, kategori, tag, sayfa, reklam ve yerel bilgi yönetiminde tam yetkilidir.

### Editor

- Haber, kategori, tag, medya, reklam listesi, yerel bilgi ve analytics yüzeylerine erişebilir.
- Sistem ayarları, kullanıcı yönetimi, IHA health/log ve entegrasyon ayarlarına erişemez.
- Layout Studio taslak düzenleyebilir, fakat canlı publish yapamaz.
- Delete/force-delete seviyesinde geniş sistem yetkisi verilmez.

### Writer

- Haber listesi, haber oluşturma, haber edit, kategori/tag okuma ve tag oluşturma ile sınırlıdır.
- Yayın, vitrin, son dakika, kategori bulk mutation ve sistem ekranlarına erişemez.
- Haber oluştursa bile status `draft` kalır; breaking/featured zorlayamaz.

## Doğrulanan Kritik Korumalar

- Kendi hesabını silme engelli.
- Son aktif `super_admin` hesabını silme/deaktive etme yüzeyleri kapalı.
- Protected kullanıcı içeren bulk delete işlemi tüm seçimi iptal ediyor; protected olmayan
  eş seçili kullanıcılar da aynı işlem içinde silinmiyor.
- IHA haberleri edit sayfasında delete action göstermez.
- IHA haberleri bulk publish/archive/draft/category/featured/breaking/delete işlemleriyle
  değiştirilemez.
- IHA haberleri resource policy düzeyinde delete ve force-delete işlemlerine kapalıdır.
- Force delete any kapalıdır.
- Writer bulk publish/vitrin aksiyonlarını göremez.
- Writer form payload ile `published`, `breaking`, `featured` zorlayamaz.

## Eklenen Test Güvenceleri

Dosyalar:

- `tests/Feature/Filament/AdminRoleResourcePermissionTest.php`
- `tests/Feature/Filament/AdminContentIntegrityTest.php`

Eklenen senaryolar:

- `test_user_bulk_delete_does_not_delete_protected_accounts_or_selected_peers`
  - Protected kullanıcı seçime girerse bulk delete atomik iptal edilir.
  - Aynı seçimdeki diğer kullanıcılar da silinmez.

- `test_iha_articles_cannot_be_deleted_or_force_deleted_by_resource_policy`
  - IHA kayıtları `canDelete` ve `canForceDelete` için false döner.
  - Manuel haber aynı yetkili kullanıcıda delete/force-delete edilebilir.
  - `canForceDeleteAny()` false kalır.

## Test Kanıtı

Rol ve içerik bütünlüğü paketi:

```bash
php artisan test tests/Feature/Filament/AdminRoleResourcePermissionTest.php tests/Feature/Filament/AdminContentIntegrityTest.php tests/Unit/Support/AdminPrivilegesTest.php
```

Sonuç:

- `22 passed`
- `189 assertions`

Admin smoke ve metin kalite paketi:

```bash
php artisan test tests/Feature/Filament/AdminDashboardAndNewsResourceTest.php tests/Feature/Filament/AdminLanguageQualityTest.php tests/Feature/Filament/AdminSettingsOperationsFinalSmokeTest.php
```

Sonuç:

- `7 passed`
- `171 assertions`

Diff kontrolü:

```bash
git diff --check
```

Sonuç:

- whitespace/diff hatası yok

## Production Notu

Kod tarafında admin yetki matrisi için local predeploy blocker yok. Canlıda şu manuel
kanıtlar alınmalıdır:

- Super admin ile `/admin/users` açılır.
- Editor ile sistem ayarları ve kullanıcı yönetimi 403 kalır.
- Writer ile haber publish/vitrin aksiyonları görünmez.
- IHA haber edit sayfasında delete yoktur.
- Son aktif super admin silinemez.

