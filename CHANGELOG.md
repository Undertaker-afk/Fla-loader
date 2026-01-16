# Changelog

All notable changes to the Fla-loader extension will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2024-01-16

### Added
- External login API endpoint for authentication via external loaders
- Session token generation and management (30-day expiration)
- User roles/groups returned in login response
- Time-limited role assignment system with configurable durations:
  - 7 days
  - 30 days
  - 180 days
  - 1 year
  - Lifetime (no expiration)
- Database migrations for:
  - File storage metadata
  - Role assignment tracking
  - Session token management
- File upload and download system with features:
  - Admin file upload interface
  - Role/group-based access control
  - Public and private file visibility options
  - Up to 4 files can be uploaded (no hard limit)
  - Token-based authentication for external loaders
- Admin panel interface for:
  - File management
  - Role assignment
  - Permission configuration
- Frontend components:
  - "Download" navbar link
  - Download page for users
  - Admin settings page
- Console command for expiring roles: `php flarum fla-loader:expire-roles`
- Comprehensive API documentation
- Setup and installation guide
- MIT License

### Security
- Session tokens expire after 30 days
- File access controlled by user group membership
- Admin-only endpoints for sensitive operations
- Automatic role expiration based on configured duration

### Documentation
- API.md - Complete API endpoint documentation
- SETUP.md - Installation and configuration guide
- README.md - Overview and quick start
- CHANGELOG.md - Version history

## [Unreleased]

### Planned Features
- Web UI for viewing role expiration times
- Bulk role assignment
- File upload progress indicator
- File versioning
- Download statistics and analytics
- Email notifications for role expiration
- Custom expiration dates (in addition to preset durations)
- File categories/tags
- Search and filter files

### Known Issues
- None reported yet

---

## Version History

- **1.0.0** (2024-01-16): Initial release
