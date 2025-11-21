<?php
require_once __DIR__ . '/../src/masters.php';
require_once __DIR__ . '/../src/auth.php';

$conn = db_connect();
$groups = master_groups();
$requestedGroup = $_GET['group'] ?? '';
$activeGroup = in_array($requestedGroup, $groups, true) ? $requestedGroup : '';
$counts = fetch_counts($conn, $activeGroup ?: null);
include __DIR__ . '/partials/header.php';
?>
<div class="row align-items-center mb-4">
    <div class="col">
        <h1 class="h3 mb-0">Master Directory</h1>
        <p class="text-muted mb-1">Discover public master data and manage them with secure administrator access.</p>
        <?php if ($activeGroup): ?>
            <span class="badge bg-light text-primary border">Showing <?php echo htmlspecialchars($activeGroup); ?> masters</span>
        <?php endif; ?>
    </div>
    <?php if (is_admin()): ?>
        <div class="col-auto">
            <a class="btn btn-outline-primary" href="/admin.php">Go to Admin</a>
        </div>
    <?php endif; ?>
</div>
<div class="row g-3">
    <?php foreach ($counts as $card): ?>
        <div class="col-md-4">
            <div class="card h-100 table-card">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="badge text-primary bg-light badge-outline">Master</span>
                        <span class="fs-4 fw-bold text-primary"><?php echo $card['count']; ?></span>
                    </div>
                    <h2 class="h5 mb-2"><?php echo htmlspecialchars($card['label']); ?></h2>
                    <p class="text-muted small mb-3">Browse and search within the <?php echo strtolower(htmlspecialchars($card['label'])); ?> catalogue.</p>
                    <div class="mt-auto">
                        <a class="btn btn-primary w-100" href="/master.php?type=<?php echo urlencode($card['table']); ?>">View <?php echo htmlspecialchars($card['label']); ?></a>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>
