<?php
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/masters.php';

require_admin();
$conn = db_connect();
$options = fetch_filter_options($conn);
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid request token.';
    } else {
        $action = $_POST['action'] ?? '';
        switch ($action) {
            case 'create_user':
                $username = trim($_POST['username'] ?? '');
                $password = $_POST['password'] ?? '';
                $role = $_POST['role'] === 'admin' ? 'admin' : 'editor';
                if ($username && $password) {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare('INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)');
                    $stmt->bind_param('sss', $username, $hash, $role);
                    $stmt->execute();
                    $message = 'User created successfully.';
                }
                break;
            case 'create_district':
                create_simple($conn, 'districts', $_POST['name'] ?? '', $message);
                break;
            case 'create_local_body_type':
                create_simple($conn, 'local_body_types', $_POST['name'] ?? '', $message);
                break;
            case 'create_block_panchayat':
                create_simple($conn, 'block_panchayats', $_POST['name'] ?? '', $message);
                break;
            case 'create_local_body':
                $name = trim($_POST['name'] ?? '');
                $district = (int) ($_POST['district_id'] ?? 0);
                $type = (int) ($_POST['local_body_type_id'] ?? 0);
                if ($name && $district && $type) {
                    $stmt = $conn->prepare('INSERT INTO local_bodies (name, district_id, local_body_type_id) VALUES (?, ?, ?)');
                    $stmt->bind_param('sii', $name, $district, $type);
                    $stmt->execute();
                    $message = 'Local body added.';
                }
                break;
            case 'create_job_station':
                $name = trim($_POST['name'] ?? '');
                $district = (int) ($_POST['district_id'] ?? 0);
                $block = (int) ($_POST['block_panchayat_id'] ?? 0);
                if ($name && $district) {
                    $stmt = $conn->prepare('INSERT INTO job_stations (name, district_id, block_panchayat_id) VALUES (?, ?, ?)');
                    $stmt->bind_param('sii', $name, $district, $block ?: null);
                    $stmt->execute();
                    $message = 'Job station added.';
                }
                break;
            case 'create_institution':
                $name = trim($_POST['name'] ?? '');
                $district = (int) ($_POST['district_id'] ?? 0);
                $category = trim($_POST['education_category'] ?? '');
                $type = trim($_POST['institution_type'] ?? '');
                if ($name && $district) {
                    $stmt = $conn->prepare('INSERT INTO academic_institutions (name, district_id, education_category, institution_type) VALUES (?, ?, ?, ?)');
                    $stmt->bind_param('siss', $name, $district, $category, $type);
                    $stmt->execute();
                    $message = 'Academic institution added.';
                }
                break;
            case 'create_course':
                $name = trim($_POST['name'] ?? '');
                $district = (int) ($_POST['district_id'] ?? 0);
                $category = trim($_POST['education_category'] ?? '');
                if ($name && $district) {
                    $stmt = $conn->prepare('INSERT INTO education_courses (name, district_id, education_category) VALUES (?, ?, ?)');
                    $stmt->bind_param('sis', $name, $district, $category);
                    $stmt->execute();
                    $message = 'Course/trade added.';
                }
                break;
            case 'create_cds':
                $name = trim($_POST['name'] ?? '');
                $district = (int) ($_POST['district_id'] ?? 0);
                $type = (int) ($_POST['local_body_type_id'] ?? 0);
                if ($name && $district && $type) {
                    $stmt = $conn->prepare('INSERT INTO cds_list (name, district_id, local_body_type_id) VALUES (?, ?, ?)');
                    $stmt->bind_param('sii', $name, $district, $type);
                    $stmt->execute();
                    $message = 'CDS entry added.';
                }
                break;
            case 'create_ads':
                $name = trim($_POST['name'] ?? '');
                $district = (int) ($_POST['district_id'] ?? 0);
                $type = (int) ($_POST['local_body_type_id'] ?? 0);
                $localBody = (int) ($_POST['local_body_id'] ?? 0);
                if ($name && $district && $type && $localBody) {
                    $stmt = $conn->prepare('INSERT INTO ads_list (name, district_id, local_body_type_id, local_body_id) VALUES (?, ?, ?, ?)');
                    $stmt->bind_param('siii', $name, $district, $type, $localBody);
                    $stmt->execute();
                    $message = 'ADS entry added.';
                }
                break;
        }
    }
}

$options = fetch_filter_options($conn);

include __DIR__ . '/partials/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 mb-1">Administration</h1>
        <p class="text-muted mb-0">Create users and manage master records securely.</p>
    </div>
    <a class="btn btn-outline-secondary" href="/">Back to site</a>
</div>
<?php if ($message): ?>
    <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>
<div class="row g-3">
    <div class="col-lg-6">
        <?php include __DIR__ . '/partials/card_user.php'; ?>
        <?php include __DIR__ . '/partials/card_geography.php'; ?>
        <?php include __DIR__ . '/partials/card_local_body.php'; ?>
    </div>
    <div class="col-lg-6">
        <?php include __DIR__ . '/partials/card_jobs.php'; ?>
        <?php include __DIR__ . '/partials/card_academics.php'; ?>
        <?php include __DIR__ . '/partials/card_cds_ads.php'; ?>
    </div>
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>
<?php
function create_simple(mysqli $conn, string $table, string $name, string &$message): void
{
    $trimmed = trim($name);
    if ($trimmed === '') {
        return;
    }
    $stmt = $conn->prepare("INSERT INTO {$table} (name) VALUES (?)");
    $stmt->bind_param('s', $trimmed);
    $stmt->execute();
    $message = ucfirst(str_replace('_', ' ', $table)) . ' entry added.';
}
?>
