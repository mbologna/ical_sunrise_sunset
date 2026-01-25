# Enhanced Sun & Twilight Calendar Generator

A PHP-based tool that generates dynamic iCalendar feeds with comprehensive sun position data for any location worldwide. Perfect for photographers, astronomers, outdoor enthusiasts, or anyone who wants automated sunrise/sunset/twilight notifications in their calendar app.

**Current Version: 5.1** - Dawn/Dusk naming, enhanced statistics, smart single-event mode

## Features

- 🌅 **Multiple Event Types**: Civil, Nautical, and Astronomical twilight periods plus full day/night cycles
- 📊 **Detailed Statistics**: Daylight duration, percentages, and yearly percentiles
- 🧠 **Smart Single-Event Mode**: Select just one event type to get a clean calendar with all sun data in event notes
- 🌍 **Any Location**: Works worldwide with latitude/longitude coordinates
- ⏰ **Custom Offsets**: Set reminders before/after actual sun events
- 🕐 **12/24 Hour Format**: Choose your preferred time display
- 🔄 **Auto-Updates**: Calendar refreshes daily with new events
- 🔒 **Secure**: Password-protected with externalized configuration
- 📱 **Universal**: Works with Google Calendar, Apple Calendar, Outlook, and any iCal-compatible app

## What You Get

### Event Types (Select Any Combination):

1. **🌌 Astronomical Dawn/Dusk** - When stars appear/disappear (Sun 12-18° below horizon)
2. **⚓ Nautical Dawn/Dusk** - When horizon becomes visible/invisible at sea (Sun 6-12° below horizon)
3. **🌅 Civil Dawn/Dusk** - First light to sunrise, sunset to last light (Sun 0-6° below horizon)
4. **☀️ Day & Night** - Complete daylight period + full night with statistics

### Each Event Includes:

- **Contextual descriptions** explaining what happens during that specific period
- **Solar events** (solar noon for day, solar midnight for night)
- **Statistics** (duration, percentage of day, yearly percentile ranking)
- **Complete sun schedule** (when selecting only one event type)

## Quick Start

### 1. Installation

```bash
# Clone repository
git clone https://github.com/yourusername/sun-twilight-calendar.git
cd sun-twilight-calendar

# Create config from example
cp config.example.php config.php

# Generate secure token (Linux/Mac)
openssl rand -hex 32

# Edit config.php and set your AUTH_TOKEN
nano config.php
```

Your `config.php`:
```php
<?php
define('AUTH_TOKEN', 'your_secure_random_token_here');
define('CALENDAR_WINDOW_DAYS', 365);  // Days to generate
define('UPDATE_INTERVAL', 86400);      // Refresh every 24 hours
```

### 2. Deploy

Upload to your web server with PHP support (7.4+). Ensure `config.php` is not web-accessible or in `.gitignore`.

```bash
# Set permissions
chmod 644 *.php
chmod 600 config.php
```

### 3. Generate Calendar

1. Navigate to `https://yourdomain.com/sunrise-sunset-calendar.php`
2. Enter your password (same as AUTH_TOKEN)
3. Set your location (or click "Use My Current Location")
4. Select event types - **Pro tip:** Select only ONE for a clean calendar with complete info
5. Click "Generate Subscription URL"

### 4. Subscribe in Your Calendar App

**Google Calendar:**
1. Copy the subscription URL
2. Google Calendar → "+" next to Other calendars → From URL
3. Paste URL → Add calendar

**Apple Calendar:**
1. Copy the webcal:// URL
2. File → New Calendar Subscription → Paste URL

**Outlook:**
1. Copy URL
2. Add calendar → Subscribe from web → Paste URL

## Configuration Options

| Parameter | Description | Default |
|-----------|-------------|---------|
| `lat` | Latitude (-90 to 90) | 41.9028 (Rome) |
| `lon` | Longitude (-180 to 180) | 12.4964 (Rome) |
| `elev` | Elevation in meters | 21 |
| `zone` | Timezone | Europe/Rome |
| `rise_off` | Morning event offset (minutes) | 0 |
| `set_off` | Evening event offset (minutes) | 0 |
| `twelve` | Use 12-hour format | 0 (24-hour) |
| `civil` | Include civil twilight | 0 |
| `nautical` | Include nautical twilight | 0 |
| `astro` | Include astronomical twilight | 0 |
| `sun` | Include day/night events | 0 |
| `desc` | Custom description | Empty |

## Smart Single-Event Mode

**The Secret Sauce:** When you select only ONE event type, all other sun times and statistics are automatically included in each event's description!

**Example:** Select only "Civil Dawn/Dusk" → You get:
- Clean calendar with just 2 events per day (dawn and dusk)
- Each event contains: astronomical dawn, nautical dawn, sunrise, solar noon, sunset, nautical dusk, astronomical dusk
- Plus complete daylight/night statistics
- All with emojis and descriptions for easy reading

Perfect for minimalist calendars with maximum information!

## Understanding the Events

### Dawn → Dusk Progression:
```
🌌 Astronomical Dawn  → Stars fade, first light appears
⚓ Nautical Dawn       → Horizon becomes visible
🌅 Civil Dawn          → Enough light for activities (First Light)
☀️ Sunrise            → Sun breaks horizon
☀️ Solar Noon         → Sun at highest point
☀️ Sunset             → Sun dips below horizon
🌇 Civil Dusk          → Artificial light needed (Last Light)
⚓ Nautical Dusk       → Horizon fades from view
🌌 Astronomical Dusk   → Complete darkness
🌙 Night              → Optimal stargazing
```

## Security

- **Keep AUTH_TOKEN secret** - Never commit `config.php` to version control
- **Use HTTPS** - Required by most calendar apps
- **Unique tokens** - Generate a different token for each installation
- **No data storage** - Everything calculated on-demand, nothing logged

## Troubleshooting

**Calendar not updating?**
- Wait 24 hours for refresh or remove/re-add subscription
- Check URL is still accessible in browser

**Wrong times?**
- Verify coordinates and timezone are correct
- Times calculated using PHP's astronomical algorithms (may differ slightly from other sources)

**Events not appearing?**
- Ensure at least one event type is selected
- Check calendar is visible/enabled in your app
- Wait 5-10 minutes for initial sync

**PHP errors?**
```bash
php -l sunrise-sunset-calendar.php  # Check syntax
tail -f /var/log/nginx/error.log    # Check server logs
```

## Finding Your Coordinates

- **Web interface**: Click "Use My Current Location"
- **Google Maps**: Right-click anywhere → coordinates appear
- **Format**: Decimal degrees (e.g., 41.9028, 12.4964)

## Technical Details

- **Language**: PHP 7.4+
- **Format**: iCalendar (RFC 5545)
- **Calculations**: PHP `date_sun_info()` function
- **Performance**: <100ms for 365 days
- **Storage**: Stateless, no database required

## Example Use Cases

- **Photographer**: Civil twilight only for golden/blue hour planning
- **Astronomer**: Astronomical twilight + night for optimal observation windows
- **Outdoor enthusiast**: All twilights for complete day planning
- **Minimalist**: Any single event type for clean calendar with full data in notes

## License

Free to use and modify. Originally by [pdxvr](https://github.com/pdxvr/ical_sunrise_sunset), enhanced 2025-2026.

## Support

Check PHP error logs and verify configuration. The script is self-contained and requires minimal setup when properly configured.
