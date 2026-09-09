# Digital Invitation Platform — Build Plan

Status: **draft, not started**. Nothing in this plan has been executed. Review, edit, strike out
what you don't want, then hand it back for execution phase by phase (or all at once).

## 0. Product shape (so every later phase agrees on the same nouns)

Three route surfaces, as you specified:

| Route | Who | Purpose |
|---|---|---|
| `/` | Public / anonymous | Marketing landing page: what this is, theme gallery preview, pricing/plans, "start now" (self-service) CTA, "request custom" CTA |
| `/user` | Authenticated subscriber | Manage own invitation(s): pick a theme or start blank, edit with the Layup builder, RSVP dashboard, publish/schedule, billing |
| `/admin` | Staff | Manage all users, subscriptions, invitations, the theme catalog, and the custom-request queue |

Two ways a customer ends up with a published invitation:

1. **Self-service** — subscribes to a plan, picks a theme (or blank), edits it themselves in `/user` using the Layup builder, publishes.
2. **Custom request** — fills a brief instead of building anything; it lands in `/admin` as a request; staff builds/edits the invitation on the customer's behalf (still just an `Invitation` record owned by that customer, edited by staff via `/admin`); staff marks it delivered.

Both paths converge on the same `Invitation` model/editor — custom requests aren't a separate system, just a different *who* is driving the builder and a request/queue wrapper around it.

## Decisions made

- **Frontend stack.** Blade + Alpine.js + Tailwind — not React. Livewire is already a transitive dependency of Filament (v4.4.4, confirmed via `composer show`), and Livewire ships Alpine automatically, so both are present with zero new packages. Layup's own widgets (entrance animations, Countdown, Gallery lightbox, Accordion, etc.) are already built on this exact stack. **GSAP** is added for extra animation polish beyond Layup's built-in entrance effects (hero reveal, custom easing/timelines on invitation themes) — loaded as a plain script, no build-tool integration needed beyond Vite already bundling it. The RSVP submission on the public invitation page can be a plain form post; a small single-purpose Livewire component is fine if reload-free submission is wanted, but that's an implementation detail, not a new dependency.
- **Payment gateway: Mayar.id** (existing account). No Laravel Cashier equivalent exists for Mayar — this will be a hand-rolled integration (thin `MayarClient` service class), detailed in Phase 4.
- **Image/file storage: Cloudflare R2.** S3-compatible, so Laravel's built-in `s3` filesystem driver works against it directly — no Mayar-style custom integration needed, just disk config + credentials.
- **Database: MySQL** (dev and prod — replaces the SQLite default that ships with the Laravel skeleton).
- **Tenancy mechanism for `/user`.** Filament's built-in panel tenancy puts the tenant id/slug in the URL (e.g. `/user/{tenant}/invitations`), which conflicts with your flat `/user` requirement. Plan uses **manual query scoping** (every resource query constrained to `auth()->id()`) instead of Filament tenancy — simpler, matches the flat URL, costs us Filament's automatic tenant-switcher UI, which we don't need since each customer only ever sees their own data.

