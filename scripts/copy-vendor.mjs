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
  ['node_modules/flatpickr/dist/flatpickr.min.js', 'assets/vendor/flatpickr/flatpickr.min.js'],
  ['node_modules/flatpickr/dist/flatpickr.min.css', 'assets/vendor/flatpickr/flatpickr.min.css'],
  ['node_modules/flatpickr/dist/l10n/id.js', 'assets/vendor/flatpickr/id.js'],
  ['node_modules/tom-select/dist/js/tom-select.complete.min.js', 'assets/vendor/tom-select/tom-select.min.js'],
  ['node_modules/tom-select/dist/css/tom-select.min.css', 'assets/vendor/tom-select/tom-select.min.css'],
];

for (const [from, to] of files) {
  const target = resolve(root, to);
  mkdirSync(dirname(target), { recursive: true });
  copyFileSync(resolve(root, from), target);
  console.log(`copied ${from} -> ${to}`);
}

cpSync(resolve(root, 'node_modules/leaflet/dist/images'), resolve(root, 'assets/vendor/leaflet/images'), { recursive: true });
console.log('copied leaflet markers -> assets/vendor/leaflet/images');
