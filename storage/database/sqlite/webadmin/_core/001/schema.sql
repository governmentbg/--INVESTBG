CREATE TABLE IF NOT EXISTS collections (
  collection integer PRIMARY KEY,
  name varchar(255) NOT NULL,
  owner integer NOT NULL,
  rw smallint NOT NULL DEFAULT '0',
  FOREIGN KEY (owner) REFERENCES users (usr)
);

CREATE TABLE IF NOT EXISTS collection_groups (
  collection integer NOT NULL,
  grp integer  NOT NULL,
  rw smallint NOT NULL DEFAULT '0',
  PRIMARY KEY (collection, grp),
  FOREIGN KEY (grp) REFERENCES grps (grp),
  FOREIGN KEY (collection) REFERENCES collections (collection)
);

CREATE TABLE IF NOT EXISTS upload_collections (
  upload integer  NOT NULL,
  collection integer  NOT NULL,
  PRIMARY KEY (upload, collection),
  FOREIGN KEY (collection) REFERENCES collections (collection),
  FOREIGN KEY (upload) REFERENCES uploads (id)
);

CREATE TABLE IF NOT EXISTS upload_user (
  upload integer  NOT NULL,
  usr integer  NOT NULL,
  PRIMARY KEY (upload, usr),
  FOREIGN KEY (usr) REFERENCES users (usr),
  FOREIGN KEY (upload) REFERENCES uploads (id)
);
