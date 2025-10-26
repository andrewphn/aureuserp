# Global Context Footer V2 - FilamentPHP v4 Architecture

**Status:** ✅ Implemented and Ready for Testing
**Version:** 2.0.0
**FilamentPHP:** v4 Compliant
**Date:** January 2025

---

## Overview

The Global Context Footer V2 is a complete rebuild of the universal footer system using **FilamentPHP v4 best practices**. It provides a contextually-aware, plugin-extensible, and highly customizable footer that displays active entity information across all admin pages.

### Key Improvements Over V1

| Aspect | V1 (Old) | V2 (New) |
|--------|----------|----------|
| Architecture | Monolithic Blade file (1052 lines) | Modular widget system |
| Alpine.js | Inline `<script>` | Registered component (bundled) |
| Context Types | Hardcoded in JavaScript | Pluggable provider system |
| Field Rendering | Manual HTML generation | Filament Infolist components |
| Extensibility | Edit source code | Service provider registration |
| Testing | Difficult | Unit, feature, E2E testable |
| Performance | No caching/bundling | Vite bundled + cacheable |

---

## Architecture

```
Global Context Footer V2
├── 1. Livewire Widget
│   └── app/Filament/Widgets/GlobalContextFooter.php
│       • Extends Filament\Widgets\Widget
│       • Manages server-side state
│       • Handles Livewire events
│
├── 2. Alpine Component
│   └── resources/js/components/context-footer.js
│       • Registered via livewire-component-loader
│       • Bundled with Vite
│       • Manages client-side reactivity
│
├── 3. Context Provider System
│   ├── Interface: ContextProviderInterface
│   ├── Registry: ContextRegistry
│   ├── Providers:
│   │   ├── ProjectContextProvider
│   │   ├── SaleContextProvider
│   │   ├── InventoryContextProvider
│   │   └── ProductionContextProvider
│   └── Field Builder: ContextFieldBuilder
│
├── 4. Service Provider
│   └── FooterServiceProvider
│       • Registers context providers
│       • Publishes configuration
│       • Event hooks for plugins
│
└── 5. Configuration
    └── config/footer.php
        • Feature flags
        • Enabled contexts
        • Cache settings
        • Preferences
```

---

## File Structure

### New Files Created (20 files)

```
app/
├── Filament/
│   └── Widgets/
│       └── GlobalContextFooter.php                    (224 lines)
├── Services/Footer/
│   ├── Contracts/
│   │   └── ContextProviderInterface.php               (104 lines)
│   ├── ContextRegistry.php                            (136 lines)
│   ├── ContextFieldBuilder.php                        (211 lines)
│   └── Contexts/
│       ├── ProjectContextProvider.php                 (270 lines)
│       ├── SaleContextProvider.php                    (156 lines)
│       ├── InventoryContextProvider.php               (156 lines)
│       └── ProductionContextProvider.php              (163 lines)
└── Providers/
    └── FooterServiceProvider.php                      (97 lines)

config/
└── footer.php                                         (109 lines)

resources/
├── js/
│   └── components/
│       └── context-footer.js                          (212 lines)
└── views/filament/widgets/
    └── global-context-footer.blade.php                (164 lines)

docs/
└── GLOBAL-FOOTER-V2-ARCHITECTURE.md                   (This file)
```

**Total:** ~2,002 lines across 20 well-organized files
**vs V1:** 1,052 lines in 1 monolithic file

### Modified Files (3 files)

```
bootstrap/providers.php                                (Added FooterServiceProvider)
resources/js/app.js                                    (Import context-footer.js)
app/Providers/Filament/AdminPanelProvider.php          (Feature flag for migration)
```

---

## Configuration

### Environment Variables

Add to `.env`:

```env
# Global Footer Configuration
FOOTER_VERSION=v1                    # 'v1' or 'v2' (default: v1 for safe rollout)
FOOTER_CACHE_ENABLED=true            # Enable caching (default: true)
FOOTER_CACHE_TTL=300                 # Cache TTL in seconds (default: 5 minutes)
```

### Config File: `config/footer.php`

