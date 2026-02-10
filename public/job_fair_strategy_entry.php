<?php
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/job_fair_daily_tasks.php';
require_once __DIR__ . '/../src/masters.php';
require_once __DIR__ . '/../src/users.php';

require_auth();
$conn = db_connect();

$errors = [];
$message = '';
$editTask = null;
$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$educationCategories = fetch_qualification_categories($conn);
$officers = fetch_active_officers($conn);
$aggregators = [];
$employers = [];
$jobTitles = [];

if ($editId > 0) {
    $stmt = $conn->prepare('SELECT * FROM job_fair_daily_tasks WHERE id = ?');
    $stmt->bind_param('i', $editId);
    $stmt->execute();
    $result = $stmt->get_result();
    $editTask = $result->fetch_assoc() ?: null;
    if (!$editTask) {
        $errors[] = 'Unable to find the selected strategy meeting.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request token.';
    } else {
        $action = $_POST['action'] ?? '';
        $meetingDate = $_POST['meeting_date'] ?? '';
        $meetingNumber = trim($_POST['meeting_number'] ?? '');
        $jobFairNumber = trim($_POST['job_fair_number'] ?? '');

        if ($meetingDate === '') {
            $errors[] = 'Meeting date is required.';
        }
        if ($meetingNumber === '') {
            $errors[] = 'Meeting number is required.';
        }
        if ($jobFairNumber === '') {
            $errors[] = 'Job fair number is required.';
        }

        if (empty($errors)) {
            if ($action === 'create_task') {
                create_job_fair_daily_task($conn, $_POST, $_FILES['minutes'] ?? null, $errors);
                if (empty($errors)) {
                    header('Location: /job_fair_daily_tasks.php?created=1');
                    exit;
                }
            }

            if ($action === 'update_task') {
                $taskId = (int) ($_POST['task_id'] ?? 0);
                if ($taskId === 0) {
                    $errors[] = 'Invalid strategy meeting selected.';
                } else {
                    update_job_fair_daily_task($conn, $taskId, $_POST, $_FILES['minutes'] ?? null, $errors);
                    if (empty($errors)) {
                        header('Location: /job_fair_daily_tasks.php?updated=1');
                        exit;
                    }
                }
            }
        }
    }
}

include __DIR__ . '/partials/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1"><?php echo $editTask ? 'Edit Job Fair Strategy' : 'New Job Fair Strategy'; ?></h1>
        <p class="text-muted mb-0">Capture committee meeting details and job fair preparation notes.</p>
    </div>
    <a class="btn btn-outline-secondary" href="/job_fair_daily_tasks.php">Back to Strategy List</a>
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

