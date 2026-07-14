#!/bin/bash
set -e

psql --username "$POSTGRES_USER" --dbname "$POSTGRES_DB" -a -f "/usr/share/postgresql/16/tsearch_data/bulgarian.sql"

for i in $(find /dataset/base/ -name schema.sql); do
    psql --username "$POSTGRES_USER" --dbname "$POSTGRES_DB" -a -f "$i"
done

for i in $(find /dataset/webadmin/ -name schema.sql); do
    psql --username "$POSTGRES_USER" --dbname "$POSTGRES_DB" -a -f "$i"
done

for i in $(find /dataset/webadmin/ -name data.sql); do
    psql --username "$POSTGRES_USER" --dbname "$POSTGRES_DB" -a -f "$i"
done
