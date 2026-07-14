#!/bin/bash
export NLS_LANG=.AL32UTF8

for i in $(find /dataset/base/ -name schema.sql); do
    sqlplus -s webadmin/webadmin@//localhost/FREEPDB1 @"$i"
done

for i in $(find /dataset/webadmin/ -name schema.sql); do
    sqlplus -s webadmin/webadmin@//localhost/FREEPDB1 @"$i"
done

for i in $(find /dataset/webadmin/ -name data.sql); do
    sqlplus -s webadmin/webadmin@//localhost/FREEPDB1 @"$i"
done
