import fs from 'node:fs';
import path from 'node:path';
import process from 'node:process';
import { spawnSync } from 'node:child_process';

const root = process.cwd();
const ignored = new Set(['vendor', 'node_modules', '.git']);

function walk(directory) {
  return fs.readdirSync(directory, { withFileTypes: true }).flatMap((entry) => {
    if (ignored.has(entry.name)) return [];
    const full = path.join(directory, entry.name);
    return entry.isDirectory() ? walk(full) : /\.[cm]?js$/.test(full) ? [full] : [];
  });
}

const files = walk(root);
const failures = [];
for (const file of files) {
  const result = spawnSync(process.execPath, ['--check', file], { encoding: 'utf8' });
  if (result.status !== 0) failures.push(path.relative(root, file) + ':\n' + result.stderr.trim());
}

if (failures.length) {
  console.error(failures.join('\n\n'));
  process.exit(1);
}

console.log('Checked ' + files.length + ' JavaScript files successfully.');
