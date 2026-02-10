<?php
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/dsm_daily_tasks.php';

require_auth();
$conn = db_connect();
$user = current_user();

$errors = [];
$taskId = isset($_GET['task_id']) ? (int) $_GET['task_id'] : 0;
$isEdit = $taskId > 0;
$task = $isEdit ? fetch_dsm_daily_task_by_id($conn, $taskId) : null;

if ($isEdit && !$task) {
    $errors[] = 'Task not found.';
    $isEdit = false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request token.';
    } else {
        $action = $_POST['action'] ?? '';
        if ($action === 'create_task') {
            create_dsm_daily_task($conn, $_POST, (int) ($user['id'] ?? 0), $errors);
            if (empty($errors)) {
                header('Location: /dsm_daily_tasks.php?task_created=1');
                exit();
            }
            $isEdit = false;
            $task = null;
        } elseif ($action === 'update_task') {
            $postedTaskId = (int) ($_POST['task_id'] ?? 0);
            update_dsm_daily_task($conn, $postedTaskId, $_POST, (int) ($user['id'] ?? 0), $errors);
            if (empty($errors)) {
                header('Location: /dsm_daily_tasks.php?task_updated=1');
                exit();
            }
            $task = fetch_dsm_daily_task_by_id($conn, $postedTaskId);
            $isEdit = $task !== null;
        }
    }
}

$taskTypes = fetch_dsm_task_types($conn);
$aggregators = fetch_aggregators_for_dsm($conn);
$allEmployers = fetch_all_employers_for_dsm($conn);
$allJobTitles = fetch_all_job_titles_for_dsm($conn);

