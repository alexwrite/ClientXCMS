<!--
  SYNC IMPACT REPORT
  ==================
  Version change: 0.0.0 -> 1.0.0 (MAJOR - Initial constitution creation)

  Modified principles: None (new document)

  New sections:
    - Vision & Mission: Complete product description and target users
    - System Architecture: Multi-tenant SaaS with extension system
    - Core Principles (7): Test-First, Simplicity, Defensive Programming, Observability, Security, Convention Over Configuration, Incremental Delivery
    - Technical Standards: Stack specification, code structure, naming conventions
    - Design System: Complete UI/UX specification with dynamic theming
    - Extension Architecture: Module and Addon patterns
    - Version Control Practices: Branch naming, commit rules
    - Governance: Amendment process and compliance

  Modified sections: None (new document)

  Removed sections: None (new document)

  Templates requiring updates:
    - .specify/templates/plan-template.md: Constitution Check section updated (compatible)
    - .specify/templates/spec-template.md: No update needed (compatible)
    - .specify/templates/tasks-template.md: No update needed (compatible)

  Follow-up TODOs: None
-->

# ClientXCMS Constitution

## Vision & Mission

### Mission Statement

ClientXCMS is a powerful and configurable Content Management System designed specifically for hosting companies. It simplifies business management by automating processes, securing client data, and providing a seamless experience for both administrators and clients.

**Problem solved:** Hosting companies (game servers, VPS, web hosting, dedicated servers) struggle with fragmented billing systems, manual service provisioning, and lack of automation. ClientXCMS centralizes client management, automated provisioning, invoicing, and support ticketing into one unified platform.

### Target Users

| Segment | Description | Primary Needs |
|---------|-------------|---------------|
| Game Server Hosting | Companies selling Minecraft, FiveM, other game servers | Pterodactyl/Wisp integration, automated provisioning |
| VPS/Cloud Providers | Companies selling virtual private servers | Proxmox/Virtualizor integration, IP management |
| Web Hosting | Companies selling cPanel/Plesk hosting | Panel integration, automated account creation |
| Resellers | Hosting resellers with white-label needs | Customizable themes, branded client area |
| Multi-Service Providers | Companies offering multiple hosting types | Unified dashboard, cross-module management |

### Long-Term Vision

Become the **market-leading billing and automation platform** for hosting companies in the francophone market, while maintaining strong international presence through multi-language support.

**Guiding principles for product decisions:**
- Automation over manual work: every service lifecycle MUST be automatable
- Extensibility first: core platform MUST support community and official extensions
- Data ownership: clients own their data, export MUST always be available
- Reliability over novelty: stable integrations beat cutting-edge and fragile

### System Architecture

ClientXCMS follows a **multi-tenant shared instance** deployment model with **extension-based architecture**. The core platform provides essential functionality, while modules and addons extend capabilities.

```
+-------------------------------------------------------+
|              ClientXCMS Instance                      |
|                                                       |
|  +------------------------------------------------+  |
|  |              FRONTEND (Blade + TailwindCSS)    |  |
|  |  Client Area + Admin Dashboard                 |  |
|  |  - Service management, invoicing, support      |  |
|  |  - Customizable themes                         |  |
|  |  - Responsive interface                        |  |
|  +------------------------------------------------+  |
|                                                       |
|  +------------------------------------------------+  |
|  |              BACKEND (Laravel)                 |  |
|  +------------------------------------------------+  |
|  |                                                |  |
|  |  CORE (mandatory)                              |  |
|  |  - Authentication (SSO, 2FA)                   |  |
|  |  - Client & Service Management                 |  |
|  |  - Invoicing & Payment Gateways                |  |
|  |  - Support Ticketing System                    |  |
|  |  - Scheduled Tasks & Automation                |  |
|  |                                                |  |
|  |  MODULES (service delivery)                    |  |
|  |  - Pterodactyl/Wisp (game servers)             |  |
|  |  - Proxmox/Virtualizor (VPS)                   |  |
|  |  - cPanel/Plesk (web hosting)                  |  |
|  |                                                |  |
|  |  ADDONS (extended features)                    |  |
|  |  - Payment gateways, Gift cards, Pages         |  |
|  |  - Discord integrations, Social auth           |  |
|  |                                                |  |
|  +------------------------------------------------+  |
|                                                       |
|  +------------------------------------------------+  |
|  |              REST API                          |  |
|  |  Bearer token authentication                   |  |
|  |  - Customers, Services, Products, Invoices     |  |
|  |  - Groups, Pricings, Health checks             |  |
|  |  - Swagger documentation at /api/documentation |  |
|  +------------------------------------------------+  |
+-------------------------------------------------------+
```

