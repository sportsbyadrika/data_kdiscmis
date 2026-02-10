<?php
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/job_fair_intends.php';
require_once __DIR__ . '/../src/masters.php';
require_once __DIR__ . '/../src/users.php';

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

        if ($action === 'update_targets') {
            $intendId = (int) ($_POST['intend_id'] ?? 0);
            if ($intendId === 0) {
                $errors[] = 'Invalid intend selected.';
            } else {
                update_job_fair_intend_targets($conn, $intendId, $_POST, $errors);
                if (empty($errors)) {
                    $message = 'Job fair targets updated.';
                }
            }
        }

        if ($action === 'update_employers') {
            $intendId = (int) ($_POST['intend_id'] ?? 0);
            if ($intendId === 0) {
                $errors[] = 'Invalid intend selected.';
            } else {
                $employerIds = array_values(array_filter($_POST['employer_ids'] ?? [], static fn($value): bool => $value !== ''));
                replace_intend_employers($conn, $intendId, $employerIds);
                if (empty($errors)) {
                    $message = 'Employers updated.';
                }
            }
        }

        if ($action === 'update_job_titles') {
            $intendId = (int) ($_POST['intend_id'] ?? 0);
            if ($intendId === 0) {
                $errors[] = 'Invalid intend selected.';
            } else {
                $jobTitleIds = array_values(array_filter($_POST['job_title_ids'] ?? [], static fn($value): bool => $value !== ''));
                replace_intend_job_titles($conn, $intendId, $jobTitleIds);
                if (empty($errors)) {
                    $message = 'Job titles updated.';
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
$employers = $stepTwo ? fetch_employers_for_intend($conn) : [];
$jobTitles = $stepTwo ? fetch_job_titles_for_intend($conn) : [];
$selectedEmployerIds = $stepTwo ? fetch_intend_employer_ids($conn, $intendId) : [];
$selectedJobTitleIds = $stepTwo ? fetch_intend_job_title_ids($conn, $intendId) : [];
$selectedJobTitleTotal = 0;
if ($stepTwo && !empty($selectedJobTitleIds)) {
    $selectedLookup = array_flip($selectedJobTitleIds);
    foreach ($jobTitles as $jobTitle) {
        if (isset($selectedLookup[(int) $jobTitle['id']])) {
            $selectedJobTitleTotal += (int) $jobTitle['openings'];
        }
    }
}
$educationCategories = $stepTwo ? fetch_qualification_categories($conn) : [];
$officers = $stepTwo ? fetch_active_officers($conn) : [];
$selectedEmployers = [];
$selectedAggregators = [];
$selectedJobTitles = [];
if ($stepTwo) {
    $selectedEmployerLookup = array_flip($selectedEmployerIds);
    $selectedJobTitleLookup = array_flip($selectedJobTitleIds);
    $selectedAggregatorLookup = [];

    foreach ($employers as $employer) {
        $employerId = (int) $employer['id'];
        if (!isset($selectedEmployerLookup[$employerId])) {
            continue;
        }
        $selectedEmployers[] = $employer;

        $aggregatorName = trim((string) ($employer['aggregator_name'] ?? ''));
        if ($aggregatorName === '') {
            continue;
        }
        if (!isset($selectedAggregatorLookup[$aggregatorName])) {
            $selectedAggregatorLookup[$aggregatorName] = [
                'id' => count($selectedAggregatorLookup) + 1,
                'name' => $aggregatorName,
            ];
        }
    }

    foreach ($jobTitles as $jobTitle) {
        if (isset($selectedJobTitleLookup[(int) $jobTitle['id']])) {
            $selectedJobTitles[] = $jobTitle;
        }
    }

    $selectedAggregators = array_values($selectedAggregatorLookup);
}

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

    <div class="card border-0 shadow-sm mt-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h2 class="h5 mb-1">Job Fair Targets</h2>
                    <p class="text-muted small mb-0">Capture hiring targets and select employers/job titles.</p>
                </div>
            </div>
            <form method="post" class="row g-3 align-items-end">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                <input type="hidden" name="action" value="update_targets">
                <input type="hidden" name="intend_id" value="<?php echo (int) $intendId; ?>">
                <div class="col-md-4">
                    <label class="form-label">Target Openings</label>
                    <input class="form-control" type="number" min="0" name="target_openings" value="<?php echo htmlspecialchars($intend['target_openings'] ?? ''); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Minimum HR Required</label>
                    <input class="form-control" type="number" min="0" name="minimum_hr_required" value="<?php echo htmlspecialchars($intend['minimum_hr_required'] ?? ''); ?>">
                </div>
                <div class="col-md-4">
                    <button class="btn btn-primary" type="submit">Save Targets</button>
                </div>
            </form>
            <div class="table-responsive mt-4">
                <table class="table align-middle">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">Category</th>
                            <th scope="col">Selected Count</th>
                            <th scope="col">Total Openings</th>
                            <th scope="col">List</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Employers</td>
                            <td><?php echo count($selectedEmployerIds); ?></td>
                            <td>-</td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#employerModal">List</button>
                            </td>
                        </tr>
                        <tr>
                            <td>Job Titles</td>
                            <td><?php echo count($selectedJobTitleIds); ?></td>
                            <td><?php echo (int) $selectedJobTitleTotal; ?></td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#jobTitleModal">List</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>


    <div class="card border-0 shadow-sm mt-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h2 class="h5 mb-1">Category Targets</h2>
                    <p class="text-muted small mb-0">Enter target counts by education category for this job fair.</p>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">Sl. No</th>
                            <th scope="col">Education Category master</th>
                            <th scope="col">Target Count for this job fair</th>
                            <th scope="col">Criteria</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($educationCategories as $index => $category): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo htmlspecialchars($category['name']); ?></td>
                                <td style="max-width: 220px;">
                                    <input class="form-control form-control-sm" type="number" min="0" name="category_targets[<?php echo (int) $category['id']; ?>][target]" placeholder="Enter target">
                                </td>
                                <td>
                                    <?php if (!empty($category['criteria'])): ?>
                                        <span class="small text-muted"><?php echo nl2br(htmlspecialchars($category['criteria'])); ?></span>
                                    <?php else: ?>
                                        <span class="small text-muted">Criteria not specified.</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($educationCategories)): ?>
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">No education categories available.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mt-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h2 class="h5 mb-1">Individual officer targets</h2>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">Sl. No</th>
                            <th scope="col">Name of officer</th>
                            <th scope="col">Aggregator wise target</th>
                            <th scope="col">Employer wise target</th>
                            <th scope="col">Job title wise target</th>
                            <th scope="col">Education category wise target</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($officers as $index => $officer): ?>
                            <?php $officerId = (int) $officer['id']; ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo htmlspecialchars($officer['name']); ?></td>
                                <td><button class="btn btn-link p-0" type="button" data-bs-toggle="modal" data-bs-target="#aggregatorTarget-<?php echo $officerId; ?>">Aggregator wise target</button></td>
                                <td><button class="btn btn-link p-0" type="button" data-bs-toggle="modal" data-bs-target="#employerTarget-<?php echo $officerId; ?>">Employer wise target</button></td>
                                <td><button class="btn btn-link p-0" type="button" data-bs-toggle="modal" data-bs-target="#jobTitleTarget-<?php echo $officerId; ?>">Job title wise target</button></td>
                                <td><button class="btn btn-link p-0" type="button" data-bs-toggle="modal" data-bs-target="#educationTarget-<?php echo $officerId; ?>">Education category wise target</button></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($officers)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">No officers available.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php foreach ($officers as $officer): ?>
        <?php $officerId = (int) $officer['id']; ?>
        <div class="modal fade" id="aggregatorTarget-<?php echo $officerId; ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Aggregator wise target - <?php echo htmlspecialchars($officer['name']); ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div><div class="modal-body">
                <?php if (empty($selectedAggregators)): ?>
                    <p class="text-muted mb-0">No aggregators from selected employers.</p>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($selectedAggregators as $aggregator): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center gap-2"><span><?php echo htmlspecialchars($aggregator['name']); ?></span><input class="form-control form-control-sm" style="max-width:140px;" type="number" min="0" placeholder="Target"></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button><button type="button" class="btn btn-primary" data-bs-dismiss="modal">Save targets</button></div></div></div>
        </div>

        <div class="modal fade" id="employerTarget-<?php echo $officerId; ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Employer wise target - <?php echo htmlspecialchars($officer['name']); ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div><div class="modal-body">
                <?php if (empty($selectedEmployers)): ?>
                    <p class="text-muted mb-0">No employers selected for this intend.</p>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($selectedEmployers as $employer): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center gap-2"><span><?php echo htmlspecialchars($employer['name']); ?><?php echo !empty($employer['aggregator_name']) ? ' • ' . htmlspecialchars($employer['aggregator_name']) : ''; ?></span><input class="form-control form-control-sm" style="max-width:140px;" type="number" min="0" placeholder="Target"></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button><button type="button" class="btn btn-primary" data-bs-dismiss="modal">Save targets</button></div></div></div>
        </div>

        <div class="modal fade" id="jobTitleTarget-<?php echo $officerId; ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Job title wise target - <?php echo htmlspecialchars($officer['name']); ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div><div class="modal-body">
                <?php if (empty($selectedJobTitles)): ?>
                    <p class="text-muted mb-0">No job titles selected for this intend.</p>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($selectedJobTitles as $jobTitle): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center gap-2"><span><?php echo htmlspecialchars($jobTitle['job_title']); ?><?php echo !empty($jobTitle['employer_name']) ? ' • ' . htmlspecialchars($jobTitle['employer_name']) : ''; ?></span><input class="form-control form-control-sm" style="max-width:140px;" type="number" min="0" placeholder="Target"></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button><button type="button" class="btn btn-primary" data-bs-dismiss="modal">Save targets</button></div></div></div>
        </div>

        <div class="modal fade" id="educationTarget-<?php echo $officerId; ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Education category wise target - <?php echo htmlspecialchars($officer['name']); ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div><div class="modal-body">
                <?php if (empty($educationCategories)): ?>
                    <p class="text-muted mb-0">No education categories available.</p>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($educationCategories as $category): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center gap-2"><span><?php echo htmlspecialchars($category['name']); ?></span><input class="form-control form-control-sm" style="max-width:140px;" type="number" min="0" placeholder="Target"></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button><button type="button" class="btn btn-primary" data-bs-dismiss="modal">Save targets</button></div></div></div>
        </div>
    <?php endforeach; ?>

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
                            <div class="list-group modal-list-scroll">
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

    <div class="modal fade" id="employerModal" tabindex="-1" aria-hidden="true" data-employer-modal>
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                    <input type="hidden" name="action" value="update_employers">
                    <input type="hidden" name="intend_id" value="<?php echo (int) $intendId; ?>">
                    <div class="modal-header">
                        <h5 class="modal-title">Employers</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                            <span class="text-muted small">Selected employers: <span data-selected-count>0</span></span>
                            <span class="text-muted small"><?php echo count($employers); ?> employers available</span>
                        </div>
                        <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
                            <input class="form-control" type="search" placeholder="Search employers..." data-search-input>
                            <div class="form-check ms-auto">
                                <input class="form-check-input" type="checkbox" id="selectAllEmployers" data-select-all>
                                <label class="form-check-label" for="selectAllEmployers">Select all</label>
                            </div>
                        </div>
                        <div class="list-group modal-list-scroll" data-search-list>
                            <?php foreach ($employers as $employer): ?>
                                <?php $isChecked = in_array((int) $employer['id'], $selectedEmployerIds, true); ?>
                                <label class="list-group-item d-flex align-items-start gap-2" data-search-item>
                                    <input class="form-check-input mt-1 selection-checkbox" type="checkbox" name="employer_ids[]" value="<?php echo (int) $employer['id']; ?>" <?php echo $isChecked ? 'checked' : ''; ?>>
                                    <span>
                                        <span class="fw-semibold"><?php echo htmlspecialchars($employer['name']); ?></span>
                                        <span class="text-muted small d-block"><?php echo htmlspecialchars($employer['code']); ?><?php echo $employer['aggregator_name'] ? ' • ' . htmlspecialchars($employer['aggregator_name']) : ''; ?></span>
                                        <span class="text-muted small d-block"><?php echo htmlspecialchars($employer['spoc_name'] ?? ''); ?><?php echo $employer['spoc_mobile'] ? ' • ' . htmlspecialchars($employer['spoc_mobile']) : ''; ?><?php echo $employer['spoc_email'] ? ' • ' . htmlspecialchars($employer['spoc_email']) : ''; ?></span>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                            <?php if (empty($employers)): ?>
                                <div class="text-muted">No employers available.</div>
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

    <div class="modal fade" id="jobTitleModal" tabindex="-1" aria-hidden="true" data-job-title-modal>
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                    <input type="hidden" name="action" value="update_job_titles">
                    <input type="hidden" name="intend_id" value="<?php echo (int) $intendId; ?>">
                    <div class="modal-header">
                        <h5 class="modal-title">Job Titles</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                            <span class="text-muted small">Selected job titles: <span data-selected-count>0</span></span>
                            <span class="text-muted small"><?php echo count($jobTitles); ?> job titles available</span>
                        </div>
                        <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
                            <input class="form-control" type="search" placeholder="Search job titles..." data-search-input>
                            <div class="form-check ms-auto">
                                <input class="form-check-input" type="checkbox" id="selectAllJobTitles" data-select-all>
                                <label class="form-check-label" for="selectAllJobTitles">Select all</label>
                            </div>
                        </div>
                        <div class="list-group modal-list-scroll" data-search-list>
                            <?php foreach ($jobTitles as $jobTitle): ?>
                                <?php $isChecked = in_array((int) $jobTitle['id'], $selectedJobTitleIds, true); ?>
                                <label class="list-group-item d-flex align-items-start gap-2" data-search-item>
                                    <input class="form-check-input mt-1 selection-checkbox" type="checkbox" name="job_title_ids[]" value="<?php echo (int) $jobTitle['id']; ?>" <?php echo $isChecked ? 'checked' : ''; ?>>
                                    <span>
                                        <span class="fw-semibold"><?php echo htmlspecialchars($jobTitle['job_title']); ?></span>
                                        <span class="text-muted small d-block"><?php echo htmlspecialchars($jobTitle['job_code']); ?> • <?php echo htmlspecialchars($jobTitle['employer_name']); ?></span>
                                        <span class="text-muted small d-block">Openings: <?php echo (int) $jobTitle['openings']; ?></span>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                            <?php if (empty($jobTitles)): ?>
                                <div class="text-muted">No job titles available.</div>
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

    <script>
        const setupSelectionModal = (modal) => {
            if (!modal) {
                return;
            }
            const countEl = modal.querySelector('[data-selected-count]');
            const checkboxes = modal.querySelectorAll('.selection-checkbox, .location-checkbox');
            const selectAll = modal.querySelector('[data-select-all]');
            const updateCount = () => {
                const selected = modal.querySelectorAll('.selection-checkbox:checked, .location-checkbox:checked').length;
                if (countEl) {
                    countEl.textContent = selected;
                }
                if (selectAll && checkboxes.length) {
                    const allChecked = Array.from(checkboxes).every((box) => box.checked);
                    const anyChecked = Array.from(checkboxes).some((box) => box.checked);
                    selectAll.indeterminate = !allChecked && anyChecked;
                    selectAll.checked = allChecked;
                }
            };
            const searchInput = modal.querySelector('[data-search-input]');
            const items = modal.querySelectorAll('[data-search-item]');
            if (searchInput && items.length) {
                searchInput.addEventListener('input', () => {
                    const query = searchInput.value.trim().toLowerCase();
                    items.forEach((item) => {
                        const text = item.textContent.toLowerCase();
                        item.classList.toggle('d-none', query !== '' && !text.includes(query));
                    });
                });
            }
            if (selectAll && checkboxes.length) {
                selectAll.addEventListener('change', () => {
                    checkboxes.forEach((checkbox) => {
                        checkbox.checked = selectAll.checked;
                    });
                    updateCount();
                });
            }
            checkboxes.forEach((checkbox) => {
                checkbox.addEventListener('change', () => {
                    updateCount();
                });
            });
            updateCount();
        };

        document.querySelectorAll('[data-location-modal]').forEach((modal) => {
            setupSelectionModal(modal);
        });
        setupSelectionModal(document.querySelector('[data-employer-modal]'));
        setupSelectionModal(document.querySelector('[data-job-title-modal]'));
    </script>
    <style>
        .modal-list-scroll {
            max-height: 320px;
            overflow-y: auto;
        }
    </style>
<?php endif; ?>
<?php include __DIR__ . '/partials/footer.php'; ?>
