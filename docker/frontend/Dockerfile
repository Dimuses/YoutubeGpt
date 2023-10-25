ARG PHP_VERSION
# Преобразуем аргументы в переменные окружения
FROM yiisoftware/yii2-php:$PHP_VERSION-apache
COPY docker/app/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini
