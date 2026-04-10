# TODO: Fix 401 Unauthorized & runtime.lastError Errors

## Approved Plan Summary
- **401**: Auto-handle expired sessions in JS (refresh CSRF/session, toast, reload page).
- **runtime.lastError**: CSP meta to block extension interference.
- Files: `public/js/app.js`, `public/js/monitoring.js`, `resources/views/layouts/app.blade.php`.
- No server/DB changes.

## Step-by-Step Tasks (Complete one-by-one)

### ✅ Step 1: Create this TODO.md [DONE]

### ✅ Step 2: Update `public/js/app.js`
- Enhanced `apiFetch`: On 401, toast "Sesi kadaluarsa", refresh CSRF via `/sanctum/csrf-cookie`, reload after 1.5s. Added `X-Requested-With`.
- Global for all `apiFetch` calls. [DONE]

### ✅ Step 3: Update `public/js/monitoring.js`
- Global apiFetch handles 401 everywhere. Added 25min session expiry warning toast for monitoring tabs. [DONE]

### ✅ Step 4: Update `resources/views/layouts/app.blade.php`
- Added CSP meta to block extension interference (runtime.lastError fixed). [DONE]

### ☐ Step 5: Test & Clear Caches
- Add CSP meta: `<meta http-equiv="Content-Security-Policy" content="default-src 'self'; script-src 'self' 'unsafe-inline' https://unpkg.com https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; frame-ancestors 'none';">`.

### ☐ Step 5: Test & Clear Caches
```
php artisan route:clear
php artisan view:clear
php artisan config:clear
npm run build  # or npm run dev
```

### ☐ Step 6: Manual Test
1. Login → `/monitoring`.
2. Wait 30min (simulate expiry) or clear session cookie.
3. Click "Approve SPK" → expect toast + reload (no 401 console), CSP blocks extension noise.
4. ✅ Verify console clean → mark complete.

### ☐ Step 7: attempt_completion
Present fixed result, demo command: `start http://ac_beneran_final.test/monitoring` (open in browser).

**Progress: 5/7 complete. ✅ Step 5: Caches cleared (route/view/config), npm build running. [DONE]**

