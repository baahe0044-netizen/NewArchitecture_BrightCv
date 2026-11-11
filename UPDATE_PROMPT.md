# LunettiStar CV — November update prompt

Use this prompt to build the update. Work on branch `agent/production-cv-builder`.

## Ground rules

- **Do not break anything that works today.** Routes, saved CVs, autosave, import,
  print, download, login, guest accounts and the PWA must behave exactly as before
  unless an item below changes them on purpose.
- **No database changes.** No new tables, columns or migrations. Everything new is
  stored inside the existing `content_json` of a CV.
- **Old CVs must keep working.** Anything saved before this update (typed dates,
  CVs without a photo, users who turned gamification on) must still open, render,
  print and download correctly.
- Plain PHP 8.1, plain JavaScript, no new frameworks. Scripts stay same-origin
  (the CSP only allows `'self'`).
- After the work: `npm run lint:js`, `lint:php`, `test:integrity`, `test:contrast`,
  `test:php`, `test:renderer` must pass. Update tests that describe behaviour this
  update changes on purpose. Check the builder, dashboard and landing page in the
  browser on desktop and at phone width (375px).

## The 11 changes

### 1. ATS score must not reward rubbish
**Today:** `app/Services/AtsService.php` only checks that fields are filled and long
enough. A CV full of "asdf asdf" with five fake skills scores high.

**Build:** add a text-quality check and let it lower the score.
- A word-quality test: share of tokens that look like real words. Penalise
  keyboard mashing (no vowels, long consonant runs such as "qwrtzx"), repeated
  characters ("aaaaaa"), the same word repeated over and over, and very low
  variety of distinct words.
- Apply it per section: summary, each bullet, skill names, role/company, degree/school.
  Rubbish text earns **zero** for that part, not partial credit.
- Contact: email must be a valid address and phone must contain at least 7 digits;
  a name must have letters.
- Add a "Content quality" recommendation that names the section with gibberish.
- A real, complete CV must still score 70 or more (the existing test in
  `tests/run.php`). Add a test that a gibberish CV scores under 30.

### 2. Skills section never ticks
**Today:** the Skills tick in `builder.js` `updateProgress()` needs **5** skills.
Every other section ticks with one real entry.

**Build:** tick Skills when at least one skill has a name, like the other sections.
Keep the "five to twelve skills" advice text. The completion percentage weighting
(in `renderer.js` `calculateProgress` and `ResumeService::completion`) stays as it is.

### 3. Dates are picked, not typed
**Today:** every date (experience, education, projects start/end, certification date)
is a free text box.

**Build:** replace each with a **Month** dropdown + **Year** dropdown (works in every
browser, including desktop Safari and Firefox, unlike `<input type="month">`).
- Store the value as `YYYY-MM` (or `YYYY` when only a year is chosen).
- Years: current year + 6 down to 1960.
- The CV shows it nicely: `Jan 2023`, with month names in the CV's language
  (en/fr/es) — done in `renderer.js`, so the preview, print and PDF all match.
- **Old typed dates:** when a CV opens, convert anything recognisable ("Jan 2023",
  "January 2023", "01/2023", "2023-01", "2023") into the new format. Anything that
  cannot be understood is kept as it is and still shown on the CV, so nothing is lost.
- Server sanitising keeps the 30-character limit and accepts both formats.
- "I currently work here" still replaces the end date with Present.

### 4. Download button at the end of the steps
**Build:** on the last step (More → Interests) the Next button becomes a
**Download CV** button that runs the existing download flow (guest account check,
save, PDF). Remove the Download button from the builder's top bar. Print / PDF stays
in the top bar. The print preview page keeps its own Download PDF button.

### 5. Skill suggestions
**Build:** in the Skills step, show "Suggested skills" chips the user can tap to add.
- Suggestions come from the CV's headline and job titles (a built-in, local list of
  common roles → skills: software, data, design, marketing, sales, finance,
  accounting, HR, teaching, healthcare/nursing, customer service, admin, project
  management, engineering, hospitality, logistics…), plus general skills when
  nothing matches.
- Hide skills the CV already has (case-insensitive). Tapping a chip adds the skill
  with level "Proficient" and removes the chip. Nothing is sent to any server.

### 6. Step count includes the "More" sub-steps
**Today:** "More sections" has 4 sub-steps (Certifications, Languages, References,
Interests) but the counter says "Step 7 of 7" for all of them.

**Build:** count every step: Personal, Summary, Experience, Education, Skills,
Projects, Certifications, Languages, References, Interests = **10 steps**.
Show "Step 8 of 10 · Languages", and so on.

### 7. Remove gamification completely
Remove XP, levels, streaks, badges, quests, rewards and template unlocks from the
whole app, without affecting anything else.
- Delete `RewardsController`, `GamificationService`, `GamificationRepository`,
  `app/Views/rewards/`, `public/assets/rewards/`, the `/rewards` and
  `/account/gamification` routes, `badge_icon()` in `config/app.php`.
- Remove the gamification parts of: the app header (streak/XP chips, Rewards link),
  bottom nav (Rewards tab → Account), dashboard (level banner, quests, badges, "+50
  XP"), builder (XP chip, level card, "+20" step weights), Account → Appearance
  (the on/off switch), and their CSS.
- Old links to `/rewards` should go to the dashboard instead of a 404.
- Keep the plain CV progress percentage and its labels — they are not gamification.
- Update comments and tests that mention it.

### 8. White, blue and black colours
Change the app's colour tokens in `public/assets/common/app.css`:
- **Light:** white page and cards, black text, blue buttons and links, navy-blue
  header (matching the logo navy).
- **Dark:** black page, near-black cards, white text, bright blue accents.
- Make blue the only colour scheme: the other accent palettes (green, citrus, cream,
  brick) are removed from Account → Appearance, and anyone who picked one gets blue.
- The CV itself is not touched (its own template colours stay).
- `npm run test:contrast` must pass; update its colour pairs to the new values.
- Regenerate nothing else; the logo stays as it is.

### 9. Photos on CVs
**Build:** a "Profile photo" field in Personal details: choose a photo, see it,
remove it.
- Resize and crop it in the browser to a square of about 320×320 px JPEG (target
  under ~60 KB) and store it as a `data:image/jpeg;base64,…` string in
  `content.personal.photo`.
- Server: accept only `data:image/(jpeg|png|webp);base64,` with valid base64, max
  ~200 KB; anything else is dropped. It must not go through the 255-character cut.
- Show it on the templates that have a picture/initials circle: the circle shows
  the photo instead of the initials. Templates that hide the circle stay text-only,
  and the field says which templates show the photo.
- Works in the live preview, print and the downloaded PDF.

### 10. Simpler landing page, easier on phones
Rewrite `app/Views/landing/index.php` and `landing.css`:
- Hero: one short headline, one short line, one main button ("Create your CV") and
  a "Log in" link. No repeated buttons.
- Three short steps (Fill in your details → Pick a template → Download), one line each.
- Keep a small template preview strip and a short footer. Drop the long sections.
- Phone first: large tap targets, one column, no horizontal scroll, text readable
  at 375px.

### 11. Example name
Change the "Full name" example from "Emmanuel Baah" to **"Ama Mensah"** everywhere
users see it (and in test data).

## Finish

- Commit on `agent/production-cv-builder` with clear messages and push to GitHub;
  PR #1 picks it up.
- Report what changed, what was tested, and anything that needs the user's action
  (uploading to Hostinger).
