#!/usr/bin/env python3
"""Restrict index.php homepage redirects to direct, query-free requests.

Preserves legacy mappings, PHP handlers and environment-specific rules.
Backup must be outside the public webroot. Does not alter article query URLs.
"""
import argparse
import importlib.util
from pathlib import Path
import re


def transform(text):
    if '\r\n' in text:
        return transform(text.replace('\r\n', '\n')).replace('\n', '\r\n')
    rule = r'(?m)^([ \t]*)RewriteRule \^index\\\.php/\?\$ / \[R=301,L,NE\]$'
    matches = list(re.finditer(rule, text))
    if len(matches) != 1:
        raise ValueError('Expected exactly one index.php homepage redirect')
    match = matches[0]
    before = text[:match.start()]
    indent = match[1]
    guards = (indent + r'RewriteCond %{THE_REQUEST} \s/+index\.php/?\s [NC]' + '\n'
              + indent + r'RewriteCond %{QUERY_STRING} ^$' + '\n')
    if before.endswith(guards):
        return text
    # Only replace a condition immediately attached to this rule.
    before = re.sub(r'(?m)^[ \t]*RewriteCond %\{THE_REQUEST\} [^\n]*\n\Z', '', before)
    return before + guards + text[match.start():]

if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--config', type=Path)
    parser.add_argument('--backup', type=Path)
    parser.add_argument('--self-test', action='store_true')
    args = parser.parse_args()
    if args.self_test:
        original = 'RewriteRule ^index\\.php/?$ / [R=301,L,NE]\n'
        updated = transform(original)
        assert transform(updated) == updated
        assert updated.endswith(original)
        direct = re.compile(r'\s/+index\.php/?\s', re.I)
        assert direct.search('GET /index.php HTTP/1.1')
        assert not direct.search('GET /index.php?option=com_content HTTP/1.1')
        assert not direct.search('GET /profil-pengadilan HTTP/1.1')
        print('index redirect checks passed')
    elif args.config and args.backup:
        spec = importlib.util.spec_from_file_location('migration', Path(__file__).with_name('enable-conservative-cache.py'))
        migration = importlib.util.module_from_spec(spec)
        spec.loader.exec_module(migration)
        migration.transform = transform
        migration.apply(args.config, args.backup)
    else:
        parser.error('Provide --config and --backup, or --self-test')
