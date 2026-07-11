const assert = require('node:assert/strict');
const fs = require('node:fs');

const source = fs.readFileSync('templates/pn_natuna_2026/js/template.js', 'utf8');
const css = fs.readFileSync('templates/pn_natuna_2026/css/template.css', 'utf8');
const template = fs.readFileSync('templates/pn_natuna_2026/index.php', 'utf8');

assert.match(source, /function createModalLifecycle\(/, 'shared modal focus lifecycle is required');
assert.match(source, /event\.key === 'Home'/, 'Instansi tabs need Home navigation');
assert.match(source, /slide\.inert = inactive/, 'inactive carousel slides must be inert');
assert.doesNotMatch(source.slice(source.indexOf('function setupStickyNav'), source.indexOf('function setupInstansiTabs')), /addEventListener\('scroll'/, 'sticky nav must not register a scroll listener');
assert.match(source, /root\.dataset\.theme = active \? 'dark' : 'light'/, 'dark theme must update html data-theme');
assert.match(css, /\/\* A11Y THEME FIXES 2026-07-11 \*\//, 'a11y CSS theme block is required');
assert.doesNotMatch(css, /\.maklumat-lightbox[\s\S]{0,300}backdrop-filter/, 'Maklumat lightbox must not blur backdrop');
assert.match(template, /data-theme="light"/, 'html must expose initial theme hook');
