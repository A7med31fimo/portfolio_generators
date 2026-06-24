# NextDev API — Authentication Endpoints

Base URL: `http://localhost:8000/api/v1`
Production: `https://api.nextdev.io/api/v1`

All responses follow this envelope:

```json
// Success
{ "status": true,  "message": "...", "data": {} }

// Error
{ "status": false, "message": "...", "errors": {} }
```

Authenticated endpoints require: `Authorization: Bearer {token}`

---

## POST /auth/register

Create a new account. Returns a token immediately — no separate login step needed.

**Rate limit:** 5 requests/minute per IP

### Request

```json
{
  "name": "Jane Smith",
  "username": "janesmith",
  "email": "jane@example.com",
  "password": "Password1!",
  "password_confirmation": "Password1!",
  "locale": "en"
}
```

| Field | Rules |
|---|---|
| `name` | required, string, 2–60 chars |
| `username` | required, 3–30 chars, lowercase letters/numbers/hyphens only, no leading/trailing hyphen, unique, not reserved |
| `email` | required, valid email, unique |
| `password` | required, 8–72 chars, 1 uppercase, 1 number, must match confirmation |
| `locale` | optional, `en` or `ar` |

### Response — 201 Created

```json
{
  "status": true,
  "message": "Account created successfully.",
  "data": {
    "user": {
      "id": 1,
      "name": "Jane Smith",
      "username": "janesmith",
      "email": "jane@example.com",
      "email_verified": false,
      "email_verified_at": null,
      "created_at": "2026-06-20T10:00:00.000000Z",
      "profile": {
        "bio": null,
        "headline": null,
        "avatar": null,
        "location": null,
        "phone": null,
        "locale": "en",
        "theme": "classic",
        "accent_color": "#00df9a",
        "is_published": false
      }
    },
    "token": "1|aBcDeFgHiJkLmNoPqRsTuVwXyZ...",
    "expires_at": "2026-07-20T10:00:00.000000Z"
  }
}
```

### Response — 422 Validation Error

```json
{
  "status": false,
  "message": "Validation failed.",
  "errors": {
    "username": ["This username is already taken."],
    "email": ["An account with this email already exists."]
  }
}
```

---

## POST /auth/login

**Rate limit:** 10 requests/minute per IP

### Request

```json
{
  "email": "jane@example.com",
  "password": "Password1!",
  "remember": false,
  "device_name": "Next.js Web"
}
```

| Field | Rules |
|---|---|
| `email` | required, valid email |
| `password` | required |
| `remember` | optional boolean — if `true`, token never expires |
| `device_name` | optional, defaults to `"web"` — labels the token for the "active sessions" UI |

### Response — 200 OK

```json
{
  "status": true,
  "message": "Login successful.",
  "data": {
    "user": { "...": "same shape as register" },
    "token": "2|xYz...",
    "expires_at": "2026-07-20T10:00:00.000000Z"
  }
}
```

### Response — 401 Unauthorized

```json
{
  "status": false,
  "message": "These credentials do not match our records.",
  "errors": { "code": "INVALID_CREDENTIALS" }
}
```

Note: the message is intentionally generic — it does not reveal whether the
email exists, to prevent account enumeration.

---

## POST /auth/logout

**Requires authentication.**

### Request

```json
{ "all_devices": false }
```

Set `all_devices: true` to revoke every token for this user (sign out everywhere).

### Response — 200 OK

```json
{
  "status": true,
  "message": "Logged out successfully.",
  "data": null
}
```

---

## GET /auth/me

**Requires authentication.**

Returns the current user with their profile.

### Response — 200 OK

```json
{
  "status": true,
  "message": "OK",
  "data": {
    "user": { "...": "full user + profile" }
  }
}
```

### Response — 401 Unauthorized

```json
{
  "status": false,
  "message": "Unauthenticated. Please log in.",
  "errors": {}
}
```

---

## POST /auth/forgot-password

**Rate limit:** 3 requests/minute per IP

### Request

```json
{ "email": "jane@example.com" }
```

### Response — 200 OK

Always returns success, regardless of whether the email exists
(prevents email enumeration attacks).

```json
{
  "status": true,
  "message": "If an account with that email exists, a password reset link has been sent.",
  "data": null
}
```

---

## POST /auth/reset-password

### Request

