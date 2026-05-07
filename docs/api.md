# API Contract — VPS ZicNet Theme API

Base URL: `https://vps.zicnet.vn/api`

Authentication: Bearer token (from `POST /api/auth/login`)

---

## Public Endpoints (no auth)

### Theme

| Method | Path | Description |
|---|---|---|
| `GET` | `/theme/active` | Active theme variables + custom CSS |

**Response `/theme/active`:**
```json
{
  "slug": "default-indigo",
  "name": "Default Indigo",
  "variables": {
    "primary": "#6366f1",
    "secondary": "#8b5cf6",
    "sidebar_bg_from": "#0f172a",
    "..."
  },
  "custom_css": null,
  "css_vars": ":root { --theme-primary: #6366f1; ... }"
}
```

### Plans

| Method | Path | Description |
|---|---|---|
| `GET` | `/plan-groups` | List active plan groups |
| `GET` | `/plans` | List active plans (filter: `?group=slug`) |
| `GET` | `/plans/{id}` | Plan detail + OS list + billing cycles |

---

## Auth Endpoints

| Method | Path | Body | Description |
|---|---|---|---|
| `POST` | `/auth/login` | `{ email, password }` | Login → token |
| `POST` | `/auth/register` | `{ name, email, password, password_confirmation }` | Register → token |
| `POST` | `/auth/logout` | — | Revoke token |
| `GET` | `/auth/me` | — | Current user info |

**Login Response:**
```json
{
  "token": "1|abcdefgh...",
  "user": {
    "id": 1,
    "name": "John",
    "email": "john@example.com",
    "balance": 500000,
    "balance_formatted": "500.000đ",
    "is_admin": false,
    "email_verified": true,
    "avatar_url": "https://..."
  }
}
```

---

## Authenticated Endpoints

All require header: `Authorization: Bearer {token}`

### Dashboard

| Method | Path | Description |
|---|---|---|
| `GET` | `/dashboard` | Stats + recent orders |

### VPS Management

| Method | Path | Description |
|---|---|---|
| `GET` | `/vps` | List instances (`?tab=active|expiring_soon|expired|deleted|all`) |
| `GET` | `/vps/{id}` | Instance detail + provider realtime info |
| `POST` | `/vps/{id}/action` | `{ action: on|off|restart|cancel }` |
| `GET` | `/vps/{id}/rebuild` | Available OS list |
| `POST` | `/vps/{id}/rebuild` | `{ os_id }` — confirm rebuild |
| `GET` | `/vps/{id}/renew` | Billing cycles for renew |
| `POST` | `/vps/{id}/renew` | `{ billing_cycle }` — confirm renew |
| `GET` | `/vps/{id}/upgrade` | Addon prices |
| `POST` | `/vps/{id}/upgrade` | `{ addon_cpu, addon_ram, addon_disk }` |

### Orders

| Method | Path | Description |
|---|---|---|
| `GET` | `/orders` | Order history (`?per_page=10|20|50`) |
| `POST` | `/orders` | Create order |
| `POST` | `/orders/apply-coupon` | Validate coupon `{ code, plan_id, amount }` |

**Create Order Body:**
```json
{
  "plan_id": 1,
  "billing_cycle": "monthly",
  "os": "ubuntu-22.04",
  "quantity": 1,
  "state": "",
  "coupon_code": "GIAMGIA10"
}
```

### Billing

| Method | Path | Description |
|---|---|---|
| `GET` | `/billing` | Balance + transactions + recent deposits |
| `GET` | `/billing/methods` | Active payment methods |
| `POST` | `/billing/deposit` | Create deposit `{ amount, payment_method_id }` |
| `GET` | `/billing/deposit/{id}` | Poll deposit status |
| `POST` | `/billing/deposit/{id}/cancel` | Cancel deposit |

**Deposit Status Response:**
```json
{
  "status": "pending|completed|expired|cancelled",
  "actual_amount": 500000,
  "actual_amount_formatted": "500.000đ",
  "remaining_seconds": 1234,
  "new_balance": 1500000
}
```

### Tickets

| Method | Path | Description |
|---|---|---|
| `GET` | `/tickets` | List tickets |
| `POST` | `/tickets` | Create `{ subject, message, priority? }` |
| `GET` | `/tickets/{id}` | Ticket detail + messages |
| `POST` | `/tickets/{id}/reply` | Reply `{ message }` |

### Profile

| Method | Path | Description |
|---|---|---|
| `GET` | `/profile` | User profile |
| `PUT` | `/profile` | Update `{ name }` |
| `POST` | `/profile/password` | Change password `{ current_password, password, password_confirmation }` |

---

## Error Responses

All errors return JSON:
```json
{
  "message": "Error description"
}
```

Validation errors (422):
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "field": ["Error message"]
  }
}
```

HTTP Status codes:
- `200` OK
- `201` Created
- `401` Unauthorized (missing/invalid token)
- `403` Forbidden
- `404` Not found
- `409` Conflict (e.g. duplicate pending deposit)
- `422` Validation / business logic error
- `500` Server error
