<?php

declare(strict_types=1);

namespace Monosize\DynamicDnsIpUpdater\Tests\Service;

use Monosize\DynamicDnsIpUpdater\Service\DnsUpdaterService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Unit tests for DnsUpdaterService.
 */
class DnsUpdaterServiceTest extends TestCase
{
    private DnsUpdaterService $service;
    private LoggerInterface $logger;
    /** @var CacheInterface&MockObject */
    private CacheInterface $cache;
    private string $tempDir;

    protected function setUp(): void
    {
        $this->logger = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $this->cache = $this->getMockBuilder(CacheInterface::class)->getMock();

        $this->tempDir = sys_get_temp_dir().'/dns_updater_test_'.uniqid();
        mkdir($this->tempDir.'/public', 0777, true);

        $this->service = new DnsUpdaterService(
            $this->tempDir,
            $this->logger,
            $this->cache
        );

        file_put_contents(
            $this->tempDir.'/public/.htaccess',
            "Require env development\n"
        );

        $_ENV['DNS_DOMAINS'] = 'example.org,example.com';
        unset($_ENV['EXTERNAL_IPV4_SERVICE']);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempDir)) {
            $this->removeDirectory($this->tempDir);
        }
    }

    public function testUpdateIpAddressesWithNoChanges(): void
    {
        /** @var ItemInterface&MockObject */
        $cacheItem = $this->getMockBuilder(ItemInterface::class)->getMock();
        $cacheItem->method('expiresAfter')->willReturn($cacheItem);

        $this->cache->method('get')
            ->willReturnCallback(function (string $key, callable $callback) {
                return ['192.168.1.1'];
            });

        $this->cache->method('delete')->willReturn(true);

        $updatedIps = $this->service->updateIpAddresses();

        $this->assertIsArray($updatedIps);
        $this->assertArrayHasKey('example.org', $updatedIps);
        $this->assertArrayHasKey('example.com', $updatedIps);
    }

    public function test_resolveIps_falls_back_to_external_service_when_no_ipv4_dns_record(): void
    {
        $_ENV['DNS_DOMAINS'] = 'ipv6only.example:both';

        $service = new class($this->tempDir, $this->logger, $this->cache) extends DnsUpdaterService {
            protected function fetchExternalIpV4(): ?string
            {
                return '92.208.188.190';
            }
        };

        file_put_contents(
            $this->tempDir.'/public/.htaccess',
            "# START DYNAMIC DNS BLOCK\n            # END DYNAMIC DNS BLOCK\nRequire env development\n"
        );

        $this->cache->method('get')->willReturnCallback(function (string $key, callable $callback) {
            /** @var ItemInterface&MockObject */
            $item = $this->getMockBuilder(ItemInterface::class)->getMock();
            $item->method('expiresAfter')->willReturn($item);
            return $callback($item);
        });
        $this->cache->method('delete')->willReturn(true);

        // ipv6only.example has no A record → gethostbyname returns the domain itself
        // Service should fall back to external service and include the IPv4
        $updatedIps = $service->updateIpAddresses(true);

        $htaccess = file_get_contents($this->tempDir.'/public/.htaccess');
        $this->assertStringContainsString('Require ip 92.208.188.190', $htaccess);
    }

    public function test_resolveIps_external_ipv4_not_used_when_dns_resolves_ipv4(): void
    {
        $_ENV['DNS_DOMAINS'] = 'example.org:ip4';

        $externalCalled = false;
        $service = new class($this->tempDir, $this->logger, $this->cache, $externalCalled) extends DnsUpdaterService {
            public function __construct(
                string $projectDir,
                \Psr\Log\LoggerInterface $logger,
                \Symfony\Contracts\Cache\CacheInterface $cache,
                private bool &$externalCalled
            ) {
                parent::__construct($projectDir, $logger, $cache);
            }

            protected function fetchExternalIpV4(): ?string
            {
                $this->externalCalled = true;
                return '1.2.3.4';
            }
        };

        file_put_contents(
            $this->tempDir.'/public/.htaccess',
            "# START DYNAMIC DNS BLOCK\n            # END DYNAMIC DNS BLOCK\nRequire env development\n"
        );

        $this->cache->method('get')->willReturnCallback(function (string $key, callable $callback) {
            /** @var ItemInterface&MockObject */
            $item = $this->getMockBuilder(ItemInterface::class)->getMock();
            $item->method('expiresAfter')->willReturn($item);
            return $callback($item);
        });
        $this->cache->method('delete')->willReturn(true);

        $service->updateIpAddresses(true);

        // example.org resolves via DNS → external service should NOT be called
        $this->assertFalse($externalCalled);
    }

    private function removeDirectory(string $dir): void
    {
        if (!file_exists($dir)) {
            return;
        }

        $scanResult = scandir($dir);
        if (false === $scanResult) {
            throw new \RuntimeException("Could not scan directory: $dir");
        }

        $files = array_diff($scanResult, ['.', '..']);
        foreach ($files as $file) {
            $path = $dir.'/'.$file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}
