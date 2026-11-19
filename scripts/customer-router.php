<?php
// PHP development server router. Run from the repository root.
// Resolve in PHP to preserve Vietnamese Windows paths (CLI arguments may use an ANSI code page).
session_save_path(dirname(__DIR__).'/.local/sessions');
$path=rawurldecode(parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH) ?: '/');
if(preg_match('~(^|/)(\.|scripts/|tests/|database/|config/|includes/|docs/)~',$path)
    || preg_match('/\.(?:sql|md|log|eml|py|sh)$/i',$path)) {
    http_response_code(404);exit('Not found');
}
if($path==='/') {require __DIR__.'/../index.php';return true;}
return false;
