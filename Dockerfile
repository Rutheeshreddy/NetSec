FROM php:apache

# Install PostgreSQL extension for PHP
RUN apt-get update && apt-get install -y libpq-dev && docker-php-ext-install pdo pdo_pgsql
RUN apt-get update && apt-get install -y postgresql-client


# Enable SSL in Apache
RUN a2enmod ssl rewrite headers

# Create self-signed certificate
RUN mkdir /etc/apache2/ssl && \
    openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
    -keyout /etc/apache2/ssl/apache.key \
    -out /etc/apache2/ssl/apache.crt \
    -subj "/C=US/ST=State/L=City/O=Organization/OU=Department/CN=localhost"

# Configure Apache for HTTPS
RUN echo "\
<VirtualHost *:443>\n\
    ServerAdmin webmaster@localhost\n\
    DocumentRoot /var/www/html/app\n\
    SSLEngine on\n\
    SSLCertificateFile /etc/apache2/ssl/apache.crt\n\
    SSLCertificateKeyFile /etc/apache2/ssl/apache.key\n\
    <Directory /var/www/html/app>\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
</VirtualHost>" > /etc/apache2/sites-available/default-ssl.conf

RUN a2ensite default-ssl

# Remove default HTTP configuration to disable HTTP
RUN a2dissite 000-default.conf

# Copy application files to the container
COPY app/ /var/www/html/app

# Set ownership and permissions for the entire app directory
RUN chown -R www-data:www-data /var/www/html/app && \
    chmod -R 777 /var/www/html/app

# Ensure the logs directory exists, set ownership, and permissions
RUN mkdir -p /var/www/html/app/logs && \
    chown -R www-data:www-data /var/www/html/app/logs && \
    chmod -R 777 /var/www/html/app/logs

# Ensure user_activity.log file has correct ownership and permissions
RUN touch /var/www/html/app/logs/user_activity.log && \
    chown www-data:www-data /var/www/html/app/logs/user_activity.log && \
    chmod 777 /var/www/html/app/logs/user_activity.log && \
    chmod 777 /var/www/html/app/logs/logger.inc.php 
EXPOSE 443

COPY set_permissions.sh /usr/local/bin/set_permissions.sh

# Give the script execute permissions
RUN chmod +x /usr/local/bin/set_permissions.sh

# Run the permissions script and start Apache
CMD ["/usr/local/bin/set_permissions.sh"]
