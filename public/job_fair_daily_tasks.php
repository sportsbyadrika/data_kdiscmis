<?php
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/job_fair_daily_tasks.php';

require_auth();
$conn = db_connect();

$filters = [
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? '',
    'search' => trim($_GET['search'] ?? ''),
];

$message = '';
$errors = [];
$editTask = null;
$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;

if ($editId > 0) {
    $stmt = $conn->prepare('SELECT * FROM job_fair_daily_tasks WHERE id = ?');
    $stmt->bind_param('i', $editId);
    $stmt->execute();
    $result = $stmt->get_result();
    $editTask = $result->fetch_assoc() ?: null;
    if (!$editTask) {
        $errors[] = 'Unable to find the selected task.';
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
                    $errors[] = 'Invalid task selected.';
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

if (isset($_GET['created'])) {
    $message = 'Job fair daily task created successfully.';
}
if (isset($_GET['updated'])) {
    $message = 'Job fair daily task updated successfully.';
}

$tasks = fetch_job_fair_daily_tasks($conn, $filters);
include __DIR__ . '/partials/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Job Fair Daily Task</h1>
        <p class="text-muted mb-0">Capture committee meeting details and track job fair preparation notes.</p>
    </div>
    <a class="btn btn-outline-secondary" href="/admin.php">Back to Dashboard</a>
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
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
            <h2 class="h6 mb-0"><?php echo $editTask ? 'Edit Task' : 'Add Task'; ?></h2>
            <?php if ($editTask): ?>
                <a class="btn btn-sm btn-outline-secondary" href="/job_fair_daily_tasks.php">Cancel edit</a>
            <?php endif; ?>
        </div>
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
            <input type="hidden" name="action" value="<?php echo $editTask ? 'update_task' : 'create_task'; ?>">
            <?php if ($editTask): ?>
                <input type="hidden" name="task_id" value="<?php echo (int) $editTask['id']; ?>">
                <input type="hidden" name="existing_minutes_name" value="<?php echo htmlspecialchars($editTask['minutes_file_name'] ?? ''); ?>">
                <input type="hidden" name="existing_minutes_path" value="<?php echo htmlspecialchars($editTask['minutes_file_path'] ?? ''); ?>">
                <input type="hidden" name="existing_minutes_type" value="<?php echo htmlspecialchars($editTask['minutes_file_type'] ?? ''); ?>">
            <?php endif; ?>
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
            <div class="d-flex justify-content-end gap-2 mt-3">
                <?php if ($editTask): ?>
                    <button class="btn btn-primary" type="submit">Update Task</button>
                <?php else: ?>
                    <button class="btn btn-primary" type="submit">Add Task</button>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<form class="card border-0 shadow-sm mb-4" method="get">
    <div class="card-body">
        <h2 class="h6 mb-3">Filter Tasks</h2>
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Meeting Date From</label>
                <input class="form-control" type="date" name="date_from" value="<?php echo htmlspecialchars($filters['date_from']); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Meeting Date To</label>
                <input class="form-control" type="date" name="date_to" value="<?php echo htmlspecialchars($filters['date_to']); ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Search</label>
                <input class="form-control" name="search" placeholder="Meeting or job fair number" value="<?php echo htmlspecialchars($filters['search']); ?>">
            </div>
            <div class="col-md-2 d-flex align-items-end gap-2">
                <a class="btn btn-outline-secondary w-100" href="/job_fair_daily_tasks.php">Reset</a>
                <button class="btn btn-primary w-100" type="submit">Apply</button>
            </div>
        </div>
    </div>
</form>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h2 class="h5 mb-1">Task List</h2>
                <p class="text-muted small mb-0"><?php echo count($tasks); ?> records found.</p>
            </div>
        </div>
        <?php if (empty($tasks)): ?>
            <div class="alert alert-info mb-0">No tasks found for the selected filters.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">Meeting Date</th>
                            <th scope="col">Meeting #</th>
                            <th scope="col">Job Fair #</th>
                            <th scope="col">Job Fair Date</th>
                            <th scope="col">Locations (Usual/Additional)</th>
                            <th scope="col">Openings</th>
                            <th scope="col">Minutes</th>
                            <th scope="col">Members Participated</th>
                            <th scope="col"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tasks as $task): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($task['meeting_date']); ?></td>
                                <td><?php echo htmlspecialchars($task['meeting_number']); ?></td>
                                <td><?php echo htmlspecialchars($task['job_fair_number']); ?></td>
                                <td><?php echo htmlspecialchars($task['job_fair_date'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars(($task['locations_usual_sdpk'] ?? '-') . '/' . ($task['locations_additional'] ?? '-')); ?></td>
                                <td><?php echo htmlspecialchars($task['openings'] ?? '-'); ?></td>
                                <td>
                                    <?php if (!empty($task['minutes_file_path'])): ?>
                                        <a href="<?php echo htmlspecialchars($task['minutes_file_path']); ?>" target="_blank" rel="noopener">Download</a>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td><?php echo nl2br(htmlspecialchars($task['members_participated'] ?? '-')); ?></td>
                                <td class="text-end">
                                    <?php
                                    $editParams = $_GET;
                                    $editParams['edit'] = (int) $task['id'];
                                    ?>
                                    <a class="btn btn-sm btn-outline-primary" href="/job_fair_daily_tasks.php?<?php echo htmlspecialchars(http_build_query($editParams)); ?>">Edit</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>
