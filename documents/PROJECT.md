# SiteSecureCheck — What we’re building

**Goal:** An agent-powered system that **scans a WordPress site for security & compliance gaps** (TLS, headers, cookies, CSP, trackers) and then **safely fixes them** (headers, cookie banner, policy pages, CSP roll-out) with  **one click** —plus alerts to Microsoft Teams/email and full rollback.

Why this matters: Most WP sites are on managed hosts where you  **can’t edit server config** ; a plugin lets us apply best-practice protections from the **application layer** and do it  **safely and reversibly** .

---

## High-level architecture

<pre class="overflow-visible!" data-start="747" data-end="1856"><div class="contain-inline-size rounded-2xl relative bg-token-sidebar-surface-primary"><div class="sticky top-9"><div class="absolute end-0 bottom-0 flex h-9 items-center pe-2"><div class="bg-token-bg-elevated-secondary text-token-text-secondary flex items-center gap-4 rounded-sm px-2 font-sans text-xs"></div></div></div><div class="overflow-y-auto p-4" dir="ltr"><code class="whitespace-pre!"><span><span>+</span><span>-------------------+         scan request          +--------------------+</span><span>
| Dashboard (Laravel|  </span><span>---------------------------> | Agent (AWS)        |</span><span>
| + Inertia/Vue)    |                               | API GW + Lambdas   |
| - Sites & secrets |  <</span><span>--- results (webhook) ----- | + Bedrock reasoning|</span><span>
| - </span><span>Trigger</span><span> Scans   |                               | + Scanner (headless)
| - Approve Fixes   |                               | + Notifiers        |
+</span><span>---------+---------+                               +----------+---------+</span><span>
          |                                                       |
          | apply fixes (</span><span>only</span><span></span><span>if</span><span> allowed)                         |
          v                                                       |
+</span><span>-------------------+                                    +--------v--------+</span><span>
| WordPress Plugin  | <</span><span>---------------------------------- | Teams / Email  |</span><span>
| - Headers, CSP     (Agent → secure REST </span><span>with</span><span> key)       | Notifications  |
| - Cookie banner   |                                    +</span><span>----------------+</span><span>
| - </span><span>Policy</span><span> pages    |
| - </span><span>Rollback</span><span>        |
+</span><span>-------------------+</span><span>
</span></span></code></div></div></pre>

**Components**

* **Dashboard (Laravel + Inertia/Vue):** Control panel to register sites, store per-site secrets, trigger scans, view issues/plans, approve fixes, see history/audit, and manage notifications.
* **Agent (AWS):** Serverless backend that runs the **scanner** (headless browser), uses **Bedrock** to reason about what to do, **posts a plan and results** back to the Dashboard, and—if allowed—**calls the WordPress plugin** to apply changes. Sends **Teams/email** alerts.
* **WordPress Plugin:** Local “fix engine.” Applies headers at PHP level, serves a region-aware cookie banner, generates policy pages, manages CSP rollout (Report-Only → Enforce), and supports  **versioned rollback** . Exposes a secure **REST API** the Agent uses.

---

## What the system checks & fixes

**Checks (scanner)**

* HTTPS redirect chain, HSTS
* Security headers:

  `Strict-Transport-Security`, `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy`
* **CSP** presence/quality (and observed script/style/img/connect sources)
* **Cookies** : `Secure`, `HttpOnly`, `SameSite`; non-essential cookies set before consent
* **Third-party** domains & trackers (to inform CSP and consent)

**Fixes (plugin via Agent)**

* Add/fix headers via `wp_send_headers`
* Enable  **cookie banner** ; block non-essential scripts until consent (EU/PDPA modes)
* Generate **Privacy/Cookie Policy** pages from templates (editable)
* Roll out  **CSP safely** : start **Report-Only** using an allowlist derived from scanner; optionally **promote to Enforce** after a clean observation window
* Keep **rollback versions** of changes

---

## End-to-end flow (happy path)

1. **Register site** in Dashboard
   * Save Domain, WP API base (`/wp-json/ssc/v1`), **WP API key** (from plugin), region mode (EU/MY/US/Other), and whether auto-fix is allowed.
2. **Run Scan** from Dashboard
   * Dashboard calls **Agent /scan** with site details + a **webhook URL** and  **webhook token** .
3. **Agent scans**
   * Headless Lambda fetches the site, records headers/cookies/CSP/third-parties.
   * Sends scan JSON to **Bedrock** to produce a **WordPress-aware remediation plan** (atomic actions).
4. **Agent returns results**
   * Posts `{score, issues, plan, applied?}` to Dashboard’s **webhook** (signed with token).
   * If **auto-fix = true** (or the Dashboard later approves), the Agent **calls the plugin** to apply changes.
