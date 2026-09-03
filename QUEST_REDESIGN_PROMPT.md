# BrightCV — "Quest" Redesign (Exact-Match) Prompt

Paste this whole file to a coding agent working in the BrightCV repository. Unlike a
mood-board brief, this is a **literal implementation spec**: the target look is fully
built already as five reference mockups, and the job is to port them into the real
app pixel-for-pixel, then verify that and keep iterating until it actually matches —
not to reinterpret the vibe.

---

## 1. Ground truth — read this before writing any CSS

The exact design to ship already exists as working HTML in this repo:

```
design/quest/Main.dc.html          — Dashboard (desktop, 1440×900)
design/quest/Builder.dc.html       — CV builder (desktop, 1440×900)
design/quest/Rewards.dc.html       — Rewards page (desktop, 1440×900) — NEW screen
design/quest/Phone.dc.html         — Dashboard (phone, 390×844)
design/quest/PhoneBuilder.dc.html  — CV builder (phone, 390×844)
```

These are not sketches — they are real HTML/CSS with literal inline `style="..."`
attributes on every element. **Treat them as the executable spec.** For any value —
a padding, a font-size, a border width, a shadow offset, a gap — copy the literal
number out of the matching block in these files. Do not eyeball it from a screenshot,
and do not round to a convenient number. If a real-app component doesn't have an
obvious match in these five files, its layout logic still applies (same borders,
same shadow language, same radii scale, same spacing rhythm) — extrapolate from the
nearest sibling component in the mockups, not from taste.

A published, pixel-identical rendering of all five is also live at:
**https://claude.ai/code/artifact/8404002c-f988-4698-a337-fd4204eba729**
Open it in a browser pane alongside the running app during the verification pass in
§8 — it is the fastest way to compare colours and spacing side by side.

---

## 2. The rule: exact match, not "inspired by"

If the ported page's header isn't the same 74px height, the same `#3A2A1E` brown, the
same 3px bottom border, and the same chip shapes as `Main.dc.html`'s header block —
it isn't done yet. Close is not the target. Re-read §8 before calling anything
finished.

---

## 3. Design tokens — the literal palette

No white, no blue, anywhere in the app chrome. One warm palette, used consistently:

| Token | Hex | Used for |
|---|---|---|
| `--sand` | `#EBD9BB` | Page background |
| `--card` | `#FBF3E3` | Card / panel surface |
| `--card-2` | `#F3E3C6` | Secondary surface, input fields, mock document lines |
| `--ink` | `#2A1D14` | Body text, every border |
| `--muted` | `#6E5A45` | Secondary text |
| `--rust` | `#C1442A` | Primary action — "do this now" |
| `--rust-deep` | `#9A3320` | Links, rust text-on-light |
| `--gold` | `#F2A81D` | In-progress fill, streak, XP |
| `--gold-soft` | `#FBE3AE` | Tip callouts, "today's quests" panel, active-stage highlight |
| `--moss` | `#4E7A34` | Done / complete |
| `--moss-deep` | `#3F6528` | Dark green text on light green |
| `--moss-soft` | `#DCE9CC` | Completed-row backgrounds |
| `--berry` | `#A02454` | Rewards, avatar |
| `--berry-soft` | `#F4D8E2` | Support / help callouts |
| `--choco` | `#3A2A1E` | Header, level banner, dark chrome |

Text-on-choco needs its own three values (used only inside `--choco` surfaces):
`#F6E9D2` (primary text), `#C9B79A` (muted text), `#55402F` / `#7C6350` (borders,
disabled icon colour).

**Typography:** one face everywhere — **Tinos** (Google Fonts), which is metrically
identical to Times New Roman, so a blocked or slow font request falls back to real
Times with no reflow. Stack: `"Tinos", "Times New Roman", Times, serif`. There is no
separate display face — headings are the same serif at heavier weight, not a
different font.

**Shape language** — read this once, it governs every component:
- Borders are **2px solid var(--ink)** on nearly everything (3px on the header's
  bottom edge, 3px on a few emphasis states like the active stage or an achievement
  line being edited).
