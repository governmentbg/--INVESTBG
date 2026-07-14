# Docker compose orchestration
The basic orchestration contains:
 - a web server (nginx)
 - an application server (php-fpm with all required dependencies)
 - a cache server (memcached) for session and cache storage compatible with horizontal scaling
 - an shared volume (should be NFS or similar in production) for storing:
    - the storage/tmp folder (needed for chunked uploads)
    - the storage/sendfile folder (needed for sendfile integration with nginx)

Additionally the following can be enabled:
 - a load balancer (haproxy) sitting in front of multiple nginx (or apache) instances
 - apache web server (instead of nginx)
 - a document storage server for uploaded files (minio) 
 - a database container (configs available for postgres, mysql and oracle)
 - a pgbouncer container for postgre
 - a sqlite database
 - a redis or dragonflydb (either using redis or memcached client libs) container (instead of memcache)

## Starting the system using a local (on host) database
This flow presumes a working local install of the system:
 - initialize the system normally for local usage (refer to the README.md in parent directory)
 - if needed create a composer.override.yml file to modify the DATABASE constant
 - navigate to the ./docker folder and invoke: ```docker compose up```

## Starting the system using a database container
 - run ```composer install``` in root folder
 - run ```composer frontend``` in root folder
 - execute with the chosen container config ```docker compose -f compose.yml -f compose.<ENGINE>.yml up```
 - for example: ```docker compose -f compose.yml -f compose.postgres.yml up```

## Starting the system using minio file storage
 - execute ```docker compose -f compose.yml -f compose.minio.yml up```

## Starting the system using a load balancer
 - execute ```docker compose -f compose.yml -f compose.haproxy.yml up```

## Starting the system using a apache balancer
 - execute ```docker compose -f compose.yml -f compose.apache.yml up```

## Scaling
To test the horizontal scaling, check the instructions above, but invoke:
```docker compose up --scale app=5```
A more complex example: 
```docker compose -f compose.yml -f compose.redis.yml -f compose.haproxy.yml -f compose.minio.yml -f compose.postgres.yml -f compose.pgbouncer.yml -f compose.performance.yml up --scale app=5 --scale web=3```

The system will be avialable at:
http://127.0.0.1:8080/
(if using the local DB - use your usual configured user, if using the database container use admin:admin)

### Notes:

The current implementation of the Nginx config will show a 404 page when requesting /not-existing.php instead of passing to /index.php. This should not be an issue given the current routing scheme.

Running Oracle in a container requires you to provide your own image as well as a a php image with oci8 support. There is a guide inside the ./oracle folder.

php.composer.dockerfile is used to generate the vakata/frontend image needed to install dependencies without having php available locally.
