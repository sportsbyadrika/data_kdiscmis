<?php
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/job_fair_intends.php';

require_auth();
$conn = db_connect();

$locationTypes = [
    ['key' => 'usual_sdpk', 'label' => 'Usual SDPK'],
    ['key' => 'additional', 'label' => 'Additional'],
];
$allowedLocationTypes = array_column($locationTypes, 'label');

$errors = [];
$message = '';
$intendId = isset($_GET['intend_id']) ? (int) $_GET['intend_id'] : 0;
$intend = $intendId ? fetch_job_fair_intend($conn, $intendId) : null;

if ($intendId && !$intend) {
    $errors[] = 'Unable to find the selected job fair intend.';
    $intendId = 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request token.';
    } else {
        $action = $_POST['action'] ?? '';
        if ($action === 'create_intend') {
            $intendId = create_job_fair_intend($conn, $_POST, $errors);
            if (empty($errors) && $intendId > 0) {
                header('Location: /job_fair_intend_entry.php?intend_id=' . $intendId . '&created=1');
                exit;
            }
        }

        if ($action === 'update_intend') {
            $intendId = (int) ($_POST['intend_id'] ?? 0);
            if ($intendId === 0) {
                $errors[] = 'Invalid intend selected.';
            } else {
                update_job_fair_intend($conn, $intendId, $_POST, $errors);
                if (empty($errors)) {
                    $message = 'Job fair intend updated.';
                }
            }
        }

        if ($action === 'update_locations') {
            $intendId = (int) ($_POST['intend_id'] ?? 0);
            $locationType = $_POST['location_type'] ?? '';
            if ($intendId === 0) {
                $errors[] = 'Invalid intend selected.';
            } elseif (!in_array($locationType, $allowedLocationTypes, true)) {
                $errors[] = 'Invalid location type selection.';
            } else {
                $centerIds = array_values(array_filter($_POST['center_ids'] ?? [], static fn($value): bool => $value !== ''));
                replace_intend_locations($conn, $intendId, $locationType, $centerIds);
                if (empty($errors)) {
                    $message = 'Job fair locations updated.';
                }
            }
        }
    }
}

if ($intendId) {
    $intend = fetch_job_fair_intend($conn, $intendId);
}

if (!$message && isset($_GET['created'])) {
    $message = 'Job fair intend created. Please continue with location selection.';
}

$stepTwo = $intendId > 0 && $intend;
$sdpkCenters = $stepTwo ? fetch_sdpk_centers_for_intend($conn) : [];
$locationCounts = $stepTwo ? fetch_intend_location_counts($conn, $intendId) : [];
$selectedLocationIds = $stepTwo ? fetch_intend_location_ids_by_type($conn, $intendId) : [];

