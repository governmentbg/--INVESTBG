#!/bin/bash
set -e

for i in $(find /dataset/base/ -name schema.sql); do
    mysql -uroot -p"$MYSQL_ROOT_PASSWORD" webadmin < "$i"
done

for i in $(find /dataset/webadmin/ -name schema.sql); do
    mysql -uroot -p"$MYSQL_ROOT_PASSWORD" webadmin < "$i"
done

for i in $(find /dataset/webadmin/ -name data.sql); do
    mysql -uroot -p"$MYSQL_ROOT_PASSWORD" webadmin < "$i"
done
