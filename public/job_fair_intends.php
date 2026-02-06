<?php
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/job_fair_intends.php';

require_auth();
$conn = db_connect();

$filters = [
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? '',
    'search' => trim($_GET['search'] ?? ''),
];

$message = '';
if (isset($_GET['created'])) {
    $message = 'Job fair intend captured successfully.';
}
if (isset($_GET['updated'])) {
    $message = 'Job fair intend updated successfully.';
}

$intends = fetch_job_fair_intends($conn, $filters);

include __DIR__ . '/partials/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Job Fair Intend</h1>
        <p class="text-muted mb-0">Capture intend details and track job fair location selections.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-primary" href="/job_fair_intend_entry.php">New Job Fair Intend</a>
        <a class="btn btn-outline-secondary" href="/admin.php">Back to Dashboard</a>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<form class="card border-0 shadow-sm mb-4" method="get">
    <div class="card-body">
        <h2 class="h6 mb-3">Filter Intends</h2>
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Intend Date From</label>
                <input class="form-control" type="date" name="date_from" value="<?php echo htmlspecialchars($filters['date_from']); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Intend Date To</label>
                <input class="form-control" type="date" name="date_to" value="<?php echo htmlspecialchars($filters['date_to']); ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Search</label>
                <input class="form-control" name="search" placeholder="Intend or job fair number" value="<?php echo htmlspecialchars($filters['search']); ?>">
            </div>
            <div class="col-md-2 d-flex align-items-end gap-2">
                <a class="btn btn-outline-secondary w-100" href="/job_fair_intends.php">Reset</a>
                <button class="btn btn-primary w-100" type="submit">Apply</button>
            </div>
        </div>
    </div>
</form>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h2 class="h5 mb-1">Intend List</h2>
                <p class="text-muted small mb-0"><?php echo count($intends); ?> records found.</p>
            </div>
        </div>
        <?php if (empty($intends)): ?>
            <div class="alert alert-info mb-0">No intends found for the selected filters.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">Intend #</th>
                            <th scope="col">Intend Date</th>
                            <th scope="col">Reference Committee #</th>
                            <th scope="col">Reference Date</th>
                            <th scope="col">Reference Job Fair #</th>
                            <th scope="col">Job Fair Date</th>
                            <th scope="col">Locations</th>
                            <th scope="col"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($intends as $intend): ?>
                            <tr>
                                <td><?php echo (int) $intend['id']; ?></td>
                                <td><?php echo htmlspecialchars($intend['intend_date']); ?></td>
                                <td><?php echo htmlspecialchars($intend['reference_committee_number']); ?></td>
                                <td><?php echo htmlspecialchars($intend['reference_date'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($intend['reference_job_fair_number']); ?></td>
                                <td><?php echo htmlspecialchars($intend['job_fair_date'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($intend['location_count']); ?></td>
                                <td class="text-end">
                                    <?php
                                    $editParams = $_GET;
                                    $editParams['intend_id'] = (int) $intend['id'];
                                    ?>
                                    <a class="btn btn-sm btn-outline-primary" href="/job_fair_intend_entry.php?<?php echo htmlspecialchars(http_build_query($editParams)); ?>">Edit</a>
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
