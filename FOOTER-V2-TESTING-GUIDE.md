# Global Footer V2 - Testing Guide

**Status:** ✅ **READY FOR TESTING**
**Current Version:** V2 Enabled
**Date:** January 24, 2025

---

## 🎯 Quick Start

1. **Login:** http://aureuserp.test/admin/login
2. **Credentials:** info@tcswoodwork.com / Lola2024!
3. **Look at bottom of page** - you should see the new footer!

---

## ✅ What's Been Verified

### Build & Configuration
- ✅ 20 files created (infrastructure, providers, components)
- ✅ JavaScript bundled successfully (`app-Cy1ZnNyN.js`)
- ✅ Alpine component registered (`contextFooter`)
- ✅ All 4 context providers loaded (project, sale, inventory, production)
- ✅ V2 footer enabled via config (`FOOTER_VERSION=v2`)
- ✅ Site accessible and working

### Technical Verification
```bash
# Alpine component in build
✅ Found in: public/build/assets/app-Cy1ZnNyN.js (3 occurrences)

# Context providers registered
✅ ["project", "sale", "inventory", "production"]

# Configuration active
✅ config('footer.version') = 'v2'
```

---

## 📋 Testing Checklist

### 1. Dashboard - No Context State

**Steps:**
1. Login and go to dashboard
2. Look at bottom of page

**Expected Results:**
- [ ] Footer appears at bottom
- [ ] Shows "No Project Selected" text
- [ ] Shows "Select Project" button
- [ ] Footer is minimized (44px tall bar)
- [ ] Project folder icon visible
- [ ] Click to expand/minimize works

**Screenshot:** Take screenshot if any issues

---

### 2. Project Context - Most Important Test

**Steps:**
1. Navigate to: **Projects** → Open any project → Click **Edit**
2. Look at footer at bottom

**Expected Results:**
- [ ] Footer automatically shows project information
- [ ] **Minimized state** shows: `Project # • Customer Name`
- [ ] **Expanded state** shows:
  - [ ] Project Number (copyable)
  - [ ] Customer Name (bold)
  - [ ] Project Type (badge)
  - [ ] Linear Feet (if set)
  - [ ] Estimates: days, weeks, months (if calculated)
  - [ ] Due Date (if set)
  - [ ] Tags (if any, shows count)
- [ ] **Action Buttons:**
  - [ ] "Save" button (green) - triggers Filament's save
  - [ ] "Switch" button - opens project selector
  - [ ] "Clear" button - clears context
  - [ ] NO "Edit" button (already on edit page)

---

### 3. Context Persistence

**Steps:**
1. Open a project edit page (sets context)
2. Navigate to Dashboard
3. Navigate to another page (e.g., Settings)

**Expected Results:**
- [ ] Footer still shows project context
- [ ] Project information persists across pages
- [ ] Can navigate anywhere and context remains

---

### 4. Save Button Integration

**Steps:**
1. On project edit page, change something
2. Click the **green "Save" button** in footer

**Expected Results:**
- [ ] Save button is enabled when form is dirty
- [ ] Save button triggers Filament's save action
- [ ] Success notification appears
- [ ] Form saves successfully

---

### 5. Browser Console Checks

**Steps:**
1. Open browser DevTools (F12 or Cmd+Option+I)
2. Go to Console tab
3. Type: `typeof window.contextFooter`

**Expected Results:**
```javascript
> typeof window.contextFooter
"function"  ✅

> typeof Alpine
"object"  ✅

> typeof Livewire
"object"  ✅

> window.componentRegistry
{contextFooter: function, ...}  ✅
```

**Check for errors:**
- [ ] No red errors in console
- [ ] No "undefined" errors for `contextFooter`
- [ ] Alpine.js initialized properly

---

### 6. Other Context Types (If Available)

#### Sale/Order Context
**Navigate to:** Sales → Orders → Edit any order

**Expected:**
- [ ] Footer shows order number
- [ ] Shows customer name
- [ ] Shows order total (if set)
- [ ] Shows order status badge
- [ ] Shows payment status badge

