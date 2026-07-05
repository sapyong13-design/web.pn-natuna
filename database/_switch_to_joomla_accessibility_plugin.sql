START TRANSACTION;
UPDATE pnn_extensions SET enabled=1, params='{\"section\":\"site\",\"useEmojis\":\"false\"}' WHERE type='plugin' AND folder='system' AND element='accessibility';
UPDATE pnn_extensions SET enabled=0 WHERE type='plugin' AND folder='system' AND element='djaccessibility';
COMMIT;
