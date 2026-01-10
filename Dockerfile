FROM php:8.0-apache

# Set working directory
WORKDIR /var/www/html

# Install system dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
        build-essential \
        git \
        curl \
        unzip \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libzip-dev \
        libonig-dev \
        libxml2-dev \
        default-mysql-client \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd bcmath zip mysqli pdo pdo_mysql mbstring exif pcntl \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Create necessary directories and set permissions
RUN mkdir -p /var/www/html/application/logs \
    && mkdir -p /var/www/html/application/cache \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Configure PHP for CodeIgniter and large data processing
RUN { \
        echo 'max_execution_time = 300'; \
        echo 'max_input_time = 300'; \
        echo 'memory_limit = 512M'; \
        echo 'post_max_size = 100M'; \
        echo 'upload_max_filesize = 100M'; \
        echo 'display_errors = Off'; \
        echo 'log_errors = On'; \
        echo 'error_log = /var/log/apache2/php_errors.log'; \
        echo 'date.timezone = UTC'; \
    } > /usr/local/etc/php/conf.d/custom.ini

# Enable Apache modules
RUN a2enmod rewrite headers

EXPOSE 80

CMD ["apache2-foreground"]