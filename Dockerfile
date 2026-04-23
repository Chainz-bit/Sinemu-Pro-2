FROM php:8.2-fpm-alpine

RUN apk add --no-cache \
    nginx \
    nodejs \
    npm \
    curl \
    zip \
    unzip \
    git \
    supervisor \
    libpng-dev \
    libzip-dev \
    oniguruma-dev \
    libxml2-dev

RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    opcache \
    zip

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# ✅ Copy package.json dulu (Docker layer cache optimization)
COPY package*.json ./
RUN npm ci --no-audit --no-fund

# ✅ Copy composer dulu (Docker layer cache optimization)  
COPY composer*.json composer.lock ./
RUN composer install --optimize-autoloader --no-dev

# Copy semua file project
COPY . .

# ✅ Build assets SETELAH semua source code ada
RUN npm run build

# Hapus node_modules (tidak dibutuhkan di production)
RUN rm -rf node_modules

RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]