<form method="post" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
    <input type="hidden" name="action" value="<?php echo $editTask ? 'update_task' : 'create_task'; ?>">
    <?php if ($editTask): ?>
        <input type="hidden" name="task_id" value="<?php echo (int) $editTask['id']; ?>">
        <input type="hidden" name="existing_minutes_name" value="<?php echo htmlspecialchars($editTask['minutes_file_name'] ?? ''); ?>">
        <input type="hidden" name="existing_minutes_path" value="<?php echo htmlspecialchars($editTask['minutes_file_path'] ?? ''); ?>">
        <input type="hidden" name="existing_minutes_type" value="<?php echo htmlspecialchars($editTask['minutes_file_type'] ?? ''); ?>">
    <?php endif; ?>
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Meeting Date</label>
                    <input class="form-control" type="date" name="meeting_date" required value="<?php echo htmlspecialchars($editTask['meeting_date'] ?? date('Y-m-d')); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Meeting Number</label>
                    <input class="form-control" name="meeting_number" required value="<?php echo htmlspecialchars($editTask['meeting_number'] ?? ''); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Job Fair Number</label>
                    <input class="form-control" name="job_fair_number" required value="<?php echo htmlspecialchars($editTask['job_fair_number'] ?? ''); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Date of Job Fair</label>
                    <input class="form-control" type="date" name="job_fair_date" value="<?php echo htmlspecialchars($editTask['job_fair_date'] ?? ''); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Locations - Usual SDPK</label>
                    <input class="form-control" type="number" min="0" name="locations_usual_sdpk" value="<?php echo htmlspecialchars($editTask['locations_usual_sdpk'] ?? ''); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Locations - Additional</label>
                    <input class="form-control" type="number" min="0" name="locations_additional" value="<?php echo htmlspecialchars($editTask['locations_additional'] ?? ''); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Locational Functional Requirements</label>
                    <textarea class="form-control" name="locational_functional_requirements" rows="2"><?php echo htmlspecialchars($editTask['locational_functional_requirements'] ?? ''); ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Campaign Target</label>
                    <textarea class="form-control" name="campaign_target" rows="2"><?php echo htmlspecialchars($editTask['campaign_target'] ?? ''); ?></textarea>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Openings</label>
                    <input class="form-control" type="number" min="0" name="openings" value="<?php echo htmlspecialchars($editTask['openings'] ?? ''); ?>">
                </div>
                <div class="col-md-8">
                    <label class="form-label">Remark on Sectoral Preference</label>
                    <textarea class="form-control" name="remark_sectoral_preference" rows="2"><?php echo htmlspecialchars($editTask['remark_sectoral_preference'] ?? ''); ?></textarea>
                </div>
                <div class="col-md-8">
                    <label class="form-label">Remark on Impact Planned</label>
                    <textarea class="form-control" name="remark_impact_planned" rows="2"><?php echo htmlspecialchars($editTask['remark_impact_planned'] ?? ''); ?></textarea>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Minutes (PDF/DOC/DOCX)</label>
                    <input class="form-control" type="file" name="minutes" accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document">
                    <?php if (!empty($editTask['minutes_file_path'])): ?>
                        <div class="form-text">
                            Current: <a href="<?php echo htmlspecialchars($editTask['minutes_file_path']); ?>" target="_blank" rel="noopener">View minutes</a>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Members Participated</label>
                    <textarea class="form-control" name="members_participated" rows="2"><?php echo htmlspecialchars($editTask['members_participated'] ?? ''); ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
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
                            <th scope="col">Education Category</th>
                            <th scope="col">Target Count</th>
                            <th scope="col">Criteria</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($educationCategories as $index => $category): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo htmlspecialchars($category['name']); ?></td>
                                <td style="max-width: 160px;">
                                    <input class="form-control form-control-sm" type="number" min="0" name="category_targets[<?php echo (int) $category['id']; ?>][target]">
                                </td>
                                <td>
                                    <?php if (!empty($category['criteria'])): ?>
                                        <div class="small text-muted"><?php echo nl2br(htmlspecialchars($category['criteria'])); ?></div>
                                    <?php else: ?>
                                        <span class="text-muted small">Criteria not specified.</span>
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

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
                <div>
                    <h2 class="h5 mb-1">Individual Officer Targets</h2>
                    <p class="text-muted small mb-0">Assign target splits by aggregator, employer, job title, and education category.</p>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">Sl. No</th>
                            <th scope="col">Name of Officer</th>
                            <th scope="col">Aggregator Wise Target</th>
                            <th scope="col">Employer Wise Target</th>
                            <th scope="col">Job Title Wise Target</th>
                            <th scope="col">Education Category Wise Target</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($officers as $index => $officer): ?>
                            <?php $officerId = (int) $officer['id']; ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo htmlspecialchars($officer['name']); ?></td>
                                <td>
                                    <button class="btn btn-link p-0" type="button" data-bs-toggle="modal" data-bs-target="#aggregatorTarget-<?php echo $officerId; ?>">Set target</button>
                                </td>
                                <td>
                                    <button class="btn btn-link p-0" type="button" data-bs-toggle="modal" data-bs-target="#employerTarget-<?php echo $officerId; ?>">Set target</button>
                                </td>
                                <td>
                                    <button class="btn btn-link p-0" type="button" data-bs-toggle="modal" data-bs-target="#jobTitleTarget-<?php echo $officerId; ?>">Set target</button>
                                </td>
                                <td>
                                    <button class="btn btn-link p-0" type="button" data-bs-toggle="modal" data-bs-target="#educationTarget-<?php echo $officerId; ?>">Set target</button>
                                </td>
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

    <div class="d-flex justify-content-end gap-2 mt-3">
        <?php if ($editTask): ?>
            <button class="btn btn-primary" type="submit">Update Strategy</button>
        <?php else: ?>
            <button class="btn btn-primary" type="submit">Save Strategy</button>
        <?php endif; ?>
    </div>

    <?php foreach ($officers as $officer): ?>
        <?php $officerId = (int) $officer['id']; ?>
        <div class="modal fade" id="aggregatorTarget-<?php echo $officerId; ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Aggregator Targets - <?php echo htmlspecialchars($officer['name']); ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <?php if (empty($aggregators)): ?>
                            <p class="text-muted mb-0">No aggregators selected for this intend.</p>
                        <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($aggregators as $aggregator): ?>
                                    <div class="list-group-item d-flex flex-wrap justify-content-between align-items-center gap-2">
                                        <span><?php echo htmlspecialchars($aggregator['name']); ?></span>
                                        <input class="form-control form-control-sm" style="max-width: 140px;" type="number" min="0" name="officer_targets[<?php echo $officerId; ?>][aggregator][<?php echo (int) $aggregator['id']; ?>]">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Save Targets</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="employerTarget-<?php echo $officerId; ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Employer Targets - <?php echo htmlspecialchars($officer['name']); ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <?php if (empty($employers)): ?>
                            <p class="text-muted mb-0">No employers selected for this intend.</p>
                        <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($employers as $employer): ?>
                                    <div class="list-group-item d-flex flex-wrap justify-content-between align-items-center gap-2">
                                        <span><?php echo htmlspecialchars($employer['name']); ?></span>
                                        <input class="form-control form-control-sm" style="max-width: 140px;" type="number" min="0" name="officer_targets[<?php echo $officerId; ?>][employer][<?php echo (int) $employer['id']; ?>]">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Save Targets</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="jobTitleTarget-<?php echo $officerId; ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Job Title Targets - <?php echo htmlspecialchars($officer['name']); ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <?php if (empty($jobTitles)): ?>
                            <p class="text-muted mb-0">No job titles selected for this intend.</p>
                        <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($jobTitles as $jobTitle): ?>
                                    <div class="list-group-item d-flex flex-wrap justify-content-between align-items-center gap-2">
                                        <span><?php echo htmlspecialchars($jobTitle['name']); ?></span>
                                        <input class="form-control form-control-sm" style="max-width: 140px;" type="number" min="0" name="officer_targets[<?php echo $officerId; ?>][job_title][<?php echo (int) $jobTitle['id']; ?>]">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Save Targets</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="educationTarget-<?php echo $officerId; ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Education Category Targets - <?php echo htmlspecialchars($officer['name']); ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <?php if (empty($educationCategories)): ?>
                            <p class="text-muted mb-0">No education categories available.</p>
                        <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($educationCategories as $category): ?>
                                    <div class="list-group-item d-flex flex-wrap justify-content-between align-items-center gap-2">
                                        <span><?php echo htmlspecialchars($category['name']); ?></span>
                                        <input class="form-control form-control-sm" style="max-width: 140px;" type="number" min="0" name="officer_targets[<?php echo $officerId; ?>][education_category][<?php echo (int) $category['id']; ?>]">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Save Targets</button>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</form>
<?php include __DIR__ . '/partials/footer.php'; ?>