```json
{
  "token": "the-token-from-the-reset-email-link",
  "email": "jane@example.com",
  "password": "NewPassword1!",
  "password_confirmation": "NewPassword1!"
}
```

### Response — 200 OK

```json
{
  "status": true,
  "message": "Password reset successfully. Please log in with your new password.",
  "data": null
}
```

Side effect: **all existing tokens for this user are revoked.** The user must
log in again on every device after a password reset.

### Response — 422 Invalid Token

```json
{
  "status": false,
  "message": "Invalid or expired password reset token. Please request a new one.",
  "errors": { "code": "INVALID_RESET_TOKEN" }
}
```

---

## GET /auth/check-username

Public endpoint. Used for live availability checking on the registration form.

### Request

```
GET /api/v1/auth/check-username?username=janesmith
```

### Response — 200 OK

```json
{
  "status": true,
  "message": "Username is available.",
  "data": {
    "available": true,
    "reason": null
  }
}
```

Possible `reason` values when `available: false`:

| Reason | Meaning |
|---|---|
| `taken` | Already registered by another user |
| `reserved` | In the reserved usernames list (see below) |
| `too_short` | Less than 3 characters |
| `too_long` | More than 30 characters |
| `invalid_chars` | Contains characters other than lowercase letters, numbers, hyphens |
| `invalid_format` | Starts or ends with a hyphen |

---

## Email Verification

`User implements MustVerifyEmail`, so Laravel automatically fires a
verification email on every registration (via the `Registered` event).
Two endpoints support this flow:

### GET /auth/email/verify/{id}/{hash}

**Requires authentication + a valid signed URL** (Laravel validates the
`expires` and `signature` query params automatically via the `signed`
middleware).

The email link points at the **Next.js frontend**
(`{FRONTEND_URL}/en/verify-email/{id}/{hash}?expires=...&signature=...`),
not directly at this API — a signed GET link can't carry an
`Authorization: Bearer` header, so a frontend page is required to read the
stored token and relay the request here.

Frontend page responsibility (`app/[locale]/verify-email/[id]/[hash]/page.tsx`):
1. Read `id`, `hash` from the path and `expires`, `signature` from the query string
2. Call `GET /api/v1/auth/email/verify/{id}/{hash}?expires=...&signature=...`
   with `Authorization: Bearer {token}` from local storage
3. Show success/error based on the response

```json
{
  "status": true,
  "message": "Email verified successfully.",
  "data": null
}
```

### POST /auth/email/verification-notification

**Requires authentication.** Resends the verification email — useful for a
"Resend verification email" button in the dashboard for unverified users.

**Rate limit:** 6 requests/minute (Laravel's framework default for this action)

```json
{
  "status": true,
  "message": "Verification email sent.",
  "data": null
}
```

---

## Reserved Usernames

Defined in `config/api.php` → `reserved_usernames`. These cannot be
registered by any user because they collide with app routes or i18n
locale prefixes:

```
api, webhook, health, dashboard, login, register, logout,
forgot-password, reset-password, profile, projects, themes,
settings, onboarding, publish, admin, staff, moderator, mod,
support, en, ar, fr, de, es, pt, zh, ja, ko, ru, nextdev,
about, pricing, blog, docs, help, contact, privacy, terms,
security, status, careers, press, changelog, www, mail, app,
root, system, null, undefined, anonymous, official
```

**When adding a new i18n locale to the Next.js frontend, add the locale
code here too** — otherwise a user could register a username that shadows
the locale route segment (e.g. `/fr/dashboard` breaking if someone registers `fr`).

---

## Authentication Flow Summary

```
1. POST /auth/register  →  receive token  →  store in localStorage
2. Every subsequent request: Authorization: Bearer {token}
3. GET  /auth/me        →  verify token is still valid (call on app load)
4. POST /auth/logout    →  revoke token  →  clear localStorage
```

## Error Status Codes

| Code | Meaning |
|---|---|
| 200 | Success |
| 201 | Resource created (register) |
| 401 | Unauthenticated — invalid/missing/expired token |
| 403 | Forbidden — authenticated but not authorized |
| 404 | Resource not found |
| 405 | HTTP method not allowed |
| 409 | Conflict — email or username already taken |
| 422 | Validation failed — check `errors` object |
| 429 | Rate limit exceeded |
| 500 | Server error |
