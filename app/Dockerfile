FROM php:7.4-fpm
# WORKDIR /var/www/html

# Fix debconf warnings upon build
# ARG DEBIAN_FRONTEND=noninteractive
RUN apt-get update && apt-get install -y --no-install-recommends apt-utils

# COPY app/composer.* ./
COPY app/. .

# Update packages & Install Git and Supervisor
RUN apt-get update -y
RUN apt-get install -yq \
    git \
    supervisor \
    zlib1g-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    gzip \
    zip \
    unzip

# Install GD extension
RUN docker-php-ext-configure gd --with-freetype=/usr/include/ --with-jpeg=/usr/include/
RUN docker-php-ext-install -j "$(nproc)" gd pcntl pdo_mysql

#Install mongodb
RUN pecl install mongodb
RUN docker-php-ext-enable mongodb

# Install inotify extension for watching file changes
RUN pecl install inotify
RUN docker-php-ext-enable inotify

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN composer install --no-dev --optimize-autoloader --no-scripts

RUN ln -s /var/www/html/storage/app/public /var/www/html/public/storage

# Added supervisor config
COPY supervisord.conf /etc/supervisor/conf.d/supervisor.conf

CMD ["/usr/bin/supervisord", "-n"]
# CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
