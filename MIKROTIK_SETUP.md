# Mikrotik Hotspot Voucher System

## Installation & Setup Guide

এই সিস্টেম আপনার **Mikrotik Router** এ locally চলবে।

### Prerequisites
- Mikrotik RouterOS 7.20.4 (বা নতুন)
- Local Network এ PHP server (Windows PC বা Linux)
- Router এবং Server same network এ

---

## Step 1: Server Setup

### 1.1 PHP Server Start করুন
```bash
cd "আপনার project folder"
php -S localhost:8000
```

### 1.2 Access করুন
```
http://192.168.88.X:8000  (আপনার PC IP)
```

---

## Step 2: Mikrotik Configuration

### 2.1 .env ফাইল আপডেট করুন

```env
# Mikrotik Router Configuration
MIKROTIK_ENABLED=true
MIKROTIK_HOST=192.168.88.1        # আপনার Mikrotik IP
MIKROTIK_PORT=8728                # Default API port
MIKROTIK_USERNAME=admin           # Mikrotik username
MIKROTIK_PASSWORD=your_password   # Mikrotik password
MIKROTIK_SSL=false                # SSL use করলে true করুন (port 8729)
MIKROTIK_PROFILE=default          # HotSpot Profile name
MIKROTIK_INTERFACE=bridge-local    # HotSpot Interface
```

### 2.2 Mikrotik Router Setup

#### A. Built-in Voucher System Enable করুন

Winbox দিয়ে Mikrotik এ login করুন:

1. **IP > Hotspot > Hotspots** এ গিয়ে আপনার HotSpot name note করুন
2. **IP > Hotspot > Profiles** এ যান
3. "default" profile খুজুন এবং edit করুন
4. Settings যেমন আছে রাখুন

#### B. API Access Enable করুন (Optional - Future use)

1. **System > Users** এ admin user edit করুন
2. **Permissions** এ "api" check করুন
3. Save করুন

#### C. HotSpot Vouchers Setup

1. **IP > Hotspot > Vouchers** এ যান
2. **New** button দিয়ে voucher package create করুন:
   - **Profile:** default
   - **Price Unit:** days বা hours
   - **Comment:** Custom voucher

---

## Step 3: Website Features

### Payment Flow:
```
User এ Visit করে (http://192.168.88.X:8000)
    ↓
Package Select করে
    ↓
Phone Number দেয়
    ↓
Payment Gateway দিয়ে pay করে (Optional)
    ↓
Voucher Code পায়
    ↓
WiFi এ login করার সময় Username তে ভাউচার কোড paste করে
```

### Admin Panel:
```
URL: http://192.168.88.X:8000/admin.php
Password: admin123 (পরিবর্তন করুন!)

Features:
- Dashboard দেখুন
- Voucher History check করুন
- Settings configure করুন
```

---

## Step 4: Mikrotik HotSpot Login Configuration

User যখন WiFi connect করে:

1. Browser এ কোনো page খোলে
2. Login page আসে (default Mikrotik login)
3. **Username:** Voucher Code পেস্ট করে
4. **Password:** কিছু দেয় না (বা same code)
5. Connected!

---

## Advanced Configuration

### Voucher এর validity adjust করুন:

`.env` এ:
```env
MIKROTIK_EXPIRE_AFTER=7d    # 7 days, 30d, 1h, 5h, etc.
```

### Custom Voucher Format:

`mikrotik_config.php` এ:
```php
'voucher' => [
    'format' => '{XXXXXX}',    // Random 6 chars
    'expire_after' => '7d',
]
```

---

## Troubleshooting

### Issue: Voucher কাজ করছে না
**Solution:**
1. Mikrotik Router reboot করুন
2. HotSpot profile update করুন
3. API connection চেক করুন

### Issue: Server connect হচ্ছে না
**Solution:**
1. `.env` এ সঠিক Mikrotik IP দিন
2. Firewall check করুন
3. API port open আছে কিনা যাচাই করুন

### Issue: Payment যাচাই হচ্ছে না
**Solution:**
1. API Key সঠিক কিনা check করুন
2. `.env` আপডেট করুন
3. UddoktaPay account verify করুন

---

## Security Tips

⚠️ **Important:**
1. `admin.php` এ password change করুন
2. `.env` file public না করুন (`.gitignore` তে আছে)
3. Mikrotik default password change করুন
4. HTTPS ব্যবহার করুন (production তে)

---

## File Structure

```
hotspotzone/
├── .env                      # Configuration
├── index.php                 # Home page
├── start_payment.php         # Payment initiate
├── success.php               # Payment success + Voucher
├── admin.php                 # Admin panel
├── cancel.php                # Payment cancel
├── MikrotikAPI.php           # Mikrotik connection
├── mikrotik_config.php       # Mikrotik settings
├── uddoktapay_config.php     # Payment config
├── codes/                    # Voucher CSV files
└── image/                    # Icons
```

---

## Next Steps

1. ✅ Local network এ test করুন
2. ✅ Voucher manually create করে test করুন
3. ✅ Admin panel দেখুন
4. ✅ Payment gateway test করুন (real transaction না করে)
5. ✅ Live launch করুন

---

## Support

Any issues? Check:
- `debug_log.txt` - Error logs
- `mikrotik_api.log` - API logs
- `.env` configuration

---

**Version:** 1.0  
**Last Updated:** Feb 2026
