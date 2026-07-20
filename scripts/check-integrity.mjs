import fs from 'node:fs';
import path from 'node:path';

const root = process.cwd();
const failures = [];

function filesUnder(directory) {
  if (!fs.existsSync(directory)) return [];
  return fs.readdirSync(directory, { withFileTypes: true }).flatMap((entry) => {
    const target = path.join(directory, entry.name);
    return entry.isDirectory() ? filesUnder(target) : [target];
  });
}

function relative(file) {
  return path.relative(root, file).replaceAll('\\', '/');
}

const sourceFiles = [
  ...filesUnder(path.join(root, 'app')),
  ...filesUnder(path.join(root, 'config')),
  ...filesUnder(path.join(root, 'database')),
  ...filesUnder(path.join(root, 'public')),
].filter((file) => /\.(php|js|css)$/.test(file));

sourceFiles.forEach((file) => {
  if (fs.statSync(file).size === 0) failures.push(relative(file) + ' is empty.');
});

const phpFiles = sourceFiles.filter((file) => file.endsWith('.php'));
phpFiles.forEach((file) => {
  const source = fs.readFileSync(file, 'utf8');
  for (const match of source.matchAll(/asset\(\s*['"]([^'"]+)['"]\s*\)/g)) {
    const asset = path.join(root, 'public/assets', match[1]);
    if (!fs.existsSync(asset) || !fs.statSync(asset).isFile()) {
      failures.push(relative(file) + ' references missing asset public/assets/' + match[1]);
    }
  }
  for (const match of source.matchAll(/View::partial\(\s*['"]([^'"]+)['"]/g)) {
    const view = path.join(root, 'app/Views', match[1] + '.php');
    if (!fs.existsSync(view) || !fs.statSync(view).isFile()) {
      failures.push(relative(file) + ' references missing partial app/Views/' + match[1] + '.php');
    }
  }
  for (const match of source.matchAll(/(?:->view|Response::view)\(\s*['"]([^'"]+)['"]/g)) {
    const view = path.join(root, 'app/Views', match[1] + '.php');
    if (!fs.existsSync(view) || !fs.statSync(view).isFile()) {
      failures.push(relative(file) + ' references missing view app/Views/' + match[1] + '.php');
    }
  }
});

const routes = fs.readFileSync(path.join(root, 'config/routes.php'), 'utf8');
for (const match of routes.matchAll(/\$router->(?:get|post|put|delete)\([^,]+,\s*'([^']+)@([^']+)'/g)) {
  const [, controller, method] = match;
  const controllerFile = path.join(root, 'app/Controllers', controller + '.php');
  if (!fs.existsSync(controllerFile) || !fs.statSync(controllerFile).isFile()) {
    failures.push('Route references missing controller ' + controller + '.');
    continue;
  }
  const source = fs.readFileSync(controllerFile, 'utf8');
  const methodPattern = new RegExp('function\\s+' + method + '\\s*\\(');
  if (!methodPattern.test(source)) {
    failures.push('Route references missing action ' + controller + '@' + method + '.');
  }
}

if (failures.length) {
  throw new Error('Repository integrity check failed:\n- ' + failures.join('\n- '));
}

console.log('Routes, views, partials, and asset references are complete.');
