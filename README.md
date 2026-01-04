# Sunrise & Sunset Calendar Subscription Generator

A PHP-based tool that generates dynamic iCalendar feeds with sunrise and sunset times for any location worldwide. Perfect for photographers, outdoor enthusiasts, or anyone who wants automated sunrise/sunset notifications in their calendar app.

**New in v4.1:** Twilight period events, externalized configuration, Rome defaults

## Features

- 🌅 **Twilight Period Events**: Calendar blocks from first light → sunrise and sunset → last light
- 🔒 **Secure Access**: Password-protected with externalized token configuration
- 🌍 **Any Location**: Works worldwide with latitude/longitude coordinates
- ⏰ **Custom Offsets**: Set reminders before/after actual sunrise/sunset times
- 📱 **Universal Compatibility**: Works with Google Calendar, Apple Calendar, Outlook, and any calendar app supporting iCal subscriptions
- 🕐 **12/24 Hour Format**: Choose your preferred time display
- 🔄 **Auto-Updates**: Calendar refreshes daily with new events
- 🔐 **Git-Safe**: Configuration stored separately for secure version control

## Requirements

- PHP 7.4 or higher
- Web server (Nginx, Apache, etc.) with PHP support
- HTTPS recommended (required for some calendar apps)

## Installation

### 1. Clone or Download

```bash
git clone https://github.com/yourusername/sunrise-sunset-calendar.git
cd sunrise-sunset-calendar
```

### 2. Create Configuration File

```bash
# Copy the example config
cp config.example.php config.php

# Generate a secure random token
openssl rand -hex 32

# Edit config.php and replace CHANGE_ME_TO_A_RANDOM_STRING with your token
nano config.php
```

Your `config.php` should look like this:

```php
<?php
define('AUTH_TOKEN', 'a8f5f167f44f4964e6c998dee827110c8f9c9eb76f9c8b5a3e6d4c2a1b0f9e8d');
define('CALENDAR_WINDOW_DAYS', 365);
define('UPDATE_INTERVAL', 86400);
```

**Important:** Never commit `config.php` to git! It's already in `.gitignore`.

### 3. Deploy to Server

#### For Nginx + PHP-FPM:

```bash
# Upload files
scp -r * user@yourserver:/var/www/html/sunrise-calendar/

# Set correct permissions
sudo chown -R www-data:www-data /var/www/html/sunrise-calendar/
sudo chmod 644 /var/www/html/sunrise-calendar/*.php
sudo chmod 600 /var/www/html/sunrise-calendar/config.php  # Extra protection for config

# Test PHP syntax
php -l /var/www/html/sunrise-calendar/sunrise-sunset-calendar.php

# Restart PHP-FPM if needed
sudo systemctl restart php-fpm
```

#### For Apache:

```bash
# Upload files
scp -r * user@yourserver:/var/www/html/sunrise-calendar/

# Set permissions
sudo chown -R www-data:www-data /var/www/html/sunrise-calendar/
sudo chmod 644 /var/www/html/sunrise-calendar/*.php
sudo chmod 600 /var/www/html/sunrise-calendar/config.php
```

### 4. Configure Web Server

#### Nginx Configuration:

Ensure your Nginx config includes PHP processing:

```nginx
location ~ \.php$ {
    fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;  # Adjust PHP version
    fastcgi_index index.php;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    include fastcgi_params;
}
```

Reload Nginx:
```bash
sudo nginx -t
sudo systemctl reload nginx
```

## Usage

### Step 1: Access the Web Interface

Navigate to your script in a web browser:
```
https://yourdomain.com/sunrise-sunset-calendar.php
```

### Step 2: Configure Your Calendar

1. **Enter Password**: Use your AUTH_TOKEN as the password
2. **Set Location**:
   - Click "Use My Current Location" for automatic detection
   - Or manually enter latitude/longitude coordinates
   - Select your timezone from the dropdown
3. **Configure Options**:
   - Set sunrise/sunset offsets (e.g., -15 for 15 minutes before)
   - Choose 12-hour or 24-hour time format
   - Select which events to include (sunrise, sunset, or both)
   - Add optional description text
