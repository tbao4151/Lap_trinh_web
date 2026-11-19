<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli' && str_replace('\\', '/', strtolower((string)realpath($_SERVER['SCRIPT_FILENAME'] ?? ''))) === str_replace('\\', '/', strtolower((string)realpath(__FILE__)))) {
    http_response_code(403);
    exit('Forbidden');
}
/**
 * Nền chung cho các trang Admin Operations (TV3): phân quyền, validate đầu vào, upload ảnh, POST handler và audit log.
 * Layout HTML dùng chung qua admin/_layout.php.
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/storefront.php';
require_once __DIR__ . '/_layout.php';

$user = cas_require_user(true);
$db = cas_db();

/** Quyền theo thao tác. Vai trò lấy từ includes/auth.php (staff/admin/owner), không tạo role mới. */
const CAS_PERMISSIONS = [
    'catalog.edit'      => ['admin', 'owner'],          // sản phẩm, ảnh, giá nhanh, phân loại
    'storefront.edit'   => ['admin', 'owner'],          // giấy gói, danh mục khám phá
    'inventory.move'    => ['staff', 'admin', 'owner'], // nhập / xuất kho theo phiếu
    'inventory.adjust'  => ['admin', 'owner'],          // kiểm kê đặt lại số tồn
    'inventory.items'   => ['admin', 'owner'],          // tạo / sửa danh mục nguyên liệu
    'delivery.update'   => ['staff', 'admin', 'owner'],
    'refund.request'    => ['staff', 'admin', 'owner'],
    'refund.review'     => ['admin', 'owner'],
    'staff.view'        => ['admin', 'owner'],
    'staff.manage'      => ['admin', 'owner'],          // admin chỉ thao tác trên tài khoản staff
    'settings.edit'     => ['admin', 'owner'],
    'owner.panel'       => ['owner'],
];

final class CasForbidden extends RuntimeException
{
}

function cas_can(array $user, string $permission): bool
{
    return in_array($user['role'], CAS_PERMISSIONS[$permission] ?? [], true);
}

function cas_client_ip(): string
{
    return substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
}

