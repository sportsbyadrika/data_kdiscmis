<?php
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/masters.php';
require_once __DIR__ . '/../src/users.php';

require_auth();
$conn = db_connect();
$user = current_user();
$canManageMasters = $user && $user['role'] === ROLE_SUPER_ADMIN;
$options = fetch_filter_options($conn);
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid request token.';
    } elseif (!$canManageMasters) {
        $message = 'You do not have permission to manage master data.';
    } else {
        $action = $_POST['action'] ?? '';
        $message = handle_master_creation($conn, $action, $_POST, $message, $options);
    }
}

$definitions = master_definitions();
include __DIR__ . '/partials/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h4 mb-1">Master Data Settings</h1>
        <p class="text-muted mb-0">Search lists and add master data records from one place.</p>
    </div>
    <a class="btn btn-outline-secondary" href="/admin.php">Back to Dashboard</a>
</div>

<?php if ($message): ?>
    <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<?php if (!$canManageMasters): ?>
    <div class="alert alert-secondary">Master data creation is restricted to Super Admin users.</div>
<?php endif; ?>

<div class="card table-card mb-4">
    <div class="card-body">
        <h2 class="h6 mb-3">Master data lists</h2>
        <div class="row g-3">
            <?php foreach ($definitions as $key => $definition): ?>
                <div class="col-md-6 col-xl-4">
                    <div class="border rounded-3 p-3 h-100 d-flex flex-column">
                        <div>
                            <div class="text-muted small mb-1"><?php echo htmlspecialchars($definition['group']); ?></div>
                            <h3 class="h6 mb-2"><?php echo htmlspecialchars($definition['title']); ?></h3>
                            <p class="text-muted small mb-3">Open list &amp; search view for this master.</p>
                        </div>
                        <div class="mt-auto">
                            <a class="btn btn-sm btn-outline-primary w-100" href="/master.php?type=<?php echo urlencode($key); ?>">View list</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php if ($canManageMasters): ?>
    <div class="row g-3">
        <div class="col-lg-6">
            <?php include __DIR__ . '/partials/card_geography.php'; ?>
            <?php include __DIR__ . '/partials/card_local_body.php'; ?>
            <?php include __DIR__ . '/partials/card_jobs.php'; ?>
        </div>
        <div class="col-lg-6">
            <?php include __DIR__ . '/partials/card_sdpk.php'; ?>
            <?php include __DIR__ . '/partials/card_academics.php'; ?>
            <?php include __DIR__ . '/partials/card_cds_ads.php'; ?>
        </div>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/partials/footer.php'; ?>
