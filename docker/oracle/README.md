# BUILDING ORACLE ARM64 CONTAINER
Download the appropriate package:
https://www.oracle.com/database/technologies/oracle-database-software-downloads.html#19c

```sh
git clone https://github.com/oracle/docker-images
cp ./LINUX.ARM64_1919000_db_home.zip ./docker-images/OracleDatabase/SingleInstance/dockerfiles/19.3.0
cd docker-images/OracleDatabase/SingleInstance/dockerfiles
./buildContainerImage.sh -v 19.3.0 -e
git clone -b 19c-arm-slim https://github.com/marcelo-ochoa/oci-oracle-free
cd oci-oracle-free
# add find utils in Docker.faststart
docker buildx build --build-arg BUILD_MODE=SLIM -t oracle/database:19.3.0-ee-slim -f Dockerfile.193 .
docker buildx build --build-arg BASE_IMAGE=oracle/database:19.3.0-ee-slim -t oracle/database:19.3.0-ee-slim-faststart -f Dockerfile.faststart .
# copy container ID and paste in compose.oracle.yml
```

# BUILDING PHP OCI8 CONTAINER
Download the appropriate instant client (both basic and sdk zips):
https://www.oracle.com/database/technologies/instant-client/downloads.html

```sh
# use the modified php.dockerfile
docker compose -f compose.yml -f compose.oracle.yml build app
```