/** Ghi nhật ký quản trị. Không bao giờ làm hỏng thao tác chính nếu ghi log lỗi. */
function cas_audit(string $action, string $entity, $entityId = null, array $detail = [], string $outcome = 'ok'): void
{
    global $user;
    try {
        $q = cas_db()->prepare('INSERT INTO admin_audit_log(user_id,action,entity_type,entity_id,outcome,detail,ip) VALUES(?,?,?,?,?,?,?)');
        $q->execute([
            $user['user_id'] ?? null, $action, $entity, $entityId === null ? null : (string)$entityId, $outcome,
            $detail ? json_encode($detail, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null, cas_client_ip(),
        ]);
    } catch (Throwable $e) {
        error_log('CAS audit: ' . $e->getMessage());
    }
}

/** Chặn thao tác khi thiếu quyền, đồng thời ghi lại lần bị chặn. */
function cas_require_permission(string $permission, string $action = ''): void
{
    global $user;
    if (!cas_can($user, $permission)) {
        cas_audit($action ?: $permission, 'permission', null, ['permission' => $permission, 'role' => $user['role']], 'denied');
        throw new CasForbidden('Tài khoản ' . (CAS_ROLE_LABELS[$user['role']] ?? $user['role']) . ' không có quyền thực hiện thao tác này.');
    }
}

// ------------------------------------------------------------------ đầu vào
function cas_in_str(string $field, int $min, int $max, string $label): string
{
    $value = trim((string)($_POST[$field] ?? ''));
    $len = mb_strlen($value, 'UTF-8');
    if ($len < $min || $len > $max) {
        throw new DomainException($min > 0 ? "{$label} phải dài từ {$min} đến {$max} ký tự." : "{$label} tối đa {$max} ký tự.");
    }
    return $value;
}

function cas_in_int(string $field, int $min, int $max, string $label): int
{
    $value = filter_var($_POST[$field] ?? null, FILTER_VALIDATE_INT);
    if ($value === false || $value < $min || $value > $max) {
        throw new DomainException("{$label} phải là số nguyên từ " . number_format($min, 0, ',', '.') . ' đến ' . number_format($max, 0, ',', '.') . '.');
    }
    return $value;
}

function cas_in_decimal(string $field, float $min, float $max, string $label): float
{
    $raw = str_replace(',', '.', trim((string)($_POST[$field] ?? '')));
    if (!preg_match('/^-?\d{1,10}(\.\d{1,2})?$/', $raw) || (float)$raw < $min || (float)$raw > $max) {
        throw new DomainException("{$label} không hợp lệ (tối đa 2 chữ số thập phân, từ {$min} đến {$max}).");
    }
    return (float)$raw;
}

function cas_in_choice(string $field, array $allowed, string $label): string
{
    $value = (string)($_POST[$field] ?? '');
    if (!in_array($value, $allowed, true)) {
        throw new DomainException("{$label} không hợp lệ.");
    }
    return $value;
}

function cas_in_id(string $field = 'id'): int
{
    return cas_in_int($field, 1, PHP_INT_MAX, 'Mã bản ghi');
}

/** Token một lần cho form có tác động tiền / kho, lưu cột UNIQUE để chống gửi trùng. */
function cas_form_token(): string
{
    return bin2hex(random_bytes(16));
}

function cas_in_token(): string
{
    $token = (string)($_POST['request_token'] ?? '');
    if (!preg_match('/^[a-f0-9]{32}$/', $token)) {
        throw new DomainException('Thiếu mã phiếu. Tải lại trang và thử lại.');
    }
    return $token;
}

/** Chuyển tên tiếng Việt thành slug ASCII. */
function cas_slugify(string $text): string
{
    $map = ['a' => 'àáạảãâầấậẩẫăằắặẳẵ', 'e' => 'èéẹẻẽêềếệểễ', 'i' => 'ìíịỉĩ', 'o' => 'òóọỏõôồốộổỗơờớợởỡ',
            'u' => 'ùúụủũưừứựửữ', 'y' => 'ỳýỵỷỹ', 'd' => 'đ'];
    $text = mb_strtolower($text, 'UTF-8');
    foreach ($map as $ascii => $chars) {
        $text = preg_replace('/[' . $chars . ']/u', $ascii, $text);
    }
    return trim((string)preg_replace('/[^a-z0-9]+/', '-', $text), '-');
}

/**
 * Lưu ảnh tải lên: kiểm MIME thật bằng finfo, kích thước, ảnh hợp lệ; tên file ngẫu nhiên, đuôi theo MIME.
 * Trả về đường dẫn tương đối từ gốc website (vd assets/uploads/products/12/ab12.webp).
 */
function cas_store_image(array $file, string $folder, int $maxBytes = 2097152): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file((string)$file['tmp_name'])) {
        throw new DomainException('Tải ảnh thất bại.');
    }
    if ((int)$file['size'] <= 0 || (int)$file['size'] > $maxBytes) {
        throw new DomainException('Ảnh phải nhỏ hơn ' . round($maxBytes / 1048576, 1) . ' MB.');
    }
    $allowed = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp'];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file((string)$file['tmp_name']);
    $size = @getimagesize((string)$file['tmp_name']);
    if (!isset($allowed[$mime]) || $size === false || $size[0] < 1 || $size[0] > 6000 || $size[1] > 6000) {
        throw new DomainException('Chỉ nhận ảnh PNG, JPG hoặc WEBP hợp lệ.');
    }
    $relative = 'assets/uploads/' . trim($folder, '/');
    $dir = __DIR__ . '/../' . $relative;
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        throw new RuntimeException('Không tạo được thư mục lưu ảnh.');
    }
    $name = bin2hex(random_bytes(12)) . '.' . $allowed[$mime];
    if (!move_uploaded_file((string)$file['tmp_name'], $dir . '/' . $name)) {
        throw new RuntimeException('Không lưu được ảnh.');
    }
    return $relative . '/' . $name;
}

