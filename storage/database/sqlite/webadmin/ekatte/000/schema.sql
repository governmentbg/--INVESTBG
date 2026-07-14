CREATE TABLE IF NOT EXISTS regions
(
    region  INTEGER NOT NULL,
    code    TEXT    NOT NULL,
    name    TEXT    NOT NULL DEFAULT '',
    name_en TEXT    NOT NULL DEFAULT '',
    pos     INTEGER NOT NULL,
    PRIMARY KEY (region),
    UNIQUE (pos)
);

CREATE TABLE IF NOT EXISTS municipalities
(
    municipality INTEGER NOT NULL,
    code         TEXT    NOT NULL,
    name         TEXT    NOT NULL DEFAULT '',
    name_en      TEXT    NOT NULL DEFAULT '',
    region       INTEGER NOT NULL,
    pos          INTEGER NOT NULL,
    PRIMARY KEY (municipality),
    FOREIGN KEY (region) REFERENCES regions (region)
);

CREATE TABLE IF NOT EXISTS cities
(
    city         INTEGER NOT NULL,
    name         TEXT    NOT NULL DEFAULT '',
    name_en      TEXT    NOT NULL DEFAULT '',
    type         INTEGER NOT NULL,
    municipality INTEGER NOT NULL,
    pos          INTEGER NOT NULL,
    parent       INTEGER,
    PRIMARY KEY (city),
    FOREIGN KEY (parent) REFERENCES cities (city) ON DELETE RESTRICT ON UPDATE RESTRICT,
    FOREIGN KEY (municipality) REFERENCES municipalities (municipality)
);