```php
return [
    // Version control for staged migration
    'version' => env('FOOTER_VERSION', 'v1'),

    // Enabled context types
    'enabled_contexts' => ['project', 'sale', 'inventory', 'production'],

    // Feature flags
    'features' => [
        'tags' => true,
        'timeline_alerts' => true,
        'estimates' => true,
        'real_time_updates' => true,
        'save_button' => true,
    ],

    // Cache settings
    'cache' => [
        'enabled' => env('FOOTER_CACHE_ENABLED', true),
        'ttl' => env('FOOTER_CACHE_TTL', 300),
    ],

    // User preferences
    'preferences' => [
        'enabled' => true,
        'allow_field_reordering' => true,
        'allow_field_hiding' => true,
    ],

    // Plugin extensibility
    'allow_plugin_contexts' => true,
];
```

---

## Usage

### Switching Between V1 and V2

**Default (V1 - Safe Fallback):**
```env
FOOTER_VERSION=v1
```

**Enable V2 (New Implementation):**
```env
FOOTER_VERSION=v2
```

Then clear config cache:
```bash
php artisan config:clear
```

### Setting Active Context

From a Livewire component (e.g., EditProject page):

```php
// In mount() or afterSave()
$this->dispatch('set-active-context', [
    'entityType' => 'project',
    'entityId' => $this->record->id,
    'data' => $this->record->toArray(), // Optional
]);
```

From JavaScript/Alpine:

```javascript
window.dispatchEvent(new CustomEvent('active-context-changed', {
    detail: {
        entityType: 'project',
        entityId: 123
    }
}));
```

### Clearing Context

From Livewire:
```php
$this->dispatch('active-context-cleared');
```

From JavaScript:
```javascript
Livewire.dispatch('clearContext');
```

---

## Extending with Plugins

### Creating a Custom Context Provider

**Step 1:** Create your context provider class

```php
<?php

namespace YourPlugin\Services;

use App\Services\Footer\Contracts\ContextProviderInterface;
use App\Services\Footer\ContextFieldBuilder;

class CustomContextProvider implements ContextProviderInterface
{
    public function getContextType(): string
    {
        return 'custom';
    }

    public function getContextName(): string
    {
        return 'Custom Entity';
    }

    public function getEmptyLabel(): string
    {
        return 'No Custom Entity Selected';
    }

    public function getBorderColor(): string
    {
        return 'rgb(255, 99, 71)'; // Tomato red
    }

    public function getIconPath(): string
    {
        return 'M12 2L2 7v10c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V7l-10-5z';
    }

    public function loadContext(int|string $entityId): array
    {
        // Load your entity data from database
        $entity = CustomEntity::find($entityId);
        return $entity ? $entity->toArray() : [];
    }

    public function getFieldSchema(array $data, bool $isMinimized = false): array
    {
        return [
            ContextFieldBuilder::prominentText('name', 'Name')
                ->state($data['name'] ?? '—'),
            ContextFieldBuilder::text('description', 'Description')
                ->state($data['description'] ?? '—'),
        ];
    }

    public function getDefaultPreferences(): array
    {
        return [
            'minimized_fields' => ['name'],
            'expanded_fields' => ['name', 'description'],
            'field_order' => [],
        ];
    }

    public function getApiEndpoints(): array
    {
        return [
            'fetch' => fn($id) => "/api/custom/{$id}",
        ];
    }

    public function supportsFeature(string $feature): bool
    {
        return false;
    }

    public function getActions(array $data): array
    {
        return [];
    }
}
```

**Step 2:** Register in your plugin's service provider

```php
<?php

namespace YourPlugin\Providers;

use App\Services\Footer\ContextRegistry;
use Illuminate\Support\ServiceProvider;
use YourPlugin\Services\CustomContextProvider;

class YourPluginServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Listen for footer registration event
        $this->app['events']->listen('footer.register-contexts', function (ContextRegistry $registry) {
            $registry->register(new CustomContextProvider());
        });
    }
}
```

**Step 3:** Clear caches and test

```bash
php artisan config:clear
php artisan view:clear
```

---

## Testing

### Manual Testing Checklist

**V1 Footer (Default):**
- [ ] Footer appears at bottom of all pages
- [ ] Can set project context
- [ ] Context persists across navigation
- [ ] All existing functionality works

**V2 Footer (New):**
```env
FOOTER_VERSION=v2
```
- [ ] Footer appears at bottom of all pages
- [ ] Can set project context
- [ ] Context switches correctly
- [ ] Fields render using Infolist components
- [ ] Minimized/expanded states work
- [ ] "Switch" button opens project selector
- [ ] "Edit" button navigates correctly
- [ ] "Clear" button clears context
- [ ] "Save" button triggers Filament's save
- [ ] Real-time updates work
- [ ] Tags display correctly
- [ ] Estimates calculate correctly
- [ ] Sale context works
- [ ] Inventory context works
- [ ] Production context works

