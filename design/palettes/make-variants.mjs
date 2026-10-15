import fs from 'node:fs';

const palettes = [
  {
    prefix: 'Forest',
    fonts: 'https://fonts.googleapis.com/css2?family=Newsreader:opsz,wght@6..72,400;6..72,500;6..72,600&family=Public+Sans:wght@400;500;600;700&display=swap',
    body: '"Public Sans"',
    disp: '.disp { font-family: "Newsreader", "Iowan Old Style", Georgia, serif; font-weight: 500; }',
    root: `    :root {
      --bg: #F5F3EA; --panel: #FFFDF7; --panel-2: #EBE8D9;
      --ink: #17251A; --muted: #5B6A57; --line: #E0DCCA;
      --primary: #1E4D33; --primary-dark: #143523; --on-primary: #FDFBF3;
      --accent: #7A5208; --accent-soft: #F7EBCF;
      --ok: #2F6B45; --ok-soft: #E3EFE5;
      --r: 14px; --r-sm: 9px;
      --shadow: 0 1px 2px rgba(23,37,26,.04), 0 10px 26px rgba(23,37,26,.06);
    }`,
    swaps: {
      '42,26,44': '23,37,26',
      '107,45,92': '30,77,51',
      '#66490F': '#6B4A07',
      '#B7A6B7': '#A9B3A4',
      '#C4B4C4': '#B8C1B4',
      '#BFAEBF': '#B4BDB0',
      '#191019': '#172018',
      '#6C616C': '#656B62',
      '#3E313E': '#333B33',
      '#DCD6DA': '#D8DCD2',
      '#E2DCE0': '#E0E4DA',
    },
  },
  {
    prefix: 'Navy',
    fonts: 'https://fonts.googleapis.com/css2?family=Archivo:wght@500;600;700;800&family=Manrope:wght@400;500;600;700&display=swap',
    body: '"Manrope"',
    disp: '.disp { font-family: "Archivo", ui-sans-serif, system-ui, sans-serif; font-weight: 700; letter-spacing: -0.02em; }',
    root: `    :root {
      --bg: #F3F6FB; --panel: #FFFFFF; --panel-2: #E9EEF8;
      --ink: #101A33; --muted: #56618A; --line: #DCE3F0;
      --primary: #16264F; --primary-dark: #0C1734; --on-primary: #FFFFFF;
      --accent: #B03A20; --accent-soft: #FBE6E0;
      --ok: #1F6B57; --ok-soft: #E2F0EC;
      --r: 12px; --r-sm: 8px;
      --shadow: 0 1px 2px rgba(16,26,51,.04), 0 10px 26px rgba(16,26,51,.07);
    }`,
    swaps: {
      '42,26,44': '16,26,51',
      '107,45,92': '22,38,79',
      '#66490F': '#8C2D18',
      '#B7A6B7': '#A7AFC6',
      '#C4B4C4': '#B3BACD',
      '#BFAEBF': '#AEB6CA',
      '#191019': '#0F1526',
      '#6C616C': '#616B85',
      '#3E313E': '#333C55',
      '#DCD6DA': '#D6DBE6',
      '#E2DCE0': '#DFE4EE',
    },
  },
];

const sources = [
  ['Main.dc.html', ''],
  ['MainBuilder.dc.html', 'Builder'],
  ['MainMobile.dc.html', 'Mobile'],
];

const ROOT_RE = /    :root \{[\s\S]*?\n    \}/;
const DISP_RE = /\.disp \{ font-family: "DM Serif Display", Georgia, serif; font-weight: 400; \}/;
const LINK_RE = /https:\/\/fonts\.googleapis\.com\/css2\?family=DM\+Serif\+Display[^"]*/;
const BODY_RE = /font-family: "Outfit", ui-sans-serif/;

for (const p of palettes) {
  for (const [file, suffix] of sources) {
    let src = fs.readFileSync(file, 'utf8');
    const before = src;

    src = src.replace(LINK_RE, p.fonts);
    src = src.replace(ROOT_RE, p.root);
    src = src.replace(DISP_RE, p.disp);
    src = src.replace(BODY_RE, `font-family: ${p.body}, ui-sans-serif`);

    for (const [from, to] of Object.entries(p.swaps)) {
      src = src.split(from).join(to);
    }

    if (src === before) throw new Error(`no substitutions applied to ${file} for ${p.prefix}`);
    // ROOT_RE matches its own replacement, so check the old palette's values are gone instead.
    for (const marker of ['#6B2D5C', '#FAF5F7', 'DM Serif Display', 'Outfit']) {
      if (src.includes(marker)) throw new Error(`${p.prefix}${suffix}: "${marker}" still present — swap failed`);
    }
    for (const re of [DISP_RE, LINK_RE, BODY_RE]) {
      if (re.test(src)) throw new Error(`${p.prefix}${suffix}: ${re} still present — swap failed`);
    }

    const out = `${p.prefix}${suffix}.dc.html`;
    fs.writeFileSync(out, src);
    console.log(`wrote ${out}`);
  }
}
