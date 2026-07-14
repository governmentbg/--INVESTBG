CREATE TABLE translations_public (
  locale varchar2(2) not null,
  k varchar2(255) not null,
  v varchar2(2000),
  CONSTRAINT PK_TRANSLATIONSP PRIMARY KEY (locale, k)
);
