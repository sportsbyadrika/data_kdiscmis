<?php
require_once __DIR__ . '/../src/tickets.php';

$conn = db_connect();
$categorySummary = fetch_ticket_category_summary($conn);
$categoryId = isset($_GET['category']) ? (int) $_GET['category'] : null;
$status = $_GET['status'] ?? 'all';
$allowedStatuses = ['all', 'pending', 'resolved'];
if (!in_array($status, $allowedStatuses, true)) {
    $status = 'all';
}

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 8;
$ticketResult = fetch_ticket_list($conn, $categoryId ?: null, $status, $page, $perPage);
$tickets = $ticketResult['rows'];
$totalTickets = $ticketResult['total'];
$totalPages = max(1, (int) ceil($totalTickets / $perPage));
$selectedCategoryName = '';
foreach ($categorySummary as $category) {
    if ($categoryId && (int) $category['id'] === $categoryId) {
        $selectedCategoryName = $category['name'];
        break;
    }
}
$newTracker = $_GET['new'] ?? '';

include __DIR__ . '/partials/header.php';
?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Tickets</h1>
        <p class="text-muted mb-0">Track issues by category, status, and resolution progress.</p>
    </div>
    <a class="btn btn-primary" href="/new_ticket.php">New Ticket</a>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <h2 class="h5 mb-3">Issue Category Summary</h2>
        <?php if (empty($categorySummary)): ?>
            <div class="alert alert-info mb-0">No issue categories are available yet.</div>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($categorySummary as $summary): ?>
                    <div class="col-md-6 col-xl-4">
                        <div class="border rounded-3 p-3 h-100">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h3 class="h6 mb-1"><?php echo htmlspecialchars($summary['name']); ?></h3>
                                    <p class="text-muted small mb-0">Issue tracker overview</p>
                                </div>
                                <span class="badge bg-light text-primary">Category</span>
                            </div>
                            <div class="d-flex flex-column gap-2">
                                <a class="summary-pill" href="/tickets.php?category=<?php echo urlencode((string) $summary['id']); ?>&status=all">
                                    <span>Total</span>
                                    <span class="fw-semibold"><?php echo (int) $summary['total_count']; ?></span>
                                </a>
                                <a class="summary-pill" href="/tickets.php?category=<?php echo urlencode((string) $summary['id']); ?>&status=resolved">
                                    <span>Resolved</span>
                                    <span class="fw-semibold text-success"><?php echo (int) $summary['resolved_count']; ?></span>
                                </a>
                                <a class="summary-pill" href="/tickets.php?category=<?php echo urlencode((string) $summary['id']); ?>&status=pending">
                                    <span>Pending</span>
                                    <span class="fw-semibold text-warning"><?php echo (int) $summary['pending_count']; ?></span>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <div>
                <h2 class="h5 mb-1">Ticket List</h2>
                <p class="text-muted small mb-0">
                    <?php if ($selectedCategoryName): ?>
                        Showing <strong><?php echo htmlspecialchars($selectedCategoryName); ?></strong> issues
                    <?php else: ?>
                        Showing all categories
                    <?php endif; ?>
                    • Status: <strong><?php echo htmlspecialchars(ucfirst($status)); ?></strong>
                </p>
            </div>
            <?php if ($categoryId || $status !== 'all'): ?>
                <a class="btn btn-sm btn-outline-secondary" href="/tickets.php">Clear filters</a>
            <?php endif; ?>
        </div>
        <?php if (empty($tickets)): ?>
            <div class="alert alert-info mb-0">No tickets found for the selected filters.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">Issue ID</th>
                            <th scope="col">Date &amp; Time</th>
                            <th scope="col">Category</th>
                            <th scope="col">Reference Institution</th>
                            <th scope="col">Reported By</th>
                            <th scope="col">Issue Details</th>
                            <th scope="col">Status</th>
                            <th scope="col"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tickets as $ticket): ?>
                            <?php
                            $attachments = fetch_ticket_attachments($conn, (int) $ticket['id']);
                            $attachmentsJson = htmlspecialchars(json_encode(array_map(
                                static fn(array $attachment): array => [
                                    'name' => $attachment['file_name'],
                                    'path' => $attachment['file_path'],
                                    'type' => $attachment['file_type'],
                                ],
                                $attachments
                            ), JSON_UNESCAPED_SLASHES));
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($ticket['tracker_number'] ?: ('#' . $ticket['id'])); ?></td>
                                <td><?php echo date('d M Y, g:i A', strtotime($ticket['created_at'])); ?></td>
                                <td><?php echo htmlspecialchars($ticket['category_name']); ?></td>
                                <td><?php echo htmlspecialchars($ticket['reference_institution']); ?></td>
                                <td>
                                    <div class="fw-semibold"><?php echo htmlspecialchars($ticket['reported_by']); ?></div>
                                    <div class="text-muted small">+91 <?php echo htmlspecialchars($ticket['reported_mobile']); ?></div>
                                </td>
                                <td class="text-muted small">
                                    <?php echo htmlspecialchars(mb_strimwidth($ticket['issue_details'], 0, 90, '...')); ?>
                                </td>
                                <td>
                                    <?php if ($ticket['status'] === 'Resolved'): ?>
                                        <span class="badge bg-success-subtle text-success">Resolved</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning-subtle text-warning">Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <button
                                        class="btn btn-sm btn-outline-primary view-ticket"
                                        data-bs-toggle="modal"
                                        data-bs-target="#ticketDetailModal"
                                        data-ticket-id="<?php echo htmlspecialchars($ticket['tracker_number'] ?: ('#' . $ticket['id'])); ?>"
                                        data-created-at="<?php echo htmlspecialchars(date('d M Y, g:i A', strtotime($ticket['created_at']))); ?>"
                                        data-category="<?php echo htmlspecialchars($ticket['category_name']); ?>"
                                        data-reference="<?php echo htmlspecialchars($ticket['reference_institution']); ?>"
                                        data-reported="<?php echo htmlspecialchars($ticket['reported_by']); ?>"
                                        data-mobile="<?php echo htmlspecialchars($ticket['reported_mobile']); ?>"
                                        data-details="<?php echo htmlspecialchars($ticket['issue_details']); ?>"
                                        data-status="<?php echo htmlspecialchars($ticket['status']); ?>"
                                        data-resolution="<?php echo htmlspecialchars($ticket['resolution_text'] ?? 'Awaiting resolution.'); ?>"
                                        data-attachments="<?php echo $attachmentsJson; ?>"
                                    >
                                        View
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <nav aria-label="Tickets pagination">
                <ul class="pagination justify-content-end">
                    <?php
                    $queryBase = ['category' => $categoryId, 'status' => $status];
                    $previousPage = max(1, $page - 1);
                    $nextPage = min($totalPages, $page + 1);
                    $previousQuery = http_build_query(array_merge($queryBase, ['page' => $previousPage]));
                    $nextQuery = http_build_query(array_merge($queryBase, ['page' => $nextPage]));
                    ?>
                    <li class="page-item <?php echo $page === 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="/tickets.php?<?php echo htmlspecialchars($previousQuery); ?>">Previous</a>
                    </li>
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <?php $pageQuery = http_build_query(array_merge($queryBase, ['page' => $i])); ?>
                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="/tickets.php?<?php echo htmlspecialchars($pageQuery); ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?php echo $page === $totalPages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="/tickets.php?<?php echo htmlspecialchars($nextQuery); ?>">Next</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="ticketDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ticket Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="text-muted small">Issue ID</div>
                        <div class="fw-semibold" id="detailTicketId"></div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small">Reported on</div>
                        <div class="fw-semibold" id="detailCreatedAt"></div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small">Category</div>
                        <div class="fw-semibold" id="detailCategory"></div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small">Status</div>
                        <div class="fw-semibold" id="detailStatus"></div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small">Reference Institution</div>
                        <div class="fw-semibold" id="detailReference"></div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small">Reported By</div>
                        <div class="fw-semibold" id="detailReported"></div>
                        <div class="text-muted small" id="detailMobile"></div>
                    </div>
                    <div class="col-12">
                        <div class="text-muted small">Issue Details</div>
                        <div class="border rounded-3 p-3 bg-light" id="detailIssue"></div>
                    </div>
                    <div class="col-12">
                        <div class="text-muted small">Resolution</div>
                        <div class="border rounded-3 p-3 bg-light" id="detailResolution"></div>
                    </div>
                    <div class="col-12">
                        <div class="text-muted small">Attachments</div>
                        <div id="detailAttachments" class="d-flex flex-column gap-2"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="newTicketModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ticket Submitted</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2">Your ticket has been created successfully.</p>
                <div class="alert alert-success mb-0">
                    Tracker Number: <strong id="newTicketTracker"></strong>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const detailModal = document.getElementById('ticketDetailModal');
    detailModal.addEventListener('show.bs.modal', (event) => {
        const trigger = event.relatedTarget;
        if (!trigger) {
            return;
        }
        document.getElementById('detailTicketId').textContent = trigger.dataset.ticketId || '-';
        document.getElementById('detailCreatedAt').textContent = trigger.dataset.createdAt || '-';
        document.getElementById('detailCategory').textContent = trigger.dataset.category || '-';
        document.getElementById('detailStatus').textContent = trigger.dataset.status || '-';
        document.getElementById('detailReference').textContent = trigger.dataset.reference || '-';
        document.getElementById('detailReported').textContent = trigger.dataset.reported || '-';
        document.getElementById('detailMobile').textContent = trigger.dataset.mobile ? `+91 ${trigger.dataset.mobile}` : '';
        document.getElementById('detailIssue').textContent = trigger.dataset.details || '-';
        document.getElementById('detailResolution').textContent = trigger.dataset.resolution || '-';

        const attachments = trigger.dataset.attachments ? JSON.parse(trigger.dataset.attachments) : [];
        const attachmentsContainer = document.getElementById('detailAttachments');
        attachmentsContainer.innerHTML = '';
        if (attachments.length === 0) {
            attachmentsContainer.innerHTML = '<span class="text-muted">No attachments provided.</span>';
        } else {
            attachments.forEach((attachment) => {
                const link = document.createElement('a');
                link.className = 'btn btn-sm btn-outline-secondary text-start';
                link.href = attachment.path;
                link.target = '_blank';
                link.rel = 'noopener';
                link.textContent = `${attachment.name} (${attachment.type})`;
                attachmentsContainer.appendChild(link);
            });
        }
    });

    <?php if (!empty($newTracker)): ?>
    const trackerModal = new bootstrap.Modal(document.getElementById('newTicketModal'));
    document.getElementById('newTicketTracker').textContent = <?php echo json_encode($newTracker); ?>;
    trackerModal.show();
    <?php endif; ?>
</script>
<?php include __DIR__ . '/partials/footer.php'; ?>