/** Danh sách file từ input multiple (images[]), bỏ ô trống. */
function cas_uploaded_files(string $field): array
{
    $files = $_FILES[$field] ?? null;
    if (!$files || !is_array($files['name'])) {
        return $files && ($files['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE ? [$files] : [];
    }
    $list = [];
    foreach ($files['name'] as $i => $name) {
        if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        $list[] = ['name' => $name, 'type' => $files['type'][$i], 'tmp_name' => $files['tmp_name'][$i], 'error' => $files['error'][$i], 'size' => $files['size'][$i]];
    }
    return $list;
}

function cas_delete_upload(?string $path): void
{
    if ($path && str_starts_with($path, 'assets/uploads/') && !str_contains($path, '..')) {
        $full = __DIR__ . '/../' . $path;
        if (is_file($full)) {
            @unlink($full);
        }
    }
}

// ------------------------------------------------------------------ POST / thông báo
$cas_error = '';
$cas_status = 200;

function cas_flash(string $message): void
{
    $_SESSION['cas_ops_flash'] = $message;
}

/**
 * Xử lý POST theo action. Handler trả về thông báo thành công; khi lỗi, trang được render lại với thông báo lỗi
 * và mã HTTP phù hợp (403 thiếu quyền, 422 dữ liệu sai, 409 xung đột CSDL).
 */
function cas_ops_post(array $handlers): void
{
    global $cas_error, $cas_status, $db;
    if (!cas_post()) {
        return;
    }
    try {
        cas_assert_csrf();
        $action = (string)($_POST['action'] ?? '');
        if (!isset($handlers[$action])) {
            throw new DomainException('Hành động không được hỗ trợ trên trang này.');
        }
        // Handler trả về thông báo, hoặc [thông báo, trang đích] khi cần chuyển sang trang khác.
        $result = $handlers[$action]();
        [$message, $target] = is_array($result) ? $result : [$result, null];
        cas_flash($message ?: 'Đã lưu thay đổi vào MySQL.');
        if ($target === null) {
            $query = $_SERVER['QUERY_STRING'] ?? '';
            $target = basename($_SERVER['SCRIPT_NAME'] ?? 'index.php') . ($query !== '' ? '?' . $query : '');
        }
        cas_redirect($target);
    } catch (Throwable $e) {
        // Lưới an toàn: mọi lỗi giữa chừng đều huỷ transaction đang mở (kho, hoàn tiền, quyền chủ sở hữu...).
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        if ($e instanceof CasForbidden) {
            $cas_status = 403;
            $cas_error = $e->getMessage();
        } elseif ($e instanceof DomainException) {
            $cas_status = 422;
            $cas_error = $e->getMessage();
        } elseif ($e instanceof PDOException) {
            error_log('CAS admin ops: ' . $e->getMessage());
            $cas_status = 409;
            $cas_error = 'Không thể lưu: dữ liệu bị trùng, đang được sử dụng hoặc MySQL từ chối thay đổi.';
        } elseif ($e instanceof RuntimeException) {
            $cas_status = $e->getCode() === 403 || http_response_code() === 403 ? 403 : 422;
            $cas_error = $e->getMessage();
        } else {
            throw $e;
        }
    }
    http_response_code($cas_status);
}

// ------------------------------------------------------------------ hiển thị
function cas_qty(mixed $value): string
{
    $v = (float)$value;
    return floor($v) == $v ? number_format($v, 0, ',', '.') : number_format($v, 2, ',', '.');
}

function cas_select(string $name, array $options, string $selected, string $attrs = ''): string
{
    $html = '<select class="admin-select" name="' . cas_e($name) . '" ' . $attrs . '>';
    foreach ($options as $value => $label) {
        $html .= '<option value="' . cas_e((string)$value) . '"' . ((string)$value === $selected ? ' selected' : '') . '>' . cas_e((string)$label) . '</option>';
    }
    return $html . '</select>';
}

function cas_rows(string $sql, array $args = []): array
{
    $q = cas_db()->prepare($sql);
    $q->execute($args);
    return $q->fetchAll();
}

function cas_value(string $sql, array $args = []): mixed
{
    $q = cas_db()->prepare($sql);
    $q->execute($args);
    return $q->fetchColumn();
}

function cas_status_badge(string $label, string $tone = ''): string
{
    return '<span class="admin-status' . ($tone ? ' ' . $tone : '') . '">' . cas_e($label) . '</span>';
}

/** Mở/đóng layout admin dùng chung với _app.php qua admin/_layout.php. */
function cas_ops_start(string $title, string $subtitle, string $icon = 'bi-flower2'): void
{
    global $user, $cas_error;
    $flash = $_SESSION['cas_ops_flash'] ?? '';
    unset($_SESSION['cas_ops_flash']);
    cas_admin_layout_start($user, $title, $subtitle, $icon, '', $cas_error, (string)$flash);
}

function cas_ops_end(): void
{
    cas_admin_layout_end();
}

/** Trang chỉ dành cho một số vai trò: GET trái quyền nhận 403 (có ghi log), không lộ dữ liệu. */
function cas_page_roles(array $roles, string $title): void
{
    global $user;
    if (in_array($user['role'], $roles, true)) {
        return;
    }
    cas_audit('page_view', 'permission', basename($_SERVER['SCRIPT_NAME'] ?? ''), ['role' => $user['role']], 'denied');
    http_response_code(403);
    cas_ops_start($title, 'Bạn không có quyền truy cập khu vực này.', 'bi-shield-lock');
    echo '<section class="admin-card admin-section"><p class="mb-0">Tài khoản ' . cas_e(CAS_ROLE_LABELS[$user['role']] ?? $user['role'])
        . ' không được phép xem trang này. Liên hệ quản trị viên nếu bạn cần được cấp quyền.</p></section>';
    cas_ops_end();
    exit;
}
