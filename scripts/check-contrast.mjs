#!/usr/bin/env node
/**
 * Measures WCAG contrast for the design tokens in every theme state.
 *
 * The palette is defined once per theme in public/assets/common/app.css, and a
 * ratio that fails is invisible until someone with low vision cannot read the
 * page. This reads the tokens straight out of the stylesheet so the numbers
 * always describe what actually ships, and fails the run if any pair drops
 * below the level its role requires.
 *
 * Usage: node scripts/check-contrast.mjs
 */

import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, join } from 'node:path';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const cssPath = join(root, 'public/assets/common/app.css');
const css = readFileSync(cssPath, 'utf8');

/** Pull one theme's token block out of the stylesheet. */
function tokensFrom(selectorPattern) {
  const start = css.search(selectorPattern);
  if (start === -1) return null;

  // Walk braces from the selector so a nested media query does not truncate it.
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
// text, icons, and the borders that carry meaning.
const PAIRS = [
  ['--ink', '--canvas', 4.5, 'body text on the page'],
  ['--ink', '--surface', 4.5, 'body text on a card'],
  ['--ink-soft', '--surface', 4.5, 'secondary text on a card'],
  ['--muted', '--surface', 4.5, 'muted text on a card'],
  ['--muted', '--canvas', 4.5, 'muted text on the page'],
  ['--brand', '--surface', 4.5, 'accent text and links on a card'],
  ['--brand', '--canvas', 4.5, 'accent text on the page'],
  ['--on-brand', '--brand', 4.5, 'label on a primary button'],
  ['--success', '--surface', 4.5, 'success text'],
  ['--warning', '--surface', 4.5, 'warning text'],
  ['--danger', '--surface', 4.5, 'error text'],
  ['--line-strong', '--surface', 3, 'a border that carries meaning'],
  ['--accent-ochre', '--surface', 4.5, 'ochre accent text'],
  ['--accent-moss', '--surface', 4.5, 'moss accent text'],
  ['--accent-teal', '--surface', 4.5, 'teal accent text'],
  ['--accent-plum', '--surface', 4.5, 'plum accent text'],
];

// Three palettes, each in light and dark. A palette is only useful if it is
// readable in both, so every combination is measured rather than the default.
const THEMES = [
  ['azure light', /:root\[data-palette="azure"\]\s*\{/],
  ['azure dark', /:root\[data-theme="dark"\]:not\(\[data-palette="mono"\]\):not\(\[data-palette="ember"\]\)\s*\{/],
  ['mono light', /:root\[data-palette="mono"\]\s*\{/],
  ['mono dark', /:root\[data-palette="mono"\]\[data-theme="dark"\]\s*\{/],
  ['ember light', /:root\[data-palette="ember"\]\s*\{/],
  ['ember dark', /:root\[data-palette="ember"\]\[data-theme="dark"\]\s*\{/],
];

let failures = 0;
let checked = 0;

for (const [name, pattern] of THEMES) {
  const tokens = tokensFrom(pattern);
  if (!tokens) {
    console.log(`\n${name}: token block not found`);
    failures++;
    continue;
  }

  console.log(`\n${name}`);
  console.log('-'.repeat(74));

  for (const [fg, bg, minimum, role] of PAIRS) {
    // A theme may legitimately not redefine every token; fall back to light.
    const light = tokensFrom(/:root\[data-palette="azure"\]\s*\{/);
    const fgValue = tokens[fg] ?? light[fg];
    const bgValue = tokens[bg] ?? light[bg];
    if (!fgValue || !bgValue) continue;

    const a = toRgb(fgValue);
    const b = toRgb(bgValue);
    if (!a || !b) continue;

    checked++;
    const value = ratio(a, b);
    const passed = value >= minimum;
    if (!passed) failures++;

    console.log(
      `${passed ? 'PASS' : 'FAIL'}  ${value.toFixed(2).padStart(5)} : 1  ` +
      `(needs ${minimum})  ${role}`
    );
  }
}

console.log(`\n${'='.repeat(74)}`);
if (failures > 0) {
  console.log(`${failures} contrast failure(s) across ${checked} measured pairs.`);
  process.exit(1);
}
console.log(`All ${checked} measured pairs meet their required contrast.`);
