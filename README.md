# Dynamic DNS IP Updater for Apache

This Symfony bundle provides an automated solution for managing dynamic IP addresses in Apache's access control configurations. In environments where IP addresses change regularly, maintaining accurate access control becomes challenging. This bundle solves that problem by automatically synchronizing your Apache configuration with the current IP addresses of your dynamic DNS domains.

## Understanding Dynamic DNS and Access Control

Dynamic DNS (DDNS) is a method of automatically updating DNS records in real-time. It's commonly used when your IP address might change periodically, such as with residential internet connections or cloud services that don't provide static IPs. While DDNS ensures your domain always points to the correct IP, maintaining access control based on these IPs requires additional automation.

This bundle bridges that gap by monitoring your dynamic DNS domains and automatically updating your Apache configuration whenever IP changes are detected. It supports both IPv4 and IPv6, providing comprehensive coverage for modern network environments.

## Core Features

The bundle has been designed with reliability and efficiency in mind:

Monitoring and Updates:
- Tracks multiple dynamic DNS domains simultaneously
- Supports both IPv4 and IPv6 address resolution with per-domain IP version control
- Implements intelligent caching to minimize unnecessary DNS queries
- Provides automatic updates when IP changes are detected

Safety and Reliability:
- Creates automatic backups before any modifications
- Implements automatic rollback on failure
- Provides comprehensive logging for all operations
- Maintains atomic operations to prevent configuration corruption

Integration:
- Seamlessly integrates with Symfony's ecosystem
- Works with Apache's .htaccess configuration
- Supports both manual and automated operation
- Provides command-line interface for easy management

## System Requirements

Before installation, ensure your environment meets these prerequisites:

- PHP 8.1 or higher
  Required for modern language features and type safety
- Symfony 6.0 or 7.0
  Provides the foundation for robust application architecture
- Apache web server with .htaccess support
  Necessary for implementing dynamic access control
- Write permissions for the .htaccess file
  Required for automated configuration updates
- DNS resolution capabilities
  Needed for resolving dynamic DNS entries

## Installation and Setup

1. First, install the package using Composer:
```bash
composer require monosize/dynamic-dns-ip-updater
```

2. Register the bundle in your Symfony application. Add this line to `config/bundles.php`:
```php
return [
    // ... other bundles ...
    Monosize\DynamicDnsIpUpdater\DynamicDnsIpUpdaterBundle::class => ['all' => true],
];
```

3. Configure your dynamic DNS domains by creating or updating your `.env` file:
```env
# List your domains with optional IP version preferences
# Format: domain[:ip4|:ip6], separated by commas
# Examples:
# - domain.com (both IPv4 and IPv6)
# - domain.com:ip4 (IPv4 only)
# - domain.com:ip6 (IPv6 only)
DNS_DOMAINS=office.dynamicdns.net:ip4,backup.dyndns.org:ip6,both.example.org
```

## Domain Configuration Options

When configuring domains in DNS_DOMAINS, you have three options for IP version control:

1. Both IP Versions (default):
   ```
   domain.com
   ```
   The bundle will resolve and use both IPv4 and IPv6 addresses if available.

2. IPv4 Only:
   ```
   domain.com:ip4
   ```
   Only IPv4 addresses will be resolved and added to the configuration.

3. IPv6 Only:
   ```
   domain.com:ip6
   ```
   Only IPv6 addresses will be resolved and added to the configuration.

This granular control allows you to optimize your access control configuration based on your specific needs for each domain.

## Configuration Management

The bundle manages a dedicated section in your .htaccess file, marked with clear delimiters. The content reflects your IP version preferences:

```apache
# START DYNAMIC DNS BLOCK
# IPv4-only domain
Require ip 203.0.113.1
# IPv6-only domain
Require ip 2001:db8::1
# Dual-stack domain
Require ip 203.0.113.2
Require ip 2001:db8::2
# END DYNAMIC DNS BLOCK
```

This block is automatically maintained by the bundle. The clear delimiters ensure:
- Easy identification of managed sections
- Safe updates without affecting other configurations
- Clean integration with existing access controls

## Operation Modes

The bundle can operate in two primary modes:

### Manual Operation

Use the command-line interface for direct control:

```bash
# Standard update - checks cache and updates only if needed
php bin/console dns:update-dynamic-ip

# Force update - bypasses cache and forces configuration refresh
php bin/console dns:update-dynamic-ip --force
```

### Automated Operation

For continuous monitoring, you can implement automatic updates using either Symfony's Scheduler or traditional cron jobs.

Using Symfony Scheduler (Recommended):
```yaml
# config/packages/scheduler.yaml
scheduler:
    dns_update:
        type: cron
        schedule: '*/5 * * * *'  # Updates every 5 minutes
        command: 'dns:update-dynamic-ip'
```

Using Traditional Crontab:
```bash
# Updates every 5 minutes
*/5 * * * * /usr/bin/php /path/to/your/project/bin/console dns:update-dynamic-ip
```

## Understanding the Update Process

Each update cycle follows a carefully designed process to ensure reliability:

1. Domain Resolution:
    - Reads configured domains from environment
    - Resolves current IPv4 and IPv6 addresses
    - Validates resolution results

2. Change Detection:
    - Compares new IPs with cached values
    - Determines if updates are necessary
    - Optimizes performance by avoiding unnecessary writes

3. Safe Updates:
    - Creates timestamped backup of current configuration
    - Validates backup creation
    - Implements update using atomic operations

4. Error Handling:
    - Detects any problems during update
    - Automatically restores from backup if needed
    - Logs detailed error information

5. Cache Management:
    - Updates cache with new IP addresses
    - Sets appropriate cache expiration
    - Maintains cache consistency

## Monitoring and Logging

The bundle integrates with Symfony's logging system to provide comprehensive operational insights:

Configuration Changes:
- IP address updates
- Backup operations
- Configuration modifications

Error Conditions:
- DNS resolution failures
- File operation issues
- Permission problems

Performance Metrics:
- Cache hits and misses
- Update timing
- Resource usage

Logs are written to your Symfony application's log directory (`var/log/`), following the standard Symfony logging conventions.

## Development and Testing

The bundle includes a comprehensive test suite and development tools:

Running Tests:
```bash
# Complete test suite with all checks
composer check-all

# Individual components
composer test           # Unit tests
composer test-coverage  # Coverage analysis
composer cs-check      # Code style validation
composer cs-fix        # Automatic style fixes
composer phpstan       # Static analysis
```

Test Coverage Analysis:
To generate detailed coverage reports, install either PCOV (recommended) or Xdebug:

```bash
# Using PCOV
pecl install pcov

# Or using Xdebug
pecl install xdebug
```

## Contributing

We welcome contributions that improve the bundle's functionality or documentation. To contribute:

1. Fork the repository
2. Create a feature branch
3. Implement your changes with tests
4. Submit a pull request

For significant changes, please open an issue first to discuss your proposed changes.

## Support and Troubleshooting

If you encounter issues or need assistance:

1. Check the logs in `var/log/` for detailed error messages
2. Verify your DNS domains are correctly configured
3. Ensure proper file permissions for .htaccess
4. Open an issue on the GitHub repository with:
    - Detailed description of the problem
    - Relevant log entries
    - Your configuration (without sensitive data)
    - Steps to reproduce the issue

## License

This project is licensed under the MIT License, promoting open collaboration and reuse. See the LICENSE file for complete terms.