### Extension Architecture

| Type | Purpose | Location | Examples |
|------|---------|----------|----------|
| **Modules** | Service delivery/provisioning | `/modules` | Pterodactyl, Proxmox, cPanel |
| **Addons** | Additional features/logic | `/addons` | Gift cards, Discord link, Pages |
| **Themes** | UI customization | `/resources/views/themes` | Custom client area designs |

**Key principle:** Modules implement `ServerTypeInterface` for provisioning logic. Addons add features without necessarily connecting to external services.

### Product Scope (This Repository)

**In scope (will be built):**
- Full-stack Laravel application with Blade frontend
- Multi-tenant architecture with customizable themes
- Core billing, invoicing, and payment processing
- Service lifecycle automation (create, suspend, unsuspend, terminate)
- Support ticketing system with departments
- REST API with Swagger documentation
- Extension system (modules + addons)
- Scheduled tasks for automated operations

**Explicitly out of scope (will NOT be built here):**
- Individual hosting panel code (Pterodactyl, Proxmox, cPanel themselves)
- Payment gateway backends (Stripe, PayPal, Mollie themselves)
- Marketing website (separate from client area)
- Mobile native applications
- Kubernetes orchestration of client services

## Core Principles

### I. Test-First Development

All feature implementation SHOULD follow Test-Driven Development (TDD) methodology where practical.

**Rules:**
- Unit tests MUST cover all business logic (Services, Actions)
- Integration tests MUST cover all API endpoints
- Edge cases and error paths MUST be tested
- Feature tests SHOULD cover critical user workflows
- PHPUnit/Pest PHP is the testing framework

**Rationale:** A billing and automation platform handles financial transactions and server provisioning. Bugs can result in financial loss or service disruption. Testing ensures reliability.

### II. Simplicity Over Cleverness

Code MUST prioritize readability and maintainability over brevity or cleverness.

