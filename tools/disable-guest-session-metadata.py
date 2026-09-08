#!/usr/bin/env python3
"""Skip guest online-presence metadata, retaining session storage and login metadata.

Uses Joomla's session_metadata_for_guest switch. Guest online counts are no
longer maintained; do not use with a published mod_whosonline module.
Run with --config configuration.php --backup /private/config-before-guest.php.
Configuration and backups must never be committed or stored in the webroot.
"""
import argparse
import importlib.util
from pathlib import Path
import re


def transform(text):
    pattern = r'(public\s+\$session_metadata_for_guest\s*=\s*)(true|false)(\s*;)'
    matches = list(re.finditer(pattern, text))
    if len(matches) > 1:
        raise ValueError('Duplicate guest metadata setting')
    if matches:
        return re.sub(pattern, r'\g<1>false\3', text)
    if '$session_metadata_for_guest' in text:
        raise ValueError('Unsupported guest metadata setting')
    anchor = r'(public\s+\$session_metadata\s*=\s*(?:true|false)\s*;)'
    newline = '\r\n' if '\r\n' in text else '\n'
    result, count = re.subn(anchor, lambda m: m[0] + newline + '\tpublic $session_metadata_for_guest = false;', text)
    if count != 1:
        raise ValueError('Expected one session_metadata declaration')
    return result


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--config', type=Path)
    parser.add_argument('--backup', type=Path)
    parser.add_argument('--self-test', action='store_true')
    args = parser.parse_args()
    if args.self_test:
        for enabled in ('true', 'false'):
            source = '<?php class JConfig { public $session_metadata = ' + enabled + '; public $session_handler = \'database\'; }'
            changed = transform(source)
            assert transform(changed) == changed
            assert 'public $session_metadata = ' + enabled + ';' in changed
            assert "public $session_handler = 'database';" in changed
            assert '$session_metadata_for_guest = false;' in changed
        assert transform('<?php public $session_metadata_for_guest = true;') == '<?php public $session_metadata_for_guest = false;'
        try:
            transform('<?php public $session_metadata_for_guest = 2;')
        except ValueError:
            pass
        else:
            raise AssertionError('Unsupported config must not be overwritten')
        print('guest metadata migration self-test passed')
    elif args.config and args.backup:
        spec = importlib.util.spec_from_file_location('cache_migration', Path(__file__).with_name('enable-conservative-cache.py'))
        migration = importlib.util.module_from_spec(spec)
        spec.loader.exec_module(migration)
        migration.transform = transform
        migration.apply(args.config, args.backup)
        print('Guest metadata disabled; session storage and other metadata settings preserved')
    else:
        parser.error('Provide --config and --backup, or --self-test')