4. **Click "Generate Subscription URL"**

### Step 3: Add to Your Calendar App

The page will display two URLs:
- **HTTP/HTTPS URL**: Standard subscription URL
- **Webcal URL**: Preferred for most calendar apps

#### Google Calendar:

1. Copy the subscription URL (either format works)
2. Open [Google Calendar](https://calendar.google.com)
3. Click the **+** button next to "Other calendars" (left sidebar)
4. Select **"From URL"**
5. Paste your subscription URL
6. Click **"Add calendar"**

The calendar will appear in your sidebar and automatically update daily.

#### Apple Calendar (macOS/iOS):

1. Copy the **webcal://** URL
2. **macOS**: File → New Calendar Subscription → Paste URL
3. **iOS**: Settings → Calendar → Accounts → Add Account → Other → Add Subscribed Calendar → Paste URL

#### Outlook:

1. Copy the subscription URL
2. Go to Calendar view
3. Select "Add calendar" → "Subscribe from web"
4. Paste URL and configure name/color
5. Click "Import"

## Configuration Options

### Available Parameters

| Parameter | Description | Default |
|-----------|-------------|---------|
| `lat` | Latitude (decimal degrees) | 45.58753 (Portland, OR) |
| `lon` | Longitude (decimal degrees) | -122.58886 (Portland, OR) |
| `zone` | Timezone (e.g., America/Los_Angeles) | America/Los_Angeles |
| `rise_off` | Sunrise offset in minutes (+/- 1440) | 0 |
| `set_off` | Sunset offset in minutes (+/- 1440) | 0 |
| `twelve` | Use 12-hour format (1=yes, 0=no) | 0 |
| `sunrise` | Include sunrise events (1=yes) | Required |
| `sunset` | Include sunset events (1=yes) | Required |
| `desc` | Custom event description | Empty |

### Advanced Configuration

Edit the constants at the top of the PHP file:

```php
define('AUTH_TOKEN', 'your_secret_token');      // Authentication token
define('CALENDAR_WINDOW_DAYS', 365);            // Days to generate ahead
define('UPDATE_INTERVAL', 86400);               // Refresh interval (seconds)
```

- **CALENDAR_WINDOW_DAYS**: How many days of events to generate (default: 365)
- **UPDATE_INTERVAL**: How often calendar apps should check for updates (default: 24 hours)

## Security Considerations

### Important Security Notes:

1. **Keep Your Token Secret**: Never share your AUTH_TOKEN or generated subscription URLs publicly
2. **Use HTTPS**: Some calendar apps require HTTPS for subscriptions
3. **Unique Tokens**: Use a different token for each installation
4. **Access Control**: Consider adding IP restrictions in your web server config
5. **No Data Storage**: The script doesn't store any data - everything is calculated on-demand

### Optional: Restrict Access by IP

Add to your Nginx config:

```nginx
location /sunrise-sunset-calendar.php {
    # Allow only your IP address
    allow 1.2.3.4;          # Your home IP
    allow 5.6.7.8;          # Your work IP
    deny all;

    # ... rest of PHP configuration
}
```

### Optional: Add HTTP Basic Auth

Create `.htpasswd` file:
```bash
sudo htpasswd -c /etc/nginx/.htpasswd yourusername
```

Add to Nginx config:
```nginx
location /sunrise-sunset-calendar.php {
    auth_basic "Restricted Access";
    auth_basic_user_file /etc/nginx/.htpasswd;

    # ... rest of PHP configuration
}
```

## Troubleshooting

### Calendar Not Updating

**Problem**: Events don't appear or stop updating

**Solutions**:
1. Check that the subscription URL is still accessible (visit it in a browser)
2. Remove and re-add the calendar subscription
3. Some calendar apps cache aggressively - wait 24 hours or force refresh
4. Verify your web server logs for errors: `tail -f /var/log/nginx/error.log`

### "Invalid Authentication Token" Error

**Problem**: Getting 403 error when accessing feed

**Solutions**:
1. Verify your AUTH_TOKEN in the PHP file matches the URL
2. Check for extra spaces or characters in the token
3. Regenerate the subscription URL with the correct password

### Times Are Wrong

**Problem**: Sunrise/sunset times don't match reality

**Solutions**:
1. Verify your latitude/longitude are correct
2. Check that the correct timezone is selected
3. Ensure your server's PHP timezone database is up to date: `sudo apt update && sudo apt upgrade`
4. Test with a known location (e.g., Portland coordinates provided as default)

### Events Not Appearing in Calendar

**Problem**: Subscription added but no events visible

**Solutions**:
1. Wait 5-10 minutes for initial sync
2. Check that at least one event type (sunrise or sunset) is selected
3. Verify the calendar is visible/enabled in your calendar app
4. Check the date range - events only appear from today forward

### PHP Errors

**Problem**: White screen or PHP errors

**Solutions**:
```bash
# Check PHP error log
sudo tail -f /var/log/php-fpm/error.log  # or /var/log/php/error.log

# Test PHP syntax
php -l sunrise-sunset-calendar.php

# Check PHP version (requires 7.4+)
php -v

# Verify PHP-FPM is running
sudo systemctl status php-fpm
```

## Finding Your Coordinates

### Using the Web Interface
- Click "Use My Current Location" button (requires browser location permission)

### Using Online Tools
- [Google Maps](https://maps.google.com): Right-click any location → coordinates appear
- [LatLong.net](https://www.latlong.net): Search for any address

### Format Requirements
- **Latitude**: -90 to 90 (negative = South)
- **Longitude**: -180 to 180 (negative = West)
- **Decimal degrees**: e.g., 45.587539, -122.588861 (not degrees/minutes/seconds)

## Example Use Cases

### Photographer's Golden Hour Reminder
- Sunrise offset: `-30` (30 minutes before sunrise)
- Sunset offset: `-30` (30 minutes before sunset)
- Both sunrise and sunset enabled

### Morning Exercise Routine
- Only sunrise enabled
- Offset: `0` (at actual sunrise time)

### Evening Dog Walk
- Only sunset enabled
- Offset: `-15` (15 minutes before sunset)

### Complete Day Planning
- Both events enabled
- Offsets: `0` for both
- 12-hour format enabled

## API/Feed URL Structure

The generated subscription URL follows this format:

```
https://yourdomain.com/sunrise-sunset-calendar.php?
  feed=1
  &token=YOUR_SECRET_TOKEN
  &lat=45.587539
  &lon=-122.588861
  &zone=America/Los_Angeles
  &rise_off=0
  &set_off=0
  &sunrise=1
  &sunset=1
  &twelve=0
  &desc=Custom%20description
```

You can manually construct URLs if needed, but it's easier to use the web interface.

## Technical Details

### How It Works

1. Calendar app requests the subscription URL
2. Script validates the authentication token
3. Calculates sunrise/sunset for next 365 days using PHP's `date_sun_info()`
4. Generates iCalendar format (RFC 5545) on-the-fly
5. Returns calendar feed with refresh headers
6. Calendar app automatically re-fetches daily

### iCalendar Format

The script generates standards-compliant iCalendar feeds:
- Version: 2.0
- Format: RFC 5545
- Encoding: UTF-8
- Timezone: Configurable per calendar
- UID: Unique per event (location + date + type)

### Performance

- Near-instant generation (< 100ms for 365 days)
- No database required
- Minimal server resources
- Stateless - no data persistence needed

## Contributing

This is a standalone script. To customize:

1. Edit the PHP file directly
2. Modify CSS styles in the `<style>` section
3. Adjust calculation parameters in the configuration section
4. Test thoroughly before deploying changes

## License

Free to use and modify. Original concept by pdxvr, optimized and enhanced 2026.

## Support

For issues:
1. Check the Troubleshooting section above
2. Verify your PHP error logs
3. Test with default coordinates first
4. Ensure your AUTH_TOKEN is properly configured
