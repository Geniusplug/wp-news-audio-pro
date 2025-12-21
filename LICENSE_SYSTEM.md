# License System Documentation

## Overview

The WP News Audio Pro plugin implements a robust license verification system with the following features:

1. **Test Code System** - Localhost-only development codes
2. **Real Purchase Code Validation** - Envato API integration
3. **One Domain Per License** - Database-backed enforcement
4. **No Fatal Errors** - Graceful error handling throughout
5. **User-Friendly Messages** - Clear feedback for users

---

## 1. Test Code System

### Test Code
```
WNAP-DEV-TEST-2025
```

### Behavior
- **Works on**: localhost, 127.0.0.1, *.local, *.test, *.dev, and local IP addresses (10.x.x.x, 192.168.x.x, 172.16-31.x.x)
- **Does NOT work on**: Live domains (example.com, mysite.org, etc.)
- **Expiration**: 90 days from activation
- **Storage**: Encrypted in WordPress options table

### Detection Logic
The system checks the current domain against localhost patterns:
- Exact matches: `localhost`, `127.0.0.1`, `::1`
- Pattern matches: domains ending with `.local`, `.test`, `.dev`, `.localhost`
- IP ranges: `10.*`, `192.168.*`, `172.16-31.*`

### Error Messages
- On localhost: "Test license activated successfully! Valid for 90 days."
- On live domain: "Test code only works on localhost/development environments. Please use a valid purchase code on live sites." + Buy button

---

## 2. Real Purchase Code Validation

### Envato API Integration

**Endpoint**: `https://api.envato.com/v3/market/author/sale?code=XXX`

**Token**: `Bearer IRXxacDkuYPM8lFe9NCNZ3rh3RMQTp49`
- Configured via `WNAP_ENVATO_API_TOKEN` constant
- Can be overridden in `wp-config.php` with `WNAP_API_TOKEN`

### Response Handling

| Status Code | Meaning | Action |
|------------|---------|--------|
| 200 | Valid purchase | Check item ID → Check domain → Activate |
| 404 | Invalid code | Show error + Buy button |
| 403/401 | API error | Show support message |
| Other | Generic error | Show retry message |

### Success Flow
1. API returns 200 OK
2. Validate item ID (if configured)
3. Check if code is already registered to another domain
4. Register code in database
5. Save encrypted license data
6. Activate license
7. Show success message

### Error Messages

**404 - Not Found**
```
Purchase code not found. Please verify your code is correct.
[Buy License] button
```

**403/401 - Authentication Error**
```
Verification service error. Please contact support for assistance.
```

**Other Errors**
```
Verification failed. Please try again later or contact support.
```

---

## 3. One Domain Per License System

### Database Table

**Table Name**: `wp_wnap_licenses`

```sql
CREATE TABLE wp_wnap_licenses (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    purchase_code varchar(255) NOT NULL,
    domain varchar(255) NOT NULL,
    status varchar(20) DEFAULT 'active',
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY purchase_code (purchase_code)
);
```

### Logic Flow

#### Activation
1. User enters purchase code
2. System validates code format
3. System checks if code already exists in database
4. If exists and `status='active'` on different domain:
   - Show error: "This license is already activated on domain.com"
   - User must deactivate on other domain first
5. If doesn't exist or `status='inactive'`:
   - Validate with Envato API
   - Register in database
   - Activate license

#### Deactivation
1. User clicks "Deactivate License"
2. System updates database: `status='inactive'`
3. System removes WordPress options
4. License can now be activated on different domain

### Error Messages

**Already Activated**
```
This license is already activated on example.com. Please deactivate it there first or contact support.
Currently activated on: example.com
```

**Successful Deactivation**
```
License deactivated successfully. You can now activate it on a different domain.
```

---

## 4. Error Handling

### No Fatal Errors Policy

Every function in the license system is wrapped in try-catch blocks:

```php
public function some_function() {
    try {
        // Function logic
    } catch (Exception $e) {
        error_log('WNAP: Error message: ' . $e->getMessage());
        return false; // or appropriate fallback
    }
}
```

### Safe Initialization

The plugin initializes hooks only after WordPress is fully loaded:

```php
// Constructor - Safe, no WordPress functions
public function __construct() {
    try {
        add_action('admin_init', array($this, 'init_license_hooks'));
    } catch (Exception $e) {
        error_log('WNAP License Manager Constructor Error: ' . $e->getMessage());
    }
}

// Init hooks - Called after WordPress is ready
public function init_license_hooks() {
    try {
        // Safe to use WordPress functions here
    } catch (Exception $e) {
        error_log('WNAP: License hooks initialization error: ' . $e->getMessage());
    }
}
```

### Graceful Degradation

If the license system encounters errors:
- Site continues to function
- Admin notices are shown (not fatal errors)
- Errors are logged to PHP error log
- User receives clear instructions

