#!/bin/sh

# تشغيل الأوامر الأساسية للارافل عند الإقلاع
php artisan migrate --force
php artisan config:cache
php artisan route:cache

# تشغيل Nginx في الخلفية
nginx -g "daemon off;" &

# تشغيل PHP-FPM في الواجهة
php-fpm