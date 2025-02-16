FROM php:8.2-apache

# Set the working directory in the container
WORKDIR /var/www/html

RUN apt-get update
RUN apt install -y vim systemctl

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

# Enable Apache rewrite module (useful for routing in frameworks)
RUN a2enmod rewrite

# Set the Apache document root to /var/www/html/src
ENV APACHE_DOCUMENT_ROOT /var/www/html/src

# Update the Apache configuration
RUN sed -ri -e "s!/var/www/html!${APACHE_DOCUMENT_ROOT}!g" /etc/apache2/sites-available/*.conf
RUN sed -ri -e "s!/var/www/!${APACHE_DOCUMENT_ROOT}!g" /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Copy your application files into the container
COPY ./src /var/www/html

# Set correct permissions for the src directory
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html

# Expose port 80 for Apache
EXPOSE 80
