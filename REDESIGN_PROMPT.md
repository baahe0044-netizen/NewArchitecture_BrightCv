# BrightCV — Full Design Overhaul Prompt

Paste this whole file to a coding agent working in the BrightCV repository. It is a
design brief, not a feature request: no new routes, no new database columns, no new
product surface. Everything below is a change to how the existing app looks, moves,
and speaks.

---

## 1. Mission

Rebuild BrightCV's entire visual and interaction design so the app feels **warm,
human, calm, and forgiving** instead of generic-SaaS. A person writing a CV is often
anxious, job-hunting, and tired. The interface should read like a patient friend who
knows the format, not like a corporate form that grades them.

You are redesigning, not rewriting. The PHP architecture, routes, controllers,
services, and data model stay exactly as they are.

---

## 2. What the app is today (read this before touching anything)

Stack: hand-rolled PHP MVC, no framework, **no build step**. Vanilla JS loaded with
`defer`. Plain CSS with custom properties. Served from `public/` via `.htaccess`
rewrites. Ships as an installable PWA with a service worker.

Design surface you will be changing:

| Area | Views | Styles / scripts |
| --- | --- | --- |
| Design tokens, buttons, cards, modals, toasts, forms | — | `public/assets/common/app.css` (~945 lines), `public/assets/common/app.js` |
| Landing page | `app/Views/landing/index.php` | `public/assets/landing/landing.css`, `landing.js` |
| Auth (login, register, forgot, reset) | `app/Views/auth/*.php` | `public/assets/auth/auth.css`, `auth.js` |
| Dashboard | `app/Views/dashboard/index.php` | `public/assets/dashboard/dashboard.css`, `dashboard.js` |
| CV builder — the core screen | `app/Views/resume/builder.php` | `public/assets/resume/builder.css` (~2,644 lines), `builder.js` (~1,677 lines) |
| Template gallery | `app/Views/templates/list.php`, `preview.php` | `public/assets/templates/template.css`, `template.js` |
| Account (profile, security, appearance) | `app/Views/account/*.php` | `public/assets/account/account.css`, `appearance.js` |
| Error pages 403 / 404 / 419 / 500 | `app/Views/errors/*.php` | `public/assets/common/app.css` |
| Shared chrome | `app/Views/components/*.php` (header, logo, flash, theme toggle, head meta) | — |
| CV document rendering + print | `app/Views/resume/print.php` | `public/assets/resume/preview.css`, `print.css`, `renderer.js` |

Existing shared JS API in `public/assets/common/app.js` — build on it, do not
duplicate it: `window.Lunetti.api()`, `.toast(message, type)`, `.openModal(id)`,
`.closeModal(id)`.

Current palette is cool corporate blue: `--brand: #4052b5`, greys running
`#f4f6f8` to `#17202e`. That is the main thing to replace.

---

## 3. Hard constraints — breaking any of these is a failed job

1. **The CV document itself stays print-safe and light.** `public/assets/resume/preview.css`
   and `print.css` deliberately opt out of the app theme so an exported CV is white
   with black text in every theme. Warm colours, textures, and motion apply to the
   *app chrome*, never to the rendered CV or the print stylesheet.
2. **Keep every DOM hook the test suite asserts on.** `scripts/test-view-accessibility.mjs`
   and `scripts/test-builder-ui.mjs` require, among others: `#email`, `#password`,
   `#name`, `#password_confirmation`, `[data-create-resume]`, `#createResumeModal`,
   `#deleteResumeModal`, `[data-duplicate]`, `[data-delete]`, `#templateSearch`,
   `[data-preview-template]`, `[data-use-template]`, `#templatePreviewModal`,
   `#nameTemplateResumeModal`, `#printNowButton`, `#printResume`, `#printData`,
   `#builderData`, `#resumePreview`, `#sectionEditor`, `#saveStatusText`, `#atsScore`,
   `#atsRecommendations`, `#summaryAssistantModal`, plus the account form actions and
   `[name=...]` fields. Restyle these elements freely; do not rename or remove them.
