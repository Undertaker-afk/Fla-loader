# Fla-loader API Documentation

## Authentication

The extension provides two methods of authentication:

1. **Session Token Authentication** (for external loaders)
2. **Flarum Session Authentication** (for logged-in users in the forum)

## API Endpoints

### 1. Login

**Endpoint:** `POST /api/fla-loader/login`

**Description:** Authenticate a user and receive a session token. **Now includes HWID validation.**

**Request Body:**
```json
{
  "username": "john_doe",
  "password": "secure_password",
  "hwid": "unique-hardware-id-string"
}
```

**Note:** The `hwid` parameter is **required**. On first login, the HWID will be registered for the user. On subsequent logins, the HWID must match the registered value.

**Response (200 OK):**
```json
{
  "data": {
    "token": "abc123def456...",
    "expiresAt": "2024-02-15T10:30:00Z",
    "user": {
      "id": 1,
      "username": "john_doe",
      "displayName": "John Doe",
      "email": "john@example.com"
    },
    "groups": [
      {
        "id": 3,
        "name": "Member",
        "namePlural": "Members",
        "color": "#3498db",
        "icon": "fas fa-user"
      }
    ]
  }
}
```

**Error Response (401 Unauthorized):**
```json
{
  "errors": [
    {
      "status": "401",
      "title": "Unauthorized",
      "detail": "Invalid credentials"
    }
  ]
}
```

**Error Response (403 Forbidden - HWID Mismatch):**
```json
{
  "errors": [
    {
      "status": "403",
      "title": "Forbidden",
      "detail": "HWID mismatch. Please contact an administrator to reset your HWID."
    }
  ]
}
```

### 2. Download File

**Endpoint:** `GET /api/fla-loader/download/{id}?token={session_token}`

**Description:** Download a file with proper authentication

**URL Parameters:**
- `id` (required): File ID

**Query Parameters:**
- `token` (optional): Session token for external authentication (not needed if using Flarum session)

**Response:** File download stream

**Error Responses:**
- `401 Unauthorized`: No valid authentication
- `403 Forbidden`: User doesn't have permission for this file
- `404 Not Found`: File not found

### 3. List Files (Admin Only)

**Endpoint:** `GET /api/fla-loader/files`

**Description:** Get list of all uploaded files

**Authentication:** Admin only

**Response (200 OK):**
```json
{
  "data": [
    {
      "id": 1,
      "filename": "abc123.zip",
      "originalName": "resource_pack.zip",
      "size": 1048576,
      "mimeType": "application/zip",
      "isPublic": true,
      "allowedGroups": [3, 4],
      "createdAt": "2024-01-15T10:00:00Z"
    }
  ]
}
```

### 4. Upload File (Admin Only)

**Endpoint:** `POST /api/fla-loader/files`

**Description:** Upload a new file

**Authentication:** Admin only

**Request:** Multipart form data
- `file` (required): The file to upload
- `isPublic` (optional, boolean): Whether file is visible in Download page
- `allowedGroups` (optional, JSON array): Array of group IDs allowed to download

**Response (201 Created):**
```json
{
  "data": {
    "id": 1,
    "filename": "abc123.zip",
    "originalName": "resource_pack.zip",
    "isPublic": true,
    "allowedGroups": [3, 4]
  }
}
```

### 5. Update File Permissions (Admin Only)

**Endpoint:** `PATCH /api/fla-loader/files/{id}`

**Description:** Update file permissions

**Authentication:** Admin only

**URL Parameters:**
- `id` (required): File ID

**Request Body:**
```json
{
  "isPublic": false,
  "allowedGroups": [3, 4, 5]
}
```

**Response (200 OK):**
```json
{
  "data": {
    "id": 1,
    "success": true
  }
}
```

### 6. Delete File (Admin Only)

**Endpoint:** `DELETE /api/fla-loader/files/{id}`

**Description:** Delete a file

**Authentication:** Admin only

**URL Parameters:**
- `id` (required): File ID

**Response:** `204 No Content`

### 7. Assign Time-Limited Role (Admin Only)

