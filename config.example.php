<?php
/**
 * Configuration file for Sunrise/Sunset Calendar Generator
 *
 * SETUP INSTRUCTIONS:
 * 1. Copy this file to config.php
 * 2. Generate a secure random token (see below)
 * 3. Replace CHANGE_ME_TO_A_RANDOM_STRING with your token
 * 4. Add config.php to your .gitignore
 */

// Generate a secure token with one of these methods:
// Linux/Mac:   openssl rand -hex 32
// Online:      https://www.random.org/strings/
// PHP:         php -r "echo bin2hex(random_bytes(32));"

define('AUTH_TOKEN', 'CHANGE_ME_TO_A_RANDOM_STRING');

// Optional: Customize these settings
define('CALENDAR_WINDOW_DAYS', 365);  // How many days ahead to generate
define('UPDATE_INTERVAL', 86400);     // How often calendars refresh (seconds)
