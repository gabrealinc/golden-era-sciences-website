# Google Drive COA feed

This script exposes only PDF metadata from the Golden Era Sciences `COAs`
folder. It does not expose any other Drive folder.

## File naming

Use this exact pattern:

`SKU__LOT-NUMBER__YYYY-MM-DD.pdf`

Example:

`GES-BPC-10MG__LOT-24081__2026-08-10.pdf`

The site matches the exact SKU prefix and selects the newest filename date.
Older batch PDFs remain available in Drive.

## Deployment

1. Create a Google Apps Script project in the Google account that owns or can
   read the COA folder.
2. Replace the default script with `Code.gs`.
3. Deploy as a Web app.
4. Execute as the deploying account.
5. Set access to anyone who can view the public COA library.
6. Paste the deployed `/exec` URL into WordPress Customizer under
   `Golden Era — Brand` → `COA index feed URL`.

The WordPress theme caches the index for 15 minutes. A newly uploaded PDF may
therefore take up to 15 minutes to become the current product COA.

## Failure behavior

If the feed is unavailable or no exact SKU match exists, the product page links
to the main COA library and labels the link `Browse COA Library`. It never uses
a partial SKU match or a different product's PDF.