**Endpoint:** `POST /api/fla-loader/roles`

**Description:** Assign a role to a user with expiration time

**Authentication:** Admin only

**Request Body:**
```json
{
  "userId": 5,
  "groupId": 3,
  "duration": "30d"
}
```

**Duration Options:**
- `7d`: 7 days
- `30d`: 30 days
- `180d`: 180 days
- `1y`: 1 year
- `lifetime`: No expiration

**Response (200 OK):**
```json
{
  "data": {
    "userId": 5,
    "groupId": 3,
    "expiresAt": "2024-02-15T10:00:00Z"
  }
}
```

### 8. Get User Role Assignments (Admin Only)

**Endpoint:** `GET /api/fla-loader/roles/{userId}`

**Description:** Get all role assignments for a user

**Authentication:** Admin only

**URL Parameters:**
- `userId` (required): User ID

**Response (200 OK):**
```json
{
  "data": [
    {
      "id": 1,
      "groupId": 3,
      "expiresAt": "2024-02-15T10:00:00Z",
      "createdAt": "2024-01-15T10:00:00Z"
    }
  ]
}
```

### 9. Reset User HWID (Admin Only)

**Endpoint:** `POST /api/fla-loader/hwid/reset`

**Description:** Reset a user's hardware ID so they can login from a new device

**Authentication:** Admin only

**Request Body:**
```json
{
  "userId": 5
}
```

**Response (200 OK):**
```json
{
  "data": {
    "userId": 5,
    "username": "john_doe",
    "hwidReset": true,
    "message": "HWID reset successfully. User can now login from a new device."
  }
}
```

### 10. Get User HWID Status (Admin Only)

**Endpoint:** `GET /api/fla-loader/hwid/{userId}`

**Description:** Check if a user has an HWID registered and view partial HWID info

**Authentication:** Admin only

**URL Parameters:**
- `userId` (required): User ID

**Response (200 OK):**
```json
{
  "data": {
    "userId": 5,
    "username": "john_doe",
    "hasHwid": true,
    "hwid": "abc12345...",
    "registeredAt": "2024-01-15T10:00:00Z"
  }
}
```

## Usage Examples

### Example 1: External Loader Login Flow

```bash
# Step 1: Login with HWID
curl -X POST https://forum.example.com/api/fla-loader/login \
  -H "Content-Type: application/json" \
  -d '{
    "username": "player123",
    "password": "secure_pass",
    "hwid": "unique-hardware-identifier"
  }'

# Response will include token and groups
# {
#   "data": {
#     "token": "xyz789...",
#     "groups": [...]
#   }
# }

# Step 2: Download file using token
curl -X GET "https://forum.example.com/api/fla-loader/download/1?token=xyz789..." \
  -o resource_pack.zip
```

### Example 2: Admin File Upload

```bash
curl -X POST https://forum.example.com/api/fla-loader/files \
  -H "Cookie: flarum_session=..." \
  -F "file=@resource_pack.zip" \
  -F "isPublic=true" \
  -F 'allowedGroups=[3,4]'
```

### Example 3: Admin Role Assignment

```bash
curl -X POST https://forum.example.com/api/fla-loader/roles \
  -H "Content-Type: application/json" \
  -H "Cookie: flarum_session=..." \
  -d '{
    "userId": 5,
    "groupId": 3,
    "duration": "30d"
  }'
```

## Scheduled Tasks

To automatically expire roles, add this to your crontab:

```bash
# Run every day at midnight
0 0 * * * cd /path/to/flarum && php flarum fla-loader:expire-roles
```

## Security Considerations

1. **Session tokens are valid for 30 days** from creation
2. **Expired tokens are automatically cleaned** when accessed
3. **File access is controlled by group membership**
4. **Role assignments automatically expire** based on configured duration
5. **Admin-only endpoints** require administrator privileges

## Error Codes

- `400 Bad Request`: Invalid request parameters
- `401 Unauthorized`: Authentication required or invalid credentials
- `403 Forbidden`: Insufficient permissions
- `404 Not Found`: Resource not found
- `500 Internal Server Error`: Server-side error
