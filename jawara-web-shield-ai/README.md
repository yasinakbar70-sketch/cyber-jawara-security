# Jawara Web Shield AI

**Advanced WordPress Security Plugin with AI-Powered Malware Detection**

Jawara Web Shield AI adalah plugin keamanan WordPress tingkat lanjut yang mengintegrasikan Google Gemini API untuk deteksi malware otomatis, proteksi login, firewall IP, dan monitoring keamanan real-time.

## 🚀 Fitur Utama

### 1. **Sistem API Gemini (AI Studio)**
- Integrasi dengan Google Gemini API untuk analisis file tingkat lanjut
- Analisis otomatis file PHP mencurigakan
- Klasifikasi risiko (Low / Medium / High)
- Rekomendasi tindakan (Delete / Edit / Monitor / Safe)
- Analisis pola serangan dari log keamanan

### 2. **Proteksi Login**
- Batasi percobaan login (default: 5 percobaan)
- Lockout IP sementara (default: 30 menit)
- Logging setiap percobaan login gagal
- Auto-block IP setelah melampaui batas

### 3. **Firewall IP**
- Blacklist dan whitelist IP address
- Blokir akses 403 sebelum WordPress fully load
- Interface admin untuk mengelola IP list
- Whitelist priority (override blacklist)

### 4. **File Integrity Scanner**
- Baseline hash SHA1 untuk semua file .php di wp-content
- Deteksi perubahan file otomatis
- Analisis dengan AI jika ada perubahan
- Logging hasil analisis

### 5. **Malware Signature Scanner**
- Scan pola berbahaya:
  - `base64_decode`, `eval(`, `gzinflate(`, `rot13(`, `preg_replace("/e"`
  - `exec()`, `system()`, `shell_exec()`, `passthru()`
  - `unserialize()`, `extract()`, `parse_str()`
  - Dan puluhan pola lainnya

### 6. **Notifikasi Telegram**
- Alert real-time via Telegram Bot
- Notifikasi untuk:
  - Perubahan file terdeteksi
  - File berbahaya ditemukan
  - Percobaan hacking (brute force)
  - IP diblokir firewall

### 7. **Dashboard Admin Lengkap**
- **Tab Pengaturan**: API Key, Token Telegram, Batas login, etc
- **Tab Firewall**: Manajemen blacklist/whitelist IP
- **Tab Scan AI**: Manual scan dengan progress
- **Tab Security Logs**: Semua event keamanan dengan filtering

### 8. **Logging & Reporting**
- Database logging untuk semua events
- Statistics dan trend analysis
- Export-ready format
- Auto-cleanup old logs (30 hari default)

## 📋 Requirements

- **PHP**: 7.4 atau lebih tinggi
- **WordPress**: 5.0 atau lebih tinggi
- **MySQL**: 5.7 atau lebih tinggi
- **Extensions**: json, curl (untuk API calls)
- **Internet**: Koneksi stabil untuk Gemini API & Telegram Bot

## 💻 Instalasi

### Langkah 1: Download & Extract
```bash
cd wp-content/plugins/
unzip jawara-web-shield-ai.zip
# Atau jika sudah ada folder:
# copy -r jawara-web-shield-ai/ wp-content/plugins/
```

### Langkah 2: Aktifkan Plugin
1. Login ke WordPress Admin Dashboard
2. Pergi ke **Plugins** → **Installed Plugins**
3. Cari "Jawara Web Shield AI"
4. Klik **Activate**

### Langkah 3: Konfigurasi API Key Gemini
1. Pergi ke https://makersuite.google.com/app/apikey
2. Buat API key baru (gunakan free tier)
3. Copy API key
4. Di WordPress, pergi ke **Jawara Shield AI** → **Settings**
5. Paste API key di field "Gemini API Key"
6. Klik **Save Changes**

### Langkah 4: (Opsional) Konfigurasi Telegram
1. Buat Bot baru di Telegram: chat dengan @BotFather
2. Copy Bot Token
3. Get Chat ID: chat dengan @userinfobot
4. Di WordPress, fill:
   - Telegram Bot Token
   - Telegram Chat ID
   - Enable Telegram Alerts (checkbox)
5. Klik **Save Changes**

### Langkah 5: Jalankan Initial Scan
1. Pergi ke **Jawara Shield AI** → **Scan AI**
2. Klik **Start Manual Scan**
3. Tunggu proses selesai (bisa beberapa menit)
4. Baseline akan tersimpan otomatis

## 🔧 Konfigurasi

### Settings Tab

| Setting | Default | Description |
|---------|---------|-------------|
| Gemini API Key | - | Required untuk AI analysis |
| Login Attempts Limit | 5 | Max percobaan sebelum lockout |
| Lockout Duration | 30 | Durasi lockout dalam menit |
| Enable Telegram | Off | Aktifkan notifikasi Telegram |
| Telegram Token | - | Bot token dari @BotFather |
| Telegram Chat ID | - | Chat ID dari @userinfobot |

### Firewall Tab
- Tambahkan/hapus IP blacklist
- Tambahkan/hapus IP whitelist
- Whitelist akan override blacklist

### Scan AI Tab
- Jalankan manual scan kapan saja
- Automatic scan berjalan:
  - Hourly: File integrity check
  - Daily: Malware signature scan

