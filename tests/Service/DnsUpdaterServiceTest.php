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

    /**
     * Set up test environment before each test.
     */
    protected function setUp(): void
    {
        // Create logger mock
        $this->logger = $this->getMockBuilder(LoggerInterface::class)
                             ->getMock();

        // Create cache mock with correct type-hinting
        $this->cache = $this->getMockBuilder(CacheInterface::class)
                            ->getMock();

        // Create temporary directory for .htaccess tests
        $this->tempDir = sys_get_temp_dir().'/dns_updater_test_'.uniqid();
        mkdir($this->tempDir.'/public', 0777, true);

        // Initialize service with mocks
        $this->service = new DnsUpdaterService(
            $this->tempDir,
            $this->logger,
            $this->cache
        );

        // Create mock .htaccess file
        file_put_contents(
            $this->tempDir.'/public/.htaccess',
            "Require env development\n"
        );

        // Set environment variable for testing
        $_ENV['DNS_DOMAINS'] = 'example.org,example.com';
    }

    /**
     * Clean up test environment after each test.
     */
    protected function tearDown(): void
    {
        if (file_exists($this->tempDir)) {
            $this->removeDirectory($this->tempDir);
        }
    }

    /**
     * Test IP address update when no changes are detected.
     */
    public function testUpdateIpAddressesWithNoChanges(): void
    {
        // Create mock for cache item
        /** @var ItemInterface&MockObject */
        $cacheItem = $this->getMockBuilder(ItemInterface::class)
                          ->getMock();

        $cacheItem->method('expiresAfter')
                  ->willReturn($cacheItem);

        // Configure cache behavior for get()
        $this->cache->method('get')
                    ->willReturnCallback(function (string $key, callable $callback) {
                        // Call cache callback with our mock item
                        return ['192.168.1.1'];
                    });

        // Configure cache behavior for delete()
        $this->cache->method('delete')
                    ->willReturn(true);

        // Call service method
        $updatedIps = $this->service->updateIpAddresses();

        // Verify results
        $this->assertIsArray($updatedIps);
        $this->assertArrayHasKey('example.org', $updatedIps);
        $this->assertArrayHasKey('example.com', $updatedIps);
    }

    /**
     * Helper method for recursive directory deletion.
     *
     * @param string $dir Directory path to remove
     *
     * @throws \RuntimeException If directory scanning fails
     */
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
