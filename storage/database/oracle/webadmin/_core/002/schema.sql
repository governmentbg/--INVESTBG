CREATE TABLE translations (
  locale varchar2(2) not null,
  k varchar2(255) not null,
  v varchar2(2000),
  CONSTRAINT PK_TRANSLATIONS PRIMARY KEY (locale, k)
);