### Security Logs Tab
- Lihat semua security events
- Filter by event type atau severity
- Export log untuk analisis

## 📊 API Gemini Request/Response Example

### Request:
```json
{
  "contents": [
    {
      "parts": [
        {
          "text": "Analyze the following PHP file for potential malware...\n\n<?php eval($_REQUEST['cmd']); ?>"
        }
      ]
    }
  ],
  "generationConfig": {
    "temperature": 0.7,
    "maxOutputTokens": 1024
  }
}
```

### Response Format:
```
MALICIOUS: Yes
RISK_LEVEL: Critical
PATTERNS: eval(), $_REQUEST, remote code execution
ACTION: Delete
EXPLANATION: This file is a classic PHP web shell used for remote code execution...
```

## 🔐 Security Features

### Input Sanitization
- Semua input disanitasi dengan `sanitize_text_field()`
- `sanitize_textarea_field()` untuk multi-line input
- `intval()` untuk numeric input

### Output Escaping
- `esc_html()` untuk plain text
- `esc_attr()` untuk attributes
- `wp_json_encode()` untuk JSON

### CSRF Protection
- Semua form menggunakan WordPress nonce
- AJAX mengecek nonce sebelum eksekusi
- `check_ajax_referer()` validation

### Permission Checking
- Hanya `manage_options` yang bisa akses admin page
- Setiap AJAX action mengecek capabilities

## 📝 Log Format

### Database Table: `wp_jwsai_logs`

```sql
- id: Primary key
- timestamp: Event timestamp
- event_type: Type of event (file_integrity, malware_signature, login_attempt, firewall, etc)
- severity: Low, Medium, High, Critical
- message: Event description
- file_path: Path to file (if applicable)
- ip_address: Client IP address
- user_id: WordPress user ID
- ai_analysis: JSON-encoded AI analysis result
```

## 🚨 Security Event Types

| Event Type | Severity | Description |
|-----------|----------|-------------|
| login_attempt | Low/High | Failed/Locked-out login |
| malware_signature | Medium/Critical | Suspicious pattern detected |
| file_integrity | High | File modification detected |
| firewall | Medium | IP blocked/action taken |

## ⏱️ Scheduled Tasks (Cron)

| Hook | Schedule | Action |
|------|----------|--------|
| jwsai_hourly_file_check | Hourly | Check file integrity |
| jwsai_daily_signature_scan | Daily | Scan malware signatures |

## 🐛 Troubleshooting

### "Gemini API key not configured"
- Pastikan API key sudah diisi di Settings tab
- Verify API key di https://makersuite.google.com/app/apikey

### "Scan tidak berjalan"
- Pastikan WordPress cron jobs berjalan
- Check dengan: `curl https://yoursite.com/wp-cron.php`
- Verifikasi WP_DISABLE_FATAL_ERROR_HANDLER tidak di-disable

### "Telegram notifikasi tidak diterima"
- Verify Bot Token dan Chat ID
- Test dengan curl:
```bash
curl -X POST https://api.telegram.org/bot<TOKEN>/sendMessage \
  -d "chat_id=<CHAT_ID>" \
  -d "text=Test message"
```

### "AJAX request failed"
- Cek browser console untuk error
- Verify nonce di AJAX request
- Cek WordPress logs di `wp-content/debug.log`

## 🔄 Update & Maintenance

### Manual Log Cleanup
```php
// Di functions.php atau via admin
Jawara_Security_Logger::cleanup_old_logs( 30 ); // Keep last 30 days
```

### Export Security Logs
- Akses database langsung atau gunakan tools seperti phpMyAdmin
- Query: `SELECT * FROM wp_jwsai_logs ORDER BY timestamp DESC`

## 📈 Performance Considerations

- File scanning bisa resource-intensive untuk large installations
- Scan berjalan di background via cron
- Limit max 50,000 characters per file untuk AI analysis
- Cache Gemini API responses untuk mengurangi quota usage

## 🔗 API Integration

### Gemini API
- **Free Tier**: 60 requests/minute
- **Paid**: Higher limits
- **Endpoint**: `https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent`
- **Docs**: https://ai.google.dev/docs

### Telegram Bot API
- **Free**: Unlimited messages
- **Rate Limit**: 30 messages/second per bot
- **Endpoint**: `https://api.telegram.org/bot<TOKEN>/sendMessage`
- **Docs**: https://core.telegram.org/bots/api

## 📄 Lisensi

GPL v2 atau lebih tinggi. See LICENSE file untuk details.

## 👨‍💻 Support & Development

- Report bugs di issue tracker
- Contribute via pull requests
- Share feedback untuk improvement

## ⚠️ Disclaimer

Plugin ini dirancang untuk meningkatkan keamanan WordPress. Namun, tidak ada sistem keamanan yang 100% sempurna. Selalu:
- Maintain regular backups
- Keep WordPress & plugins updated
- Use strong passwords
- Monitor security logs regularly
- Consider additional security measures

## 🙏 Credits

Dikembangkan dengan ❤️ menggunakan:
- WordPress Plugin Development Standards
- Google Gemini AI
- Telegram Bot API

---

**Last Updated**: November 2025  
**Version**: 1.0.0  
**Tested on**: WordPress 6.4+, PHP 7.4+
