<?php
require_once __DIR__ . '/../src/tickets.php';
require_once __DIR__ . '/../src/masters.php';

$conn = db_connect();
$categories = fetch_ticket_categories($conn);
$districts = fetch_named($conn, 'districts');

$errors = [];
$formData = [
    'category_id' => '',
    'district_id' => '',
    'reference_institution' => '',
    'event_name' => '',
    'reported_by' => '',
    'reported_mobile' => '',
    'reported_email' => '',
    'issue_details' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['category_id'] = $_POST['category_id'] ?? '';
    $formData['district_id'] = $_POST['district_id'] ?? '';
    $formData['reference_institution'] = trim($_POST['reference_institution'] ?? '');
    $formData['event_name'] = trim($_POST['event_name'] ?? '');
    $formData['reported_by'] = trim($_POST['reported_by'] ?? '');
    $formData['reported_mobile'] = trim($_POST['reported_mobile'] ?? '');
    $formData['reported_email'] = trim($_POST['reported_email'] ?? '');
    $formData['issue_details'] = trim($_POST['issue_details'] ?? '');

    if ($formData['category_id'] === '') {
        $errors[] = 'Please select a category of issue.';
    }
    if ($formData['district_id'] === '') {
        $errors[] = 'Please select a district.';
    }
    if ($formData['reference_institution'] === '') {
        $errors[] = 'Please provide the reference institution.';
    }
    if ($formData['event_name'] === '') {
        $errors[] = 'Please enter the event name.';
    }
    if ($formData['reported_by'] === '') {
        $errors[] = 'Please enter the reported by name.';
    }
    if ($formData['reported_mobile'] === '' || !preg_match('/^\d{10}$/', $formData['reported_mobile'])) {
        $errors[] = 'Please enter a valid 10-digit mobile number.';
    }
    if ($formData['reported_email'] !== '' && !filter_var($formData['reported_email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if ($formData['issue_details'] === '') {
        $errors[] = 'Please describe the issue details.';
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
        $tracker = create_ticket($conn, [
            'category_id' => (int) $formData['category_id'],
            'district_id' => (int) $formData['district_id'],
            'reference_institution' => $formData['reference_institution'],
            'event_name' => $formData['event_name'],
            'reported_by' => $formData['reported_by'],
            'reported_mobile' => $formData['reported_mobile'],
            'reported_email' => $formData['reported_email'],
            'issue_details' => $formData['issue_details'],
        ], $uploadedAttachments);

        header('Location: /ticket_status_check.php?new=' . urlencode($tracker));
        exit;
    }
}

include __DIR__ . '/partials/header.php';
?>
<div class="row mb-4">
    <div class="col">
        <h1 class="h3">New Ticket</h1>
        <p class="text-muted">Capture a new issue and receive a tracker number for follow up.</p>
    </div>
    <div class="col-auto">
        <a class="btn btn-outline-secondary" href="/ticket_status_check.php">Back to Ticket Status Check</a>
    </div>
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

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="post" enctype="multipart/form-data">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Category of Issue <span class="text-danger">*</span></label>
                    <select class="form-select" name="category_id" required>
                        <option value="">Select a category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo (int) $category['id']; ?>" <?php echo (string) $category['id'] === $formData['category_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">District <span class="text-danger">*</span></label>
                    <select class="form-select" name="district_id" required>
                        <option value="">Select a district</option>
                        <?php foreach ($districts as $district): ?>
                            <option value="<?php echo (int) $district['id']; ?>" <?php echo (string) $district['id'] === $formData['district_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($district['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Institution/SDPK center <span class="text-danger">*</span></label>
                    <input class="form-control" name="reference_institution" value="<?php echo htmlspecialchars($formData['reference_institution']); ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Event Name <span class="text-danger">*</span></label>
                    <input class="form-control" name="event_name" value="<?php echo htmlspecialchars($formData['event_name']); ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Reported By Name <span class="text-danger">*</span></label>
                    <input class="form-control" name="reported_by" value="<?php echo htmlspecialchars($formData['reported_by']); ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Reported By Mobile Number <span class="text-danger">*</span></label>
                    <input class="form-control" name="reported_mobile" value="<?php echo htmlspecialchars($formData['reported_mobile']); ?>" maxlength="10" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Reported By Email</label>
                    <input class="form-control" type="email" name="reported_email" value="<?php echo htmlspecialchars($formData['reported_email']); ?>">
                </div>
                <div class="col-12">
                    <label class="form-label">Issue Details <span class="text-danger">*</span></label>
                    <textarea class="form-control" name="issue_details" rows="4" required><?php echo htmlspecialchars($formData['issue_details']); ?></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label">Attachment (images or PDF only)</label>
                    <input class="form-control" type="file" name="attachments[]" accept="image/*,application/pdf" multiple>
                    <div class="form-text">You can upload multiple attachments. Supported types: JPG, PNG, GIF, WEBP, PDF.</div>
                </div>
                <div class="col-12 d-flex justify-content-end">
                    <button class="btn btn-primary">Submit Ticket</button>
                </div>
            </div>
        </form>
    </div>
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>
