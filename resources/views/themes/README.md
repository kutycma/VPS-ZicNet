# Theme Development Guide

## Tổng quan

Mỗi "theme" là một thư mục con trong `resources/views/themes/`. Laravel sẽ tự động load views từ theme đang active (được cấu hình trong bảng `themes`).

## Cách hoạt động

```
ThemeServiceProvider
  └── prependLocation("resources/views/themes/{active_slug}/")

Controller gọi:      view('client.dashboard')
Laravel tìm theo thứ tự:
  1. resources/views/themes/default/client/dashboard.blade.php   ← theme
  2. resources/views/client/dashboard.blade.php                  ← fallback
  3. 404 nếu không tìm thấy
```

Admin views (`resources/views/admin/`) **không bao giờ bị override** vì không có file nào trong theme folder tương ứng.

---

## Cách tạo Blade Theme mới

### 1. Copy theme mặc định
```bash
cp -r resources/views/themes/default resources/views/themes/my-theme
```

### 2. Chỉnh sửa views theo ý muốn
Cấu trúc thư mục cần giữ nguyên:
```
themes/my-theme/
├── layouts/
│   ├── client.blade.php    ← Layout chính (sidebar, navbar, footer)
│   └── auth.blade.php      ← Layout trang login/register
├── client/
│   ├── dashboard.blade.php
│   ├── profile.blade.php
│   ├── billing/
│   ├── orders/
│   ├── tickets/
│   └── vps/
├── auth/
│   ├── login.blade.php
│   ├── register.blade.php
│   ├── verify.blade.php
│   └── passwords/
├── components/
│   └── telegram-bubble.blade.php
└── welcome.blade.php       ← Landing page
```

Bạn **không bắt buộc** phải có tất cả files — nếu một file không tồn tại trong theme, Laravel sẽ dùng file gốc trong `resources/views/`.

### 3. Đăng ký theme trong database
```bash
# Thêm record vào bảng themes hoặc dùng Admin panel
INSERT INTO themes (name, slug, is_active, is_default, variables, created_at, updated_at)
VALUES ('My Theme', 'my-theme', 0, 0, '{}', NOW(), NOW());
```

Hoặc vào **Admin → Themes → Tạo mới**, nhập:
- Name: `My Theme`
- Slug: `my-theme`

### 4. Kích hoạt
Vào **Admin → Themes** → nhấn **Activate**.

---

## Cách tạo Theme bằng Framework khác (Next.js, Vue, React...)

Nếu không muốn dùng Blade, bạn có thể build theme bằng bất kỳ framework nào. Laravel đã expose đầy đủ **REST API**.

### Bước 1: Lấy theme variables

```js
// Fetch active theme on app init
const res = await fetch('https://vps.zicnet.vn/api/theme/active');
const theme = await res.json();

// Apply CSS variables to :root
const root = document.documentElement;
Object.entries(theme.variables).forEach(([key, val]) => {
  root.style.setProperty(`--theme-${key.replace(/_/g, '-')}`, val);
});

// Inject custom CSS nếu có
if (theme.custom_css) {
  const style = document.createElement('style');
  style.textContent = theme.custom_css;
  document.head.appendChild(style);
}
```

### Bước 2: Authenticate

```js
// Login → nhận token
const res = await fetch('/api/auth/login', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ email, password }),
});
const { token } = await res.json();
localStorage.setItem('api_token', token);

// Dùng token cho mọi request sau
const headers = {
  'Authorization': `Bearer ${localStorage.getItem('api_token')}`,
  'Content-Type': 'application/json',
};
```

### Bước 3: Xem đầy đủ API tại `docs/api.md`

---

## CSS Variables

Theme variables được lưu dưới dạng JSON trong `themes.variables`. Sau khi inject vào `:root`, bạn dùng như sau:

```css
/* Màu chính */
color: var(--theme-primary);
background: var(--theme-sidebar-bg-from);

/* Font */
font-family: var(--theme-font-family);
```

Xem danh sách đầy đủ tại `ThemeService::getDefaultVariables()` hoặc gọi `GET /api/theme/active`.

---

## Lưu ý

- Blade themes đọc `CSRF token` từ `<meta name="csrf-token">` — không cần thêm gì.
- API themes dùng Sanctum Bearer Token (`Authorization: Bearer {token}`).
- Admin panel (`/admin/*`) luôn dùng giao diện Blade cố định, không bị ảnh hưởng bởi theme.
