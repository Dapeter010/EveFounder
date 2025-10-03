# Nginx Configuration for Large File Uploads (iPhone Images)

## Problem
iPhone images (especially HEIC format and high-resolution photos) can be large, and nginx has default limits that prevent uploads.

## Solution

### 1. Update Nginx Configuration

Add or update these settings in your nginx configuration file:

**Location**: Usually `/etc/nginx/nginx.conf` or `/etc/nginx/sites-available/evefound` (your site config)

```nginx
http {
    # ... other settings ...

    # Increase client body size to allow large image uploads (up to 25MB)
    client_max_body_size 25M;

    # Increase buffer sizes for large requests
    client_body_buffer_size 128k;

    # Increase timeouts for uploads
    client_body_timeout 300s;
    send_timeout 300s;
}

server {
    listen 80;
    server_name yourdomain.com;

    root /var/www/evefound/public;
    index index.php index.html;

    # Important: Override at server level if needed
    client_max_body_size 25M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;

        # Increase FastCGI timeouts and buffers
        fastcgi_read_timeout 300s;
        fastcgi_send_timeout 300s;
        fastcgi_buffer_size 128k;
        fastcgi_buffers 256 16k;
        fastcgi_busy_buffers_size 256k;
        fastcgi_temp_file_write_size 256k;
    }

    # Deny access to hidden files
    location ~ /\. {
        deny all;
    }
}
```

### 2. Update PHP-FPM Configuration (if using PHP-FPM)

**Location**: Usually `/etc/php/8.4/fpm/php.ini`

```ini
upload_max_filesize = 25M
post_max_size = 150M
max_file_uploads = 20
memory_limit = 256M
max_execution_time = 300
max_input_time = 300
```

### 3. Restart Services

```bash
# Test nginx configuration
sudo nginx -t

# Reload nginx
sudo systemctl reload nginx
# or
sudo service nginx reload

# Restart PHP-FPM (adjust version number as needed)
sudo systemctl restart php8.4-fpm
# or
sudo service php8.4-fpm restart
```

### 4. Verify Configuration

```bash
# Check nginx configuration
sudo nginx -T | grep client_max_body_size

# Check PHP configuration
php -i | grep upload_max_filesize
php -i | grep post_max_size
```

## Notes

- **client_max_body_size**: Set to 25M to allow iPhone photos (including HEIC format)
- **post_max_size**: Set to 150M to allow multiple large images in one request (up to 6 photos × 25MB)
- The Laravel app now accepts: jpeg, png, jpg, heic, heif, webp formats
- HEIC images are automatically converted to JPEG on the backend for compatibility

## Troubleshooting

If you still get errors:

1. **Check nginx error logs**: `sudo tail -f /var/log/nginx/error.log`
2. **Check PHP-FPM logs**: `sudo tail -f /var/log/php8.4-fpm.log`
3. **Verify permissions**: Ensure `storage/app/public/profile-photos` is writable
4. **Clear Laravel cache**: `php artisan config:clear && php artisan cache:clear`

## Development Environment

For local development, the `.user.ini` file in the `public/` directory will handle PHP settings automatically.
