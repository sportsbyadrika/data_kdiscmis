<?php
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/tickets.php';
require_once __DIR__ . '/../src/masters.php';

require_auth([ROLE_STATE_USER, ROLE_SUPER_ADMIN]);
$conn = db_connect();
$categories = fetch_ticket_categories($conn);

$status = $_GET['status'] ?? 'all';
$allowedStatuses = ['all', 'pending', 'resolved'];
if (!in_array($status, $allowedStatuses, true)) {
    $status = 'all';
}

$categoryId = isset($_GET['category']) && $_GET['category'] !== '' ? (int) $_GET['category'] : null;
$searchFilters = [
    'reference' => trim($_GET['reference'] ?? ''),
    'event_name' => trim($_GET['event_name'] ?? ''),
    'reported' => trim($_GET['reported'] ?? ''),
    'mobile' => trim($_GET['mobile'] ?? ''),
];

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;
$ticketResult = fetch_ticket_list($conn, $categoryId, $status, $page, $perPage, $searchFilters);
$tickets = $ticketResult['rows'];
$totalTickets = $ticketResult['total'];
$totalPages = max(1, (int) ceil($totalTickets / $perPage));

$message = '';
$errors = [];
if (isset($_GET['updated']) && $_GET['updated'] === '1') {
    $message = 'Ticket updated successfully.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request token.';
    } else {
        $action = $_POST['action'] ?? '';
        if ($action === 'update_ticket') {
            $ticketId = (int) ($_POST['ticket_id'] ?? 0);
            $statusInput = $_POST['status'] ?? 'Pending';
            $statusValue = $statusInput === 'Resolved' ? 'Resolved' : 'Pending';
            $resolutionText = trim($_POST['resolution_text'] ?? '');
            $eventName = trim($_POST['event_name'] ?? '');

            if ($ticketId === 0) {
                $errors[] = 'Invalid ticket selected.';
            }

            $uploadedAttachments = [];
            if (!empty($_FILES['attachments']['name'][0])) {
                $uploadDir = __DIR__ . '/uploads/tickets';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $allowedMime = ['application/pdf', 'image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                foreach ($_FILES['attachments']['name'] as $index => $name) {
                    $error = $_FILES['attachments']['error'][$index];
                    if ($error !== UPLOAD_ERR_OK) {
                        $errors[] = 'One of the attachments failed to upload.';
                        continue;
                    }
                    $tmpPath = $_FILES['attachments']['tmp_name'][$index];
                    $mimeType = $finfo->file($tmpPath);
                    if (!in_array($mimeType, $allowedMime, true)) {
                        $errors[] = 'Attachments must be an image or PDF file.';
                        continue;
                    }
                    $extension = pathinfo($name, PATHINFO_EXTENSION);
                    $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($name, PATHINFO_FILENAME));
                    $fileName = sprintf('%s_%s.%s', $safeName, uniqid('', true), $extension ?: 'file');
                    $destination = $uploadDir . '/' . $fileName;
                    if (!move_uploaded_file($tmpPath, $destination)) {
                        $errors[] = 'Unable to save one of the attachments.';
                        continue;
                    }
                    $uploadedAttachments[] = [
                        'file_name' => $name,
                        'file_path' => '/uploads/tickets/' . $fileName,
                        'file_type' => strtoupper($extension ?: $mimeType),
                    ];
                }
            }

            if (empty($errors)) {
                update_ticket_resolution($conn, $ticketId, $statusValue, $resolutionText, $eventName);
                append_ticket_attachments($conn, $ticketId, $uploadedAttachments);
                $redirectQuery = $_SERVER['QUERY_STRING'];
                $redirectUrl = '/tickets_manage.php' . ($redirectQuery ? '?' . $redirectQuery . '&updated=1' : '?updated=1');
                header('Location: ' . $redirectUrl);
                exit;
            }
        }
    }
}

include __DIR__ . '/partials/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Ticket Management</h1>
        <p class="text-muted mb-0">Search, filter, and update ticket resolutions.</p>
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

<form class="card border-0 shadow-sm mb-4" method="get">
    <div class="card-body">
        <h2 class="h6 mb-3">Filter Tickets</h2>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Issue Category</label>
                <select class="form-select" name="category">
                    <option value="">All categories</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo (int) $category['id']; ?>" <?php echo $categoryId === (int) $category['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Institution/SDPK center</label>
                <input class="form-control" name="reference" value="<?php echo htmlspecialchars($searchFilters['reference']); ?>" placeholder="Search by institution">
            </div>
            <div class="col-md-4">
                <label class="form-label">Event Name</label>
                <input class="form-control" name="event_name" value="<?php echo htmlspecialchars($searchFilters['event_name']); ?>" placeholder="Search by event">
            </div>
            <div class="col-md-4">
                <label class="form-label">Reported By</label>
                <input class="form-control" name="reported" value="<?php echo htmlspecialchars($searchFilters['reported']); ?>" placeholder="Search by name">
            </div>
            <div class="col-md-4">
                <label class="form-label">Reported Mobile</label>
                <input class="form-control" name="mobile" value="<?php echo htmlspecialchars($searchFilters['mobile']); ?>" placeholder="Search by mobile">
            </div>
            <div class="col-md-4">
                <label class="form-label">Status</label>
                <select class="form-select" name="status">
                    <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All</option>
                    <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="resolved" <?php echo $status === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                </select>
            </div>
            <div class="col-12 d-flex justify-content-end gap-2">
                <a class="btn btn-outline-secondary" href="/tickets_manage.php">Reset</a>
                <button class="btn btn-primary" type="submit">Apply Filters</button>
            </div>
        </div>
    </div>
