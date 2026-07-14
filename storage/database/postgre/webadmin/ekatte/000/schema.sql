CREATE TABLE IF NOT EXISTS regions
(
    region  int2        NOT NULL,
    code    varchar(3)  NOT NULL,
    "name"  varchar(50) NOT NULL DEFAULT '',
    name_en varchar(50) NOT NULL DEFAULT '',
    pos     int2        NOT NULL,
    CONSTRAINT regions_pk PRIMARY KEY (region),
    CONSTRAINT regions_unique UNIQUE (pos)
);

CREATE TABLE IF NOT EXISTS municipalities
(
    municipality int2         NOT NULL,
    code         varchar(5)   NOT NULL,
    "name"       varchar(100) NOT NULL DEFAULT '',
    name_en      varchar(100) NOT NULL DEFAULT '',
    region       int2         NOT NULL,
    pos          int2         NOT NULL,
    CONSTRAINT municipalities_pk PRIMARY KEY (municipality),
    CONSTRAINT municipalities_regions_fk FOREIGN KEY (region) REFERENCES regions (region)
);

CREATE TABLE IF NOT EXISTS cities
(
    city         int4                    NOT NULL,
    "name"       varchar(100) DEFAULT '' NOT NULL,
    name_en      varchar(100) DEFAULT '' NOT NULL,
    "type"       int2                    NOT NULL,
    municipality int2                    NOT NULL,
    pos          int2                    NOT NULL,
    parent       int4                    NULL,
    CONSTRAINT cities_pk PRIMARY KEY (city),
    CONSTRAINT cities_cities_fk FOREIGN KEY (parent) REFERENCES cities (city) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT cities_municipalities_fk FOREIGN KEY (municipality) REFERENCES municipalities (municipality)
);
