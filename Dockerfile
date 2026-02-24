FROM php:8.2-apache

# Install dependencies
RUN apt-get update && apt-get install -y \
    acl \
    file \
    gettext \
    git \
    unzip \
    libicu-dev \
    libzip-dev \
    && docker-php-ext-install \
    intl \
    opcache \
    zip \
    pdo_mysql

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Configure Apache DocumentRoot to /public
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Copy project files
COPY . .

# Install dependencies (production)
RUN composer install --no-dev --optimize-autoloader

# Adjust permissions
RUN chown -R www-data:www-data var && chmod -R 775 var

# Use the production configuration
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Environment Setup
# Note: You should pass real secrets via environment variables at runtime
ENV APP_ENV=prod
ENV APP_DEBUG=0

EXPOSE 80
