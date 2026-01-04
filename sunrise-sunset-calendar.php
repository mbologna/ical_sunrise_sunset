<?php
/**
 * Sunrise/Sunset iCal Calendar Generator with Subscription Support
 * Generates dynamic iCalendar feeds that update automatically
 *
 * @version 3.0 - Subscription support with authentication
 */

// Configuration
define('AUTH_TOKEN', 'your_secret_token_here_change_this'); // CHANGE THIS TO A RANDOM STRING
define('CALENDAR_WINDOW_DAYS', 365); // How many days ahead to generate
define('UPDATE_INTERVAL', 86400); // How often calendars should refresh (24 hours)

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Input validation functions
function sanitize_float($value, $default, $min = -90, $max = 90) {
    $filtered = filter_var($value, FILTER_VALIDATE_FLOAT);
    if ($filtered === false || $filtered < $min || $filtered > $max) {
        return $default;
    }
    return $filtered;
}

function sanitize_int($value, $default, $min = -1440, $max = 1440) {
    $filtered = filter_var($value, FILTER_VALIDATE_INT);
    if ($filtered === false || $filtered < $min || $filtered > $max) {
        return $default;
    }
    return $filtered;
}

function sanitize_timezone($value) {
    $zones = timezone_identifiers_list();
    return in_array($value, $zones, true) ? $value : 'America/Los_Angeles';
}

function sanitize_text($value, $max_length = 500) {
    $clean = strip_tags($value);
    $clean = str_replace(["\r\n", "\r", "\n"], " ", $clean);
    return substr($clean, 0, $max_length);
}

function verify_token($provided_token) {
    return hash_equals(AUTH_TOKEN, $provided_token);
}

// Handle calendar feed requests (subscription URL)
if (isset($_GET['feed']) && isset($_GET['token'])) {

    // Verify authentication token
    if (!verify_token($_GET['token'])) {
        http_response_code(403);
        die('Invalid authentication token');
    }

    // Get parameters from URL
    $lat = sanitize_float($_GET['lat'] ?? '', 45.58753958079636, -90, 90);
    $lon = sanitize_float($_GET['lon'] ?? '', -122.58886098861694, -180, 180);
    $timezone = sanitize_timezone($_GET['zone'] ?? 'America/Los_Angeles');
    $rise_offset = sanitize_int($_GET['rise_off'] ?? 0, 0) * 60;
    $set_offset = sanitize_int($_GET['set_off'] ?? 0, 0) * 60;
    $include_sunrise = isset($_GET['sunrise']) && $_GET['sunrise'] === '1';
    $include_sunset = isset($_GET['sunset']) && $_GET['sunset'] === '1';
    $twelve_hour = isset($_GET['twelve']) && $_GET['twelve'] === '1';
    $description = sanitize_text($_GET['desc'] ?? '');

    // Set timezone
    putenv("TZ={$timezone}");
    date_default_timezone_set($timezone);

    // Generate calendar feed
    header('Content-Type: text/calendar; charset=utf-8');
    header('Cache-Control: max-age=' . UPDATE_INTERVAL);

    echo "BEGIN:VCALENDAR\r\n";
    echo "VERSION:2.0\r\n";
    echo "PRODID:-//Sunrise Sunset Calendar//EN\r\n";
    echo "CALSCALE:GREGORIAN\r\n";
    echo "METHOD:PUBLISH\r\n";
    echo "X-WR-CALNAME:Sunrise/Sunset - {$lat}, {$lon}\r\n";
    echo "X-WR-TIMEZONE:{$timezone}\r\n";
    echo "X-PUBLISHED-TTL:PT" . (UPDATE_INTERVAL / 3600) . "H\r\n";
    echo "REFRESH-INTERVAL;VALUE=DURATION:PT" . (UPDATE_INTERVAL / 3600) . "H\r\n";

    $start = strtotime('today');
    $end = strtotime('+' . CALENDAR_WINDOW_DAYS . ' days');
    $current_day = $start;

    while ($current_day <= $end) {
        $sun_info = date_sun_info($current_day, $lat, $lon);

        if ($include_sunrise && isset($sun_info['sunrise'])) {
            $sunrise_time = $sun_info['sunrise'] + $rise_offset;
            $date_str = gmdate('Ymd', $sunrise_time);
            $time_str = gmdate('His', $sunrise_time);
            $display_time = date($twelve_hour ? 'g:i A' : 'H:i', $sun_info['sunrise']);

            echo "BEGIN:VEVENT\r\n";
            echo "UID:sunrise-{$date_str}-{$lat}-{$lon}@sunrise-calendar\r\n";
            echo "DTSTAMP:" . gmdate('Ymd\THis\Z') . "\r\n";
            echo "DTSTART:{$date_str}T{$time_str}Z\r\n";
            echo "DTEND:{$date_str}T{$time_str}Z\r\n";
            echo "SUMMARY:Sunrise: {$display_time}\r\n";
            if ($description) {
                echo "DESCRIPTION:" . $description . "\r\n";
            }
            echo "TRANSP:TRANSPARENT\r\n";
            echo "END:VEVENT\r\n";
        }

        if ($include_sunset && isset($sun_info['sunset'])) {
            $sunset_time = $sun_info['sunset'] + $set_offset;
            $date_str = gmdate('Ymd', $sunset_time);
            $time_str = gmdate('His', $sunset_time);
            $display_time = date($twelve_hour ? 'g:i A' : 'H:i', $sun_info['sunset']);

            echo "BEGIN:VEVENT\r\n";
            echo "UID:sunset-{$date_str}-{$lat}-{$lon}@sunrise-calendar\r\n";
            echo "DTSTAMP:" . gmdate('Ymd\THis\Z') . "\r\n";
            echo "DTSTART:{$date_str}T{$time_str}Z\r\n";
            echo "DTEND:{$date_str}T{$time_str}Z\r\n";
            echo "SUMMARY:Sunset: {$display_time}\r\n";
            if ($description) {
                echo "DESCRIPTION:" . $description . "\r\n";
            }
            echo "TRANSP:TRANSPARENT\r\n";
            echo "END:VEVENT\r\n";
        }

        $current_day = strtotime('+1 day', $current_day);
    }

    echo "END:VCALENDAR\r\n";
    exit;
}

