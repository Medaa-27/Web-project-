<?php
/**
 * Language Loader
 * Loads selected language from session, falls back to English
 */

// Default language
define('DEFAULT_LANGUAGE', 'en');

// Supported languages
$supported_languages = ['en', 'am', 'ti'];

// Handle explicit language selection via query string
if (isset($_GET['lang']) && in_array($_GET['lang'], $supported_languages)) {
    $_SESSION['lang'] = $_GET['lang'];
}

// Get language from session or default
$lang_code = isset($_SESSION['lang']) && in_array($_SESSION['lang'], $supported_languages) ? $_SESSION['lang'] : DEFAULT_LANGUAGE;

// Load language file
$lang_file = __DIR__ . '/lang/' . $lang_code . '.php';
if (file_exists($lang_file)) {
    require_once $lang_file;
} else {
    // Fallback to English
    require_once __DIR__ . '/lang/en.php';
}

/**
 * Translation function
 * @param string $key Translation key
 * @param mixed ...$args Optional sprintf arguments
 * @return string Translated string
 */
function __($key, ...$args) {
    global $lang;
    $text = isset($lang[$key]) ? $lang[$key] : $key;
    if (!empty($args)) {
        $text = sprintf($text, ...$args);
    }
    return $text;
}

/**
 * Translation helper alias for templates
 */
function t($key, ...$args) {
    return __($key, ...$args);
}

/**
 * Set language in session
 * @param string $lang_code Language code (en, am, ti)
 */
function set_language($lang_code) {
    global $supported_languages;
    if (in_array($lang_code, $supported_languages)) {
        $_SESSION['lang'] = $lang_code;
    }
}

/**
 * Get current language code
 * @return string
 */
function get_current_language() {
    return $_SESSION['lang'] ?? DEFAULT_LANGUAGE;
}

/**
 * Build a URL that preserves current query string and sets language code.
 */
function build_language_url($lang_code) {
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $parts = parse_url($uri);
    parse_str($parts['query'] ?? '', $query);
    $query['lang'] = $lang_code;
    $path = $parts['path'] ?? '/';
    return $path . '?' . http_build_query($query);
}
?>