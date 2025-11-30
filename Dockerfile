# Use official PHP + Apache image
FROM php:8.2-apache

# Install required PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Copy the 'online' folder contents into the web root
COPY online/ /var/www/html/

# Fix permissions so Apache can read files
RUN chown -R www-data:www-data /var/www/html \
 && chmod -R 755 /var/www/html

# Configure Apache to listen on port 8080
RUN sed -i 's/80/8080/g' /etc/apache2/ports.conf \
 && sed -i 's/:80/:8080/g' /etc/apache2/sites-enabled/000-default.conf \
 && sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Set environment variable for Cloud Run
ENV PORT=8080
EXPOSE 8080

# Start Apache
CMD ["apache2-foreground"]