---

## 5. User Interface

### License Activation Page

**Location**: WordPress Admin → WP News Audio Pro → License

**Elements**:
- Purchase code input field
- "Activate License" button with loading state
- Error/success message area
- Buy License button (appears on invalid codes)

### JavaScript AJAX Handler

**File**: `assets/js/admin-script.js`

**Features**:
- Real-time validation
- Loading indicator
- Error message display
- Success message with auto-reload
- XSS prevention (all messages sanitized)

### CSS Styling

**File**: `assets/css/admin-style.css`

**Features**:
- Error messages: Red background with warning icon
- Success messages: Green background with checkmark
- Buy button: Prominent call-to-action
- Responsive design

---

## 6. Security Features

### Encryption
License data is encrypted using WordPress `AUTH_KEY`:
- XOR encryption algorithm
- Base64 encoding
- Stored in WordPress options table

### Domain Fingerprinting
Unique signature generated from:
- Domain name
- Server IP
- Site URL
- ABSPATH
- AUTH_KEY

### HMAC Signature
License data is signed using:
- AUTH_KEY
- SECURE_AUTH_KEY
- SHA-256 HMAC algorithm

### Validation
Every license check verifies:
1. License status is 'active'
2. Domain matches current domain
3. Fingerprint matches current installation
4. HMAC signature is valid

---

## 7. API Reference

### Main Methods

#### `verify_with_envato($code)`
Validates purchase code with Envato API.

**Parameters**:
- `$code` (string) - Purchase code to verify

**Returns**: Array with:
- `success` (bool) - Whether validation succeeded
- `message` (string) - User-friendly message
- `action` (string) - Optional action (e.g., 'buy')
- `buy_url` (string) - URL to purchase page (if action='buy')
- `domain` (string) - Domain where code is registered (if already active)

#### `is_license_valid()`
Checks if current license is valid.

**Returns**: `bool` - True if valid, false otherwise

#### `deactivate_license()`
Deactivates current license.

**Returns**: `bool` - True on success, false on failure

#### `get_license_data()`
Retrieves decrypted license data.

**Returns**: `array|false` - License data or false if not found

---

## 8. Testing Checklist

### Test Code
- [ ] Enter "WNAP-DEV-TEST-2025" on localhost → Should activate
- [ ] Enter "WNAP-DEV-TEST-2025" on live domain → Should fail with message
- [ ] Test code expires after 90 days → Should deactivate

### Real Purchase Code
- [ ] Enter valid code → Should activate
- [ ] Enter invalid code → Should show 404 error + Buy button
- [ ] API authentication fails → Should show support message
- [ ] Connection error → Should show retry message

### One Domain Per License
- [ ] Activate code on Domain A → Should succeed
- [ ] Try to activate same code on Domain B → Should fail with "already activated" message
- [ ] Deactivate on Domain A → Should succeed
- [ ] Activate on Domain B → Should now succeed

### Error Handling
- [ ] Corrupt license data → Should not cause fatal error
- [ ] Database connection issue → Should not cause fatal error
- [ ] API timeout → Should show appropriate error message
- [ ] Invalid nonce → Should show security error

### User Experience
- [ ] Clear error messages for all scenarios
- [ ] Buy button appears when appropriate
- [ ] Loading indicators work correctly
- [ ] Page reloads after successful activation
- [ ] Deactivation confirms and reloads

---

## 9. Support Information

**Email**: info.geniusplugtechnology@gmail.com  
**WhatsApp**: +880 1761 487193  
**Website**: https://geniusplug.com/support/

---

## 10. Constants

### Plugin Constants

```php
// Main plugin file: wp-news-audio-pro.php
define('WNAP_ENVATO_ITEM_ID', ''); // Set after CodeCanyon approval
define('WNAP_ENVATO_API_TOKEN', 'IRXxacDkuYPM8lFe9NCNZ3rh3RMQTp49');
define('WNAP_SUPPORT_EMAIL', 'info.geniusplugtechnology@gmail.com');
define('WNAP_SUPPORT_WHATSAPP', '+880 1761 487193');
define('WNAP_SUPPORT_URL', 'https://geniusplug.com/support/');
```

### Override in wp-config.php

```php
// Optional: Override API token
define('WNAP_API_TOKEN', 'your-custom-token');

// Optional: Override item ID
define('WNAP_ITEM_ID', '12345678');

// Optional: Override support email
define('WNAP_EMAIL', 'custom@example.com');
```

---

## Changelog

### Version 1.0.0 - Complete Rewrite
- ✅ Test code system with localhost-only enforcement
- ✅ Envato API integration with correct endpoint
- ✅ Database-backed one domain per license system
- ✅ Comprehensive error handling (no fatal errors)
- ✅ User-friendly error messages
- ✅ Automatic deactivation and re-activation support
