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
    "data-ampuh-gobi-select",
    "setSelectedGobi",
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
  constructor(tag = 'div', attrs = {}, text = '') { this.tagName = tag.toUpperCase(); this.attrs = {...attrs}; this.ownText = text; this.children = []; this.parentElement = null; this.hidden = false; this.listeners = {}; this.classes = new Set(); this.classList = { toggle: (name, enabled) => enabled ? this.classes.add(name) : this.classes.delete(name), contains: name => this.classes.has(name), add: name => this.classes.add(name), remove: name => this.classes.delete(name) }; }
  get textContent() { return [this.ownText, ...this.children.map(child => child.textContent)].join(' ').trim(); }
  set textContent(value) { this.ownText = value; }
  get id() { return this.attrs.id || ''; }
  append(...nodes) { nodes.forEach(node => { node.parentElement = this; this.children.push(node); }); return this; }
  setAttribute(name, value) { this.attrs[name] = String(value); }
  getAttribute(name) { return this.attrs[name] ?? null; }
  hasAttribute(name) { return Object.hasOwn(this.attrs, name); }
  addEventListener(type, listener) { (this.listeners[type] ||= []).push(listener); }
  fire(type) { (this.listeners[type] || []).forEach(listener => listener({ currentTarget: this, target: this })); }
  matches(selector) { return selector.split(',').some(part => { part = part.trim(); if (part === '[hidden]') return this.hidden; if (part.startsWith('.')) return (this.attrs.class || '').split(/\s+/).includes(part.slice(1)); const data = part.match(/^\[data-([^\]=]+)(?:="([^"]+)")?\]$/); if (data) return Object.hasOwn(this.attrs, `data-${data[1]}`) && (!data[2] || this.attrs[`data-${data[1]}`] === data[2]); return false; }); }
  closest(selector) { for (let node = this; node; node = node.parentElement) if (node.matches(selector)) return node; return null; }
  querySelectorAll(selector) { const result = []; const visit = node => node.children.forEach(child => { if (child.matches(selector)) result.push(child); visit(child); }); visit(this); return result; }
  querySelector(selector) { return this.querySelectorAll(selector)[0] || null; }
}
const root = new Node('article', {'data-ampuh-directory': ''});
const search = new Node('input', {'data-ampuh-search': ''}); search.value = '';
const select = new Node('select', {'data-ampuh-gobi-select': ''}); select.value = '';
const filter = new Node('div', {'data-ampuh-gobi-filter': ''});
const one = new Node('button', {'data-ampuh-filter-value': '1', 'aria-pressed': 'false'}, 'GOBI Satu');
const two = new Node('button', {'data-ampuh-filter-value': '2', 'aria-pressed': 'false'}, 'GOBI Dua');
const close = new Node('button', {'data-ampuh-close-all': ''});
const results = new Node('p', {'data-ampuh-results': ''});
const tree = new Node('div', {class: 'ampuh-directory__tree'});
const gobi1 = new Node('section', {'data-ampuh-gobi': '1', 'data-search-text': 'gobi pelayanan'}, 'GOBI pelayanan');
const toggle1 = new Node('button', {'data-ampuh-toggle': '', 'aria-controls': 'panel-1', 'aria-expanded': 'false'}, 'Buka GOBI');
const panel1 = new Node('div', {'data-ampuh-panel': '', id: 'panel-1'}); panel1.hidden = true;
const checklist = new Node('section', {'data-search-text': 'checklist layanan publik'}, 'Checklist layanan publik');
const toggle2 = new Node('button', {'data-ampuh-toggle': '', 'aria-controls': 'panel-2', 'aria-expanded': 'false'}, 'Buka sub checklist');
const panel2 = new Node('div', {'data-ampuh-panel': '', id: 'panel-2'}); panel2.hidden = true;
const sub = new Node('section', {'data-search-text': 'sub checklist layanan'}, 'Sub checklist layanan');
const heading = new Node('h5', {}, 'Daftar dokumen (1)');
const files = new Node('ul');
const file = new Node('li', {'data-search-text': 'surat keputusan ampuh.pdf', 'data-ampuh-file-result': ''}, 'Surat Keputusan ÁMPUH.pdf');
const gobi2 = new Node('section', {'data-ampuh-gobi': '2', 'data-search-text': 'gobi hukum'}, 'GOBI hukum');
const legal = new Node('section', {'data-search-text': 'checklist perkara'}, 'Checklist perkara');
root.append(search, select, filter.append(one, two), close, results, tree.append(gobi1.append(toggle1, panel1.append(checklist.append(toggle2, panel2.append(sub.append(heading, files.append(file)))))), gobi2.append(legal)));
const document = { addEventListener: () => {}, querySelector: selector => selector === '[data-ampuh-directory]' ? root : null, getElementById: id => root.querySelectorAll('[data-ampuh-panel]').find(node => node.attrs.id === id) || null, createElement: tag => new Node(tag) };
const animationFrames = [];
const context = { document, window: { requestAnimationFrame: callback => animationFrames.push(callback) }, console };
vm.createContext(context); vm.runInContext(source, context);
context.setupAmpuhDirectory();
if (toggle1.getAttribute('aria-expanded') !== 'false' || !panel1.hidden) throw Error('initial disclosures must remain closed');
if (one.getAttribute('aria-pressed') !== 'false' || two.getAttribute('aria-pressed') !== 'false') throw Error('initial GOBI filters must expose unpressed state before clicks');
toggle1.fire('click');
if (toggle1.getAttribute('aria-expanded') !== 'true' || panel1.hidden || !panel1.classList.contains('is-revealing')) throw Error('opening must unhide panel and apply reveal start state');
if (animationFrames.length !== 1) throw Error('opening must queue first animation frame');
animationFrames.shift()();
if (!panel1.classList.contains('is-revealing')) throw Error('first animation frame must retain reveal start state');
if (animationFrames.length !== 1) throw Error('first animation frame must queue reveal completion');
animationFrames.shift()();
if (panel1.classList.contains('is-revealing')) throw Error('second animation frame must remove reveal start state');
close.fire('click');
if (toggle1.getAttribute('aria-expanded') !== 'false' || !panel1.hidden || panel1.classList.contains('is-revealing')) throw Error('close all must immediately hide panel and clear reveal state');
delete context.window.requestAnimationFrame;
toggle1.fire('click');
if (panel1.hidden || panel1.classList.contains('is-revealing')) throw Error('fallback without requestAnimationFrame must safely reveal panel and remove start state');
close.fire('click');
search.value = 'SUB CHECKLIST'; search.fire('input');
if (gobi1.hidden || !gobi2.hidden || results.textContent !== 'Tidak ada dokumen yang cocok.' || !tree.classList.contains('ampuh-directory__empty')) throw Error('sub-checklist title search must reveal its branch but announce zero documents');
search.value = 'KEPUTUSAN'; search.fire('input');
if (gobi1.hidden || !gobi2.hidden || !results.textContent.includes('1') || toggle1.getAttribute('aria-expanded') !== 'true' || toggle2.getAttribute('aria-expanded') !== 'true' || panel2.hidden || heading.textContent !== 'Daftar dokumen (1)' || file.hidden) throw Error('file search must count result, open ancestors, and display documents directly in sub-checklist panel');
one.fire('click');
if (gobi1.hidden || !gobi2.hidden) throw Error('GOBI filter must intersect active search');
search.value = ''; search.fire('input');
if (gobi1.hidden || !gobi2.hidden || tree.classList.contains('ampuh-directory__empty')) throw Error('clearing search must preserve active GOBI filter and clear empty state');
if (one.getAttribute('aria-pressed') !== 'true') throw Error('clearing search must preserve active GOBI button state');
if (select.value !== '1') throw Error('desktop button must synchronize mobile select');
select.value = '2'; select.fire('change');
if (!gobi1.hidden || gobi2.hidden || two.getAttribute('aria-pressed') !== 'true' || one.getAttribute('aria-pressed') !== 'false') throw Error('mobile select must synchronize visibility and desktop buttons');
select.value = ''; select.fire('change');
if (gobi1.hidden || gobi2.hidden || one.getAttribute('aria-pressed') !== 'false' || two.getAttribute('aria-pressed') !== 'false') throw Error('all-GOBI option must clear unified filter');
if (toggle1.getAttribute('aria-expanded') !== 'false' || toggle2.getAttribute('aria-expanded') !== 'false') throw Error('clearing search must reset disclosures to closed');
two.fire('click');
if (!gobi1.hidden || gobi2.hidden) throw Error('GOBI filter must select requested GOBI');
two.fire('click');
if (gobi1.hidden || gobi2.hidden) throw Error('second filter click must clear filter');
search.value = 'tidak-ditemukan'; search.fire('input');
if (!gobi1.hidden || !gobi2.hidden || results.textContent !== 'Tidak ada dokumen yang cocok.' || !tree.classList.contains('ampuh-directory__empty')) throw Error('no match must show empty state and live message');
search.value = ''; search.fire('input');
if (tree.classList.contains('ampuh-directory__empty')) throw Error('clearing no-match query must remove empty state');
context.document = { querySelector: () => null };
context.setupAmpuhDirectory();
'''
with tempfile.NamedTemporaryFile("w", suffix=".js", encoding="utf-8", delete=False) as runner:
    runner.write(NODE_TEST)
    runner_path = runner.name
assert "data-ampuh-file-result" in function_source
try:
    completed = subprocess.run(["node", runner_path, str(SOURCE)], text=True, capture_output=True)
    assert completed.returncode == 0, completed.stderr
finally:
    Path(runner_path).unlink(missing_ok=True)
print("AMPUH directory interaction contract: ok")
