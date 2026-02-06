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
if (isset($_GET['created'])) {
    $message = 'Job fair strategy meeting created successfully.';
}
if (isset($_GET['updated'])) {
    $message = 'Job fair strategy meeting updated successfully.';
}

$tasks = fetch_job_fair_daily_tasks($conn, $filters);
include __DIR__ . '/partials/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Job Fair Strategy</h1>
        <p class="text-muted mb-0">Review strategy meetings and track job fair preparation notes.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-primary" href="/job_fair_strategy_entry.php">New Strategy Meeting</a>
        <a class="btn btn-outline-secondary" href="/admin.php">Back to Dashboard</a>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<form class="card border-0 shadow-sm mb-4" method="get">
    <div class="card-body">
        <h2 class="h6 mb-3">Filter Strategies</h2>
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
                <h2 class="h5 mb-1">Strategy List</h2>
                <p class="text-muted small mb-0"><?php echo count($tasks); ?> records found.</p>
            </div>
        </div>
        <?php if (empty($tasks)): ?>
            <div class="alert alert-info mb-0">No strategies found for the selected filters.</div>
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
                                    <a class="btn btn-sm btn-outline-primary" href="/job_fair_strategy_entry.php?<?php echo htmlspecialchars(http_build_query($editParams)); ?>">Edit</a>
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
