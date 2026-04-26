<?php

declare(strict_types=1);

namespace Monosize\DynamicDnsIpUpdater\Service;

use Monosize\DynamicDnsIpUpdater\Exception\DnsResolutionException;
use Monosize\DynamicDnsIpUpdater\Exception\HtaccessUpdateException;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Service responsible for updating DNS records and managing .htaccess file.
 */
class DnsUpdaterService
{
    // IP version constants for better code readability
    private const IP_VERSION_BOTH = 'both';
    private const IP_VERSION_V4 = 'ip4';
    private const IP_VERSION_V6 = 'ip6';

    /**
     * @param string          $projectDir Root directory of the project
     * @param LoggerInterface $logger     Logger for recording operations
     * @param CacheInterface  $cache      Cache for storing IP addresses
     */
    public function __construct(
        private readonly string $projectDir,
        private readonly LoggerInterface $logger,
        private readonly CacheInterface $cache
    ) {
    }

    /**
     * Parses a domain string that may include IP version preference.
     *
     * @param string $domainString Domain string in format "domain.com[:ip4|:ip6]"
     *
     * @return array{domain: string, ipVersion: string} Parsed domain and IP version
     */
    private function parseDomainString(string $domainString): array
    {
        $parts = explode(':', trim($domainString));
        $domain = $parts[0];
        $ipVersion = $parts[1] ?? self::IP_VERSION_BOTH;

        // Validate IP version specification
        if (!\in_array($ipVersion, [self::IP_VERSION_BOTH, self::IP_VERSION_V4, self::IP_VERSION_V6], true)) {
            $ipVersion = self::IP_VERSION_BOTH;
        }

        return [
            'domain' => $domain,
            'ipVersion' => $ipVersion,
        ];
    }

    /**
     * Retrieves configured domains from environment variable.
     *
     * @return array<array{domain: string, ipVersion: string}> List of configured domains with IP preferences
     */
    private function getDomains(): array
    {
        $domainsStr = $_ENV['DNS_DOMAINS'] ?? '';
        $domainStrings = array_filter(explode(',', $domainsStr));

        return array_map(fn (string $domainString) => $this->parseDomainString($domainString), $domainStrings);
    }

    /**
     * Resolves IP addresses for a given domain based on IP version preference.
     *
     * @param string $domain    Domain name to resolve
     * @param string $ipVersion Desired IP version (ip4, ip6, or both)
     *
     * @throws DnsResolutionException When DNS resolution fails
     *
     * @return array<string> List of resolved IP addresses
     */
    private function resolveIps(string $domain, string $ipVersion): array
    {
        $ips = [];

        // Resolve IPv4 if requested
        if (\in_array($ipVersion, [self::IP_VERSION_BOTH, self::IP_VERSION_V4], true)) {
            $ipv4 = gethostbyname($domain);
            if ($ipv4 === $domain) {
                // No A record — fall back to external HTTP service
                $ipv4 = $this->fetchExternalIpV4();
                if (null === $ipv4) {
                    throw new DnsResolutionException("Could not resolve IPv4 address for $domain");
                }
                $this->logger->info("No A record for $domain, using external IPv4: $ipv4");
            }
            $ips[] = $ipv4;
        }

        // Resolve IPv6 if requested
        if (\in_array($ipVersion, [self::IP_VERSION_BOTH, self::IP_VERSION_V6], true)) {
            $dns = dns_get_record($domain, \DNS_AAAA);
            if (false !== $dns && \is_array($dns) && !empty($dns[0]['ipv6'])) {
                $ips[] = $dns[0]['ipv6'];
            } elseif (self::IP_VERSION_V6 === $ipVersion) {
                throw new DnsResolutionException("Could not resolve IPv6 address for $domain");
            }
        }

        return $ips;
    }

    /**
     * Fetches the current public IPv4 address from an external HTTP service.
     * Override in tests to avoid real HTTP calls.
     */
    protected function fetchExternalIpV4(): ?string
    {
        $serviceUrl = $_ENV['EXTERNAL_IPV4_SERVICE'] ?? 'https://api4.ipify.org';
        $ip = @file_get_contents($serviceUrl);
        if (false === $ip) {
            return null;
        }
        $ip = trim($ip);
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return null;
        }

