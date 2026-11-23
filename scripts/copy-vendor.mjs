import { copyFileSync, mkdirSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const files = [
  ['node_modules/three/build/three.module.js', 'assets/vendor/three.module.js'],
  ['node_modules/three/build/three.core.js', 'assets/vendor/three.core.js'],
  ['node_modules/chart.js/dist/chart.umd.js', 'assets/vendor/chart.umd.js'],
];

for (const [from, to] of files) {
  const target = resolve(root, to);
  mkdirSync(dirname(target), { recursive: true });
  copyFileSync(resolve(root, from), target);
  console.log(`copied ${from} -> ${to}`);
}
