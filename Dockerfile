FROM wordpress:4.9.8-apache

RUN apt-get update && apt-get install -y php7-curl