5. **Plugin applies changes**
   * Sets headers, enables banner, creates policy pages, sets CSP in  **Report-Only** , records a **version snapshot** for rollback.
6. **Notify & verify**
   * Agent posts a summary to  **Teams/email** .
   * Dashboard can kick off a **re-scan** to see the score improve.
   * If something breaks, **rollback** instantly.

---

## Security model (simple & strong)

* **Per-site WP API key** stored in WP options and in the Dashboard (server-side only). All Agent → Plugin calls use `Authorization: Bearer <key>`.
* **Webhook** from Agent → Dashboard is signed with `X-SSC-Token` (env secret).

  (Optionally: add timestamp + HMAC to prevent replay.)
* **No secrets in the browser.** Dashboard never exposes WP API keys to the front end.
* **Least-privilege IAM** for Lambdas/Bedrock/S3. HTTPS everywhere.

---

## Data & objects we store (Dashboard)

* **Sites** : domain, wp_api_base, wp_api_key (encrypted), region_mode, auto_fix, Teams webhook, email, last_score.
* **Scans** : status, score, issues (JSON), plan (JSON), applied flag, raw scan JSON.
* **Actions** : audit trail (scan request, applied fixes, rollback, notify) with input/output snapshots.

---

## Why a plugin (not just server config)

* Works on **shared/managed hosts** (no Nginx/Apache access required).
* **Safe & reversible** : versioned settings;  **one-click rollback** .
* **WP-aware** : CONSENT gating, policy pages, typical plugin/script patterns.
* **Uniform** across different hosts/CDNs.

---

## Roles & responsibilities

* **Dashboard (human-facing)**
  * Adds sites, stores secrets, sets policies (auto-fix vs manual).
  * Triggers scans, reviews issues/plan, approves fixes.
  * Surfaces score/changes/history; sends stakeholder alerts.
* **Agent (automation)**
  * Scans, reasons, proposes a safe plan.
  * Applies fixes when allowed; posts results and notifications.
* **Plugin (enforcer)**
  * Applies headers/CSP/banner/policies; tracks versions.
  * Provides a **secure control surface** (REST) for the Agent.

---

## Deployment at a glance

* **Agent** : AWS CDK stack (API GW + Lambdas + Bedrock access).
* **Dashboard** : Laravel app (Inertia/Vue). Host anywhere (VPS/Forge/Elastic Beanstalk).
* **Plugin** : Zip → Upload to each WordPress site → Activate → Copy API key to Dashboard.

*(You already scaffolded these; this is just the conceptual map.)*

---

## What’s “safe by default” in our fixes

* **CSP** starts as **Report-Only** to avoid breaking live JS. Promote later.
* **Referrer-Policy** defaults to `strict-origin-when-cross-origin`.
* **HSTS** with a long `max-age` (preload optional).
* **Permissions-Policy** denies sensitive features unless explicitly needed.
* **Cookies** upgraded to `Secure`/`HttpOnly`/`SameSite` where compatible.
* **Consent** gating for non-essential trackers in EU/PDPA modes.

---

## Example sequences

### A. Auto-fix ON

1. Scan → Agent computes plan → **applies** via Plugin → Posts results → Teams alert → Re-scan shows improved score → CSP remains Report-Only for 48h.

### B. Auto-fix OFF

1. Scan → Agent posts plan (no changes) → Reviewer clicks **Apply** in Dashboard → Agent calls Plugin → Teams alert → Re-scan.

### C. Rollback

1. Something conflicts → Dashboard clicks **Rollback** → Plugin restores previous version → Teams alert → Re-scan.

---

## Extensibility ideas

* **CSP learner** : collect violation reports, suggest allowlist deltas automatically.
* **DSR portal starter** : access/download/delete request form for privacy laws.
* **Scheduled scans** & SLA reports.
* **Multi-tenant orgs** & RBAC in Dashboard.
* **Non-WP stacks** : GitHub PR mode (for Nginx/CSP/server files) instead of Plugin.

---

## Boundaries & assumptions

* We don’t promise  **zero breakage** ; CSP enforcement is purposely delayed.
* We **don’t change** app business logic—only security/compliance controls.
* We assume site owners consent to scanning/applying fixes and provide  **plugin API keys** .

---

## One-paragraph pitch (for docs/README)

> **SiteSecureCheck** is an agent-powered security & compliance copilot for WordPress. It scans your site (TLS, headers, cookies, CSP, trackers), proposes a safe remediation plan, and—if you allow—applies the fixes automatically through a lightweight plugin (headers, consent banner, policy pages, CSP roll-out) with full rollback. A Laravel dashboard manages sites, secrets, scans, and approvals; an AWS agent does the scanning, reasoning, and notifications (Teams/email). It’s designed for managed WP hosting, where you can’t edit server configs—but still want best-practice protections, fast.
>