3. **Accessibility invariants:** every page keeps a `<main>` landmark and a non-empty
   `<title>`; every form control keeps an associated `<label>`; every button keeps an
   accessible name; element IDs stay unique per page.
4. **No build tooling, no npm runtime dependencies, no CSS framework, no CDN.** Write
   plain CSS and plain JS into the existing files. `package.json` devDependencies are
   test-only and stay that way.
5. **No new HTTP requests on load for decoration.** Illustrations are inline SVG or
   data URIs. No web font that blocks first paint — if you add a display face, load it
   `font-display: swap` with a real system fallback stack, self-hosted under
   `public/assets/`, and keep total added weight under ~100 KB.
6. **Offline still works.** If you add asset files, register them in `public/sw.js`
   alongside the existing precache list, and keep `public/offline.html` visually
   consistent with the new design.
7. **Theme system stays intact.** Three states — `system` (no `data-theme`), `light`,
   `dark` — set by `app/Views/components/theme_init.php` and
   `public/assets/common/theme-init.js` before first paint. Every new colour is a token
   defined for all three. No colour may be defined only inside a media query.
8. **`npm run check` must pass** when you are done.

---

## 4. Pillar A — Micro-interactions and motion

### A1. Build a motion system, then use only that system

Add motion tokens to `:root` in `app.css` and reference them everywhere. No ad-hoc
durations or easings anywhere in the codebase:

```css
--motion-fast: 120ms;    /* hover, focus, colour shifts */
--motion-base: 220ms;    /* the default for state change */
--motion-slow: 420ms;    /* entry of a panel, modal, page section */
--ease-out: cubic-bezier(.22, .61, .36, 1);
--ease-spring: cubic-bezier(.34, 1.26, .64, 1);   /* arrivals only, never exits */
```

Rules:

- Things that **enter** may overshoot slightly (`--ease-spring`). Things that **leave**
  never do — they fade and settle with `--ease-out`.
- Animate `transform` and `opacity` only. Never animate `height`, `top`, `width`, or
  `box-shadow` in a loop.
- Nothing moves more than ~8px. This is settling, not sliding.
- Nothing loops forever except a genuine progress indicator.

### A2. Honour reduced motion — globally, once

Add a single global block in `app.css`:

```css
@media (prefers-reduced-motion: reduce) {
  *, *::before, *::after {
    animation-duration: .01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: .01ms !important;
    scroll-behavior: auto !important;
  }
}
```

Any JS-driven animation must also check
`window.matchMedia('(prefers-reduced-motion: reduce)').matches` and skip straight to
the end state. Reduced motion must never mean "the feedback disappears" — the state
still changes, it just changes instantly.

### A3. Specific interactions to replace

- **Toasts** (`Lunetti.toast`): currently appear and are removed by a bare `setTimeout`.
  Give them a rise-and-fade entry, a graceful exit rather than an abrupt `remove()`,
  and pause the dismissal timer while hovered or focused. Keep `role="status"` /
  `role="alert"` and the `aria-live` region exactly as they are.
- **Modals** (`Lunetti.openModal` / `closeModal`): backdrop fades, panel scales from
  ~0.97 with `--ease-spring`. Preserve the existing focus-trap, focus-restore, and
  `aria-modal` handling — wrap it, do not rewrite it.
- **Builder autosave** (`#saveStatusText` in `builder.js`): the moment of "saved" is the
  app's most repeated interaction. Make it a soft, deliberate transition — writing to
  saved, with a gentle checkmark draw — that never shifts layout and never steals focus.
- **Buttons**: press state uses a subtle `scale(.98)` on `:active`. Focus rings use the
  existing `--focus` token and must stay clearly visible in both themes.
- **Adding or reordering a CV entry in the builder**: new rows fade and settle into
  place rather than snapping in. Removed rows collapse gently.
- **Dashboard cards and template cards**: lift 2–3px on hover with a shadow token
  change, at `--motion-fast`.

### A4. Delightful loading states

Replace generic spinners with skeletons and human messages.

- **Skeletons** for the dashboard CV list, the template gallery, and the builder
  preview: shaped like the content they replace, with a slow shimmer that respects
  reduced motion.
