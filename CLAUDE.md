# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Goal in One is a goal management and task tracking web application built with Laravel (backend) and Vue.js (frontend). The application allows users to create goals, manage tasks (simple, recurring, and deadline-based), and visualize progress through an interactive dashboard.

## Essential Commands

### Development
```bash
# Start all development servers (Laravel, queue, logs, Vite)
composer dev

# Or start individually:
php artisan serve          # Laravel server at http://localhost:8000
yarn dev                   # Vite dev server for frontend hot-reload
php artisan queue:listen   # Queue worker
php artisan pail           # Log viewer
```

### Testing
```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --filter=AuthTest
php artisan test --filter=GoalTest
php artisan test --filter=TaskTest

# Run with coverage
php artisan test --coverage
```

### Database
```bash
# Run migrations
php artisan migrate

# Fresh database with migrations
php artisan migrate:fresh

# Rollback last migration
php artisan migrate:rollback
```

### Building
```bash
# Build frontend for production
yarn build

# Install dependencies
composer install
yarn install
```

### Deployment
```bash
# Full deployment to production
bash deploy.sh

# Deployment with tests
bash deploy.sh --with-tests
```

### Cache Management
```bash
# Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Cache for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Architecture Overview

### Backend Architecture (Laravel)

**Authentication & Authorization:**
- Laravel Sanctum for API authentication with token-based auth
- All API routes under `/api` prefix with Sanctum middleware protection
- UUIDs used as public identifiers (never expose database IDs)
- TurnstileService for bot protection (Cloudflare Turnstile integration)

**Data Model Relationships:**
```
User
 └─ hasMany Goals
     └─ hasMany Tasks
         └─ hasMany TaskCompletion (completion history)
```

**Key Models:**
- `User`: Authentication model with UUID
- `Goal`: Contains title, description, status, completed_at
  - Has computed attributes: `completed_tasks_count`, `total_tasks_count`, `progress_percentage`
- `Task`: Three types: 'simple', 'recurring', 'deadline'
  - Recurring tasks have `recurrence_type`: daily/weekly/monthly
  - Auto-resets after completion based on recurrence type
  - Has computed attributes: `is_overdue`, `days_until_due`
  - Method `markAsCompleted()` handles completion logic and recurring task resets
- `TaskCompletion`: Tracks completion history for analytics

**API Controllers Structure:**
- `AuthController`: Registration, login, logout, account deletion
- `GoalController`: Standard resource controller for goals (CRUD)
- `TaskController`: Handles both goal-attached and independent tasks
- `DashboardController`: Stats and progress data for visualizations

**Route Key Pattern:**
All models use UUID as route key (`getRouteKeyName()` returns 'uuid'), so routes use UUIDs instead of database IDs for security.

### Frontend Architecture (Vue.js 3 + TypeScript)

**State Management (Pinia):**
- `auth.ts`: User authentication state and API calls
- `goals.ts`: Goals CRUD and state management
- `tasks.ts`: Tasks CRUD and completion logic
- `dashboard.ts`: Dashboard statistics and progress data

**Routing:**
- Route guards check `requiresAuth` and `requiresGuest` meta fields
- Navigation controlled by `authStore.isAuthenticated`
- All authenticated routes redirect to `/login` if not authenticated

**Component Organization:**
```
components/
  layout/       # AppHeader, AppSidebar, AppLayout
  ui/           # Reusable UI components (buttons, modals, cards)
  charts/       # Chart.js wrapper components for visualizations
views/
  auth/         # Login, Register
  goals/        # Goals, GoalDetail
  tasks/        # Tasks
  Dashboard.vue
  Statistics.vue
  Profile.vue
```

**Vite Configuration:**
- `@` alias points to `resources/js`
- Entry point: `resources/js/app.ts` and `resources/css/app.css`

## Key Patterns & Conventions

### Model Boot Method Pattern
All models auto-generate UUIDs on creation:
```php
protected static function boot() {
    parent::boot();
    static::creating(function ($model) {
        if (empty($model->uuid)) {
            $model->uuid = Str::uuid();
        }
    });
}
```

### Task Completion Pattern
Tasks use a special `markAsCompleted()` method that:
1. Updates task status to 'completed'
2. Records completion in TaskCompletion history
3. For recurring tasks, automatically resets status based on recurrence_type

### API Response Pattern
Controllers typically return JSON responses with:
- 201 for creation
- 200 for updates/reads
- 204 for deletions
- Include computed attributes in responses (e.g., progress_percentage)

### Testing Pattern
- Use `RefreshDatabase` trait for clean test database
- Helper method `authenticatedUser()` returns `[$user, $token]` tuple
- Tests use Bearer token authentication in headers
- Verify both response structure and database state with `assertDatabaseHas`

## Code Style

- **Backend**: PSR-12 PHP coding standards
- **Frontend**: TypeScript strict mode, Vue 3 Composition API
- **Formatting**: Laravel Pint for PHP
- **Commit Messages**: Conventional Commits format

## Environment Configuration

### Key Environment Variables
```env
# Turnstile bot protection (optional)
APP_IS_USE_TURNSTILE=true
TURNSTILE_SITE_KEY=your_site_key
TURNSTILE_SECRET_KEY=your_secret_key

# Sanctum configuration
SANCTUM_STATEFUL_DOMAINS=your-domain.com
SESSION_DOMAIN=.your-domain.com
```

## Important Implementation Details

### Task Type Logic
When working with tasks, be aware of three distinct types:
- **simple**: One-time completion, no recurrence
- **recurring**: Auto-resets based on recurrence_type (daily/weekly/monthly)
- **deadline**: Has due_date, can be overdue

### Goal Progress Calculation
Goal progress is calculated dynamically:
```php
progress_percentage = (completed_tasks / total_tasks) * 100
```
This is a computed attribute on the Goal model, not stored in database.

### Independent Tasks
Tasks can exist without a goal (`goal_id` nullable). These are created via `/api/tasks` POST endpoint instead of `/api/goals/{goalUuid}/tasks`.

### Authentication Flow
1. Register/Login returns user data + token
2. Frontend stores token in auth store
3. Axios interceptor (resources/js/axios.ts) adds token to all requests
4. Backend verifies via Sanctum middleware

## File Locations

- **API Routes**: [routes/api.php](routes/api.php)
- **Models**: [app/Models/](app/Models/)
- **Controllers**: [app/Http/Controllers/Api/](app/Http/Controllers/Api/)
- **Frontend Entry**: [resources/js/app.ts](resources/js/app.ts)
- **Vue Router**: [resources/js/router/index.ts](resources/js/router/index.ts)
- **Pinia Stores**: [resources/js/stores/](resources/js/stores/)
- **Migrations**: [database/migrations/](database/migrations/)
- **Tests**: [tests/Feature/](tests/Feature/)
- **Vite Config**: [vite.config.js](vite.config.js)
