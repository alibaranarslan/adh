# ADH Public Visual Smoke - 2026-05-13

## Summary

Local public visual smoke result: PASS with caveats.

In-app Browser plugin connection timed out twice, so validation used a repo-external Playwright temp harness with installed local Chrome. Screenshots were written outside the repository.

## Environment

- Base URL: `http://127.0.0.1:8000`
- Desktop viewport: `1366x768`
- Mobile viewport: `390x844`
- Browser path: Browser plugin attempted, timed out.
- Fallback: Playwright package installed under `%TEMP%\adh-pw`, launched with `C:\Program Files\Google\Chrome\Application\chrome.exe`.
- Screenshot directory: `C:\Users\Ali\AppData\Local\Temp\adh-public-smoke-clean-2026-05-13`

## Checked Pages

| Page | Status | Title | Mojibake | Demo text | Above-fold broken images | Console |
| --- | ---: | --- | --- | --- | ---: | --- |
| `/` | 200 | `Adıyaman Dijital Haber` | no | no | 0 | clean |
| `/kategori/spor` | 200 | `Spor Haberleri | Adıyaman Dijital Haber` | no | no | 0 | clean |
| `/mardinde-sampiyonluk-gecesinin-adresi-mardian-mall-oldu` | 200 | `Mardin’de şampiyonluk gecesinin adresi Mardian Mall oldu | Adıyaman Dijital Haber` | no | no | 0 | clean |
| `/arama?q=Mardin` | 200 | `"Mardin" arama sonuçları | Adıyaman Dijital Haber` | no | no | 0 | clean |
| `/iletisim` | 200 | `İletişim | Adıyaman Dijital Haber` | no | no | 0 | clean |
| `/kvkk` | 200 | `KVKK Aydınlatma Metni | Adıyaman Dijital Haber` | no | no | 0 | clean |

## Interaction Proof

- Cookie consent `Kabul Et` interaction hides the banner visually.
- Dark mode toggle changes the root HTML class to `dark`.
- Mobile homepage smoke has no horizontal overflow: `scrollWidth=390`, `innerWidth=390`.

## Visual Notes

- Cookie consent appears on fresh sessions and covers part of the hero until accepted. This is expected behavior, not a blocker.
- Offscreen lazy images initially report `naturalWidth=0` before scroll. Above-fold image check is clean.
- Full mobile QA remains a separate workstream; this pass only checked critical overflow/readability.

## Decision

Public visual local smoke is acceptable for predeploy. No P0/P1 visual blocker found.

Production visual verification remains BLOCKED until Hetzner HTTPS/domain deployment exists.
