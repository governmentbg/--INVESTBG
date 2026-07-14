
CREATE TABLE IF NOT EXISTS notifications (
  notification integer PRIMARY KEY,
  thread integer  DEFAULT NULL,
  sender integer  DEFAULT NULL,
  title varchar(250) NOT NULL DEFAULT '',
  body varchar(2000) NOT NULL DEFAULT '',
  link varchar(500) NOT NULL DEFAULT '',
  sent timestamp NOT NULL,
  reply smallint  NOT NULL DEFAULT '0',
  FOREIGN KEY (sender) REFERENCES users (usr),
  FOREIGN KEY (thread) REFERENCES notifications (notification)
);

CREATE TABLE IF NOT EXISTS notification_files (
  notification integer NOT NULL,
  upload integer NOT NULL,
  pos integer  NOT NULL,
  PRIMARY KEY (notification, upload),
  FOREIGN KEY (notification) REFERENCES notifications (notification),
  FOREIGN KEY (upload) REFERENCES uploads (id)
);

CREATE TABLE IF NOT EXISTS notification_recipients (
  notification integer  NOT NULL,
  recipient integer  NOT NULL,
  opened timestamp DEFAULT NULL,
  mailed timestamp DEFAULT NULL,
  PRIMARY KEY (notification, recipient),
  FOREIGN KEY (recipient) REFERENCES users (usr),
  FOREIGN KEY (notification) REFERENCES notifications (notification)
);
