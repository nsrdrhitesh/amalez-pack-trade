# Amlex Pack Trade — website handoff

## Scope and visual direction

A static Laravel Blade corporate homepage for Packaging and Printing Solutions. The design uses warm ivory, charcoal green, muted neutral surfaces, and burnt orange; editorial typography; original packaging still lifes; restrained borders; and an original folded-pack / layered-print logo concept. The temporary display name is **Amlex Pack Trade** and the confirmed canonical domain is **https://amalezpacktrade.com**. This implementation does not deploy or connect that domain.

The public homepage and sitemap deliberately bypass session, shared-session-errors, and CSRF middleware. These are GET-only routes with no writes. Their content comes from configuration and Blade, with no database queries or authentication. The existing admin authentication, authorization, database configuration, and admin interface are preserved. No migrations or database changes were needed for this phase.

## Homepage sections

1. Sticky navigation with desktop active states, keyboard focus styles, and mobile menu.
2. Packaging hero with original concept imagery and two clear CTA paths.
3. Compact non-numerical packaging highlights.
4. Company introduction and working approach.
5. Four editable product concepts, format filters, and accessible native product-detail dialogs.
6. Industry/application considerations, with a configuration-driven confirmed-segment view when supplied.
7. Four-step custom packaging brief process.
8. Manufacturing process considerations using original CSS/SVG diagrams rather than invented facility photographs.
9. Partnership principles / why choose us.
10. Original illustrative product gallery.
11. Prominent quote CTA.
12. Client-side enquiry brief preparation and local `.txt` download.
13. Corporate footer with configurable contact and social information.

The enquiry form validates required name, email, format, and requirement fields in the browser. It never submits to a server, sends email, or reports a fake submission. Data is held in page memory only; downloading a brief is an explicit user action. The form is inert until Alpine initializes, avoiding accidental browser form submissions when JavaScript is unavailable. A real enquiry endpoint must be implemented separately with server-side validation, CSRF, throttling, spam controls, privacy wording, and an approved delivery destination.

## Edit the business information

Start with `config/company.php`:

| Setting | Meaning |
| --- | --- |
| `name` | Working display name used throughout navigation, metadata, and page text. |
| `domain` | Canonical HTTPS origin; also used for sitemap and social preview URLs. |
| `logo` | Optional local public-relative path for an approved logo. `null` uses the configurable text and original vector mark. |
| `legal_name` | Keep null until verified. A verified value switches the minimal WebSite schema to Organization. |
| `email`, `phone`, `address` | Keep null until verified. No guessed contact details are shown. |
| `social_links` | Verified HTTPS links keyed by visible label. |
| `products` | Names, descriptions, family/filter group, details, example applications, image key, and confirmation status. |
| `industries` | Confirmed segments as arrays with `name` and `description`. Empty uses application questions without claiming served markets. |
| `capabilities` | Confirmed process/capability names. Empty shows an explicitly illustrative process map. |

Section copy is editable in the small Blade partials. Product images use local keys under `public/images/products/`. Change `image` or replace the relevant local asset to update a card. Keep the same filter families or update the three filter controls in the products partial. Hero and gallery images are separate local assets. No restructuring or CMS is needed to update these items. After configuration edits run `php artisan config:clear`; rebuild only when source CSS/JS or asset references change.

## Files created / changed

```text
config/company.php                         business identity and product data
app/Providers/AppServiceProvider.php        registers frontend component namespace
app/Http/Controllers/Frontend/HomeController.php
routes/web.php                             stateless homepage and XML sitemap
resources/views/frontend/
  layouts/app.blade.php                     SEO and page shell
  home.blade.php                            ordered section composition
  sitemap.blade.php
  components/
    brand.blade.php                         original configurable brand concept
    arrow.blade.php
    navbar.blade.php
    section-heading.blade.php
    product-card.blade.php
    footer.blade.php
  partials/
    hero.blade.php
    about.blade.php
    products.blade.php
    industries.blade.php
    solutions.blade.php
    capabilities.blade.php
    why-choose-us.blade.php
    gallery.blade.php
    contact.blade.php
resources/css/packaging.css                 separate corporate design system
resources/js/app.js                         Alpine filters, dialogs, brief builder
resources/images/source/                    original generated PNG studies
public/images/
  branding/mark.svg                         favicon concept
  branding/amlex-logo-concept.svg           original wordmark concept
  hero/packaging-{800,1600}.webp
  products/{pouches,spout,labels,rolls}.webp
  gallery/{materials,application}.webp
public/robots.txt                          blocks indexing during preview
scripts/prepare-packaging-assets.php        reproducible GD image optimization
tests/Feature/PackagingWebsiteTest.php       stateless route, metadata and asset tests
tests/Feature/AdminAuthenticationTest.php   updated homepage text assertion
vite.config.js                             adds separate packaging CSS entry
docs/PACKAGING-WEBSITE.md                   this handoff
docs/RESEARCH-AND-ASSETS.md                 research, image provenance and prompts
```

