# ADH News Detail and IHA Body Readiness - 2026-05-13

## Summary

Local latest IHA detail readiness: PASS with production caveats.

The latest sampled IHA articles have non-empty, deeper-than-summary body content and render public detail pages with HTTP 200.

## Sampled Latest IHA Articles

| Slug | Category | Body length | Summary length | Image | Public status |
| --- | --- | ---: | ---: | --- | --- |
| `mardinde-sampiyonluk-gecesinin-adresi-mardian-mall-oldu` | `spor` | 2126 | 107 | yes | 200 |
| `mardinde-cifte-sampiyonluk-coskusu` | `spor` | 1175 | 187 | yes | 200 |
| `ikinci-kattan-dusen-genc-hayatini-kaybetti` | `asayis` | 643 | 60 | yes | 200 |
| `sanliurfada-anneler-gunu-unutulmadi` | `gundem` | 1760 | 200 | yes | not separately probed |
| `karagul-hasadi-basladi` | `ekonomi` | 2030 | 118 | yes | not separately probed |

## HTTP Evidence

- `/mardinde-sampiyonluk-gecesinin-adresi-mardian-mall-oldu`: 200
- `/mardinde-cifte-sampiyonluk-coskusu`: 200
- `/ikinci-kattan-dusen-genc-hayatini-kaybetti`: 200
- `/kategori/spor`: 200
- `/kategori/asayis`: 200

## Test Evidence

Included in the wide local suite:

```bash
php artisan test tests/Feature/Public/PublicPagesTest.php tests/Feature/Public/NewsDetailPresentationTest.php
```

Result inside the full run:

- Public pages and detail presentation tests passed.
- Locale-prefixed detail routes passed.
- Detail fallback, escaping and dark-mode contrast tests passed.

## Decision

The local database currently contains IHA articles with visible full body text. News detail local readiness is acceptable.

Production latest-news proof remains BLOCKED until Hetzner cron, queue worker and controlled live IHA sync evidence are collected.
