# Fla-loader Extension - Complete Feature Overview

## Project Summary

Fla-loader is a comprehensive Flarum extension designed to enable external application integration with a Flarum forum. It provides secure authentication, time-limited role management, and a file distribution system with role-based access control.

## Key Features

### 1. External Authentication System
- **API Endpoint**: `POST /api/fla-loader/login`
- **Authentication Method**: Username/password → Session token
- **Token Lifetime**: 30 days (configurable)
- **Security**: Tokens stored securely in database with expiration tracking
- **Response**: Returns user data + complete list of groups/roles

**Use Case**: External applications (game loaders, desktop clients) can authenticate users and maintain persistent sessions.

### 2. Time-Limited Role Assignment
- **Duration Options**: 7 days, 30 days, 180 days, 1 year, or lifetime
- **Admin Interface**: Simple UI for assigning roles to users
- **Automatic Expiration**: Console command removes expired roles
- **Database Tracking**: All role assignments tracked with expiration dates
- **User Groups**: Seamlessly integrates with Flarum's existing group system

**Use Case**: Grant temporary VIP access, trial memberships, or time-limited permissions.

### 3. File Management & Distribution
- **Admin Upload**: Upload files through admin panel
- **Access Control**: Per-file group/role permissions
- **Visibility Settings**: Public (shown in Downloads page) or private (API only)
- **Multiple Files**: No hard limit on number of files
- **Token Authentication**: Files downloadable via session token for external apps
- **File Metadata**: Tracks filename, size, MIME type, permissions

**Use Case**: Distribute game resources, mods, documents, or exclusive content based on user roles.

### 4. User-Facing Download Interface
- **Navigation Integration**: "Download" link added to forum navbar
- **File Listing**: Shows all files user has permission to access
- **Clean UI**: Displays file name, size, and download button
- **Role-Based Filtering**: Automatically shows only accessible files

### 5. Admin Dashboard
- **File Management Section**:
  - Upload new files
  - View all uploaded files
  - Configure file permissions
  - Delete files
  
- **Role Assignment Section**:
  - Select user by ID
  - Choose group/role
  - Set expiration duration
  - Instant assignment

## Technical Architecture

### Backend (PHP)
```
src/
├── Api/Controller/
│   ├── LoginController.php              # User authentication
│   ├── DownloadController.php           # File download handler
│   ├── ListFilesController.php          # List all files
│   ├── UploadFileController.php         # File upload
│   ├── UpdateFileController.php         # Update file permissions
│   ├── DeleteFileController.php         # Delete files
│   ├── AssignRoleController.php         # Assign time-limited roles
│   └── GetUserRolesController.php       # Get user role assignments
├── Console/
│   ├── ExpireRolesCommand.php           # Expire time-limited roles
│   └── CleanupSessionsCommand.php       # Clean expired tokens
├── Migration/
│   ├── *_create_fla_loader_files_table.php
│   ├── *_create_fla_loader_role_assignments_table.php
│   └── *_create_fla_loader_sessions_table.php
└── FlaLoaderServiceProvider.php
```

### Frontend (JavaScript)
```
js/src/
├── admin/
│   ├── index.js                         # Admin extension registration
│   └── components/
│       └── FlaLoaderPage.js             # Admin dashboard UI
└── forum/
    ├── index.js                         # Forum extension registration
    └── components/
        └── DownloadPage.js              # User download page
```

### Database Schema

**fla_loader_files**
- Stores file metadata (name, path, size, MIME type)
- Tracks visibility and group permissions
- Links to physical file storage

**fla_loader_role_assignments**
- Tracks user-role assignments
- Stores expiration timestamps
- Foreign keys to users and groups tables

**fla_loader_sessions**
- Stores session tokens
- Links to user accounts
- Tracks expiration times

## API Endpoints

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/api/fla-loader/login` | None | Login and get token |
| GET | `/api/fla-loader/download/{id}` | Token/Session | Download file |
| GET | `/api/fla-loader/files` | Admin | List all files |
| POST | `/api/fla-loader/files` | Admin | Upload file |
| PATCH | `/api/fla-loader/files/{id}` | Admin | Update permissions |
| DELETE | `/api/fla-loader/files/{id}` | Admin | Delete file |
| POST | `/api/fla-loader/roles` | Admin | Assign role |
| GET | `/api/fla-loader/roles/{userId}` | Admin | Get assignments |

## Console Commands

```bash
# Expire time-limited roles (run via cron)
php flarum fla-loader:expire-roles

# Clean up expired session tokens
php flarum fla-loader:cleanup-sessions
```

## Integration Examples

### Python Example
```python
from fla_loader_client import FlaLoaderClient

client = FlaLoaderClient('https://forum.example.com')
if client.login('username', 'password'):
    if client.has_group('VIP'):
        client.download_file(1, './resource_pack.zip')
```

### Node.js Example
```javascript
const FlaLoaderClient = require('./fla_loader_client');

const client = new FlaLoaderClient('https://forum.example.com');
await client.login('username', 'password');
if (client.hasGroup('VIP')) {
    await client.downloadFile(1, './resource_pack.zip');
}
```

## Security Features

1. **Password Hashing**: Uses PHP's password_verify for secure authentication
2. **Token Expiration**: All tokens expire after 30 days
3. **Role-Based Access**: Files protected by group membership
4. **Admin-Only Operations**: Sensitive operations require admin privileges
5. **Automatic Cleanup**: Expired roles and tokens automatically removed
6. **File Validation**: Checks file existence and readability before serving

## Performance Considerations

- **Database Indexing**: Foreign keys and indexes on frequently queried columns
- **Streaming Downloads**: Large files streamed to prevent memory issues
- **Minimal Queries**: Efficient database queries with proper joins
- **Caching Ready**: Structure supports caching layer implementation

## Scalability

- **File Storage**: Files stored on filesystem (can be moved to S3/CDN)
- **Token Management**: Database-backed session storage
- **Role Management**: Leverages Flarum's existing group system
- **API Design**: RESTful, stateless, horizontally scalable

## Extensibility

The extension is designed to be extended:
- Add custom duration options
- Implement file versioning
- Add download analytics
- Integrate with payment systems
- Add email notifications
- Create custom file categories

## Production Checklist

- [ ] Configure web server (Nginx/Apache)
- [ ] Set up HTTPS/SSL
- [ ] Configure file upload limits
- [ ] Set storage directory permissions
- [ ] Configure cron jobs for role expiration
- [ ] Set up backup strategy for files
- [ ] Monitor disk space usage
- [ ] Configure rate limiting (optional)
- [ ] Set up logging and monitoring
- [ ] Review security settings

## Maintenance Tasks

**Daily**
- Run role expiration command via cron

**Weekly**
- Clean up expired session tokens
- Check storage space usage

**Monthly**
- Review access logs
- Audit role assignments
- Backup uploaded files

## Support & Documentation

- **API Documentation**: See [API.md](API.md)
- **Setup Guide**: See [SETUP.md](SETUP.md)
- **Examples**: See [examples/](examples/)
- **Changelog**: See [CHANGELOG.md](CHANGELOG.md)

## License

MIT License - See [LICENSE](LICENSE) file for details.

## Version

**Current Version**: 1.0.0
**Flarum Compatibility**: 1.2.0+
**PHP Version**: 8.0+

## Contributors

- Undertaker (@Undertaker-afk)

---

**Built with ❤️ for the Flarum community**
