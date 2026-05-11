# ADH Runtime DB Forensics

## Executive Summary
- Aktif public ADH server `http://127.0.0.1:8000`, `C:\nwp0203\haber-sitesi` içinden çalışan `php artisan serve` process’idir.
- Çalışan Laravel runtime, `.env` dosyasındaki MySQL ayarlarını **kullanmıyor**. Aynı runtime içinden alınan kanıt, efektif `database.default` değerinin `sqlite` olduğunu ve hedef DB’nin `C:\nwp0203\haber-sitesi\database\database.sqlite` olduğunu gösteriyor.
- Config cache kaynaklı bir sapma yok:
  - `config_cached = false`
  - `routes_cached = false`
  - `bootstrap/cache/config.php` yok
  - `bootstrap/cache` altında yalnızca `packages.php` ve `services.php` var
- Aynı runtime bağlamında alınan haber sayımları:
  - `total_news = 0`
  - `published = 0`
  - `source_iha = 0`
  - `source_iha_published = 0`
- Bu nedenle çalışan public ADH server şu anda boş haber verisine bakan bir sqlite runtime ile çalışıyor. Editoryal/UI işi için doğru içerik zemini aktif runtime’ta yok.

## Exact Runtime Config Evidence
### Running public server identity
- Dinleyen process:
  - `C:\xampp\php\php.exe -S 127.0.0.1:8000 ...\vendor\laravel\framework\...\server.php`
- Parent process:
  - `C:\xampp\php\php.exe artisan serve --host=127.0.0.1 --port=8000`
- Runtime health:
  - `GET /health` -> `{"status":"ok","app":"adh","database":"ok","cache":"ok","queue_driver":"database","queue_table_present":true}`

### Effective runtime values from the same Laravel runtime
Kanıt, geçici lokal diagnostic route ile aynı running server içinden alındı. Bu route yalnızca `127.0.0.1` / `::1` için açıktı ve kanıt toplandıktan sonra kaldırıldı.

- `app.env = local`
- `app.url = http://localhost:8000`
- `database.default = sqlite`
- `queue.default = database`
- `cache.default = database`
- `base_path = C:\nwp0203\haber-sitesi`
- `working_directory = C:\nwp0203\haber-sitesi\public`
- `environment_file_path = C:\nwp0203\haber-sitesi\.env`
- `php_binary = C:\xampp\php\php.exe`
- `php_sapi = cli-server`

### Runtime env values seen by the running server
- `APP_ENV = local`
- `APP_URL = http://localhost:8000`
- `DB_CONNECTION = sqlite`
- `DB_HOST = 127.0.0.1`
- `DB_PORT = 3306`
- `DB_DATABASE = C:\nwp0203\haber-sitesi\database\database.sqlite`
- `QUEUE_CONNECTION = database`
- `CACHE_STORE = database`

### Bootstrap/cache inspection
- Runtime içinden:
  - `config_cached = false`
  - `routes_cached = false`
- Filesystem’de:
  - `bootstrap/cache/packages.php`
  - `bootstrap/cache/services.php`
- `bootstrap/cache/config.php` yok
- Sonuç: efektif runtime sapması config cache’ten kaynaklanmıyor.

## Exact DB Target Evidence
- Default runtime connection: `sqlite`
- Runtime `DB::connection()->getDatabaseName()`:
  - `C:\nwp0203\haber-sitesi\database\database.sqlite`
- Driver-specific probe:
  - `PRAGMA database_list`
  - `main -> C:\nwp0203\haber-sitesi\database\database.sqlite`

Bu kanıt aynı running server içinden alındı; shell bootstrap tahmini değil.

## Counts And Sample Records From The Same Runtime
Kaynak: aynı running server içindeki geçici diagnostic route.

- `total_news = 0`
- `total_news_with_trashed = 0`
- `published = 0`
- `source_iha = 0`
- `source_iha_published = 0`

### Latest 10 rows
- Boş dizi. Aynı runtime DB’de örnek haber satırı yok.

### Category distribution by slug
- Boş dizi.

### City distribution by city_slug
- Boş dizi.

Ham kanıt: [ADH-RUNTIME-DB-EVIDENCE.json](/C:/nwp0203/haber-sitesi/handoff/03-quality/release-audit/ADH-RUNTIME-DB-EVIDENCE.json:1)

