# 419 CSRF Error Analysis

## Problem

The application is experiencing persistent 419 CSRF errors during Livewire/FilamentPHP requests, preventing normal operation.

## Root Cause Identified

From the debug test (`test-419-debug.mjs`), the pattern is clear:

1. **First Livewire request succeeds** (200 OK)
2. **All subsequent Livewire requests fail with 419**
3. **Livewire is rotating CSRF tokens**, but subsequent requests use old tokens

### Evidence from Logs

```
→ POST http://aureuserp.test/livewire/update
✅ 200 http://aureuserp.test/livewire/update  <-- FIRST REQUEST SUCCEEDS

→ POST http://aureuserp.test/livewire/update
❌ 419 http://aureuserp.test/livewire/update  <-- SUBSEQUENT REQUESTS FAIL

→ POST http://aureuserp.test/livewire/update
❌ 419 http://aureuserp.test/livewire/update  <-- CONTINUES TO FAIL
```

## Contributing Factors

1. **Session Configuration**:
   - `SESSION_DRIVER=database`
   - `SESSION_SAME_SITE=lax`
   - Session lifetime is very long (43200 mins = 30 days)

2. **Additional Error**: `/api/footer/preferences` endpoint returns 500 error
   - This might be related to the footer customizer system
   - May be interfering with session initialization

## Recommended Fixes

### Immediate Fix: Clear Sessions and Restart

```bash
# Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# Clear sessions table
php artisan tinker --execute="DB::table('sessions')->truncate();"

# Restart Laravel Herd
herd restart
```

### Configuration Changes to Test

#### Option 1: Change Session Same-Site to None (for local dev)

In `.env`:
```env
SESSION_SAME_SITE=none
SESSION_SECURE_COOKIE=false
```

#### Option 2: Switch to File-Based Sessions (simpler for development)

In `.env`:
```env
SESSION_DRIVER=file
```

#### Option 3: Publish and Configure Livewire

```bash
php artisan livewire:publish --config
```

Then in `config/livewire.php`, ensure:
```php
'middleware_group' => 'web',
'legacy_model_binding' => false,
```

### Fix the Footer API Error

The `/api/footer/preferences` 500 error needs investigation:

```bash
# Check Laravel logs
tail -50 storage/logs/laravel.log | grep -A 10 "footer/preferences"
```

## FilamentPHP v4 Specific Notes

FilamentPHP v4 uses Livewire v3, which has stricter CSRF token handling. The framework expects:

1. Proper session configuration
2. CSRF token in `X-CSRF-TOKEN` header
3. Same-site cookie policy compatible with SPA-style updates

## Next Steps

1. Try each fix above sequentially
2. After each change, test with:
   ```bash
   curl -I http://aureuserp.test/admin/login
   ```
3. Then retry the browser-based test
4. Once 419 errors are resolved, continue with V2 canvas viewer testing

## V2 Canvas Viewer Status

✅ **Implementation Complete** - All code is ready
⏳ **Testing Blocked** - Cannot test due to 419 errors
🎯 **Next**: Fix CSRF issue, then manually test V2 workflow
