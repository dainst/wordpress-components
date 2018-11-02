FROM wordpress:4.9.8-php7.2-apache

RUN apt-get update && apt-get install -y curl nano

# WP Config
RUN mv /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini
RUN rm /usr/local/etc/php/php.ini-development

# WP Plugins
COPY --chown=www-data:www-data ./plugins /var/www/html/wp-content/plugins
COPY --chown=www-data:www-data ./themes /var/www/html/wp-content/themes
