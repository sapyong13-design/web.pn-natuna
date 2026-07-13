#!/usr/bin/env python3
import importlib.util
from pathlib import Path
import tempfile
import unittest

MODULE_PATH = Path(__file__).with_name("apply-db-migrations.py")
SPEC = importlib.util.spec_from_file_location("apply_db_migrations", MODULE_PATH)
MIGRATIONS = importlib.util.module_from_spec(SPEC)
SPEC.loader.exec_module(MIGRATIONS)


class MigrationRunnerTests(unittest.TestCase):
    def test_discovers_sql_files_in_name_order(self):
        with tempfile.TemporaryDirectory() as directory:
            root = Path(directory)
            (root / "20260714_second.sql").write_text("SELECT 2;", encoding="utf-8")
            (root / "20260713_first.sql").write_text("SELECT 1;", encoding="utf-8")
            (root / "notes.txt").write_text("ignore", encoding="utf-8")
            self.assertEqual(
                [path.name for path in MIGRATIONS.discover_migrations(root)],
                ["20260713_first.sql", "20260714_second.sql"],
            )

    def test_renders_joomla_prefix_placeholder(self):
        self.assertEqual(
            MIGRATIONS.render_migration("UPDATE #__modules SET published=1;", "pnn_"),
            "UPDATE pnn_modules SET published=1;",
        )

    def test_rejects_unsafe_identifiers(self):
        with self.assertRaises(ValueError):
            MIGRATIONS.validate_identifier("site; DROP DATABASE site")

    def test_checksum_is_stable_and_content_sensitive(self):
        first = MIGRATIONS.migration_checksum("SELECT 1;\n")
        self.assertEqual(first, MIGRATIONS.migration_checksum("SELECT 1;\n"))
        self.assertNotEqual(first, MIGRATIONS.migration_checksum("SELECT 2;\n"))


if __name__ == "__main__":
    unittest.main()