**Non-negotiable rules:**
- YAGNI (You Aren't Gonna Need It): implement only what is explicitly required
- Functions MUST have a single responsibility (max 50 lines)
- Controllers MUST NOT exceed 200 lines; use Services/Actions for logic
- Cyclomatic complexity MUST NOT exceed 10 per function
- Nesting depth MUST NOT exceed 4 levels (includes callbacks and conditionals)
- Premature abstraction is forbidden; duplicate code is acceptable until pattern emerges 3+ times
- Zero tolerance for undefined behaviors or ambiguous implementations

**Rationale:** Simple code is easier to debug, extend, and hand off. ClientXCMS is open-source with community contributions - clarity enables contribution.

### III. Defensive Programming

All code MUST assume hostile or malformed input until validated.

**Non-negotiable rules:**
- All user inputs MUST be validated using Laravel Form Requests
- Functions MUST validate their parameters explicitly
- Error conditions MUST be handled, not ignored
- Fail fast: invalid states MUST raise exceptions immediately
- Never trust data from external APIs (panels, payment gateways)
- Explicit error handling MUST exist at every level
- Database queries MUST use Eloquent or Query Builder (no raw SQL with user input)

**Rationale:** ClientXCMS handles payment data and server provisioning. Silent failures can corrupt billing data or leave services in inconsistent states.

### IV. Observability by Design

All system behavior MUST be traceable and measurable.

**Non-negotiable rules:**
- Structured logging MUST be implemented for all business operations
- Log levels MUST be used appropriately (DEBUG, INFO, WARNING, ERROR, CRITICAL)
- Logs MUST include context (customer_id, service_id, invoice_id, request_id)
- Action logs MUST track admin operations for audit purposes
- External API calls MUST be logged with request/response for debugging
- Errors MUST include stack traces and relevant context for debugging

**Rationale:** When a service fails to provision or a payment fails to process, operators need visibility to diagnose and resolve issues quickly.

### V. Security as Foundation

Security MUST be integrated from the start, not bolted on later.

**Non-negotiable rules:**
- OWASP Top 10 vulnerabilities MUST be addressed in design phase
- Authentication (SSO, 2FA) MUST be verified on every protected route
- Database queries MUST use Eloquent or prepared statements
- Sensitive data MUST be encrypted at rest and in transit
- API keys and secrets MUST never be committed to version control
- Sessions and tokens MUST have appropriate expiration policies
- All admin actions MUST be logged for audit trail
- CSRF protection MUST be enabled on all forms
- reCAPTCHA SHOULD be used on public-facing forms

**Rationale:** ClientXCMS handles payment information, customer data, and server credentials. Security breaches can result in financial loss and legal consequences.

### VI. Convention Over Configuration

Code MUST follow Laravel conventions unless deviation is explicitly justified.

**Non-negotiable rules:**
- File structure MUST follow Laravel standard layout
- Naming conventions MUST follow Laravel/PSR-12 standards
- Eloquent MUST be used for database operations
- Blade MUST be used for templating (no Vue/React in core)
- Laravel's built-in features MUST be preferred over third-party packages when functionality overlaps
- Custom solutions MUST be documented with rationale for deviation
- Extension structure MUST follow the established `/modules` and `/addons` patterns

**Rationale:** Conventions reduce cognitive load, enable faster onboarding, and leverage community knowledge. ClientXCMS being open-source means contributors expect standard Laravel patterns.

### VII. Incremental Delivery

Features MUST be delivered in small, deployable increments.

**Non-negotiable rules:**
- MVP MUST be defined and delivered first
- Each user story MUST be independently testable and deployable
- Feature branches MUST be short-lived (merge within days, not weeks)
- Commits MUST be atomic and represent complete, working changes
- Breaking changes MUST be avoided; migration paths MUST be provided when unavoidable
- Core functionality first, error handling second, edge cases third, optimizations last
- Database migrations MUST be reversible

**Rationale:** ClientXCMS serves production hosting companies. Small increments reduce risk and enable faster feedback from the community.

## Technical Standards

### Stack Specification

- **Language**: PHP 8.1+
- **Framework**: Laravel 11.x
- **Frontend**: Blade + TailwindCSS + Preline UI
- **Database**: MySQL 8.0+ or MariaDB 10.6+
- **Cache/Queue**: Redis
- **Testing**: PHPUnit with Pest style
- **API Documentation**: L5-Swagger
- **PDF Generation**: DomPDF

### Code Structure Standards

| Element | Limit | Action if exceeded |
|---------|-------|-------------------|
| Function | 50 lines max | Split into smaller functions |
| Controller | 200 lines max | Extract to Services/Actions |
| Cyclomatic complexity | 10 max per function | Refactor logic |
| Nesting depth | 4 levels max | Extract to functions |
| File | 1 concern per file | Split by responsibility |

**Additional constraints:**
- High cohesion, low coupling between modules
- Clear dependency injection patterns
- Use Laravel Service Container for dependency resolution
- Traits for shared behavior (e.g., HasMetadata, Loggable)

### Naming Conventions

| Element | Convention | Example |
|---------|------------|---------|
| Variables | camelCase, descriptive | `$customerInvoice` |
| Functions | camelCase, action verbs | `getServiceById()`, `calculateInvoiceTotal()` |
| Classes | PascalCase, nouns | `Customer`, `InvoiceService` |
| Constants | UPPER_SNAKE_CASE | `MAX_RETRY_ATTEMPTS` |
| Database tables | snake_case, plural | `customers`, `invoice_items` |
| Database columns | snake_case | `created_at`, `customer_id` |
| Routes | kebab-case | `/admin/invoice-items` |
| Blade views | kebab-case | `invoice-details.blade.php` |

### Comments & Documentation

**Rules:**
- Comments explain WHY and complex WHAT (not obvious operations)
- Critical sections MUST have comments (security, payment logic, provisioning)
- PHPDoc for public methods with `@param`, `@return`, `@throws`
- Complex business logic MUST be documented inline
- No commented-out code (delete it, git has history)
- TODO comments MUST reference issue/ticket when possible

**Forbidden:**
- Comments stating the obvious
- Empty catch blocks without explanation
- Magic numbers without constants or comments

### Code Quality Requirements

- **Static Analysis**: PHPStan level 6 minimum (when configured)
- **Code Style**: Laravel Pint (PSR-12 based)
- **Test Coverage**: Critical business logic (billing, provisioning) MUST be tested

### Performance Expectations

- **Response Time**: p95 < 500ms for dashboard pages
- **Database Queries**: Maximum 15 queries per page (use eager loading)
- **Memory**: Maximum 128MB per request
- **API Rate Limiting**: Configured per endpoint sensitivity
- Optimize after profiling, not prematurely

## Design System

### Brand Identity

**Logo**: ClientXCMS logo with blue accent
**Brand Voice**: Professional, reliable, technical expertise

### CSS Framework

**Tailwind CSS** is the standard for all UI components. The project uses a dynamic theming system loaded from `storage/app/theme.json`.

### Color Palette

ClientXCMS uses **Indigo** as the primary brand color (customizable via `theme.json`).

#### Default Theme Colors

| Role | Color | Tailwind Class | Usage |
|------|-------|----------------|-------|
| **Primary** | Indigo | `indigo-500/600` | Main actions, buttons, links, active states |
| **Success** | Green | `green-500/600` | Success states, positive actions |
| **Warning** | Yellow/Amber | `yellow-500/600` | Warning states, attention required |
| **Danger** | Red | `red-500/600` | Error states, destructive actions |
| **Info** | Blue | `blue-500/600` | Informational messages |
| **Text Primary** | Gray | `gray-800` | Main text content |
| **Text Secondary** | Gray | `gray-500` | Secondary text, placeholders |

#### Dark Mode

Dark mode is supported via Tailwind's `class` strategy:

```javascript
darkMode: 'class'
```

### Typography

| Element | Classes | Usage |
|---------|---------|-------|
| Page Title | `text-2xl font-bold` | Main page headings |
| Section Title | `text-xl font-semibold` | Section headings |
| Card Title | `text-lg font-medium` | Card headings |
| Body Text | `text-base` | Regular content |
| Small Text | `text-sm text-gray-500` | Secondary info |
| Label | `text-sm font-medium` | Form labels |

### Component Library

**Preline UI** is used for interactive components (dropdowns, modals, tabs). Components use `hs-*` prefixes for JavaScript hooks.

### Font Family

```css
font-family: 'Bricolage Grotesque Variable', 'Inter Variable', 'Inter', system-ui, sans-serif;
```

### Responsive Breakpoints

Follow Tailwind's default breakpoints:
- `sm`: 640px (mobile landscape)
- `md`: 768px (tablet)
- `lg`: 1024px (desktop)
- `xl`: 1280px (large desktop)
- `2xl`: 1536px (extra large desktop)

**Mobile-first**: Default styles for mobile, then override with breakpoint prefixes.

## Extension Architecture

### Module Structure

Modules live in `/modules/{ModuleName}/` and follow this structure:

```
modules/Pterodactyl/
├── PterodactylServiceProvider.php  # Laravel service provider
├── Http/
│   └── Controllers/
├── Models/
├── Services/
├── Views/
├── Routes/
├── Database/
│   └── Migrations/
└── config.json                      # Module metadata
```

### Addon Structure

Addons live in `/addons/{AddonName}/` with similar structure to modules.

### Extension Requirements

- Extensions MUST register via Laravel Service Providers
- Extensions MUST NOT modify core files directly
- Extensions MUST use events/hooks for integration points
- Extensions MUST include `config.json` with metadata
- Database migrations MUST be namespaced to avoid conflicts

## Version Control Practices

### Branch Naming

| Type | Pattern | Example |
|------|---------|---------|
| Feature | `feature/*` | `feature/stripe-subscriptions` |
| Bug fix | `bugfix/*` | `bugfix/invoice-calculation` |
| Hotfix | `hotfix/*` | `hotfix/payment-gateway-error` |

### Commit Rules

- Atomic commits with clear, concise messages
- Each commit MUST represent a complete, working change
- Use conventional commit format when possible: `type(scope): description`
- Types: `feat`, `fix`, `docs`, `style`, `refactor`, `test`, `chore`

### Pre-Development Checklist

Before writing any code:
1. Feature specification MUST be reviewed
2. Data preservation impact MUST be assessed
3. Breaking changes MUST be identified and migration planned
4. Test scenarios MUST be defined
5. Backup strategy MUST be discussed for destructive changes

## Development Workflow

### During Development

1. Create feature branch from `dev`
2. Write tests first when practical
3. Implement feature with clear commits
4. Run test suite before pushing
5. Request code review via Pull Request
6. Merge to `dev` after approval

### Code Review Requirements

- All changes MUST be reviewed before merge to main branches
- Reviews MUST verify compliance with this constitution
- Security-sensitive changes MUST have explicit security review
- Database schema changes MUST be reviewed for migration safety

## Governance

### Amendment Process

1. Proposed amendments MUST be documented with rationale
2. Impact on existing code MUST be assessed
3. Migration plan MUST be provided for breaking changes
4. Version MUST be updated following semantic versioning:
   - MAJOR: Principle removal or incompatible changes
   - MINOR: New principles or expanded guidance
   - PATCH: Clarifications and typo fixes

### Compliance

- All pull requests SHOULD be verified against this constitution
- Constitution violations MUST be resolved before merge to protected branches
- Exceptions MUST be documented with justification
- Runtime guidance is provided in project CLAUDE.md files

### Versioning Policy

This constitution follows semantic versioning. The constitution supersedes all other practices when conflicts arise.

**Version**: 1.0.0 | **Ratified**: 2026-01-02 | **Last Amended**: 2026-01-02
