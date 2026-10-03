# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

BrokerPorez is a Laravel 13 (PHP ^8.3) app for computing Serbian capital-gains tax on brokerage activity. Users import their broker's CSV export of transactions (columns like `Action`, `Time (UTC)`, `No. of shares`, `Price / share`, `Withholding tax`, `Charge amount`). Each transaction is converted to RSD at the official NBS middle exchange rate for its date. Sells are matched against buy lots in FIFO order, and the result feeds the PPDG-3R tax return.

Implemented: auth (registration, login, 6-digit email verification), Trading 212 CSV import, NBS rate lookup, the FIFO ledger, a tabbed dashboard (Pregled / Kapitalna dobit / Dividende / Uvoz) and PDF export of the PPDG-3R and PP OPO tax forms. The tax logic is a port of the user's spreadsheet `trading212_invest_porezi.ods` (sheets "Kapitalna Dobit" and "Dividende", StarBasic macro `IZRACUNAJ_PORESKU_OSNOVICU`); `tests/Fixtures/excel_*.csv` are its rows and the unit tests must keep reproducing them. Domain identifiers, routes, UI text and comments are in Serbian (Latin); the PDF forms are in Cyrillic like the official ones. Keep that convention.

## Commands

```sh
composer setup          # install deps, create .env, key:generate, migrate, npm install + build
composer dev            # run the dev stack (php artisan dev)
npm run dev             # Vite dev server only
npm run build           # production asset build
npm run typecheck       # tsc --noEmit over resources/js/**/*.ts

composer test                               # clears config, then php artisan test
php artisan test --filter=SomeTest          # single test class or method
php artisan test tests/Feature/AuthStraniceTest.php
php artisan test --testsuite=Unit

vendor/bin/pint                             # format code (Laravel Pint)
vendor/bin/pint --test                      # check formatting only
```

**Node ≥ 20.19 is required** (Vite 8, TypeScript 7 native binaries). Run `npm install` under that Node version; installing under an older Node silently skips the platform-specific optional packages and `vite`/`tsc` then fail.

## Database: read this before touching data code

- **The schema is raw SQL, not migrations**, applied by hand to MySQL (`broker_porez`), in this order: `sql/broker_proezi_create_tables.sql` (domain), `sql/auth/korisnici_verifikacija.sql` (verification columns + `verifikacioni_kodovi`), `sql/porezi/porezi_dopune.sql` (`transakcije.kurs` nullable, `kurs_porez`, `jedinstveni_kljuc` dedupe key, `poreski_profil`), `sql/session/laravel_session_table_create.sql`, `sql/cache/laravel_cache_table_create.sql`. `database/migrations/` holds only the stock Laravel tables and is not run against MySQL. New tables go in a new `sql/` file, not a migration.
- `.env` uses MySQL (`broker_porez`), with `SESSION_DRIVER`, `CACHE_STORE` and `QUEUE_CONNECTION` all set to `database`. `phpunit.xml` overrides this to **in-memory SQLite** with array cache and session drivers. Tests therefore only see tables created by migrations; the domain tables won't exist in tests unless migrations are added for them.
- The cache table matters: `RateLimiter` (login throttling, code resend) uses `CACHE_STORE=database`.
- Domain tables use `kreirano_at` and no `updated_at`. Eloquent models for them need custom timestamp handling (e.g. `const CREATED_AT = 'kreirano_at'; const UPDATED_AT = null;`) and explicit `$table` names, because Serbian plurals don't follow Laravel's inflection.

### Domain model (FIFO tax ledger)

- `imovina`: the global asset registry, keyed by **ISIN** (unique). `simbol` stores the last-seen ticker, since tickers can change while ISINs don't.
- `kursevi`: the NBS middle rate per (`datum`, `valuta`), unique on that pair.
- `transakcije`: the raw ledger of CSV rows, scoped by `korisnik_id`. `tip_akcije` holds the broker's raw `Action` string ('Deposit', 'Market buy', 'Dividend', …). `imovina_id` is NULL for deposits and fees. Each money field carries its own currency column, and `kurs` stores the NBS rate applied.
- `poreski_lotovi`: one lot per buy transaction. `preostala_kolicina` is decremented as FIFO consumes the lot.
- `alokacije_lotova_prodaje`: links a sell transaction to the lots it consumed. It records `iskoriscena_kolicina`, which supports fractional shares, and `nabavna_vrednost_rsd`, the proportional cost basis in RSD that PPDG-3R needs.
- Every per-user table carries `korisnik_id` (cascade delete) for multi-user isolation. Always scope queries by user.
- Monetary and quantity columns are `DECIMAL(28,10)` and rates are `DECIMAL(20,10)`. Don't do tax math in PHP floats. Use decimal-safe arithmetic (bcmath, or a decimal cast).