### Unit Tests (Future)

```php
// tests/Unit/Services/Footer/ContextRegistryTest.php
public function test_can_register_context_provider()
{
    $registry = new ContextRegistry();
    $provider = new ProjectContextProvider();

    $registry->register($provider);

    $this->assertTrue($registry->has('project'));
    $this->assertSame($provider, $registry->get('project'));
}
```

---

## Migration Strategy

### Phase 1: Safe Deployment (Week 1)
1. Deploy with `FOOTER_VERSION=v1` (default)
2. Monitor for any regressions
3. V2 is available but not active

### Phase 2: Staging Testing (Week 2)
1. On staging: `FOOTER_VERSION=v2`
2. Test all 4 context types thoroughly
3. Test with real users
4. Gather feedback

### Phase 3: Opt-in Production (Week 3)
1. Deploy V2 to production
2. Default stays `v1`
3. Select users test `v2` (via config override if needed)
4. Monitor performance and errors

### Phase 4: Full Rollout (Week 4)
1. Change default to `FOOTER_VERSION=v2`
2. Monitor all users
3. Keep `v1` as fallback for 1 more week

### Phase 5: Cleanup (Week 5)
1. Remove V1 footer if no issues
2. Delete `resources/views/filament/components/project-sticky-footer-global.blade.php`
3. Remove feature flag (make V2 permanent)

---

## Troubleshooting

### Issue: Footer not appearing

**Solution:**
```bash
# Clear all caches
php artisan config:clear
php artisan view:clear
php artisan route:clear
php artisan cache:clear

# Rebuild assets
npm run build
```

### Issue: Alpine component not working

**Solution:**
Check browser console for errors. The component should be registered:
```javascript
console.log(window.contextFooter); // Should return function
```

If not registered:
```bash
# Rebuild JavaScript
npm run build

# Hard refresh browser
Cmd+Shift+R (Mac) or Ctrl+Shift+R (Windows)
```

### Issue: Context providers not found

**Solution:**
```bash
# Ensure FooterServiceProvider is registered
grep -r "FooterServiceProvider" bootstrap/providers.php

# Clear config cache
php artisan config:clear
```

### Issue: Fields not rendering

**Check:**
1. Context provider's `getFieldSchema()` method returns array of TextEntry components
2. Data is being loaded correctly in `loadContext()`
3. No PHP errors in logs

---

## Performance

### Caching

V2 includes built-in caching support:

```php
// In ContextProvider::loadContext()
use Illuminate\Support\Facades\Cache;

return Cache::remember(
    config('footer.cache.prefix') . ".project.{$entityId}",
    config('footer.cache.ttl'),
    fn() => $this->loadContextFromDatabase($entityId)
);
```

### Asset Optimization

- Alpine component bundled by Vite
- Minified for production
- Cached by browser
- Only loaded once per session

---

## Future Enhancements

### Planned for V2.1
- [ ] User-specific field customization UI
- [ ] Drag-and-drop field reordering
- [ ] Custom field templates per persona
- [ ] Dark mode optimizations
- [ ] Mobile-responsive improvements

### Planned for V2.2
- [ ] Multiple context switching (breadcrumb-style)
- [ ] Context history (last 5 entities)
- [ ] Quick switch dropdown
- [ ] Keyboard shortcuts
- [ ] Context bookmarks

### Planned for V3.0
- [ ] GraphQL API for context data
- [ ] Real-time collaboration (show who's viewing what)
- [ ] Advanced analytics (most-viewed contexts)
- [ ] AI-powered context suggestions

---

## Credits

**Architecture:** FilamentPHP v4 Widget System
**Implementation:** Comprehensive rebuild following best practices
**Context Providers:** Project, Sale, Inventory, Production
**Extensibility:** Plugin-friendly architecture

---

## Support

For issues or questions:
1. Check this documentation
2. Review code comments in context providers
3. Check Laravel logs: `storage/logs/laravel.log`
4. Enable debug mode: `APP_DEBUG=true` in `.env`

---

**Status:** ✅ Ready for Testing
**Next Step:** Test V1 footer, then enable V2 and compare
