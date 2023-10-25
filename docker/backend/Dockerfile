ARG PHP_VERSION
# Преобразуем аргументы в переменные окружения
FROM yiisoftware/yii2-php:$PHP_VERSION-apache
COPY docker/app/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

# Change document root for Apache
RUN sed -i -e 's|/app/web|/app/backend/web|g' /etc/apache2/sites-available/000-default.conf