- Shadows are **hard, offset, no blur**: `4px 4px 0 var(--ink)` on major panels,
  `3px 3px 0 var(--ink)` on buttons and mid-size cards, `2px 2px 0 var(--ink)` on
  small badges. Never a soft/blurred shadow, never `backdrop-filter`, never a
  gradient fill.
- Radii: 14px cards, 9–12px buttons and inputs, 999px pills and circles, 5–8px small
  tiles.
- Texture: a halftone dot ground on every page —
  `background-image: radial-gradient(rgba(42,29,20,.09) 1.2px, transparent 1.2px); background-size: 17px 17px;`
  (the `.dots` class in every mockup file). This, the flat colour blocks, and the hard
  offset shadows are precisely what keeps this from reading as generic AI-generated
  UI — do not soften any of it.
- Icons are inline SVG only, stroke-based, 1.9–2.4px stroke, rounded caps. No emoji,
  no icon fonts, anywhere.

---

## 4. Scope beyond a pure reskin

Two things here are bigger than CSS:

**A new screen.** Rewards (`design/quest/Rewards.dc.html`) doesn't exist in the app
today. It needs a route, a controller, a view, and a nav entry between Templates and
Account (see the header in every desktop mockup: *Dashboard · Templates · Rewards ·
Account*).

**Game state.** XP, levels, streaks, quests, and badges are real product state, not
decoration — the Rewards page's own footer is explicit that this must stay honest
("points are only worth something if they track real quality... nothing is awarded
for simply logging in"). Decide and implement with that constraint:

- *Level thresholds* (from the Rewards ladder): Starter 0 XP, Drafter 400 XP,
  Contender 1,000 XP, Shortlister 1,500 XP, Interviewer 2,200 XP.
- *XP-worthy events* (from the mockups' literal numbers): a stage of the builder
  finished (+40 for Experience shown; size others by effort), an achievement line
  that contains a number (+20), a summary trimmed under 60 words (+25), contact
  details completed (+15), a new CV tailored from an existing one (+50).
- *Streak*: consecutive days with a save/edit.
- *Badges*: named, earned accomplishments (see the Rewards grid) — "First draft",
  "Numbers person" (an achievement line with a digit in it), "Tight summary", "Five
  days running" (streak), "First download", "Two CVs going", plus the locked set
  (Robot score 80+, One page exactly, Three roles tailored, Fourteen days running, No
  filler words, Every stage finished).
- *Template unlocks*: gated by level (Broadsheet at Level 4 / 260 XP away, Two Column
  at Level 5 / 960 XP away, per the mockup).

This needs schema work (columns or tables for XP, streak, earned badges, unlock
state) — flag that plainly rather than faking it. If a full migration is out of scope
for this pass, ship **Phase 1**: derive the displayed numbers deterministically from
data the app already has (completion %, ATS score, resume count, export count) so the
screen is truthful and stable across reloads, not random placeholder numbers, and
say explicitly in your handover that persisted XP/streak/badges are Phase 2.

**Off switch.** The Rewards page says the whole layer can be turned off in Account.
Add a "Gamification" toggle to `app/Views/account/appearance.php` (same pattern as
the existing theme radiogroup) that, when off, hides the streak/XP chips from every
header, hides the quest and badge panels from the dashboard, hides XP labels in the
builder's stage rail, and reverts the Rewards nav item — falling back to plain
language ("72% complete" instead of a progress ring plus streak chip, etc.).

---

## 5. Screen-by-screen mapping

For each: read the mockup file named, port its literal values, wire it to the real
view/controller/asset files named.

### 5.1 Dashboard — `design/quest/Main.dc.html` (desktop), `Phone.dc.html` (mobile)
→ `app/Views/dashboard/index.php`, `public/assets/dashboard/dashboard.css`

Key components to port exactly: the choco level banner (level badge, two-colour XP
bar — gold for total progress, a second rust segment for "earned today", "next
unlock" callout); the "Continue with…" panel (mock CV thumbnail, progress ring SVG,
the five-node journey stepper *Drafted → Filled in → Polishing → ATS 80+ → Sent out*
with the current node enlarged and numbered); the CV library grid (percentage chip
vs. "Done" chip, dashed "start a new run" card with its XP hint); the right rail
("Today's three" quest list — done/active/pending states each styled differently,
badges preview grid, support callout).

### 5.2 CV builder — `design/quest/Builder.dc.html` (desktop), `PhoneBuilder.dc.html` (mobile)
→ `app/Views/resume/builder.php`, `public/assets/resume/builder.css`,
`public/assets/resume/builder.js`

Port: the choco top bar (back button, title chip, Saved chip, "+90 XP today" chip,
undo/redo, Look/Download PDF buttons); the 282px stage rail (level card with progress
ring, six stage rows each carrying an XP value, the active stage visually distinct);
the editor column (stage header with its XP reward stated up front, the entry card
with per-line XP shown on a completed achievement line, the tip box that ties a
concrete instruction to an XP number, add-line/add-role affordances); the preview
column (a "Robot score" meter alongside the existing ATS number, live A4 preview).
**The CV sheet inside the preview stays plain white with black text, exactly as it
does today** — none of this palette or shadow language touches
`resume/preview.css` / `resume/print.css`. That boundary is deliberate in the current
codebase; keep it.

Mobile: stage progress becomes a two-colour bar plus a horizontally scrollable row of
stage chips (done/active/pending), and the footer becomes a fixed Back / Next-stage
bar with the XP reward printed on the Next button.

### 5.3 Rewards — `design/quest/Rewards.dc.html` (desktop only; build the phone
version following the same mobile patterns as the other two screens)
→ new `app/Views/rewards/index.php`, new `RewardsController`, new route
`/rewards` (`['auth']`), new `public/assets/rewards/rewards.css`, new nav entry in
`app/Views/components/app_header.php` and (if you build a mobile bottom tab bar —
see §5.4) in its markup too.

Port: the level ladder (five nodes, the current one enlarged, connectors coloured by
whether that gap is crossed); the badges grid (earned tiles are solid colour with a
2px2px0 shadow, locked tiles are dashed-border with a lock icon); the templates list
(in-use / owned-with-Use-button / locked-with-XP-cost, each visually distinct); the
"closest badge" callout computed from real data (nearest locked badge to the user's
current numbers); the transparency panel with the link into the gamification toggle
from §4.

### 5.4 Mobile chrome
The phone mockups introduce a bottom tab bar (Home / Templates / a raised centre "+"
FAB / Rewards / Account) that the current app doesn't have — it currently relies on
the header's hamburger menu on small screens. Decide with the team whether this
replaces or supplements that pattern; the mockups assume it replaces it below ~768px.
Port its exact shape from `Phone.dc.html`: pill-shaped bar, 2px ink border, 4px4px0
shadow, the FAB raised and rust-filled with its own shadow, active tab highlighted in
gold.

---

## 6. Hard constraints — unchanged from the current codebase

1. **The CV document stays print-safe and light**, per §5.2 — `resume/preview.css`
   and `print.css` are out of scope for this palette.
2. **Every DOM hook the test suite asserts on must survive.** `scripts/test-view-accessibility.mjs`
   and `scripts/test-builder-ui.mjs` require, among others: `#email`, `#password`,
   `#name`, `#password_confirmation`, `[data-create-resume]`, `#createResumeModal`,
   `#deleteResumeModal`, `[data-duplicate]`, `[data-delete]`, `#templateSearch`,
   `[data-preview-template]`, `[data-use-template]`, `#templatePreviewModal`,
   `#nameTemplateResumeModal`, `#printNowButton`, `#printResume`, `#printData`,
   `#builderData`, `#resumePreview`, `#sectionEditor`, `#saveStatusText`, `#atsScore`,
   `#atsRecommendations`, `#summaryAssistantModal`, plus account form actions and
   `[name=...]` fields. Restyle freely; do not rename or remove them.
3. **Accessibility invariants stay**: a `<main>` landmark and non-empty `<title>` on
   every page, a real `<label>` on every form control, an accessible name on every
   button, unique element IDs per page. Verify contrast on this palette specifically
   — `--muted` (`#6E5A45`) on `--sand`/`--card`, and any accent-on-accent-soft pairing
   (e.g. `--rust` badges), are the pairs most likely to fail 4.5:1; measure, don't
   assume, since flat saturated colour blocks are exactly where this goes wrong.
4. **No build step, no npm runtime dependency, no CSS framework, no CDN** beyond a
   Google Fonts `<link>` for Tinos — add it wherever the app's existing font links
   live (`grep -r "fonts.googleapis.com" app/Views` to find the current pattern and
   follow it) so every page picks it up from one shared partial.
5. **Offline still works.** Register any new asset file in `public/sw.js`; keep
   `public/offline.html` visually consistent with the new palette.
6. **The existing theme system** (`data-theme` light/dark, the Azure/Mono/Ember
   palette picker in `app.css`) has no equivalent in these mockups — they show one
   palette only. Default assumption for this pass: **retire the palette picker**
   (Quest becomes the one identity) and **ship light-only** for now, since inventing
   a dark variant would be extrapolation beyond the reference, not porting it. Flag
   this explicitly in your handover rather than silently dropping dark-mode support
   people may rely on for visual comfort — it's a real product decision, not yours to
   make unilaterally.
7. **`npm run check` must pass** when you are done.

---

## 7. Suggested order of work

1. Tokens: replace the `:root` colour values in `app.css` with §3's palette (keep
   existing variable *names* where they map cleanly — `--brand`→rust, `--surface`→
   card, `--canvas`→sand, `--ink`, `--line`, `--success`→moss, `--warning`→gold,
   `--danger` — so downstream CSS files need fewer rewrites); strip every
   `backdrop-filter` / frosted-glass rule and the `data-palette` system; add the hard
   shadow tokens and the `.dots` texture as shared utilities.
