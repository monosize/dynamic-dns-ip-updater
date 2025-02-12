<?php

declare(strict_types=1);

namespace Monosize\DynamicDnsIpUpdater\Exception;

/**
 * Exception thrown during .htaccess file operations.
 *
 * This exception is thrown when:
 * - The .htaccess file cannot be found
 * - File permissions prevent reading or writing
 * - Backup operations fail
 * - File content updates fail
 */
class HtaccessUpdateException extends \RuntimeException
{
}
