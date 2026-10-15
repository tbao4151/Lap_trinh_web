<?php
declare(strict_types=1);
/**
 * ADM-06 — Tài khoản nội bộ: nhân viên, hồ sơ cá nhân, chủ sở hữu và nhật ký quản trị.
 * Vai trò vẫn là roles.name của includes/auth.php (staff/admin/owner); không tạo vai trò mới, không xoá tài khoản (chỉ khoá).
 */

const CAS_INTERNAL_ROLES = ['staff', 'admin', 'owner'];
const CAS_ACCOUNT_STATUS = ['active' => 'Đang hoạt động', 'locked' => 'Đã khoá'];

function cas_people_role_id(string $role): int
{
    $id = cas_value('SELECT role_id FROM roles WHERE name=?', [$role]);
    if ($id === false) {
        throw new DomainException('Vai trò "' . $role . '" chưa có trong CSDL. Chạy migration 001.');
    }
    return (int)$id;
}

function cas_people_find(int $id, bool $lock = false): ?array
{
    $rows = cas_rows('SELECT u.user_id, u.full_name, u.email, u.phone, u.status, u.password_hash, r.name role
        FROM users u JOIN roles r ON r.role_id=u.role_id WHERE u.user_id=?' . ($lock ? ' FOR UPDATE' : ''), [$id]);
    return $rows[0] ?? null;
}

/** Xác nhận lại bằng mật khẩu của chính người thao tác cho các thao tác nhạy cảm. */
function cas_people_confirm_password(array $actor, string $field = 'confirm_password'): void
{
    $hash = cas_value('SELECT password_hash FROM users WHERE user_id=?', [$actor['user_id']]);
    if (!is_string($hash) || !password_verify((string)($_POST[$field] ?? ''), $hash)) {
        cas_audit('password_confirm', 'user', $actor['user_id'], [], 'denied');
        throw new DomainException('Mật khẩu xác nhận không đúng.');
    }
}

function cas_people_phone(string $field, ?int $exceptUser = null): ?string
{
    $phone = preg_replace('/\D+/', '', (string)($_POST[$field] ?? ''));
    if ($phone === '') {
        return null;
    }
    if (!preg_match('/^0\d{9}$/', $phone)) {
        throw new DomainException('Số điện thoại phải gồm 10 chữ số, bắt đầu bằng 0.');
    }
    if ((int)cas_value('SELECT COUNT(*) FROM users WHERE phone=? AND user_id<>?', [$phone, $exceptUser ?? 0])) {
        throw new DomainException('Số điện thoại đã được dùng cho một tài khoản khác.');
    }
    return $phone;
}

function cas_people_new_password(string $field, string $confirmField): string
{
    $password = (string)($_POST[$field] ?? '');
    if (!cas_valid_password($password)) {
        throw new DomainException('Mật khẩu cần tối thiểu 10 ký tự, gồm chữ hoa, chữ thường, số và ký tự đặc biệt.');
    }
    if (!hash_equals($password, (string)($_POST[$confirmField] ?? ''))) {
        throw new DomainException('Mật khẩu nhập lại không khớp.');
    }
    return $password;
}

/**
 * Người thao tác có được quản lý tài khoản đích không:
 * không tự thao tác trên chính mình; admin chỉ quản lý staff; owner quản lý staff/admin; tài khoản owner chỉ xử lý ở trang Chủ sở hữu.
 */
function cas_people_assert_manageable(array $actor, ?array $target, string $action): array
{
    if (!$target || !in_array($target['role'], CAS_INTERNAL_ROLES, true)) {
        throw new DomainException('Không tìm thấy tài khoản nội bộ.');
    }
    if ((int)$target['user_id'] === (int)$actor['user_id']) {
        cas_audit($action, 'user', $target['user_id'], ['reason' => 'self'], 'denied');
        throw new CasForbidden('Không thể tự thay đổi trạng thái hoặc vai trò của chính mình.');
    }
    if ($target['role'] === 'owner') {
        cas_audit($action, 'user', $target['user_id'], ['reason' => 'owner_target'], 'denied');
        throw new CasForbidden('Tài khoản chủ sở hữu chỉ được quản lý ở trang Chủ sở hữu.');
    }
    if ($actor['role'] === 'admin' && $target['role'] !== 'staff') {
        cas_audit($action, 'user', $target['user_id'], ['reason' => 'admin_on_admin'], 'denied');
        throw new CasForbidden('Quản trị viên chỉ quản lý được tài khoản nhân viên.');
    }
    return $target;
}

// ------------------------------------------------------------------ Nhân viên
function cas_staff_create(PDO $db, array $actor): string
{
    cas_require_permission('staff.manage', 'staff_create');
    $name = cas_in_str('full_name', 2, 120, 'Họ tên');
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
        throw new DomainException('Email không hợp lệ.');
    }
    $allowedRoles = $actor['role'] === 'owner' ? ['staff', 'admin'] : ['staff'];
    $role = (string)($_POST['role'] ?? 'staff');
    if (!in_array($role, $allowedRoles, true)) {
        cas_audit('staff_create', 'user', null, ['role' => $role], 'denied');
        throw new CasForbidden($actor['role'] === 'owner' ? 'Tài khoản chủ sở hữu được nâng cấp ở trang Chủ sở hữu.' : 'Quản trị viên chỉ tạo được tài khoản nhân viên.');
    }
    $phone = cas_people_phone('phone');
    $password = cas_people_new_password('password', 'password_confirm');
    if ((int)cas_value('SELECT COUNT(*) FROM users WHERE email=?', [$email])) {
        throw new DomainException('Email đã được dùng cho một tài khoản khác.');
    }
    $db->prepare("INSERT INTO users(role_id,full_name,email,password_hash,phone,status) VALUES(?,?,?,?,?,'active')")
       ->execute([cas_people_role_id($role), $name, $email, password_hash($password, PASSWORD_DEFAULT), $phone]);
    $id = (int)$db->lastInsertId();
    cas_audit('staff_create', 'user', $id, ['email' => $email, 'role' => $role]);
    return 'Đã tạo tài khoản ' . CAS_ROLE_LABELS[$role] . ' ' . $email . '. Gửi mật khẩu tạm cho người dùng qua kênh riêng và yêu cầu đổi sau lần đăng nhập đầu.';
}