- **Message copy** while an operation is in flight — rotate gently, never faster than
  ~2.5s, and keep it plain when the user could be stressed:
  - Saving: "Saving your work…"
  - ATS check: "Reading your CV the way a robot would…"
  - Assistant / summary help: "Thinking about how to say that better…"
  - PDF export: "Setting your CV on the page…"
- Every loading state needs a live region so a screen-reader user hears it.
- A loading state that runs longer than ~10s must say so and offer a way out.

### A5. Haptic and sound cues — optional, off by default

- Add a **Feedback** section to `app/Views/account/appearance.php` (it already holds the
  theme radiogroup and is the natural home) with two independent switches: *Vibration*
  and *Sound*. **Both default to off.**
- Persist the choice the same way the theme preference is persisted, and read it in
  `app.js`. Never fire either cue unless the user turned it on.
- Vibration: `navigator.vibrate` where supported, feature-detected, one short pulse
  (≤ 20ms) for a completed save, export, or delete. Nothing on hover, nothing on typing.
- Sound: at most two short, soft, self-hosted cues (success, error) under ~15 KB each.
  Never autoplay on page load. Never play when the tab is hidden.
- Visual feedback is the baseline and always fires; haptics and sound are additive.

### A6. Forgiving inputs

The highest-value item in this brief. Apply it to auth forms, account forms, and every
builder field.

- **Never validate on `input`.** Validate on `blur`, and only after the field has been
  touched. Re-validate on `input` *only* once a field is already in an error state, so
  the error clears the instant it is fixed.
- **No red until the user has actually finished.** Use a neutral hint colour while
  typing; escalate to `--danger` only on submit or after blur.
- Error text sits **under** the field, is tied to it with `aria-describedby`, and the
  field gets `aria-invalid="true"`. The error must not shift the layout below it —
  reserve the space.
- On failed submit, move focus to the first invalid field and summarise at the top of
  the form in a polite live region.
- Show requirements (password rules, character limits) **before** they are broken, as a
  hint — not after, as a punishment.
- Keep destructive actions reversible where the API allows. After a delete, offer an
  undo path in the toast if the backend supports it; otherwise say plainly, and
  beforehand, that it cannot be undone.

---

## 5. Pillar B — Writing and tone

### B1. Voice definition (write this into the repo)

Create `docs/Voice.md` defining BrightCV's voice so future changes stay consistent:
**warm, plain, specific, never cute about someone's job search.** Second person.
Contractions allowed. No exclamation marks except on genuine wins. No jargon
("leverage", "utilise", "seamless"). No blame ("you failed to…"). International English,
no US-centric idioms — the sample data in the repo is already Accra-based.

### B2. Rewrite every string in the app

Sweep all views in `app/Views/`, all user-facing strings in `public/assets/**/*.js`, and
controller flash and validation messages. Concrete direction:

- **Buttons say what happens**, in the user's words: "Download my CV", not "Export".
  "Start a new CV", not "Create". "Save and keep writing", not "Submit".
- **Empty states** get a warm one-liner plus exactly one obvious next action. The
  dashboard with no CVs should feel like an invitation, not a void.
- **Error explanations** say what happened, why, and what to do next — in that order, in
  plain language, with no codes. Replace strings like the current
  `"The server returned an unreadable response."` and `"Request failed."` in `app.js`
  with something a person can act on, e.g. *"We couldn't reach the server just then.
  Your work is still here — check your connection and try again."*
- **Never lose the user's work silently.** Any failure message must say explicitly
  whether their content is safe.
- **404** (`app/Views/errors/404.php`): today it reads "We could not find that page."
  Give it light, tasteful wit plus a real way out — and keep it short. **403 / 419 / 500
  get warmth but not jokes**: a 419 means a session expired and someone may have just
  lost typing time; a 500 means something broke on your side. Apologise plainly, say
  what to do, and offer support.
- **Success screens** (CV exported, password changed, account updated) get a genuine,
  human acknowledgement — one line, no confetti-speak.
- **Microcopy under fields** replaces placeholder-only labelling. Placeholders must never
  be the only label; the accessibility test enforces real `<label>` elements.

