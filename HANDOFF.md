# Golden Era Sciences — Website Handoff

Prepared by Gab Real Inc · July 2026

---

## Summary

[goldenerasciences.com](https://goldenerasciences.com) is **live, working, and
fully wired up**. The code is in GitHub, GitHub is connected to the site, and
pushing a change deploys it to production in under a minute. This has been
tested end to end, not just configured.

Nothing needs to be set up. It runs today.

---

## How it is built

| | |
|---|---|
| **Site** | goldenerasciences.com |
| **Hosting** | WordPress.com, Commerce plan |
| **Store** | WooCommerce — all products, pricing, inventory and orders |
| **Theme** | Custom, hand-coded in **PHP, CSS and vanilla JavaScript** |
| **Code** | [github.com/gabrealinc/golden-era-sciences-website](https://github.com/gabrealinc/golden-era-sciences-website) (public) |
| **Deploys** | WordPress.com GitHub Deployments, automatic on push to `main` |
| **Staging** | Available under WordPress.com → Staging Site |

This is a **custom-coded theme**, not a page builder, not a template. Changes
are made in code, committed to GitHub, and deployed automatically.

Dan (`info@goldenerasciences.com`) is already a **collaborator** on the
repository and an **administrator** on the WordPress site.

---

## The deployment pipeline

Already connected and confirmed working:

| Setting | Value |
|---|---|
| Repository | `gabrealinc/golden-era-sciences-website` |
| Branch | `main` |
| Destination | `/wp-content/themes/golden-era` |
| Mode | Simple (copy files, no build step) |
| Deploy on push | On |

Manage it at
`wordpress.com/github-deployments/goldenerasciences.com`.

**To make a change:**

```bash
git add -A
git commit -m "Describe the change"
git push origin main
```

That's it. The deployment runs automatically and finishes in well under a
minute. You can watch it at the Deployments URL above.

Test anything risky on the staging site first.

---

## The repository is yours to keep or move

Both options work. It's your call.

### Option 1 — keep using the current repository

`github.com/gabrealinc/golden-era-sciences-website` is public, Dan is already a
collaborator, and it is already connected to the live site. You can start
working immediately with no setup.

Gab Real Inc will not be making changes to it.

Additional developers can be added as collaborators at any time — just ask.

### Option 2 — move it to your own GitHub account

If you'd rather own the repository outright, that's straightforward and
probably the tidier long-term answer.

1. Create a GitHub account under Dan or the business, if there isn't one.
2. Create a new repository. Private is fine.
3. Clone the current repo and push its contents to the new one:

   ```bash
   git clone https://github.com/gabrealinc/golden-era-sciences-website.git
   cd golden-era-sciences-website
   git remote set-url origin https://github.com/<your-account>/<new-repo>.git
   git push -u origin main
   ```

4. In WordPress.com → Deployments, disconnect the current repository, then
   **Connect repository** and point it at the new one.

   **Set the destination directory to `/wp-content/themes/golden-era`.**
   WordPress.com will auto-fill this from your repository name, which will be
   wrong. The folder name must stay `golden-era` because that is the theme
   WordPress has active.

5. Trigger a manual deployment to confirm, then check the site.

Everything else stays the same. The site, the products, the orders and the
WordPress account are untouched by moving the repository.

---

## Repository layout

**The repository root is the theme root.** WordPress.com's Simple deployment
copies the repo contents verbatim into the theme directory, so `style.css` and
`functions.php` have to sit at the top level.

```
style.css              Theme header. Real styles are in assets/css.
functions.php          Setup, assets, helpers, includes.
front-page.php         Homepage.
header.php  footer.php  index.php  page.php  single.php  404.php
comments.php  searchform.php  screenshot.png

assets/
  css/theme.css        All styles. Design tokens at the top.
  js/theme.js          Age gate, mobile menu, FAQ accordion, signup.
  images/              Logo, hero, vials, product placeholder, OG image.
  video/hero-loop.mp4  Homepage hero background.

inc/
  woocommerce.php      All WooCommerce customisation.
  subscribe.php        Newsletter handler and subscriber storage.
  faq-data.php         FAQ copy.
  customizer.php       Social links, contact details.

template-parts/        hero, marquee, quality, faq, age-gate, subscribe
woocommerce/           Template overrides, mirrors WooCommerce's own paths
```

---

## Two things that will save you time

### There is no build step, deliberately

Simple deployment copies files verbatim. It does not run `npm install`,
Webpack, Sass, or anything else. So everything runs exactly as committed:

- CSS is hand-written in `assets/css/theme.css`. No Tailwind, no preprocessor.
- JS is plain vanilla in `assets/js/theme.js`. No bundler.
- There is no `package.json` and no `node_modules`.

This is why deploys are fast and never silently half-fail. If you want a build
step later, switch the connection to **Advanced** mode and add a workflow file.

### You cannot edit theme files in wp-admin

WordPress.com blocks writes through **Appearance → Theme File Editor**. It
accepts your edit, shows no error, and silently discards it. This was confirmed
by saving a change and fetching the file back.

**Git is the only way to change this site's code.** Worth knowing before you
lose an hour to it.

---

## Design system

All colours, fonts and spacing are defined once, in the `:root` block at the top
of `assets/css/theme.css`. Change a value there and it updates everywhere.

| Token | Value | Use |
|---|---|---|
| `--ge-espresso` | `#1a0a05` | Dark backgrounds, body text |
| `--ge-parchment` | `#f2e8d5` | Page background |
| `--ge-cream` | `#faf5ea` | Cards, light surfaces |
| `--ge-sand` / `--ge-sand-dark` | `#e8d9c0` / `#d9c9a8` | Borders, muted text |
| `--ge-gold` | `#c2a25f` | Primary accent, buttons |
| `--ge-gold-mid` / `--ge-gold-deep` | `#a3823f` / `#6e5526` | Hovers, headings |
| `--ge-stone` | `#5a4a35` | Body copy on light |

Fonts: **Playfair Display** for headings, **Times New Roman** for body,
**Copperplate** (falling back to Montserrat) for uppercase UI labels. Loaded
from Google Fonts in `functions.php`.

---

## Managed in the WordPress admin, not in code

- **Products, pricing, inventory, orders** — all WooCommerce. Nothing about the
  catalog lives in the theme.
- **Appearance → Menus.** Three locations: Primary Navigation, Footer — Explore,
  Footer — Legal. Primary is set up; the two footer menus are not yet.
- **Settings → Reading.** Static homepage is set; `front-page.php` handles it.
- **Appearance → Customize → Golden Era — Brand.** Instagram, TikTok, contact
  email and phone.
- **Featured products.** The homepage grid shows products flagged Featured (the
  star in the products list). If fewer than four are flagged it tops up with
  best sellers, so the section is never empty.

### Certificates of Analysis

The COA button first looks up the product SKU in the Golden Era Sciences Google
Drive COA feed. If an exact SKU match is unavailable, it opens the shared COA
library. The legacy product custom field remains supported as a fallback:

- Key: `coa_url`
- Value: the full `https://…` link to the PDF

`coa`, `_coa_url` and `certificate_of_analysis` also work as legacy field names.

Name PDFs `SKU__LOT-NUMBER__YYYY-MM-DD.pdf`. Adding or replacing a correctly
named PDF in the connected Drive folder updates the website feed without a
theme deploy once the included Apps Script has been published.

### Newsletter signups

The footer form stores a private backup under **Tools → Subscribers** and
synchronizes each signup to the approved Google Sheet. Email Opt In is true
for every completed signup; SMS Opt In is true only when the optional phone
number is supplied. To replace the built-in form with MailPoet or another
provider instead:

```php
add_filter( 'ge_subscribe_shortcode', function () {
    return '[mailpoet_form id="1"]';
} );
```

### Age gate

Shown until the visitor confirms they are 21+. Stored in `localStorage`, not a
cookie, so it never varies the server response and page caching stays intact.

```php
add_filter( 'ge_age_gate_enabled', '__return_false' );  // to disable
```

---

## Deliberate decisions

Worth understanding before changing them.

- **Reviews and star ratings are disabled on products.** Customer reviews on a
  research-chemical catalog invite use claims, which is a compliance risk, not
  just a design preference.
- **Every product page carries a Research Use Only notice**, injected via a hook
  so it cannot be forgotten on a new product.
- **No shop sidebar.** Category filtering is the chip row at the top of the
  archive.
- **Product loop markup is fully overridden.** `content-product.php` emits the
  card and `loop/loop-start.php` supplies a CSS grid instead of WooCommerce's
  `<ul><li>`. The standard loop hooks are still fired, so extensions work.

---

## Open items

Not blockers for the site, but they are the next things to handle.

1. **The store cannot take payments yet.** WooCommerce setup is at step 5 of 7
   and WooPayments is installed but not activated. Nothing can be ordered until
   this is finished. **This is the blocker for launch.**
2. **The site logo file is malformed.** `ges-logo.png` in the media library is a
   256×256 image where the artwork occupies only a 256×37 strip at the top; the
   rest is transparent. It renders as a thin smear at any size. The theme falls
   back to a correct bundled logo so nothing looks broken, but a clean export
   should replace it.
3. **Footer menus are not populated.** Create and assign *Footer — Explore* and
   *Footer — Legal* under Appearance → Menus.

---

## If something breaks

- **Site looks unstyled.** The deploy destination is probably wrong. It must be
  `/wp-content/themes/golden-era`.
- **Pushes do nothing.** Same cause. Check the destination directory.
- **A deploy broke the site.** In wp-admin → Appearance → Themes, activate
  Twenty Twenty-Five to get back to a working state, then fix and redeploy.
  Pages, products and orders are never affected by a theme switch.
- **Anything else.** WordPress.com → **Logs** shows PHP errors with file and
  line. Fastest way to see what actually happened.

---

## Documentation in the repository

- `HANDOFF.md` — this document
- `README.md` — theme structure, design tokens, configuration reference

---

Built by [Gab Real Inc](https://gabrealinc.com) · hello@gabrealinc.com
