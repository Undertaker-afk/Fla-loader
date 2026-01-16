# Installation and Setup Guide for Fla-loader

## Prerequisites

- Flarum 1.2.0 or higher
- PHP 8.0 or higher
- Composer
- Node.js and npm
- Write permissions for file uploads (storage directory)

## Installation Steps

### 1. Install via Composer

```bash
cd /path/to/your/flarum
composer require undertaker/fla-loader
```

### 2. Run Migrations

The extension includes database migrations that create three tables:
- `fla_loader_files` - Stores file metadata
- `fla_loader_role_assignments` - Tracks time-limited role assignments
- `fla_loader_sessions` - Manages session tokens for external authentication

Run migrations:

```bash
php flarum migrate
```

### 3. Enable the Extension

You can enable the extension through:

**Option A: Admin Panel**
1. Login to your Flarum admin panel
2. Navigate to Extensions
3. Find "Fla Loader" and click Enable

**Option B: Command Line**
```bash
php flarum extension:enable undertaker-fla-loader
```

### 4. Build Frontend Assets

```bash
cd vendor/undertaker/fla-loader
npm install
npm run build
```

Then clear Flarum cache:

```bash
cd /path/to/your/flarum
php flarum cache:clear
```

### 5. Configure Storage Directory

Ensure the storage directory is writable:

```bash
mkdir -p storage/app/fla-loader
chmod 755 storage/app/fla-loader
```

### 6. Set Up Cron Job (Important!)

To automatically expire time-limited roles, add this to your crontab:

```bash
crontab -e
```

Add this line:

```bash
# Expire roles daily at midnight
0 0 * * * cd /path/to/your/flarum && php flarum fla-loader:expire-roles >> /var/log/flarum-cron.log 2>&1
```

Or run it more frequently (e.g., every hour):

```bash
0 * * * * cd /path/to/your/flarum && php flarum fla-loader:expire-roles >> /var/log/flarum-cron.log 2>&1
```

### 7. Configure Web Server (Optional)

If you want to serve files through a CDN or specific domain, configure your web server:

**Nginx Example:**
```nginx
location /api/fla-loader/download/ {
    # Add CORS headers if needed for external loaders
    add_header Access-Control-Allow-Origin "*";
    add_header Access-Control-Allow-Methods "GET, OPTIONS";
    
    # Pass to PHP
    try_files $uri $uri/ /index.php?$query_string;
}
```

**Apache Example:**
```apache
<Location /api/fla-loader/download/>
    # Add CORS headers if needed
    Header set Access-Control-Allow-Origin "*"
    Header set Access-Control-Allow-Methods "GET, OPTIONS"
</Location>
```

## Configuration

### Admin Panel Access

1. Login as an administrator
2. Go to Administration → Extensions
3. Click on "Fla Loader"

You will see two main sections:

#### File Management
- Upload files (up to 4 files recommended, but no hard limit)
- Set file visibility (public/private)
- Configure group-based access control
- Delete files

#### Role Assignment
- Assign roles to users with expiration times
- Choose from: 7 days, 30 days, 180 days, 1 year, or lifetime
- Track role assignments

### File Access Control

Files can be configured with:
- **Public access**: Visible in the Downloads page for all logged-in users
- **Group-based access**: Only users in specific groups can download
- **Hidden files**: Not visible in Downloads page, only accessible via API with token

## User Guide

### For Forum Users

1. **Access Downloads**: Click "Download" in the navigation bar
2. **Browse Files**: See all files you have permission to access
3. **Download**: Click the download button next to any file

### For Administrators

#### Uploading Files

1. Navigate to Admin → Fla Loader
2. Under "File Management", click the file upload button
3. Select a file
4. The file will be uploaded automatically
5. You can then modify its permissions

#### Assigning Time-Limited Roles

1. Navigate to Admin → Fla Loader
2. Under "Time-Limited Role Assignment":
   - Enter the User ID
   - Select the Group/Role
   - Choose Duration (7d, 30d, 180d, 1y, or lifetime)
3. Click "Assign Role"

The role will automatically expire after the specified duration.

#### Managing File Permissions

After uploading a file:
1. View the file in the file list
2. Edit permissions:
   - Toggle public visibility
   - Add/remove allowed groups

### For External Loader Developers

See [API.md](API.md) for complete API documentation.

Basic flow:
```
1. POST /api/fla-loader/login
   → Receive session token and user groups
   
2. Check user groups for required permissions

3. GET /api/fla-loader/download/{id}?token={token}
   → Download file if user has permission
```

## Troubleshooting

### Files Not Downloading

1. Check file permissions: `ls -la storage/app/fla-loader/`
2. Verify the file exists in the database: `SELECT * FROM fla_loader_files;`
3. Check user group membership
4. Review PHP error logs

### Roles Not Expiring

1. Verify cron job is running: `tail -f /var/log/flarum-cron.log`
2. Manually run: `php flarum fla-loader:expire-roles`
3. Check database: `SELECT * FROM fla_loader_role_assignments WHERE expires_at < NOW();`

### Session Tokens Not Working

1. Check token hasn't expired (30-day limit)
2. Verify token exists: `SELECT * FROM fla_loader_sessions WHERE token = 'your_token';`
3. Ensure expires_at is in the future

### Upload Fails

1. Check PHP upload limits in `php.ini`:
   ```ini
   upload_max_filesize = 100M
   post_max_size = 100M
   ```
2. Verify storage directory permissions: `chmod 755 storage/app/fla-loader`
3. Check available disk space: `df -h`

## Security Best Practices

1. **Use HTTPS**: Always use HTTPS in production to protect session tokens
2. **Regular Token Cleanup**: Consider adding a cleanup script for expired tokens
3. **File Size Limits**: Configure appropriate file size limits
4. **Group Permissions**: Carefully manage which groups have access to files
5. **Monitor Access**: Review access logs regularly

## Uninstallation

To remove the extension:

```bash
# Disable extension
php flarum extension:disable undertaker-fla-loader

# Remove via composer
composer remove undertaker/fla-loader

# Optionally remove database tables
php flarum migrate:reset --extension=undertaker-fla-loader
```

⚠️ **Warning**: Uninstalling will remove all uploaded files and role assignments!

## Support

For issues, feature requests, or contributions:
- GitHub: https://github.com/Undertaker-afk/Fla-loader
- Report bugs in the Issues section

## License

MIT License - See LICENSE file for details
