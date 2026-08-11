#!/usr/bin/env python3
"""Prepare/apply Cloudflare edge-cache rules and process targeted purge queues."""
import argparse
import json
import os
import sys
import time
import urllib.error
import urllib.parse
import urllib.request
from pathlib import Path

API = "https://api.cloudflare.com/client/v4"
PUBLIC_PREFIXES = ("/berita", "/pengumuman", "/profil-pengadilan", "/layanan-publik", "/layanan-hukum", "/informasi-perkara", "/zona-integritas", "/transparansi", "/ampuh")
BYPASS_PREFIXES = ("/administrator", "/api")
BYPASS_OPTIONS = ("com_users", "com_ajax", "com_contact", "com_privacy")


def request_json(method, endpoint, token, payload=None):
    body = json.dumps(payload).encode("utf-8") if payload is not None else None
    req = urllib.request.Request(API + endpoint, data=body, method=method, headers={"Authorization": "Bearer " + token, "Content-Type": "application/json"})
    with urllib.request.urlopen(req, timeout=45) as response:
        data = json.load(response)
    if not data.get("success"):
        raise RuntimeError("Cloudflare API menolak request")
    return data


def cache_rules(hostname):
    public_paths = " or ".join(['http.request.uri.path eq "/"'] + ['starts_with(http.request.uri.path, "{}")'.format(path) for path in PUBLIC_PREFIXES])
    bypass_paths = " or ".join(['starts_with(http.request.uri.path, "{}")'.format(path) for path in BYPASS_PREFIXES])
    bypass_options = " or ".join(['http.request.uri.query contains "option={}"'.format(option) for option in BYPASS_OPTIONS])
    cookie_bypass = 'http.cookie ne ""'
    common = 'http.host eq "{}"'.format(hostname)
    bypass = "({}) and (not http.request.method in {{\"GET\" \"HEAD\"}} or {} or {} or {} or any(http.request.headers[\"authorization\"][*] ne \"\"))".format(common, bypass_paths, bypass_options, cookie_bypass)
    eligible = "({}) and http.request.method in {{\"GET\" \"HEAD\"}} and ({})".format(common, public_paths)
    return [
        {"ref": "pn_natuna_joomla_bypass", "description": "PN Natuna bypass authenticated and dynamic Joomla traffic", "expression": bypass, "action": "set_cache_settings", "action_parameters": {"cache": False}},
        {"ref": "pn_natuna_public_html", "description": "PN Natuna cache public HTML at edge", "expression": eligible, "action": "set_cache_settings", "action_parameters": {"cache": True, "edge_ttl": {"mode": "override_origin", "default": 7200, "status_code_ttl": [{"status_code": 200, "value": 7200}, {"status_code_range": {"from": 201, "to": 499}, "value": 0}, {"status_code_range": {"from": 500}, "value": -1}]}, "browser_ttl": {"mode": "respect_origin"}, "serve_stale": {"disable_stale_while_updating": False}, "cache_key": {"cache_deception_armor": True}}},
        {"ref": "pn_natuna_home_html", "description": "PN Natuna shorter homepage edge TTL", "expression": '({}) and http.request.method in {{\"GET\" \"HEAD\"}} and http.request.uri.path eq "/"'.format(common), "action": "set_cache_settings", "action_parameters": {"cache": True, "edge_ttl": {"mode": "override_origin", "default": 900, "status_code_ttl": [{"status_code": 200, "value": 900}, {"status_code_range": {"from": 201}, "value": 0}]}, "browser_ttl": {"mode": "respect_origin"}}},
    ]


def validate_url(url, base):
    target, expected = urllib.parse.urlsplit(url), urllib.parse.urlsplit(base)
    if target.scheme != "https" or target.hostname != expected.hostname or target.username or target.password or target.fragment:
        raise ValueError("URL purge di luar origin")
    return urllib.parse.urlunsplit(("https", expected.netloc, target.path or "/", target.query, ""))


def command_prepare(args):
    payload = {"name": "PN Natuna public edge cache", "kind": "zone", "phase": "http_request_cache_settings", "rules": cache_rules(args.hostname)}
    text = json.dumps(payload, indent=2, ensure_ascii=False) + "\n"
    if args.output:
        Path(args.output).write_text(text, encoding="utf-8")
    else:
        print(text, end="")


