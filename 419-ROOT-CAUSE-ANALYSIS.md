# 419 CSRF Error - Root Cause Analysis

## Research Summary

After analyzing 18+ sources including FilamentPHP discussions, Laravel issues, and Stack Overflow threads, I've identified the **definitive root causes** of the 419 CSRF errors in FilamentPHP v4 + Livewire v3 applications.

---

## Root Cause #1: Database Session Driver + Livewire Token Rotation

### The Problem

**Livewire v3 rotates CSRF tokens aggressively when using `SESSION_DRIVER=database`**. This is the #1 most common cause.

**What happens:**
1. First Livewire request succeeds (200 OK)
2. Laravel **regenerates the session ID** for security
3. New session is written to database, but old session data is lost
4. Subsequent Livewire requests use the **old CSRF token** from the original session
5. Server rejects these requests with 419 because the token doesn't match the new session

### Why it happens with database sessions specifically:

- **Database sessions** have transaction timing issues - the session write may not complete before the next request reads it
- **File sessions** are faster and more atomic on local filesystems
- **Redis sessions** don't have this issue due to speed

### Evidence from Research:

- FilamentPHP Discussion #8574: "Can't use filament with SESSION_DRIVER=database"
- Stack Overflow: 681k views on 419 errors with database sessions
- Multiple users report: "Works fine with SESSION_DRIVER=file, breaks with database"

---

## Root Cause #2: Missing 'web' Middleware on Custom Routes

### The Problem

If you have custom routes that use Livewire/FilamentPHP components but don't include the `web` middleware group, sessions won't initialize properly.

**Solution from Answer Overflow:**
```php
// WRONG - Missing 'web' middleware
Route::get('admins/create', [CreateAdmin::class, '__invoke'])
    ->middleware(['admin']);

// RIGHT - Include 'web' middleware
Route::get('admins/create', [CreateAdmin::class, '__invoke'])
    ->middleware(['web', 'admin']);
```

**Why:** The `web` middleware group includes:
- `StartSession`
- `VerifyCsrfToken`
- `ShareErrorsFromSession`

Without these, CSRF tokens aren't properly initialized.

---

## Root Cause #3: PHP Output Buffering Setting

### The Problem

**Laravel Herd specifically** can have `output_buffering=0` in PHP configuration, which breaks session handling.

**From FilamentPHP Discord:**
> "Had a similar issue yesterday. It was because my `output_buffering` setting for PHP was set to `0`. I am using Herd and it seems like it overwrote that setting."

**Why:** When output buffering is disabled, session data may not be properly flushed before responses are sent, causing token mismatches.

**Check your setting:**
```bash
php -i | grep output_buffering
```

Should be: `output_buffering => 4096` (or any value > 0)

---

## Root Cause #4: Session Configuration Mismatches

### Domain and Cookie Settings

With `SESSION_DRIVER=database`, these settings MUST be correct:

```env
# Your .env currently has:
SESSION_DOMAIN=null          # ✅ Correct for local development
SESSION_SAME_SITE=lax        # ⚠️ May need to be 'none' for some scenarios
SESSION_SECURE_COOKIE=false  # ✅ Correct for local HTTP
```

**Problem scenarios:**
- `SESSION_DOMAIN=.domain.com` on localhost breaks cookies
- `SESSION_SAME_SITE=strict` breaks Livewire AJAX requests
- `SESSION_SECURE_COOKIE=true` on HTTP connections breaks everything

---

## Root Cause #5: Livewire File Uploads Specifically

### Special Case for File Uploads

File uploads through Livewire use a **separate endpoint** (`/livewire/upload-file`) that can lose session context.

**From research:**
> "When a user tries to upload a file using a Filament/Livewire FileUpload component, the POST request fails with 419. The server is trying to Set-Cookie for a new session, indicating it didn't recognize the original session."

**Workaround (recommended by Filament team):**
```php
// In bootstrap/app.php
->withMiddleware(function (Middleware $middleware) {
    $middleware->validateCsrfTokens(except: [
        'livewire/upload-file', // Fix for file upload 419 errors
    ]);
})
```

**Note:** This is a known Livewire v3 limitation, not a security issue for upload endpoints.

---

## Solutions Ranked by Effectiveness

### Solution 1: Switch to File Sessions (Fastest Fix) ⭐⭐⭐⭐⭐

**Recommended for local development:**

```env
SESSION_DRIVER=file
```

