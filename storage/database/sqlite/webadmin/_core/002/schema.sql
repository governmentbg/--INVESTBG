CREATE TABLE IF NOT EXISTS translations (
  locale varchar(2) not null,
  k varchar(255) not null,
  v varchar(2000),
  PRIMARY KEY (locale, k)
);
