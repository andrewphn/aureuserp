# Deploy PDF Processing to Staging

## Step 1: SSH into staging server
```bash
ssh user@staging.tcswoodwork.com
cd /path/to/aureuserp  # Navigate to your Laravel app directory
```

## Step 2: Checkout feature branch
```bash
# Fetch latest branches
git fetch origin

# Checkout the PDF processing feature branch
git checkout feature/pdf-processing
git pull origin feature/pdf-processing
```

## Step 3: Install dependencies (if needed)
```bash
composer install --no-dev --optimize-autoloader
```

## Step 4: Run migration
```bash
php artisan migrate --force
```

## Step 5: Clear caches
```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
```

## Step 6: Start/restart queue worker
```bash
# If using supervisor, restart the queue worker
sudo supervisorctl restart aureuserp-worker

# OR if running manually (in a screen/tmux session)
php artisan queue:work --daemon

# OR if using Laravel Horizon
php artisan horizon:terminate
```

## Step 7: Check storage permissions
```bash
chmod -R 775 storage/app/public
chmod -R 775 storage/app/public/pdf-thumbnails
```

## Step 8: Test the feature
1. Go to https://staging.tcswoodwork.com/admin
2. Navigate to any Project
3. Upload a PDF document in the Documents tab
4. Watch the processing status change: pending → processing → completed
5. Check thumbnails generated in `storage/app/public/pdf-thumbnails/{document_id}/`
6. Monitor logs: `tail -f storage/logs/laravel.log`

## Step 9: If tests pass, merge to master
```bash
# On your local machine
git checkout master
git merge feature/pdf-processing
git push origin master

# Then on staging, switch back to master
ssh user@staging.tcswoodwork.com
cd /path/to/aureuserp
git checkout master
git pull origin master
```

## Rollback (if needed)
```bash
# Checkout master branch
git checkout master
git pull origin master

# Rollback migration
php artisan migrate:rollback --step=1
```
