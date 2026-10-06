# Account Architecture

## 1. Overview
The W3HealthChecker Account Architecture enables persistent user identity while maintaining complete respect for anonymous scanning. Anonymous users can scan, compare, and use all free specialized tools without registering. Once users register, they unlock persistent workspaces, multi-project categorization, historical scan tracking, automated monitoring, and privacy-controlled data synchronization.

## 2. Authentication Flow
- **Registration (`/api/auth/register`)**:
  - Validates name, unique lowercase email, and confirmed secure password (min 8 chars).
  - Automatically provisions a default "Personal Workspace" project for immediate site organization.
  - Issues a freshly regenerated session and signs the user in immediately.
- **Login (`/api/auth/login`)**:
  - Throttled defensively against credential stuffing attacks.
  - Validates credentials and regenerates the session ID to prevent session fixation.
  - Supports optional persistent cookie sessions ("Remember Me").
- **Logout (`/api/auth/logout`)**:
  - Invalidates the active web session and regenerates CSRF tokens.
- **Current User Profile (`/api/auth/me`)**:
  - Returns authenticated user details and aggregated statistics.

## 3. Account Security Controls
- **Password Hashing**: Bcrypt with application work factor.
- **Anti-Enumeration**: Standardized error messages prevent account enumeration on credential failure.
- **CSRF Protection**: All auth mutation endpoints enforce Laravel CSRF token verification.
- **Rate Limiting**: Rate limits applied to authentication endpoints to suppress brute-force attempts.
- **Strict Authorization**: User ID filtering is enforced on all workspace and project models at the query level.
