<?php
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/applicants.php';

require_auth([ROLE_STATE_USER]);
$conn = db_connect();
$user = current_user();
$message = '';
$hasAccess = $user && in_array($user['team_role'] ?? '', ['verifier', 'approver'], true);

$purpose = trim($_GET['purpose'] ?? 'all');
$status = trim($_GET['status'] ?? 'all');
$allowedStatuses = applicant_crm_statuses();
if ($status !== 'all' && !in_array($status, $allowedStatuses, true)) {
    $status = 'all';
}

$records = $hasAccess ? fetch_applicant_list($conn, $purpose, $status) : [];

include __DIR__ . '/partials/header.php';
?>
<style>
    @media print {
        .no-print {
            display: none !important;
        }
        .table-card {
            box-shadow: none !important;
        }
        body {
            background: #fff;
        }
    }
</style>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4 no-print">
    <div>
        <h1 class="h4 mb-1">Applicant List</h1>
        <p class="text-muted mb-0">
            Purpose: <?php echo htmlspecialchars($purpose === 'all' ? 'All' : $purpose); ?> | Status: <?php echo htmlspecialchars($status === 'all' ? 'All' : $status); ?>
        </p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <button class="btn btn-outline-secondary" type="button" onclick="window.print()">Print</button>
        <a class="btn btn-outline-primary" href="/applicants.php">Back to Applicants</a>
    </div>
</div>

<?php if (!$hasAccess): ?>
    <div class="alert alert-secondary">You do not have access to this module.</div>
<?php elseif (empty($records)): ?>
    <div class="alert alert-secondary">No applicants found for the selected filters.</div>
<?php else: ?>
    <div class="card table-card">
        <div class="card-body">
            <h2 class="h6 mb-3">Applicants (<?php echo (int) count($records); ?>)</h2>
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 70px;">Sl No</th>
                            <th>Unique ID</th>
                            <th>Name</th>
                            <th>Mobile</th>
                            <th>Email</th>
                            <th>Purpose</th>
                            <th>Data Status</th>
                            <th>CRM Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($records as $index => $row): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo htmlspecialchars($row['unique_id']); ?></td>
                                <td><?php echo htmlspecialchars($row['name']); ?></td>
                                <td><?php echo htmlspecialchars($row['mobile']); ?></td>
                                <td><?php echo htmlspecialchars($row['email'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($row['purpose']); ?></td>
                                <td><?php echo htmlspecialchars($row['data_status']); ?></td>
                                <td><?php echo htmlspecialchars($row['crm_status']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/partials/footer.php'; ?>