function cas_staff_status(PDO $db, array $actor): string
{
    cas_require_permission('staff.manage', 'staff_status');
    $status = cas_in_choice('status', array_keys(CAS_ACCOUNT_STATUS), 'Trạng thái');
    $target = cas_people_assert_manageable($actor, cas_people_find(cas_in_id('id')), 'staff_status');
    if ($target['status'] === $status) {
        throw new DomainException('Tài khoản đã ở trạng thái "' . CAS_ACCOUNT_STATUS[$status] . '".');
    }
    $db->prepare('UPDATE users SET status=? WHERE user_id=?')->execute([$status, $target['user_id']]);
    cas_audit('staff_status', 'user', $target['user_id'], ['before' => $target['status'], 'after' => $status]);
    return ($status === 'locked' ? 'Đã khoá ' : 'Đã mở khoá ') . $target['email'] . ($status === 'locked' ? ' — phiên đăng nhập hiện tại mất quyền ngay ở yêu cầu kế tiếp.' : '.');
}

function cas_staff_role(PDO $db, array $actor): string
{
    if ($actor['role'] !== 'owner') {
        cas_audit('staff_role', 'user', $_POST['id'] ?? null, ['role' => $actor['role']], 'denied');
        throw new CasForbidden('Chỉ chủ sở hữu được đổi vai trò nhân viên / quản trị viên.');
    }
    $role = cas_in_choice('role', ['staff', 'admin'], 'Vai trò');
    $target = cas_people_assert_manageable($actor, cas_people_find(cas_in_id('id')), 'staff_role');
    if ($target['role'] === $role) {
        throw new DomainException('Tài khoản đã có vai trò ' . CAS_ROLE_LABELS[$role] . '.');
    }
    $db->prepare('UPDATE users SET role_id=? WHERE user_id=?')->execute([cas_people_role_id($role), $target['user_id']]);
    cas_audit('staff_role', 'user', $target['user_id'], ['before' => $target['role'], 'after' => $role]);
    return 'Đã đổi ' . $target['email'] . ' thành ' . CAS_ROLE_LABELS[$role] . '.';
}

function cas_staff_reset_password(PDO $db, array $actor): string
{
    cas_require_permission('staff.manage', 'staff_reset_password');
    $target = cas_people_assert_manageable($actor, cas_people_find(cas_in_id('id')), 'staff_reset_password');
    $password = cas_people_new_password('new_password', 'new_password_confirm');
    cas_people_confirm_password($actor);
    $db->prepare('UPDATE users SET password_hash=?,session_version=session_version+1 WHERE user_id=?')->execute([password_hash($password, PASSWORD_DEFAULT), $target['user_id']]);
    cas_audit('staff_reset_password', 'user', $target['user_id']);
    return 'Đã đặt mật khẩu tạm cho ' . $target['email'] . '.';
}

