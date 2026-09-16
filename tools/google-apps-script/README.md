# Current COA delivery

The existing Golden Era Sciences Website Automation deployment reads only the
verified Purity Reports and Endotoxin Reports folders. Drive folders remain
restricted to named collaborators. The public endpoint publishes current report
metadata and delivers only PDFs belonging to selected complete pairs. It cannot
fetch an arbitrary Drive file supplied by a visitor.

## Locked filenames

- `SKU__LOT-NUMBER__PURITY.pdf`
- `SKU__LOT-NUMBER__ENDOTOXIN.pdf`

Use the approved product-sheet SKU and exact strength. The existing approved
WooCommerce SKU aliases are in `inc/coa.php`.

## Replacement workflow

Upload both reports into their respective folders. The lot with the latest
file creation time becomes the current candidate for that exact SKU. An
incomplete new pair, duplicate type, or tied lot timestamp is withheld. Older
pairs are not used as fallbacks. Updating an existing file also refreshes its
preview through its modified timestamp.

The theme caches metadata for 15 minutes. Product and index links resolve the
current exact PDF at click time and serve it through WordPress without exposing
Drive browsing links. Metadata cache expiry is the publication delay; report
preview imports run hourly through WooCommerce Action Scheduler and depend on
working WordPress cron. Tools > Batch Reports provides immediate per-product
sync and import errors. Uploading a replacement does not require a theme deploy.

The first successful manual import verifies host compatibility and enables the
hourly queue. Main product images remain first, purity previews second, and
endotoxin previews third. Other gallery images are preserved. Managed old images
and PDF media copies are removed after a successful replacement. Import errors
leave stored prior assets intact, while product output suppresses previews that
no longer match the current report keys.

## Deployment

Edit the existing script and update its existing Web app deployment to a new
version. Preserve the deployment URL, owner execution, and current access
settings. Preserve `doPost` and subscriber behavior. Do not change Drive sharing
to make folders public. The existing deployment already has the Drive access
needed to read these reports.

## Known catalog gaps as of September 16, 2026

No approved exact pair exists for GLP-S 20mg, GLP-R 24mg, Sermorelin 10mg, or
AOD-9604 5mg. Never substitute another strength or fabricate mappings.
