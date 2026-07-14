FROM httpd:latest
COPY mod_xsendfile.c /usr/local/apache2/modules/mod_xsendfile.c
COPY httpd.conf /usr/local/apache2/conf/httpd.conf
RUN apt-get update &&  apt-get install -y libapr1 libapr1-dev libaprutil1-dev gcc
RUN cd /usr/local/apache2/modules/ && apxs -cia mod_xsendfile.c
