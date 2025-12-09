# Changelog

Semua perubahan notable pada plugin Cyber Jawara Security akan didokumentasikan di file ini.

Format yang digunakan mengikuti [Keep a Changelog](https://keepachangelog.com/).

## [2.1.6] - 2025-12-09

### Removed
- **Cleanup**: Menghapus kode scanner lama (Database Scanner & Vulnerability Scanner) yang belum digunakan.
- **Cleanup**: Menghapus file ZIP debug dan membersihkan struktur direktori.

## [2.1.5] - 2025-12-09

### Stable
- **Final Release**: Update rilis final v2.1.5 yang sudah diuji, bebas debug code, dan menyertakan perbaikan struktur repository dan keamanan AJAX.

## [2.1.4] - 2025-12-09

### Added
- **Debug Features**: Menambahkan output diagnostik untuk troubleshooting masalah auto-update.

## [2.1.3] - 2025-12-09

### Fixed
- **Stability**: Memperbaiki masalah "Forbidden" saat tes Telegram dan Scan dengan menangani kedaluwarsa Nonce secara lebih baik (memberi pesan "Please refresh" alih-alih error server).
- **Security**: Memperkuat validasi request AJAX pada semua fitur.

## [2.1.2] - 2025-12-09

### Changed
- **Structure Update**: Memindahkan seluruh file plugin ke root repository untuk memperbaiki masalah instalasi dari GitHub ZIP. Sekarang file bisa diinstall langsung tanpa error "No valid plugin found".

## [2.1.1] - 2025-12-09

### Fixed
- **Critical Fix**: Menambahkan pembuatan tabel `traffic_log` saat aktivasi untuk mencegah error dashboard
- **Critical Fix**: Memperbaiki alur setup 2FA untuk menampilkan kode backup sebelum reload halaman
- **Critical Fix**: Memperbaiki tombol "Show Backup Codes" yang tidak berfungsi
- **UI Improvement**: Memperbaiki selector ID JavaScript untuk form manual scan dan firewall IP
- **Improvement**: Validasi Tes Telegram diperbarui (Bot Token sekarang opsional/built-in)

## [2.1.0] - 2024-12-03

### Changed
- **Rebranding**: Plugin name changed from "Jawara Web Shield AI" to "Cyber Jawara Security"
- Updated all user-facing names in admin interface
- Updated documentation (README.md, DEVELOPER_GUIDE.md)
- Technical identifiers (text domain, file names, class names) remain unchanged for compatibility

### Added
- **Permanent IP Blocking**: IPs are now automatically added to permanent blacklist after 5 failed login attempts
- Enhanced Telegram notifications to indicate permanent blocks

## [2.0.0] - 2024-12-01

### 🚀 Major Release - Enterprise-Grade Security

### Added
- **Advanced Malware Scanner** dengan multi-engine detection
  - 200+ malware signatures (PHP execution, obfuscation, backdoors, SQL injection, etc)
  - Heuristic analysis dengan 8+ rules
  - AI-powered analysis integration dengan Gemini
  - Threat scoring system (0-100)
  - Quarantine system untuk infected files
  - Batch scanning capabilities
- **Advanced Firewall** dengan intelligent protection
  - SQL injection detection dan prevention (24+ patterns)
  - XSS (Cross-Site Scripting) protection (26+ patterns)
  - Rate limiting per IP address
  - Bad bot filtering (18+ known malicious bots)
  - Custom firewall rules engine
  - Geo-blocking berdasarkan country code
- **Geo-IP Service**
  - IP geolocation lookup menggunakan ipapi.co
  - Country detection dan blocking
  - Caching untuk performance (24 hours)
  - Support 60+ countries
- **Threat Intelligence Integration**
  - AbuseIPDB API integration
  - IP reputation checking
  - Abuse confidence scoring
  - Auto-blacklist untuk malicious IPs
  - Threat level classification (clean/low/medium/high/critical)
  - Database caching untuk repeated lookups
  - New database table: `wp_jwsai_threat_intelligence`
- **Admin Interface** untuk Advanced Security
  - Threat Intelligence statistics dashboard
  - Attack protection toggles (SQL/XSS/Bots)
  - Rate limiting configuration
  - Geo-blocking management
  - AbuseIPDB API key setup

### Enhanced
- Security logging dengan threat data
- Telegram notifications untuk firewall blocks
- Real-time threat monitoring
- Performance dengan caching strategies

### Database
- New table: `wp_jwsai_threat_intelligence` untuk IP reputation data
- Indexes untuk fast lookups (ip_address, last_checked, is_malicious)

### Configuration
- 8 new options untuk advanced security
- Default values optimized untuk security
- Backward compatible dengan 1.x settings

### Performance
- Efficient caching untuk Geo-IP lookups
- Transient-based rate limiting
- Minimal database queries
- Smart signature checking

## [1.0.2] - 2024-12-01

### Fixed
- Removed duplicate Plugin Update Checker initialization code
- Synchronized plugin version constant (1.0.2) with header version
- Re-enabled CSRF protection (nonce verification) for Telegram test functionality
- Fixed firewall log severity levels from 'info' to 'medium' to match documentation
- Increased file truncation limit from 50KB to 100KB for better AI analysis
- Added comprehensive error handling in file scanner for read failures

### Added
- Complete admin.css with modern gradient styling and responsive design
- Complete admin.js with AJAX handlers for all admin functionality
- Languages directory structure for i18n support
- Tab navigation with localStorage persistence
- IP address validation in JavaScript

### Improved
- Enhanced user experience with loading spinners and progress indicators
- Better error messages and user feedback
- More robust file reading with proper validation
- Documentation accuracy in README and CHANGELOG

## [1.0.0] - 2024-11-29

### Added
- Initial release dari Jawara Web Shield AI
- Integrasi Gemini API untuk AI-powered malware analysis
- Proteksi login dengan rate limiting dan IP lockout
- IP Firewall dengan blacklist dan whitelist functionality
- File Integrity Scanner dengan SHA1 hashing
- Malware Signature Scanner dengan 40+ dangerous patterns
- Notifikasi Telegram untuk security alerts
- Security Logging dengan database storage
- Admin Dashboard dengan 4 tabs (Settings, Firewall, Scan AI, Logs)
- AJAX functionality untuk IP management dan manual scanning
- Scheduled cron jobs untuk background scanning
- Input sanitization dan output escaping
- CSRF protection dengan WordPress nonce
- Permission checking untuk admin-only features
- Responsive admin interface dengan CSS styling
- Comprehensive documentation dan developer guide

### Features
1. **Gemini AI Integration**
   - File analysis untuk malware detection
   - Risk level classification (Low/Medium/High)
   - Recommended actions (Delete/Edit/Monitor/Safe)
   - Attack pattern analysis

2. **Login Protection**
   - Configurable login attempt limits
   - Automatic IP lockout
   - Failed attempt logging
   - Login statistics

3. **Firewall Management**
   - IP Blacklist functionality
   - IP Whitelist with priority
   - 403 error blocking
   - AJAX-based management

4. **File Security**
   - Baseline hash creation
   - Integrity checking
   - Change detection
   - AI-powered analysis untuk changes

5. **Malware Detection**
   - Signature-based scanning
   - Pattern recognition
   - Gemini AI analysis
   - Pattern logging

6. **Notifications**
   - Telegram Bot integration
   - Real-time alerts
   - Severity-based messaging
   - Markdown formatting

7. **Logging & Reporting**
   - Event logging ke database
   - Statistics gathering
   - Log filtering dan search
   - Auto-cleanup

### Security Features
- All inputs sanitized
- All outputs escaped properly
- CSRF tokens untuk semua forms
- Permission-based access control
- SQL injection prevention dengan prepared statements
- XSS protection dengan proper escaping

### Documentation
- Complete README dengan installation guide
- Developer guide dengan code examples
- API documentation
- Troubleshooting section
- Performance tips

### Compatibility
- WordPress 5.0+
- PHP 7.4+
- MySQL 5.7+

## Future Roadmap

### Planned untuk v1.1
- [ ] Two-factor authentication (2FA)
- [ ] Advanced firewall rules (geo-blocking, rate limiting)
- [ ] Integration dengan WPScan untuk CVE checking
- [ ] Email notifications alternative to Telegram
- [ ] REST API untuk third-party integrations
- [ ] Log export to CSV/JSON
- [ ] Multi-site WordPress support
- [ ] Performance metrics dashboard

### Planned untuk v2.0
- [ ] Machine learning untuk pattern detection
- [ ] Automated malware removal
- [ ] Backup & restore integration
- [ ] Admin activity logging
- [ ] User behavior analytics
- [ ] Custom firewall rules builder
- [ ] Slack integration
- [ ] Webhook support untuk custom integrations

## Known Issues

Tidak ada known issues pada v1.0.0.

Untuk melaporkan bug, silahkan buat issue di GitHub repository.

## Support

Untuk support, dokumentasi, atau pertanyaan:
- Email: support@jawaraweb.shield
- GitHub: https://github.com/yourusername/jawara-web-shield-ai
- WordPress.org Forums: https://wordpress.org/support/plugin/jawara-web-shield-ai

---

**Version History:**
- 2.0.0 (2024-12-01) - **MAJOR RELEASE** - Enterprise-Grade Security
- 1.0.2 (2024-12-01) - Bug Fixes & Improvements
- 1.0.0 (2024-11-29) - Initial Release
