<?php
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/applicants.php';

require_auth([ROLE_STATE_USER]);
$conn = db_connect();
$user = current_user();
$message = '';
$hasAccess = $user && in_array($user['team_role'] ?? '', ['verifier', 'approver'], true);

$purposeFilter = trim($_GET['purpose'] ?? 'all');
$statusFilter = trim($_GET['status'] ?? 'all');
$selectedId = (int) ($_GET['id'] ?? 0);
$allowedStatuses = applicant_crm_statuses();
if ($statusFilter !== 'all' && !in_array($statusFilter, $allowedStatuses, true)) {
    $statusFilter = 'all';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $hasAccess) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid request token.';
    } else {
        $action = $_POST['action'] ?? '';
        $applicantId = (int) ($_POST['applicant_id'] ?? 0);
        switch ($action) {
            case 'update_crm':
                $crmStatus = (string) ($_POST['crm_status'] ?? '');
                $crmRemarks = trim((string) ($_POST['crm_remarks'] ?? ''));
                if ($applicantId <= 0) {
                    $message = 'Select a valid applicant.';
                } elseif (update_applicant_crm($conn, $applicantId, $crmStatus, $crmRemarks)) {
                    $message = 'CRM details updated successfully.';
                } else {
                    $message = 'Unable to update CRM details.';
                }
                break;
            case 'add_call':
                $callDate = trim((string) ($_POST['call_date'] ?? ''));
                $duration = trim((string) ($_POST['duration'] ?? ''));
                $callStatus = trim((string) ($_POST['call_status'] ?? ''));
                $callRemarks = trim((string) ($_POST['call_remarks'] ?? ''));
                $contactedBy = trim((string) ($_POST['contacted_by'] ?? ''));
                if ($applicantId <= 0) {
                    $message = 'Select a valid applicant.';
                } elseif ($callDate === '') {
                    $message = 'Call date is required.';
                } elseif (create_applicant_call($conn, $applicantId, $callDate, $duration, $callStatus, $callRemarks, $contactedBy)) {
                    $message = 'Call record added successfully.';
                } else {
                    $message = 'Unable to add call record.';
                }
                break;
        }
    }

    if ($applicantId > 0) {
        $selectedId = $applicantId;
    }
}

$purposes = fetch_applicant_purposes($conn);
$applicants = $hasAccess ? fetch_applicants_for_crm($conn, $purposeFilter, $statusFilter) : [];
if ($selectedId === 0 && !empty($applicants)) {
    $selectedId = (int) $applicants[0]['id'];
}

$selectedApplicant = $selectedId > 0 ? fetch_applicant_details($conn, $selectedId) : null;
$callHistory = $selectedApplicant ? fetch_applicant_calls($conn, $selectedId) : [];

include __DIR__ . '/partials/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h4 mb-1">CRM Workspace</h1>
        <p class="text-muted mb-0">Manage customer follow-ups, statuses, and call history.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-outline-secondary" href="/admin.php">Back to Dashboard</a>
        <?php if ($hasAccess): ?>
            <a class="btn btn-outline-primary" href="/applicants.php">Applicants Overview</a>
        <?php endif; ?>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<?php if (!$hasAccess): ?>
    <div class="alert alert-secondary">You do not have access to this module.</div>
