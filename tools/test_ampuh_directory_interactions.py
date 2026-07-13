"""Focused behavioral contract for AMPUH directory interactions."""
from pathlib import Path
import subprocess
import tempfile
ROOT = Path(__file__).resolve().parents[1]
ROOT = Path(__file__).resolve().parents[1]
SOURCE = ROOT / "templates/pn_natuna_2026/js/template.js"
source = SOURCE.read_text(encoding="utf-8")
for token in [
    "setupAmpuhDirectory()",
    "data-ampuh-search",
    "data-ampuh-gobi-filter",
    "aria-expanded",
    "ampuh-directory__empty",
]:
    assert token in source, token
start = source.index("function setupAmpuhDirectory")
depth = 0
end = start
for end, character in enumerate(source[start:], start):
    if character == "{":
        depth += 1
    elif character == "}":
        depth -= 1
        if depth == 0:
            break
function_source = source[start:end + 1]
assert "localStorage" not in function_source
assert "sessionStorage" not in function_source

NODE_TEST = r'''
const fs = require('fs');
const vm = require('vm');
const source = fs.readFileSync(process.argv[2], 'utf8');
class Node {
  constructor(tag = 'div', attrs = {}, text = '') { this.tagName = tag.toUpperCase(); this.attrs = {...attrs}; this.ownText = text; this.children = []; this.parentElement = null; this.hidden = false; this.listeners = {}; this.classList = { toggle() {}, add() {}, remove() {} }; }
  get textContent() { return [this.ownText, ...this.children.map(child => child.textContent)].join(' ').trim(); }
  set textContent(value) { this.ownText = value; }
  append(...nodes) { nodes.forEach(node => { node.parentElement = this; this.children.push(node); }); return this; }
  setAttribute(name, value) { this.attrs[name] = String(value); }
  getAttribute(name) { return this.attrs[name] ?? null; }
  hasAttribute(name) { return Object.hasOwn(this.attrs, name); }
  addEventListener(type, listener) { (this.listeners[type] ||= []).push(listener); }
  fire(type) { (this.listeners[type] || []).forEach(listener => listener({ currentTarget: this, target: this })); }
  matches(selector) { return selector.split(',').some(part => { part = part.trim(); if (part === '[hidden]') return this.hidden; const data = part.match(/^\[data-([^\]=]+)(?:="([^"]+)")?\]$/); if (data) return Object.hasOwn(this.attrs, `data-${data[1]}`) && (!data[2] || this.attrs[`data-${data[1]}`] === data[2]); return false; }); }
  closest(selector) { for (let node = this; node; node = node.parentElement) if (node.matches(selector)) return node; return null; }
  querySelectorAll(selector) { const result = []; const visit = node => node.children.forEach(child => { if (child.matches(selector)) result.push(child); visit(child); }); visit(this); return result; }
  querySelector(selector) { return this.querySelectorAll(selector)[0] || null; }
}
const root = new Node('article', {'data-ampuh-directory': ''});
const search = new Node('input', {'data-ampuh-search': ''}); search.value = '';
const filter = new Node('div', {'data-ampuh-gobi-filter': ''});
const one = new Node('button', {'data-ampuh-filter-value': '1'}, 'GOBI Satu');
const two = new Node('button', {'data-ampuh-filter-value': '2'}, 'GOBI Dua');
const close = new Node('button', {'data-ampuh-close-all': ''});
const results = new Node('p', {'data-ampuh-results': ''});
const tree = new Node('div');
const gobi1 = new Node('section', {'data-ampuh-gobi': '1', 'data-search-text': 'gobi pelayanan'}, 'GOBI pelayanan');
const toggle1 = new Node('button', {'data-ampuh-toggle': '', 'aria-controls': 'panel-1', 'aria-expanded': 'false'}, 'Buka GOBI');
const panel1 = new Node('div', {'data-ampuh-panel': '', id: 'panel-1'}); panel1.hidden = true;
const checklist = new Node('section', {'data-search-text': 'checklist layanan publik'}, 'Checklist layanan publik');
const toggle2 = new Node('button', {'data-ampuh-toggle': '', 'aria-controls': 'panel-2', 'aria-expanded': 'false'}, 'Buka checklist');
const panel2 = new Node('div', {'data-ampuh-panel': '', id: 'panel-2'}); panel2.hidden = true;
const sub = new Node('section', {'data-search-text': 'sub checklist layanan'}, 'Sub checklist layanan');
const files = new Node('ul', {}, 'Surat Keputusan ÁMPUH.pdf');
const gobi2 = new Node('section', {'data-ampuh-gobi': '2', 'data-search-text': 'gobi hukum'}, 'GOBI hukum');
const legal = new Node('section', {'data-search-text': 'checklist perkara'}, 'Checklist perkara');
root.append(search, filter.append(one, two), close, results, tree.append(gobi1.append(toggle1, panel1.append(checklist.append(toggle2, panel2.append(sub.append(files))))), gobi2.append(legal)));
const document = { addEventListener: () => {}, querySelector: selector => selector === '[data-ampuh-directory]' ? root : null, getElementById: id => root.querySelectorAll('[data-ampuh-panel]').find(node => node.attrs.id === id) || null, createElement: tag => new Node(tag) };
const context = { document, window: {}, console };
vm.createContext(context); vm.runInContext(source, context);
context.setupAmpuhDirectory();
if (toggle1.getAttribute('aria-expanded') !== 'false' || !panel1.hidden) throw Error('initial disclosures must remain closed');
toggle1.fire('click');
if (toggle1.getAttribute('aria-expanded') !== 'true' || panel1.hidden) throw Error('toggle must synchronize aria-expanded and panel hidden');
close.fire('click');
if (toggle1.getAttribute('aria-expanded') !== 'false' || !panel1.hidden) throw Error('close all must close every disclosure');
search.value = 'LAYANAN'; search.fire('input');
if (gobi1.hidden || !gobi2.hidden) throw Error(`search must be case-insensitive and hide non-matches: ${gobi1.hidden}/${gobi2.hidden}, ${gobi1.textContent}`);
if (!results.textContent.includes('1')) throw Error('search must report matching sub-checklist or file count');
one.fire('click');
if (gobi1.hidden || !gobi2.hidden) throw Error('GOBI filter must intersect active search');
search.value = ''; search.fire('input');
if (gobi1.hidden || gobi2.hidden) throw Error('empty search must restore all items');
if (toggle1.getAttribute('aria-expanded') !== 'false' || toggle2.getAttribute('aria-expanded') !== 'false') throw Error('empty search must reset disclosures to closed');
two.fire('click');
if (!gobi1.hidden || gobi2.hidden) throw Error('GOBI filter must select requested GOBI');
two.fire('click');
if (gobi1.hidden || gobi2.hidden) throw Error('second filter click must clear filter');
search.value = 'tidak-ditemukan'; search.fire('input');
if (!gobi1.hidden || !gobi2.hidden || results.textContent !== 'Tidak ada dokumen yang cocok.') throw Error('no match must show empty state and live message');
context.document = { querySelector: () => null };
context.setupAmpuhDirectory();
'''
with tempfile.NamedTemporaryFile("w", suffix=".js", encoding="utf-8", delete=False) as runner:
    runner.write(NODE_TEST)
    runner_path = runner.name
try:
    completed = subprocess.run(["node", runner_path, str(SOURCE)], text=True, capture_output=True)
    assert completed.returncode == 0, completed.stderr
finally:
    Path(runner_path).unlink(missing_ok=True)
print("AMPUH directory interaction contract: ok")
