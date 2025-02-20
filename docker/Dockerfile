FROM php:8.2-apache

# Set the working directory in the container
WORKDIR /var/www/html

# Combine system updates and installations
RUN apt-get update && apt-get install -y \
    vim \
    systemctl \
    gettext-base \
    && rm -rf /var/lib/apt/lists/*

# XDEBUG
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

# Xdebug configuration
RUN echo "xdebug.mode=debug" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.start_with_request=yes" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.client_host=host.docker.internal" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.client_port=9003" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.log=/tmp/xdebug.log" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

# Install necessary PHP extensions
RUN docker-php-ext-install pdo pdo_mysql
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Set up logging
RUN rm -f /var/log/apache2/access.log /var/log/apache2/error.log \
    && touch /var/log/apache2/access.log /var/log/apache2/error.log \
    && chown www-data:www-data /var/log/apache2/access.log /var/log/apache2/error.log \
    && chmod 640 /var/log/apache2/access.log /var/log/apache2/error.log

# Enable Apache modules
RUN a2enmod rewrite

# Set the Apache document root
ENV APACHE_DOCUMENT_ROOT=/var/www/html/src

# Copy and process the custom Apache configuration
COPY ./docker/000-default.conf /etc/apache2/sites-available/

RUN a2enmod rewrite
RUN envsubst < /etc/apache2/sites-available/000-default.conf > /etc/apache2/sites-available/000-default.conf.tmp \
    && mv /etc/apache2/sites-available/000-default.conf.tmp /etc/apache2/sites-available/000-default.conf

# Remove the default site and enable our custom one
RUN a2dissite 000-default
RUN a2ensite 000-default

# Copy your application files
COPY ./src /var/www/html

# Set correct permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Expose port 80
EXPOSE 80