def credentials():
    token, zone = os.environ.get("CLOUDFLARE_API_TOKEN", ""), os.environ.get("CLOUDFLARE_ZONE_ID", "")
    if not token or not zone:
        raise RuntimeError("CLOUDFLARE_API_TOKEN dan CLOUDFLARE_ZONE_ID wajib di private environment")
    return token, zone


def command_apply(args):
    token, zone = credentials()
    current = request_json("GET", "/zones/{}/rulesets/phases/http_request_cache_settings/entrypoint".format(zone), token)
    backup = Path(args.backup)
    backup.parent.mkdir(parents=True, exist_ok=True)
    backup.write_text(json.dumps(current.get("result"), indent=2) + "\n", encoding="utf-8")
    result = current.get("result") or {}
    existing = [rule for rule in result.get("rules", []) if not str(rule.get("ref", "")).startswith("pn_natuna_")]
    payload = {"name": result.get("name") or "zone", "kind": "zone", "phase": "http_request_cache_settings", "rules": existing + cache_rules(args.hostname)}
    request_json("PUT", "/zones/{}/rulesets/phases/http_request_cache_settings/entrypoint".format(zone), token, payload)
    print("Cloudflare edge cache rules applied; rollback: {}".format(backup))

def command_rollback(args):
    token, zone = credentials()
    saved = json.loads(Path(args.backup).read_text(encoding="utf-8"))
    if not isinstance(saved, dict) or saved.get("phase") != "http_request_cache_settings" or not isinstance(saved.get("rules"), list):
        raise ValueError("Backup ruleset tidak valid")
    payload = {key: saved[key] for key in ("name", "kind", "phase", "rules") if key in saved}
    payload["kind"] = "zone"
    payload["phase"] = "http_request_cache_settings"
    request_json("PUT", "/zones/{}/rulesets/phases/http_request_cache_settings/entrypoint".format(zone), token, payload)
    print("Cloudflare edge cache rules rolled back from {}".format(args.backup))



def command_purge(args):
    token, zone = credentials()
    base = "https://" + args.hostname
    queue = Path(args.queue)
    if not queue.exists() or queue.stat().st_size == 0:
        print("Cloudflare purge queue empty")
        return
    claimed = queue.with_name(queue.name + ".processing.{}".format(os.getpid()))
    queue.replace(claimed)
    try:
        urls = []
        for line in claimed.read_text(encoding="utf-8").splitlines():
            record = json.loads(line)
            for url in record.get("urls", []):
                urls.append(validate_url(url, base))
        urls = list(dict.fromkeys(urls))
        for offset in range(0, len(urls), 30):
            request_json("POST", "/zones/{}/purge_cache".format(zone), token, {"files": urls[offset:offset + 30]})
        claimed.unlink()
        print("Cloudflare purged {} URL".format(len(urls)))
    except Exception:
        with queue.open("a", encoding="utf-8") as destination:
            destination.write(claimed.read_text(encoding="utf-8"))
        claimed.unlink(missing_ok=True)
        raise


def main():
    parser = argparse.ArgumentParser()
    sub = parser.add_subparsers(dest="command", required=True)
    prepare = sub.add_parser("prepare"); prepare.add_argument("--hostname", default="pn-natuna.go.id"); prepare.add_argument("--output"); prepare.set_defaults(func=command_prepare)
    apply = sub.add_parser("apply"); apply.add_argument("--hostname", default="pn-natuna.go.id"); apply.add_argument("--backup", required=True); apply.set_defaults(func=command_apply)
    purge = sub.add_parser("purge"); purge.add_argument("--hostname", default="pn-natuna.go.id"); purge.add_argument("--queue", required=True); purge.set_defaults(func=command_purge)
    rollback = sub.add_parser("rollback"); rollback.add_argument("--backup", required=True); rollback.set_defaults(func=command_rollback)
    args = parser.parse_args()
    try:
        args.func(args)
    except (RuntimeError, ValueError, OSError, urllib.error.URLError, json.JSONDecodeError) as error:
        print("Cloudflare edge operation failed: {}".format(error), file=sys.stderr)
        return 1
    return 0


if __name__ == "__main__":
    sys.exit(main())
