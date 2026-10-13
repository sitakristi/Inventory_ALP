FROM php:8.2-apache

RUN apt-get update -y && apt-get install -y git unzip
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY . /var/www/html/
RUN composer install --no-interaction --optimize-autoloader

EXPOSE 80

CMD sh -c "if [ -f /etc/secrets/firebase_credentials.json ]; then cp /etc/secrets/firebase_credentials.json /var/www/html/firebase_credentials.json && chown www-data:www-data /var/www/html/firebase_credentials.json; fi; apache2-foreground"