<?php
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/masters.php';
require_once __DIR__ . '/../src/users.php';

require_auth();
$conn = db_connect();
$options = fetch_filter_options($conn);
$user = current_user();
$childRole = $user ? child_role_for($user['role']) : null;
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid request token.';
    } else {
        $action = $_POST['action'] ?? '';
        if ($action === 'create_user') {
            $message = create_subordinate_user($conn, $user, $_POST);
        }
    }
}

$search = trim($_GET['search'] ?? '');
$subUsers = $childRole ? fetch_subordinate_users($conn, $user, $search) : [];

include __DIR__ . '/partials/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h4 mb-1">User Management</h1>
        <p class="text-muted mb-0">Create and review <?php echo htmlspecialchars($childRole ? strtolower(role_label($childRole)) : 'subordinate'); ?> accounts.</p>
    </div>
    <a class="btn btn-outline-secondary" href="/admin.php">Back to Dashboard</a>
</div>

<?php if ($message): ?>
    <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<?php if (!$childRole): ?>
    <div class="alert alert-secondary">There are no subordinate roles available for your access level.</div>
<?php else: ?>
    <div class="row g-3 mb-4">
        <div class="col-lg-5">
            <div class="card table-card">
                <div class="card-body">
                    <h2 class="h6 mb-3">Create <?php echo htmlspecialchars(role_label($childRole)); ?></h2>
                    <?php $requiresDistrict = in_array($childRole, [ROLE_DISTRICT_USER, ROLE_LOCALBODY_USER], true); ?>
                    <form method="post" class="row g-3">
                        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                        <input type="hidden" name="action" value="create_user">
                        <div class="col-12">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Mobile number (username)</label>
                            <input type="text" name="mobile" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <?php if ($requiresDistrict): ?>
                            <div class="col-12">
                                <label class="form-label">District</label>
                                <select name="district_id" class="form-select" <?php echo $childRole === ROLE_LOCALBODY_USER && $user['district_id'] ? 'disabled' : ''; ?> required>
                                    <option value="">Select district</option>
                                    <?php foreach ($options['districts'] as $district): ?>
                                        <option value="<?php echo (int) $district['id']; ?>" <?php echo $user['district_id'] === (int) $district['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($district['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if ($childRole === ROLE_LOCALBODY_USER && $user['district_id']): ?>
                                    <small class="text-muted">Localbody users will be tied to your district.</small>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        <div class="col-12">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="col-12 d-flex justify-content-end">
                            <button class="btn btn-primary" type="submit">Create <?php echo htmlspecialchars(strtolower(role_label($childRole))); ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card table-card">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                        <div>
                            <h2 class="h6 mb-1"><?php echo htmlspecialchars(role_label($childRole)); ?> List</h2>
                            <p class="text-muted small mb-0">Search by name, mobile number, or email.</p>
                        </div>
                        <form class="d-flex gap-2" method="get">
                            <input type="search" class="form-control form-control-sm" name="search" placeholder="Search" value="<?php echo htmlspecialchars($search); ?>">
                            <button class="btn btn-sm btn-outline-primary" type="submit">Search</button>
                        </form>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col">Name</th>
                                    <th scope="col">Mobile</th>
                                    <th scope="col">Email</th>
                                    <th scope="col">District</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($subUsers as $subUser): ?>
                                    <tr>
                                        <td class="fw-semibold"><?php echo htmlspecialchars($subUser['name']); ?></td>
                                        <td><?php echo htmlspecialchars($subUser['mobile']); ?></td>
                                        <td><?php echo htmlspecialchars($subUser['email']); ?></td>
                                        <td><?php echo htmlspecialchars($subUser['district_name'] ?? ''); ?></td>
                                        <td>
                                            <?php if ($subUser['status'] === 'active'): ?>
                                                <span class="badge bg-success-subtle text-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary-subtle text-secondary">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-muted small"><?php echo $subUser['created_at'] ? date('d M Y', strtotime($subUser['created_at'])) : '-'; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($subUsers)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4">No users found for this level.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/partials/footer.php'; ?>
