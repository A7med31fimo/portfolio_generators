# NextDev API — Laravel 12 Backend

A clean-architecture REST API for the NextDev portfolio SaaS, built with
Laravel 12, Sanctum token authentication, and MySQL.

```
Frontend (Next.js)
       ↓
Controller       ← validates input via FormRequest, returns JsonResponse
       ↓
Service          ← business logic, transactions, domain rules
       ↓
Repository       ← data access, query logic (interface-bound)
       ↓
MySQL
```

---

## Quick Start

### 1. Install dependencies

```bash
composer install
```

### 2. Environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` — set `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` for your MySQL instance.

### 3. Create the database

```bash
mysql -u root -p -e "CREATE DATABASE nextdev CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### 4. Run migrations + seed

```bash
php artisan migrate --seed
```

This creates `users`, `profiles`, `personal_access_tokens`, and
`password_reset_tokens` tables, then seeds 11 test users
(`dev@nextdev.io` / `Password1!` plus 10 random factory users).

### 5. Start the server

```bash
php artisan serve
# API available at http://localhost:8000/api/v1
```

### 6. Verify

```bash
curl http://localhost:8000/api/v1/health
```

```json
{
  "status": true,
  "message": "NextDev API is running",
  "data": { "version": "v1", "environment": "local", "timestamp": "..." }
}
```

---

## Folder Structure

```
app/
  Console/Commands/           ← Custom artisan commands (Phase 3+)
  Exceptions/
    Handler.php                ← Global exception → JSON envelope converter
    BusinessException.php      ← Domain-level exception (422/409/403 etc.)
  Http/
    Controllers/Api/V1/Auth/
      AuthController.php       ← register, login, logout, me, forgot/reset password
    Middleware/
      ForceJsonResponse.php    ← Forces Accept: application/json on all API routes
      SetLocale.php            ← Reads X-Locale header from Next.js
    Requests/Auth/
      RegisterRequest.php      ← Validation + reserved-username check
      LoginRequest.php
      ForgotPasswordRequest.php
      ResetPasswordRequest.php
    Resources/Api/V1/
      UserResource.php         ← API response shape for User
      ProfileResource.php      ← API response shape for Profile
  Models/
    User.php                   ← hasOne(Profile), HasApiTokens
    Profile.php                ← belongsTo(User)
  Repositories/
    Contracts/                 ← Interfaces — controllers/services depend on these
      UserRepositoryInterface.php
      ProfileRepositoryInterface.php
    Eloquent/                  ← Concrete implementations
      EloquentUserRepository.php
      EloquentProfileRepository.php
  Services/
    AuthService.php            ← register/login/logout/password-reset business logic
    UserService.php            ← username availability + validation logic
  Providers/
    AppServiceProvider.php     ← Binds interfaces → implementations, rate limiters

config/
  api.php                      ← API version, reserved usernames, rate limits
  auth.php                     ← Guards, providers, password broker
  cors.php                     ← CORS for Next.js frontend
  sanctum.php                  ← Stateful domains, token expiration

database/
  migrations/                  ← users, profiles, personal_access_tokens, password_reset_tokens
  factories/                   ← UserFactory, ProfileFactory
  seeders/                     ← DatabaseSeeder, UserSeeder

routes/
  api.php                      ← All /api/v1/* routes, rate-limited groups

docs/
  API.md                       ← Full endpoint reference with request/response examples
  nextjs-integration/
    api-client.ts              ← Drop-in fetch wrapper for Next.js
    auth.service.ts            ← Drop-in auth service for Next.js
    examples.tsx                ← Copy-paste component examples for every endpoint
```

---

## Architecture Decisions

### Why Repository + Service layers (not just fat controllers)?

- **Repositories** isolate Eloquent query logic behind an interface. Swapping
  MySQL for a different store, or adding query caching, touches one file —
  not every controller that queries users.
- **Services** hold business rules (password hashing, transaction boundaries,
  username reservation checks) independent of HTTP. They're directly unit-testable
  without booting a request/response cycle.
- **Controllers** stay thin: validate → call service → format response.

### Why a custom `BusinessException`?

Domain errors (e.g. "username taken", "invalid reset token") aren't validation
errors (422 from a FormRequest) — they're discovered *during* business logic,
often inside a transaction. `BusinessException` carries a status code and an
error `code` string, and the global Handler renders it consistently with every
other API error.

### Why does `forgotPassword` always return success?

Returning a different response for "email not found" vs "email found, link sent"
lets an attacker enumerate which emails have accounts. The endpoint always
returns 200 with the same message regardless of whether the email exists.

### Why does password reset revoke all tokens?

If an account was compromised, the attacker may hold a valid Sanctum token.
Resetting the password should immediately invalidate every existing session —
not just block future logins with the old password.

### Token expiration strategy

- Default: 30 days (`SANCTUM_TOKEN_EXPIRY=43200` minutes)
- "Remember me" checked at login: token never expires (`expires_at: null`)
- Each token is named by `device_name` — groundwork for a future
  "manage active sessions" dashboard page (revoke a single device).

---

## Testing the API

```bash
# Register
curl -X POST http://localhost:8000/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "Jane Smith",
    "username": "janesmith",
    "email": "jane@example.com",
    "password": "Password1!",
    "password_confirmation": "Password1!"
  }'

# Login
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{ "email": "dev@nextdev.io", "password": "Password1!" }'

# Use the returned token:
curl http://localhost:8000/api/v1/auth/me \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"

# Check username availability
curl "http://localhost:8000/api/v1/auth/check-username?username=janesmith"

# Logout
curl -X POST http://localhost:8000/api/v1/auth/logout \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

## Next.js Integration

Three ready-to-copy files in `docs/nextjs-integration/`:

1. **`api-client.ts`** → copy to `services/api-client.ts` in your Next.js project.
   Handles token storage, headers, and the Laravel response envelope.

2. **`auth.service.ts`** → copy to `services/auth.service.ts`.
   Exports `register()`, `login()`, `logout()`, `getMe()`, `forgotPassword()`,
   `resetPassword()`, `checkUsername()` — typed functions matching every endpoint.

3. **`examples.tsx`** → reference only (commented code blocks).
   Full working component examples for every auth flow: register form, login
   form, logout button, server-side `getCurrentUser`, forgot/reset password,
   live username availability.

Add to your Next.js `.env.local`:

```
NEXT_PUBLIC_API_URL=http://localhost:8000/api/v1
```

See `docs/API.md` for the complete endpoint reference.

---

## What's NOT in Phase 2 (by design)

- Dashboard content APIs (projects, themes, skills) — Phase 3
- File upload / avatar storage — Phase 3
- Email verification enforcement on protected routes — Phase 3
- OAuth (Google/GitHub) — requires `laravel/socialite`, not yet installed
- Subscriptions / billing — Phase 5
