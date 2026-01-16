<?php
require_once __DIR__ . '/../src/tickets.php';

$conn = db_connect();
$categorySummary = fetch_ticket_category_summary($conn);

include __DIR__ . '/partials/header.php';
?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Tickets</h1>
        <p class="text-muted mb-0">Track issues by category and view status updates.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-outline-secondary" href="/ticket_status_check.php">Ticket Status Check</a>
        <a class="btn btn-primary" href="/new_ticket.php">New Ticket</a>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <h2 class="h5 mb-3">Issue Category Summary</h2>
        <?php if (empty($categorySummary)): ?>
            <div class="alert alert-info mb-0">No issue categories are available yet.</div>
        <?php else: ?>
            <div class="card-table">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">Issue Category</th>
                            <th scope="col">Total</th>
                            <th scope="col">Resolved</th>
                            <th scope="col">Pending</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categorySummary as $summary): ?>
                            <tr>
                                <td class="fw-semibold"><?php echo htmlspecialchars($summary['name']); ?></td>
                                <td>
                                    <a class="text-decoration-none" href="/ticket_status_check.php?category=<?php echo urlencode((string) $summary['id']); ?>">
                                        <?php echo (int) $summary['total_count']; ?>
                                    </a>
                                </td>
                                <td>
                                    <span class="text-success"><?php echo (int) $summary['resolved_count']; ?></span>
                                </td>
                                <td>
                                    <span class="text-warning"><?php echo (int) $summary['pending_count']; ?></span>
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
