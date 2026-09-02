FROM php:8.3-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    bash \
    git \
    curl \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libwebp-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    mariadb-client \
    linux-headers \
    $PHPIZE_FLAGS

# Configure and install PHP extensions required by Drupal 11
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
        gd \
        pdo_mysql \
        mysqli \
        opcache \
        intl \
        mbstring \
        zip \
        bcmath \
        exif

# Install Composer
COPY --from=composer:2.8 /usr/bin/composer /usr/local/bin/composer

# Set working directory
WORKDIR /var/www/html

# Create Drush launcher / symlink
ENV PATH="/var/www/html/vendor/bin:${PATH}"

# Copy custom php.ini configuration
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini

# Fix permissions
RUN chown -R www-data:www-data /var/www/html

USER www-data

EXPOSE 9000
CMD ["php-fpm"]
