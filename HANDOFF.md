# Golden Era Sciences — Developer Handoff

Prepared by Gab Real Inc · July 2026

---

## What you are inheriting

[goldenerasciences.com](https://goldenerasciences.com) is **live and working**.
It runs on WordPress.com with WooCommerce, using a custom theme built for this
brand. That theme is what you have in this zip.

Everything you need is here. There is no prior repository to inherit, no legacy
branches, and no migration to unpick. Start fresh.

---

## The setup

| | |
|---|---|
| **Host** | WordPress.com, Commerce plan |
| **Production** | goldenerasciences.com |
| **Staging** | WordPress.com → Staging Site |
| **CMS** | WordPress 7.0.2, PHP 8.4 |
| **Store** | WooCommerce, 17 products |
| **Theme** | Golden Era Sciences (`golden-era`) — in this zip |
| **Deploys** | WordPress.com GitHub Deployments |

Site administrator accounts already exist for `info@goldenerasciences.com`.

---

## Setup: connect the code to the site

The theme is currently installed directly on the server. Putting it in a
repository gives you version control and one-command deploys. Takes about ten
minutes, once.

### 1. Create a repository

Create a new GitHub repository under whichever account should own this
long-term. Private is recommended. Name it whatever you like, for example
`golden-era-website`.

### 2. Push the theme to it

Unzip `golden-era-theme.zip`. Inside is a folder called `golden-era`.

**Push the contents of that folder to the repository root** — so `style.css` and
`functions.php` end up at the top level, not nested inside a subfolder. See
"Repo layout" below for why.

```bash
unzip golden-era-theme.zip
cd golden-era
git init
git add -A
git commit -m "Golden Era Sciences WordPress theme"
git branch -M main
git remote add origin https://github.com/<account>/golden-era-website.git
git push -u origin main
```

### 3. Connect it to WordPress.com

Go to `wordpress.com/github-deployments/goldenerasciences.com` and click
**Connect repository**.

| Setting | Value |
|---|---|
| Repository | the one you just created |
| Branch | `main` |
| **Destination directory** | **`/wp-content/themes/golden-era`** |
| Deployment mode | **Simple** |
| Deploy changes on push | on |

Then use **⋯ → Trigger manual deployment** to confirm it works.

That's it. From here, `git push` updates the live site in under a minute.

---

## Repo layout

**The repository root is the theme root.** WordPress.com's Simple deployment
copies the repo contents verbatim into the destination directory, so the layout
must be:

```
style.css              ← must be at the top level
functions.php
front-page.php
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

Commit what is *inside* the `golden-era` folder, not the folder itself.

---

## Making changes

```bash
git add -A
git commit -m "Describe the change"
git push origin main
```

Deploys automatically, under a minute. Watch it at
`wordpress.com/github-deployments/goldenerasciences.com`.

Test anything risky on the staging site first.

### No build step, deliberately

Simple deployment copies files verbatim. It does not run `npm install`, Webpack,
Sass, or anything else. So everything runs exactly as committed:

- CSS is hand-written in `assets/css/theme.css`. No Tailwind, no preprocessor.
- JS is plain vanilla in `assets/js/theme.js`. No bundler.
- No `package.json`, no `node_modules`.

This is why deploys are fast and never silently half-fail. If you want a build
step later, switch the connection to **Advanced** mode and add a workflow file.

### You cannot edit theme files in wp-admin

WordPress.com blocks writes through **Appearance → Theme File Editor**. It
accepts the edit, shows no error, and silently discards it. Verified by saving a
change and fetching the file back.

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

## Site configuration

These live in the WordPress admin, not in code.

- **Appearance → Menus.** Three locations: Primary Navigation, Footer — Explore,
  Footer — Legal. Primary is set up; the two footer menus are not yet.
- **Settings → Reading.** Static homepage is set; `front-page.php` handles it.
- **Appearance → Customize → Golden Era — Brand.** Instagram, TikTok, contact
  email and phone.
- **Products.** The homepage grid shows products flagged **Featured** (the star
  in the products list). If fewer than four are flagged it tops up with best
  sellers, so the section is never empty.

### Certificates of Analysis

The COA button on a product page reads a custom field. Add it in the product
editor under Custom Fields:

- Key: `coa_url`
- Value: the full `https://…` link to the PDF

No field, no button. `coa`, `_coa_url` and `certificate_of_analysis` also work.

### Newsletter signups

The footer form stores subscribers under **Tools → Subscribers** and fires a
`ge_new_subscriber` action. To use MailPoet or another provider instead:

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

Worth understanding before you change them.

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

1. **The store cannot take payments yet.** WooCommerce setup is at step 5 of 7
   and WooPayments is installed but not activated. Nothing can be ordered until
   this is finished. This is the blocker for launch.
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
  line.

---

## In this zip

| File | What it is |
|---|---|
| `HANDOFF.md` | This document |
| `README.md` | Theme documentation: structure, tokens, configuration |
| `DEPLOY-STEPS.md` | Deployment reference |
| everything else | The theme |

---

Built by [Gab Real Inc](https://gabrealinc.com) · hello@gabrealinc.com
