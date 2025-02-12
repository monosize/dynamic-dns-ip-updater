<?php

declare(strict_types=1);

namespace Monosize\DynamicDnsIpUpdater\Exception;

/**
 * Exception thrown when DNS resolution fails.
 *
 * This exception is used when the service cannot resolve
 * IP addresses for configured domains
 */
class DnsResolutionException extends \RuntimeException
{
}