function cas_staff_page(): void
{
    global $db, $user;
    cas_page_roles(['admin', 'owner'], 'Nhân viên');
    cas_ops_post([
        'staff_create' => static fn() => cas_staff_create($db, $user),
        'staff_status' => static fn() => cas_staff_status($db, $user),
        'staff_role' => static fn() => cas_staff_role($db, $user),
        'staff_reset_password' => static fn() => cas_staff_reset_password($db, $user),
    ]);
    $q = trim((string)($_GET['q'] ?? ''));
    $role = in_array($_GET['role'] ?? '', CAS_INTERNAL_ROLES, true) ? $_GET['role'] : '';
    $status = array_key_exists($_GET['status'] ?? '', CAS_ACCOUNT_STATUS) ? $_GET['status'] : '';
    $where = ["r.name IN ('staff','admin','owner')"];
    $args = [];
    if ($q !== '') {
        $where[] = '(u.full_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)';
        array_push($args, "%$q%", "%$q%", "%$q%");
    }
    if ($role !== '') {
        $where[] = 'r.name=?';
        $args[] = $role;
    }
    if ($status !== '') {
        $where[] = 'u.status=?';
        $args[] = $status;
    }
    $rows = cas_rows('SELECT u.user_id, u.full_name, u.email, u.phone, u.status, u.created_at, r.name role,
            (SELECT MAX(a.created_at) FROM admin_audit_log a WHERE a.user_id=u.user_id) last_action
        FROM users u JOIN roles r ON r.role_id=u.role_id WHERE ' . implode(' AND ', $where) . "
        ORDER BY FIELD(r.name,'owner','admin','staff'), u.full_name LIMIT 200", $args);
    $counts = [];
    foreach (cas_rows("SELECT r.name role, u.status, COUNT(*) n FROM users u JOIN roles r ON r.role_id=u.role_id WHERE r.name IN ('staff','admin','owner') GROUP BY r.name, u.status") as $c) {
        $counts[$c['role']][$c['status']] = (int)$c['n'];
    }
    $resetId = filter_var($_GET['reset'] ?? null, FILTER_VALIDATE_INT);
    $resetTarget = $resetId ? cas_people_find($resetId) : null;
    $isOwner = $user['role'] === 'owner';
    $canManage = static function (array $row) use ($user): bool {
        return (int)$row['user_id'] !== (int)$user['user_id'] && $row['role'] !== 'owner' && ($user['role'] === 'owner' || $row['role'] === 'staff');
    };

    cas_ops_start('Nhân viên', 'Tài khoản nội bộ trong bảng users. Quản trị viên quản lý nhân viên; chủ sở hữu quản lý cả quản trị viên.', 'bi-person-badge');
    ?>
<section class="admin-grid-3 admin-section">
  <?php foreach (CAS_INTERNAL_ROLES as $r): ?>
  <article class="admin-card"><span class="cas-muted"><?= cas_e(CAS_ROLE_LABELS[$r]) ?></span><h2 class="admin-card-title mb-0"><?= (int)($counts[$r]['active'] ?? 0) ?> đang hoạt động</h2><span class="cas-muted"><?= (int)($counts[$r]['locked'] ?? 0) ?> đã khoá</span></article>
  <?php endforeach; ?>
</section>
<section class="admin-card admin-section">
  <form class="cas-admin-stack" method="get">
    <input class="admin-input" name="q" value="<?= cas_e($q) ?>" placeholder="Tên, email hoặc SĐT">
    <?= cas_select('role', ['' => 'Mọi vai trò'] + array_intersect_key(CAS_ROLE_LABELS, array_flip(CAS_INTERNAL_ROLES)), $role) ?>
    <?= cas_select('status', ['' => 'Mọi trạng thái'] + CAS_ACCOUNT_STATUS, $status) ?>
    <button class="admin-btn" type="submit">Lọc</button><a class="admin-btn-light" href="nhan-vien.php">Bỏ lọc</a>
  </form>
  <div class="admin-table-wrap mt-3"><table class="admin-table">
    <thead><tr><th>Tài khoản</th><th>Vai trò</th><th>Trạng thái</th><th>Hoạt động gần nhất</th><th>Thao tác</th></tr></thead><tbody>
    <?php foreach ($rows as $row): ?>
    <tr><td><strong><?= cas_e($row['full_name']) ?></strong><?= (int)$row['user_id'] === (int)$user['user_id'] ? ' <span class="cas-muted">(bạn)</span>' : '' ?><div class="cas-muted"><?= cas_e($row['email']) ?><?= $row['phone'] ? ' · ' . cas_e($row['phone']) : '' ?></div></td>
      <td><?= cas_status_badge(CAS_ROLE_LABELS[$row['role']], $row['role'] === 'owner' ? 'warning' : ($row['role'] === 'admin' ? 'success' : '')) ?></td>
      <td><?= cas_status_badge(CAS_ACCOUNT_STATUS[$row['status']] ?? $row['status'], $row['status'] === 'active' ? 'success' : 'danger') ?></td>
      <td class="cas-muted"><?= cas_e($row['last_action'] ?? '—') ?><div>Tạo <?= cas_e(substr((string)$row['created_at'], 0, 10)) ?></div></td>
      <td><?php if ($canManage($row)): ?><div class="cas-admin-table-form">
        <form method="post" data-confirm="<?= $row['status'] === 'active' ? 'Khoá tài khoản này? Người dùng sẽ mất quyền truy cập ngay.' : 'Mở khoá tài khoản này?' ?>"><?= cas_csrf_form() ?><input type="hidden" name="action" value="staff_status"><input type="hidden" name="id" value="<?= (int)$row['user_id'] ?>"><input type="hidden" name="status" value="<?= $row['status'] === 'active' ? 'locked' : 'active' ?>"><button class="admin-btn-light<?= $row['status'] === 'active' ? ' text-danger' : '' ?>" type="submit"><?= $row['status'] === 'active' ? 'Khoá' : 'Mở khoá' ?></button></form>
        <?php if ($isOwner): ?><form method="post" data-confirm="Đổi vai trò tài khoản này?"><?= cas_csrf_form() ?><input type="hidden" name="action" value="staff_role"><input type="hidden" name="id" value="<?= (int)$row['user_id'] ?>"><input type="hidden" name="role" value="<?= $row['role'] === 'staff' ? 'admin' : 'staff' ?>"><button class="admin-btn-light" type="submit"><?= $row['role'] === 'staff' ? 'Nâng lên Quản trị viên' : 'Hạ xuống Nhân viên' ?></button></form><?php endif; ?>
        <a class="admin-btn-light" href="?reset=<?= (int)$row['user_id'] ?>#resetForm">Đặt mật khẩu tạm</a>
      </div><?php elseif ($row['role'] === 'owner' && $isOwner): ?><a class="admin-btn-light" href="chu-so-huu.php">Quản lý ở trang Chủ sở hữu</a><?php else: ?><span class="cas-muted">—</span><?php endif; ?></td></tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?><tr><td colspan="5"><div class="admin-empty">Không có tài khoản phù hợp bộ lọc.</div></td></tr><?php endif; ?>
    </tbody></table></div>
</section>
<section class="admin-grid-2 admin-section">
<article class="admin-card">
  <h2 class="admin-card-title">Tạo tài khoản nội bộ</h2>
  <form method="post" class="admin-form-grid mt-3" autocomplete="off"><?= cas_csrf_form() ?><input type="hidden" name="action" value="staff_create">
    <div class="full"><label class="admin-label">Họ tên</label><input class="admin-input" name="full_name" maxlength="120" required></div>
    <div><label class="admin-label">Email đăng nhập</label><input class="admin-input" name="email" type="email" maxlength="150" required></div>
    <div><label class="admin-label">Số điện thoại</label><input class="admin-input" name="phone" inputmode="tel" maxlength="12"></div>
    <div><label class="admin-label">Vai trò</label><?= cas_select('role', $isOwner ? ['staff' => 'Nhân viên', 'admin' => 'Quản trị viên'] : ['staff' => 'Nhân viên'], 'staff') ?></div>
    <div></div>
    <div><label class="admin-label">Mật khẩu tạm</label><input class="admin-input" name="password" type="password" autocomplete="new-password" required></div>
    <div><label class="admin-label">Nhập lại mật khẩu</label><input class="admin-input" name="password_confirm" type="password" autocomplete="new-password" required></div>
    <div class="full"><button class="admin-btn" type="submit">Tạo tài khoản</button><p class="cas-muted mt-2 mb-0">Mật khẩu ≥ 10 ký tự, có chữ hoa, chữ thường, số, ký tự đặc biệt. Hệ thống chỉ lưu bản băm (password_hash).</p></div>
  </form>
</article>
<article class="admin-card" id="resetForm">
  <?php if ($resetTarget && $canManage($resetTarget)): ?>
  <h2 class="admin-card-title">Đặt mật khẩu tạm cho <?= cas_e($resetTarget['email']) ?></h2>
  <form method="post" class="admin-form-grid mt-3" autocomplete="off" data-confirm="Đặt mật khẩu tạm cho tài khoản này?"><?= cas_csrf_form() ?><input type="hidden" name="action" value="staff_reset_password"><input type="hidden" name="id" value="<?= (int)$resetTarget['user_id'] ?>">
    <div><label class="admin-label">Mật khẩu tạm mới</label><input class="admin-input" name="new_password" type="password" autocomplete="new-password" required></div>
    <div><label class="admin-label">Nhập lại</label><input class="admin-input" name="new_password_confirm" type="password" autocomplete="new-password" required></div>
    <div class="full"><label class="admin-label">Mật khẩu của bạn (xác nhận)</label><input class="admin-input" name="confirm_password" type="password" autocomplete="current-password" required></div>
    <div class="full"><button class="admin-btn" type="submit">Đặt mật khẩu tạm</button> <a class="admin-btn-light" href="nhan-vien.php">Huỷ</a></div>
  </form>
  <?php else: ?>
  <h2 class="admin-card-title">Quy tắc phân quyền</h2>
  <ul class="mb-0 mt-2">
    <li>Nhân viên: xử lý đơn, giao hàng, nhập/xuất kho, tạo yêu cầu hoàn tiền; không vào được trang này.</li>
    <li>Quản trị viên: thêm nhân viên, khoá/mở khoá, đặt mật khẩu tạm cho nhân viên; sửa sản phẩm, kho, cài đặt.</li>
    <li>Chủ sở hữu: toàn quyền, đổi vai trò nhân viên ↔ quản trị viên, quản lý chủ sở hữu ở trang riêng.</li>
    <li>Không ai tự đổi vai trò / trạng thái của chính mình; tài khoản không bị xoá, chỉ khoá để giữ lịch sử.</li>
  </ul>
  <?php endif; ?>
</article>
</section>
<?php
    cas_ops_end();
}

// ------------------------------------------------------------------ Tài khoản của tôi
function cas_profile_save(PDO $db, array $actor): string
{
    $name = cas_in_str('full_name', 2, 120, 'Họ tên');
    $phone = cas_people_phone('phone', (int)$actor['user_id']);
    if ($name === $actor['full_name'] && $phone === ($actor['phone'] ?: null)) {
        throw new DomainException('Không có thông tin nào thay đổi.');
    }
    $db->prepare('UPDATE users SET full_name=?, phone=? WHERE user_id=?')->execute([$name, $phone, $actor['user_id']]);
    cas_audit('profile_save', 'user', $actor['user_id'], ['before' => ['full_name' => $actor['full_name'], 'phone' => $actor['phone']], 'after' => ['full_name' => $name, 'phone' => $phone]]);
    return 'Đã cập nhật hồ sơ.';
}

function cas_password_change(PDO $db, array $actor): string
{
    cas_people_confirm_password($actor, 'current_password');
    $password = cas_people_new_password('new_password', 'new_password_confirm');
    if (hash_equals((string)$_POST['current_password'], $password)) {
        throw new DomainException('Mật khẩu mới phải khác mật khẩu hiện tại.');
    }
    $db->prepare('UPDATE users SET password_hash=?,session_version=session_version+1 WHERE user_id=?')->execute([password_hash($password, PASSWORD_DEFAULT), $actor['user_id']]);
    $version=$db->prepare('SELECT session_version FROM users WHERE user_id=?');
    $version->execute([$actor['user_id']]);
    $_SESSION['cas_session_version']=(int)$version->fetchColumn();
    session_regenerate_id(true);
    unset($_SESSION['cas_csrf']);
    cas_audit('password_change', 'user', $actor['user_id']);
    return 'Đã đổi mật khẩu. Phiên đăng nhập đã được cấp mã mới.';
}

function cas_profile_page(): void
{
    global $db, $user;
    cas_ops_post([
        'profile_save' => static fn() => cas_profile_save($db, $user),
        'password_change' => static fn() => cas_password_change($db, $user),
    ]);
    $me = cas_rows('SELECT u.created_at FROM users u WHERE u.user_id=?', [$user['user_id']])[0] ?? [];
    $activity = cas_rows('SELECT action, entity_type, entity_id, outcome, created_at FROM admin_audit_log WHERE user_id=? ORDER BY log_id DESC LIMIT 12', [$user['user_id']]);
    cas_ops_start('Tài khoản của tôi', 'Hồ sơ đăng nhập trang quản trị; vai trò do quản trị viên / chủ sở hữu cấp, không tự đổi tại đây.', 'bi-person-circle');
    ?>
<section class="admin-grid-2 admin-section">
<article class="admin-card">
  <h2 class="admin-card-title">Hồ sơ</h2>
  <p class="cas-muted">Email đăng nhập: <strong><?= cas_e($user['email']) ?></strong> · Vai trò: <?= cas_status_badge(CAS_ROLE_LABELS[$user['role']] ?? $user['role'], 'success') ?> · Tạo ngày <?= cas_e(substr((string)($me['created_at'] ?? ''), 0, 10)) ?></p>
  <form method="post" class="admin-form-grid"><?= cas_csrf_form() ?><input type="hidden" name="action" value="profile_save">
    <div class="full"><label class="admin-label">Họ tên</label><input class="admin-input" name="full_name" maxlength="120" required value="<?= cas_e(cas_post() && ($_POST['action'] ?? '') === 'profile_save' ? (string)($_POST['full_name'] ?? '') : $user['full_name']) ?>"></div>
    <div class="full"><label class="admin-label">Số điện thoại</label><input class="admin-input" name="phone" inputmode="tel" maxlength="12" value="<?= cas_e(cas_post() && ($_POST['action'] ?? '') === 'profile_save' ? (string)($_POST['phone'] ?? '') : (string)$user['phone']) ?>"></div>
    <div class="full"><button class="admin-btn" type="submit">Lưu hồ sơ</button></div>
  </form>
</article>
<article class="admin-card">
  <h2 class="admin-card-title">Đổi mật khẩu</h2>
  <form method="post" class="admin-form-grid mt-3" autocomplete="off"><?= cas_csrf_form() ?><input type="hidden" name="action" value="password_change">
    <div class="full"><label class="admin-label">Mật khẩu hiện tại</label><input class="admin-input" name="current_password" type="password" autocomplete="current-password" required></div>
    <div><label class="admin-label">Mật khẩu mới</label><input class="admin-input" name="new_password" type="password" autocomplete="new-password" required></div>
    <div><label class="admin-label">Nhập lại</label><input class="admin-input" name="new_password_confirm" type="password" autocomplete="new-password" required></div>
    <div class="full"><button class="admin-btn" type="submit">Đổi mật khẩu</button></div>
  </form>
</article>
</section>
<section class="admin-card admin-section">
  <h2 class="admin-card-title">Hoạt động gần đây của bạn</h2>
  <div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Thời gian</th><th>Thao tác</th><th>Đối tượng</th><th>Kết quả</th></tr></thead><tbody>
  <?php foreach ($activity as $a): ?><tr><td><?= cas_e($a['created_at']) ?></td><td><?= cas_e($a['action']) ?></td><td><?= cas_e($a['entity_type'] . ($a['entity_id'] !== null ? ' #' . $a['entity_id'] : '')) ?></td><td><?= cas_status_badge($a['outcome'] === 'ok' ? 'Thành công' : 'Bị chặn', $a['outcome'] === 'ok' ? 'success' : 'danger') ?></td></tr><?php endforeach; ?>
  <?php if (!$activity): ?><tr><td colspan="4"><div class="admin-empty">Chưa có thao tác nào được ghi nhận.</div></td></tr><?php endif; ?>
  </tbody></table></div>
</section>
<?php
    cas_ops_end();
}

// ------------------------------------------------------------------ Chủ sở hữu
/** Hiển thị JSON chi tiết nhật ký dễ đọc (bản ghi cũ có thể còn ký tự thoát \/). */
function cas_audit_detail_text(?string $detail): string
{
    $data = $detail !== null ? json_decode($detail, true) : null;
    return is_array($data) ? (string)json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : (string)$detail;
}

/** Khoá các dòng owner đang hoạt động trong transaction để hai thao tác đồng thời không thể cùng hạ owner cuối. */
function cas_owner_active_ids(PDO $db): array
{
    $q = $db->query("SELECT u.user_id FROM users u JOIN roles r ON r.role_id=u.role_id WHERE r.name='owner' AND u.status='active' FOR UPDATE");
    return array_map('intval', $q->fetchAll(PDO::FETCH_COLUMN));
}

function cas_owner_change(PDO $db, array $actor): string
{
    cas_require_permission('owner.panel', 'owner_change');
    $op = cas_in_choice('op', ['promote', 'demote', 'lock', 'unlock'], 'Thao tác');
    $id = cas_in_id('id');
    cas_people_confirm_password($actor);
    $db->beginTransaction();
    $owners = cas_owner_active_ids($db);
    $target = cas_people_find($id, true);
    if (!$target || !in_array($target['role'], ['admin', 'owner'], true)) {
        throw new DomainException('Chỉ quản trị viên hoặc chủ sở hữu mới được xử lý ở trang này.');
    }
    $removesOwner = ($op === 'demote' && $target['role'] === 'owner') || ($op === 'lock' && $target['role'] === 'owner' && $target['status'] === 'active');
    if ($removesOwner && array_diff($owners, [$id]) === []) {
        $db->rollBack();
        cas_audit('owner_' . $op, 'user', $id, ['reason' => 'last_owner'], 'denied');
        throw new DomainException('Không thể ' . ($op === 'demote' ? 'hạ cấp' : 'khoá') . ' chủ sở hữu đang hoạt động cuối cùng. Hãy nâng một quản trị viên khác lên chủ sở hữu trước.');
    }
    if ($op === 'lock' && (int)$target['user_id'] === (int)$actor['user_id']) {
        $db->rollBack();
        throw new DomainException('Không thể tự khoá tài khoản của chính mình.');
    }
    switch ($op) {
        case 'promote':
            if ($target['role'] !== 'admin' || $target['status'] !== 'active') {
                throw new DomainException('Chỉ nâng được quản trị viên đang hoạt động lên chủ sở hữu.');
            }
            $db->prepare('UPDATE users SET role_id=? WHERE user_id=?')->execute([cas_people_role_id('owner'), $id]);
            $message = 'Đã nâng ' . $target['email'] . ' lên Chủ sở hữu.';
            break;
        case 'demote':
            if ($target['role'] !== 'owner') {
                throw new DomainException('Tài khoản này không phải chủ sở hữu.');
            }
            $db->prepare('UPDATE users SET role_id=? WHERE user_id=?')->execute([cas_people_role_id('admin'), $id]);
            $message = 'Đã chuyển ' . $target['email'] . ' về Quản trị viên.';
            break;
        default:
            $status = $op === 'lock' ? 'locked' : 'active';
            if ($target['status'] === $status) {
                throw new DomainException('Tài khoản đã ở trạng thái "' . CAS_ACCOUNT_STATUS[$status] . '".');
            }
            $db->prepare('UPDATE users SET status=? WHERE user_id=?')->execute([$status, $id]);
            $message = ($op === 'lock' ? 'Đã khoá ' : 'Đã mở khoá ') . $target['email'] . '.';
    }
    $db->commit();
    cas_audit('owner_' . $op, 'user', $id, ['before' => ['role' => $target['role'], 'status' => $target['status']]]);
    if ((int)$target['user_id'] === (int)$actor['user_id'] && $op === 'demote') {
        return [$message . ' Bạn không còn quyền vào trang Chủ sở hữu.', 'index.php'];
    }
    return $message;
}

function cas_owner_page(): void
{
    global $db, $user;
    cas_page_roles(['owner'], 'Chủ sở hữu');
    cas_ops_post(['owner_change' => static fn() => cas_owner_change($db, $user)]);
    $leaders = cas_rows("SELECT u.user_id, u.full_name, u.email, u.status, r.name role FROM users u JOIN roles r ON r.role_id=u.role_id
        WHERE r.name IN ('owner','admin') ORDER BY FIELD(r.name,'owner','admin'), u.full_name");
    $activeOwners = count(array_filter($leaders, static fn($u) => $u['role'] === 'owner' && $u['status'] === 'active'));
    $kpi = [
        'Thao tác 24 giờ qua' => (int)cas_value("SELECT COUNT(*) FROM admin_audit_log WHERE created_at >= NOW() - INTERVAL 1 DAY"),
        'Lần bị chặn quyền (7 ngày)' => (int)cas_value("SELECT COUNT(*) FROM admin_audit_log WHERE outcome='denied' AND created_at >= NOW() - INTERVAL 7 DAY"),
        'Hoàn tiền chờ xử lý' => (int)cas_value("SELECT COUNT(*) FROM refunds WHERE status IN ('requested','approved')"),
        'Nguyên liệu dưới mức tối thiểu' => (int)cas_value("SELECT COUNT(*) FROM inventory_items WHERE status='active' AND quantity < min_quantity"),
    ];
    $action = trim((string)($_GET['log_action'] ?? ''));
    $outcome = in_array($_GET['outcome'] ?? '', ['ok', 'denied'], true) ? $_GET['outcome'] : '';
    $actor = filter_var($_GET['actor'] ?? null, FILTER_VALIDATE_INT) ?: null;
    $page = max(1, (int)($_GET['page'] ?? 1));
    $where = ['1=1'];
    $args = [];
    if ($action !== '') {
        $where[] = 'a.action=?';
        $args[] = $action;
    }
    if ($outcome !== '') {
        $where[] = 'a.outcome=?';
        $args[] = $outcome;
    }
    if ($actor) {
        $where[] = 'a.user_id=?';
        $args[] = $actor;
    }
    $total = (int)cas_value('SELECT COUNT(*) FROM admin_audit_log a WHERE ' . implode(' AND ', $where), $args);
    $logs = cas_rows('SELECT a.*, u.full_name, u.email FROM admin_audit_log a LEFT JOIN users u ON u.user_id=a.user_id WHERE ' . implode(' AND ', $where)
        . ' ORDER BY a.log_id DESC LIMIT 50 OFFSET ' . (($page - 1) * 50), $args);
    $actions = cas_rows('SELECT DISTINCT action FROM admin_audit_log ORDER BY action');
    $actors = cas_rows("SELECT DISTINCT u.user_id, u.full_name FROM admin_audit_log a JOIN users u ON u.user_id=a.user_id ORDER BY u.full_name");

    cas_ops_start('Chủ sở hữu', 'Quản lý cấp cao nhất: chủ sở hữu, quản trị viên và nhật ký mọi thao tác quản trị.', 'bi-shield-check');
    ?>
<section class="admin-grid-4 admin-section">
  <?php foreach ($kpi as $label => $value): ?><article class="admin-card"><span class="cas-muted"><?= cas_e($label) ?></span><h2 class="admin-card-title mb-0"><?= $value ?></h2></article><?php endforeach; ?>
</section>
<section class="admin-card admin-section">
  <h2 class="admin-card-title">Chủ sở hữu và quản trị viên <span class="cas-muted">(<?= $activeOwners ?> chủ sở hữu đang hoạt động)</span></h2>
  <p class="cas-muted">Mọi thay đổi ở đây cần nhập lại mật khẩu của bạn. Hệ thống luôn giữ ít nhất một chủ sở hữu đang hoạt động; tài khoản không bị xoá.</p>
  <div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Tài khoản</th><th>Vai trò</th><th>Trạng thái</th><th>Thao tác (cần mật khẩu)</th></tr></thead><tbody>
  <?php foreach ($leaders as $row):
      $ops = [];
      if ($row['role'] === 'admin' && $row['status'] === 'active') { $ops['promote'] = 'Nâng lên Chủ sở hữu'; }
      if ($row['role'] === 'owner') { $ops['demote'] = 'Chuyển về Quản trị viên'; }
      if ((int)$row['user_id'] !== (int)$user['user_id']) { $ops[$row['status'] === 'active' ? 'lock' : 'unlock'] = $row['status'] === 'active' ? 'Khoá' : 'Mở khoá'; }
      $lastOwner = $row['role'] === 'owner' && $row['status'] === 'active' && $activeOwners <= 1; ?>
    <tr><td><strong><?= cas_e($row['full_name']) ?></strong><?= (int)$row['user_id'] === (int)$user['user_id'] ? ' <span class="cas-muted">(bạn)</span>' : '' ?><div class="cas-muted"><?= cas_e($row['email']) ?></div></td>
      <td><?= cas_status_badge(CAS_ROLE_LABELS[$row['role']], $row['role'] === 'owner' ? 'warning' : 'success') ?></td>
      <td><?= cas_status_badge(CAS_ACCOUNT_STATUS[$row['status']] ?? $row['status'], $row['status'] === 'active' ? 'success' : 'danger') ?><?= $lastOwner ? '<div class="cas-muted">Chủ sở hữu cuối — được bảo vệ</div>' : '' ?></td>
      <td><?php if ($ops): ?><form method="post" class="cas-admin-table-form" data-confirm="Xác nhận thay đổi quyền / trạng thái của tài khoản này?"><?= cas_csrf_form() ?><input type="hidden" name="action" value="owner_change"><input type="hidden" name="id" value="<?= (int)$row['user_id'] ?>">
        <?= cas_select('op', $ops, (string)array_key_first($ops)) ?><input class="admin-input" type="password" name="confirm_password" placeholder="Mật khẩu của bạn" autocomplete="current-password" required><button class="admin-btn-light" type="submit">Thực hiện</button></form><?php else: ?><span class="cas-muted">—</span><?php endif; ?></td></tr>
  <?php endforeach; ?>
  </tbody></table></div>
</section>
<section class="admin-card admin-section">
  <h2 class="admin-card-title">Nhật ký quản trị (<?= $total ?> bản ghi)</h2>
  <form class="cas-admin-stack" method="get">
    <select class="admin-select" name="log_action"><option value="">Mọi thao tác</option><?php foreach ($actions as $a): ?><option <?= $a['action'] === $action ? 'selected' : '' ?>><?= cas_e($a['action']) ?></option><?php endforeach; ?></select>
    <?= cas_select('outcome', ['' => 'Mọi kết quả', 'ok' => 'Thành công', 'denied' => 'Bị chặn'], $outcome) ?>
    <select class="admin-select" name="actor"><option value="">Mọi người dùng</option><?php foreach ($actors as $a): ?><option value="<?= (int)$a['user_id'] ?>" <?= (int)$a['user_id'] === $actor ? 'selected' : '' ?>><?= cas_e($a['full_name']) ?></option><?php endforeach; ?></select>
    <button class="admin-btn" type="submit">Lọc</button><a class="admin-btn-light" href="chu-so-huu.php">Bỏ lọc</a>
  </form>
  <div class="admin-table-wrap mt-3"><table class="admin-table"><thead><tr><th>Thời gian</th><th>Người thao tác</th><th>Thao tác</th><th>Đối tượng</th><th>Kết quả</th><th>Chi tiết</th></tr></thead><tbody>
  <?php foreach ($logs as $log): ?><tr><td class="text-nowrap"><?= cas_e($log['created_at']) ?></td><td><?= cas_e($log['full_name'] ?? '—') ?><div class="cas-muted"><?= cas_e($log['ip'] ?? '') ?></div></td><td><?= cas_e($log['action']) ?></td>
    <td><?= cas_e($log['entity_type'] . ($log['entity_id'] !== null ? ' #' . $log['entity_id'] : '')) ?></td><td><?= cas_status_badge($log['outcome'] === 'ok' ? 'Thành công' : 'Bị chặn', $log['outcome'] === 'ok' ? 'success' : 'danger') ?></td>
    <td class="cas-muted text-break" style="max-width:340px"><?= cas_e(mb_strimwidth(cas_audit_detail_text($log['detail']), 0, 220, '…', 'UTF-8')) ?></td></tr><?php endforeach; ?>
  <?php if (!$logs): ?><tr><td colspan="6"><div class="admin-empty">Chưa có bản ghi phù hợp.</div></td></tr><?php endif; ?>
  </tbody></table></div>
  <?php if ($total > 50): ?><div class="cas-admin-stack mt-3"><?php for ($p = 1; $p <= (int)ceil($total / 50); $p++): ?><a class="<?= $p === $page ? 'admin-btn' : 'admin-btn-light' ?>" href="?<?= cas_e(http_build_query(array_filter(['log_action' => $action, 'outcome' => $outcome, 'actor' => $actor, 'page' => $p]))) ?>"><?= $p ?></a><?php endfor; ?></div><?php endif; ?>
</section>
<?php
    cas_ops_end();
}
