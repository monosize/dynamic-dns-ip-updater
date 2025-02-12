# Dynamic DNS IP Updater for Apache

This Symfony bundle automatically updates your Apache .htaccess file with IP addresses from dynamic DNS domains. It's particularly useful when you need to maintain access control based on dynamic IP addresses that change periodically.

## Features

The bundle provides several key features to make dynamic IP management easier:

- Monitors multiple dynamic DNS domains simultaneously
- Supports both IPv4 and IPv6 addresses
- Automatically updates .htaccess when IP addresses change
- Creates automatic backups before modifications
- Provides automatic rollback in case of errors
- Implements caching to prevent unnecessary updates
- Offers detailed logging of all operations

## Requirements

Your environment needs to meet these requirements:

- PHP 8.1 or higher
- Symfony 6.0 or 7.0
- Apache web server with .htaccess support
- Write permissions for the .htaccess file
- DNS resolution capabilities for the server

## Installation

Install the package via Composer:

```bash
composer require monosize/dynamic-dns-ip-updater
```

Register the bundle in your `config/bundles.php`:

```php
return [
    // ...
    Monosize\DynamicDnsIpUpdater\DynamicDnsIpUpdaterBundle::class => ['all' => true],
];
```

## Configuration

1. Create or update your `.env` file with your dynamic DNS domains:

```env
# Comma-separated list of domains to monitor
DNS_DOMAINS=yourdomain1.example.org,yourdomain2.example.org
```

2. Ensure your .htaccess file is writable by the web server.

3. The bundle will automatically create a section in your .htaccess file marked with:
```apache
# START DYNAMIC DNS BLOCK
# ...
# END DYNAMIC DNS BLOCK
```

## Usage

### Command Line

You can update IP addresses manually using the console command:

```bash
# Normal update (checks cache first)
php bin/console dns:update-dynamic-ip

# Force update (bypasses cache)
php bin/console dns:update-dynamic-ip --force
```

### Automatic Updates

For automatic updates, you can use Symfony's Scheduler or a cron job.

#### Using Symfony Scheduler

Add this configuration to your scheduler:

```yaml
# config/packages/scheduler.yaml
scheduler:
    dns_update:
        type: cron
        schedule: '*/5 * * * *'  # Every 5 minutes
        command: 'dns:update-dynamic-ip'
```

#### Using Crontab

Add this line to your crontab:

```bash
*/5 * * * * /usr/bin/php /path/to/your/project/bin/console dns:update-dynamic-ip
```

## How It Works

The bundle follows this process for each update:

1. Reads the configured domains from DNS_DOMAINS environment variable
2. Resolves current IPv4 and IPv6 addresses for each domain
3. Compares new IPs with cached values to detect changes
4. Creates a backup of the current .htaccess file
5. Updates the .htaccess file with new IP addresses if changes are detected
6. Automatically restores from backup if any errors occur
7. Updates the cache with new IP addresses

## Logging

All operations are logged using Symfony's logging system. You can find logs about:
- IP address changes
- .htaccess updates
- Backup operations
- Error conditions
- Cache operations

Check your Symfony logs (typically in `var/log/`) for detailed information.

## Development

### Running Tests

```bash
# Run all checks
composer check-all

# Run specific checks
composer test           # Run PHPUnit tests
composer test-coverage  # Generate test coverage report
composer cs-check      # Check coding standards
composer cs-fix        # Fix coding standards
composer phpstan       # Run static analysis
```

### Code Coverage

To generate code coverage reports, you need either the PCOV or Xdebug extension installed:

```bash
# Install PCOV (recommended)
pecl install pcov

# Or install Xdebug
pecl install xdebug
```

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request. For major changes, please open an issue first to discuss what you would like to change.

Please make sure to update tests as appropriate.

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

If you encounter any problems or have questions, please open an issue on the GitHub repository.