        return $ip;
    }

    /**
     * Updates the .htaccess file with IP addresses from all configured domains.
     *
     * @param bool $force If true, bypasses cache and forces update
     *
     * @throws DnsResolutionException  When DNS resolution fails
     * @throws HtaccessUpdateException When .htaccess update fails
     *
     * @return array<string, array<string>> Map of domains to their resolved IPs
     */
    public function updateIpAddresses(bool $force = false): array
    {
        $domains = $this->getDomains();
        if (empty($domains)) {
            throw new \RuntimeException('No domains configured. Please set DNS_DOMAINS in your .env file.');
        }

        $updatedIps = [];
        $allNewIps = [];

        // Resolve IPs for all domains
        foreach ($domains as $domainConfig) {
            $domain = $domainConfig['domain'];
            $ipVersion = $domainConfig['ipVersion'];

            $cacheKey = "dynamic_dns_ips_{$ipVersion}_".md5($domain);
            $newIps = $this->resolveIps($domain, $ipVersion);
            $updatedIps[$domain] = $newIps;

            // Check cache if not forced
            if (!$force) {
                $cachedIps = $this->cache->get($cacheKey, function (ItemInterface $item) use ($newIps) {
                    $item->expiresAfter(86400);

                    return $newIps;
                });

                // Skip if IPs haven't changed
                if ($this->areIpsEqual($cachedIps, $newIps)) {
                    $this->logger->info("No IP changes for domain: $domain");
                    continue;
                }
            }

            // Update cache
            $this->cache->delete($cacheKey);
            $this->cache->get($cacheKey, function (ItemInterface $item) use ($newIps) {
                $item->expiresAfter(86400);

                return $newIps;
            });

            $allNewIps = array_merge($allNewIps, $newIps);
        }

        // Update .htaccess if we have any changes
        if (!empty($allNewIps)) {
            $this->updateHtaccess($allNewIps);
        }

        return $updatedIps;
    }

    /**
     * Updates the .htaccess file with the new IP addresses.
     *
     * @param array<string> $ips List of IP addresses to add to .htaccess
     *
     * @throws HtaccessUpdateException When file operations fail
     */
    private function updateHtaccess(array $ips): void
    {
        $htaccessPath = $this->projectDir.'/public/.htaccess';
        $backupPath = null;

        try {
            if (!file_exists($htaccessPath)) {
                throw new HtaccessUpdateException(".htaccess file not found at: $htaccessPath");
            }

            $htaccess = file_get_contents($htaccessPath);
            if (false === $htaccess) {
                throw new HtaccessUpdateException('Failed to read .htaccess file');
            }

            // Create backup
            $backupPath = $htaccessPath.'.bak-'.date('Y-m-d-His');
            if (!copy($htaccessPath, $backupPath)) {
                throw new HtaccessUpdateException("Failed to create backup at: $backupPath");
            }

            // Create new IP block
            $startMarker = '# START DYNAMIC DNS BLOCK';
            $endMarker = '# END DYNAMIC DNS BLOCK';

            $newBlock = "$startMarker\n";
            foreach ($ips as $ip) {
                $newBlock .= "            Require ip $ip\n";
            }
            $newBlock .= "            $endMarker";

            // Update or insert block
            if (str_contains($htaccess, $startMarker)) {
                $pattern = "/$startMarker.*$endMarker/s";
                $htaccess = preg_replace($pattern, $newBlock, $htaccess);
            } else {
                $htaccess = str_replace('            Require env development', "$newBlock\n            Require env development", $htaccess);
            }

            // Save changes
            if (false === file_put_contents($htaccessPath, $htaccess)) {
                throw new HtaccessUpdateException('Failed to write to .htaccess file');
            }

        } catch (\Exception $e) {
            // Restore backup if available
            if (null !== $backupPath && file_exists($backupPath)) {
                if (!copy($backupPath, $htaccessPath)) {
                    $this->logger->error('Failed to restore .htaccess backup', [
                        'backup_path' => $backupPath,
                        'htaccess_path' => $htaccessPath,
                    ]);
                } else {
                    $this->logger->info('Successfully restored .htaccess from backup');
                }
            }
            throw new HtaccessUpdateException($e->getMessage(), 0, $e);
        }
    }

    /**
     * Compares two arrays of IPs for equality.
     */
    private function areIpsEqual(array $ips1, array $ips2): bool
    {
        sort($ips1);
        sort($ips2);

        return $ips1 === $ips2;
    }
}
