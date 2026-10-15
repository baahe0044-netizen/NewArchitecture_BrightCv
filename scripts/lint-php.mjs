import fs from 'node:fs';
import path from 'node:path';
import process from 'node:process';
import parserModule from 'php-parser';

const root = process.cwd();
const ignored = new Set(['vendor', 'node_modules', '.git']);

function walk(directory) {
  return fs.readdirSync(directory, { withFileTypes: true }).flatMap((entry) => {
    if (ignored.has(entry.name)) return [];
    const full = path.join(directory, entry.name);
    return entry.isDirectory() ? walk(full) : full.endsWith('.php') ? [full] : [];
  });
}

const parser = new parserModule.Engine({
  parser: { extractDoc: true, suppressErrors: false },
  ast: { withPositions: true },
});

const files = walk(root);
const failures = [];
for (const file of files) {
  try {
    parser.parseCode(fs.readFileSync(file, 'utf8'), path.relative(root, file));
  } catch (error) {
    failures.push(path.relative(root, file) + ': ' + error.message);
  }
}

if (failures.length) {
  console.error(failures.join('\n'));
  process.exit(1);
}

console.log('Parsed ' + files.length + ' PHP files successfully.');
