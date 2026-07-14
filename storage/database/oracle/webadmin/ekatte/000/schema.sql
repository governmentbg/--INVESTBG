CREATE TABLE regions
(
    region  NUMBER(5)               NOT NULL,
    code    VARCHAR2(3)             NOT NULL,
    name    VARCHAR2(50) DEFAULT '' NOT NULL,
    name_en VARCHAR2(50) DEFAULT '' NOT NULL,
    pos     NUMBER(5)               NOT NULL,
    CONSTRAINT regions_pk PRIMARY KEY (region),
    CONSTRAINT regions_unique UNIQUE (pos)
);

CREATE TABLE municipalities
(
    municipality NUMBER(5)                NOT NULL,
    code         VARCHAR2(5)              NOT NULL,
    name         VARCHAR2(100) DEFAULT '' NOT NULL,
    name_en      VARCHAR2(100) DEFAULT '' NOT NULL,
    region       NUMBER(5)                NOT NULL,
    pos          NUMBER(5)                NOT NULL,
    CONSTRAINT municipalities_pk PRIMARY KEY (municipality),
    CONSTRAINT municipalities_regions_fk FOREIGN KEY (region) REFERENCES regions (region)
);

CREATE TABLE cities
(
    city         NUMBER(10)               NOT NULL,
    name         VARCHAR2(100) DEFAULT '' NOT NULL,
    name_en      VARCHAR2(100) DEFAULT '' NOT NULL,
    type         NUMBER(5)                NOT NULL,
    municipality NUMBER(5)                NOT NULL,
    pos          NUMBER(5)                NOT NULL,
    parent       NUMBER(10),
    CONSTRAINT cities_pk PRIMARY KEY (city),
    CONSTRAINT cities_cities_fk FOREIGN KEY (parent) REFERENCES cities (city) ON DELETE RESTRICT,
    CONSTRAINT cities_municipalities_fk FOREIGN KEY (municipality) REFERENCES municipalities (municipality)
);
