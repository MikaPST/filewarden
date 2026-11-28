"""Fail CI on any PCP finding; PCP 2.2 emits a success sentence for an empty strict JSON report."""
import json
import sys
from pathlib import Path

raw = Path(sys.argv[1]).read_text(encoding='utf-8-sig').strip()
if raw == 'Success: Checks complete. No errors found.':
    findings = []
else:
    findings = json.loads(raw)
    if not isinstance(findings, list):
        raise ValueError('Unexpected PCP strict JSON report')
if findings:
    raise SystemExit('Plugin Check findings: ' + str(len(findings)))
print('Plugin Check: zero errors and zero warnings; no ignored codes or checks.')
