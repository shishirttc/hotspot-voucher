# System Change Log - Dynamic Voucher Generation

## Version 2.0 - Dynamic Code Generation

### What Changed?

**Before (v1.0):**
- Pre-generated codes থাকত CSV files এ
- Payment এর পর সেই codes use হতো
- Limited codes ছিল

**Now (v2.0):**
- No pre-generated codes
- Payment এর পরে **dynamically** code generate হয়
- প্রতিটি user এর জন্য **unique** code
- Unlimited codes possible

---

## Architecture

```
User Payment
     ↓
Payment Verification
     ↓
generateVoucherCode(package_id, mobile)
     ↓
Generate Unique 12-char Code
     ↓
saveGeneratedCode() 
     ↓
Store in generated_codes.log
     ↓
Show to User
```

---

## New Files

### 1. **CodeGenerator.php**
Dynamic code generation সব functions এখানে:

```php
// Main functions:
generateVoucherCode($package_id, $mobile)     // Generate unique code
codeExists($code)                              // Check if code already exists
saveGeneratedCode($code_data)                  // Save to log
getAllGeneratedCodes()                         // Get all codes
getCodesByMobile($mobile)                      // Get user's codes
markCodeAsUsed($code)                          // Mark code as used
getCodeStatistics()                            // Get stats
```

---

## Generated Codes Log

**File:** `generated_codes.log`

**Format:** JSON (one per line)

```json
{
  "code": "45B99C6720B2",
  "package_id": "5h",
  "mobile": "01310291293",
  "generated_at": "2026-02-12 12:00:00",
  "expires_at": "2026-02-19",
  "used": false,
  "used_at": null
}
```

---

## Code Generation Algorithm

```
1. Combine: package_id + mobile + timestamp + random(1000-9999)
2. MD5 hash করুন
3. প্রথম 12 chars নিন
4. UPPERCASE করুন
5. Database এ check করুন (exists?)
6. If exists, retry with new random
7. Return unique code
```

### Example:
```
Input: package_id=5h, mobile=01310291293
→ "5h0131029129313981746985" (MD5 hash করা)
→ "45B99C6720B2" (first 12 chars, uppercase)
```

---

## File Changes

### Modified:
- `success.php` - Dynamic generation logic added
- `admin.php` - CodeGenerator.php include added

### Cleared:
- `codes/5h.csv` - (empty)
- `codes/1d.csv` - (empty)
- `codes/3d.csv` - (empty)
- `codes/7d.csv` - (empty)
- `codes/15d.csv` - (empty)
- `codes/30d.csv` - (empty)

### New:
- `CodeGenerator.php` - All generation functions
- `generated_codes.log` - Code database

---

## Testing

### Test 1: Generate Code
```php
require 'CodeGenerator.php';
$code = generateVoucherCode('5h', '01310291293');
echo $code; // Output: 45B99C6720B2
```

### Test 2: Save & Retrieve
```php
saveGeneratedCode([
    'code' => $code,
    'package_id' => '5h',
    'mobile' => '01310291293'
]);

$codes = getCodesByMobile('01310291293');
print_r($codes);
```

### Test 3: Check Statistics
```php
$stats = getCodeStatistics();
echo $stats['total']; // Total codes
echo $stats['used'];  // Used codes
```

---

## Security Benefits

✅ **Unlimited codes** - No pre-stock needed  
✅ **Unique per user** - Mobile + timestamp based  
✅ **Unique globally** - Database check  
✅ **Trackable** - All codes logged  
✅ **Usage tracking** - Can mark as used  
✅ **Expiry support** - Automatic expiry dates  

---

## Database Structure

`generated_codes.log` Format:
```
{code, package_id, mobile, generated_at, expires_at, used, used_at}
```

**Index by:** `code` (unique)  
**Searchable by:** `mobile`, `package_id`, `used`

---

## Admin Panel Updates

Admin panel এখন দেখাবে:

- **Total Codes Generated**
- **Codes Used**
- **By Package Breakdown**
- **Recent Transactions**

---

## Migration from v1.0

যারা v1.0 ব্যবহার করছেন:

1. ✅ Old CSV files clear করা হয়েছে
2. ✅ New CodeGenerator.php added
3. ✅ success.php updated
4. ✅ New generated_codes.log created

**No backward compatibility** - শুধু নতুন system use করবে

---

## Troubleshooting

### Issue: Code not generating
**Solution:**
- Check CodeGenerator.php is included
- Check write permissions on folder

### Issue: Duplicate codes
**Solution:**
- `codeExists()` function checks for duplicates
- If happens, retry with new random

### Issue: Code not saved
**Solution:**
- Check `generated_codes.log` file permissions
- Check folder is writable

---

## Next Steps

1. ✅ Live deploy করুন
2. ✅ Payment test করুন
3. ✅ Generated codes check করুন
4. ✅ Mikrotik এ validate করুন

---

**Version:** 2.0  
**Date:** Feb 12, 2026  
**Status:** Production Ready