The admin still uses its existing `app.css` / `forma.css` bundle. Public corporate styling is a separate Vite entry so it does not restyle the working admin panel.

## Environment and commands

No new runtime dependencies were installed and no framework upgrade was performed. Existing environment: Laravel 12.71.0, PHP 8.2.4, Composer 2.7.7, Node 22.21.0, npm 10.9.4, Tailwind 4.3.5, Alpine.js 3.17.5, Vite 6.4.5. Image optimization uses the already installed PHP GD extension.

```powershell
cd "C:\xampp1\htdocs\New folder\laravel-fullstack-starter"
# Serve existing built assets (no Vite dev server required):
php artisan serve --host=127.0.0.1 --port=8000
# Or use the existing combined PHP + Vite development command:
composer run dev
# Compile assets / templates and run the full regression suite:
npm.cmd run build
php artisan view:cache
php artisan test
# Rebuild optimized images from the retained original studies:
php scripts/prepare-packaging-assets.php
```

Local website: http://127.0.0.1:8000/ . Sitemap: http://127.0.0.1:8000/sitemap.xml . Existing admin: http://127.0.0.1:8000/admin/login . Use an existing server on port 8000 rather than launching a competing copy.

## SEO, performance, and accessibility

One H1; semantic header, nav, main, section, article, figure, and footer; skip link; descriptive image alternatives; explicit image dimensions; visible keyboard focus; native dialog focus containment and Escape dismissal; mobile navigation with expanded state; labelled native validated form controls; reduced-motion support; no external tracking, map, font, or runtime API requests.

Metadata includes title, description, canonical URL, Open Graph, Twitter card, and minimal WebSite JSON-LD. Organization details are not invented. The sitemap contains only the public home URL. Assets are local WebP, the hero has 800px and 1536px variants and preload/srcset, and below-the-fold photographs are lazy loaded. Individual delivery images are approximately 17–61 KB. Typography uses an installed system sans stack for reliable rendering without an external font request.

**Indexing is intentionally blocked during the preview.** After business and legal review, set `COMPANY_SITE_INDEXABLE=true` in the deployment environment, change `public/robots.txt` to allow `/`, add the production sitemap URL, and clear cached configuration. Keep `/admin/` disallowed. The canonical domain and social image URLs assume the eventual HTTPS deployment. Analytics, Search Console verification, and Maps are future integrations, not configured services.

## Validation and remaining review

Latest completed validation: **26 tests passed with 295 assertions**, successful Vite production build (corporate CSS approximately 14.6 KB gzip, shared JS approximately 20.6 KB gzip), successful Blade template compilation, and successful route-cache compilation. Route cache was cleared afterward for convenient local editing. The live homepage and sitemap returned HTTP 200.

Automated feature coverage checks stateless rendering with an unusable session database configuration, zero homepage DB queries, section and CTA targets, a single H1, metadata, schema boundaries, valid local images and asset budgets, XML output, configurable content, and explicit preview-only form semantics. The full existing authentication/authorization regression suite remains applicable.

Responsive CSS targets mobile, tablet, laptop, and large desktop layouts. The requested visual review matrix is **375, 430, 768, 1024, 1440, and 1920 pixels**. Browser-based rendering, console, interactive keyboard/touch behavior, and overflow at these six exact widths still require browser verification; automated PHP tests and asset compilation do not establish those visual outcomes. Prior browser access in this environment was unavailable. Do not treat unobserved viewport checks as passed.

## Client confirmation and next phase

- Final display/legal name, official logo, and brand palette approval.
- Approved company description and history.
- Actual available product formats, structures, finishes, application limits, quantities, and lead times.
- Verified industry segments, regulatory suitability, certifications, and manufacturing capabilities.
- Genuine facility/product photography and permission to use any client portfolio material.
- Verified telephone, email, address, social links, and enquiry recipient.
- Privacy notice, legal documents, and rules for handling enquiry data.
- Responsive browser QA followed by a real enquiry flow; later CMS and product pages if needed.

The domain has not been deployed, DNS has not been modified, and no external enquiry service has been connected.
