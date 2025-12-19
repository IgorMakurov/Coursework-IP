# Используем официальный образ с PHP и Apache
FROM php:8.1-apache

# Устанавливаем зависимости PDO/MySQL
RUN apt-get update && apt-get install -y \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libonig-dev \
    libfreetype6-dev \
    libxml2-dev \
    mariadb-client \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo_mysql gd zip opcache \
    && a2enmod rewrite \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf

# Копируем все файлы проекта
# Контекст сборки (BUILD CONTEXT) должен быть корнем проекта (survey-app)
COPY . /var/www/html/

# Устанавливаем права доступа
RUN chown -R www-data:www-data /var/www/html/

# Перемещаем файлы в корень веб-сервера Apache
# В Dockerfile, мы можем скопировать .htaccess прямо в корень
COPY public/.htaccess /var/www/html/.htaccess

# Порт, который Render будет использовать (должен совпадать с переменной $PORT)

EXPOSE 80

