CREATE TABLE IF NOT EXISTS uploads_versions (
  id integer PRIMARY KEY,
  upload integer NOT NULL,
  version text  NOT NULL ,
  name text  NOT NULL ,
  location text  NOT NULL ,
  bytesize integer NOT NULL DEFAULT '0' ,
  uploaded timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ,
  hash varchar(32) NOT NULL DEFAULT '' ,
  data bytea DEFAULT NULL ,
  settings text ,
  FOREIGN KEY (upload) REFERENCES uploads (id)
);