### B3. Consistency sweep

Same concept, same word, everywhere. Pick **CV** or **résumé** and use one only (the app
is called BrightCV — pick "CV"). Same for "template" vs "design", "download" vs "export",
"account" vs "profile". Fix the codebase to match, including page `<title>`s, the PWA
manifest, toasts, modal headings, and any email copy.

---

## 6. Pillar C — Visual design and layout

### C1. Replace the palette with a warm, tactile one

Rewrite the token block at the top of `app.css` — all three theme states.

- Move off `#4052b5` cool blue. Aim for a **warm, earthy** family: a grounded ink (warm
  near-black, not blue-black), a paper-like canvas (soft warm off-white, not `#f4f6f8`),
  and an accent with warmth in it — terracotta, ochre, deep moss, or a muted plum. One
  accent, used sparingly.
- No neon. No purple-to-blue gradients. No pure `#000` or `#fff` for text or ground.
- Semantic colours (`--success`, `--warning`, `--danger`) get warmed to match rather than
  sitting as stock traffic-light hues, while staying unmistakably distinguishable.
- **Contrast is non-negotiable:** body text ≥ 4.5:1, large text and UI borders ≥ 3:1, in
  light, dark, and system-dark.
- The dark theme is a **warm** dark (brown-black, not navy), and must be re-derived from
  the new palette, not mechanically inverted.
- Update `<meta name="theme-color">` in `app/Views/components/head_meta.php` and the PWA
  manifest colours to the new palette.

### C2. Organic, imperfect touches

- Author **custom inline SVG illustrations** with a visible hand-drawn quality — slightly
  irregular strokes, open line work, no perfect geometry — for: the landing hero, each
  empty state (no CVs, no search results, offline), the four error pages, and the
  export-success moment. They must be original, theme-token-coloured
  (`stroke="currentColor"` or `var(--…)`), `aria-hidden="true"` when decorative, and
  carry a real `<title>` when meaningful.
- Redraw the icon set as one consistent family: uniform stroke width, rounded caps,
  slightly loose. Ship it as an inline SVG sprite, not per-icon files.
- Add **restrained texture**: a very low-opacity paper grain or subtle noise on the page
  canvas, done in CSS or a tiny tiling data URI. It must be near-invisible on a cheap
  screen, and must never appear on the CV document or in print.
- Soften geometry — slightly larger, less uniform radii — and swap the current flat drop
  shadows for warmer, softer ones tinted with the ink colour rather than grey.

### C3. Whitespace and typography

- Introduce a spacing scale as tokens (`--space-1` … `--space-9`, ~4px base) and use it
  everywhere. Delete one-off pixel margins as you go.
- **Increase breathing room by roughly 25–40%** on the landing page, dashboard, account
  pages, and error pages. Section rhythm should be generous and consistent.
- The **builder is the exception**: it is a dense working tool. Improve its rhythm and
  grouping, but do not trade away information density there. Give the editor column calm
  and the preview column room.
- Typography: a warmer, more characterful display face for headings (self-hosted,
  `font-display: swap`, real fallback stack) against a highly readable text face; body
  text ~16–17px at ~1.6 line-height; measure capped near 65–75 characters.
- Establish a type scale as tokens and apply it — no arbitrary `font-size` values left in
  the stylesheets.

### C4. Photography

The repo currently ships **no photography** (only `img/gear.png` and generated PWA
icons). The brief calls for real human photography over polished 3D or AI renders.
**Do not generate, fabricate, or hotlink people images.** Instead:

- Build the landing page and any social-proof area with proper `<figure>` / `<img>`
  slots — correct aspect ratios, `width` and `height` set to prevent layout shift,
  `loading="lazy"`, meaningful `alt` — and fill them with the hand-drawn illustrations as
  a designed placeholder state.
- Add a section to `docs/Voice.md` (or a new `docs/Design.md`) specifying what real
  photos must be when the owner supplies them: real people, natural light, unposed,
  diverse, no stock-corporate handshakes, no 3D renders — plus the exact file paths and
  dimensions to drop them into.
