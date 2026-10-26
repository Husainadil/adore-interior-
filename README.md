# Adore Interior

A Laravel 13.38 application with Blade, Tailwind 4, Alpine, GSAP/ScrollTrigger, Swiper, Lucide, and Vite. Fonts and responsive stock images are served locally. PHP 8.3+ is required; development was verified with PHP 8.5.12 and Node 24.

## Open locally

From this directory, use:

```powershell
powershell -ExecutionPolicy Bypass -File .\serve.ps1
```

Visit http://127.0.0.1:8766. The launcher uses the existing C:\php85 runtime with required extensions and disables OPcache for the local development server. This is process-local; it does not modify system PHP or PowerShell policy.

The current `.env` uses SQLite for a self-contained local preview. Production is prepared for MySQL through Laravel's standard database configuration and portable migrations. MySQL was not connected or tested in this session.

For a fresh installation on a normally configured PHP machine:

```sh
composer install
cp .env.example .env
php artisan key:generate
# Configure your database in .env before migrating.
php artisan migrate --seed
php artisan storage:link
npm ci
npm run build
php artisan serve --port=8766
```

On this Windows machine, replace `php` commands with `powershell -ExecutionPolicy Bypass -File .\php-local.ps1` when the PHP executable is not on PATH.

## Private administration

Create your administrator interactively; there is no default password or public registration:

```powershell
powershell -ExecutionPolicy Bypass -File .\php-local.ps1 artisan studio:admin
```

Sign in at `/admin/login`. Passwords must have at least 12 characters. The CLI conceals password entry. Login is rate limited and protected by sessions and CSRF. Every admin action checks the administrator role. Profile changes require the current password.

Modules include banners, about/page content, services, projects, categories, project images, before/after comparisons, testimonials, inquiries, SEO, settings, media, and profile/password. Forms validate on the server. Content supports draft/published status, ordering, unique slugs and soft deletion. Inquiries are searchable and filterable, with new/contacted/consultation/closed statuses. Contact and consultation submissions share one inbox.

- **Pages:** `about`, `privacy` and `terms` control their corresponding page copy. `process-01` through `process-06`, `principle-01` through `principle-03` and `feature-01` through `feature-06` control the design journey, philosophy and reasons to choose the studio. Titles and descriptions are rendered in sort order. Keep these slug prefixes when editing.
- **Banner:** the first published banner controls the homepage. Title is the first headline line, content the italic second line, description the eyebrow, image the hero photograph.
- **Media:** upload JPEG, PNG or WebP images up to 5 MB and 8000×8000. With GD enabled, images are re-encoded to WebP at a maximum dimension of 2400px, stripping metadata. Copy the returned `/storage/media/...` path into content image fields. SVG and executable uploads are rejected. On systems without GD, validated source files are retained; enable GD for production optimization.
- **Project galleries:** add a project-image record for each photograph, select its parent project, and set alt text and sort order.
- **SEO:** enter a path such as `/contact` or `/` and its unique title/description. Canonical, Open Graph, LocalBusiness JSON-LD, sitemap and robots are generated automatically.
- **Logo:** upload the supplied original brand logo, then create a website setting with key `logo` and its media path. The development label explicitly states the original logo is pending. No substitute logo was created.
- **Address:** the `address` setting controls the footer address. Phone, WhatsApp and email use the business details supplied in the brief.

## Content integrity

The six portfolio entries are clearly labeled illustrative concepts with stock photos. They are not claims of completed client work. Remove or replace them through the admin when real project material is available. Project detail pages preserve this distinction. The comparison is an explicitly labeled monochrome/colour control demo using one photograph; replace both images with a matched real pair and clear the sample flag to publish a genuine transformation.

Testimonials are hidden unless published and not marked sample. Only add verified testimonials. No client identities, awards, completion counts, guarantees, hours or social accounts have been invented. Privacy and terms copy can be edited through Pages before launch.

## Inquiry notifications

Submissions are stored before notifications are queued using Laravel's database queue. Local mail uses the log driver and is not delivered externally. Set `INQUIRY_NOTIFICATION_EMAIL` if the destination should differ from the supplied studio address. Configure SMTP with environment variables on the server and run:

```sh
php artisan queue:work --tries=3 --timeout=60
```

Use a supervisor to keep the worker running. Restart workers after deployments. Monitor `failed_jobs` and retry failures with Laravel's queue commands. Keep SMTP credentials in server environment configuration, never in source or browser code.

## Verification

```powershell
powershell -ExecutionPolicy Bypass -File .\php-local.ps1 vendor/phpunit/phpunit/phpunit
npm.cmd run build
```

The feature suite covers public routes, published/draft behavior, sitemap exclusion, inquiry persistence and notification dispatch, spam/validation rejection, admin authorization/login/logout, all admin modules, content creation/deletion, status changes, invalid images, WebP optimization and JSON-LD/robots. Tests use an isolated in-memory SQLite database. No production data is used.

Browser, responsive screenshots, touch/keyboard behavior, Lighthouse and Core Web Vitals could not be measured because this session exposed no browser. CSS includes 320px through desktop breakpoints, focus states and reduced-motion rules; these still need visual browser QA before launch. Build success is not a Lighthouse result.

## Production deployment

1. Provision PHP 8.3+ with Laravel extensions plus GD, MySQL 8+, HTTPS and a queue worker. Point the web root at `public/`, never the project root.
2. Set `APP_ENV=production`, `APP_DEBUG=false`, the correct HTTPS `APP_URL`, a unique generated `APP_KEY`, `SESSION_SECURE_COOKIE=true`, and production MySQL credentials. Use `DB_CONNECTION=mysql`, `DB_HOST`, `DB_PORT=3306`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`. Remove `DB_DATABASE` paths left over from SQLite. Never commit `.env`.
3. Run `composer install --no-dev --optimize-autoloader`, `npm ci`, `npm run build`, `php artisan migrate --force` and `php artisan storage:link`. Run the sample seeder only if demonstration content is desired; it is not required to operate the CMS.
4. Ensure `storage/` and `bootstrap/cache/` are writable by the web service. Back up database and media. Configure the web server to reject execution under `/storage/` and deny dotfiles; the Laravel public `.htaccess` handles Apache front-controller routing.
5. Run `php artisan optimize`, create the administrator interactively, configure SMTP and supervise `queue:work`. Restart queue workers on release.
6. Upload the original logo and approved project photos; replace sample copy and verify contact details, legal copy and map directions. Build favicon assets from the original logo once available.
7. Verify desktop/mobile widths (320, 375, 390, 768, 1024, 1440, 1920), menu focus/escape behavior, filters, comparison control, validation/success states, admin login/uploads/publishing and actual email delivery. Run Lighthouse with production caching and compression.

Use normal production OPcache after verifying your host's PHP installation. A local PHP 8.5.12 OPcache issue required disabling it for the bundled development launcher; no vendor files were altered.

Stock image source IDs are recorded in `public/images/SOURCES.md`. `optimize-images.php` regenerates 640/1024/1600 WebP variants of the included photographs when run with GD. No deployment, domain changes or external messages were performed.
