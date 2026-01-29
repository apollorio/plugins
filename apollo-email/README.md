# Apollo Email Service

Unified email service for Apollo platform.

## Features

- ✅ **Unified Email API** - Single service for all email sending
- ✅ **Email Queue** - Background processing with priority support
- ✅ **Email Templates** - CPT-based template management
- ✅ **SMTP Configuration** - Flexible SMTP settings
- ✅ **Security Logging** - Audit trail for all email activity
- ✅ **User Preferences** - Per-user email notification settings
- ✅ **Admin UI** - Email Hub for monitoring and configuration

## Requirements

- WordPress 6.0+
- PHP 8.0+
- Apollo Core plugin

## Installation

1. Upload the plugin to `wp-content/plugins/apollo-email/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Visit Settings → Email Hub to configure

## Usage

### Send an Email

```php
use ApolloEmail\UnifiedEmailService;

$email_service = UnifiedEmailService::get_instance();

$email_service->send(
    'recipient@example.com',
    'Email Subject',
    '<p>Email body with HTML</p>',
    [
        'priority' => 'high',
        'template' => 'welcome-email',
    ]
);
```

### Queue an Email

```php
use ApolloEmail\Queue\QueueManager;

$queue = QueueManager::get_instance();

$queue->enqueue(
    [
        'recipient_email' => 'user@example.com',
        'subject' => 'Queued Email',
        'body' => '<p>This will be sent in the background</p>',
        'priority' => 'normal',
    ]
);
```

### Use Email Template

```php
use ApolloEmail\Templates\TemplateManager;

$template_manager = TemplateManager::get_instance();

$body = $template_manager->render(
    'event-notification',
    [
        'event_title' => 'Summer Festival',
        'event_date' => '2024-07-15',
        'user_name' => 'João Silva',
    ]
);

$email_service->send( 'user@example.com', 'Event Update', $body );
```

## Database Tables

- `wp_apollo_email_queue` - Email queue
- `wp_apollo_email_log` - Email send log
- `wp_apollo_email_security_log` - Security audit log

## Dev/Test UI

When `WP_DEBUG` is enabled, visit:

**http://your-site.com/dev/email**

Test features:

- Send test email
- View queue status
- Check security logs
- Render template previews

## Backward Compatibility

This plugin replaces the email functionality from:

- `apollo-core/includes/class-apollo-email-service.php` (deprecated)
- `apollo-core/includes/communication/email/class-email-manager.php` (deprecated)
- `apollo-social/src/Email/UnifiedEmailService.php` (moved here)

Old class references are automatically aliased for compatibility.

## Testing

```bash
# Install dependencies
composer install

# Run tests
composer test

# Check coding standards
composer cs

# Run static analysis
composer stan
```

## Hooks

### Actions

- `apollo_email/before_send` - Before sending an email
- `apollo_email/after_send` - After sending an email
- `apollo_email/queue/processed` - After queue item processed
- `apollo_email/template/rendered` - After template rendered

### Filters

- `apollo_email/smtp_config` - Modify SMTP configuration
- `apollo_email/queue/priority` - Modify email priority
- `apollo_email/template/placeholders` - Add custom placeholders

## License

GPL-2.0-or-later
