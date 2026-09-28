/**
 * Post-build fix for "Mismatched anonymous define() module".
 *
 * v3: handles every shape the Parcel output can take instead of assuming one.
 *   1. Anonymous `define(function...)` present  -> give it a name (old behaviour).
 *   2. Our named define already present         -> nothing to do (idempotent).
 *   3. No define at all (IIFE bundle, which is what
 *      current Parcel emits when the entry has no exports)
 *                                               -> append a guarded named define so
 *                                                  RequireJS gets a registered module.
 */
const fs = require('fs');
const path = require('path');

const filePath = path.resolve(__dirname, 'build/index.js');
let content = fs.readFileSync(filePath, 'utf-8');

const moduleName = 'recitannotation';

const anonPattern = /define\(\s*(function\s*\([^)]*\))/;
const namedPattern = new RegExp(`define\\(\\s*["']${moduleName}["']`);

if (namedPattern.test(content)) {
  console.log(`✔ define('${moduleName}') already present — nothing to do.`);
  process.exit(0);
}

const anonMatches = content.match(new RegExp(anonPattern, 'g'));

if (anonMatches && anonMatches.length > 0) {
  if (anonMatches.length > 1) {
    console.warn(`⚠ Found ${anonMatches.length} anonymous define() calls — only the first will be named. Inspect build/index.js manually.`);
  }
  content = content.replace(anonPattern, `define('${moduleName}', [], $1`);
  fs.writeFileSync(filePath, content, 'utf-8');
  console.log(`✔ Named define() added to build (matched: ${anonMatches[0].trim()})`);
  process.exit(0);
}

// Case 3: no anonymous define. Register the module ourselves.
const shim =
  `if (typeof define === 'function' && define.amd) { ` +
  `define('${moduleName}', [], function () { return window.loadRecitAnnotationReactApp; }); }\n`;

// Keep the sourceMappingURL comment as the last line.
const smIndex = content.lastIndexOf('//# sourceMappingURL=');
content = smIndex === -1
  ? content.replace(/\s*$/, '\n') + shim
  : content.slice(0, smIndex) + shim + content.slice(smIndex);

fs.writeFileSync(filePath, content, 'utf-8');
console.log(`✔ No anonymous define in build (plain IIFE) — appended named define('${moduleName}').`);