<?php
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/dsm_daily_tasks.php';

require_auth();
$conn = db_connect();
$user = current_user();
$process = (string) ($_GET['process'] ?? $_POST['process'] ?? '');
$allowedProcesses = ['', 'new_employer', 'edit_employer', 'new_job_title', 'edit_job_title'];
if (!in_array($process, $allowedProcesses, true)) {
    $process = '';
}
$search = trim($_GET['search'] ?? '');
$message = '';
$errors = [];

if (isset($_GET['created'])) {
    $message = 'Entry saved successfully with activity date log.';
}
if (isset($_GET['updated'])) {
    $message = 'Entry updated successfully with activity date log.';
}
if (isset($_GET['task_created'])) {
    $message = 'Daily task created successfully.';
}
if (isset($_GET['task_updated'])) {
    $message = 'Daily task updated successfully.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request token.';
    } else {
        $action = $_POST['action'] ?? '';
        if ($action === 'create_employer') {
            create_employer_for_dsm($conn, $_POST, (int) ($user['id'] ?? 0), $errors);
            if (empty($errors)) {
                header('Location: /dsm_daily_tasks.php?process=new_employer&created=1');
                exit();
            }
            $process = 'new_employer';
        } elseif ($action === 'update_employer') {
            update_employer_for_dsm($conn, (int) ($_POST['employer_id'] ?? 0), $_POST, (int) ($user['id'] ?? 0), $errors);
            if (empty($errors)) {
                header('Location: /dsm_daily_tasks.php?process=edit_employer&updated=1');
                exit();
            }
            $process = 'edit_employer';
        } elseif ($action === 'create_job_title') {
            create_job_title_for_dsm($conn, $_POST, (int) ($user['id'] ?? 0), $errors);
            if (empty($errors)) {
                header('Location: /dsm_daily_tasks.php?process=new_job_title&created=1');
                exit();
            }
            $process = 'new_job_title';
        } elseif ($action === 'update_job_title') {
            update_job_title_for_dsm($conn, (int) ($_POST['job_title_id'] ?? 0), $_POST, (int) ($user['id'] ?? 0), $errors);
            if (empty($errors)) {
                header('Location: /dsm_daily_tasks.php?process=edit_job_title&updated=1');
                exit();
            }
            $process = 'edit_job_title';
        }
    }
}

$aggregators = fetch_aggregators_for_dsm($conn);
$employers = search_employers_for_dsm($conn, $search);
$jobTitles = search_job_titles_for_dsm($conn, $search);
$selectedEmployer = isset($_GET['employer_id']) ? fetch_employer_for_dsm($conn, (int) $_GET['employer_id']) : null;
$selectedJobTitle = isset($_GET['job_title_id']) ? fetch_job_title_for_dsm($conn, (int) $_GET['job_title_id']) : null;

$filters = [
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? '',
    'job_fair_number' => trim($_GET['job_fair_number'] ?? ''),
    'employer_name' => trim($_GET['employer_name'] ?? ''),
    'job_title' => trim($_GET['job_title'] ?? ''),
    'meeting_owner' => trim($_GET['meeting_owner'] ?? ''),
];
$tasks = fetch_dsm_daily_tasks($conn, $filters);


