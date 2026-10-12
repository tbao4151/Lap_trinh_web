<?php
declare(strict_types=1);
require_once __DIR__.'/includes/auth.php';
if(cas_post() && cas_csrf_valid($_POST['csrf']??null)){cas_logout();cas_redirect('index.php');}
http_response_code(405);
echo 'Yêu cầu không hợp lệ.';
