<?php
// PHP development server only. Keep the document root restricted to public/.
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (is_string($path) && preg_match('~^/api/([a-z_]+\.php)$~D', $path, $match)) {
    $endpoint = dirname(__DIR__) . '/api/' . $match[1];
    if (is_file($endpoint)) {
        require $endpoint;
        return true;
    }
}
return false;
