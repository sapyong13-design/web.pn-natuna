START TRANSACTION;
UPDATE pnn_extensions SET enabled=0 WHERE type='plugin' AND folder='system' AND element IN ('accessibility', 'djaccessibility');
COMMIT;
