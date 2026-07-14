CREATE TABLE IF NOT EXISTS collections (
  collection SERIAL NOT NULL,
  name varchar(255) NOT NULL,
  owner int NOT NULL,
  rw smallint NOT NULL DEFAULT '0',
  PRIMARY KEY (collection),
  CONSTRAINT FK_COLLECTION_USER FOREIGN KEY (owner) REFERENCES users (usr)
);

CREATE TABLE IF NOT EXISTS collection_groups (
  collection int NOT NULL,
  grp int NOT NULL,
  rw smallint NOT NULL DEFAULT '0',
  PRIMARY KEY (collection,grp),
  CONSTRAINT FK_COLLGROUPS_GRP FOREIGN KEY (grp) REFERENCES grps (grp),
  CONSTRAINT FK_COLLGROUPS_COLL FOREIGN KEY (collection) REFERENCES collections (collection)
);

CREATE TABLE IF NOT EXISTS upload_collections (
  upload int NOT NULL,
  collection int NOT NULL,
  PRIMARY KEY (upload,collection),
  CONSTRAINT FK_UPLOADCOLL_COLL FOREIGN KEY (collection) REFERENCES collections (collection),
  CONSTRAINT FK_UPLOADCOLL_FILE FOREIGN KEY (upload) REFERENCES uploads (id)
);

CREATE TABLE IF NOT EXISTS upload_user (
  upload int NOT NULL,
  usr int NOT NULL,
  PRIMARY KEY (upload,usr),
  CONSTRAINT FK_UPLOADUSER_USR FOREIGN KEY (usr) REFERENCES users (usr),
  CONSTRAINT FK_UPLOADUSER_UPL FOREIGN KEY (upload) REFERENCES uploads (id)
);