// Handle form submission to generate subscription URL
if (isset($_POST['generate_url']) && $_SERVER['REQUEST_METHOD'] === 'POST') {

    // Verify password
    $password = $_POST['password'] ?? '';
    if (!verify_token($password)) {
        $error = 'Invalid password';
    } else {
        // Build subscription URL
        $params = [
            'feed' => '1',
            'token' => AUTH_TOKEN,
            'lat' => $_POST['lat'] ?? 45.58753958079636,
            'lon' => $_POST['lon'] ?? -122.58886098861694,
            'zone' => $_POST['zone'] ?? 'America/Los_Angeles',
            'rise_off' => $_POST['rise_off'] ?? 0,
            'set_off' => $_POST['set_off'] ?? 0,
            'twelve' => isset($_POST['twelve']) ? '1' : '0',
            'desc' => $_POST['description'] ?? '',
        ];

        if (isset($_POST['sunrise'])) {
            $params['sunrise'] = '1';
        }
        if (isset($_POST['sunset'])) {
            $params['sunset'] = '1';
        }

        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $script = $_SERVER['SCRIPT_NAME'];
        $subscription_url = $protocol . '://' . $host . $script . '?' . http_build_query($params);

        // For webcal protocol (better for calendar apps)
        $webcal_url = str_replace(['https://', 'http://'], 'webcal://', $subscription_url);
    }
}

// Display form
putenv("TZ=America/Los_Angeles");
date_default_timezone_set('America/Los_Angeles');

