#!/bin/bash

# Goal in One - Production Deployment Script
# This script automates the deployment process for the Goal in One application

set -e  # Exit on any error

echo "🚀 Starting Goal in One deployment..."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if we're in the correct directory
if [ ! -f "artisan" ]; then
    print_error "artisan file not found. Please run this script from the Laravel project root."
    exit 1
fi

# Step 1: Update code from repository
print_status "Pulling latest code from repository..."
git pull origin main || {
    print_error "Failed to pull latest code"
    exit 1
}

# Step 2: Install/Update Composer dependencies
print_status "Installing/updating Composer dependencies..."
composer install --no-dev --optimize-autoloader || {
    print_error "Failed to install Composer dependencies"
    exit 1
}

# Step 3: Install/Update NPM dependencies
print_status "Installing/updating NPM dependencies..."
npm ci || {
    print_error "Failed to install NPM dependencies"
    exit 1
}

# Step 4: Build frontend assets
print_status "Building frontend assets for production..."
npm run build || {
    print_error "Failed to build frontend assets"
    exit 1
}

# Step 5: Copy production environment file
if [ -f ".env.production" ]; then
    print_status "Copying production environment configuration..."
    cp .env.production .env
    print_warning "Please ensure all environment variables are properly configured!"
else
    print_warning ".env.production file not found. Using existing .env file."
fi

# Step 6: Generate application key if needed
if ! grep -q "APP_KEY=base64:" .env; then
    print_status "Generating application key..."
    php artisan key:generate --force
fi

# Step 7: Clear and cache configuration
print_status "Optimizing application configuration..."
php artisan config:clear
php artisan config:cache
php artisan route:clear
php artisan route:cache
php artisan view:clear
php artisan view:cache

# Step 8: Run database migrations
print_status "Running database migrations..."
php artisan migrate --force || {
    print_error "Database migration failed"
    exit 1
}

# Step 9: Clear application cache
print_status "Clearing application cache..."
php artisan cache:clear
php artisan queue:restart

# Step 10: Set proper permissions
print_status "Setting proper file permissions..."
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || {
    print_warning "Could not change file ownership. Please ensure web server has proper permissions."
}

# Step 11: Run tests (optional)
if [ "$1" = "--with-tests" ]; then
    print_status "Running tests..."
    php artisan test || {
        print_warning "Some tests failed. Please review before proceeding."
    }
fi

# Step 12: Create symbolic link for storage (if needed)
if [ ! -L "public/storage" ]; then
    print_status "Creating storage symbolic link..."
    php artisan storage:link
fi

print_success "🎉 Deployment completed successfully!"
print_status "Application is now ready at your configured domain."

echo ""
echo "📋 Post-deployment checklist:"
echo "   ✅ Verify database connection"
echo "   ✅ Test user registration and login"
echo "   ✅ Check all application features"
echo "   ✅ Monitor application logs"
echo "   ✅ Set up SSL certificate"
echo "   ✅ Configure backup strategy"
echo ""
print_status "Happy goal tracking! 🎯"