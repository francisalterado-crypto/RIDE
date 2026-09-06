-- Remove the student account module

DELETE FROM user_roles
WHERE role_id IN (SELECT id FROM roles WHERE slug = 'student');

DELETE FROM users
WHERE email IN (
    'student.cet@ride.local',
    'student.cas@ride.local',
    'student.cbm@ride.local'
);

DELETE FROM roles WHERE slug = 'student';

ALTER TABLE users
    DROP COLUMN IF EXISTS requested_role;