$default_lat = 45.58753958079636;
$default_lon = -122.58886098861694;
$sun_info = date_sun_info(time(), $default_lat, $default_lon);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Generate subscribable sunrise/sunset calendar feeds">
    <meta name="robots" content="noindex, nofollow">
    <title>Sunrise & Sunset Calendar Subscription Generator</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }

        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        h1 {
            color: #2c3e50;
            margin-top: 0;
            font-size: 1.8em;
        }

        .info-box {
            background: #e8f4f8;
            border-left: 4px solid #3498db;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }

        .success-box {
            background: #d4edda;
            border-left: 4px solid #28a745;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }

        .error-box {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
            color: #721c24;
        }

        .warning-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-weight: 600;
            margin-bottom: 5px;
            color: #2c3e50;
        }

        .required {
            color: #e74c3c;
        }

        input[type="text"],
        input[type="number"],
        input[type="password"],
        select,
        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            font-family: inherit;
        }

        input[type="text"]:focus,
        input[type="number"]:focus,
        input[type="password"]:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            padding: 10px 0;
        }

        input[type="checkbox"] {
            width: 20px;
            height: 20px;
            margin-right: 10px;
            cursor: pointer;
        }

        .checkbox-group label {
            margin: 0;
            cursor: pointer;
            font-weight: normal;
        }

        button {
            background: #3498db;
            color: white;
            border: none;
            padding: 12px 24px;
            font-size: 16px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.3s;
        }

        button:hover {
            background: #2980b9;
        }

        button.secondary {
            background: #95a5a6;
        }

        button.secondary:hover {
            background: #7f8c8d;
        }

        input[type="submit"] {
            background: #27ae60;
            color: white;
            border: none;
            padding: 15px 30px;
            font-size: 18px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            width: 100%;
            transition: background 0.3s;
        }

        input[type="submit"]:hover {
            background: #229954;
        }

        .help-text {
            font-size: 0.9em;
            color: #666;
            margin-top: 5px;
        }

        hr {
            border: none;
            border-top: 1px solid #eee;
            margin: 25px 0;
        }

        .url-display {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            border: 1px solid #dee2e6;
            word-break: break-all;
            font-family: monospace;
            font-size: 14px;
        }

        .copy-button {
            margin-top: 10px;
            background: #6c757d;
        }

        .copy-button:hover {
            background: #5a6268;
        }

        #location-status {
            padding: 10px;
            margin-top: 10px;
            border-radius: 4px;
            display: none;
        }

        #location-status.success {
            display: block;
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        #location-status.error {
            display: block;
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        @media (max-width: 768px) {
            body {
                padding: 10px;
            }

            .container {
                padding: 20px;
            }

            h1 {
                font-size: 1.5em;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🌅 Sunrise & Sunset Calendar Subscription</h1>

        <div class="info-box">
            <strong>Today's Information (Portland, Oregon)</strong><br>
            Date: <?php echo date('F j, Y'); ?><br>
            Sunrise: <?php echo date('g:i A', $sun_info['sunrise']); ?><br>
            Sunset: <?php echo date('g:i A', $sun_info['sunset']); ?>
        </div>

        <?php if (isset($error)): ?>
        <div class="error-box">
            <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <?php if (isset($subscription_url)): ?>
        <div class="success-box">
            <h3>✅ Subscription URL Generated!</h3>

            <p><strong>Your Calendar Subscription URL:</strong></p>
            <div class="url-display" id="subscription-url"><?php echo htmlspecialchars($subscription_url); ?></div>
            <button class="copy-button" onclick="copyToClipboard('subscription-url')">📋 Copy URL</button>

            <p style="margin-top: 20px;"><strong>Webcal URL (recommended for most apps):</strong></p>
            <div class="url-display" id="webcal-url"><?php echo htmlspecialchars($webcal_url); ?></div>
            <button class="copy-button" onclick="copyToClipboard('webcal-url')">📋 Copy Webcal URL</button>

            <hr>

            <h4>How to Add to Google Calendar:</h4>
            <ol>
                <li>Copy the subscription URL above (either one works)</li>
                <li>Open <a href="https://calendar.google.com" target="_blank">Google Calendar</a></li>
                <li>Click the <strong>+</strong> next to "Other calendars"</li>
                <li>Select <strong>"From URL"</strong></li>
                <li>Paste your subscription URL</li>
                <li>Click <strong>"Add calendar"</strong></li>
            </ol>

            <p><strong>Note:</strong> Your calendar will automatically update with new sunrise/sunset times daily. Keep this URL private!</p>
        </div>
        <?php endif; ?>

        <div class="warning-box">
            <strong>🔒 Authentication Required</strong><br>
            This page requires a password to generate calendar feeds. The generated URLs contain an authentication token that allows calendar apps to access your feed.
        </div>

        <button onclick="getLocation()" class="secondary">📍 Use My Current Location</button>
        <div id="location-status"></div>

        <form method="post" onsubmit="return validateForm()">
            <hr>

            <div class="form-group">
                <label for="password">Password <span class="required">*</span></label>
                <input type="password" name="password" id="password" required>
                <div class="help-text">Enter the password to generate your calendar feed</div>
            </div>

            <hr>

            <div class="form-group">
                <label for="lat">Latitude <span class="required">*</span></label>
                <input type="number" name="lat" id="lat" step="0.000001" min="-90" max="90"
                       value="<?php echo $default_lat; ?>" required>
                <div class="help-text">Decimal format (e.g., 45.5875)</div>
            </div>

            <div class="form-group">
                <label for="lon">Longitude <span class="required">*</span></label>
                <input type="number" name="lon" id="lon" step="0.000001" min="-180" max="180"
                       value="<?php echo $default_lon; ?>" required>
                <div class="help-text">Decimal format, use negative for West (e.g., -122.5889)</div>
            </div>

            <div class="form-group">
                <label for="zone">Timezone <span class="required">*</span></label>
                <select name="zone" id="zone" required>
                    <option value="America/Los_Angeles" selected>America/Los_Angeles (Pacific)</option>
                    <?php
                    $zones = timezone_identifiers_list();
                    foreach ($zones as $zone) {
                        if ($zone !== 'America/Los_Angeles') {
                            echo "<option value=\"" . htmlspecialchars($zone) . "\">" .
                                 htmlspecialchars($zone) . "</option>\n";
                        }
                    }
                    ?>
                </select>
            </div>

            <hr>

            <div class="form-group">
                <label for="rise_off">Sunrise Offset (minutes)</label>
                <input type="number" name="rise_off" id="rise_off" value="0" min="-1440" max="1440">
                <div class="help-text">Use +15 or -15 to trigger before/after actual sunrise</div>
            </div>

            <div class="form-group">
                <label for="set_off">Sunset Offset (minutes)</label>
                <input type="number" name="set_off" id="set_off" value="0" min="-1440" max="1440">
                <div class="help-text">Use +15 or -15 to trigger before/after actual sunset</div>
            </div>

            <hr>

            <div class="form-group">
                <strong>Event Types <span class="required">*</span></strong>
                <div class="checkbox-group">
                    <input type="checkbox" name="sunrise" id="sunrise" checked>
                    <label for="sunrise">Generate sunrise events</label>
                </div>
                <div class="checkbox-group">
                    <input type="checkbox" name="sunset" id="sunset" checked>
                    <label for="sunset">Generate sunset events</label>
                </div>
            </div>

            <div class="form-group">
                <div class="checkbox-group">
                    <input type="checkbox" name="twelve" id="twelve">
                    <label for="twelve">Use 12-hour time format (AM/PM)</label>
                </div>
            </div>

            <hr>

            <div class="form-group">
                <label for="description">Event Description (optional)</label>
                <textarea name="description" id="description" rows="3"
                          placeholder="Add a custom description to all events"></textarea>
            </div>

            <input type="submit" name="generate_url" value="Generate Subscription URL">
        </form>

        <div class="footer" style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; font-size: 0.9em; color: #666;">
            <strong>Important Notes:</strong>
            <ul>
                <li>The calendar automatically generates <?php echo CALENDAR_WINDOW_DAYS; ?> days of events from today</li>
                <li>Calendar apps will refresh the feed every <?php echo (UPDATE_INTERVAL / 3600); ?> hours</li>
                <li>Keep your subscription URL private - it contains your authentication token</li>
                <li>Daylight Saving Time is automatically handled</li>
                <li>Your location data is only used in the URL parameters, not stored on the server</li>
            </ul>

            <strong>Security:</strong>
            <ul>
                <li>Change the AUTH_TOKEN in the PHP file to a random string</li>
                <li>Never share your password or subscription URLs publicly</li>
                <li>The feed URL is public but requires your secret token to access</li>
            </ul>
        </div>
    </div>

    <script>
        function getLocation() {
            const status = document.getElementById('location-status');
            status.className = '';
            status.style.display = 'none';
            status.textContent = 'Getting your location...';

            if (!navigator.geolocation) {
                status.className = 'error';
                status.textContent = 'Geolocation is not supported by your browser.';
                return;
            }

            navigator.geolocation.getCurrentPosition(
                function(position) {
                    document.getElementById('lat').value =
                        Math.round(position.coords.latitude * 1000000) / 1000000;
                    document.getElementById('lon').value =
                        Math.round(position.coords.longitude * 1000000) / 1000000;

                    status.className = 'success';
                    status.textContent = '✓ Location retrieved successfully!';
                },
                function(error) {
                    status.className = 'error';
                    let message = 'Unable to retrieve your location. ';
                    switch(error.code) {
                        case error.PERMISSION_DENIED:
                            message += 'Permission denied. Please enable location services.';
                            break;
                        case error.POSITION_UNAVAILABLE:
                            message += 'Location information unavailable.';
                            break;
                        case error.TIMEOUT:
                            message += 'Location request timed out.';
                            break;
                        default:
                            message += 'Error: ' + error.message;
                    }
                    status.textContent = message;
                }
            );
        }

        function validateForm() {
            const sunrise = document.getElementById('sunrise').checked;
            const sunset = document.getElementById('sunset').checked;

            if (!sunrise && !sunset) {
                alert('Please select at least one event type (sunrise or sunset).');
                return false;
            }

            const password = document.getElementById('password').value;
            if (!password) {
                alert('Please enter the password.');
                return false;
            }

            return true;
        }

        function copyToClipboard(elementId) {
            const element = document.getElementById(elementId);
            const text = element.textContent;

            navigator.clipboard.writeText(text).then(function() {
                const button = event.target;
                const originalText = button.textContent;
                button.textContent = '✓ Copied!';
                button.style.background = '#28a745';

                setTimeout(function() {
                    button.textContent = originalText;
                    button.style.background = '';
                }, 2000);
            }, function(err) {
                alert('Failed to copy: ' + err);
            });
        }
    </script>
</body>
</html>
