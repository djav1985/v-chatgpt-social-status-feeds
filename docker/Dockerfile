
# Use official PHP image with Apache
FROM php:8.0-apache

# Install PHP extensions and dependencies in a single step
RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libfreetype6-dev cron mariadb-client \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd mysqli pdo pdo_mysql \
    && rm -rf /var/lib/apt/lists/*  # Clean up to reduce image size

# Set the working directory
WORKDIR /var/www/

# Copy application files from /root/ (since Dockerfile is in /)
COPY root/ /var/www/
# Bring in docker specific configuration
COPY docker/docker-config.php /var/www/docker-config.php
COPY docker/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Update Apache configuration to set the document root
RUN sed -i 's|/var/www/html|/var/www/public|g' /etc/apache2/sites-available/000-default.conf

# Ensure proper permissions
RUN chown -R www-data:www-data /var/www && chmod -R 755 /var/www

# Set up cron jobs
RUN echo "0 12 1 * * php /var/www/cron.php reset_usage" >> /etc/crontab && \
    echo "0 12 * * * php /var/www/cron.php clear_list" >> /etc/crontab && \
    echo "0 12 * * * php /var/www/cron.php cleanup" >> /etc/crontab && \
    echo "0 12 * * * php /var/www/cron.php purge_images" >> /etc/crontab && \
    echo "0 0 * * * php /var/www/cron.php fill_query" >> /etc/crontab && \
    echo "* * * * * php /var/www/cron.php run_query" >> /etc/crontab && \
    crontab /etc/crontab


# Delete old config.php and rename docker-config.php to config.php
RUN rm -f /var/www/config.php && mv /var/www/docker-config.php /var/www/config.php

# Expose port 80
EXPOSE 80

# Start application through entrypoint script
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
