# Fla-loader

A Flarum extension for external loader integration with time-limited roles and file management.

## Features

- **External Login API**: Authenticate users via external loaders using username/password and receive session tokens
  - **HWID Protection**: Hardware ID validation to prevent account sharing (automatically registered on first login)
- **Time-Limited Role Assignment**: Admins can assign roles with expiration times (7 days, 30 days, 180 days, 1 year, or lifetime)
- **File Management System**: 
  - Upload files with role-based access control
  - Download navbar entry for user-accessible files
  - API endpoint for external loader downloads with session token authentication
  - Admin interface for managing files and permissions
- **HWID Management**: Admins can reset user HWIDs to allow login from new devices

## Installation

1. Install via composer:
   ```bash
   composer require undertaker/fla-loader
   ```

2. Enable the extension in your Flarum admin panel

3. Run migrations:
   ```bash
   php flarum migrate
   ```

## API Endpoints

### Login
- **POST** `/api/fla-loader/login`
  - Body: `{"username": "...", "password": "...", "hwid": "..."}`
  - Returns: Session token and user groups/roles
  - Note: HWID is required and validated

### File Download
- **GET** `/api/fla-loader/download/{id}?token=...`
  - Query params: `token` (session token)
  - Returns: File download

### File Management (Admin only)
- **GET** `/api/fla-loader/files` - List all files
- **POST** `/api/fla-loader/files` - Upload file
- **PATCH** `/api/fla-loader/files/{id}` - Update file permissions
- **DELETE** `/api/fla-loader/files/{id}` - Delete file

### Role Assignment (Admin only)
- **POST** `/api/fla-loader/roles` - Assign time-limited role
  - Body: `{"userId": 1, "groupId": 2, "duration": "30d"}`
- **GET** `/api/fla-loader/roles/{userId}` - Get user's role assignments

### HWID Management (Admin only)
- **POST** `/api/fla-loader/hwid/reset` - Reset a user's HWID
  - Body: `{"userId": 1}`
- **GET** `/api/fla-loader/hwid/{userId}` - Check user's HWID status

## Console Commands

- `php flarum fla-loader:expire-roles` - Manually expire roles (should be run via cron)
- `php flarum fla-loader:cleanup-sessions` - Clean up expired session tokens

## Development

If you want to modify the extension's JavaScript code:

1. Clone the repository
2. Install dependencies:
   ```bash
   npm install
   ```
3. Make your changes to files in `js/src/`
4. Build the assets:
   ```bash
   npm run build
   ```
   Or use development mode with auto-rebuild:
   ```bash
   npm run dev
   ```

## License

MIT