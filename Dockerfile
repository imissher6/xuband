FROM php:8.2-apache

# Install PHP extensions
RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libfreetype6-dev \
    zip unzip curl \
    && docker-php-ext-install pdo pdo_mysql mysqli gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Fix Apache MPM conflict: disable event/worker, enable prefork
RUN a2dismod mpm_event mpm_worker 2>/dev/null || true \
    && a2enmod mpm_prefork rewrite

# Copy Apache config
COPY apache.conf /etc/apache2/sites-available/000-default.conf
COPY ports.conf /etc/apache2/ports.conf

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . /var/www/html/

# Create uploads directory and set permissions
RUN mkdir -p /var/www/html/public/uploads \
    && chown -R www-data:www-data /var/www/html/public/uploads \
    && chmod -R 755 /var/www/html/public/uploads

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Railway injects $PORT at runtime — expose it
EXPOSE ${PORT:-80}

# Start Apache with $PORT substituted at runtime
CMD bash -c "sed -i \"s/Listen .*/Listen \${PORT:-80}/g\" /etc/apache2/ports.conf && \
    sed -i \"s/<VirtualHost \*:[0-9]*>/<VirtualHost *:\${PORT:-80}>/g\" /etc/apache2/sites-available/000-default.conf && \
    apache2-foreground"
