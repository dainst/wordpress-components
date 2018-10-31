FROM wordpress:4.9.8-php7.2-apache

RUN apt-get update && apt-get install -y curl

RUN mkdir -p /var/www/html/wp-content/plugins/eagle-storytelling-application/
COPY ./plugins/storytelling /var/www/html/wp-content/plugins/wordpress-storytelling/

