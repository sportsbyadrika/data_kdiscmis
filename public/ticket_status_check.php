<?php
require_once __DIR__ . '/../src/tickets.php';

$conn = db_connect();
$categories = fetch_ticket_categories($conn);

$categoryId = isset($_GET['category']) && $_GET['category'] !== '' ? (int) $_GET['category'] : null;
$statusFilter = $_GET['status'] ?? '';
$allowedStatuses = ['all', 'pending', 'resolved'];
if (!in_array($statusFilter, $allowedStatuses, true)) {
    $statusFilter = '';
}
$searchFilters = [
    'issue_id' => trim($_GET['issue_id'] ?? ''),
    'event_name' => trim($_GET['event_name'] ?? ''),
    'reported' => trim($_GET['reported'] ?? ''),
    'mobile' => trim($_GET['mobile'] ?? ''),
];

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 8;
$tickets = [];
$totalTickets = 0;
$totalPages = 1;
$searchErrors = [];
$isSearchRequest = isset($_GET['search']);
if ($isSearchRequest) {
    if (!$categoryId) {
        $searchErrors[] = 'Please select an issue category.';
    }
    if ($statusFilter === '') {
        $searchErrors[] = 'Please select a ticket status.';
    }
    if (empty($searchErrors)) {
        $ticketResult = fetch_ticket_list($conn, $categoryId, $statusFilter, $page, $perPage, $searchFilters);
        $tickets = $ticketResult['rows'];
        $totalTickets = $ticketResult['total'];
        $totalPages = max(1, (int) ceil($totalTickets / $perPage));
    }
}
$newTracker = $_GET['new'] ?? '';

include __DIR__ . '/partials/header.php';
?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Ticket Status Check</h1>
        <p class="text-muted mb-0">Search by issue details to view ticket status updates.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-outline-secondary" href="/tickets.php">Category Summary</a>
        <a class="btn btn-primary" href="/new_ticket.php">New Ticket</a>
    </div>
</div>

<form class="card border-0 shadow-sm mb-4" method="get">
    <div class="card-body">
        <h2 class="h6 mb-3">Search Tickets</h2>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Issue Category</label>
                <select class="form-select" name="category">
                    <option value="">Select category</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo (int) $category['id']; ?>" <?php echo $categoryId === (int) $category['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Ticket Status</label>
                <select class="form-select" name="status">
                    <option value="">Select status</option>
                    <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All statuses</option>
                    <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="resolved" <?php echo $statusFilter === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Issue ID (optional)</label>
                <input class="form-control" name="issue_id" value="<?php echo htmlspecialchars($searchFilters['issue_id']); ?>" placeholder="e.g., ISS-20240501-00001">
            </div>
            <div class="col-md-4">
                <label class="form-label">Event Name</label>
                <input class="form-control" name="event_name" value="<?php echo htmlspecialchars($searchFilters['event_name']); ?>" placeholder="Search by event">
            </div>
            <div class="col-md-4">
                <label class="form-label">Reported By Name</label>
                <input class="form-control" name="reported" value="<?php echo htmlspecialchars($searchFilters['reported']); ?>" placeholder="Search by name">
            </div>
            <div class="col-md-4">
                <label class="form-label">Reported By Mobile Number</label>
                <input class="form-control" name="mobile" value="<?php echo htmlspecialchars($searchFilters['mobile']); ?>" placeholder="Search by mobile">
            </div>
            <div class="col-12 d-flex justify-content-end gap-2">
                <a class="btn btn-outline-secondary" href="/ticket_status_check.php">Reset</a>
                <button class="btn btn-primary" type="submit" name="search" value="1">Search</button>
            </div>
        </div>
    </div>
</form>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <?php if (!$isSearchRequest): ?>
            <div class="alert alert-info mb-0">Select an issue category and status, then click search to view tickets.</div>
        <?php elseif (!empty($searchErrors)): ?>
            <div class="alert alert-warning mb-0">
                <ul class="mb-0">
                    <?php foreach ($searchErrors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php else: ?>
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <div>
                    <h2 class="h5 mb-1">Ticket List</h2>
                    <p class="text-muted small mb-0"><?php echo $totalTickets; ?> results found.</p>
                </div>
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
                            <th scope="col">Event Name</th>
                            <th scope="col">Institution/SDPK center</th>
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
                                <td><?php echo htmlspecialchars($ticket['event_name'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($ticket['reference_institution']); ?></td>
                                <td><?php echo htmlspecialchars($ticket['reported_by']); ?></td>
                                <td class="text-muted small issue-details-cell">
                                    <span class="d-block text-truncate"><?php echo htmlspecialchars($ticket['issue_details']); ?></span>
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
                                        data-event-name="<?php echo htmlspecialchars($ticket['event_name'] ?? ''); ?>"
                                        data-reference="<?php echo htmlspecialchars($ticket['reference_institution']); ?>"
                                        data-reported="<?php echo htmlspecialchars($ticket['reported_by']); ?>"
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
                    $queryBase = array_filter([
                        'category' => $categoryId,
                        'status' => $statusFilter,
                        'issue_id' => $searchFilters['issue_id'],
                        'event_name' => $searchFilters['event_name'],
                        'reported' => $searchFilters['reported'],
                        'mobile' => $searchFilters['mobile'],
                        'search' => '1',
                    ], static fn($value): bool => $value !== null && $value !== '');
                    $previousPage = max(1, $page - 1);
                    $nextPage = min($totalPages, $page + 1);
                    $previousQuery = http_build_query(array_merge($queryBase, ['page' => $previousPage]));
                    $nextQuery = http_build_query(array_merge($queryBase, ['page' => $nextPage]));
                    ?>
                    <li class="page-item <?php echo $page === 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="/ticket_status_check.php?<?php echo htmlspecialchars($previousQuery); ?>">Previous</a>
                    </li>
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <?php $pageQuery = http_build_query(array_merge($queryBase, ['page' => $i])); ?>
                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="/ticket_status_check.php?<?php echo htmlspecialchars($pageQuery); ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?php echo $page === $totalPages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="/ticket_status_check.php?<?php echo htmlspecialchars($nextQuery); ?>">Next</a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
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
                        <div class="text-muted small">Event Name</div>
                        <div class="fw-semibold" id="detailEventName"></div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small">Institution/SDPK center</div>
                        <div class="fw-semibold" id="detailReference"></div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small">Reported By</div>
                        <div class="fw-semibold" id="detailReported"></div>
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
        const statusValue = trigger.dataset.status || '-';
        document.getElementById('detailTicketId').textContent = trigger.dataset.ticketId || '-';
        document.getElementById('detailCreatedAt').textContent = trigger.dataset.createdAt || '-';
        document.getElementById('detailCategory').textContent = trigger.dataset.category || '-';
        document.getElementById('detailStatus').innerHTML = statusValue === '-'
            ? '-'
            : `<span class="badge ${statusValue === 'Resolved' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning'}">${statusValue}</span>`;
        document.getElementById('detailEventName').textContent = trigger.dataset.eventName || '-';
        document.getElementById('detailReference').textContent = trigger.dataset.reference || '-';
        document.getElementById('detailReported').textContent = trigger.dataset.reported || '-';
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