<?php else: ?>
    <div class="card table-card mb-4">
        <div class="card-body">
            <form method="get" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Purpose</label>
                    <select name="purpose" class="form-select">
                        <option value="all">All purposes</option>
                        <?php foreach ($purposes as $purpose): ?>
                            <option value="<?php echo htmlspecialchars($purpose); ?>" <?php echo $purposeFilter === $purpose ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($purpose); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">CRM Status</label>
                    <select name="status" class="form-select">
                        <option value="all">All statuses</option>
                        <?php foreach ($allowedStatuses as $status): ?>
                            <option value="<?php echo htmlspecialchars($status); ?>" <?php echo $statusFilter === $status ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($status); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <button class="btn btn-primary" type="submit">Apply Filters</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card table-card h-100">
                <div class="card-body">
                    <h2 class="h6 mb-3">Customers</h2>
                    <?php if (empty($applicants)): ?>
                        <div class="alert alert-secondary">No customers match the selected filters.</div>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($applicants as $applicant): ?>
                                <?php
                                    $itemLink = '/crm.php?' . http_build_query([
                                        'purpose' => $purposeFilter,
                                        'status' => $statusFilter,
                                        'id' => $applicant['id'],
                                    ]);
                                    $activeClass = $selectedApplicant && (int) $applicant['id'] === (int) $selectedApplicant['id'] ? 'active' : '';
                                ?>
                                <a class="list-group-item list-group-item-action <?php echo $activeClass; ?>" href="<?php echo htmlspecialchars($itemLink); ?>">
                                    <div class="d-flex justify-content-between">
                                        <strong><?php echo htmlspecialchars($applicant['name']); ?></strong>
                                        <span class="badge bg-light text-primary border"><?php echo htmlspecialchars($applicant['crm_status']); ?></span>
                                    </div>
                                    <div class="small text-muted"><?php echo htmlspecialchars($applicant['purpose']); ?></div>
                                    <div class="small"><?php echo htmlspecialchars($applicant['mobile']); ?></div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card table-card h-100">
                <div class="card-body">
                    <?php if (!$selectedApplicant): ?>
                        <div class="alert alert-secondary">Select a customer to view CRM details.</div>
                    <?php else: ?>
                        <h2 class="h6 mb-3">Customer Details</h2>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <div class="small text-muted">Name</div>
                                <div class="fw-semibold"><?php echo htmlspecialchars($selectedApplicant['name']); ?></div>
                            </div>
                            <div class="col-md-6">
                                <div class="small text-muted">Unique ID</div>
                                <div class="fw-semibold"><?php echo htmlspecialchars($selectedApplicant['unique_id']); ?></div>
                            </div>
                            <div class="col-md-6">
                                <div class="small text-muted">Mobile</div>
                                <div class="fw-semibold"><?php echo htmlspecialchars($selectedApplicant['mobile']); ?></div>
                            </div>
                            <div class="col-md-6">
                                <div class="small text-muted">Email</div>
                                <div class="fw-semibold"><?php echo htmlspecialchars($selectedApplicant['email'] ?? ''); ?></div>
                            </div>
                            <div class="col-md-6">
                                <div class="small text-muted">Purpose</div>
                                <div class="fw-semibold"><?php echo htmlspecialchars($selectedApplicant['purpose']); ?></div>
                            </div>
                            <div class="col-md-6">
                                <div class="small text-muted">Data Status</div>
                                <div class="fw-semibold"><?php echo htmlspecialchars($selectedApplicant['data_status']); ?></div>
                            </div>
                        </div>

                        <div class="row g-4">
                            <div class="col-12">
                                <h3 class="h6">Update CRM Status &amp; Remarks</h3>
                                <form method="post" class="row g-3">
                                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                    <input type="hidden" name="action" value="update_crm">
                                    <input type="hidden" name="applicant_id" value="<?php echo (int) $selectedApplicant['id']; ?>">
                                    <div class="col-md-6">
                                        <label class="form-label">CRM Status</label>
                                        <select name="crm_status" class="form-select" required>
                                            <?php foreach ($allowedStatuses as $status): ?>
                                                <option value="<?php echo htmlspecialchars($status); ?>" <?php echo $selectedApplicant['crm_status'] === $status ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($status); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label">Remarks</label>
                                        <textarea name="crm_remarks" class="form-control" rows="3"><?php echo htmlspecialchars($selectedApplicant['crm_remarks'] ?? ''); ?></textarea>
                                    </div>
                                    <div class="col-12">
                                        <button class="btn btn-primary" type="submit">Save CRM Details</button>
                                    </div>
                                </form>
                            </div>

                            <div class="col-12">
                                <h3 class="h6">Add Call Record</h3>
                                <form method="post" class="row g-3">
                                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                    <input type="hidden" name="action" value="add_call">
                                    <input type="hidden" name="applicant_id" value="<?php echo (int) $selectedApplicant['id']; ?>">
                                    <div class="col-md-4">
                                        <label class="form-label">Date of call</label>
                                        <input type="date" name="call_date" class="form-control" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Duration</label>
                                        <input type="text" name="duration" class="form-control" placeholder="e.g., 15 min">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Status</label>
                                        <input type="text" name="call_status" class="form-control" placeholder="e.g., Connected">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Contacted by</label>
                                        <input type="text" name="contacted_by" class="form-control" placeholder="Team member name">
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label">Remarks</label>
                                        <textarea name="call_remarks" class="form-control" rows="3" placeholder="Notes from the call"></textarea>
                                    </div>
                                    <div class="col-12">
                                        <button class="btn btn-outline-primary" type="submit">Add Call Record</button>
                                    </div>
                                </form>
                            </div>

                            <div class="col-12">
                                <h3 class="h6">Call History</h3>
                                <?php if (empty($callHistory)): ?>
                                    <div class="alert alert-secondary">No call history recorded.</div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-striped align-middle">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Duration</th>
                                                    <th>Status</th>
                                                    <th>Contacted by</th>
                                                    <th>Remarks</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($callHistory as $call): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($call['call_date']); ?></td>
                                                        <td><?php echo htmlspecialchars($call['duration'] ?? ''); ?></td>
                                                        <td><?php echo htmlspecialchars($call['status'] ?? ''); ?></td>
                                                        <td><?php echo htmlspecialchars($call['contacted_by'] ?? ''); ?></td>
                                                        <td><?php echo htmlspecialchars($call['remarks'] ?? ''); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/partials/footer.php'; ?>
