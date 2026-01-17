<?php
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/applicants.php';

require_auth([ROLE_STATE_USER]);
$conn = db_connect();
$user = current_user();
$message = '';
$hasAccess = $user && in_array($user['team_role'] ?? '', ['verifier', 'approver'], true);

if (!$hasAccess) {
    $message = 'Only verifier or approver users can access applicant analytics.';
}

$pivot = $hasAccess ? fetch_applicant_pivot($conn) : ['rows' => [], 'totals' => []];

function applicant_list_link(string $purpose, string $status): string
{
    return '/applicants_list.php?' . http_build_query([
        'purpose' => $purpose,
        'status' => $status,
    ]);
}

include __DIR__ . '/partials/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h4 mb-1">Applicants</h1>
        <p class="text-muted mb-0">Review applicant CRM status counts and drill into printable lists.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-outline-secondary" href="/admin.php">Back to Dashboard</a>
        <?php if ($hasAccess): ?>
            <a class="btn btn-outline-primary" href="/applicants_bulk_upload.php">Bulk Upload</a>
            <a class="btn btn-outline-success" href="/crm.php">CRM Workspace</a>
        <?php endif; ?>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<?php if (!$hasAccess): ?>
    <div class="alert alert-secondary">You do not have access to this module.</div>
<?php elseif (empty($pivot['rows'])): ?>
    <div class="alert alert-secondary">No applicant records found yet.</div>
<?php else: ?>
    <div class="card table-card">
        <div class="card-body">
            <h2 class="h6 mb-3">Applicants by purpose &amp; CRM status</h2>
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 70px;">Sl No</th>
                            <th>Purpose</th>
                            <th class="text-center">CRM Pending</th>
                            <th class="text-center">CRM Completed</th>
                            <th class="text-center">CRM Postponed</th>
                            <th class="text-center">CRM Cancelled</th>
                            <th class="text-center">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pivot['rows'] as $index => $row): ?>
                            <?php $purpose = (string) $row['purpose']; ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo htmlspecialchars($purpose); ?></td>
                                <td class="text-center">
                                    <a target="_blank" rel="noopener" href="<?php echo htmlspecialchars(applicant_list_link($purpose, 'CRM Pending')); ?>">
                                        <?php echo (int) $row['crm_pending']; ?>
                                    </a>
                                </td>
                                <td class="text-center">
                                    <a target="_blank" rel="noopener" href="<?php echo htmlspecialchars(applicant_list_link($purpose, 'CRM Completed')); ?>">
                                        <?php echo (int) $row['crm_completed']; ?>
                                    </a>
                                </td>
                                <td class="text-center">
                                    <a target="_blank" rel="noopener" href="<?php echo htmlspecialchars(applicant_list_link($purpose, 'CRM Postponed')); ?>">
                                        <?php echo (int) $row['crm_postponed']; ?>
                                    </a>
                                </td>
                                <td class="text-center">
                                    <a target="_blank" rel="noopener" href="<?php echo htmlspecialchars(applicant_list_link($purpose, 'CRM Cancelled')); ?>">
                                        <?php echo (int) $row['crm_cancelled']; ?>
                                    </a>
                                </td>
                                <td class="text-center">
                                    <a target="_blank" rel="noopener" href="<?php echo htmlspecialchars(applicant_list_link($purpose, 'all')); ?>">
                                        <?php echo (int) $row['total']; ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="table-secondary fw-semibold">
                            <td colspan="2">Total</td>
                            <td class="text-center">
                                <a target="_blank" rel="noopener" href="<?php echo htmlspecialchars(applicant_list_link('all', 'CRM Pending')); ?>">
                                    <?php echo (int) ($pivot['totals']['crm_pending'] ?? 0); ?>
                                </a>
                            </td>
                            <td class="text-center">
                                <a target="_blank" rel="noopener" href="<?php echo htmlspecialchars(applicant_list_link('all', 'CRM Completed')); ?>">
                                    <?php echo (int) ($pivot['totals']['crm_completed'] ?? 0); ?>
                                </a>
                            </td>
                            <td class="text-center">
                                <a target="_blank" rel="noopener" href="<?php echo htmlspecialchars(applicant_list_link('all', 'CRM Postponed')); ?>">
                                    <?php echo (int) ($pivot['totals']['crm_postponed'] ?? 0); ?>
                                </a>
                            </td>
                            <td class="text-center">
                                <a target="_blank" rel="noopener" href="<?php echo htmlspecialchars(applicant_list_link('all', 'CRM Cancelled')); ?>">
                                    <?php echo (int) ($pivot['totals']['crm_cancelled'] ?? 0); ?>
                                </a>
                            </td>
                            <td class="text-center">
                                <a target="_blank" rel="noopener" href="<?php echo htmlspecialchars(applicant_list_link('all', 'all')); ?>">
                                    <?php echo (int) ($pivot['totals']['total'] ?? 0); ?>
                                </a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p class="text-muted small mb-0">Click any count to open a printable list in a new tab.</p>
        </div>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/partials/footer.php'; ?>