- **Billing model: recurring subscription**, monthly or yearly, priced in **IDR (Rupiah)**. Each plan is offered at both intervals; yearly is priced at roughly 10× the monthly rate (~2 months free) as the standard SaaS discount pattern. `Plan` becomes one row per tier-per-interval (e.g. "Plus Monthly" and "Plus Yearly" are separate `Plan` records, each with its own `mayar_tier_id` — Mayar's tier/membership model is naturally one tier per price+interval, this maps directly).

### Plan tiers (IDR — confirmed)

| Tier | Monthly | Yearly | Invitations (while subscribed) | Key features |
|---|---|---|---|---|
| Starter | Rp 49.000 | Rp 490.000 | 1 | Branding shown, RSVP capped ~50 guests, standard themes only |
| Plus | Rp 99.000 | Rp 990.000 | 3 | Branding removed, RSVP capped ~300 guests, standard + premium themes |
| Pro | Rp 199.000 | Rp 1.990.000 | 10 | Unlimited RSVP, custom domain, priority support |
| Organizer | Rp 499.000 | Rp 4.990.000 | Unlimited | For EOs/vendors reselling to clients, white-label option |

"Invitations (while subscribed)" is the cap on `Invitation` records the account can have while the `Subscription` is `active` — not a per-billing-period reset, since an invitation is a lasting piece of content, not a consumable. Cancelling/expiring doesn't delete existing invitations but should stop further creation and can optionally re-lock premium features (confirm desired behavior in Phase 4).

## Still open (flagged, not blocking — pick when you review)

- **Domain model for public invitation URLs.** Assuming `yourapp.com/i/{slug}` to start; custom domains per invitation (premium feature, gated to Pro/Organizer per the table above) is called out as a stretch item, not in the base plan.

---

## Phase 1 — Foundations

- [ ] Finish wiring `crumbls/layup` (currently required but inert): register `LayupPlugin` in `AdminPanelProvider`, publish `config/layup.php`, run `layup:install`.
- [ ] Decide and set `pages.enabled = false` / `frontend.enabled = false` in `config/layup.php` — we're not using the bundled Pages CMS, we're subclassing `Page` for our own `Invitation` model (see Phase 2).
- [ ] `php artisan storage:link`, confirm `filament:assets` runs cleanly.
- [ ] Add `role` (enum: `admin`, `customer`) to `users` table, or a lightweight `App\Enums\UserRole` + column — needed to gate `/admin` vs `/user` panel access via `canAccessPanel()`.
- [ ] Switch `DB_CONNECTION` to `mysql` (dev and prod) and update `.env`/`.env.example`; drop the SQLite skeleton default.
- [ ] Configure Cloudflare R2 as the default filesystem disk: `composer require league/flysystem-aws-s3-v3` (Laravel's `s3` driver, R2 is S3-compatible), add an `r2` disk in `config/filesystems.php` (`driver: s3`, R2 endpoint, `use_path_style_endpoint: true`), set `FILESYSTEM_DISK=r2` and R2 credentials in `.env`. Point `config/layup.php` → `uploads.disk` at `r2` so all `FileUpload` fields (theme previews, invitation images/galleries) go straight to R2.
- [ ] Pull in GSAP for the public-facing animation layer: `npm install gsap` (or CDN script tag in the invitation/marketing layout — either works with Vite already in place), import where needed in `resources/js`.
- [ ] Confirm Mayar.id sandbox vs production API base and get API keys before Phase 4 (see Phase 4 for the exact env vars).

## Phase 2 — Domain model ✅ done

- [x] `Invitation` model, subclassing `Crumbls\Layup\Models\Page` (per Layup's "swapping the page model" pattern) — inherits revisions, scheduled publishing, SEO/JSON-LD, slug handling for free.
  - Migration adds: `user_id` (owner), `event_date`, `host_name`, `venue`, `theme_id` (nullable, which starter theme it was created from), `is_custom_build` (bool — created via custom-request path). Also carries the base Page columns (`parent_id`, `path`, `content`, `status`, `published_at`, `meta`, `featured_image`, `author`) since Layup's bundled migrations are skipped while `pages.enabled = false` — verified via tinker that create/save/revision/path-generation all work identically to the bundled `Page`.
  - `config/layup.php` → `pages.model = App\Models\Invitation::class`, `pages.table = invitations`. Own `layup_page_revisions` migration added too (Layup's own is also skipped), FK'd to `invitations`.
- [x] `Theme` model — starter templates for the picker. Fields: `name`, `description`, `preview_image`, `content` (JSON), `category`, `is_active`.
  - Seeded: Blank + Elegant Wedding, Modern Birthday, Corporate Event (Hero/Countdown/Gallery/Map/Testimonial widgets).
- [x] `Plan` model — matches the confirmed IDR tiers. Seeded 8 rows (Starter/Plus/Pro/Organizer × monthly/yearly) via `PlanSeeder`.
- [x] `Subscription` model — `user_id`, `plan_id`, `status`, `mayar_member_id`, `mayar_invoice_id`, `current_period_end`.
- [x] `CustomRequest` model — as specified, plus `assignedAdmin()`/`invitation()`/`user()` relations.
- [x] `Guest` model (went with a single model rather than separate Guest/Rsvp — one row already captures the full RSVP state) — `invitation_id`, `name`, `attending` (`App\RsvpStatus` enum: Attending/NotAttending/Maybe), `party_size`, `message`, `responded_at`.
- [x] Factories + seeders for all of the above — verified end-to-end via tinker (create, relations, enum casts, revision auto-save all confirmed working).
- [x] Policies: `InvitationPolicy` (owner-or-admin), `CustomRequestPolicy` (owner-or-staff) — written, not yet wired to any panel (that's Phase 3).
- New: `App\SubscriptionStatus`, `App\CustomRequestStatus`, `App\RsvpStatus` enums added (same pattern as Phase 1's `App\UserRole`) for the status/attending columns above.
- Fixed while building: `SubscriptionFactory`'s default `plan_id` was colliding with the seeded `Plan` catalog's unique `(tier, billing_interval)` constraint — now reuses an existing `Plan` row when one exists, falls back to the factory only when the table is empty.

## Phase 3 — Auth & panel access ✅ done

- [x] Registration flow for customers — enabled Filament's built-in `->registration()` on the `user` panel (`/user/register`). No custom Register page needed: the registration form only collects name/email/password, and `role` isn't among them, so every self-registered user gets `role = customer` from the migration's column default. Admin accounts are staff-provisioned only (seeding/tinker), no self-registration on `/admin`.
- [x] `User implements FilamentUser`, single `canAccessPanel()` keyed off `$panel->getId()`: `admin` → `UserRole::Admin`, `user` → `UserRole::Customer`, anything else → false. Verified via tinker against both seeded test users (admin@example.com / test@example.com) — correct in both directions, and via HTTP that `/admin` redirects guests to login (302) while `/user/login` and `/user/register` both resolve (200).
- [x] New `UserPanelProvider` (`id: 'user'`, `path: 'user'`, scaffolded via `php artisan make:filament-panel user` and auto-registered in `bootstrap/providers.php`) — distinct primary color (Blue vs admin's Amber) so the two panels are visually distinguishable, resources auto-discovered from `App\Filament\User\Resources` (kept separate from admin's `App\Filament\Resources` namespace on purpose, since Phase 6/7 need distinct `InvitationResource` classes with different query scope per panel). `LayupPlugin` registered here too (mirrors Phase 1's admin setup) since the `/user` panel's `InvitationResource` will need the builder field in Phase 6.
- [x] Redirect logic on `/`: implemented as an immediate redirect for already-authenticated visitors (`/admin` or `/user` depending on role) rather than a CTA swap on the marketing page — the current `welcome.blade.php` is still Laravel's default placeholder, not the real marketing page Phase 5 builds, so redirecting away is simpler than theming a page that's about to be replaced anyway. **Worth revisiting in Phase 5** if you'd rather a logged-in visitor see the real marketing page with a "Go to dashboard" CTA instead of bouncing immediately — easy to swap then.
- [ ] Resource/query scoping to `auth()->id()` — **deferred to Phase 6/7**, not done here: there are no Filament resources anywhere in the app yet (`app/Filament/Resources` was empty), so there's nothing to scope. The convention itself is already decided (manual scoping, not Filament tenancy — see "Decisions made" above); it gets applied the moment `InvitationResource`/`CustomRequestResource` are created.

## Phase 4 — Billing & subscriptions (Mayar.id) ✅ done

No Cashier-equivalent exists for Mayar, so this is a hand-rolled integration. Core principle from Mayar's own docs: **the browser redirect is UX, the webhook is truth** — never mark a subscription active on redirect alone, only on a confirmed webhook.

**Real API shapes, confirmed against `docs.mayar.id/api-reference-v2` (not guessed):**
- `POST /hl/v2/memberships/members/create` — body: `productId`, `membershipTierId`, `customerInfo.{name,email,mobile}`, `membershipMonthlyPeriod`. Returns `data.membershipCustomer.{id, memberId, expiredAt, ...}`.
- `POST /hl/v2/memberships/members/{memberId}/invoice/create` — `{memberId}` is the short `memberId` code (e.g. `MBR8X2QK`), not the `id` UUID. Body: `productId`. Returns `data.{id, membershipBillUrl, expiredAt}`.
- **Important correction to the original plan**: `membershipTierId` and billing interval are *orthogonal* — one Mayar tier covers both monthly and yearly via `membershipMonthlyPeriod` (1 or 12), priced per-period inside Mayar's own dashboard. So Mayar-side you create **4 tiers** (Starter/Plus/Pro/Organizer), not 8. Locally, `Plan.mayar_tier_id` is **shared** across a tier's monthly and yearly row (e.g. "Starter Monthly" and "Starter Yearly" both point at the same Mayar tier id) — no schema change needed, just how the column gets populated.
- Webhook signature verification is genuinely undocumented publicly (confirmed via docs + a working integration writeup) — implemented as a shared `?token=` query param on the registered webhook URL instead. **Still flagged as needing reconfirmation with Mayar support before relying on it in production.**

- [x] Env vars — `MAYAR_API_KEY`, `MAYAR_WEBHOOK_TOKEN`, `MAYAR_IS_PRODUCTION` were already in `.env` (user-provided). Added `MAYAR_PRODUCT_ID` (placeholder, empty — **your action item**: create one Mayar membership product with 4 tiers, monthly+yearly priced per tier, paste the product id here and each tier's id into the matching `Plan` rows' `mayar_tier_id`. Nothing checkout-related will actually succeed against real Mayar until this is done). Config centralized in `config/services.php` under `mayar`, base URL derived from `is_production` (`api.mayar.id` vs `api.mayar.club`) unless `MAYAR_API_BASE` overrides it.
- [x] `App\Services\Mayar\MayarClient` — `createMember()`, `createInvoice()`, matching the confirmed shapes above.
- [x] `Subscription` model — unchanged from Phase 2, fields already fit.
- [x] `App\Services\SubscriptionCheckoutService::checkout(User, Plan): string` — creates the `Subscription` row `pending`, calls Mayar, returns `membershipBillUrl`. `SubscriptionController@checkout` (`POST /subscribe/{plan}`, `auth` middleware) drives it and redirects.
- [x] `MayarWebhookController` (`POST /webhooks/mayar`, CSRF-exempted in `bootstrap/app.php`) — token check via `hash_equals`, every delivery logged, `MayarWebhookEvent` table records `event_key` (event + data id, or a payload hash) before acting so retries are no-ops. Handles `payment.received`, `membership.memberExpired`, `membership.memberUnsubscribed`, `membership.changeTierMemberRegistered`; unhandled events are logged, not errored. Always returns 200 once accepted.
- [x] Entitlement checks — `User::activeSubscription()`, `currentPlan()`, `canCreateInvitation()` (false with no active subscription — there's no free tier). Not wired into any UI yet since `InvitationResource` doesn't exist (Phase 6).
- [x] Billing status page inside `/user` (`/user/billing`, Filament `Billing` page) — current plan + renewal date, honest note that Mayar doesn't expose a self-service portal URl via API so there's no "manage" link, plus plain HTML forms (grouped by tier) to subscribe. Collects phone number inline when missing (required by Mayar's `customerInfo.mobile`) — added a `phone` column to `users` for this.
- [ ] `Plan.mayar_tier_id` population — still null on all 8 seeded rows. **Blocked on your Mayar-side product/tier setup**, see above.

**Bugs found and fixed while building this:**
- `SubscriptionController`'s `auth` middleware redirected unauthenticated requests to a bare `route('login')` that doesn't exist (only the two panels' own named login routes do) — would have 500'd for every guest. Fixed via `Authenticate::redirectUsing()` in `AppServiceProvider` pointing at `/user/login`.
- The Billing page's first draft stored `plansByTier` (a grouped collection of collections) as a public Livewire property, which crashed on mount — Livewire's Eloquent-collection synthesizer can't serialize a collection of collections. Fixed by switching both `activeSubscription` and `plansByTier` to Livewire `#[Computed]` methods instead of stored public state (correct pattern for read-only display data anyway).

**Tests added** (`tests/Feature/MayarWebhookControllerTest.php`, `SubscriptionControllerTest.php`, `Services/SubscriptionCheckoutServiceTest.php`, `Filament/User/BillingPageTest.php`) — 13 new tests, all outbound Mayar calls faked via `Http::fake()` + `Http::preventStrayRequests()`, covering: token verification, webhook idempotency, payment/expiry/tier-change handling, checkout's phone/tier-id guard clauses, and panel access to the billing page. Full suite: 18/18 passing.

## Phase 5 — Landing page (`/`) ✅ done

Design approach: rather than a generic SaaS-gradient page, the visual language is grounded in the actual subject — invitation stationery. Palette is warm ivory "cardstock" (`--color-paper`), warm ink, a deep ceremonial pine-green accent, and a muted brass/foil accent — deliberately *not* the cream+terracotta combo that reads as an AI-generated default. Display type is **Fraunces** (added via the project's existing Bunny Fonts convention in `vite.config.js`, same pattern as the existing Instrument Sans), paired with the existing Instrument Sans for body/UI. One deliberate motion moment (GSAP hero entrance — headline, subcopy, CTA row staggered in, plus the invitation-card mockup), not scattered hover effects. All design tokens live in `resources/css/app.css`'s `@theme` block (`bg-paper`, `font-display`, `text-pine`, etc. — verified compiled into real Tailwind utilities).

- [x] Hero — headline/subcopy/CTA left, a static invitation-card mockup right (hand-built HTML/CSS, not a generic image) as the GSAP entrance target.
- [x] Theme gallery — `LandingController` pulls `Theme::active()->latest()->limit(6)`. Since **authenticated users never reach this page at all** (redirected straight to their panel, see below), every CTA here — theme cards, pricing, nav — simply links to `/user/register`. There's no separate logged-in-vs-guest CTA branching to build.
- [x] Pricing — plans grouped by tier (reusing the same `groupBy('tier')` pattern as the Billing page), monthly price primary with a yearly note, feature bullets via a new `Plan::featureBullets()` model method (translates the stored `features` flags into plain language — reusable anywhere pricing is shown).
- [x] "Prefer we build it for you?" section + inline custom-request form, anchored at `#custom-request`.
- [x] `CustomRequestController@store` — validates, creates a `CustomRequest` (`status = new`); for guests, `firstOrCreate`s a `User` by email (random password, `role = customer`) rather than erroring or requiring signup first. Existing-email guests get attached to their existing account rather than duplicated.
- [x] Basic SEO — title, meta description, OG title/description/url, canonical, twitter:card. No `og:image` yet (nothing to reference honestly — add one when there's real brand art).
- Replaced Laravel's default `welcome.blade.php` entirely (it was unused starter-kit boilerplate); `LandingController@index` now also owns the already-existing "redirect authenticated visitors to their panel" logic moved here from the route closure.
- Enabled `->passwordReset()` on `UserPanelProvider` — required for this phase's own custom-request flow to make sense: a guest-created account (random password, never told to them) would otherwise have no way to ever log in.

**Known gap, flagged not fixed**: `config('app.name')` is still the Laravel default (`APP_NAME=Laravel` in `.env`), so the page title and header/footer wordmark literally say "Laravel" right now. Not something to guess a brand name for — **set `APP_NAME` to your actual product name** whenever you've decided it.

**Tests added**: `LandingControllerTest` (active-only theme listing, plan grouping, both redirect cases), `CustomRequestControllerTest` (guest account creation, existing-email reuse, authenticated shortcut, validation). Also fixed the pre-existing `ExampleTest` and added `LazilyRefreshDatabase` to it — it never needed the database before this phase, now the route it hits does. Full suite: 28/28 passing.

## Phase 6 — `/user` panel (self-service editing) ✅ done

- [x] `InvitationResource` (`App\Filament\User\Resources\Invitations`) — title/slug (auto-filled from title on create, `->live(onBlur:true)`), host name, venue, event date, status/publish-at, and `LayupBuilder::make('content')`. **This is the first real resource in `/user`, so it's where the Phase 3-deferred query-scoping convention actually gets applied**: `InvitationResource::getEloquentQuery()` filters to `user_id = auth()->id()`.
- [x] "Create invitation" flow — a `theme_id` select (active themes only, create-only field) that's `->live()` with `afterStateUpdated()` setting the `content` field's live state directly from the theme's JSON. Deliberately *not* done via `mutateFormDataBeforeCreate()` — that would silently discard any edits the customer made in the builder before submitting, since it runs after the whole form (including their live builder edits) is already filled in. Blank start is the default (no theme selected).
- [x] `plan.invitation_limit` enforcement — `InvitationPolicy::create()` now calls `$user->canCreateInvitation()` (built in Phase 4, unused until now). Admins bypass it unconditionally (they create invitations for custom requests regardless of the customer's plan).
- [x] Publish/schedule controls — plain `status` select + `published_at` picker; the smart behavior (auto-set `published_at` on publish, auto-reclassify a future-dated publish as `scheduled`) is entirely inherited from `Page::booted()`, nothing custom needed.
- [x] Revision history — `RevisionsRelationManager` (read-only table: saved-at, author, note + a "Restore" action). No Filament UI ships with Layup for this, only model methods — built from scratch.
- [x] RSVP dashboard (`/user/rsvps`, `App\Filament\User\Pages\Rsvps`) — table of `Guest` rows scoped to the customer's invitations, an attending-count summary (sums `party_size` where `attending = Attending`), CSV export.
- [x] Account/billing — already fully built in Phase 4 (`/user/billing`), nothing further needed here.

**Two real bugs found and fixed while building this** (both caught by tests failing unexpectedly, not by inspection):
1. **`Filament\Forms\Components\Section` doesn't exist in this installed Filament 5 version** — it's `Filament\Schemas\Components\Section` (forms/infolists were unified under "Schemas" in Filament 5). Wrong-namespace guesses from general Filament knowledge don't hold for this specific version; confirmed via `find` in `vendor/filament` before trusting it.
2. **`User::canAccessPanel()` was wrongly denying freshly-created customers within the same request.** Root cause: `role` has a DB column default (`'customer'`) but nothing set it on the in-memory model after `create()` — Eloquent doesn't re-fetch DB-default columns after an insert, so `$user->role` was `null` (not the enum) until the *next* request re-hydrated the model from the database. This isn't just a test-fixture quirk: **Filament's own registration flow only submits name/email/password**, so a real customer immediately after self-registering would have hit this exact gap on their first request. Fixed at the model level with a PHP `protected $attributes = ['role' => 'customer']` default (mirrors the DB default so both stay in sync), not by patching individual factories.

Also learned mid-phase: Layup's revisions snapshot content *as of* each save (a checkpoint trail), not "the version before this edit" — the first test written against the relation manager assumed the opposite and consequently failed for the right reason (the assertion's premise was wrong, not the restore logic). Confirmed against Layup's own docs wording before rewriting the test.

**Tests added**: `InvitationResourceTest` (owner-only list/edit scoping, create blocked with no subscription, create blocked past `invitation_limit`, theme-content cloning), `RsvpsPageTest`, `RsvpExportControllerTest`, `RevisionsRelationManagerTest`. Filament resource/page tests needed `Filament::setCurrentPanel(Filament::getPanel('user'))` in `setUp()` — `Livewire::test()` doesn't traverse the `/user/*` URL, so without it Filament resolves routes/policies against the default (`admin`) panel instead. Full suite: 39/39 passing.

**Added later (post-Phase-11, user-reported gap)**: there was no way to see an invitation's actual rendered design from either panel — Phase 8's public `/i/{slug}` route only serves `published` invitations, so a "Preview" link pointing at it would 404 for every draft (i.e. almost every invitation actively being designed). Added `InvitationPreviewController` (`GET /invitations/{invitation}/preview`, `auth` middleware) — extends the public `InvitationPageController` but swaps the published-only lookup for a plain `findOrFail()` and swaps the "must be published" gate for `Gate::authorize('view', $record)` (owner or admin, reusing the existing `InvitationPolicy`, not a new rule). A "Preview — not published yet" banner appears in the shared invitation layout whenever `$layupPage->status !== 'published'`, so it's unambiguous when previewing a draft. Wired into both `EditInvitation` pages (header action) and both `InvitationsTable`s (row action), admin and customer alike. Tests: owner can preview their own draft/published invitation, admin can preview anyone's, a different customer gets 403, guest redirected to login. Full suite: 94/94 passing.

**Bug found immediately after, via the user hitting it live**: `TypeError` in `HasLayupContent::buildRowTree()` — `Argument #1 ($rowData) must be of type array, string given`. Root cause was mine: two leftover invitations in the dev database from a Phase 8 tinker debugging session (`content: {"rows": ["second"]}` — a placeholder string used to test revision-tracking at the data level, never meant to be rendered, and never cleaned up). Deleted both. But the crash exposed a real, general robustness gap worth fixing regardless of that specific stale data: Layup's own `ContentValidator` deliberately *warns rather than blocks* malformed content from being saved (`Page::booted()`, "widget data issues are soft errors") — yet `buildRowTree()` has zero error handling for a malformed row/column/widget entry (unlike individual widgets, which are already wrapped in try/catch), so a single bad entry anywhere in that shape hard-crashes the entire page with a 500. Since Layup's own policy already allows that data to exist, the render path has to tolerate it.

Fixed by overriding `Invitation::getLayupContent()` to sanitize the content tree (rows → columns → widgets, all levels) before it reaches Layup's builder, dropping non-array entries instead of crashing on them. Deliberately scoped to only the *render* path — Filament's builder binds directly to the raw `content` column, not through this method, so a customer can still open the builder and see/fix malformed data rather than have it silently vanish from the editor too.

Verified the regression test actually catches the bug, not just that it passes: `git stash`ed the fix, re-ran the test, got the exact byte-for-byte error message the user reported, then restored the fix and confirmed green. Full suite: 96/96 passing.

**Follow-up gap, also user-reported**: Preview only ever existed for `InvitationResource` — `ThemeResource` never got one, in either panel. Turned out to need its own path rather than reusing `InvitationPreviewController`, since `Theme` isn't a `Page` subclass: Layup's default frontend view and `<x-layup-seo />` both unconditionally call Page-only methods (`getMetaTitle()`, `getUrl()`, `getFeaturedImageUrl()`, `$page->meta`) that `Theme` doesn't have. Added `HasLayupContent` to `Theme` (documented by Layup for exactly this — "add Layup content rendering to any Eloquent model") plus a minimal `getMetaTitle()`, a `ThemePreviewController` (admin-only — themes have no customer-facing route, so no `InvitationPolicy`-style owner check applies) and a dedicated `layouts.theme-preview` layout that skips `<x-layup-seo/>` entirely (SEO meta is meaningless for a template that's never itself a published page). Wired into `ThemeResource`'s edit page and table the same way as Invitation's. Tests added per Boost's project rule (prefer tests over tinker/verification scripts): `ThemePreviewControllerTest` plus two assertions in `ThemeResourceTest` proving the preview link actually renders in both the list and edit page HTML — not just that the route works in isolation, since that gap (route fine, button not rendered) is exactly what "no preview button" turned out to mean for `Invitation` once already. Full suite: 102/102 passing.

## Phase 7 — `/admin` panel ✅ done

- [x] `InvitationResource` (`App\Filament\Resources\Invitations` — separate class from the customer panel's, no query scoping, sees every owner's invitations) — same fields as the customer version plus an `user_id` owner-reassignment select and an `is_custom_build` toggle.
- [x] `UserResource` — name/email/phone/role, password only required on create (blank = unchanged on edit), table shows role badge, current plan (via `currentPlan()`), invitation count. Deliberately no bulk-delete action — deleting a `User` cascades to all their invitations/subscriptions/custom-requests (Phase 2's FK design), so bulk delete stayed off the table to reduce the blast radius of a misclick; single delete still available with Filament's default confirmation.
- [x] `ThemeResource` — full CRUD with `LayupBuilder::make('content')`, `FileUpload` for `preview_image` (on the `r2` disk, same as everywhere else), category/active filters.
- [x] `CustomRequestResource` — queue table with an inline-editable status `SelectColumn` (no separate transition actions needed), "Assign to me" row action, and a "Build this" action. The build action is a **plain HTTP redirect via a dedicated controller** (`CustomRequestBuildController`, `GET /admin/custom-requests/{customRequest}/build`), not a Filament action closure — same reasoning as Phase 4's billing buttons: a redirect-after-side-effect from inside a Livewire action is uncertain territory, a real controller isn't. It creates the `Invitation` (owned by the requester, `is_custom_build = true`) only if one isn't already linked, bumps `new` → `in_review`, then redirects straight into the admin's own `InvitationResource` edit page for it.
- [x] `PlanResource` — full CRUD including the `features` JSON via dot-notation form fields (`features.remove_branding` etc. — Filament binds these directly against the array-cast column). Table flags any plan with no `mayar_tier_id` configured in red, so the still-outstanding Mayar setup from Phase 4 stays visible.
- [x] `SubscriptionResource` — full CRUD; setting `status` to `active` directly here *is* the "comp a subscription" mechanism — no separate action needed, editing the field is the override.
- [x] `AdminOverview` dashboard widget — active subscriptions + estimated MRR (yearly plans normalized to monthly), custom-request backlog (new/in_review/in_progress), invitations published this calendar week.
- Extracted `RevisionsRelationManager` (Phase 6) out of the customer-only namespace into `App\Filament\RelationManagers` so both `InvitationResource` classes share the exact same read-only revision history + restore UI — identical behavior in both panels, only the surrounding resource's access scope differs.

**One thing worth knowing, not a bug**: `AdminOverview`'s stats never appear in a plain `GET /admin` response — Filament widgets default to lazy-loading (`Filament\Support\Concerns\CanBeLazy`, `$isLazy = true`), so content loads via a follow-up Livewire request the initial page load doesn't include. First test written against it failed for exactly this reason; fixed by testing the widget component directly via `Livewire::test(AdminOverview::class)` rather than hitting the dashboard route.

**Tests added**: one resource-access test pair (admin can view / customer forbidden) per resource, `assignToMe` and the widget tested via `Livewire::test()`, and a dedicated `CustomRequestBuildControllerTest` covering the non-admin-forbidden case, invitation creation + linking + status bump, and that reopening an already-built request reuses the same invitation rather than creating a second one. Full suite: 56/56 passing.

## Phase 8 — Public invitation frontend ✅ done

This phase surfaced three real, pre-existing bugs — one significant enough that it would have broken every photo on every invitation in production. All found and fixed by testing actual rendered output against real behavior rather than trusting that things compiled.

### Bug 1 (significant): every media widget ignored the configured upload disk entirely

Layup's bundled widget views (`image`, `gallery`, `hero`, `testimonial`, `card`, `person`, `banner`, `slider`, `masonry`, `logo-grid`, `logo-slider`, `avatar-group`, `team-grid`, `before-after`, `hotspot`, `image-hotspot`, `image-card`, `image-text`, `blurb`, `audio`, `file-download`, `testimonial-carousel`, `testimonial-slider` — 25 occurrences across 23 files) all hardcoded `asset('storage/' . $data['src'])` — the `public`-disk storage-symlink convention — regardless of `config('layup.uploads.disk')`. Since that's been `r2` since Phase 1 (your explicit ask), **every uploaded photo on every invitation would have rendered a broken image** — a launch-blocking defect for a product whose entire point is photo-rich invitations.

Fixed via Laravel's standard vendor-view-override mechanism: `php artisan vendor:publish --tag=layup-views`, then a mechanical regex replace of the one broken pattern (identical shape everywhere: `asset('storage/' . EXPR)` → `\Illuminate\Support\Facades\Storage::disk(config('layup.uploads.disk', 'public'))->url(EXPR)`) across all 25 occurrences. Backward-compatible — for the `public` disk this produces the same result as before, so it's a strict improvement, not a breaking change.

**Trade-off worth knowing**: `resources/views/vendor/layup/` now shadows those 23 files permanently — Laravel always prefers the published copy over the package's own. A future `composer update` improving these specific templates won't reach the app automatically; re-diff `vendor/crumbls/layup/resources/views/components/` against the published copies occasionally, or re-apply this same patch after upgrading.

### Bug 2: every invitation's SEO/OG/canonical URL pointed at a route that doesn't exist

`config('layup.frontend.prefix')` was still `'pages'` (Layup's bundled default) even though `frontend.enabled = false` disabled that route back in Phase 1. `Page::getUrl()` (used for `og:url`, canonical, and JSON-LD `url`) reads that config regardless of whether the route is enabled — so every shared invitation link would have previewed a dead `/pages/{slug}` URL instead of the real `/i/{slug}`, defeating this phase's own "shared links render a proper preview card" requirement. Fixed with a one-line config change (`prefix: 'i'`) — no code changes needed since `getUrl()` already builds from this value.

### Bug 3: Layup's own `layup:make-widget` generator scaffolds broken code

The generated `RsvpFormWidget` stub declared `public static function getViewName(): string`, but the parent (`BaseBladeWidget`) declares it as a non-static instance method — an incompatible override that's a PHP **fatal error** (not an exception — it crashed the PHP process outright, which is what actually blocked the first test run here). Fixed by correcting the override to `protected function getViewName(): string` and pointing it at the file's actual location (the generator's default-convention view path assumption doesn't match where it writes the file, either).

### What was built

- [x] `InvitationPageController extends AbstractController`, serving `/i/{slug}`, `published()`-scoped (404 on draft/unpublished/unknown).
- [x] `resources/views/components/layouts/invitation.blade.php` — deliberately neutral chrome (white background, base font, no imposed marketing palette) since the page renders customer-authored content, not our brand.
- [x] Guest-name personalization (`?to=Jane`) via a small banner, not a fragile content-placeholder-replacement scheme — implemented via `view()->share('guestName', ...)` in `getViewData()`, mirroring the exact pattern `AbstractController` itself already uses for `layupPage` (Blade components don't inherit parent-view scope, so this is the correct mechanism, not a workaround).
- [x] `RsvpFormWidget` (custom Layup widget, `App\Layup\Widgets`, auto-discovered) — posts to `POST /i/{invitation:slug}/rsvp` (`RsvpSubmissionController`), 404s on unpublished invitations, creates a `Guest` record. Plain form post, no Livewire needed.
- [x] Separate Vite entry (`invitation.css`/`invitation.js`) rather than reusing the marketing bundle — Alpine.js (new dependency, `npm install alpinejs`) is only needed here, not on the landing page, so it stays out of that bundle. `@layupScripts` (theme CSS + all `Alpine.data(...)` registrations) is emitted automatically as part of Layup's own content-loop template — no manual wiring needed beyond rendering `{{ $slot }}`.
- [x] GSAP hero-reveal (guest banner fade-in, reduced-motion respected).
- [x] SEO confirmed correct end-to-end (title, OG, canonical, JSON-LD) — including the prefix fix above.
- [x] Image/upload-disk resolution confirmed correct end-to-end (Bug 1 above) — tested via a Mockery expectation on `Storage::disk('r2')->url(...)` rather than `Storage::fake()`, since fake() returns its own generic `/storage/{path}` convention from `url()` regardless of a disk's actual configured URL, which would have silently passed a still-broken implementation.

**Tests added**: `InvitationPageControllerTest` (render, draft/unknown → 404, guest-name banner shown/hidden, SEO title, OG URL correctness, disk-aware image resolution), `RsvpSubmissionControllerTest` (valid submission, validation, blocked on unpublished, party-size default). Full suite: 68/68 passing.

## Phase 9 — Domain-specific widgets (custom Layup widgets) ✅ done

User confirmed all three optional extras.

- [x] `RsvpForm` widget — built in Phase 8 (the public page needed it to function).
- [x] `GiftInfoWidget` (`gift-info`) — heading, intro text, a `Repeater` of bank/e-wallet accounts (bank name, account number, holder) with a copy-to-clipboard button per account, plus an optional digital-gift link button. Accounts with no number are skipped at render time (a partially-filled repeater row shouldn't show a blank card).
- [x] `MusicPlayerWidget` (`music-player`) — a fixed floating play/pause button, `<audio loop>`. Best-effort autoplay attempt behind a try/catch (browsers block audio autoplay until user interaction — the toggle button always works regardless, so this is honest about what autoplay can and can't guarantee rather than pretending it always works). Applied Phase 8's lesson immediately: resolves the audio URL via `Storage::disk(config('layup.uploads.disk'))->url(...)`, not the hardcoded `asset('storage/...')` pattern that was broken everywhere else.
- [x] `LoveStoryTimelineWidget` (`love-story-timeline`) — a themed preset subclassing Layup's built-in `TimelineWidget` (not a from-scratch widget): overrides type/label/icon/default placeholder events ("How We Met" / "First Date" / "The Proposal" instead of the generic company-timeline defaults), reuses the parent's form schema and rendering entirely unchanged.
- [x] `php artisan layup:doctor` — 12/12 passing (100 widgets: 95 built-in + 4 custom). `layup:list-widgets` confirms all four.
- Added `[x-cloak] { display: none !important; }` to `invitation.css` — the standard Alpine convention, needed once these widgets started using `x-cloak` (copy-confirmation text, play/pause icon swap) so toggled content doesn't flash visible before Alpine initializes.

**One more tool limitation found**: `layup:doctor`'s Blade-view check is hardcoded to two conventional path guesses (`layup::components.{type}` / `components.layup.{type}`) — it doesn't actually call a widget's real `getViewName()`, so a widget that legitimately overrides it (as `LoveStoryTimelineWidget` initially did, to reuse `TimelineWidget`'s view) reports a false-positive failure. Rather than fight the tool, simplified: dropped the override and placed a one-line pass-through view at the conventional path instead (`@include('layup::components.timeline', ...)`) — cleaner than maintaining two mechanisms doing the same job, and it satisfies the doctor's check honestly rather than gaming it.

**Tests added** (`CustomWidgetsTest`): gift-info renders account details/digital link and skips incomplete accounts, music-player resolves via the configured disk and renders nothing without a file, love-story-timeline renders provided events. Full suite: 73/73 passing.

## Phase 10 — Notifications ✅ done

Email (not WhatsApp — no provider/credentials for that, and it wasn't asked for; easy to add a channel later if wanted). All five notifications `implements ShouldQueue` and route through the `mail` channel only.

- [x] `NewRsvpReceived` → invitation owner, on every new `Guest`.
- [x] `SubscriptionActivated` → customer, on activation *and* renewal (see design note below).
- [x] `SubscriptionPaymentReminder` → customer, on Mayar's `payment.reminder` event — this was previously just logged with a "optional: notify" comment in Phase 4; now actually wired.
- [x] `SubscriptionEnded` → customer, on expiry or cancellation (one class, parameterized by which — the two read almost identically, didn't see a reason for two classes).
- [x] `CustomRequestStatusChanged` → requester, on any status transition.
- [x] Queued via the `database` driver — confirmed for real (not just via `Notification::fake()` in tests): triggered a live notification and checked the `jobs` table directly, one row landed as expected.

**Design choice**: triggered via Eloquent Observers (`GuestObserver`, `SubscriptionObserver`, `CustomRequestObserver`, registered with `#[ObservedBy(...)]` on each model — matches this codebase's existing attribute-based style on `User`) rather than calling `notify()` inline in each controller. Model-level means it fires no matter which path changes the record — a controller today, a Filament admin edit tomorrow — without having to remember to wire notifications into every new caller. Confirmed with a dedicated test that updates a `CustomRequest` directly (bypassing every controller) and still triggers the notification.

**Renewal vs. activation nuance**: a renewal webhook doesn't change `Subscription.status` (it's already `active`) — only `current_period_end` moves forward. `wasChanged('status')` alone would silently miss every renewal receipt. `SubscriptionObserver` checks both: a status transition to `active`, *or* an already-`active` subscription whose `current_period_end` changed. Payment reminders aren't a status change at all (`status` stays `pending`), so that one couldn't go through the observer — it's sent directly from `MayarWebhookController`'s `payment.reminder` handler instead.

**Caught before it shipped**: `SubscriptionEnded`'s first draft claimed "you'll need an active subscription to create new [invitations] or publish changes" — checked `InvitationPolicy::update()` before finalizing the copy and found editing/publishing existing invitations was never entitlement-gated (only `create()` is). Fixed the copy to only claim what's actually true rather than ship user-facing text that misrepresents the product.

**Tests**: added notification assertions to the existing RSVP, Mayar webhook, and custom-request-build tests (the real integration points), plus two new tests — payment.reminder → `SubscriptionPaymentReminder`, and payment.received on an already-active subscription → renewal still notifies. Added one model-level `CustomRequestObserverTest` proving the observer fires from a direct `update()` call, independent of any controller, plus that unrelated field updates don't spuriously notify. Full suite: 77/77 passing.

## Phase 11 — Testing ✅ done

Every phase since Phase 2 shipped with its own tests as it was built, rather than deferring testing to the end — so this phase was an **audit against the checklist's named flows**, not from-scratch authorship. Went through each named item, grepped existing coverage, and only added what was genuinely missing.

**Already covered** (verified, not re-tested): create-from-theme, RSVP submission, custom-request lifecycle (each transition tested at the point it was implemented — assign, build, status-change notification), panel access gating (`BillingPageTest` already proved admin-denied-from-`/user`; since `canAccessPanel()` gates the whole panel rather than per-resource, one representative test covers the mechanism — repeating it per resource would test the same defect twice). Publish/schedule auto-behavior (future-dated publish → reclassified `scheduled`, etc.) is inherited unchanged from Layup's own `Page::booted()` — that's vendor logic, not tested here, per "leave framework behavior to framework tests."

**Real gaps found and closed:**

- [x] **Registration** — genuinely untested. Added `UserRegistrationTest`, which specifically re-proves the Phase 6 bug (`role` null in-memory right after `create()` until re-hydrated) doesn't regress: registers through the real `Filament\Auth\Pages\Register` component, asserts the resulting user's `role` is `UserRole::Customer` in-memory immediately, and that `canAccessPanel()` is already correct without a fresh request.
- [x] **subscribe→checkout→entitlement as one flow** — each piece (checkout, webhook activation, entitlement check) had its own test, but nothing walked all three together. Added `SubscribeCheckoutEntitlementFlowTest`: checkout → Mayar webhook activates → entitlement flips → invitation actually created through the real form → plan limit blocks a second one. Passed on the first run, which is itself useful signal that the integration seams between phases hold together.
- [x] **Policy tests, direct** — `InvitationPolicyTest` and `CustomRequestPolicyTest` (new `tests/Feature/Policies/`). The existing HTTP tests only proved "access denied somehow" — `InvitationResource` has *both* query scoping (`getEloquentQuery()`) and policy checks, so an HTTP 404 doesn't reveal which layer actually did the denying. If the query scope were ever weakened in a refactor, only a direct policy assertion would still catch it. `CustomRequestPolicy` specifically had **zero** prior coverage — no customer-facing route reaches it yet (a customer can only create a request, never view/edit one), so testing it directly means it's already verified and already protected the moment any future UI reaches it.

Full suite: 89/89 passing (up from 77 at the end of Phase 10 — 12 new tests, all closing real gaps, none redundant). Ran via `php artisan test --compact` throughout, per project convention.

## Ad-hoc: Elementor-style 3-panel content builder

The user asked for the invitation/theme content editor (Layup's `LayupBuilder` field) to look and behave like WordPress Elementor: widget picker on the left, canvas in the center, page structure on the right, drag-and-drop.

**Investigated Layup's actual builder first rather than guessing.** Its Alpine component (entirely inline inside `layup-builder.blade.php`'s `@script` block — no separate JS file involved) already had full drag-and-drop plumbing for placing a widget from a picker into a column (`onPickerDragStart`, `drag.fromPicker`, `onDropCol`) — it was just wired to a modal (`modal-widget-picker.blade.php`) instead of a persistent sidebar, and there was no structure/outline panel at all. This meant the 3-panel layout was mostly a restructuring job, not new drag-and-drop logic from scratch.

**Implementation**, entirely in the already-published override `resources/views/vendor/layup/forms/components/layup-builder.blade.php` (never touch `vendor/crumbls/layup/...` directly — it's composer-managed):
- Left panel: the same search/category/recently-used widget list markup as the modal, made permanently visible, reusing the existing `onPickerDragStart` drag source. Click-to-add also works via a new `quickAddWidget()` method (creates a full-width row first if the canvas is empty, else appends to the last column of the last row) — parity with drag for anyone who doesn't want to drag.
- Right panel: new — a live tree of rows → columns → widgets built from the same `content` state, click-to-scroll-to (added `data-row-id` / `data-col-id` / `data-widget-id` attributes to the corresponding canvas elements) with two-way select highlighting.
- Both panels are toggleable from the toolbar (new icon buttons) so the canvas can reclaim full width.
- The existing per-column "+ Add Widget" modal was left intact as a secondary path — not removed, since it still works and costs nothing to keep.
- All new CSS lives in a `<style>` block inside the same override file (Layup registers `layup.css` directly from the vendor path via `FilamentAsset::register`, which isn't publish-overridable, so extending in-place was the only option without forking the CSS asset registration itself).

**Verified with tests, not tinker** (`tests/Feature/LayupBuilderElementorPanelsTest.php`): first pass asserted panel markers were merely *present*, which missed a real ordering bug — the new `<style>` block's CSS selectors (`.lyp-sidebar-right`) matched via `strpos` before the actual right-panel `<div>`, so an ordering assertion using class-name text gave a false pass. Fixed by asserting on `x-show="leftPanelOpen"` / `x-show="rightPanelOpen"` instead (unique to the actual elements), which then correctly caught that ordering was in fact right. Full suite: 106/106 passing.

**Known limitations, not yet built**: dragging directly onto empty canvas space to auto-create a row (currently you place a row via the existing "+ Add Row" template picker, then drag/click widgets into it); reordering rows/columns from the structure panel itself (currently click-to-scroll-and-select only, no drag-in-tree).

## Ad-hoc: WYSIWYG iframe canvas (look like preview, edit like Elementor)

Follow-up to the 3-panel layout above: the user asked for the canvas itself to *look like* the real invitation (not Layup's generic gray placeholder boxes) while staying fully drag-and-drop editable, with "Preview" still showing the real non-editable result.

**Why a gray-box canvas in the first place**: Layup's builder only renders a widget's real HTML in-canvas for ~30 "cheap" widgets that opt in via `supportsLivePreview()`; everything else (Hero, Countdown, Gallery, Map, our custom Gift Info/Love Story Timeline — i.e. everything invitations actually use) falls back to a plain-text summary. And even with real HTML, it wouldn't *look* right: `/admin` and `/i/{slug}` are two independently-compiled Tailwind bundles (confirmed no custom Filament theme CSS is even registered), so loading `invitation.css` straight into the admin page would fire its Tailwind preflight reset across the whole panel — headings, buttons, inputs, everything — a real regression, not a nitpick.

**Architecture**: the canvas is now a same-origin `<iframe>`, matching how Elementor/WordPress's site editor solve the exact same styling-isolation problem. Since it's same-origin (not a third-party embed), no postMessage bridge was needed — the parent Alpine component reaches directly into `iframe.contentDocument` to bind click/drag listeners and calls straight into its own existing methods (`rowEdit`, `widgetAdd`, `onDropCol`, etc.).

- **`app/Layup/Forms/Components/LayupBuilder.php`** (new) — subclasses Layup's field. Drops the `supportsLivePreview()` opt-in gate entirely (every widget now renders its real Blade view), and adds `renderCanvasFrame(array $content): string`, exposed to JS the same way Layup exposes its own methods (`#[Renderless] #[ExposedLivewireMethod]`). Renders the **in-progress, possibly-unsaved** content — not the saved DB row — via a dedicated editor-only Blade view, so it can never affect `layup::components.row`/`column` or the live public page.
- **`resources/views/filament/layup/canvas-frame.blade.php`** (new) — the iframe's document: loads the real `invitation.css` via `@vite`, copies (doesn't share) `row.blade.php`/`column.blade.php`'s width-map and gutter classes for visual fidelity, and wraps every row/column/widget with `data-row-id`/`data-col-id`/`data-widget-id` plus a small hover toolbar (edit/duplicate/delete, add/move/delete column, drag handles) — reusing the same SVG icons as the rest of the builder chrome.
- **`resources/views/vendor/layup/forms/components/layup-builder.blade.php`** — replaced the entire rows/ruler/insert-zone canvas block with the iframe; kept the "Add Row" bottom button as-is. New Alpine wiring: `renderCanvas()` (debounced call to `renderCanvasFrame`, sets `iframe.srcdoc`), `onCanvasFrameLoad()` (rebinds click/drag listeners on every reload, since a new `srcdoc` is a whole new document), and click/drag delegation methods that translate iframe DOM events into calls on the *same* existing methods the light-DOM canvas used to call directly. Hooked the re-render into `pushHistory()` (the one place every content-mutating method in this file already converges) plus `undo()`/`redo()` directly, rather than instrumenting every mutation site individually.
- The structure panel's `scrollToNode()` (added in the 3-panel work) now targets `iframe.contentDocument` instead of the light DOM, since that's the only place row/column/widget elements exist anymore.

**A real bug the registry surfaced, not just styling**: `renderCanvasFrame()` returned nothing for every widget on the first pass — `WidgetRegistry` was empty. Layup's field class relies on `LayupPlugin` having already registered widgets when a Filament *panel* boots; calling the method outside that (as in a unit test, or in principle from anywhere the panel hasn't fully booted) leaves the registry empty. Fixed the same way Layup's own public-facing `AbstractController` does it — `use RegistersWidgets;` and call `$this->ensureWidgetsRegistered()` before every registry lookup (idempotent, so redundant calls are free).

**Known v1 limitations** (flagged, not silently dropped): column drag-to-resize isn't reimplemented (cross-frame mouse-capture during a drag is a materially harder problem than click/dragstart/drop and was cut for scope; column width can still be changed via its settings action); the old per-row hover "insert zone" between existing rows was dropped in favor of the single bottom "Add Row" button; widgets render statically in the canvas (no Alpine/GSAP — countdowns don't tick, sliders don't autoplay) since the iframe intentionally doesn't load `invitation.js`, only its CSS; "Preview" shows the last **saved** state, not live unsaved canvas edits — it already only showed the last-saved state before this change, so not a new gap.

**Tests**: `tests/Feature/LayupCanvasFrameTest.php` (6 tests) — a widget that never opted into Layup's live preview now renders for real, the real stylesheet loads, malformed/unregistered content is skipped instead of crashing (same defensive posture as the public page), empty content renders the empty state, column actions correctly disable move-left/right at the row's edges. Updated `LayupBuilderElementorPanelsTest` to assert the iframe is wired up rather than the now-removed light-DOM row/col/widget markup. Full suite: 112/112 passing.

## Ad-hoc: mobile-only canvas + hero-height fix

Two quick follow-ups to the WYSIWYG canvas above.

**Hero rendering far too tall**: the iframe was auto-resizing itself to its content's `scrollHeight` (via a `ResizeObserver`), but Hero uses `min-height: 70vh` (`hero.blade.php`), which resolves against the iframe's *own* viewport. Growing the iframe to fit Hero → Hero's `70vh` recalculates against the now-taller iframe → grows again — a circular dependency. It converges rather than runs away forever (0.7 < 1), but converges on a Hero roughly 3x too tall, which is exactly what was reported. Fixed by giving `.lyp-canvas-frame` a fixed height (`75vh`, resolved against the real browser viewport since that CSS lives outside the iframe) with the iframe's native scrollbar for overflow, and removing the resize/`ResizeObserver` logic entirely.

**Mobile-only canvas**: since invitation links are almost exclusively opened on a phone, the builder's desktop/tablet/mobile breakpoint toggle was reduced to mobile-only in `config/layup.php` — `breakpoints` now has just the `sm` entry (width changed from Tailwind's 640px `sm:` threshold to 390px, a real phone's CSS viewport, so `sm:`/`md:`/`lg:`-prefixed utility classes correctly stay inactive in the canvas exactly as they would on a real phone) and `default_breakpoint` is `sm`. No Blade/JS changes needed — the toolbar's breakpoint buttons are already generated from this config.

**Tests**: `LayupBuilderElementorPanelsTest` — one asserting the fixed-height CSS rule is present, one asserting the config (source of truth for the toolbar) only exposes the mobile breakpoint at 390px. Full suite: 114/114 passing.

## Ad-hoc: pin the public invitation page to phone width too

Follow-up to the mobile-only editor canvas: the same rationale ("guests almost exclusively open this on a phone") applies to the actual public page, not just the admin/staff editor. Confirmed the editor canvas was already correctly locked regardless of the admin's own screen size (`.lyp-canvas-inner`'s `max-width` is a fixed 390px pulled from `config('layup.breakpoints.sm.width')`, not viewport-relative — no code changes needed there).

For the public side, `resources/views/components/layouts/invitation.blade.php` and `theme-preview.blade.php` now wrap `{{ $slot }}` in `mx-auto w-full max-w-[430px] bg-white shadow-xl` on a `bg-gray-100` body — so on a desktop or tablet browser the invitation renders as a centered phone-width card instead of a responsive layout stretching to fill the window. Moved the "not published yet" / "Dear {guest}" / "Theme preview" banners inside the same wrapper so they're visually part of the card rather than full-bleed across the backdrop.

**Known trade-off, not fixed**: `max-width` on the wrapper doesn't stop `md:`/`lg:`-prefixed Tailwind classes inside individual widgets from activating — those are real CSS media queries keyed to the *actual* browser viewport, not to a parent element's width, so a genuinely wide desktop browser will still trigger e.g. `md:text-5xl` inside a squeezed 430px column (text renders a bit larger than a native mobile page would, though nothing overflows or breaks — Tailwind's own flex-wrap/w-full patterns keep content fluid). The fully-correct fix would be reworking every widget view to use CSS container queries instead of viewport-based breakpoints — a much larger undertaking (90+ view files) that wasn't warranted for what's fundamentally a visual-consistency request; flagging it rather than silently leaving it undiscovered.

**Tests**: added a `max-w-[430px]` assertion to `InvitationPageControllerTest` and `ThemePreviewControllerTest`. Full suite: 115/115 passing.

## Ad-hoc: right-click context menu (cut/copy/duplicate/paste/delete)

Elementor-style right-click menu on rows, columns, and widgets — working from both the canvas iframe and the structure panel, per the user's explicit "including structure" ask.

**Clipboard is in-memory only** (this editing session's Alpine state, not the system clipboard, not persisted) — deliberately scoped down from a "real" OS clipboard, since cross-tab/cross-session paste wasn't asked for and would add real complexity (permissions prompts, serialization format) for no clear benefit here.

**Same-origin coordinate translation, no postMessage**: `openContextMenu()` positions the menu (`position: fixed`, light DOM) using `event.clientX/clientY`, offset by the iframe's `getBoundingClientRect()` when the event originated inside it — detected via `event.view !== window` (an event dispatched inside the iframe carries the iframe's own `contentWindow` as `view`, a reliable same-origin trick that needed no new plumbing).

**No new backend method** — clipboard copy/cut/paste/duplicate-column are all pure client-side `content` mutations (clone with a fresh `Math.random()`-based id, splice into the target array, `pushHistory()` — same "optimistic local update, no server round-trip" pattern Layup's own `columnMove` already used, confirming Livewire's `$wire.$entangle` syncs plain nested mutations back to the server without an explicit method call). Duplicate/Delete on widgets and rows reuse Layup's own existing `widgetDuplicate`/`widgetDelete`/`rowDuplicate`/`rowDelete` methods rather than reimplementing them; column duplicate didn't have an existing method, so it's implemented the same client-side-clone way as paste.

**Paste target rules** (`canPasteHere()`): a copied widget can paste onto a widget (inserts after it) or a column (appends); a copied column can paste onto a column (inserts after) or a row (appends); a copied row can paste onto a row (inserts after) or empty canvas (appends at the end). Right-clicking empty canvas space only offers Paste (nothing to cut/copy/duplicate/delete there).

**Tests**: `LayupBuilderElementorPanelsTest` — menu markup and all five actions' click handlers render, the structure panel's row/column/widget nodes are wired with `@contextmenu.prevent`. (The iframe's own delegated `contextmenu` listener lives inside the Livewire `@script` block, which the raw HTTP response HTML-escapes — not asserted against directly, same reason `scrollHeight`/`ResizeObserver` weren't earlier.) Full suite: 116/116 passing.

## Ad-hoc: fixed "right-click delete row doesn't delete"

Root cause, found by auditing every content-mutating path against the WYSIWYG canvas's re-render trigger: deleting a row goes through Layup's confirmation-modal flow (`rowDelete()` → `$wire.mountAction('rowDelete', ...)` → confirm → server removes it from `content.rows` and dispatches a `layup-row-deleted` browser event → the listener calls `rowDeleted(id)` to filter it out of the client's `content` too). Every sibling `layup-*-deleted`/`layup-*-updated` listener (column-deleted, widget-deleted, widget-updated, column-updated, row-updated) already calls `pushHistory()` at the end — which, since the WYSIWYG canvas work, also re-renders the iframe. `rowDeleted()` was the one exception that didn't. The row genuinely *was* removed from `content.rows` (would have saved correctly), but the canvas iframe was never told to redraw, so the row stayed visibly stuck until some unrelated action happened to trigger a re-render — indistinguishable from "delete doesn't work" from the user's seat. Added the missing `pushHistory()` call to `rowDeleted()`, matching every sibling handler. This is a Filament confirmation-modal flow either way — right-click Delete and the row toolbar's delete button both open the same "Are you sure?" dialog, then correctly remove it.

**Test**: `LayupBuilderElementorPanelsTest::test_row_deletion_triggers_a_canvas_re_render` asserts `rowDeleted()`'s body calls `pushHistory()`. Full suite: 117/117 passing.

## Ad-hoc: padding/margin had zero effect, and structure panel couldn't drag-drop

Two separate reports in one message.

**"I set Hero padding to 0px but preview still shows padding"**: found by reading, not guessing. Every widget's shared "Design" tab (`BaseView::getDesignFormSchema()`) exposes per-side padding/margin pickers (`SpacingPicker::advanced()`) — but `BaseView::buildInlineStyles()`, the vendor helper all ~90 widget views call to turn that tab's data into CSS, never reads `padding`/`margin` at all. Setting either field saves correctly but has always had zero visual effect, on any widget, regardless of value — not Hero-specific, and not something introduced by earlier work this session. Can't subclass a static method called by fully-qualified name from 90+ Blade files, and won't patch vendor source directly (composer-managed, wiped on update), so added `App\Layup\Support\StyleHelper::buildInlineStyles()` — wraps the vendor helper and adds the missing padding/margin CSS — and did a project-wide find/replace across all 92 published widget views (`\Crumbls\Layup\View\BaseView::buildInlineStyles($data)` → `\App\Layup\Support\StyleHelper::buildInlineStyles($data)`), the same technique already used for the disk-URL and http-passthrough-guard fixes earlier in this project. Deliberately checks `=== null || === ''` rather than `empty()`/`!empty()` on each side's value — `empty(0)` is `true` in PHP, which would have silently dropped exactly the "set to 0" case the user hit.

**"Make sure I can drag-drop into canvas and structure"**: canvas drag-drop already worked; the structure panel was click-to-scroll only, no drag support at all. Added it by pointing a second set of native HTML5 drag attributes at the exact same Alpine state and methods the canvas iframe already drives (`rowDrag`/`onRowDragStart`/`onRowDragOver`/`onRowDrop` for rows; `drag`/`onDragStart`/`onDragOverWidget`/`onDragOverCol`/`onDropCol` for widgets and for dropping a palette widget straight into a column) — not a parallel implementation. Being light DOM (no iframe boundary to cross), it needed no coordinate translation, unlike the canvas/context-menu work.

**Tests**: `tests/Unit/StyleHelperTest.php` (5 tests, including the explicit "all sides set to 0" case) plus an end-to-end `InvitationPageControllerTest` test rendering a real Hero widget with zero padding through `/i/{slug}`. `LayupBuilderElementorPanelsTest::test_the_structure_panel_supports_drag_and_drop` asserts the drag attributes are wired. Full suite: 124/124 passing.

## Ad-hoc: canvas drag-drop actually broken, and Save moved into the header

**"In canvas I can't drag and drop"** — a real, root-cause-verified bug, not a misunderstanding. Confirmed via research (see sources in-session) rather than assumption: native HTML5 `dragover`/`drop` do **not** reliably cross an iframe boundary, even same-origin, in either direction. My earlier "same-origin means no postMessage bridge needed" reasoning was correct for `contentDocument` access, click, and context-menu delegation — but wrong for native drag events specifically, which was an unverified assumption at the time I built the iframe canvas. The iframe's own `dragover`/`drop` listeners only ever fire for a drag that started inside that same document — so dragging a widget from the left palette (light DOM) into the canvas, or from the structure panel into the canvas, silently registered nothing.

Fixed with the documented workaround: a transparent overlay (`.lyp-canvas-drag-overlay`) sits on top of the iframe, but *only* while a drag that didn't start inside the canvas is in flight (tracked via a new `sourceIsFrame` flag on `drag`/`rowDrag`, set from `event.view !== window` at drag-start — the same trick already used for the context menu's coordinate translation). Being light DOM itself, the overlay reliably receives `dragover`/`drop`; it then subtracts the iframe's own `getBoundingClientRect()` offset from the pointer position and calls the iframe document's own `elementFromPoint()` to find the real target inside it, reusing the exact same row/column/widget resolution logic already written for the iframe's native listeners. Reordering something already inside the canvas is untouched — it never had this problem, since both drag source and drop target share one document there, and the overlay only appears when they don't.

**Known residual limitation**: the reverse direction (dragging an existing canvas widget *out* to the structure panel) has the same underlying cross-frame issue and isn't fixed — would need a second, oppositely-oriented overlay. Not addressed since it wasn't the reported case; flagging rather than leaving it a silent surprise.

**Save button relocated**: per request, `getSaveFormAction()` now sits in the header actions (between Preview and Delete) on both admin and user Edit Invitation pages, and `getFormActions()` returns `[]` to remove Filament's default sticky bottom bar — no duplicate Save button.

**Tests**: `LayupBuilderElementorPanelsTest::test_the_canvas_has_a_light_dom_drag_overlay_for_drags_starting_outside_it` asserts the overlay and its handlers are wired, and that `sourceIsFrame` gates it. Full suite: 125/125 passing.

## Ad-hoc: "Save changes" genuinely did nothing — found and fixed

Root cause: moving Save into the header actions (previous session entry) reused `getSaveFormAction()`, which renders a native `type="submit"` button wired to the page's `<form wire:submit="save">`. That only works while the button is physically inside that `<form>` element — header actions render in the page header, outside it, so the button was inert. Fixed by building the header Save action with `->action('save')` instead — calls the same underlying `save()` Livewire method directly, the same mechanism every other header action (Delete, Restore, Preview) already uses regardless of where it renders. Also gave Preview and Save distinct colors (`gray` / `primary`) per request, since both defaulted to the same color and were visually indistinguishable.

**Writing the regression test surfaced a second, more interesting finding**: `->callAction('save')` in the test failed even against the fix, while `->call('save')` (calling the Livewire method directly) passed. Traced it by reading `Action::getLivewireClickHandler()`: because this action has no `->submit()`/`->requiresConfirmation()`/URL, Filament renders it as a bare `wire:click="save"` — a direct Livewire call that bypasses Filament's mounted-action pipeline entirely, which `->callAction()` in tests specifically simulates. Confirmed empirically (not just by reading the source) that the *rendered page* really does contain `wire:click="save"`, proving the fix is correct for an actual click and the `->callAction()` failure was a testing-methodology mismatch, not a second bug. Both admin and user `InvitationResourceTest` now assert this directly.

**Tests**: `test_the_header_save_action_actually_persists_changes` (calls `save()` the same way a real click does, asserts the record updates) and `test_the_save_button_renders_as_a_direct_livewire_click_handler` (asserts the actual `wire:click="save"` markup), in both admin and user suites. Full suite: 129/129 passing.

## Ad-hoc: Media Library (WordPress-style), per-user scoped

Installed `awcodes/filament-curator` (v5.3.5, compatible with this project's Filament 5.8 — confirmed via a `composer require --dry-run` before actually installing) rather than building a media library from scratch, per explicit user decision on the size/dependency trade-off.

**Per-user scoping + admin All/Self toggle** (the actual ask, not Curator's own tenancy feature): Curator ships a "tenancy" mode, but it requires Filament's *panel-level* tenancy (`Filament::hasTenancy()`), which this app deliberately doesn't use (flat `/user` path, manual query scoping elsewhere — same reasoning as InvitationResource/ThemeResource). Disabled Curator's tenancy in config and wrote `App\Filament\Resources\Media\MediaResource extends Awcodes\Curator\Resources\Media\MediaResource`: `getEloquentQuery()` hard-scopes customers to their own `user_id`, leaves admins unrestricted by default, and `table()` adds a `Radio` filter (`All users' media` / `Just my own`) — visible only to admins, since it would do nothing for customers anyway. `App\Observers\MediaOwnerObserver` stamps `user_id` from the current session on upload.

**Two real bugs found and fixed along the way, not just the intended scaffolding:**
- `curator:install`'s generated `config/curator.php` had `'relationship_name' => user` — unquoted, so PHP parses it as an undefined constant reference, which throws the instant the config file loads. Quoted it (moot anyway once tenancy was disabled in favor of manual scoping).
- The base `Awcodes\Curator\Models\Media`'s `$fillable` has no concept of ownership, so `user_id` passed to `Media::create([...])` was being silently dropped by mass-assignment protection — caught via a filter test that should have shown 1 record after narrowing to "self" and showed 0 instead. Redeclared `$fillable` on `App\Models\Media` including `user_id`.

**Also found while writing tests, not a bug**: `assertCanSeeTableRecords()`/`assertSee()` don't reliably reflect a filter-triggered re-render against Curator's *default grid layout* specifically (it renders rows via its own `View::make()` column rather than Filament's standard row template) — even though the real page renders correctly (confirmed both via the plain-HTTP test and a temporary debug log proving the query itself was always correct). Filter tests use `assertCountTableRecords()` instead, which reads the query directly rather than parsing rendered HTML.

**Browse-library-or-upload picker wired into the two upload fields this project owns directly** (Theme's `preview_image`, `MusicPlayerWidget`'s `audio_file`) via `CuratorPicker`, which is Curator's own field replacing `FileUpload` — this is what actually delivers "drag-and-drop or browse, with the option to reuse an existing library item" the way WordPress's media modal does. Note the field's state model: `CuratorPicker` stores the *picked Media record's id*, not a storage path, which is a real difference from `FileUpload` — required updating `ThemesTable`'s image column and `music-player.blade.php`'s URL resolution to resolve that id back to a URL, and updating `CustomWidgetsTest`/adding a `ThemeResourceTest` case to match.

**What's NOT wired up — flagged, not silently incomplete**: every other file-upload field an invitation actually uses (Hero background image, Gallery images, Person photos, and the rest across ~90 widget classes) is defined by `FileUpload::make(...)` inside *vendor* PHP (`vendor/crumbls/layup/src/View/*.php`), which can't be edited directly (composer-managed) and can't be globally reconfigured into a `CuratorPicker` via a hook — Filament's `configureUsing()` only lets you call further methods on an *existing* component instance, it can't swap the component's class. The only path to media-library-enabling those fields would be forking each affected widget class into an `App\Layup\Widgets\*` override registered under the same `getType()` key (Layup's `WidgetRegistry` allows same-key overriding) — a materially larger, separate undertaking not started here.

**Tests**: `MediaResourceTest` (admin: 5 tests — sees everyone's by default, the radio filter narrows correctly, upload stamps the current user, customers get a 403; user: 3 tests — hard self-scoping, can't open another customer's record, never sees the admin filter). Plus the `CustomWidgetsTest`/`ThemeResourceTest` updates above. Full suite: 138/138 passing.

## Ad-hoc: widget uploads weren't showing up in the Media Library

The gap flagged as a known limitation in the previous entry ("every other file-upload field... can't be globally reconfigured into a CuratorPicker") turned out to have a much smaller fix than forking vendor widget classes: the actual complaint was narrower than "give every widget a full library-or-browse picker" — it was "a file I uploaded through a widget doesn't show up in the library at all," which doesn't require changing the widget's UI or its stored value, just making the upload *also* register a Media row.

Filament's `FileUpload` (`BaseFileUpload`) has a `saveUploadedFileUsing(Closure)` hook — the default closure just stores the file and returns its path. `FileUpload::configureUsing()` (Filament's global per-component-class customization hook, distinct from `CuratorPicker` which is an unrelated class) lets that default be replaced for *every* `FileUpload::make(...)` app-wide, including the ~90 vendor Layup widget fields defined in PHP this project can't edit — registered in `AppServiceProvider::boot()`.

`App\Layup\Support\MediaCataloger::saveAndCatalog()` is the new default: captures the upload's size/mime/original name *before* calling through to the real `saveUploadedFile()` (which can move the temp file, after which re-reading those isn't reliable), stores it exactly as before, then creates a matching `Media` row — same disk, same path, same directory. The widget's own stored value never changes (still a plain path string), so nothing about how any widget renders its data was touched. `MediaOwnerObserver` (already in place from the Media Library work) stamps the uploading user automatically, same as any other upload.

**Testing this properly meant proving two different things, not one**: that `saveAndCatalog()` itself is correct (disk/path/dimensions/owner — verified by constructing a real `TemporaryUploadedFile` the same way Livewire's own upload endpoint does, via `FileUploadConfiguration::storeTemporaryFile()` — worth noting its return value needs the temp-directory prefix stripped again before `TemporaryUploadedFile`'s constructor, which re-applies it, or the path doubles), and separately that `AppServiceProvider`'s `configureUsing()` registration actually *reaches* a plain `FileUpload::make(...)` the way a vendor widget declares one — traced through `ComponentManager::configure()`'s actual source to confirm parent-class `setUp()` (which sets the vendor default) runs before the registered `configureUsing()` callback (which then overrides it), then confirmed it empirically via reflection on a configured field's `saveUploadedFileUsing` closure, rather than trusting the source-reading alone.

**Tests**: `tests/Unit/MediaCatalogerTest.php` (4 tests) — file gets stored and cataloged with correct metadata, current user is stamped as owner, non-image uploads are cataloged without dimensions, and the global wiring itself is verified via reflection on a plain configured `FileUpload` field. Full suite: 142/142 passing.

## Ad-hoc: Media Library thumbnails and previews were broken — wrong storage source

Reported as "no thumbnail in the grid" plus an explicit error on preview: `League\Glide\Filesystem\FileNotFoundException: Could not find the image 'public/layup/heroes/....jpeg'`.

Root cause: Curator's Glide server (used for both grid thumbnails and the "view" action — anything that isn't a byte-for-byte passthrough) has its own default source filesystem, `storage_path('app')` with a `'public'` path prefix — i.e. it always reads originals from *local* disk, with no awareness that this app's media actually lives on the `'r2'` disk (S3-compatible remote, `config('curator.default_disk')`). Every media record's real file is fine — Glide just never had a way to reach it, hence the literal `public/{path}` in the error (its hardcoded local prefix, glued onto the real path).

**First attempt was wrong in an instructive way**: called `app(GlideManager::class)->serverConfig([...])` once in `AppServiceProvider::boot()`, resolving `Storage::disk('r2')->getDriver()` eagerly at that point. The path-prefix bug was gone (confirmed by the error message itself changing from `public/layup/heroes/...` to the correct `layup/heroes/...`), but the file still "wasn't found" — because in tests, `Storage::fake()` runs *after* the app has already booted, so the disk driver captured at boot time was already stale. That's not just a test artifact to work around — it's the same class of bug (eager capture of something that can legitimately change) that would bite any real per-request disk reconfiguration, so it warranted fixing properly rather than papering over in the test. Replaced the whole approach: `App\Layup\Support\CuratorGlideManager extends Awcodes\Curator\Config\GlideManager`, overriding `getServer()` to resolve the disk's Flysystem adapter fresh on *every* call rather than caching it once — bound in place of the vendor class via `AppServiceProvider::register()` (registered there specifically because auto-discovered package providers register before app providers, so this reliably overrides Curator's own binding rather than racing it).

**Test**: `CuratorGlideSourceTest` — creates a real image on the actually-configured disk, requests its Glide-generated thumbnail URL through the real route, and asserts a 200 with an image content-type — an end-to-end proof, not a mock of the fix. Full suite: 143/143 passing.

## Ad-hoc: every sidebar entry used the same icon

Literal cause: every resource in both panels was left at `make:filament-resource`'s scaffold default, `Heroicon::OutlinedRectangleStack` — never customized, so the entire sidebar (Invitations, Themes, Custom Requests, Plans, Subscriptions, Users in admin; Invitations in user) showed the identical icon. The two custom user-panel pages (Billing, Rsvps) had no icon set at all, defaulting to Filament's own generic page icon — also indistinguishable from each other.

Gave each a distinct, semantically-fitting icon: Invitations → envelope, Themes → swatch, Custom Requests → chat bubble, Plans → tag, Subscriptions/Billing → credit card, Users → people, Rsvps → clipboard-check. The Media Library resource already had its own (photo icon, from Curator's own config) and didn't need touching.

**Test**: `NavigationIconsTest` — asserts no two sidebar entries within the same panel share an icon. Written so a newly scaffolded resource that's left at the generator default (the exact original bug) fails loudly instead of silently blending in. Full suite: 145/145 passing.

## Ad-hoc: "Copy Link" button in the Media Library

`App\Filament\Resources\Media\MediaResource::table()` appends a `copyLinkAction()` to the existing Edit/Delete row actions via `$table->pushRecordActions([...])` — not `->recordActions([...])`, which would have replaced Curator's own `MediaTable::configure()` array instead of adding to it. Works in both the grid and list layouts, since both render off the same `recordActions`.

Copying to the clipboard is a browser API, not something a server round trip can do, so the action has no server-side `->action()` — it uses `->alpineClickHandler()` (the same mechanism Filament's own `TextInput\Actions\CopyAction` uses for its field-level copy buttons) with the record's real resolved URL embedded directly into the generated JS via `Illuminate\Support\Js::from()`, since it's already known server-side rather than needing to be read back out of the DOM at click time.

**Test note**: `Js::from()` escapes forward slashes (`json_encode`'s default), so an `assertSee($media->url)` on the plain URL string doesn't find it in the rendered page — the test needle has to be `Js::from($media->url)->toHtml()`, matching what's actually embedded, not the human-readable URL.

**Tests**: added to both `MediaResourceTest` suites — button renders with the correct label, and the specific record's real URL (not a placeholder) is embedded in its click handler. Full suite: 147/147 passing.

## Phase 12 — Pre-release hardening

- [ ] Confirm MySQL is fully configured for production (connection, backups — see below) rather than the Laravel skeleton's SQLite default.
- [ ] `/security-review` pass — pay particular attention to the Mayar webhook endpoint (token verification, replay/idempotency), mass-assignment on all new models, and `allow_raw_html` in `config/layup.php` (default `true` lets HTML/Embed/Map widgets render unescaped — confirm that's acceptable for staff-authored themes and any customer-facing HTML widget access).
- [ ] Confirm `layup:safelist` / Tailwind build is part of the deploy pipeline (dynamic classes from saved content need the safelist regenerated and rebuilt).
- [ ] Backups for the database (invitations, RSVPs, subscriptions are all business-critical).
- [ ] Error monitoring / log review wired up.
- [ ] Load-check the public invitation route (`/i/{slug}`) — this is the highest-traffic, most spiky path (guests all hit one link around the event).

## Phase 13 — Release

- [ ] Deploy (Laravel Cloud, per project convention) — production `.env`, payment keys, mail, queue worker.
- [ ] DNS/domain cutover.
- [ ] Seed production `Plan` and initial `Theme` catalog.
- [ ] Smoke test all three surfaces (`/`, `/user`, `/admin`) in production before announcing.
- [ ] Launch.

---

## Suggested execution order

Phases 1–3 are prerequisite plumbing (do these regardless). Phase 4 (billing) is the biggest external dependency and should be confirmed/started early since it gates Phase 5's pricing section and Phase 6's entitlement checks. Phases 5–9 can mostly proceed in parallel once 1–4 are settled. 10–13 are back-loaded on purpose.
