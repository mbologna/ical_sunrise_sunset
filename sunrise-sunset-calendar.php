<?php
/**
 * Sunrise/Sunset iCal Calendar Generator
 * Generates iCalendar files with sunrise and sunset times for any location
 *
 * @author Original: pdxvr | Optimized: 2026
 * @version 2.0
 */

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Input validation and sanitization functions
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

function sanitize_date($value, $default) {
    $timestamp = strtotime($value);
    return $timestamp !== false ? $timestamp : $default;
}

function sanitize_text($value, $max_length = 500) {
    $clean = strip_tags($value);
    $clean = str_replace(["\r\n", "\r", "\n"], " ", $clean);
    return substr($clean, 0, $max_length);
}

// Process form submission
if (isset($_POST['submit']) && $_SERVER['REQUEST_METHOD'] === 'POST') {

    // Validate CSRF token would go here in production

    // Sanitize all inputs
    $timezone = sanitize_timezone($_POST['zone'] ?? '');
    putenv("TZ={$timezone}");
    date_default_timezone_set($timezone);

    $start = isset($_POST['start']) && $_POST['start'] !== ''
        ? sanitize_date($_POST['start'], strtotime('today'))
        : strtotime('today');

    $end = isset($_POST['end']) && $_POST['end'] !== ''
        ? sanitize_date($_POST['end'], $start + 31449600)
        : $start + 31449600;

    // Limit to 365 days maximum
    if ($end - $start > 31536000) {
        $end = $start + 31536000;
    }

    $rise_offset = sanitize_int($_POST['rise_off'] ?? 0, 0) * 60;
    $set_offset = sanitize_int($_POST['set_off'] ?? 0, 0) * 60;
    $lat = sanitize_float($_POST['lat'] ?? '', 45.58753958079636, -90, 90);
    $lon = sanitize_float($_POST['lon'] ?? '', -122.58886098861694, -180, 180);
    $twelve_hour = isset($_POST['twelve']);
    $description = sanitize_text($_POST['description'] ?? '');
    $include_sunrise = isset($_POST['sunrise']);
    $include_sunset = isset($_POST['sunset']);

    // Validate at least one event type selected
    if (!$include_sunrise && !$include_sunset) {
        die('Error: Please select at least one event type (sunrise or sunset)');
    }

    // Generate iCal file
    header('Content-Type: text/calendar; charset=utf-8');
    header('Content-Disposition: attachment; filename="sunrise-sunset-calendar.ics"');

    echo "BEGIN:VCALENDAR\r\n";
    echo "VERSION:2.0\r\n";
    echo "PRODID:-//Sunrise Sunset Calendar Generator//EN\r\n";
    echo "CALSCALE:GREGORIAN\r\n";
    echo "METHOD:PUBLISH\r\n";
    echo "X-WR-CALNAME:Sunrise/Sunset Calendar for {$lat}, {$lon}\r\n";
    echo "X-WR-TIMEZONE:{$timezone}\r\n";

    $current_day = $start;
    $days_processed = 0;

    while ($current_day <= $end && $days_processed < 365) {
        $sun_info = date_sun_info($current_day, $lat, $lon);

        if ($include_sunrise && isset($sun_info['sunrise'])) {
            $sunrise_time = $sun_info['sunrise'] + $rise_offset;
            $date_str = gmdate('Ymd', $sunrise_time);
            $time_str = gmdate('His', $sunrise_time);
            $display_time = date($twelve_hour ? 'g:i A' : 'H:i', $sun_info['sunrise']);

            echo "BEGIN:VEVENT\r\n";
            echo "UID:{$date_str}T{$time_str}-sunrise-{$lat}-{$lon}@sunrise-calendar\r\n";
            echo "DTSTAMP:" . gmdate('Ymd\THis\Z') . "\r\n";
            echo "DTSTART:{$date_str}T{$time_str}Z\r\n";
            echo "DTEND:{$date_str}T{$time_str}Z\r\n";
            echo "SUMMARY:Sunrise: {$display_time}\r\n";
            if ($description) {
                echo "DESCRIPTION:" . str_replace(["\r", "\n"], [" ", " "], $description) . "\r\n";
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
            echo "UID:{$date_str}T{$time_str}-sunset-{$lat}-{$lon}@sunrise-calendar\r\n";
            echo "DTSTAMP:" . gmdate('Ymd\THis\Z') . "\r\n";
            echo "DTSTART:{$date_str}T{$time_str}Z\r\n";
            echo "DTEND:{$date_str}T{$time_str}Z\r\n";
            echo "SUMMARY:Sunset: {$display_time}\r\n";
            if ($description) {
                echo "DESCRIPTION:" . str_replace(["\r", "\n"], [" ", " "], $description) . "\r\n";
            }
            echo "TRANSP:TRANSPARENT\r\n";
            echo "END:VEVENT\r\n";
        }

        $current_day = strtotime('+1 day', $current_day);
        $days_processed++;
    }

    echo "END:VCALENDAR\r\n";
    exit;
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
    <meta name="description" content="Generate custom iCalendar files with sunrise and sunset times for any location worldwide">
    <title>Sunrise & Sunset iCal Calendar Generator</title>
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

        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            font-size: 0.9em;
            color: #666;
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
        <h1>🌅 Sunrise & Sunset Calendar Generator</h1>

        <div class="info-box">
            <strong>Today's Information (Portland, Oregon)</strong><br>
            Date: <?php echo date('F j, Y'); ?><br>
            Sunrise: <?php echo date('g:i A', $sun_info['sunrise']); ?><br>
            Sunset: <?php echo date('g:i A', $sun_info['sunset']); ?>
        </div>

        <div class="warning-box">
            <strong>⚠️ Important:</strong> Before importing the generated .ics file into your calendar app, please review it first. Removing hundreds of individual events is tedious if something goes wrong. This tool generates downloadable files only (not subscribable calendars).
        </div>

        <button onclick="getLocation()" class="secondary">📍 Use My Current Location</button>
        <div id="location-status"></div>

        <form method="post" onsubmit="return validateForm()">
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
                <label for="start">Start Date</label>
                <input type="text" name="start" id="start" placeholder="MM/DD/YYYY or 'today'">
                <div class="help-text">Leave blank to start today</div>
            </div>

            <div class="form-group">
                <label for="end">End Date</label>
                <input type="text" name="end" id="end" placeholder="MM/DD/YYYY or '+1 year'">
                <div class="help-text">Leave blank for one year from start (365 days maximum)</div>
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

            <input type="submit" name="submit" value="Generate Calendar File">
        </form>

        <div class="footer">
            <strong>Notes:</strong>
            <ul>
                <li>Daylight Saving Time is automatically handled based on your selected timezone</li>
                <li>Your location data is only used to calculate sunrise/sunset times and is not stored</li>
                <li>Generated files work with Apple Calendar, Google Calendar, Outlook, and most calendar apps</li>
                <li>Maximum 365 days of events per file</li>
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

            return true;
        }
    </script>
</body>
</html>