include __DIR__ . '/partials/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1"><?php echo $stepTwo ? 'Edit Job Fair Intend' : 'New Job Fair Intend'; ?></h1>
        <p class="text-muted mb-0">Capture intend details and select job fair locations.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-outline-secondary" href="/job_fair_intends.php">Back to Intend List</a>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
            <input type="hidden" name="action" value="<?php echo $stepTwo ? 'update_intend' : 'create_intend'; ?>">
            <?php if ($stepTwo): ?>
                <input type="hidden" name="intend_id" value="<?php echo (int) $intendId; ?>">
            <?php endif; ?>
            <div class="row g-3">
                <?php if ($stepTwo): ?>
                    <div class="col-md-4">
                        <label class="form-label">Intend Number</label>
                        <input class="form-control" value="<?php echo (int) $intendId; ?>" readonly>
                    </div>
                <?php endif; ?>
                <div class="col-md-4">
                    <label class="form-label">Intend Date</label>
                    <input class="form-control" type="date" name="intend_date" required value="<?php echo htmlspecialchars($intend['intend_date'] ?? date('Y-m-d')); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Reference Co-ordination Committee Number</label>
                    <input class="form-control" type="number" min="0" name="reference_committee_number" required value="<?php echo htmlspecialchars($intend['reference_committee_number'] ?? ''); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Reference Date</label>
                    <input class="form-control" type="date" name="reference_date" value="<?php echo htmlspecialchars($intend['reference_date'] ?? ''); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Reference Job Fair Number</label>
                    <input class="form-control" name="reference_job_fair_number" required value="<?php echo htmlspecialchars($intend['reference_job_fair_number'] ?? ''); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Job Fair Date</label>
                    <input class="form-control" type="date" name="job_fair_date" value="<?php echo htmlspecialchars($intend['job_fair_date'] ?? ''); ?>">
                </div>
            </div>
            <div class="d-flex justify-content-end gap-2 mt-3">
                <?php if ($stepTwo): ?>
                    <button class="btn btn-primary" type="submit">Update Intend</button>
                <?php else: ?>
                    <button class="btn btn-primary" type="submit">Save &amp; Continue</button>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<?php if ($stepTwo): ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h2 class="h5 mb-1">Job Fair Location List</h2>
                    <p class="text-muted small mb-0">Select SDPK centers for each location type.</p>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">Sl. No</th>
                            <th scope="col">Job Fair Location Type</th>
                            <th scope="col">Number of Locations</th>
                            <th scope="col">List</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $totalLocations = 0; ?>
                        <?php foreach ($locationTypes as $index => $type): ?>
                            <?php $count = $locationCounts[$type['label']] ?? 0; ?>
                            <?php $totalLocations += $count; ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo htmlspecialchars($type['label']); ?></td>
                                <td><?php echo (int) $count; ?></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#locationModal-<?php echo htmlspecialchars($type['key']); ?>">List</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="table-light">
                            <td colspan="2" class="fw-semibold">Total</td>
                            <td class="fw-semibold"><?php echo (int) $totalLocations; ?></td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php foreach ($locationTypes as $type): ?>
        <?php $selectedIds = $selectedLocationIds[$type['label']] ?? []; ?>
        <div class="modal fade" id="locationModal-<?php echo htmlspecialchars($type['key']); ?>" tabindex="-1" aria-hidden="true" data-location-modal>
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                        <input type="hidden" name="action" value="update_locations">
                        <input type="hidden" name="intend_id" value="<?php echo (int) $intendId; ?>">
                        <input type="hidden" name="location_type" value="<?php echo htmlspecialchars($type['label']); ?>">
                        <div class="modal-header">
                            <h5 class="modal-title"><?php echo htmlspecialchars($type['label']); ?> Locations</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted small">Selected centers: <span data-selected-count>0</span></span>
                                <span class="text-muted small"><?php echo count($sdpkCenters); ?> centers available</span>
                            </div>
                            <div class="list-group">
                                <?php foreach ($sdpkCenters as $center): ?>
                                    <?php $isChecked = in_array((int) $center['id'], $selectedIds, true); ?>
                                    <label class="list-group-item d-flex align-items-start gap-2">
                                        <input class="form-check-input mt-1 location-checkbox" type="checkbox" name="center_ids[]" value="<?php echo (int) $center['id']; ?>" <?php echo $isChecked ? 'checked' : ''; ?>>
                                        <span>
                                            <span class="fw-semibold"><?php echo htmlspecialchars($center['name']); ?></span>
                                            <span class="text-muted small d-block"><?php echo htmlspecialchars($center['code']); ?> • <?php echo htmlspecialchars($center['district_name']); ?></span>
                                        </span>
                                    </label>
                                <?php endforeach; ?>
                                <?php if (empty($sdpkCenters)): ?>
                                    <div class="text-muted">No SDPK centers available.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Save Selection</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <script>
        document.querySelectorAll('[data-location-modal]').forEach((modal) => {
            const countEl = modal.querySelector('[data-selected-count]');
            const checkboxes = modal.querySelectorAll('.location-checkbox');
            const updateCount = () => {
                const selected = modal.querySelectorAll('.location-checkbox:checked').length;
                if (countEl) {
                    countEl.textContent = selected;
                }
            };
            checkboxes.forEach((checkbox) => {
                checkbox.addEventListener('change', updateCount);
            });
            updateCount();
        });
    </script>
<?php endif; ?>
<?php include __DIR__ . '/partials/footer.php'; ?>
