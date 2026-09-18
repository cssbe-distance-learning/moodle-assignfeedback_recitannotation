/**
 * Runs `parcel ./src/index.js --dist-dir ./build` as a normal dev server,
 * but also watches build/index.js and re-applies the named-define fix
 * after every incremental rebuild. `npm start` never triggers postbuild
 * (that only fires after the `build` script), and even if it did, a
 * one-shot patch would just get overwritten by the next rebuild — so the
 * patch has to be reapplied continuously, not once.
 */
const { spawn } = require('child_process');
const fs = require('fs');
const path = require('path');

const buildDir = path.resolve(__dirname, 'build');
const buildPath = path.join(buildDir, 'index.js');
const moduleName = 'recitannotation';

fs.mkdirSync(buildDir, { recursive: true });

const pattern = /define\(\s*(function\s*\([^)]*\))/;

function applyFix() {
  if (!fs.existsSync(buildPath)) return;

  let content;
  try {
    content = fs.readFileSync(buildPath, 'utf-8');
  } catch {
    return; // Parcel may still be mid-write; next change event will retry
  }

  // Already patched this build — avoids re-triggering fs.watch in a loop.
  if (content.includes(`define('${moduleName}'`)) return;

  if (!pattern.test(content)) return; // nothing anonymous to patch (yet)

  const fixed = content.replace(pattern, `define('${moduleName}', [], $1`);
  fs.writeFileSync(buildPath, fixed, 'utf-8');
  console.log(`✔ [watch] Named define() re-applied to build/index.js`);
}

let debounce;
fs.watch(buildDir, (eventType, filename) => {
  if (filename !== 'index.js') return;
  clearTimeout(debounce);
  debounce = setTimeout(applyFix, 150); // let Parcel finish writing first
});

const parcel = spawn(
  'npx',
  ['parcel', './src/index.js', '--dist-dir', './build'],
  { stdio: 'inherit', shell: true }
);

parcel.on('exit', (code) => process.exit(code ?? 0));