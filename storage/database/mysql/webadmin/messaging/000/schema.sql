
CREATE TABLE IF NOT EXISTS notifications (
  notification int(10) unsigned NOT NULL AUTO_INCREMENT,
  thread int(10) unsigned DEFAULT NULL,
  sender int(10) unsigned DEFAULT NULL,
  title varchar(250) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  body varchar(2000) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  link varchar(500) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  sent datetime NOT NULL,
  reply tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (notification),
  KEY FK_NOTIFICATION_THREAD (thread),
  KEY FK_NOTIFICATION_SENDER (sender),
  CONSTRAINT FK_NOTIFICATION_SENDER FOREIGN KEY (sender) REFERENCES users (usr),
  CONSTRAINT FK_NOTIFICATION_THREAD FOREIGN KEY (thread) REFERENCES notifications (notification)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Нотификации';

CREATE TABLE IF NOT EXISTS notification_files (
  notification int(10) unsigned NOT NULL,
  upload bigint(20) unsigned NOT NULL,
  pos int(10) unsigned NOT NULL,
  PRIMARY KEY (notification,upload),
  KEY FK_NOTIFFILES_FILE (upload),
  CONSTRAINT FK_NOTIFFILES_NOTIF FOREIGN KEY (notification) REFERENCES notifications (notification),
  CONSTRAINT FK_NOTIFFILES_FILE FOREIGN KEY (upload) REFERENCES uploads (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ;

CREATE TABLE IF NOT EXISTS notification_recipients (
  notification int(10) unsigned NOT NULL,
  recipient int(10) unsigned NOT NULL,
  opened datetime DEFAULT NULL,
  mailed datetime DEFAULT NULL,
  PRIMARY KEY (notification,recipient),
  KEY FK_NOTIFICATION_RECIPIENT (recipient),
  CONSTRAINT FK_NOTIFICATION_RECIPIENT FOREIGN KEY (recipient) REFERENCES users (usr),
  CONSTRAINT FK_RECIPIENT_NOTIFICATION FOREIGN KEY (notification) REFERENCES notifications (notification)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
