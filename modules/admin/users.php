<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once '../../config/db.php';
require_once '../../config/auth_guard.php';
require_once '../../config/lang.php';
require_once '../../config/admin_helpers.php';

admin_only();

$searchQuery = trim($_GET['q'] ?? '');
$roleFilter = $_GET['role'] ?? 'all';
$marketFilter = $_GET['market_id'] ?? '';
$activeFilter = $_GET['is_active'] ?? 'all';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;

$where = "WHERE 1=1";
$params = [];

if ($searchQuery) {
    $where .= " AND (u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
    $search = "%$searchQuery%";
    array_push($params, $search, $search, $search);
}

if ($roleFilter && $roleFilter !== 'all') {
    $where .= " AND u.role = ?";
    $params[] = $roleFilter;
}

if ($marketFilter) {
    $where .= " AND u.market_id = ?";
    $params[] = (int)$marketFilter;
}

if ($activeFilter !== 'all') {
    $where .= " AND u.is_active = ?";
    $params[] = $activeFilter === '1' ? 1 : 0;
}

try {
    $countStmt = $pdo->prepare("SELECT COUNT(*) as total FROM users u $where");
    $countStmt->execute($params);
    $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    $pages = max(1, ceil($total / $perPage));
    
    if ($page > $pages) $page = $pages;
    
    $offset = ($page - 1) * $perPage;
    $paramsCopy = $params;
    $paramsCopy[] = $perPage;
    $paramsCopy[] = $offset;
    
    $listStmt = $pdo->prepare("
        SELECT 
            u.id, u.name, u.email, u.phone, u.role, u.market_id, u.stall_no,
            u.is_active, u.last_login_at, u.created_at,
            m.name as market_name
        FROM users u
        LEFT JOIN markets m ON m.id = u.market_id
        $where
        ORDER BY u.created_at DESC
        LIMIT ? OFFSET ?
    ");
    
    foreach ($params as $i => $param) {
        $listStmt->bindValue($i + 1, $param);
    }
    $listStmt->bindValue(count($params) + 1, $perPage, PDO::PARAM_INT);
    $listStmt->bindValue(count($params) + 2, $offset, PDO::PARAM_INT);
    
    $listStmt->execute();
    $users = $listStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $marketsStmt = $pdo->prepare("SELECT id, name FROM markets ORDER BY name");
    $marketsStmt->execute();
    $markets = $marketsStmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Users list error: " . $e->getMessage());
    $users = [];
    $total = 0;
    $pages = 1;
}

if (isset($_GET['export']) && $_GET['export'] === '1') {
    header('Content-Type: text/csv; charset=utf-8-sig');
    header('Content-Disposition: attachment; filename="users_export_' . date('Y-m-d-H-i-s') . '.csv"');
    
    $f = fopen('php://output', 'w');
    fputcsv($f, ['Name', 'Email', 'Phone', 'Role', 'Market', 'Status', 'Last Login', 'Joined']);
    
    foreach ($users as $user) {
        fputcsv($f, [
            $user['name'],
            $user['email'],
            $user['phone'],
            $user['role'],
            $user['market_name'] ?? '-',
            $user['is_active'] ? 'Active' : 'Inactive',
            $user['last_login_at'] ?? '-',
            $user['created_at']
        ]);
    }
    fclose($f);
    exit;
}

require_once '../../templates/header.php';
?>

<div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-6 py-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-3xl font-bold text-gray-900">User Management</h2>
                <p class="text-gray-600 text-sm mt-1">Manage sellers, managers, and admins</p>
            </div>
            <a href="<?= BASE_URL ?>/modules/admin/user_create.php" class="btn-primary">➕ Create User</a>
        </div>
        
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
            <form method="GET" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <input type="text" name="q" value="<?= htmlspecialchars($searchQuery) ?>" placeholder="Search name, email, phone..." class="input-field">
                    
                    <select name="role" class="input-field">
                        <option value="all">All Roles</option>
                        <option value="seller" <?= $roleFilter === 'seller' ? 'selected' : '' ?>>Seller</option>
                        <option value="manager" <?= $roleFilter === 'manager' ? 'selected' : '' ?>>Manager</option>
                        <option value="admin" <?= $roleFilter === 'admin' ? 'selected' : '' ?>>Admin</option>
                    </select>
                    
                    <select name="market_id" class="input-field">
                        <option value="">All Markets</option>
                        <?php foreach ($markets as $m): ?>
                            <option value="<?= $m['id'] ?>" <?= $marketFilter == $m['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($m['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <select name="is_active" class="input-field">
                        <option value="all">All Statuses</option>
                        <option value="1" <?= $activeFilter === '1' ? 'selected' : '' ?>>Active</option>
                        <option value="0" <?= $activeFilter === '0' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                    
                    <div class="flex gap-2">
                        <button type="submit" class="btn-primary flex-1">🔍 Search</button>
                        <a href="<?= BASE_URL ?>/modules/admin/users.php?export=1" class="btn-secondary flex-1 text-center">📥 CSV</a>
                    </div>
                </div>
            </form>
        </div>
        
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="text-left py-3 px-4 font-semibold text-gray-700">Name & Email</th>
                            <th class="text-left py-3 px-4 font-semibold text-gray-700">Phone</th>
                            <th class="text-center py-3 px-4 font-semibold text-gray-700">Role</th>
                            <th class="text-left py-3 px-4 font-semibold text-gray-700">Market</th>
                            <th class="text-center py-3 px-4 font-semibold text-gray-700">Status</th>
                            <th class="text-left py-3 px-4 font-semibold text-gray-700">Last Login</th>
                            <th class="text-center py-3 px-4 font-semibold text-gray-700">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr><td colspan="7" class="py-6 text-center text-gray-500">No users found</td></tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                            <tr class="border-b border-gray-100 hover:bg-gray-50">
                                <td class="py-3 px-4">
                                    <div>
                                        <p class="font-medium text-gray-900"><?= htmlspecialchars($user['name']) ?></p>
                                        <p class="text-xs text-gray-500"><?= htmlspecialchars($user['email']) ?></p>
                                    </div>
                                </td>
                                <td class="py-3 px-4 text-gray-600"><?= htmlspecialchars($user['phone']) ?></td>
                                <td class="py-3 px-4 text-center">
                                    <span class="px-2.5 py-1 rounded-full text-xs font-bold <?php
                                        switch ($user['role']) {
                                            case 'admin': echo 'bg-purple-100 text-purple-700'; break;
                                            case 'manager': echo 'bg-blue-100 text-blue-700'; break;
                                            default: echo 'bg-gray-100 text-gray-700'; break;
                                        }
                                    ?>">
                                        <?= htmlspecialchars(ucfirst($user['role'])) ?>
                                    </span>
                                </td>
                                <td class="py-3 px-4">
                                    <p class="text-gray-600"><?= htmlspecialchars($user['market_name'] ?? '-') ?></p>
                                    <?php if ($user['stall_no']): ?>
                                        <p class="text-xs text-gray-500">Stall: <?= htmlspecialchars($user['stall_no']) ?></p>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span class="px-2.5 py-1 rounded-full text-xs font-bold <?= $user['is_active'] ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                                        <?= $user['is_active'] ? '✅ Active' : '🚫 Inactive' ?>
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-sm text-gray-600">
                                    <?php
                                    if ($user['last_login_at']) {
                                        $diff = time() - strtotime($user['last_login_at']);
                                        if ($diff < 3600) echo round($diff / 60) . 'm ago';
                                        elseif ($diff < 86400) echo round($diff / 3600) . 'h ago';
                                        else echo round($diff / 86400) . 'd ago';
                                    } else {
                                        echo '<span class="text-gray-400">Never</span>';
                                    }
                                    ?>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <div class="flex gap-2 justify-center">
                                        <a href="<?= BASE_URL ?>/modules/admin/user_edit.php?id=<?= $user['id'] ?>" class="px-2.5 py-1 rounded text-xs bg-blue-100 text-blue-600 hover:bg-blue-200 font-medium">✏️</a>
                                        <button type="button" onclick="toggleUserStatus(<?= $user['id'] ?>, <?= $user['is_active'] ? 0 : 1 ?>)" class="px-2.5 py-1 rounded text-xs font-medium <?= $user['is_active'] ? 'bg-red-100 text-red-600 hover:bg-red-200' : 'bg-green-100 text-green-600 hover:bg-green-200' ?>">
                                            <?= $user['is_active'] ? '🔒' : '🔓' ?>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($pages > 1): ?>
            <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
                <p class="text-sm text-gray-600">Showing <?= (($page - 1) * $perPage) + 1 ?> to <?= min($page * $perPage, $total) ?> of <?= $total ?></p>
                <div class="flex gap-2">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>&q=<?= urlencode($searchQuery) ?>&role=<?= $roleFilter ?>" class="px-3 py-2 border border-gray-300 rounded text-sm font-medium text-gray-700 hover:bg-gray-50">← Previous</a>
                    <?php endif; ?>
                    <span class="px-3 py-2 text-sm font-medium text-gray-700">Page <?= $page ?> of <?= $pages ?></span>
                    <?php if ($page < $pages): ?>
                        <a href="?page=<?= $page + 1 ?>&q=<?= urlencode($searchQuery) ?>&role=<?= $roleFilter ?>" class="px-3 py-2 border border-gray-300 rounded text-sm font-medium text-gray-700 hover:bg-gray-50">Next →</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function toggleUserStatus(userId, newStatus) {
    if (!confirm('Are you sure?')) return;
    if (newStatus === 0 && userId === <?= $_SESSION['user_id'] ?>) {
        alert('You cannot deactivate your own account');
        return;
    }
    
    fetch('<?= BASE_URL ?>/modules/admin/user_toggle.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
        body: 'user_id=' + userId + '&action=' + (newStatus ? 'activate' : 'deactivate') + '&csrf_token=<?= csrf_token() ?>'
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) location.reload();
        else alert('Error: ' + d.error);
    })
    .catch(e => alert('Network error: ' + e.message));
}
</script>

<?php require_once '../../templates/footer.php'; ?>
