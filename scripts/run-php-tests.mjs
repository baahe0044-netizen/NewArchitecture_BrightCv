import path from 'node:path';
import process from 'node:process';
import { PHP } from '@php-wasm/universal';
import { loadNodeRuntime, useHostFilesystem } from '@php-wasm/node';

const testFile = path.join(process.cwd(), 'tests', 'run.php').replaceAll('\\', '/');
const php = new PHP(await loadNodeRuntime('8.1', {
  emscriptenOptions: { processId: 1 },
}));
useHostFilesystem(php);
const result = await php.runStream({
  code: '<?php require ' + JSON.stringify(testFile) + ';',
});

const stdout = await result.stdoutText;
const stderr = await result.stderrText;
const exitCode = await result.exitCode;
if (stdout) process.stdout.write(stdout);
if (stderr) process.stderr.write(stderr);

if (exitCode !== 0 || stderr) {
  process.exit(exitCode || 1);
}

process.exit(0);
