/**
 * This script fixes the "Mismatched anonymous define() module" error.
 * This error typically occurs when there's a conflict between RequireJS (used by Moodle/YUI) and Parcel's module system,
 * especially when a bundled file includes an anonymous AMD module that gets loaded by RequireJS.
 *
 * v2: matches any anonymous `define(function...)` call directly, instead of the
 * surrounding define.amd wrapper — Parcel has changed that wrapper's exact
 * formatting (ternary vs if-block, spacing) across versions, which silently
 * broke the old regex. This version is agnostic to that wrapper shape.
 */
const fs = require('fs');
const path = require('path');

const filePath = path.resolve(__dirname, 'build/index.js');
let content = fs.readFileSync(filePath, 'utf-8');

const moduleName = 'recitannotation';

// Matches `define(` immediately followed by an anonymous `function (...)`.
// This deliberately does NOT match named calls like define('classnames', [], function(){...})
// since those start with a string literal, not `function`.
const pattern = /define\(\s*(function\s*\([^)]*\))/;

const matches = content.match(new RegExp(pattern, 'g'));

if (!matches || matches.length === 0) {
  console.error('✖ No anonymous define(function...) found — build output format has changed. Aborting.');
  process.exit(1);
}

if (matches.length > 1) {
  console.warn(`⚠ Found ${matches.length} anonymous define() calls — expected exactly 1. Only the first will be named. Inspect build/index.js manually.`);
}

const fixedContent = content.replace(pattern, `define('${moduleName}', [], $1`);

fs.writeFileSync(filePath, fixedContent, 'utf-8');
console.log(`✔ Named define() added to build (matched: ${matches[0].trim()})`);