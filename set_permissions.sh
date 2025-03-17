#!/bin/bash
# Set correct ownership and permissions for logs directory and log files
chown -R www-data:www-data /var/www/html/app/logs
chmod -R 755 /var/www/html/app/logs
chmod 644 /var/www/html/app/logs/user_activity.log
chmod 644 /var/www/html/app/logs/logger.inc.php

echo "Setting permissions for HTMLPurifier cache directory..."
chown -R www-data:www-data /var/www/html/app/includes/htmlpurifier-4.15.0/library/HTMLPurifier/DefinitionCache/Serializer
chmod -R 755 /var/www/html/app/includes/htmlpurifier-4.15.0/library/HTMLPurifier/DefinitionCache/Serializer
chown -R www-data:www-data /var/www/html/app/uploads
chmod -R 755 /var/www/html/app/uploads

# Start Apache
apache2-foreground
