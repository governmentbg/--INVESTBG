CREATE TABLE IF NOT EXISTS uploads_versions (
  id SERIAL NOT NULL,
  upload int NOT NULL ,
  version text  NOT NULL ,
  name text  NOT NULL ,
  location text  NOT NULL ,
  bytesize int NOT NULL DEFAULT '0' ,
  uploaded timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ,
  hash varchar(32) NOT NULL DEFAULT '' ,
  data bytea DEFAULT NULL ,
  settings text ,
  PRIMARY KEY (id),
  CONSTRAINT FK_UVERS_UPLOAD FOREIGN KEY (upload) REFERENCES uploads (id)
);
