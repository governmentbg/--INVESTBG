
CREATE TABLE IF NOT EXISTS notifications (
  notification SERIAL NOT NULL,
  thread int  DEFAULT NULL,
  sender int  DEFAULT NULL,
  title varchar(250) NOT NULL DEFAULT '',
  body varchar(2000) NOT NULL DEFAULT '',
  link varchar(500) NOT NULL DEFAULT '',
  sent timestamp NOT NULL,
  reply smallint  NOT NULL DEFAULT '0',
  PRIMARY KEY (notification),
  CONSTRAINT FK_NOTIFICATION_SENDER FOREIGN KEY (sender) REFERENCES users (usr),
  CONSTRAINT FK_NOTIFICATION_THREAD FOREIGN KEY (thread) REFERENCES notifications (notification)
);

CREATE TABLE IF NOT EXISTS notification_files (
  notification int NOT NULL,
  upload int NOT NULL,
  pos int  NOT NULL,
  PRIMARY KEY (notification,upload),
  CONSTRAINT FK_NOTIFFILES_NOTIF FOREIGN KEY (notification) REFERENCES notifications (notification),
  CONSTRAINT FK_NOTIFFILES_FILE FOREIGN KEY (upload) REFERENCES uploads (id)
);

CREATE TABLE IF NOT EXISTS notification_recipients (
  notification int  NOT NULL,
  recipient int  NOT NULL,
  opened timestamp DEFAULT NULL,
  mailed timestamp DEFAULT NULL,
  PRIMARY KEY (notification,recipient),
  CONSTRAINT FK_NOTIFICATION_RECIPIENT FOREIGN KEY (recipient) REFERENCES users (usr),
  CONSTRAINT FK_RECIPIENT_NOTIFICATION FOREIGN KEY (notification) REFERENCES notifications (notification)
);
