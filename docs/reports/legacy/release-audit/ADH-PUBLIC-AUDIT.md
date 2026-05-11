# ADH-PUBLIC-AUDIT

## 2026-04-17 Regression Note

The 2026-04-16 recovery findings below remain historically valid, but they no longer describe the active local runtime at `http://127.0.0.1:8000`.

Current revalidation on 2026-04-17:

- homepage again renders the `Bilgi Panosu` / widget-only state
- local capture saved as `docs/screenshots/adh-current-2026-04-17.png`
- active SQLite counts are back to:
  - `news_articles = 0`
  - `published = 0`
  - `source='iha' = 0`
  - `iha_sync_logs = 0`

Interpretation:

- the prior recovery was real, but the active runtime no longer reflects that recovered state
- this audit file must therefore be read together with the newer 2026-04-17 reports before any release decision

---

## ADH IHA Recovery Update

Date verified: 2026-04-16

Public ADH recovery status:

- fixed locally: homepage no longer shows only one news item
- homepage now exposes multiple recovered IHA detail links
- `/kategori/gundem` and `/kategori/asayis` return recovered IHA content
- `/il/gaziantep` and `/il/adiyaman` return recovered IHA content
- representative detail pages return `200` and render images where present

Verified public counts:

- total news: `63`
- published news visible to public scope: `62`
- IHA published rows: `61`

Open public-side note:

- 8 IHA rows remain without `featured_image`; this does not block the overall public recovery.