## Tax pipeline

Import → rates → FIFO → reports → forms. Each step lives in `app/Services/`:

1. `Uvoz/Trading212CsvParser` maps T212 columns **by header name** (exports differ: `Time` vs `Time (UTC)`, `+00:00` suffix, optional ID/Notes/Result/fee columns). Times are UTC; the NBS rate date is the **Europe/Belgrade** date. `jedinstveni_kljuc` is a content hash (not the broker ID, which dividends lack), so re-importing overlapping exports is a no-op.
2. `Kursevi/NbsKursService` reads `kursevi` (middle rate by NBS *date of application*, per 1 unit; GBX = GBP/100) and fetches missing dates from `config('porezi.kurs_api_url')` (`/rates/{date}`). `kurs = NULL` means "rate unknown" and is surfaced in the UI; never default it to 1.
3. `Porezi/FifoKalkulator` is pure (no DB) and unit-tested against the spreadsheet; `FifoService::preracunaj()` deletes and rebuilds the user's `poreski_lotovi` / `alokacije_lotova_prodaje`. Call it (or `PrimenaKurseva::primeni()`) after anything that changes transactions or rates.
4. `KapitalnaDobitIzvestaj` / `DividendeIzvestaj` compute the spreadsheet columns per `Period` (`sve`, `2026`, `2026-H1`). Sales whose quantity exceeds imported buys keep the spreadsheet behaviour (basis from available lots only) and are flagged via `nedostaje`.
5. `Obrasci/ObrazacPdf` renders `resources/views/obrasci/*.blade.php` with dompdf (CSS 2.1 only: tables and inline-block, no flexbox; DejaVu Sans for Cyrillic). Form codes (vrsta prijave, šifra vrste prihoda, …) are defaults in `config/porezi.php` and are **unverified** — the UI tells users to check them.

All money math uses `BcMath\Number` via `App\Support\Decimal` (`Decimal::format()` gives Serbian `1.519,96`). Decimal columns are deliberately not cast on models; use `$model->decimal('kolona')`. Every query on per-user tables is scoped by `korisnik_id`.

## Auth

- The auth model is `App\Models\Korisnik` (table `korisnici`), set in `config/auth.php`. The stock `User` model, factory and `users` migration are unused leftovers.
- The password column is `lozinka_hash`, wired through `protected $authPasswordName` plus a `hashed` cast. So `Auth::attempt(['email' => …, 'password' => …])` works unchanged; assign the plain password to `lozinka_hash` and the cast hashes it.
- Email verification is custom (not Laravel's `MustVerifyEmail`). `App\Services\VerifikacijaEmailaService` issues a hashed 6-digit code in `verifikacioni_kodovi` (15 min, max 5 attempts, one active code per user). It sets `korisnici.email_verifikovan_at` on success.
- Routes behind the `verifikovan` middleware alias (`App\Http\Middleware\EmailVerifikovan`, registered in `bootstrap/app.php`) require a verified email. Unverified users are sent to `/verifikacija`.
- `MAIL_MAILER=log`, so verification codes appear in `storage/logs/laravel.log`.
- Validation messages come from `lang/sr/validation.php` (`APP_LOCALE=sr`). It covers only the rules in use, so add messages there when you use a new rule.

## Frontend

Blade + Bootstrap 5 + TypeScript (Tailwind was removed). The layout is `resources/views/layouts/app.blade.php`, with `@yield('naslov')` for the title and `@yield('sadrzaj')` for the body. The entry point is `resources/js/app.ts`. Dashboard pages extend `layouts/knjiga.blade.php` (tabs, `@yield('knjiga')`). Design tokens (paper/ink/stamp-blue) are CSS variables at the top of `resources/css/app.css`; `<x-kucice :iznos>` renders the amount due in tax-form boxes. Page behaviour is attached through `data-*` attributes: `data-toggle-lozinka`, `data-poklapa-se-sa`, `data-kod-unos`, `data-odbrojavanje` (`resources/js/auth.ts`) and `data-auto-submit`, `data-potvrda`, `data-lista-fajlova` (`resources/js/dashboard.ts`). Feature tests that render views need `$this->withoutVite()`.
