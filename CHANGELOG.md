## [1.2.0] - 2026-04-26

### Added
- External IPv4 fallback for DS-Lite / IPv6-only connections: when the configured domain has no A record, the public IPv4 is fetched from an HTTP service (default: `https://api4.ipify.org`) and written to `.htaccess`
- `EXTERNAL_IPV4_SERVICE` env var to override the fallback URL

## [1.1.0] - 2025-02-12

### Added
- IP version control for individual domains using the new DNS_DOMAINS format
- Support for IPv4-only configuration using `:ip4` suffix
- Support for IPv6-only configuration using `:ip6` suffix
- Enhanced domain parsing with IP version preference validation
- Updated documentation with new configuration examples

### Changed
- Modified DNS_DOMAINS environment variable format to support IP version preferences
- Improved cache key generation to account for IP version preferences
- Enhanced error handling for IP version-specific resolution failures

## [1.0.1] - 2025-02-12

### First Release

## [1.0.0] - 2025-02-12

### Changes
- Update README with enhanced documentation and examples
- Add MIT license