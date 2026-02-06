<?php
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/masters.php';
require_once __DIR__ . '/../src/tickets.php';
require_once __DIR__ . '/../src/users.php';

require_auth();
$conn = db_connect();
$options = fetch_filter_options($conn);
$message = '';
$user = current_user();
$childRole = child_role_for($user['role']);
$dashboardCounts = user_dashboard_counts($conn, $user);
$ticketCounts = fetch_ticket_status_counts($conn);
$canManageMasters = $user['role'] === ROLE_SUPER_ADMIN;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid request token.';
    } else {
        $action = $_POST['action'] ?? '';
        switch ($action) {
            case 'create_district':
            case 'create_local_body_type':
            case 'create_block_panchayat':
            case 'create_local_body':
            case 'create_job_station':
            case 'create_facilitation_center':
            case 'create_qualification_category':
            case 'create_academic_authority':
            case 'create_institution':
            case 'create_course':
            case 'create_cds':
            case 'create_ads':
            case 'create_sdpk_center':
                if ($canManageMasters) {
                    $message = handle_master_creation($conn, $action, $_POST, $message, $options);
                } else {
                    $message = 'You do not have permission to manage master data.';
                }
                break;
        }
    }
}

$options = fetch_filter_options($conn);

include __DIR__ . '/partials/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 mb-1">Dashboard</h1>
        <p class="text-muted mb-0">Manage users and data according to your access level.</p>
    </div>
    <div class="text-end">
        <span class="badge bg-light text-primary border"><?php echo htmlspecialchars(role_label($user['role'])); ?></span>
        <div class="small text-muted">Signed in as <?php echo htmlspecialchars($user['name'] ?? $user['mobile']); ?></div>
    </div>
</div>
<?php if ($message): ?>
    <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<?php if (!empty($dashboardCounts)): ?>
    <div class="row g-3 mb-3">
        <?php foreach ($dashboardCounts as $card): ?>
            <div class="col-md-4">
                <div class="card h-100 table-card">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge text-primary bg-light badge-outline"><?php echo htmlspecialchars($card['label']); ?></span>
                            <span class="fs-4 fw-bold text-primary"><?php echo $card['count']; ?></span>
                        </div>
                        <p class="text-muted small mb-0"><?php echo htmlspecialchars($card['description']); ?></p>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="alert alert-secondary">No subordinate users found for your level yet.</div>
<?php endif; ?>

<?php if ($user['role'] === ROLE_STATE_USER): ?>
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <a class="card h-100 table-card text-decoration-none" href="/tickets_manage.php">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="badge text-primary bg-light badge-outline">Tickets</span>
                        <span class="fs-4 fw-bold text-primary"><?php echo (int) $ticketCounts['total']; ?></span>
                    </div>
                    <p class="text-muted small mb-2">Total tickets across all districts.</p>
                    <div class="d-flex flex-wrap gap-2">
                        <span class="badge bg-warning-subtle text-warning">Pending: <?php echo (int) $ticketCounts['pending']; ?></span>
                        <span class="badge bg-success-subtle text-success">Resolved: <?php echo (int) $ticketCounts['resolved']; ?></span>
                    </div>
                </div>
            </a>
        </div>
        <?php if (in_array($user['team_role'] ?? '', ['verifier', 'approver'], true)): ?>
            <div class="col-md-4">
                <a class="card h-100 table-card text-decoration-none" href="/applicants.php">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge text-primary bg-light badge-outline">Applicants</span>
                        </div>
                        <p class="text-muted small mb-0">Review CRM status counts and bulk upload applicant records.</p>
                    </div>
                </a>
            </div>
            <div class="col-md-4">
                <a class="card h-100 table-card text-decoration-none" href="/crm.php">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge text-primary bg-light badge-outline">CRM</span>
                        </div>
                        <p class="text-muted small mb-0">Manage customer follow-ups, remarks, and call logs.</p>
                    </div>
                </a>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="card h-100 table-card">
            <div class="card-body d-flex flex-column">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="badge text-primary bg-light badge-outline">Job Fair</span>
                </div>
                <p class="text-muted small mb-3">Plan, capture, and track job fair strategy meetings and intend details.</p>
                <div class="d-flex flex-wrap gap-2 mt-auto">
                    <a class="btn btn-sm btn-outline-primary" href="/job_fair_daily_tasks.php">Strategy Meetings</a>
                    <a class="btn btn-sm btn-primary" href="/job_fair_intends.php">Job Fair Intend</a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-12">
        <div class="card table-card">
            <div class="card-body">
                <h2 class="h6 mb-2">Manage settings</h2>
                <p class="text-muted mb-3">Use the Settings menu to manage users and master data with dedicated list and action pages.</p>
                <div class="d-flex flex-wrap gap-2">
                    <a class="btn btn-outline-primary" href="/settings_users.php">
                        Manage <?php echo htmlspecialchars($childRole ? role_label($childRole) : 'users'); ?>
                    </a>
                    <?php if ($canManageMasters): ?>
                        <a class="btn btn-outline-secondary" href="/settings_masters.php">Manage master data</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>
<?php
?>
