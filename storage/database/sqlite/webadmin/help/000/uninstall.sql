DROP TABLE IF EXISTS help ;

DELETE FROM group_permissions WHERE perm = 'help';
DELETE FROM permissions WHERE perm = 'help';
DELETE FROM modules WHERE name = 'help';
