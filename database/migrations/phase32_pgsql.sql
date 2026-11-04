-- Postgres only: rows seeded with explicit ids do not advance identity sequences, so the next insert would reuse those ids.
SELECT setval(pg_get_serial_sequence('campuses', 'id'), COALESCE((SELECT MAX(id) FROM campuses), 1));
SELECT setval(pg_get_serial_sequence('clusters', 'id'), COALESCE((SELECT MAX(id) FROM clusters), 1));
SELECT setval(pg_get_serial_sequence('colleges', 'id'), COALESCE((SELECT MAX(id) FROM colleges), 1));
SELECT setval(pg_get_serial_sequence('permissions', 'id'), COALESCE((SELECT MAX(id) FROM permissions), 1));
SELECT setval(pg_get_serial_sequence('roles', 'id'), COALESCE((SELECT MAX(id) FROM roles), 1));
SELECT setval(pg_get_serial_sequence('users', 'id'), COALESCE((SELECT MAX(id) FROM users), 1));
