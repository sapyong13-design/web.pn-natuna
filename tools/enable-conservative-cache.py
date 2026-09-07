#!/usr/bin/env python3
"""Enable Joomla conservative caching without changing page-cache/session guards."""
import argparse
import os
from pathlib import Path
import re
import tempfile


def transform(text):
    pattern = r'(public\s+\$caching\s*=\s*)0(\s*;)'
    if re.search(r'public\s+\$caching\s*=\s*1\s*;', text):
        return text
    result, count = re.subn(pattern, r'\g<1>1\2', text)
    if count != 1:
        raise ValueError('Expected exactly one disabled Joomla caching setting')
    return result


def apply(config, backup):
    original = config.read_bytes()
    changed = transform(original.decode('utf-8')).encode('utf-8')
    if changed == original:
        print('Conservative cache already enabled')
        return
    with backup.open('xb') as handle:
        os.chmod(backup, 0o600)
        handle.write(original)
    fd, temporary = tempfile.mkstemp(prefix='.cache-config-', dir=str(config.parent))
    try:
        with os.fdopen(fd, 'wb') as handle:
            handle.write(changed)
            handle.flush()
            os.fsync(handle.fileno())
        os.chmod(temporary, config.stat().st_mode & 0o777)
        os.replace(temporary, config)
    finally:
        if os.path.exists(temporary):
            os.unlink(temporary)
    print('Conservative cache enabled; backup:', backup)


def self_test():
    original = "<?php\r\nclass JConfig { public $caching = 0; public $cachetime = 15; }\r\n"
    with tempfile.TemporaryDirectory() as directory:
        config = Path(directory) / 'configuration.php'
        backup = Path(directory) / 'backup.php'
        config.write_bytes(original.encode())
        apply(config, backup)
        assert backup.read_bytes() == original.encode()
        assert config.read_bytes() == original.replace('$caching = 0', '$caching = 1').encode()
        apply(config, backup)
        assert backup.read_bytes() == original.encode()
    try:
        transform('<?php public $caching = 2;')
    except ValueError:
        pass
    else:
        raise AssertionError('Unexpected cache mode must not be overwritten')
    print('self-test passed')


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--self-test', action='store_true')
    parser.add_argument('--config', type=Path)
    parser.add_argument('--backup', type=Path)
    args = parser.parse_args()
    if args.self_test:
        self_test()
    elif args.config and args.backup:
        apply(args.config, args.backup)
    else:
        parser.error('Provide --config and --backup, or --self-test')