- Flag this in your summary as needing the owner's own licensed photos.

---

## 7. Pillar D — Personalization and control

- **Remember preferences per device.** The theme choice already persists — extend the
  same mechanism to: the builder's active section, the editor/preview split or collapsed
  state, dashboard sort and view mode, the template gallery's last filter, and scroll
  position when returning to a long list. Use `localStorage` wrapped in `try/catch`
  (private mode and blocked storage must not throw), namespace the keys (`brightcv:…`),
  and always render correctly when nothing is stored.
- **Escape hatches everywhere.** Every modal closes on `Esc`, on backdrop click, and via a
  visible, labelled close button. Any onboarding, tour, tip, or promo is skippable in one
  click and stays dismissed. Nothing traps focus except a real modal. Nothing auto-plays.
  Nothing blocks the builder.
- **Turn the noise off.** The Feedback switches from §A5 live beside the theme control in
  `app/Views/account/appearance.php`, together with a toggle for non-essential toasts and
  any animation-heavy flourish, so a user can quiet the app without the OS-level
  reduced-motion setting being their only lever.
- **Real support access.** Add a persistent, visible way to reach a human: a "Get help"
  entry in the account navigation (`app/Views/components/account_nav.php`) and a footer
  link, plus a support line on **every error page** (403, 419, 500 especially) and on any
  failed save or export. Since there is no support route today, wire it to a `mailto:`
  built from a single constant defined once in `config/app.php`, with the subject
  pre-filled to reference what the user was doing. Do not invent a chatbot and do not
  build a ticketing system.

---

## 8. Suggested order of work

1. Tokens first: palette, spacing, type, motion, shadows, radii in `app.css`. Verify
   contrast in all three theme states before going further.
2. Shared primitives: buttons, cards, forms, modals, toasts, skeletons, header, logo,
   flash — everything in `app/Views/components/` and the shared half of `app.css`.
3. Error pages and empty states (fastest visible payoff, and where the voice work shows
   most).
4. Landing, then auth, dashboard, templates, account.
5. Builder last, and carefully — largest surface, most heavily tested.
6. Copy sweep and `docs/Voice.md` across everything you touched.
7. Preferences, escape hatches, feedback switches, support links.

Work in reviewable commits, one area per commit.

---

## 9. Acceptance criteria

- [ ] `npm run check` passes (lint:php, lint:js, test:integrity, test:php, test:renderer,
      test:sw, test:entry, test:ui, test:views).
- [ ] Every DOM hook listed in §3.2 still exists and is still found by the tests.
- [ ] Light, dark, and system themes each verified on landing, dashboard, builder,
      templates, account, and all four error pages.
- [ ] Contrast verified: ≥ 4.5:1 body text, ≥ 3:1 large text and UI borders, every theme.
- [ ] Full keyboard pass: every interactive element reachable, focus always visible,
      modals trap and restore focus, `Esc` closes everything closeable.
- [ ] `prefers-reduced-motion: reduce` verified — the app is still fully usable and no
      feedback is lost, only the movement.
- [ ] An exported or printed CV is unaffected by the redesign: still white, still
      print-safe, in every theme.
- [ ] Offline mode still works; any new asset is precached in `public/sw.js`;
      `public/offline.html` matches the new design.
- [ ] No new runtime dependency, no build step, no CDN request, no blocking font.
- [ ] No layout shift on load (reserved dimensions on images, illustrations, and error
      text slots).
- [ ] Mobile verified at 375px wide and at a tablet width; nothing scrolls horizontally.
- [ ] Every user-facing string reviewed against `docs/Voice.md`; terminology consistent
      app-wide.

---

## 10. Out of scope

New routes, new controllers, schema changes, auth changes, pricing or paywall UI, an
AI-feature expansion, analytics, a component framework, a CSS framework, a build
pipeline, and any third-party script.

---

## 11. Report back

When you finish, summarise: the new token palette with the contrast numbers you
measured; every file you changed; anything in this brief you could not do and why; and an
explicit list of what still needs the owner personally — licensed photography, the
support email address, and any brand decision you had to make on their behalf.
