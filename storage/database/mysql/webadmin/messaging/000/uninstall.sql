DROP TABLE IF EXISTS notification_files CASCADE;
DROP TABLE IF EXISTS notification_recipients CASCADE;
DROP TABLE IF EXISTS notifications CASCADE;

DELETE FROM modules WHERE name = 'notifications';
