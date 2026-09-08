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

## Phase 5 — Landing page (`/`)

- [ ] Hero section — what the product is, primary CTA.
- [ ] Theme gallery preview — pulls active `Theme` records, links into "start with this theme" (goes to signup/checkout if logged out, straight to `/user` invitation-create if already subscribed).
- [ ] Pricing section — plan cards from `Plan` model, "Subscribe" → checkout (Phase 4).
- [ ] "Prefer we build it for you?" section — CTA into the custom-request form (public form, or gated behind a quick signup so we have a `user_id` to attach the request to).
- [ ] Public custom-request form → creates `CustomRequest` (`status = new`), optionally creates the `User` account inline if they weren't signed up yet.
- [ ] Basic SEO/OG for the landing page itself.

## Phase 6 — `/user` panel (self-service editing)

- [ ] "Create invitation" flow: pick a `Theme` (clones its `content` JSON into a new `Invitation`) or start blank; respects `plan.invitation_limit`.
- [ ] `InvitationResource` (Filament): title/slug/event details form + `LayupBuilder::make('content')` for the visual editor.
- [ ] Publish / schedule controls (`status`, `published_at`) — reuses Layup's built-in scheduled-publishing behavior inherited from `Page`.
- [ ] RSVP dashboard widget/page — list of `Guest` responses for the customer's invitation(s), attending count, export (CSV).
- [ ] Revision history UI (inherited from Layup) — restore a previous version of the design.
- [ ] Account/billing page (links into Phase 4's billing portal).

## Phase 7 — `/admin` panel

- [ ] `InvitationResource` (admin scope, unfiltered) — see/edit/delete any customer's invitation, reassign owner if needed.
- [ ] `UserResource` — manage customers, roles, subscription status at a glance.
- [ ] `ThemeResource` — CRUD the starter-theme catalog (build a theme using the same `LayupBuilder` field, mark active/inactive, set preview image + category).
- [ ] `CustomRequestResource` — queue view (`new` → `in_review` → `in_progress` → `delivered`), assign to staff member, and a "build this" action that creates/opens the linked `Invitation` (using the admin's own Layup builder access) so staff design it directly against the customer's account.
- [ ] `PlanResource` / `SubscriptionResource` — pricing management, view active subscriptions, manually override/comp a subscription if needed.
- [ ] Dashboard widgets: MRR-ish subscription count, custom-request backlog, invitations published this week.

## Phase 8 — Public invitation frontend

- [ ] Custom controller (extends Layup's `AbstractController`) serving `/i/{slug}`, published-only, 404 on draft/unpublished.
- [ ] Invitation-specific Blade layout (distinct from admin/marketing layout) — full-bleed, mobile-first (most guests open on phone).
- [ ] Guest-name personalization via query string (`/i/{slug}?to=Jane`) — common expectation for this product category; small custom Blade/Alpine addition, not a Layup feature.
- [ ] RSVP submission: custom widget (`php artisan layup:make-widget RsvpForm`) posting to a route that creates a `Guest` record against the invitation — plain form post is enough; a small Livewire component is a fine upgrade if reload-free submission is wanted, no new dependency either way.
- [ ] SEO/OG per invitation (inherited from `Page`/Layup) so shared links (WhatsApp/IG) render a proper preview card.
- [ ] `@layupScripts` + Alpine wired into the invitation layout for interactive widgets (Countdown, Gallery lightbox, Accordion, etc.); GSAP layered in for the invitation's hero/reveal animation.
- [ ] Confirm images/galleries uploaded through the builder resolve to R2 URLs correctly on the public page (disk already set to `r2` in Phase 1).

## Phase 9 — Domain-specific widgets (custom Layup widgets)

- [ ] `RsvpForm` widget (Phase 8).
- [ ] Optional: gift/bank-transfer info widget, background-music player widget, love-story timeline preset — only if these are actually wanted; don't build ahead of a confirmed feature list.
- [ ] `php artisan layup:doctor` and `layup:list-widgets` run clean after additions.

## Phase 10 — Notifications

- [ ] Email (or WhatsApp, if that's part of the product) on: new RSVP received (to owner), subscription receipt/renewal/failure, custom-request status change.
- [ ] Queued via the existing `database` queue driver (already the app default).

## Phase 11 — Testing

- [ ] Feature tests per resource/flow: registration, subscribe→checkout→entitlement, create-from-theme, publish/schedule, RSVP submission, custom-request lifecycle, panel access gating (`admin` can't leak into `/user` data scoping and vice versa).
- [ ] Policy tests: a customer cannot view/edit another customer's `Invitation` or `CustomRequest`.
- [ ] Run via `php artisan test --compact`, per project convention.

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
