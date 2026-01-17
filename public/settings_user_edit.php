<?php
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/masters.php';
require_once __DIR__ . '/../src/users.php';

require_auth();
$conn = db_connect();
$user = current_user();
$childRole = $user ? child_role_for($user['role']) : null;
$options = fetch_filter_options($conn);
$teams = fetch_teams($conn);
$teamRoles = team_roles();
$functionalityOptions = available_functionalities();
$message = '';

$userId = (int) ($_GET['id'] ?? 0);
$subUser = $user ? fetch_subordinate_user($conn, $user, $userId) : null;
$selectedFunctionalities = $subUser ? fetch_user_functionalities($conn, (int) $subUser['id']) : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid request token.';
    } elseif (!$user || !$childRole) {
        $message = 'You do not have permission to update users.';
    } else {
        $action = $_POST['action'] ?? '';
        if ($action === 'update_user') {
            $message = update_subordinate_user($conn, $user, $userId, $_POST);
            $subUser = fetch_subordinate_user($conn, $user, $userId);
            $selectedFunctionalities = $subUser ? fetch_user_functionalities($conn, (int) $subUser['id']) : [];
        }
    }
}

include __DIR__ . '/partials/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h4 mb-1">Edit User</h1>
        <p class="text-muted mb-0">Update <?php echo htmlspecialchars($childRole ? strtolower(role_label($childRole)) : 'user'); ?> access and profile details.</p>
    </div>
    <a class="btn btn-outline-secondary" href="/settings_users.php">Back to User Management</a>
</div>

<?php if ($message): ?>
    <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<?php if (!$subUser || !$childRole): ?>
    <div class="alert alert-secondary">The selected user could not be found.</div>
<?php else: ?>
    <?php $requiresDistrict = in_array($childRole, [ROLE_DISTRICT_USER, ROLE_LOCALBODY_USER], true); ?>
    <div class="card table-card">
        <div class="card-body">
            <h2 class="h6 mb-3">Edit <?php echo htmlspecialchars(role_label($childRole)); ?></h2>
            <form method="post" class="row g-3">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                <input type="hidden" name="action" value="update_user">
                <div class="col-12">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($subUser['name']); ?>" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($subUser['email']); ?>" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Mobile number (username)</label>
                    <input type="text" name="mobile" class="form-control" value="<?php echo htmlspecialchars($subUser['mobile']); ?>" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Password (leave blank to keep current)</label>
                    <input type="password" name="password" class="form-control">
                </div>
                <div class="col-12">
                    <label class="form-label">Team</label>
                    <select name="team_id" class="form-select" required>
                        <option value="">Select team</option>
                        <?php foreach ($teams as $team): ?>
                            <option value="<?php echo (int) $team['id']; ?>" <?php echo (int) $subUser['team_id'] === (int) $team['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($team['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Team role</label>
                    <select name="team_role" class="form-select" required>
                        <option value="">Select role</option>
                        <?php foreach ($teamRoles as $roleValue => $label): ?>
                            <option value="<?php echo htmlspecialchars($roleValue); ?>" <?php echo $subUser['team_role'] === $roleValue ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if ($requiresDistrict): ?>
                    <div class="col-12">
                        <label class="form-label">District</label>
                        <select name="district_id" class="form-select" <?php echo $childRole === ROLE_LOCALBODY_USER && $user['district_id'] ? 'disabled' : ''; ?> required>
                            <option value="">Select district</option>
                            <?php foreach ($options['districts'] as $district): ?>
                                <option value="<?php echo (int) $district['id']; ?>" <?php echo (int) $subUser['district_id'] === (int) $district['id'] ? 'selected' : ''; ?>>
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
                        <option value="active" <?php echo $subUser['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $subUser['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Accessible functionalities</label>
                    <div class="border rounded-3 p-2">
                        <?php foreach ($functionalityOptions as $functionality): ?>
                            <?php $checked = in_array($functionality, $selectedFunctionalities, true); ?>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="functionalities[]" value="<?php echo htmlspecialchars($functionality); ?>" id="edit-functionality-<?php echo md5($functionality); ?>" <?php echo $checked ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="edit-functionality-<?php echo md5($functionality); ?>">
                                    <?php echo htmlspecialchars($functionality); ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <small class="text-muted">Select at least one.</small>
                </div>
                <div class="col-12 d-flex justify-content-end">
                    <button class="btn btn-primary" type="submit">Update <?php echo htmlspecialchars(strtolower(role_label($childRole))); ?></button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/partials/footer.php'; ?>
