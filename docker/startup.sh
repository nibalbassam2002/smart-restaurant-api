#!/bin/bash

# 1. طباعة جملة للتأكد من السجلات (Logs)
echo "Starting deployment..."

# 2. تنظيف الكاش لضمان عدم وجود إعدادات قديمة
php artisan config:clear
php artisan route:clear

# 3. تشغيل الميغريشن
# نستخدم --force لأننا في وضع الإنتاج (Production)
# بدون هذا الخيار سيسألك "هل أنت متأكد؟" ويتوقف السيرفر
echo "Running migrations..."
php artisan migrate --force

# 4. تشغيل Nginx في الخلفية
service nginx start

# 5. تشغيل PHP-FPM (العملية الرئيسية التي تبقي الحاوية تعمل)
php-fpm