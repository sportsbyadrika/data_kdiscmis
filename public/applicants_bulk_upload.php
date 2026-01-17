<?php
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/applicants.php';

require_auth([ROLE_STATE_USER]);
$conn = db_connect();
$user = current_user();
$message = '';
$status = null;

if (!$user || !in_array($user['team_role'] ?? '', ['verifier', 'approver'], true)) {
    $message = 'Only verifier or approver users can access bulk uploads.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid request token.';
    } elseif (!isset($_FILES['upload_file']) || $_FILES['upload_file']['error'] !== UPLOAD_ERR_OK) {
        $message = 'Please upload a valid CSV file.';
    } else {
        $result = upsert_applicants_from_csv($conn, $_FILES['upload_file']['tmp_name']);
        if (isset($result['error'])) {
            $message = $result['error'];
        } else {
            $status = $result;
        }
    }
}

include __DIR__ . '/partials/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h4 mb-1">Bulk Upload Applicants</h1>
        <p class="text-muted mb-0">Upload applicant details with insert/update support on Unique ID and CRM status.</p>
    </div>
    <a class="btn btn-outline-secondary" href="/admin.php">Back to Dashboard</a>
</div>

<?php if ($message): ?>
    <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<?php if ($status): ?>
    <div class="alert alert-success">
        Uploaded records: <?php echo (int) $status['inserted']; ?> inserted,
        <?php echo (int) $status['updated']; ?> updated,
        <?php echo (int) $status['skipped']; ?> skipped.
    </div>
<?php endif; ?>

<?php if (!$user || !in_array($user['team_role'] ?? '', ['verifier', 'approver'], true)): ?>
    <div class="alert alert-secondary">You do not have access to this module.</div>
<?php else: ?>
    <div class="card table-card">
        <div class="card-body">
            <h2 class="h6 mb-3">Upload CSV</h2>
            <form method="post" enctype="multipart/form-data" class="row g-3">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                <div class="col-12">
                    <label class="form-label">CSV file</label>
                    <input type="file" name="upload_file" class="form-control" accept=".csv" required>
                </div>
                <div class="col-12">
                    <div class="alert alert-light border">
                        <strong>Expected columns</strong>
                        <div class="small text-muted mt-1">
                            <?php echo htmlspecialchars(implode(', ', applicants_template_headers())); ?>
                        </div>
                    </div>
                </div>
                <div class="col-12 d-flex justify-content-end">
                    <button class="btn btn-primary" type="submit">Upload Applicants</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/partials/footer.php'; ?>
