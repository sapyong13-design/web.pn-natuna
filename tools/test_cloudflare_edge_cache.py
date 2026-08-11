#!/usr/bin/env python3
import importlib.util
import json
import os
import subprocess
import sys
import tempfile
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
CLI = ROOT / "tools/cloudflare-edge-cache.py"

spec = importlib.util.spec_from_file_location("cloudflare_edge", CLI)
module = importlib.util.module_from_spec(spec)
spec.loader.exec_module(module)

rules = module.cache_rules("pn-natuna.go.id")
assert [rule["ref"] for rule in rules] == ["pn_natuna_joomla_bypass", "pn_natuna_public_html", "pn_natuna_home_html"]
assert rules[0]["action_parameters"]["cache"] is False
assert "/administrator" in rules[0]["expression"] and 'http.cookie ne ""' in rules[0]["expression"]
assert "authorization" in rules[0]["expression"] and "com_users" in rules[0]["expression"]
assert rules[1]["action_parameters"]["edge_ttl"]["default"] == 7200
assert rules[2]["action_parameters"]["edge_ttl"]["default"] == 900
assert module.validate_url("https://pn-natuna.go.id/berita/a", "https://pn-natuna.go.id") == "https://pn-natuna.go.id/berita/a"
for unsafe in ("http://pn-natuna.go.id/", "https://example.com/", "https://user@pn-natuna.go.id/"):
    try:
        module.validate_url(unsafe, "https://pn-natuna.go.id")
    except ValueError:
        pass
    else:
        raise AssertionError("unsafe purge URL accepted: " + unsafe)

with tempfile.TemporaryDirectory() as directory:
    output = Path(directory) / "rules.json"
    result = subprocess.run([sys.executable, str(CLI), "prepare", "--output", str(output)], text=True, capture_output=True)
    assert result.returncode == 0, result.stderr
    payload = json.loads(output.read_text(encoding="utf-8"))
    assert payload["phase"] == "http_request_cache_settings" and len(payload["rules"]) == 3
    env = dict(os.environ); env.pop("CLOUDFLARE_API_TOKEN", None); env.pop("CLOUDFLARE_ZONE_ID", None)
    blocked = subprocess.run([sys.executable, str(CLI), "apply", "--backup", str(Path(directory) / "backup.json")], env=env, text=True, capture_output=True)
    assert blocked.returncode == 1 and "private environment" in blocked.stderr


calls = []
existing = {"name": "existing", "kind": "zone", "phase": "http_request_cache_settings", "rules": [{"ref": "keep_me", "action": "set_cache_settings", "expression": "true", "action_parameters": {"cache": False}}]}
module.credentials = lambda: ("private-token", "zone-id")
module.request_json = lambda method, endpoint, token, payload=None: calls.append((method, endpoint, payload)) or ({"success": True, "result": existing} if method == "GET" else {"success": True, "result": {}})
with tempfile.TemporaryDirectory() as directory:
    backup = Path(directory) / "rules-backup.json"
    module.command_apply(type("Args", (), {"backup": str(backup), "hostname": "pn-natuna.go.id"})())
    saved = json.loads(backup.read_text(encoding="utf-8"))
    assert saved == existing
    applied = calls[-1][2]
    assert applied["rules"][0]["ref"] == "keep_me" and len(applied["rules"]) == 4
    calls.clear()
    module.command_rollback(type("Args", (), {"backup": str(backup)})())
    assert calls[-1][2]["rules"] == existing["rules"]

calls.clear()
with tempfile.TemporaryDirectory() as directory:
    queue = Path(directory) / "purge.jsonl"
    queue.write_text(json.dumps({"urls": ["https://pn-natuna.go.id/", "https://pn-natuna.go.id/berita/a", "https://pn-natuna.go.id/berita/a"]}) + "\n", encoding="utf-8")
    module.command_purge(type("Args", (), {"queue": str(queue), "hostname": "pn-natuna.go.id"})())
    assert not queue.exists()
    assert calls[-1][0] == "POST" and calls[-1][2] == {"files": ["https://pn-natuna.go.id/", "https://pn-natuna.go.id/berita/a"]}
print("Cloudflare edge cache tooling contract: ok")
