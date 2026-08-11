# Golden Era Sciences — WordPress Theme

Custom WooCommerce theme for [goldenerasciences.com](https://goldenerasciences.com).

## Compliance release 1.1.0

Deploying this version through WordPress.com GitHub Deployments applies the
approved compliance revision automatically on the first site request. It
updates the 17 WooCommerce products, cleans the Contact page, retires and
redirects the legacy catalog, education, and calculator pages, removes
biological product categories, repairs catalog menu links, and refreshes
product image alt text and common SEO description fields.

The migration runs once and saves a private pre-change snapshot in the
`ge_compliance_migration_backup_2026-08-11_3` WordPress option before marking
the release complete. Theme files and database content are therefore
separately recoverable.

**The repository root is the theme root**, so `style.css` and `functions.php`
sit at the top level. WordPress.com's GitHub Deployments copies the repo
contents straight into the theme directory.

---

## How deployment works

| | |
|---|---|
| Host | WordPress.com, Commerce plan |
| Method | WordPress.com **GitHub Deployments** |
| Branch | `main` |
| Destination | `/wp-content/themes/golden-era` |
| Mode | **Simple** (copy files, no build step) |
| Trigger | Automatic on push to `main` |

Manage it at
`https://wordpress.com/github-deployments/goldenerasciences.com`.

### Why there is no build step

Simple deployment mode copies files verbatim. It does not run `npm install`,
Webpack, Sass, or anything else. So everything in this repo has to run exactly
as committed:

- CSS is hand-authored in `assets/css/theme.css`. No Tailwind, no preprocessor.
- JS is plain ES5-compatible vanilla in `assets/js/theme.js`. No bundler.
- No `node_modules`, no `package.json`, no lockfiles.

If you ever need a build step, switch the connection to **Advanced** mode in
WordPress.com and add a workflow file. Until then, keep it build-free — it is
the reason deploys are 40 seconds and never silently fail.

### Deploying

```bash
git add -A
git commit -m "Describe the change"
git push origin main
```

Then watch the run at the Deployments URL above. Test on the **staging site**
first where the change is risky; staging is under Settings → Staging Site in
WordPress.com.

---

## Structure

```
style.css                     Theme header only. Real styles are in assets/css.
functions.php                 Setup, assets, helpers, includes.
front-page.php                Homepage.
index.php                     Blog index / archive / search fallback.
page.php  single.php  404.php  searchform.php
header.php  footer.php

assets/
  css/theme.css               All styles. Design tokens at the top.
  js/theme.js                 Age gate, mobile menu, FAQ accordion, signup.
  images/                     Logo, hero, vials, product placeholder, OG image.
  video/hero-loop.mp4         Homepage hero background loop.

inc/
  woocommerce.php             All WooCommerce customisation. Loaded only when Woo is active.
  subscribe.php               Newsletter AJAX handler + subscriber storage.
  faq-data.php                FAQ copy (filterable).
  customizer.php              Social links, contact details.

template-parts/
  hero.php  marquee.php  quality.php  faq.php  age-gate.php  subscribe.php

woocommerce/                  Template overrides. Mirrors WooCommerce's own paths.
  archive-product.php         Shop, category, tag pages.
  single-product.php
  content-single-product.php
  content-product.php         The product card.
  loop/loop-start.php         Replaces Woo's <ul> with the theme's CSS grid.
  loop/loop-end.php
```

---

## Design tokens

All colors, fonts, and spacing live in one place: the `:root` block at the top
of `assets/css/theme.css`. Change a brand color there and it updates everywhere.

| Token | Value | Use |
|---|---|---|
| `--ge-espresso` | `#1a0a05` | Dark backgrounds, body text |
| `--ge-parchment` | `#f2e8d5` | Page background |
| `--ge-cream` | `#faf5ea` | Cards, light surfaces |
| `--ge-sand` / `--ge-sand-dark` | `#e8d9c0` / `#d9c9a8` | Borders, muted text on dark |
| `--ge-gold` | `#c2a25f` | Primary accent, buttons |
| `--ge-gold-mid` / `--ge-gold-deep` | `#a3823f` / `#6e5526` | Hovers, headings |
| `--ge-stone` | `#5a4a35` | Body copy on light |

Fonts: **Playfair Display** for headings, **Times New Roman** for body,
**Copperplate** (falling back to Montserrat) for uppercase UI labels.
Loaded from Google Fonts in `functions.php`.

---

## WordPress setup

These are set in the admin, not in code:

1. **Appearance → Menus.** Create and assign three menus:
   - *Primary Navigation* — Home, All Peptides, About Us, FAQs, Contact Us
   - *Footer — Explore*
   - *Footer — Legal*
2. **Settings → Reading.** Set a static homepage. `front-page.php` takes over automatically.
3. **Appearance → Customize → Golden Era — Brand.** Instagram, TikTok, contact email and phone.
4. **Appearance → Customize → Site Identity.** Upload the logo to override the bundled one.
5. **WooCommerce → Settings.** Confirm the Shop page is assigned; the theme's
   "Shop All" links resolve through it.

### Featured products

The homepage grid shows products flagged **Featured** in the product editor
(the star in the products list). If fewer than four are flagged, it tops up
with best sellers so the section is never empty.

### Certificates of Analysis

The COA button looks up the product SKU in the connected Golden Era Sciences
Google Drive library. Name PDFs `SKU__LOT-NUMBER__YYYY-MM-DD.pdf`; adding or
replacing a correctly named file updates the site without a theme deploy.

If there is no exact SKU match, the button opens the shared COA library. The
legacy `coa_url`, `coa`, `_coa_url`, and `certificate_of_analysis` product
custom fields remain supported as fallbacks.

### Newsletter signups

The footer form stores a private backup under **Tools → Subscribers** and
synchronizes first name, last name, email, optional phone, and consent status
to the approved Google Sheet. Email Opt In is always true for a completed
signup. SMS Opt In is true only when the visitor supplies a phone number.

To use MailPoet, Jetpack, or another provider's form instead, drop this in a
site-specific plugin or `functions.php`:

```php
add_filter( 'ge_subscribe_shortcode', function () {
    return '[mailpoet_form id="1"]';
} );
```

### Age gate

Shown until the visitor confirms they are 21+. The answer is stored in
`localStorage`, deliberately not a cookie, so it never varies the server
response and page caching stays intact.

Disable with:

```php
add_filter( 'ge_age_gate_enabled', '__return_false' );
```

### FAQs

Copy lives in `inc/faq-data.php` so it is version controlled and deploys with
the theme. Filter `ge_faqs` to source them from a CPT instead.

---

## Deliberate decisions

Worth knowing before you change them:

- **Reviews and star ratings are disabled on products.** Customer reviews on a
  research-chemical catalog invite use claims, which is a compliance risk, not
  just a design choice. Removed in `inc/woocommerce.php`.
- **Every product page carries a Research Use Only notice**, injected via hook
  so it cannot be forgotten on a new product.
- **No shop sidebar.** Category filtering is the chip row at the top of the
  archive.
- **Product loop markup is fully overridden.** `content-product.php` emits the
  card and `loop/loop-start.php` supplies a CSS grid instead of Woo's
  `<ul><li>`. The standard loop hooks are still fired, so extensions work.

---

## Local development

```bash
# any local WordPress works; wp-env, Local, MAMP, Studio
git clone <your-repo-url> golden-era
# place in wp-content/themes/ and activate
```

Nothing to install or compile. Edit and refresh.

---