include __DIR__ . '/partials/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1"><?php echo $isEdit ? 'Edit DSM Daily Task' : 'New DSM Daily Task'; ?></h1>
        <p class="text-muted mb-0">Capture DSM daily task details and meeting outcomes.</p>
    </div>
    <a class="btn btn-outline-secondary" href="/dsm_daily_tasks.php">Back to Task List</a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php $formTask = $task ?: []; ?>
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="post" class="row g-3">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
            <input type="hidden" name="action" value="<?php echo $isEdit ? 'update_task' : 'create_task'; ?>">
            <?php if ($isEdit): ?>
                <input type="hidden" name="task_id" value="<?php echo (int) ($formTask['id'] ?? 0); ?>">
            <?php endif; ?>

            <div class="col-md-3">
                <label class="form-label">Date</label>
                <input class="form-control" type="date" name="task_date" required value="<?php echo htmlspecialchars($_POST['task_date'] ?? ($formTask['task_date'] ?? date('Y-m-d'))); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Task type</label>
                <select class="form-select" name="task_type_id" required>
                    <option value="">Select task type</option>
                    <?php $selectedTaskType = $_POST['task_type_id'] ?? ($formTask['task_type_id'] ?? ''); ?>
                    <?php foreach ($taskTypes as $taskType): ?>
                        <option value="<?php echo (int) $taskType['id']; ?>" <?php echo ((string) $selectedTaskType === (string) $taskType['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($taskType['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Job Fair number</label>
                <input class="form-control" name="job_fair_number" value="<?php echo htmlspecialchars($_POST['job_fair_number'] ?? ($formTask['job_fair_number'] ?? '')); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Duration</label>
                <input class="form-control" name="duration" placeholder="e.g. 45 mins" value="<?php echo htmlspecialchars($_POST['duration'] ?? ($formTask['duration'] ?? '')); ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Task title</label>
                <input class="form-control" name="task_title" required value="<?php echo htmlspecialchars($_POST['task_title'] ?? ($formTask['task_title'] ?? '')); ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Meeting owner</label>
                <input class="form-control" name="meeting_owner" value="<?php echo htmlspecialchars($_POST['meeting_owner'] ?? ($formTask['meeting_owner'] ?? '')); ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Aggregator</label>
                <select class="form-select" name="aggregator_id" id="aggregator_id">
                    <option value="">Select aggregator</option>
                    <?php foreach ($aggregators as $aggregator): ?>
                        <?php $selected = $_POST['aggregator_id'] ?? ($formTask['aggregator_id'] ?? ''); ?>
                        <option value="<?php echo (int) $aggregator['id']; ?>" <?php echo ((string) $selected === (string) $aggregator['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($aggregator['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Employer</label>
                <select class="form-select" name="employer_id" id="employer_id">
                    <option value="">Select employer</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Job title</label>
                <select class="form-select" name="job_title_id" id="job_title_id">
                    <option value="">Select job title</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Meeting members</label>
                <textarea class="form-control" name="meeting_members" rows="2"><?php echo htmlspecialchars($_POST['meeting_members'] ?? ($formTask['meeting_members'] ?? '')); ?></textarea>
            </div>
            <div class="col-md-3">
                <label class="form-label">Result</label>
                <?php $selectedResult = $_POST['result'] ?? ($formTask['result'] ?? 'Pending'); ?>
                <select class="form-select" name="result">
                    <option value="Closed" <?php echo $selectedResult === 'Closed' ? 'selected' : ''; ?>>Closed</option>
                    <option value="Pending" <?php echo $selectedResult === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="Cancelled" <?php echo $selectedResult === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Call status</label>
                <?php $selectedCall = $_POST['call_status'] ?? ($formTask['call_status'] ?? ''); ?>
                <select class="form-select" name="call_status">
                    <option value="">Select call status</option>
                    <option value="Connected" <?php echo $selectedCall === 'Connected' ? 'selected' : ''; ?>>Connected</option>
                    <option value="Not responding" <?php echo $selectedCall === 'Not responding' ? 'selected' : ''; ?>>Not responding</option>
                    <option value="Rescheduled" <?php echo $selectedCall === 'Rescheduled' ? 'selected' : ''; ?>>Rescheduled</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Task details</label>
                <textarea class="form-control" name="task_details" rows="3"><?php echo htmlspecialchars($_POST['task_details'] ?? ($formTask['task_details'] ?? '')); ?></textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label">Result details</label>
                <textarea class="form-control" name="result_details" rows="3"><?php echo htmlspecialchars($_POST['result_details'] ?? ($formTask['result_details'] ?? '')); ?></textarea>
            </div>
            <div class="col-12 d-flex gap-2">
                <button class="btn btn-primary" type="submit"><?php echo $isEdit ? 'Update Task' : 'Save Task'; ?></button>
                <a class="btn btn-outline-secondary" href="/dsm_daily_tasks.php">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const employers = <?php echo json_encode($allEmployers, JSON_UNESCAPED_UNICODE); ?>;
    const jobTitles = <?php echo json_encode($allJobTitles, JSON_UNESCAPED_UNICODE); ?>;

    const aggregatorSelect = document.getElementById('aggregator_id');
    const employerSelect = document.getElementById('employer_id');
    const jobTitleSelect = document.getElementById('job_title_id');

    if (!aggregatorSelect || !employerSelect || !jobTitleSelect) {
        return;
    }

    const selectedEmployerId = '<?php echo htmlspecialchars((string) ($_POST['employer_id'] ?? ($formTask['employer_id'] ?? ''))); ?>';
    const selectedJobTitleId = '<?php echo htmlspecialchars((string) ($_POST['job_title_id'] ?? ($formTask['job_title_id'] ?? ''))); ?>';

    function populateEmployers() {
        const aggregatorId = aggregatorSelect.value;
        employerSelect.innerHTML = '<option value="">Select employer</option>';

        employers
            .filter((employer) => aggregatorId === '' || String(employer.aggregator_id) === aggregatorId)
            .forEach((employer) => {
                const option = document.createElement('option');
                option.value = employer.id;
                option.textContent = employer.name;
                if (String(employer.id) === selectedEmployerId) {
                    option.selected = true;
                }
                employerSelect.appendChild(option);
            });
    }

    function populateJobTitles() {
        const employerId = employerSelect.value;
        jobTitleSelect.innerHTML = '<option value="">Select job title</option>';

        jobTitles
            .filter((jobTitle) => employerId !== '' && String(jobTitle.employer_id) === employerId)
            .forEach((jobTitle) => {
                const option = document.createElement('option');
                option.value = jobTitle.id;
                option.textContent = jobTitle.job_title;
                if (String(jobTitle.id) === selectedJobTitleId) {
                    option.selected = true;
                }
                jobTitleSelect.appendChild(option);
            });
    }

    aggregatorSelect.addEventListener('change', function () {
        populateEmployers();
        populateJobTitles();
    });

    employerSelect.addEventListener('change', populateJobTitles);

    populateEmployers();
    populateJobTitles();
})();
</script>
<?php include __DIR__ . '/partials/footer.php'; ?>
