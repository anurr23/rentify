import { copyFileSync, cpSync, mkdirSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const files = [
  ['node_modules/three/build/three.module.js', 'assets/vendor/three.module.js'],
  ['node_modules/three/build/three.core.js', 'assets/vendor/three.core.js'],
  ['node_modules/chart.js/dist/chart.umd.js', 'assets/vendor/chart.umd.js'],
  ['node_modules/leaflet/dist/leaflet.js', 'assets/vendor/leaflet/leaflet.js'],
  ['node_modules/leaflet/dist/leaflet.css', 'assets/vendor/leaflet/leaflet.css'],
];

for (const [from, to] of files) {
  const target = resolve(root, to);
  mkdirSync(dirname(target), { recursive: true });
  copyFileSync(resolve(root, from), target);
  console.log(`copied ${from} -> ${to}`);
}

cpSync(resolve(root, 'node_modules/leaflet/dist/images'), resolve(root, 'assets/vendor/leaflet/images'), { recursive: true });
console.log('copied leaflet markers -> assets/vendor/leaflet/images');