2. Shared chrome: header (desktop + mobile), buttons, cards, chips, the stage-row
   pattern — everything reused across screens.
3. Dashboard (§5.1), then Builder (§5.2) — these carry the DOM hooks in §6, so build
   and re-run `npm run check` after each.
4. Rewards (§5.3): route, controller, view, CSS, nav entry.
5. Gamification data (§4) and the off-switch in Appearance.
6. Mobile chrome (§5.4) across all screens.
7. The verification pass in §8 — budget real time for this; it's not a formality.

---

## 8. Visual verification protocol — do this, then do it again

This is the part the earlier ask was explicit about: build it, check it against the
reference, and keep fixing it until it actually matches — not once, in a loop, until
it holds up.

For **each of the five screens**, at its reference viewport (1440px desktop / 390px
mobile — use this project's browser-preview tooling to launch the real app and load
the actual route):

1. **Render both side by side.** Open the real, running page in one tab and the
   matching `design/quest/*.dc.html` file (or the published artifact URL in §1) in
   another, at the same viewport width.
2. **Diff systematically, not by impression:**
   - Sample the actual rendered colour of the header, the primary button, the active
     stage/quest row, and the badge tiles — do they match the hex values in §3
     exactly, or did a browser default / an old token leak through?
   - Compare header height, panel padding, card radius, border width, and shadow
     offset against the literal numbers in the source file for that region.
   - Compare every string of copy verbatim — button labels, chip text, XP numbers,
     stage names — against the mockup.
   - Check every icon is the same inline SVG shape, not a stand-in.
   - Confirm no gradient, blur, or soft shadow crept in anywhere in the ported
     region.
3. **List every mismatch found**, however small, before fixing anything — this
   avoids fixing the same class of bug five times across five screens.
4. **Fix, then re-render and re-diff.** Do not consider a screen done on the first
   pass; assume the first port has drift and go looking for it.
5. **Repeat until**: colours match the §3 hex values exactly, spacing matches the
   source file's literal values, every string of copy matches, and nothing in §6 has
   regressed (`npm run check` passes, DOM hooks intact, contrast holds, CV preview
   still print-safe).
6. Only then move to the next screen. When all five hold up, do one more full pass
   across all five together — a fix made late for screen 5 sometimes drifts a shared
   token used by screen 2.

Do not report the redesign as finished until this loop has actually run and the
mismatches it found have actually been fixed — "looks close" is not the bar; "matches
the reference file's literal values" is.

---

## 9. Report back

When done, state: which screens you verified pixel-for-pixel and what the
verification loop in §8 actually caught and fixed (not just that it ran); which parts
of §4 you shipped as Phase 1 (derived numbers) versus flagged for Phase 2 (persisted
schema); your decision on the theme picker in §6.6 and why; and anything in this
brief you could not complete, with the reason.
