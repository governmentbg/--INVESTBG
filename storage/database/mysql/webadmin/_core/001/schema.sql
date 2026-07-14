CREATE TABLE IF NOT EXISTS collections (
  collection int(10) unsigned NOT NULL AUTO_INCREMENT,
  name varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  owner int(10) unsigned NOT NULL,
  rw tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (collection),
  KEY FK_COLLECTION_USER (owner),
  CONSTRAINT FK_COLLECTION_USER FOREIGN KEY (owner) REFERENCES users (usr)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS collection_groups (
  collection int(10) unsigned NOT NULL,
  grp int(10) unsigned NOT NULL,
  rw tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (collection,grp),
  KEY FK_COLLGROUPS_GRP (grp),
  CONSTRAINT FK_COLLGROUPS_GRP FOREIGN KEY (grp) REFERENCES grps (grp),
  CONSTRAINT FK_COLLGROUPS_COLL FOREIGN KEY (collection) REFERENCES collections (collection)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS upload_collections (
  upload bigint(20) unsigned NOT NULL,
  collection int(10) unsigned NOT NULL,
  PRIMARY KEY (upload,collection),
  KEY FK_UPLOADCOLL_COLL (collection),
  CONSTRAINT FK_UPLOADCOLL_COLL FOREIGN KEY (collection) REFERENCES collections (collection),
  CONSTRAINT FK_UPLOADCOLL_FILE FOREIGN KEY (upload) REFERENCES uploads (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS upload_user (
  upload bigint(20) unsigned NOT NULL,
  usr int(10) unsigned NOT NULL,
  PRIMARY KEY (upload,usr),
  KEY FK_UPLOADUSR_USR (usr),
  CONSTRAINT FK_UPLOADUSR_UPL FOREIGN KEY (usr) REFERENCES users (usr),
  CONSTRAINT FK_UPLOADUSR_USR FOREIGN KEY (upload) REFERENCES uploads (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
