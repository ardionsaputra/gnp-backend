# ==========================================================
# 1️⃣ Base image – PHP 8.3 + Apache (official image)
# ==========================================================
FROM php:8.3-apache

# ----------------------------------------------------------
# 2️⃣ Install sistem dependencies (zip, git, pgsql/mysql)
# ----------------------------------------------------------
RUN apt-get update && apt-get install -y 
    libzip-dev zip unzip git 
    && docker-php-ext-install zip 
    && docker-php-ext-install pdo_pgsql pgsql 
    # Uncomment baris berikut bila Anda memakai MySQL
    # && docker-php-ext-install pdo_mysql mysqli 
    && a2enmod rewrite

# ----------------------------------------------------------
# 3️⃣ Install Composer secara global (menggunakan image resmi)
# ----------------------------------------------------------
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# ----------------------------------------------------------
# 4️⃣ Set working directory (cocok dengan struktur Laravel)
# ----------------------------------------------------------
WORKDIR /var/www/html

# ----------------------------------------------------------
# 5️⃣ Copy source code ke dalam image
# ----------------------------------------------------------
COPY . .

# ----------------------------------------------------------
# 6️⃣ Install dependensi PHP via Composer
# ----------------------------------------------------------
RUN composer install --no-dev --optimize-autoloader 
    && chown -R www-data:www-data storage 
    && chown -R www-data:www-data bootstrap/cache

# ----------------------------------------------------------
# 7️⃣ Expose port 80 (Apache default)
# ----------------------------------------------------------
EXPOSE 80

# ----------------------------------------------------------
# 8️⃣ Default command – jalankan Apache di foreground
# ----------------------------------------------------------
CMD [\"apache2-foreground\"]
