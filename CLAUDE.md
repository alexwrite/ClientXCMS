# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

ClientXCMS is a Laravel 11-based CMS designed for hosting companies. It manages customer accounts, billing, provisioning, support tickets, and integrates with various payment gateways and hosting panels. The application uses a modular architecture with extensibility through addons and themes.

## Technology Stack

- **Backend**: PHP 8.1+ with Laravel 11
- **Frontend**: AlpineJS, TailwindCSS, Vite
- **Database**: MySQL/MariaDB
- **Container**: Docker with Nginx and supervisord
- **Queue**: Database (configurable to Redis)
- **Testing**: PHPUnit 10
- **API Documentation**: L5-Swagger (OpenAPI)

## Development Environment

### Initial Setup

```bash
# Install PHP dependencies
composer install

# Install Node dependencies
npm install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Run migrations
php artisan migrate

# Build frontend assets
npm run build
```

### Development Commands

```bash
# Start development server
php artisan serve

# Watch and rebuild frontend assets
npm run dev

# Run all tests
php artisan test

# Run specific test file
php artisan test --filter=CustomerControllerTest

# Run tests in specific suite
php artisan test --testsuite=Feature

# Code formatting (Laravel Pint)
./vendor/bin/pint

# Generate IDE helper files
php artisan ide-helper:generate

# Generate Swagger API documentation
php artisan clientxcms:swagger
```

### Docker Environment

```bash
# Start application with Docker
docker-compose up -d

# Access application at http://localhost
# Access phpMyAdmin at http://localhost/phpmyadmin

# View logs
docker-compose logs -f app

# Execute artisan commands in container
docker-compose exec app php artisan [command]
```

## Architecture Overview

### Core Domain Structure

The application is organized into several key domains under `app/`:

- **Account**: Customer management, email messages, and customer-related operations
- **Billing**: Invoices, payments, subscriptions, gateways, coupons, and pricing
- **Provisioning**: Services, servers, service lifecycle, and hosting panel integrations
- **Helpdesk**: Support tickets, departments, messages, and attachments
- **Store**: Products, groups, baskets, checkout, and catalog management

### Key Architectural Patterns

#### 1. Extension System

The application uses an extension-based architecture allowing for modular functionality:

- **Product Types** (`AbstractProductType`): Define different types of products (hosting, domains, VPS, etc.)
  - Each product type implements `ProductTypeInterface`
  - Located in `app/Core/` or custom extensions
  - Must define `uuid()`, `title()`, `type()`, `data()`, `panel()`, `server()`

- **Server Types** (`AbstractServerType`): Define hosting panel integrations (cPanel, Plesk, Virtualizor, etc.)
  - Implement provisioning operations: `createAccount()`, `suspendAccount()`, `unsuspendAccount()`, `expireAccount()`
  - Return `ServiceStateChangeDTO` for all operations
  - Located in custom addons or extensions

- **Gateway Types** (`AbstractGatewayType`): Payment gateway integrations (Stripe, PayPal, etc.)
  - Implement `createPayment()`, `processPayment()`, `notification()` for webhooks
  - Support payment sources/methods via `addSource()`, `removeSource()`, `getSources()`
  - Located in `app/Core/Gateway/`

#### 2. Service Layer Architecture

Business logic is centralized in service classes under `app/Services/`:

- `Account/CustomerService`: Customer operations and account management
- `Billing/InvoiceService`: Invoice generation, payment processing, tax calculations
- `Provisioning/ServiceService`: Service lifecycle (create, suspend, terminate, renew)
- `Store/BasketService`: Shopping cart operations and checkout flow
- `Store/GatewayService`: Payment gateway management and processing
- `Helpdesk/TicketService`: Support ticket operations

**Pattern**: Controllers should be thin and delegate to services. Services contain business logic and orchestrate between models, external APIs, and events.

#### 3. DTO Pattern

Data Transfer Objects are extensively used for type-safe data passing:

- Located in `app/DTO/`
- Used for API responses, service state changes, provisioning results
- Examples:
  - `ServiceStateChangeDTO`: Provisioning operation results
  - `GatewayPayInvoiceResultDTO`: Payment processing results
  - `ProductPriceDTO`: Product pricing calculations

#### 4. Event-Driven Architecture

The application uses Laravel events for decoupled operations:

- Events in `app/Events/`
- Listeners in `app/Listeners/`
- Used for notifications, logging, provisioning triggers, invoice state changes

### Database Structure

Key models and relationships:

- **Customer** (account owner) → has many Services, Invoices, SupportTickets
- **Service** (provisioned resource) → belongs to Customer, Product, Server
- **Invoice** → has many InvoiceItems, belongs to Customer
- **Product** → belongs to Group, has many Pricings, ConfigOptions
- **Server** → has type (ServerType), has many Services

### Scheduled Tasks

Automated operations defined in `app/Console/Kernel.php`:

- `invoices:delivery` - Every minute: Process pending invoices
- `services:expire` - Every minute: Expire services past due date
- `services:renewals` - Every 3 hours: Generate renewal invoices
- `services:notify-expiration` - Daily 09:00: Send expiration notifications
- `clientxcms:helpdesk-close` - Daily 12:00: Auto-close resolved tickets
- `clientxcms:invoice-delete` - Daily 00:00: Purge old unpaid invoices
- `clientxcms:purge-metadata` - Weekly: Clean up metadata
- `clientxcms:telemetry` - Daily 00:00: Send telemetry data

**Important**: These tasks require a cron job or Laravel scheduler to be configured in production.

### Routes Organization

Routes are separated by context:

- `routes/web.php` - Public front-end routes
- `routes/admin.php` - Admin panel routes (prefixed with `/admin` or `ADMIN_PREFIX` env var)
- `routes/auth.php` - Authentication routes
- `routes/store.php` - Store/catalog routes
- `routes/api-customer.php` - Customer API routes
- `routes/api-application.php` - Application API routes
- `routes/client/*` - Client area routes (invoices, services, helpdesk)

## Testing Strategy

### Test Structure

- **Unit Tests** (`tests/Unit/`): Test individual classes, models, services
- **Feature Tests** (`tests/Feature/`): Test HTTP endpoints, complete workflows
- **Module Tests**: Extensions can have tests in `modules/*/tests/`

### Database Testing

Tests use a separate database (`clientxcms_test` by default):

```xml
<env name="DB_DATABASE" value="clientxcms_test"/>
```

Ensure this database exists before running tests:

```bash
mysql -u root -e "CREATE DATABASE IF NOT EXISTS clientxcms_test"
```

### Testing Traits

- `RefreshExtensionDatabase`: Use for tests involving extensions
- `CreatesApplication`: Base trait for setting up Laravel application in tests

## Extension Development

### Creating Custom Product Types

```bash
php artisan clientxcms:create-extension [name]
```

Custom product types should:
1. Extend `AbstractProductType`
2. Implement provisioning logic through server types
3. Define product configuration forms
4. Handle service lifecycle events

### Creating Custom Server Types

Server types must implement:
- `createAccount()`: Initial service provisioning
- `suspendAccount()`: Temporary suspension
- `unsuspendAccount()`: Reactivation
- `expireAccount()`: Permanent termination
- `testConnection()`: Validate server credentials

All methods return `ServiceStateChangeDTO` with success/failure status.

### Creating Custom Gateway Types

1. Extend `AbstractGatewayType`
2. Implement `createPayment()`: Initialize payment session
3. Implement `processPayment()`: Handle return from gateway
4. Implement `notification()`: Handle webhooks
5. Define `validate()`: Configuration validation rules

## Important Configuration

### Environment Variables

Critical environment variables:

- `APP_URL`: Application base URL
- `ADMIN_PREFIX`: Admin panel URL prefix (default: `admin`)
- `OAUTH_CLIENT_ID` / `OAUTH_CLIENT_SECRET`: OAuth credentials (must be generated)
- `QUEUE_CONNECTION`: Queue driver (use `database` or `redis` for production)
- `TELEMETRY_DISABLED`: Disable telemetry reporting (default: false)

