# Catalog reconciliation, September 16, 2026

Product Information, tab `Aug 2026 COA's`, is the current catalog reference identified by Gabby. Golden Era SKU's Order #1 is an older comparison source, not an override for current records.

Sources:
- [Product Information](https://docs.google.com/spreadsheets/d/1adC902rs62X4bE7TYreNnA5GNEy0936x7YyLYxnxZ1U/edit)
- [Golden Era SKU's Order #1](https://docs.google.com/spreadsheets/d/1Twf_EVxf37QhlW3zsqa5PfNLkKm9LS6PhbtFBeT5DUo/edit)

The current sheet contains 30 product rows, including reconstitution water. Retail prices and WooCommerce IDs are empty. The older sheet contains 18 order rows, including separate tirzepatide strengths. The website contains 17 parent products, with tirzepatide strengths grouped as variations.

## Exact-file evidence

All 48 PDFs in the verified purity and endotoxin folders match the current sheet's expected filenames exactly, covering 24 same-SKU, same-lot pairs. All 24 rows are marked COA Yes. The other six rows are marked No and have no matching pair in those folders. A generated filename alone is not evidence that a report exists.

AOD-9604 5mg, row 30, is marked `COA? No`. Its purity and endotoxin filename cells are formulas that construct expected filenames. An exact Drive filename search found no AOD purity PDF. The accessible `AOD-9604 (5mg).pdf` is bottle-label artwork, not a laboratory report.

## Conflicts requiring reconciliation

| Item | Current product sheet | Older order sheet / live site |
| --- | --- | --- |
| Retatrutide | GLP3-R 10mg and 30mg | GLP-R 24mg |
| Sermorelin | 5mg | 10mg |
| KLOW | Separate 80mg / 3mL and 55mg / 6mL rows | 55mg / 6mL live product |
| NAD+ | 1000mg / 3mL | 1000mg / 6mL |
| GLOW | 70mg / 3mL | 70mg / 6mL |
| GLP website names | GLP1-S, GLP2-T, GLP3-R | GLP-S, GLP-T, GLP-R |
| SKUs | Exact compound-strength strings | Legacy GES-prefixed aliases |

The live 55mg KLOW product must never expose the 80mg report. Its alias was corrected immediately to the sheet's 55mg SKU, for which no current matching report is available.

Additional current-sheet rows absent from the live catalog include tesamorelin, TB-500 TB-4 43AA 5mg, ipamorelin 5mg, DSIP, L-glutathione, PT-141, selank, semax, oxytocin, and reconstitution water. Catalog inclusion and prices must be resolved before making these purchasable. No price or report is inferred from a different strength.