#### Inventory Context
**Navigate to:** Inventory → Items → Edit any item

**Expected:**
- [ ] Footer shows item name
- [ ] Shows SKU
- [ ] Shows quantity with unit
- [ ] Shows location (if set)

#### Production Context
**Navigate to:** Production → Jobs → Edit any job

**Expected:**
- [ ] Footer shows job number
- [ ] Shows project/customer name
- [ ] Shows production status

---

## 🔄 Compare V1 vs V2

Want to see the difference? Switch between versions:

### Test V1 (Old Footer)
```bash
echo "FOOTER_VERSION=v1" >> .env
php artisan config:clear
```
Refresh browser - should see old footer

### Test V2 (New Footer)
```bash
echo "FOOTER_VERSION=v2" >> .env
php artisan config:clear
```
Refresh browser - should see new FilamentPHP v4 footer

---

## 🐛 Troubleshooting

### Footer Not Appearing?

**Try this:**
```bash
php artisan config:clear
php artisan view:clear
php artisan route:clear
```
Then **hard refresh** browser: `Cmd+Shift+R` (Mac) or `Ctrl+Shift+R` (Windows)

### Alpine Component Not Working?

**Rebuild assets:**
```bash
npm run build
```
Then **hard refresh** browser

### JavaScript Errors in Console?

**Check logs:**
```bash
tail -f storage/logs/laravel.log
```

**Enable debug:**
```bash
# In .env
APP_DEBUG=true
```

---

## 📊 What Changed from V1 to V2?

| Feature | V1 (Old) | V2 (New) |
|---------|----------|----------|
| **Architecture** | 1 Blade file (1052 lines) | Modular widget system (20 files) |
| **Alpine.js** | Inline script | Registered component |
| **Field Rendering** | Manual HTML | Filament Infolist components |
| **Context Types** | Hardcoded | Pluggable providers |
| **Extensibility** | Edit source code | Plugin events |
| **Testing** | Difficult | Unit/Feature/E2E testable |
| **Performance** | No caching | Vite bundled + cacheable |
| **FilamentPHP** | Non-compliant | v4 Compliant ✅ |

---

## 🎨 Visual Comparison

### V1 Footer
- Fixed height footer
- Inline JavaScript
- Manual field rendering

### V2 Footer
- Proper FilamentPHP widget
- Bundled Alpine component
- Filament Infolist fields
- Cleaner, more maintainable code

---

## 📸 Screenshots to Take

If you encounter any issues, please take screenshots of:

1. **Dashboard with footer** (no context state)
2. **Project edit page with footer** (showing project context)
3. **Browser console** (showing Alpine/Livewire checks)
4. **Any errors** (if they occur)

Save as:
- `footer-v2-dashboard.png`
- `footer-v2-project-context.png`
- `footer-v2-console.png`
- `footer-v2-error.png` (if issues)

---

## ✅ Success Criteria

**Footer V2 is working correctly if:**

1. ✅ Footer appears on all admin pages
2. ✅ Shows "No Project Selected" when no context
3. ✅ Automatically displays project info on edit pages
4. ✅ Context persists across navigation
5. ✅ Save button works on edit pages
6. ✅ Can expand/minimize footer
7. ✅ Can clear context
8. ✅ No JavaScript errors in console
9. ✅ Alpine component loaded (`window.contextFooter`)
10. ✅ All 4 context types work (if tested)

---

## 📚 Additional Resources

- **Full Documentation:** `docs/GLOBAL-FOOTER-V2-ARCHITECTURE.md`
- **Config File:** `config/footer.php`
- **Widget Code:** `app/Filament/Widgets/GlobalContextFooter.php`
- **Alpine Component:** `resources/js/components/context-footer.js`

---

## 🚀 Next Steps After Testing

1. **If V2 works:** Continue testing with real workflows
2. **If issues found:** Document them, keep V1 as fallback
3. **Migration plan:** See docs for staged rollout strategy
4. **Plugin development:** Review how to add custom context types

---

**Status:** ✅ **READY FOR YOUR TESTING!**

Visit **http://aureuserp.test/admin** and check out the new footer! 🎉
