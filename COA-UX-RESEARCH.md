# COA UX decision – September 16, 2026

Primary-site research:

- [Blue Sky Peptide](https://www.blueskypeptide.com/coa) provides a dedicated,
  alphabetically sorted and paginated PDF index.
- [SwissChems](https://swisschems.is/product/os-01-100mg-30caps/) exposes a
  Check Lab Report affordance beside purchasing controls and a Lab Results
  product section. Its site states reports are available at product level and
  through its independent-test-results page. This observation concerns
  navigation only; its claims and product copy are not Golden Era's template.
- [Merck Millipore](https://www.merckmillipore.com/RW/en/documents-search?tab=coa)
  uses lot/batch lookup, with optional product number, for exact document
  retrieval. This supports showing the lot alongside every report pair.
- [Biotech Peptides](https://biotechpeptides.com/product/bpc-157/) has product
  information and specifications, but its fetched public page did not surface
  a distinct COA link. This is a limitation of the observed page, not proof
  that no report exists elsewhere.

Golden Era's implemented choice: a searchable on-site COA index, exact PDF
buttons and lot identification on product pages, a View reports anchor on
product cards, and first-page report previews in gallery positions 2 and 3.
A quick-view modal would duplicate report controls and add focus-management
and mobile maintenance work without improving the full-PDF reading experience.
All public entry points use one SKU/lot matching record and stable report
resolver URLs. No public Drive-folder link is emitted.

Research-only wording is preserved. Reports are presented as batch
analytical documentation, not guarantees of safety, sterility, efficacy, or
suitability for human or veterinary use.

Current-source audit: canonical Notion Client, Scope of Work, Worklog, current
COA task, connected Drive subfolders, live WooCommerce Store API, and current
GitHub main. The current source folders hold 24 pairs. Only 13 pairs map exactly
to the 17 live products through approved existing aliases. Four strength gaps
are explicitly shown as unavailable.

The active deployment workflow is GitHub main to WordPress.com GitHub
Deployments, destination `/wp-content/themes/golden-era`, Simple mode.
Vercel is retired for this site according to the current Notion worklog.
