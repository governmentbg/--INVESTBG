DROP TABLE upload_collections CASCADE CONSTRAINTS;
DROP TABLE collection_groups CASCADE CONSTRAINTS;
DROP TABLE collections CASCADE CONSTRAINTS;
DROP TABLE upload_user CASCADE CONSTRAINTS;

DELETE FROM group_permissions WHERE perm = 'uploads/master';
DELETE FROM permissions WHERE perm = 'uploads/master';
