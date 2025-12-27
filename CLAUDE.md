# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

One-Time Link is a secure link sharing system where links automatically expire after being accessed a specified number of times. Used for sensitive information sharing, email verification, password reset, and temporary authorization.

## Tech Stack

- **Frontend**: Vue 3 + TypeScript + Vite + Ant Design Vue 4 + Pinia
- **Backend**: ThinkPHP 6 (PHP 7.2+)
- **Database**: MySQL 5.7+
- **Cache**: Redis 5.0+

## Development Commands

### Frontend (in `frontend/`)
```bash
npm install          # Install dependencies
npm run dev          # Start dev server at http://localhost:3000
npm run build        # Production build to dist/
```

### Backend (in `backend/`)
```bash
composer install     # Install dependencies
php think run        # Start dev server at http://localhost:8080
php think queue:listen  # Start queue worker for async notifications
```

### Database
Import `database/schema.sql` to MySQL to create all tables.

## Architecture

### Frontend Structure
```
frontend/src/
├── api/           # API layer (types.ts, request.ts with Axios interceptors, onetime.ts)
├── store/modules/ # Pinia stores (onetime.ts for link state management)
├── views/         # Pages (Home, CreateLink, LinkManage, LinkDetail, LinkAccess)
├── layouts/       # DefaultLayout (with nav), BlankLayout (for verify page)
├── utils/         # format.ts (time/status), validate.ts, storage.ts
└── components/    # Reusable components
```

### Backend Structure
```
backend/app/
├── controller/    # LinkController, LogController (API endpoints)
├── service/       # TokenService (core), EncryptService, LogService
├── model/         # OnetimeLink, OnetimeLinkLog, OnetimeLinkNotification
├── middleware/    # CorsMiddleware, RateLimitMiddleware
└── validate/      # LinkValidate (form validation rules)
```

### Data Flow
1. **Create**: Frontend form → `POST /api/onetime/create` → TokenService.createLink() → MySQL + Redis cache
2. **Access**: `/verify/:token` → TokenService.consumeToken() → verify → increment visits → log access → return content

### Key Business Logic
- Token generation: UUID v4 via `ramsey/uuid`
- Data encryption: AES-256-CBC in TokenService (encrypt/decrypt methods)
- Cache strategy: Redis with TTL matching expire_time, prefix `onetime:token:`
- Rate limiting: IP-based, 60 requests/minute window

## API Routes (defined in `backend/route/app.php`)
```
POST   /api/onetime/create        # Create link
GET    /api/onetime/verify/:token # Verify without consuming
GET    /api/onetime/access/:token # Access and consume token
POST   /api/onetime/revoke        # Revoke link
GET    /api/onetime/list          # List links with filters
GET    /api/onetime/detail/:id    # Link detail with logs
GET    /api/onetime/statistics    # Usage statistics
```

## Database Tables
- `onetime_links` - Main table (token, content_type, max_visits, status, expire_time)
- `onetime_link_logs` - Access logs (ip, user_agent, visit_result, geo info)
- `onetime_link_notifications` - Notification records

## Link Status Values
1=Active, 2=Used, 3=Expired, 4=Revoked

## Content Types
email_verify, password_reset, magic_login, secret_share, file_download, qrcode_auth, invite_code, custom

## Environment Configuration
- Frontend: `.env.development` / `.env.production` with `VITE_API_BASE_URL`
- Vite proxy configured in `vite.config.ts` for `/api` routes
