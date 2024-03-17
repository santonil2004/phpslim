FROM php:8.0-apache

# Set the working directory to /var/www/html
WORKDIR /var/www/html

# Set the default timezone to UTC
ENV TZ=UTC

# Install necessary dependencies
RUN apt-get update && \
    apt-get install -y \
        curl \
        unzip

# Enable mod_rewrite
RUN a2enmod rewrite

# Set the webroot to /var/www/html/public
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install extensions
RUN docker-php-ext-install pdo_mysql

# Clean up unnecessary files to reduce image size
RUN rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*