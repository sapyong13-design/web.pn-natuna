"""Run: python tools/test_survey_periods.py"""
import runpy
from pathlib import Path

find_latest = runpy.run_path(str(Path(__file__).with_name('refresh-survey.py')))['find_latest']
for kind in ('SKM', 'IPAK'):
    files = [('old', f'{kind} TW2 2026.pdf'), ('new', f'{kind} TW 3 2026.pdf'),
             ('invalid', f'{kind} TW5 2026.pdf'), ('past', f'{kind} TW4 2025.pdf')]
    assert find_latest(files, kind)['id'] == 'new'
    assert find_latest([('compact', f'{kind} TW3 2026.pdf')], kind)['tw'] == 3
assert find_latest([('other', 'LAPORAN SKM TW3 2026.pdf')], 'SKM') is None
print('Survey periods: OK')
