#!/usr/bin/env python3
import importlib.util
from pathlib import Path
import unittest

MODULE_PATH = Path(__file__).with_name("export-sanitized-db.py")
SPEC = importlib.util.spec_from_file_location("export_sanitized_db", MODULE_PATH)
EXPORT = importlib.util.module_from_spec(SPEC)
SPEC.loader.exec_module(EXPORT)


class SanitizedExportTests(unittest.TestCase):
    def test_commands_exclude_sensitive_table_rows(self):
        schema, data = EXPORT.build_commands("mysqldump", "db.example", 3306, "deploy", "site", "abc_")
        self.assertIn("--no-data", schema)
        self.assertIn("--no-create-info", data)
        for table in EXPORT.SENSITIVE_TABLES:
            self.assertIn(f"--ignore-table=site.abc_{table}", data)
        self.assertNotIn("--password", " ".join(schema + data))

    def test_rejects_unsafe_identifiers(self):
        with self.assertRaises(ValueError):
            EXPORT.build_commands("mysqldump", "localhost", 3306, "deploy", "site;drop", "abc_")

    def test_output_validator_rejects_sensitive_insert(self):
        with self.assertRaises(RuntimeError):
            EXPORT.validate_dump("INSERT INTO `abc_session` VALUES (1);", "abc_")
        EXPORT.validate_dump("CREATE TABLE `abc_session` (`session_id` varchar(200));", "abc_")


if __name__ == "__main__":
    unittest.main()
