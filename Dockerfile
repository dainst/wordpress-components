FROM wordpress:4.9.8-php7.2-apache

# RUN apt-get update && apt-get install -y wget unzip

# Plugins
COPY --chown=www-data:www-data ./plugins /var/www/html/wp-content/plugins
COPY --chown=www-data:www-data ./themes/shap-theme /var/www/html/wp-content/themes/shap-theme
