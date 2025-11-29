# 1. نستخدم نسخة PHP الرسمية
FROM php:8.2-fpm

# 2. تثبيت المكتبات اللازمة للنظام و Nginx
# تمت إضافة libpq-dev هنا وهي ضرورية لقواعد بيانات Postgres
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libpq-dev \
    nginx

# 3. تنظيف الكاش لتقليل حجم الصورة
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# 4. تثبيت امتدادات PHP المطلوبة للارافل
# تمت إضافة pdo_pgsql هنا لكي يستطيع PHP التحدث مع قاعدة البيانات
RUN docker-php-ext-install pdo_mysql pdo_pgsql mbstring exif pcntl bcmath gd

# 5. تثبيت Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 6. تحديد مجلد العمل
WORKDIR /var/www

# 7. نسخ ملفات المشروع إلى الحاوية
COPY . .

# 8. تثبيت مكتبات لارافل (وضع الإنتاج)
RUN composer install --no-interaction --optimize-autoloader --no-dev

# 9. نسخ إعدادات Nginx التي أنشأناها
COPY docker/nginx.conf /etc/nginx/conf.d/default.conf

# 10. إعطاء صلاحيات للملفات
RUN chown -R www-data:www-data /var/www \
    && chmod -R 775 /var/www/storage

# 11. تجهيز ملف التشغيل
COPY docker/startup.sh /usr/local/bin/startup.sh
RUN chmod +x /usr/local/bin/startup.sh

# 12. المنفذ الذي سيعمل عليه الدوكر
EXPOSE 80

# 13. أمر التشغيل النهائي
CMD ["/usr/local/bin/startup.sh"]