#!/usr/bin/env node
/**
 * Measures WCAG contrast for the Quest design tokens.
 *
 * The palette is defined once, in the top-level :root block of
 * public/assets/common/app.css (Quest ships as the app's one identity,
 * light only), and a ratio that fails is invisible until someone with low
 * vision cannot read the page. This reads the tokens straight out of the
 * stylesheet so the numbers always describe what actually ships, and fails
 * the run if any pair drops below the level its role requires.
 *
 * Flat, saturated colour blocks -- exactly what this palette is built from
 * -- are precisely where contrast most often goes wrong, which is why every
 * pair here is measured rather than assumed.
 *
 * Usage: node scripts/check-contrast.mjs
 */

import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, join } from 'node:path';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const cssPath = join(root, 'public/assets/common/app.css');
const css = readFileSync(cssPath, 'utf8');

/** Pull the first top-level :root { ... } block out of the stylesheet. */
function tokensFrom() {
  const start = css.indexOf(':root {');
  if (start === -1) return null;

  let depth = 0;
  let index = css.indexOf('{', start);
  const from = index;
  for (; index < css.length; index++) {
    if (css[index] === '{') depth++;
    else if (css[index] === '}') {
      depth--;
      if (depth === 0) break;
    }
  }

  const block = css.slice(from, index);
  const tokens = {};
  for (const match of block.matchAll(/(--[a-z0-9-]+)\s*:\s*([^;]+);/gi)) {
    tokens[match[1]] = match[2].trim();
  }
  return tokens;
}

function resolve(tokens, value, depth = 0) {
  if (depth > 5) return value;
  const ref = value.match(/^var\((--[a-z0-9-]+)\)$/i);
  if (ref && tokens[ref[1]]) {
    return resolve(tokens, tokens[ref[1]], depth + 1);
  }
  return value;
}

function toRgb(value) {
  const hex = value.trim();
  if (/^#[0-9a-f]{6}$/i.test(hex)) {
    return [
      parseInt(hex.slice(1, 3), 16),
      parseInt(hex.slice(3, 5), 16),
      parseInt(hex.slice(5, 7), 16),
    ];
  }
  if (/^#[0-9a-f]{3}$/i.test(hex)) {
    return [hex[1], hex[2], hex[3]].map((c) => parseInt(c + c, 16));
  }
  const rgb = hex.match(/^rgba?\(([^)]+)\)$/i);
  if (rgb) {
    const parts = rgb[1].split(',').map((p) => parseFloat(p));
    return [parts[0], parts[1], parts[2]];
  }
  return null;
}

function luminance([r, g, b]) {
  const channel = (value) => {
    const v = value / 255;
    return v <= 0.03928 ? v / 12.92 : ((v + 0.055) / 1.055) ** 2.4;
  };
  return 0.2126 * channel(r) + 0.7152 * channel(g) + 0.0722 * channel(b);
}

function ratio(foreground, background) {
  const a = luminance(foreground);
  const b = luminance(background);
  const [light, dark] = a > b ? [a, b] : [b, a];
  return (light + 0.05) / (dark + 0.05);
}

// Each pair names the level its role requires: 4.5 for body text, 3 for large
// text, icons, and the borders that carry meaning. Includes the literal §3
// pairings this palette is built from -- rust/gold/moss/berry text and the
// choco header's own text colours -- not just the generic ink/muted set.
const PAIRS = [
  ['--ink', '--sand', 4.5, 'body text on the page'],
  ['--ink', '--card', 4.5, 'body text on a card'],
  ['--ink', '--card-2', 4.5, 'body text on a secondary surface'],
  ['--muted', '--card', 4.5, 'muted text on a card'],
  ['--muted', '--sand', 4.5, 'muted text on the page'],
  ['--rust-deep', '--card', 4.5, 'rust link/eyebrow text on a card'],
  ['--rust-deep', '--sand', 4.5, 'rust link text on the page'],
  ['--card', '--rust', 4.5, 'label on a rust (primary) button'],
  ['--card', '--moss', 4.5, 'label on a moss (next-stage) button'],
  ['--moss-deep', '--moss-soft', 4.5, 'moss XP text on a completed row'],
  ['--rust-deep', '--card', 4.5, 'rust XP text on a card'],
  ['--moss-deep', '--card', 4.5, 'moss done-state text on a card'],
  ['--ink', '--gold', 4.5, 'text on a gold chip or stage marker'],
  ['--ink', '--gold-soft', 4.5, 'text on the gold-soft quest panel'],
  ['--ink', '--moss-soft', 4.5, 'text on a completed quest row'],
  ['--ink', '--berry-soft', 4.5, 'text on a berry-soft support callout'],
  ['--danger', '--card', 4.5, 'error text'],
  ['--warning', '--card', 4.5, 'warning text'],
  ['--success', '--card', 4.5, 'success text'],
  ['--line-strong', '--card', 3, 'a border that carries meaning'],
  // The choco header, level banner, and stage rail card carry their own text
  // colours rather than the page palette.
  ['--choco-ink', '--choco', 4.5, 'primary text on the choco header/banner'],
  ['--choco-muted', '--choco', 4.5, 'muted text on the choco header/banner'],
  ['--choco-ink', '--choco-well', 4.5, 'text on the sunken XP-bar well'],
];

const tokens = tokensFrom();
if (!tokens) {
  console.log('No :root token block found.');
  process.exit(1);
}

let failures = 0;
let checked = 0;

console.log('Quest palette');
console.log('-'.repeat(74));

for (const [fg, bg, minimum, role] of PAIRS) {
  const fgValue = tokens[fg];
  const bgValue = tokens[bg];
  if (!fgValue || !bgValue) {
    console.log(`SKIP  (missing token)  ${role}  [${fg} / ${bg}]`);
    continue;
  }

  const a = toRgb(resolve(tokens, fgValue));
  const b = toRgb(resolve(tokens, bgValue));
  if (!a || !b) {
    console.log(`SKIP  (unresolved colour)  ${role}  [${fg}=${fgValue} / ${bg}=${bgValue}]`);
    continue;
  }

  checked++;
  const value = ratio(a, b);
  const passed = value >= minimum;
  if (!passed) failures++;

  console.log(
    `${passed ? 'PASS' : 'FAIL'}  ${value.toFixed(2).padStart(5)} : 1  ` +
    `(needs ${minimum})  ${role}`
  );
}

console.log(`\n${'='.repeat(74)}`);
if (failures > 0) {
  console.log(`${failures} contrast failure(s) across ${checked} measured pairs.`);
  process.exit(1);
}
console.log(`All ${checked} measured pairs meet their required contrast.`);