## Homepage, Category, And City Page Read Proof
Kod-path kanıtı:
- Homepage: [HomePageController.php](/C:/nwp0203/haber-sitesi/app/Http/Controllers/HomePageController.php:1) -> [HomeModuleDataService.php](/C:/nwp0203/haber-sitesi/app/Services/HomeModuleDataService.php:1) -> `NewsArticle::published()` / `Category::active()`
- Category: [NewsController.php](/C:/nwp0203/haber-sitesi/app/Http/Controllers/NewsController.php:1) -> `Category::active()->where('slug', ...)` + `NewsArticle::published()`
- City: [CityController.php](/C:/nwp0203/haber-sitesi/app/Http/Controllers/CityController.php:1) -> `NewsArticle::published()->where('city_slug', ...)`

Runtime response kanıtı:
- Cache-bypass için `?forensics=<timestamp>` ile istek atıldı; böylece `cache.page` middleware yeni key üretti ve eski HTML cache’i bypass edildi.
- Homepage:
  - `GET /?forensics=<ts>` -> `200`
  - Haber section başlıkları (`Son Haberler`, `Günün Önemli Gelişmeleri`, `Adıyaman Gündemi`, `En Çok Okunan`, `Bölge Haberleri`) görünmüyor
  - Article detail link’i tespit edilmedi
- City:
  - `GET /il/adiyaman?forensics=<ts>` -> `200`
  - Açık empty-state metni var: `henüz haber bulunmuyor`
  - Article detail link’i tespit edilmedi
- Category:
  - `GET /kategori/asayis?forensics=<ts>` -> `404`
  - Aynı runtime tanı çıktısında `category_distribution = []`

Sonuç:
- Homepage, category ve city yüzeyleri aynı runtime DB bağlamını kullanıyor.
- Aktif runtime DB boş olduğu için public yüzeyler de içerik açısından boş/çökük davranıyor.

## Why Shell And Public Runtime Diverged
### Observed divergence
- `.env` dosyası MySQL işaret ediyor:
  - `DB_CONNECTION=mysql`
  - `DB_HOST=127.0.0.1`
  - `DB_DATABASE=adh_database`
- Shell bootstrap, override olmadan gerçekten MySQL’e gitmeye çalışıyor ve başarısız oluyor:
  - `SQLSTATE[HY000] [2002] ... connection refused`
- Çalışan public runtime ise sqlite görüyor ve aynı sqlite dosyasına bağlanıyor.

### Proven cause
- Config cache yok, alternate base path yok, alternate env file yok.
- Running runtime içindeki `env('DB_CONNECTION')` ve `env('DB_DATABASE')` değerleri `.env` ile çelişiyor.
- Mevcut shell process’inde bu env değişkenleri boş.
- Bu kombinasyonun anlamı:
  - çalışan `php artisan serve` process’i, `.env` yerine **process-level environment override** ile başlatılmış
  - override edilen kritik değerler:
    - `DB_CONNECTION=sqlite`
    - `DB_DATABASE=C:\nwp0203\haber-sitesi\database\database.sqlite`

### Cross-check
- Shell’e geçici olarak aynı env override verildiğinde, shell bootstrap da runtime ile aynı sonucu verdi:
  - `db_default = sqlite`
  - `db_name = C:\nwp0203\haber-sitesi\database\database.sqlite`
  - `news = 0`
  - `published = 0`
- Override olmadan shell bootstrap MySQL connection refused veriyor.

### Additional note
- `database.sqlite` dosyasının timestamp’i güncel; bu, haber verisi olduğu anlamına gelmiyor.
- `cache.default = database` ve `/health` route’u cache probe yazdığı için sqlite dosyası runtime sırasında güncelleniyor.

## Temporary Diagnostics
- Eklendi:
  - `GET /__local/runtime-db-forensics`
  - yalnızca localhost IP’lerinden erişilebilen geçici JSON tanı route’u
- Amaç:
  - çalışan server içinden efektif config ve DB target kanıtı toplamak
  - aynı runtime içinden haber sayımları almak
- Durum:
  - kanıt toplandıktan sonra route kaldırıldı
  - doğrulama: route artık `404` dönüyor

## Whether UI Work Is Safe To Proceed
- Hayır.
- Runtime DB kimliği artık kanıtlı; fakat bu kimlik aktif public server’ın boş sqlite DB’ye baktığını gösteriyor.
- Bu durumda yapılacak editoryal/UI iyileştirme, kullanıcının hedeflediği “recovered IHA content present” runtime üzerinde doğrulanamaz.
- Önce şu iki durumdan biri sağlanmalı:
  - public server intended recovered DB target ile yeniden başlatılmalı
  - veya aktif sqlite runtime DB’ye veri geri yüklenmeli