### OAuth Setup

The application uses Laravel Passport for API authentication. After initial setup:

```bash
php artisan clientxcms:install-oauth
```

This generates OAuth credentials that must be added to `.env`.

## Code Conventions

### File Organization

- Abstract classes: `app/Abstracts/`
- Contracts/Interfaces: `app/Contracts/`
- Services: `app/Services/[Domain]/`
- DTOs: `app/DTO/[Domain]/`
- Core implementations: `app/Core/`
- Business logic models: `app/Models/[Domain]/`

### Naming Patterns

- Services: `{Domain}Service` (e.g., `InvoiceService`)
- DTOs: `{Purpose}DTO` (e.g., `ServiceStateChangeDTO`)
- Events: `{Action}{Entity}` (e.g., `InvoiceCreated`)
- Jobs: `{Action}{Entity}Job` (e.g., `ProcessInvoiceJob`)

### Model Traits

Common traits used across models:

- `HasMetadata`: Adds key-value metadata storage
- `Loggable`: Adds action logging
- `Translatable`: Adds multi-language support
- `ModelStatutTrait`: Adds status management
- `CanUse2FA`: Adds two-factor authentication

## API Development

### Swagger Documentation

API documentation is auto-generated from PHPDoc annotations:

```bash
# Generate Swagger JSON
php artisan clientxcms:swagger
```

Access documentation at `/docs/api-docs.json`

### API Versioning

- Customer API: `/api/customer/*`
- Application API: `/api/application/*`
- Authentication: Laravel Sanctum tokens

## Admin Panel Customization

Admin routes require:
- Authentication (`auth` middleware)
- Admin role (`admin` middleware)
- Permission checks (via Permission model)

Custom admin pages should:
1. Extend base admin controllers
2. Use permission checks
3. Follow existing UI patterns (Preline components)

## Theme Development

Themes are located in `resources/themes/[theme-name]/`:

- `theme.json`: Theme metadata
- `views/`: Blade templates
- `views/sections/`: Reusable sections

Use Artisan command to create themes:

```bash
php artisan clientxcms:create-theme [name]
```

## Provisioning Integration

When integrating with hosting panels:

1. Server credentials stored encrypted in `servers` table
2. Service provisioning tracked in `services` table
3. Metadata storage available via `HasMetadata` trait
4. All API calls should be wrapped in try-catch
5. Return descriptive error messages in `ServiceStateChangeDTO`
6. Log provisioning operations for debugging

## Common Pitfalls

1. **Migrations**: Always check for existing tables before creating migrations
2. **OAuth**: Must run `clientxcms:install-oauth` during setup
3. **Scheduler**: Laravel scheduler must be configured in cron for automated tasks
4. **Queue**: Use queue for long-running operations (provisioning, email sending)
5. **Testing DB**: Ensure `clientxcms_test` database exists before running tests
6. **Extensions**: Extension database migrations need special handling via `RefreshExtensionDatabase`

## Installation Flow

For fresh installations:

```bash
# 1. Setup database
php artisan clientxcms:install-db

# 2. Create admin user
php artisan clientxcms:create-admin

# 3. Setup OAuth
php artisan clientxcms:install-oauth

# 4. Update application
php artisan clientxcms:on-update
```

## Debugging Tools

- **Laravel Debugbar**: Available in development (`DEBUGBAR_ENABLED=true`)
- **Telescope**: Available if installed (set `TELESCOPE_ENABLED=true`)
- **Log files**: `storage/logs/` (check scheduled task logs for cron issues)
- **Sentry**: Integrated for error tracking (configure `SENTRY_DSN`)

## License

This is proprietary software with a custom license. Personal and non-commercial use permitted. Commercial use requires authorization from CLIENTXCMS. See LICENSE file and https://clientxcms.com/eula for details.
