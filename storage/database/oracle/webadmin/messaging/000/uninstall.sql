DROP TABLE notification_files CASCADE CONSTRAINTS;
DROP TABLE notification_recipients CASCADE CONSTRAINTS;
DROP TABLE notifications CASCADE CONSTRAINTS;

DELETE FROM modules WHERE name = 'notifications';
