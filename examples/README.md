# Example Client Implementations

This directory contains example implementations showing how to integrate with the Fla-loader API from external applications.

## Available Examples

### 1. Python Client (`python_client.py`)

A Python implementation demonstrating:
- User authentication
- Session token management
- Group/role checking
- File downloading with progress

**Requirements:**
```bash
pip install requests
```

**Usage:**
```bash
python python_client.py
```

Edit the configuration variables at the top of the file before running.

### 2. Node.js Client (`nodejs_client.js`)

A Node.js implementation demonstrating the same features as the Python client.

**Requirements:**
```bash
npm install axios
```

**Usage:**
```bash
node nodejs_client.js
```

Edit the configuration variables at the top of the file before running.

## Integration Guide

### Basic Flow

1. **Login**: Authenticate with username/password
2. **Store Token**: Save the session token (valid for 30 days)
3. **Check Permissions**: Verify user has required group/role
4. **Download Files**: Download files using the token

### Example Integration

```python
# Python example
client = FlaLoaderClient('https://forum.example.com')
if client.login('username', 'password'):
    if client.has_group('VIP'):
        client.download_file(1, './resource_pack.zip')
```

```javascript
// Node.js example
const client = new FlaLoaderClient('https://forum.example.com');
await client.login('username', 'password');
if (client.hasGroup('VIP')) {
    await client.downloadFile(1, './resource_pack.zip');
}
```

## Security Considerations

1. **Store tokens securely**: Don't hardcode credentials
2. **Use environment variables**: Store sensitive data in env files
3. **Handle token expiration**: Check token validity before use
4. **Use HTTPS**: Always use secure connections in production

## Extending the Examples

You can extend these examples with:
- Token caching/storage
- Automatic token refresh
- Multiple file downloads
- Error retry logic
- Progress callbacks
- Custom logging

## Support

For more information, see:
- [API.md](../API.md) - Complete API documentation
- [SETUP.md](../SETUP.md) - Installation guide
- [README.md](../README.md) - Project overview