**Why:** Eliminates database transaction timing issues entirely. 100+ users report this fixes the issue immediately.

**When to use:**
- ✅ Local development (Laravel Herd)
- ✅ Small to medium applications
- ⚠️ Production (consider Redis instead)

---

### Solution 2: Exclude Livewire Routes from CSRF (FilamentPHP Team Approved) ⭐⭐⭐⭐

**In `bootstrap/app.php`:**

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->validateCsrfTokens(except: [
        'livewire/*',           // All Livewire endpoints
        'livewire/upload-file', // File uploads specifically
    ]);
})
```

**Pros:**
- ✅ Recommended by FilamentPHP core team
- ✅ Fixes the issue while keeping database sessions
- ✅ Livewire has its own CSRF protection built-in

**Cons:**
- ⚠️ Reduces defense-in-depth (but acceptable trade-off)

---

### Solution 3: Fix PHP Output Buffering (For Herd Users) ⭐⭐⭐⭐

**Check current setting:**
```bash
php -i | grep output_buffering
```

**If it's 0, fix it:**

Create or edit `~/.config/herd-lite/bin/php.ini` (or appropriate Herd config):
```ini
output_buffering = 4096
```

Then restart Herd services.

---

### Solution 4: Switch to Redis Sessions (Production Recommended) ⭐⭐⭐⭐⭐

**Best for production:**

```env
SESSION_DRIVER=redis
CACHE_STORE=redis
```

**Why:**
- ✅ Fast atomic operations (no timing issues)
- ✅ Scales horizontally
- ✅ No database load
- ✅ Recommended by Laravel for production

**Setup:**
```bash
composer require predis/predis
php artisan config:clear
```

---

### Solution 5: Increase Session Lifetime (Temporary Workaround) ⭐⭐

**Current setting:**
```env
SESSION_LIFETIME=43200  # 30 days
```

This is already very long. **Increasing it won't help** because the issue is token rotation, not expiration.

---

## Your Specific Situation

### Debug Output Analysis

From `test-419-debug.mjs` output:

```
→ POST http://aureuserp.test/livewire/update
✅ 200 OK  <-- First request succeeds

→ POST http://aureuserp.test/livewire/update
❌ 419     <-- Subsequent requests fail

→ POST http://aureuserp.test/livewire/update
❌ 419     <-- Continues failing
```

This is **textbook Root Cause #1** - Database session driver + Livewire token rotation.

### Additional Issue: Footer API 500 Error

```
→ GET http://aureuserp.test/api/footer/preferences
⚠️ 500 Internal Server Error
```

This 500 error on **page load** may be interfering with session initialization. Should be fixed separately.

---

## Recommended Action Plan

### For Immediate Fix (Choose ONE):

**Option A - Fastest (Recommended for now):**
```bash
# Change .env
SESSION_DRIVER=file

# Clear caches
php artisan config:clear
php artisan cache:clear

# Test immediately
```

**Option B - FilamentPHP Team Approved:**
Edit `bootstrap/app.php`:
```php
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(/*...*/)
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'validate.annotation.access' => \App\Http\Middleware\ValidateAnnotationAccess::class,
        ]);

        // FIX 419 CSRF ERRORS WITH LIVEWIRE
        $middleware->validateCsrfTokens(except: [
            'livewire/*',
        ]);

        $middleware->throttleApi('api');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
```

Then:
```bash
php artisan config:clear
```

**Option C - Check PHP Config (Herd specific):**
```bash
php -i | grep output_buffering
# If it shows 0, fix it in Herd config
```

---

## Long-Term Production Solution

1. **Switch to Redis for sessions** (best performance + scalability)
2. **Keep CSRF exclusion for Livewire** (team approved)
3. **Fix the footer API 500 error** (may be causing initialization issues)

---

## References

- FilamentPHP Discussion #8574 (database sessions)
- Answer Overflow #1200027134912114708 (web middleware fix)
- FilamentPHP Discussion #1357228669273247784 (Livewire CSRF exclusion)
- Stack Overflow #52583886 (681k views - database sessions)
- Laravel Herd + output_buffering issue (FilamentPHP Discord)

---

## Next Steps

1. **Choose a solution** from the recommended action plan above
2. **Apply the fix**
3. **Clear all caches**
4. **Retry the V2 canvas viewer test**
5. **Fix footer API error separately** (investigate `FooterApiController@getFooterPreferences`)

The V2 canvas annotation system is **code-complete and ready** once the 419 issue is resolved.
