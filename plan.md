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

## Still open (flagged, not blocking — pick when you review)

- **Invitation limit per plan.** Assuming plans gate by *number of invitations* + *feature flags* (custom domain, RSVP limit, remove branding, guest-name personalization). Confirm the actual tiers before Phase 4.
- **Domain model for public invitation URLs.** Assuming `yourapp.com/i/{slug}` to start; custom domains per invitation (premium feature) is called out as a stretch item, not in the base plan.

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

## Phase 2 — Domain model

- [ ] `Invitation` model, subclassing `Crumbls\Layup\Models\Page` (per Layup's "swapping the page model" pattern) — inherits revisions, scheduled publishing, SEO/JSON-LD, slug handling for free.
  - Migration adds: `user_id` (owner), `event_date`, `host_name`, `venue`, `theme_id` (nullable, which starter theme it was created from), `is_custom_build` (bool — created via custom-request path).
  - `config/layup.php` → `pages.model = App\Models\Invitation::class`, `pages.table = invitations`.
- [ ] `Theme` model — starter templates for the picker. Fields: `name`, `description`, `preview_image`, `content` (JSON — a pre-built Layup layout), `category` (wedding/birthday/corporate/etc.), `is_active`.
  - Seeder with a handful of starter themes (blank + 3–5 designed ones) built using Layup widgets (Hero, Countdown, Gallery, Map, Timeline, Testimonial).
- [ ] `Plan` model — `name`, `price`, `billing_interval`, `invitation_limit`, `features` (JSON: custom_domain, remove_branding, rsvp_limit, guest_personalization, priority_support).
- [ ] `Subscription` model — `user_id`, `plan_id`, `status`, `current_period_end` (or Cashier's own subscription table if Cashier is adopted — see Phase 4).
- [ ] `CustomRequest` model — `user_id`, `event_type`, `event_date`, `style_notes`, `budget`, `status` (`new`, `in_review`, `in_progress`, `delivered`, `cancelled`), `invitation_id` (nullable, set once staff creates the working `Invitation` for them), `assigned_admin_id`.
- [ ] `Guest` / `Rsvp` model — `invitation_id`, `name`, `attending` (bool/enum incl. "maybe"), `party_size`, `message`, `responded_at`.
- [ ] Factories + seeders for all of the above (Boost/Laravel convention).
- [ ] Policies: `InvitationPolicy` (owner-or-admin), `CustomRequestPolicy` (owner-or-staff).

## Phase 3 — Auth & panel access

- [ ] Registration flow for customers (`/register` or via the pricing CTA) — creates `User` with `role = customer`.
- [ ] `AdminPanelProvider`: `canAccessPanel()` restricted to `role === 'admin'`.
- [ ] New `UserPanelProvider` (`id: 'user'`, `path: 'user'`): `canAccessPanel()` restricted to `role === 'customer'`; login page at `/user/login`.
- [ ] Every resource/query in the `user` panel scoped to `auth()->id()` (global scope on `Invitation`/`CustomRequest` when accessed outside admin context, or `modifyQueryUsing` per resource — pick one convention and apply consistently).
- [ ] Redirect logic: logged-in customer hitting `/` sees marketing page with an authenticated CTA ("Go to dashboard") instead of "Sign up".

## Phase 4 — Billing & subscriptions (Mayar.id)

No Cashier-equivalent exists for Mayar, so this is a small hand-rolled integration rather than a package install. Core principle from Mayar's own docs: **the browser redirect is UX, the webhook is truth** — never mark a subscription active on redirect alone, only on a confirmed webhook.

- [ ] Env vars: `MAYAR_API_KEY` (bearer token), `MAYAR_API_BASE` (sandbox `api.mayar.club/hl/v2`, production `api.mayar.id/hl/v2`), `MAYAR_WEBHOOK_TOKEN` (verification token), plus a `MAYAR_TIER_ID`/`MAYAR_PRODUCT_ID` per `Plan` (set on the `Plan` model, one Mayar product/tier per local plan).
- [ ] `App\Services\Mayar\MayarClient` — thin HTTP client (`Http::withToken(...)->baseUrl(...)`) wrapping: `POST /memberships/members/create`, `POST /memberships/members/{memberId}/invoice/create`.
- [ ] `Subscription` model stores `mayar_member_id`, `mayar_invoice_id`, `status` (`pending`, `active`, `expired`, `cancelled`), `current_period_end`.
- [ ] Checkout flow: user picks a `Plan` → create `Subscription` row as `pending` → call `MayarClient` to create the member + invoice → store the returned Mayar IDs on the pending `Subscription` → redirect the user to the returned `membershipBillUrl` (Mayar's hosted checkout page).
- [ ] Webhook endpoint (`POST /webhooks/mayar`, CSRF-exempt): verify the shared token (query param, per Mayar's documented pattern — **re-check Mayar's dashboard/support for the current verification mechanism before going live, their public docs don't fully spell out signature verification**), log the raw payload, and store the event id for idempotency (skip if already processed) before acting.
  - `payment.received` (status `SUCCESS`) → find `Subscription` by `mayar_member_id`/`mayar_invoice_id`, set `active`, set `current_period_end` from `membershipCustomer.expiredAt`.
  - `membership.memberExpired`, `membership.memberUnsubscribed` → set `Subscription.status` accordingly, revoke access immediately.
  - `membership.changeTierMemberRegistered` → swap the linked `Plan`.
  - `payment.reminder` → optional: notify the user their payment is incomplete.
  - Always return HTTP 200 once the payload is safely accepted, even if processing is deferred to a queued job.
- [ ] Entitlement checks read `Subscription.status === 'active'`: block creating a new `Invitation` past `plan.invitation_limit`; gate premium features (custom domain, branding removal) behind `plan.features`.
- [ ] Billing status page inside `/user` (current plan, renewal date, "manage on Mayar" link) — Mayar hosts its own billing portal, so this is a read-only summary plus a link out, not a custom portal build.
- [ ] `Plan` seeding matches real pricing tiers and their Mayar product/tier IDs (confirm numbers + Mayar-side product setup before building checkout UI).

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
