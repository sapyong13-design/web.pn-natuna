import json
import subprocess
import sys
import tempfile
import unittest
from pathlib import Path

SCRIPT = Path(__file__).with_name("security_cli.py")

class SecurityCliTests(unittest.TestCase):
    def run_cli(self, *args):
        return subprocess.run([sys.executable, str(SCRIPT), *map(str, args)], text=True, capture_output=True)

    def test_integrity_detects_new_webshell_without_echoing_payload(self):
        with tempfile.TemporaryDirectory() as d:
            root = Path(d); baseline = root / "baseline.json"
            (root / "index.php").write_text("<?php echo 'ok';", encoding="utf-8")
            self.assertEqual(self.run_cli("integrity", "baseline", root, "--baseline", baseline).returncode, 0)
            secret = "DO_NOT_PRINT_PAYLOAD"
            (root / "upload.php").write_text("<?php eval(base64_decode($_POST['x'])); /*"+secret+"*/", encoding="utf-8")
            result = self.run_cli("integrity", "check", root, "--baseline", baseline, "--json")
            self.assertEqual(result.returncode, 2)
            self.assertIn("suspicious_php", result.stdout)
            self.assertNotIn(secret, result.stdout)

    def test_judol_detects_db_and_sitemap_terms(self):
        with tempfile.TemporaryDirectory() as d:
            root = Path(d); (root/"sitemap.xml").write_text("<loc>/slot-gacor</loc>", encoding="utf-8")
            records = root/"content.json"
            records.write_text(json.dumps([{"source":"content","id":7,"title":"Casino online","body":"x"}]), encoding="utf-8")
            result = self.run_cli("judol", "--root", root, "--records", records, "--json")
            self.assertEqual(result.returncode, 2)
            self.assertIn("casino", result.stdout.lower())

    def test_accounts_reports_counts_and_names_but_not_secrets(self):
        with tempfile.TemporaryDirectory() as d:
            data = Path(d)/"accounts.json"
            secret="SECRET_HASH_TOKEN"
            data.write_text(json.dumps({"admins":[{"name":"Operator","username":"operator","groups":["Super Users"],"mfa":False,"password":secret}],"sessions":[{"user_id":1}],"tokens":[{"user_id":1,"token":secret}]}), encoding="utf-8")
            result=self.run_cli("accounts", data, "--json")
            self.assertEqual(result.returncode, 2)
            self.assertIn("Operator", result.stdout); self.assertNotIn(secret, result.stdout)

    def test_preflight_fails_unsafe_inputs(self):
        with tempfile.TemporaryDirectory() as d:
            p=Path(d)/"preflight.json"
            p.write_text(json.dumps({"https":False,"admin_outer_gate":False,"mfa_admins":False,"origin_restricted":False,"security_headers":[],"config_outside_webroot":False,"archive_outside_webroot":False,"permissions":{"files":"0777","dirs":"0777"}}), encoding="utf-8")
            result=self.run_cli("preflight", p, "--json")
            self.assertEqual(result.returncode, 2); self.assertIn("https", result.stdout)

if __name__ == "__main__": unittest.main()
