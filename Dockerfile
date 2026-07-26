# Origin Driving School Management System
# Production container: PHP 8.2 + Apache, serving the application and
# seeding the MySQL database on first boot.

FROM php:8.2-apache

# Install the MySQL PDO driver and the mysql client (used by the entrypoint
# to wait for the database and import the schema on first run).
RUN apt-get update \
    && apt-get install -y --no-install-recommends default-mysql-client \
    && docker-php-ext-install pdo_mysql \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Enable URL rewriting and headers (used by .htaccess in public/uploads).
RUN a2enmod rewrite headers

# Copy the application into the image.
COPY . /var/www/html/

# The application lives in the origin_driving_school/ subdirectory, so point
# Apache's document root there.
ENV APP_DOCROOT=/var/www/html/origin_driving_school
COPY deploy/000-default.conf /etc/apache2/sites-available/000-default.conf

# Ensure the uploads directory is writable by the web server.
RUN mkdir -p /var/www/html/origin_driving_school/public/uploads \
    && chown -R www-data:www-data /var/www/html/origin_driving_school/public/uploads

COPY deploy/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Railway (and most PaaS) provide the listening port via $PORT; default to 80.
ENV PORT=80
EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