include __DIR__ . '/partials/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">DSM Daily Task</h1>
        <p class="text-muted mb-0">Capture daily process updates for employer and job title masters with date-based tracking.</p>
    </div>
    <a class="btn btn-outline-secondary" href="/admin.php">Back to Dashboard</a>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger mb-3">
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if (($process ?? '') === ''): ?>
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h5 mb-3">Select Process</h2>
            <div class="d-flex flex-wrap gap-2">
                <a class="btn btn-primary" href="/dsm_daily_tasks.php?process=new_employer">New employer</a>
                <a class="btn btn-outline-primary" href="/dsm_daily_tasks.php?process=edit_employer">Edit employer</a>
                <a class="btn btn-primary" href="/dsm_daily_tasks.php?process=new_job_title">New job title</a>
                <a class="btn btn-outline-primary" href="/dsm_daily_tasks.php?process=edit_job_title">Edit job title</a>
                <a class="btn btn-success" role="button" href="/dsm_daily_task_entry.php">New DSM task</a>
            </div>
        </div>
    </div>

    <form class="card border-0 shadow-sm mb-4" method="get">
        <div class="card-body">
            <h2 class="h6 mb-3">Filter Task List</h2>
            <div class="row g-3">
                <div class="col-md-2">
                    <label class="form-label">Date from</label>
                    <input class="form-control" type="date" name="date_from" value="<?php echo htmlspecialchars($filters['date_from']); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Date to</label>
                    <input class="form-control" type="date" name="date_to" value="<?php echo htmlspecialchars($filters['date_to']); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Job Fair #</label>
                    <input class="form-control" name="job_fair_number" value="<?php echo htmlspecialchars($filters['job_fair_number']); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Employer name</label>
                    <input class="form-control" name="employer_name" value="<?php echo htmlspecialchars($filters['employer_name']); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Job title</label>
                    <input class="form-control" name="job_title" value="<?php echo htmlspecialchars($filters['job_title']); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Meeting owner</label>
                    <input class="form-control" name="meeting_owner" value="<?php echo htmlspecialchars($filters['meeting_owner']); ?>">
                </div>
                <div class="col-12 d-flex justify-content-end gap-2">
                    <a class="btn btn-outline-secondary" href="/dsm_daily_tasks.php">Reset</a>
                    <button class="btn btn-primary" type="submit">Apply</button>
                </div>
            </div>
        </div>
    </form>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h5 mb-0">Task List</h2>
                <span class="text-muted small"><?php echo count($tasks); ?> records found.</span>
            </div>
            <?php if (empty($tasks)): ?>
                <div class="alert alert-info mb-0">No daily tasks found for selected filters.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Task type</th>
                            <th>Task title</th>
                            <th>Result</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($tasks as $task): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($task['task_date']); ?></td>
                                <td><?php echo htmlspecialchars($task['task_type_name']); ?></td>
                                <td><?php echo htmlspecialchars($task['task_title']); ?></td>
                                <td><?php echo htmlspecialchars($task['result']); ?></td>
                                <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="/dsm_daily_task_entry.php?task_id=<?php echo (int) $task['id']; ?>">Edit</a></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php elseif (($process ?? '') === 'new_employer'): ?>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h2 class="h5 mb-3">New employer</h2>
            <p class="text-muted small">Create a new employer record. Entry date is automatically logged in DSM activity.</p>
            <form method="post" class="row g-3">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                <input type="hidden" name="action" value="create_employer">
                <div class="col-md-4">
                    <label class="form-label">Employer code</label>
                    <input class="form-control" name="code" required value="<?php echo htmlspecialchars($_POST['code'] ?? ''); ?>">
                </div>
                <div class="col-md-8">
                    <label class="form-label">Employer name</label>
                    <input class="form-control" name="name" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">SPOC name</label>
                    <input class="form-control" name="spoc_name" value="<?php echo htmlspecialchars($_POST['spoc_name'] ?? ''); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">SPOC mobile</label>
                    <input class="form-control" name="spoc_mobile" value="<?php echo htmlspecialchars($_POST['spoc_mobile'] ?? ''); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">SPOC email</label>
                    <input class="form-control" type="email" name="spoc_email" value="<?php echo htmlspecialchars($_POST['spoc_email'] ?? ''); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Aggregator</label>
                    <select class="form-select" name="aggregator_id">
                        <option value="">Select aggregator</option>
                        <?php foreach ($aggregators as $aggregator): ?>
                            <option value="<?php echo (int) $aggregator['id']; ?>" <?php echo (($_POST['aggregator_id'] ?? '') == $aggregator['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($aggregator['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button class="btn btn-primary" type="submit">Save employer</button>
                    <a class="btn btn-outline-secondary" href="/dsm_daily_tasks.php">Process home</a>
                </div>
            </form>
        </div>
    </div>
<?php elseif (($process ?? '') === 'edit_employer'): ?>
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <h2 class="h5 mb-3">Edit employer</h2>
            <form class="row g-2" method="get">
                <input type="hidden" name="process" value="edit_employer">
                <div class="col-md-8">
                    <input class="form-control" name="search" placeholder="Search employer by name/code" value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button class="btn btn-outline-primary w-100" type="submit">Search</button>
                    <a class="btn btn-outline-secondary w-100" href="/dsm_daily_tasks.php?process=edit_employer">Reset</a>
                </div>
            </form>
            <div class="table-responsive mt-3">
                <table class="table table-sm align-middle">
                    <thead class="table-light">
                        <tr><th>Code</th><th>Name</th><th>Aggregator</th><th></th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($employers as $employer): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($employer['code']); ?></td>
                            <td><?php echo htmlspecialchars($employer['name']); ?></td>
                            <td><?php echo htmlspecialchars($employer['aggregator_name'] ?? '-'); ?></td>
                            <td class="text-end"><a class="btn btn-sm btn-primary" href="/dsm_daily_tasks.php?process=edit_employer&employer_id=<?php echo (int) $employer['id']; ?>">Edit</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php if ($selectedEmployer): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h3 class="h6 mb-3">Edit selected employer: <?php echo htmlspecialchars($selectedEmployer['name']); ?></h3>
                <form method="post" class="row g-3">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                    <input type="hidden" name="action" value="update_employer">
                    <input type="hidden" name="employer_id" value="<?php echo (int) $selectedEmployer['id']; ?>">
                    <div class="col-md-4"><label class="form-label">Employer code</label><input class="form-control" name="code" required value="<?php echo htmlspecialchars($_POST['code'] ?? $selectedEmployer['code']); ?>"></div>
                    <div class="col-md-8"><label class="form-label">Employer name</label><input class="form-control" name="name" required value="<?php echo htmlspecialchars($_POST['name'] ?? $selectedEmployer['name']); ?>"></div>
                    <div class="col-md-4"><label class="form-label">SPOC name</label><input class="form-control" name="spoc_name" value="<?php echo htmlspecialchars($_POST['spoc_name'] ?? ($selectedEmployer['spoc_name'] ?? '')); ?>"></div>
                    <div class="col-md-4"><label class="form-label">SPOC mobile</label><input class="form-control" name="spoc_mobile" value="<?php echo htmlspecialchars($_POST['spoc_mobile'] ?? ($selectedEmployer['spoc_mobile'] ?? '')); ?>"></div>
                    <div class="col-md-4"><label class="form-label">SPOC email</label><input class="form-control" type="email" name="spoc_email" value="<?php echo htmlspecialchars($_POST['spoc_email'] ?? ($selectedEmployer['spoc_email'] ?? '')); ?>"></div>
                    <div class="col-md-6">
                        <label class="form-label">Aggregator</label>
                        <select class="form-select" name="aggregator_id">
                            <option value="">Select aggregator</option>
                            <?php foreach ($aggregators as $aggregator): ?>
                                <?php $selectedAgg = $_POST['aggregator_id'] ?? $selectedEmployer['aggregator_id']; ?>
                                <option value="<?php echo (int) $aggregator['id']; ?>" <?php echo ((string) $selectedAgg === (string) $aggregator['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($aggregator['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12"><button class="btn btn-primary" type="submit">Update employer</button></div>
                </form>
            </div>
        </div>
    <?php endif; ?>
<?php elseif (($process ?? '') === 'new_job_title'): ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h2 class="h5 mb-3">New job title</h2>
            <p class="text-muted small">Create a new job title under an employer. Date-based creation log is captured automatically.</p>
            <form method="post" class="row g-3">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                <input type="hidden" name="action" value="create_job_title">
                <div class="col-md-4"><label class="form-label">Job code</label><input class="form-control" name="job_code" required value="<?php echo htmlspecialchars($_POST['job_code'] ?? ''); ?>"></div>
                <div class="col-md-8"><label class="form-label">Job title</label><input class="form-control" name="job_title" required value="<?php echo htmlspecialchars($_POST['job_title'] ?? ''); ?>"></div>
                <div class="col-md-6">
                    <label class="form-label">Employer</label>
                    <select class="form-select" name="employer_id" required>
                        <option value="">Select employer</option>
                        <?php foreach ($employers as $employer): ?>
                            <option value="<?php echo (int) $employer['id']; ?>" <?php echo (($_POST['employer_id'] ?? '') == $employer['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($employer['name'] . ' (' . $employer['code'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3"><label class="form-label">Openings</label><input type="number" min="0" class="form-control" name="openings" value="<?php echo htmlspecialchars($_POST['openings'] ?? '0'); ?>"></div>
                <div class="col-md-3"><label class="form-label">Status</label><select class="form-select" name="status"><option value="active">Active</option><option value="inactive" <?php echo (($_POST['status'] ?? '') === 'inactive') ? 'selected' : ''; ?>>Inactive</option></select></div>
                <div class="col-md-6"><label class="form-label">Job location</label><input class="form-control" name="job_location" value="<?php echo htmlspecialchars($_POST['job_location'] ?? ''); ?>"></div>
                <div class="col-md-6"><label class="form-label">Job description</label><input class="form-control" name="job_description" value="<?php echo htmlspecialchars($_POST['job_description'] ?? ''); ?>"></div>
                <div class="col-12"><label class="form-label">Job details</label><textarea class="form-control" name="job_details" rows="3"><?php echo htmlspecialchars($_POST['job_details'] ?? ''); ?></textarea></div>
                <div class="col-12 d-flex gap-2"><button class="btn btn-primary" type="submit">Save job title</button><a class="btn btn-outline-secondary" href="/dsm_daily_tasks.php">Process home</a></div>
            </form>
        </div>
    </div>
<?php elseif (($process ?? '') === 'edit_job_title'): ?>
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <h2 class="h5 mb-3">Edit job title</h2>
            <form class="row g-2" method="get">
                <input type="hidden" name="process" value="edit_job_title">
                <div class="col-md-8"><input class="form-control" name="search" placeholder="Search employer/job title/job code" value="<?php echo htmlspecialchars($search); ?>"></div>
                <div class="col-md-4 d-flex gap-2"><button class="btn btn-outline-primary w-100" type="submit">Search</button><a class="btn btn-outline-secondary w-100" href="/dsm_daily_tasks.php?process=edit_job_title">Reset</a></div>
            </form>
            <div class="table-responsive mt-3">
                <table class="table table-sm align-middle">
                    <thead class="table-light"><tr><th>Job code</th><th>Job title</th><th>Employer</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($jobTitles as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['job_code']); ?></td>
                            <td><?php echo htmlspecialchars($item['job_title']); ?></td>
                            <td><?php echo htmlspecialchars($item['employer_name']); ?></td>
                            <td class="text-end"><a class="btn btn-sm btn-primary" href="/dsm_daily_tasks.php?process=edit_job_title&job_title_id=<?php echo (int) $item['id']; ?>">Edit</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php if ($selectedJobTitle): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h3 class="h6 mb-3">Edit selected job title: <?php echo htmlspecialchars($selectedJobTitle['job_title']); ?></h3>
                <p class="text-muted small">On save, previous values and new values are logged for this job title edit.</p>
                <form method="post" class="row g-3">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                    <input type="hidden" name="action" value="update_job_title">
                    <input type="hidden" name="job_title_id" value="<?php echo (int) $selectedJobTitle['id']; ?>">
                    <div class="col-md-4"><label class="form-label">Job code</label><input class="form-control" name="job_code" required value="<?php echo htmlspecialchars($_POST['job_code'] ?? $selectedJobTitle['job_code']); ?>"></div>
                    <div class="col-md-8"><label class="form-label">Job title</label><input class="form-control" name="job_title" required value="<?php echo htmlspecialchars($_POST['job_title'] ?? $selectedJobTitle['job_title']); ?>"></div>
                    <div class="col-md-6">
                        <label class="form-label">Employer</label>
                        <select class="form-select" name="employer_id" required>
                            <option value="">Select employer</option>
                            <?php foreach ($employers as $employer): ?>
                                <?php $selectedEmp = $_POST['employer_id'] ?? $selectedJobTitle['employer_id']; ?>
                                <option value="<?php echo (int) $employer['id']; ?>" <?php echo ((string) $selectedEmp === (string) $employer['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($employer['name'] . ' (' . $employer['code'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3"><label class="form-label">Openings</label><input type="number" min="0" class="form-control" name="openings" value="<?php echo htmlspecialchars($_POST['openings'] ?? (string) $selectedJobTitle['openings']); ?>"></div>
                    <div class="col-md-3"><label class="form-label">Status</label><select class="form-select" name="status"><option value="active" <?php echo (($_POST['status'] ?? $selectedJobTitle['status']) === 'active') ? 'selected' : ''; ?>>Active</option><option value="inactive" <?php echo (($_POST['status'] ?? $selectedJobTitle['status']) === 'inactive') ? 'selected' : ''; ?>>Inactive</option></select></div>
                    <div class="col-md-6"><label class="form-label">Job location</label><input class="form-control" name="job_location" value="<?php echo htmlspecialchars($_POST['job_location'] ?? ($selectedJobTitle['job_location'] ?? '')); ?>"></div>
                    <div class="col-md-6"><label class="form-label">Job description</label><input class="form-control" name="job_description" value="<?php echo htmlspecialchars($_POST['job_description'] ?? ($selectedJobTitle['job_description'] ?? '')); ?>"></div>
                    <div class="col-12"><label class="form-label">Job details</label><textarea class="form-control" rows="3" name="job_details"><?php echo htmlspecialchars($_POST['job_details'] ?? ($selectedJobTitle['job_details'] ?? '')); ?></textarea></div>
                    <div class="col-12"><button class="btn btn-primary" type="submit">Update job title</button></div>
                </form>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php include __DIR__ . '/partials/footer.php'; ?>
