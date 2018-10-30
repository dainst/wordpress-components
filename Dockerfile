FROM wordpress:4.9.8-php7.2-apache

RUN apt-get update && apt-get install -y curl

