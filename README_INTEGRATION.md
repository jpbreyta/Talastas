# Talastas - User Profile & In-App Notifications

This package was generated from the capstone documentation supplied in chat. It does **not** modify any GitHub repository.

## Why these features fit the paper

The documentation describes:

- secure account and role management under **Handle Users and Access Management**;
- a System Administrator who manages user accounts and roles;
- audit-submission confirmation for the Barangay Focal Person / IT Coordinator;
- administrators validating, approving, or flagging audit entries; and
- data encoders viewing the latest audit status such as **Pending** or **Validated**.

The paper does not explicitly define a separate "notification module", so the implementation below treats those documented confirmations/status outputs as **in-app database notifications**. This is intentionally an implementation extension rather than a claim that the paper explicitly specifies a notification center.

## Included files

```text
app/
  Http/Controllers/
    AccountProfileController.php
    NotificationController.php
  Notifications/
    TalastasNotification.php
  Services/
    NotificationService.php

database/migrations/
  2026_09_15_000000_create_notifications_table.php

resources/views/
  components/
    notification-dropdown.blade.php
    profile-dropdown.blade.php
  notifications/
    index.blade.php
  partials/
    account-controls.blade.php
  profile/
    account.blade.php

routes/
  profile_notifications.php

tests/Feature/
  ProfileNotificationTest.php
```

## Design choices

### Profile

The profile is deliberately conservative:

- Displays name, email/account identifier, and role.
- Identity and role are **read-only** to the user because the paper assigns account/role management to the System Administrator.
- The user may change their own password.
- A password change revokes existing Sanctum/mobile tokens.
- No phone number, home address, birthday, avatar, or other unnecessary personal fields are added.

This follows data-minimization principles and avoids introducing PII that the capstone documentation does not require.

### Notifications

Notifications are database-only and intended for:

1. quarterly audit submission confirmation;
2. admin notice that an audit requires validation;
3. audit status changes such as validated or flagged;
4. completed analytics/gap-analysis notices; and
5. account-security changes.

Payloads intentionally exclude full inventory records, passwords, API keys, tokens, contact details, or technical device specifications.

## Installation

### 1. Copy the files

Copy each file into the matching path in your Laravel application.

The controller is named `AccountProfileController` so it does not overwrite a project that already has `ProfileController.php`.

### 2. Verify that User uses Laravel Notifiable

Your `App\Models\User` should include:

```php
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;
}
```

If it already has `Notifiable`, do not change anything.

### 3. Load the route file

Near the bottom of `routes/web.php`, add:

```php
require __DIR__ . '/profile_notifications.php';
```

Do not duplicate the routes if your application already defines routes with the same names.

### 4. Run the migration

```bash
php artisan migrate
```

### 5. Add the account controls to your header

Inside your authenticated header/navigation area, add:

```blade
@include('partials.account-controls')
```

That renders both the notification bell and profile dropdown.

### 6. Clear cached views/routes during development

```bash
php artisan optimize:clear
```

### 7. Run tests

```bash
php artisan test --filter=ProfileNotificationTest
```

## Connecting notifications to the audit workflow

The package does not assume the exact names of your audit controllers. Call `NotificationService` only after the corresponding database transaction succeeds.

### After a quarterly audit is saved

```php
use App\Services\NotificationService;

public function store(Request $request, NotificationService $notifications)
{
    // ...validate and save audit...

    $notifications->auditSubmitted(
        submitter: $request->user(),
        institutionName: $institution->name,
        institutionId: $institution->id,
        auditId: $audit->audit_id,
        actionUrl: "/institutions/{$institution->id}",
    );

    // ...response...
}
```

### After an administrator validates or flags an audit

```php
$notifications->auditStatusChanged(
    submitter: $audit->user,
    institutionName: $audit->institution->name,
    status: $audit->validation_status, // e.g. validated or flagged
    institutionId: $audit->institution_id,
    auditId: $audit->audit_id,
    actionUrl: "/institutions/{$audit->institution_id}",
);
```

### After gap analysis finishes

Pass only the users who should receive planning/analytics notices:

```php
$notifications->analysisCompleted(
    recipients: $authorizedRecipients,
    institutionName: $institution->name,
    institutionId: $institution->id,
    analysisId: $analytics->id,
    actionUrl: '/analytics',
);
```

## Role mapping

The paper describes role concepts such as:

- System Administrator
- LGU City Planner
- Barangay Focal Person / IT Coordinator (also described in testing as a Data Encoder)

If your database currently stores generic role keys such as `admin`, `editor`, and `viewer`, keep authorization based on the database values. You may later create display labels such as:

```php
[
    'admin' => 'System Administrator',
    'editor' => 'Barangay Focal Person / IT Coordinator',
    'viewer' => 'LGU City Planner',
]
```

Treat that as an application mapping, not a new database permission system unless your team formally adopts it.

## Security notes

- All notification/profile routes require authentication.
- Notification lookup is performed through the authenticated user's relationship, preventing another user from marking someone else's notification as read.
- Notification action links only allow local `/...` paths.
- Blade uses escaped `{{ }}` rendering for notification text.
- Password update requires the current password and is rate-limited.
- Password changes revoke Sanctum tokens.
- The notification table should not be used as a copy of audit/inventory data.
- Keep administrative account management separate from the self-profile page.

## Optional next step

A later module can add a full **Admin User Management** page for creating, changing roles, and deactivating data-encoder accounts. That behavior is explicitly present in the paper's System Administrator test case, but it is intentionally not bundled here because this package focuses only on the requested user profile and notification features.