</form>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
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
                                <td>
                                    <div class="fw-semibold"><?php echo htmlspecialchars($ticket['reported_by']); ?></div>
                                    <div class="text-muted small">+91 <?php echo htmlspecialchars($ticket['reported_mobile']); ?></div>
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
                                        class="btn btn-sm btn-outline-primary edit-ticket"
                                        data-bs-toggle="modal"
                                        data-bs-target="#ticketEditModal"
                                        data-ticket-id="<?php echo (int) $ticket['id']; ?>"
                                        data-tracker="<?php echo htmlspecialchars($ticket['tracker_number'] ?: ('#' . $ticket['id'])); ?>"
                                        data-event-name="<?php echo htmlspecialchars($ticket['event_name'] ?? ''); ?>"
                                        data-status="<?php echo htmlspecialchars($ticket['status']); ?>"
                                        data-resolution="<?php echo htmlspecialchars($ticket['resolution_text'] ?? ''); ?>"
                                        data-attachments="<?php echo $attachmentsJson; ?>"
                                    >
                                        Edit
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
                        'status' => $status,
                        'reference' => $searchFilters['reference'],
                        'event_name' => $searchFilters['event_name'],
                        'reported' => $searchFilters['reported'],
                        'mobile' => $searchFilters['mobile'],
                    ], static fn($value): bool => $value !== null && $value !== '');
                    $previousPage = max(1, $page - 1);
                    $nextPage = min($totalPages, $page + 1);
                    $previousQuery = http_build_query(array_merge($queryBase, ['page' => $previousPage]));
                    $nextQuery = http_build_query(array_merge($queryBase, ['page' => $nextPage]));
                    ?>
                    <li class="page-item <?php echo $page === 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="/tickets_manage.php?<?php echo htmlspecialchars($previousQuery); ?>">Previous</a>
                    </li>
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <?php $pageQuery = http_build_query(array_merge($queryBase, ['page' => $i])); ?>
                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="/tickets_manage.php?<?php echo htmlspecialchars($pageQuery); ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?php echo $page === $totalPages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="/tickets_manage.php?<?php echo htmlspecialchars($nextQuery); ?>">Next</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="ticketEditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Ticket</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    <input type="hidden" name="action" value="update_ticket">
                    <input type="hidden" name="ticket_id" id="editTicketId" value="">
                    <div class="mb-3">
                        <label class="form-label">Ticket ID</label>
                        <div class="form-control-plaintext fw-semibold" id="editTicketTracker"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Event Name</label>
                        <input class="form-control" name="event_name" id="editTicketEventName" value="">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status" id="editTicketStatus">
                            <option value="Pending">Pending</option>
                            <option value="Resolved">Resolved</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Resolution Details</label>
                        <textarea class="form-control" name="resolution_text" id="editTicketResolution" rows="4"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Attachments (images or PDF only)</label>
                        <input class="form-control" type="file" name="attachments[]" accept="image/*,application/pdf" multiple>
                        <div class="form-text">Upload new files to append to the ticket.</div>
                    </div>
                    <div>
                        <label class="form-label">Existing Attachments</label>
                        <div id="editTicketAttachments" class="d-flex flex-column gap-2"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const editModal = document.getElementById('ticketEditModal');
    editModal.addEventListener('show.bs.modal', (event) => {
        const trigger = event.relatedTarget;
        if (!trigger) {
            return;
        }
        document.getElementById('editTicketId').value = trigger.dataset.ticketId || '';
        document.getElementById('editTicketTracker').textContent = trigger.dataset.tracker || '-';
        document.getElementById('editTicketEventName').value = trigger.dataset.eventName || '';
        document.getElementById('editTicketStatus').value = trigger.dataset.status || 'Pending';
        document.getElementById('editTicketResolution').value = trigger.dataset.resolution || '';

        const attachments = trigger.dataset.attachments ? JSON.parse(trigger.dataset.attachments) : [];
        const attachmentsContainer = document.getElementById('editTicketAttachments');
        attachmentsContainer.innerHTML = '';
        if (attachments.length === 0) {
            attachmentsContainer.innerHTML = '<span class="text-muted">No attachments uploaded yet.</span>';
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
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>
