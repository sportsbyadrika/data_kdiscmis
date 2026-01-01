<?php
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/masters.php';

require_auth();
$conn = db_connect();
$options = fetch_filter_options($conn);
$message = '';
$user = current_user();
$childRole = child_role_for($user['role']);
$dashboardCounts = user_dashboard_counts($conn, $user);
$canManageMasters = $user['role'] === ROLE_SUPER_ADMIN;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid request token.';
    } else {
        $action = $_POST['action'] ?? '';
        switch ($action) {
            case 'create_user':
                $message = create_subordinate_user($conn, $user, $_POST);
                break;
            case 'create_district':
            case 'create_local_body_type':
            case 'create_block_panchayat':
            case 'create_local_body':
            case 'create_job_station':
            case 'create_facilitation_center':
            case 'create_qualification_category':
            case 'create_academic_authority':
            case 'create_institution':
            case 'create_course':
            case 'create_cds':
            case 'create_ads':
            case 'create_sdpk_center':
                if ($canManageMasters) {
                    $message = handle_master_creation($conn, $action, $_POST, $message, $options);
                } else {
                    $message = 'You do not have permission to manage master data.';
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
        <h1 class="h4 mb-1">Dashboard</h1>
        <p class="text-muted mb-0">Manage users and data according to your access level.</p>
    </div>
    <div class="text-end">
        <span class="badge bg-light text-primary border"><?php echo htmlspecialchars(role_label($user['role'])); ?></span>
        <div class="small text-muted">Signed in as <?php echo htmlspecialchars($user['name'] ?? $user['mobile']); ?></div>
    </div>
</div>
<?php if ($message): ?>
    <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<?php if (!empty($dashboardCounts)): ?>
    <div class="row g-3 mb-3">
        <?php foreach ($dashboardCounts as $card): ?>
            <div class="col-md-4">
                <div class="card h-100 table-card">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge text-primary bg-light badge-outline"><?php echo htmlspecialchars($card['label']); ?></span>
                            <span class="fs-4 fw-bold text-primary"><?php echo $card['count']; ?></span>
                        </div>
                        <p class="text-muted small mb-0"><?php echo htmlspecialchars($card['description']); ?></p>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="alert alert-secondary">No subordinate users found for your level yet.</div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-lg-6">
        <?php include __DIR__ . '/partials/card_user.php'; ?>
        <?php if ($canManageMasters): ?>
            <?php include __DIR__ . '/partials/card_geography.php'; ?>
            <?php include __DIR__ . '/partials/card_local_body.php'; ?>
        <?php else: ?>
            <div class="card table-card mb-3">
                <div class="card-body">
                    <h2 class="h6 mb-2">Master data</h2>
                    <p class="text-muted mb-0">Master data creation is restricted to Super Admin users.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <div class="col-lg-6">
        <?php if ($canManageMasters): ?>
            <?php include __DIR__ . '/partials/card_jobs.php'; ?>
            <?php include __DIR__ . '/partials/card_sdpk.php'; ?>
            <?php include __DIR__ . '/partials/card_academics.php'; ?>
            <?php include __DIR__ . '/partials/card_cds_ads.php'; ?>
        <?php endif; ?>
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

function child_role_for(string $role): ?string
{
    return match ($role) {
        ROLE_SUPER_ADMIN => ROLE_STATE_USER,
        ROLE_STATE_USER => ROLE_DISTRICT_USER,
        ROLE_DISTRICT_USER => ROLE_LOCALBODY_USER,
        default => null,
    };
}

function create_subordinate_user(mysqli $conn, array $currentUser, array $input): string
{
    $childRole = child_role_for($currentUser['role']);
    if ($childRole === null) {
        return 'You cannot create users at this level.';
    }

    $name = trim($input['name'] ?? '');
    $email = trim($input['email'] ?? '');
    $mobile = trim($input['mobile'] ?? '');
    $password = $input['password'] ?? '';
    $status = ($input['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';
    $districtId = (int) ($input['district_id'] ?? 0);
    $requiresDistrict = in_array($childRole, [ROLE_DISTRICT_USER, ROLE_LOCALBODY_USER], true);

    if ($childRole === ROLE_LOCALBODY_USER && $districtId === 0 && $currentUser['district_id']) {
        $districtId = (int) $currentUser['district_id'];
    }

    if ($name === '' || $email === '' || $mobile === '' || $password === '') {
        return 'All user fields are required.';
    }

    if ($requiresDistrict && $districtId === 0) {
        return 'District selection is required for this user role.';
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $districtValue = $districtId ?: null;

    try {
        $stmt = $conn->prepare(
            'INSERT INTO users (name, email, mobile, password_hash, role, district_id, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param(
            'sssssisi',
            $name,
            $email,
            $mobile,
            $hash,
            $childRole,
            $districtValue,
            $status,
            $currentUser['id']
        );
        $stmt->execute();
    } catch (mysqli_sql_exception $exception) {
        if ($exception->getCode() === 1062) {
            return 'A user with this mobile number already exists.';
        }
        return 'Unable to create user. Please try again.';
    }

    return ucfirst(str_replace('_', ' ', $childRole)) . ' created successfully.';
}

function handle_master_creation(mysqli $conn, string $action, array $input, string $message, array $options): string
{
    switch ($action) {
        case 'create_district':
            create_simple($conn, 'districts', $input['name'] ?? '', $message);
            break;
        case 'create_local_body_type':
            create_simple($conn, 'local_body_types', $input['name'] ?? '', $message);
            break;
        case 'create_block_panchayat':
            create_simple($conn, 'block_panchayats', $input['name'] ?? '', $message);
            break;
        case 'create_local_body':
            $lbCode = trim($input['lb_code'] ?? '');
            $blockLbCode = trim($input['block_lb_code'] ?? '');
            $name = trim($input['name'] ?? '');
            $district = (int) ($input['district_id'] ?? 0);
            $type = (int) ($input['local_body_type_id'] ?? 0);
            if ($lbCode && $name && $district && $type) {
                $blockLbValue = $blockLbCode !== '' ? $blockLbCode : null;
                $stmt = $conn->prepare('INSERT INTO local_bodies (lb_code, block_lb_code, name, district_id, local_body_type_id) VALUES (?, ?, ?, ?, ?)');
                $stmt->bind_param('sssii', $lbCode, $blockLbValue, $name, $district, $type);
                $stmt->execute();
                $message = 'Local body added.';
            }
            break;
        case 'create_job_station':
            $name = trim($input['name'] ?? '');
            $district = (int) ($input['district_id'] ?? 0);
            $block = (int) ($input['block_panchayat_id'] ?? 0);
            $latitude = (float) ($input['latitude'] ?? 0);
            $longitude = (float) ($input['longitude'] ?? 0);
            if ($name && $district) {
                $stmt = $conn->prepare('INSERT INTO job_stations (name, district_id, latitude, longitude, block_panchayat_id) VALUES (?, ?, ?, ?, ?)');
                $stmt->bind_param('siddi', $name, $district, $latitude, $longitude, $block ?: null);
                $stmt->execute();
                $message = 'Job station added.';
            }
            break;
        case 'create_facilitation_center':
            $name = trim($input['name'] ?? '');
            $district = (int) ($input['district_id'] ?? 0);
            $block = (int) ($input['block_panchayat_id'] ?? 0);
            $localBody = (int) ($input['local_body_id'] ?? 0);
            $latitude = (float) ($input['latitude'] ?? 0);
            $longitude = (float) ($input['longitude'] ?? 0);
            if ($name && $district && $localBody) {
                $blockValue = $block ?: null;
                $stmt = $conn->prepare('INSERT INTO facilitation_centers (name, district_id, latitude, longitude, block_panchayat_id, local_body_id) VALUES (?, ?, ?, ?, ?, ?)');
                $stmt->bind_param('siddii', $name, $district, $latitude, $longitude, $blockValue, $localBody);
                $stmt->execute();
                $message = 'Facilitation center added.';
            }
            break;
        case 'create_qualification_category':
            create_simple($conn, 'qualification_categories', $input['name'] ?? '', $message);
            break;
        case 'create_academic_authority':
            $code = trim($input['code'] ?? '');
            $name = trim($input['name'] ?? '');
            $authorityType = trim($input['authority_type'] ?? '');
            if ($code !== '' && $name !== '' && $authorityType !== '') {
                $stmt = $conn->prepare('INSERT INTO academic_authorities (code, name, authority_type) VALUES (?, ?, ?)');
                $stmt->bind_param('sss', $code, $name, $authorityType);
                $stmt->execute();
                $message = 'Academic authority added.';
            }
            break;
        case 'create_institution':
            $name = trim($input['name'] ?? '');
            $district = (int) ($input['district_id'] ?? 0);
            $qualificationCategory = (int) ($input['qualification_category'] ?? 0);
            $type = trim($input['institution_type'] ?? '');
            $latitude = (float) ($input['latitude'] ?? 0);
            $longitude = (float) ($input['longitude'] ?? 0);
            if ($name && $district) {
                $qualificationValue = $qualificationCategory ?: null;
                $stmt = $conn->prepare('INSERT INTO academic_institutions (name, district_id, latitude, longitude, qualification_category, institution_type) VALUES (?, ?, ?, ?, ?, ?)');
                $stmt->bind_param('siddis', $name, $district, $latitude, $longitude, $qualificationValue, $type);
                $stmt->execute();
                $message = 'Academic institution added.';
            }
            break;
        case 'create_course':
            $name = trim($input['name'] ?? '');
            $district = (int) ($input['district_id'] ?? 0);
            $qualificationCategory = (int) ($input['qualification_category'] ?? 0);
            if ($name && $district) {
                $qualificationValue = $qualificationCategory ?: null;
                $stmt = $conn->prepare('INSERT INTO education_courses (name, district_id, qualification_category) VALUES (?, ?, ?)');
                $stmt->bind_param('sii', $name, $district, $qualificationValue);
                $stmt->execute();
                $message = 'Course/trade added.';
            }
            break;
        case 'create_cds':
            $name = trim($input['name'] ?? '');
            $district = (int) ($input['district_id'] ?? 0);
            $type = (int) ($input['local_body_type_id'] ?? 0);
            if ($name && $district && $type) {
                $stmt = $conn->prepare('INSERT INTO cds_list (name, district_id, local_body_type_id) VALUES (?, ?, ?)');
                $stmt->bind_param('sii', $name, $district, $type);
                $stmt->execute();
                $message = 'CDS entry added.';
            }
            break;
        case 'create_ads':
            $name = trim($input['name'] ?? '');
            $district = (int) ($input['district_id'] ?? 0);
            $type = (int) ($input['local_body_type_id'] ?? 0);
            $localBody = (int) ($input['local_body_id'] ?? 0);
            if ($name && $district && $type && $localBody) {
                $stmt = $conn->prepare('INSERT INTO ads_list (name, district_id, local_body_type_id, local_body_id) VALUES (?, ?, ?, ?)');
                $stmt->bind_param('siii', $name, $district, $type, $localBody);
                $stmt->execute();
                $message = 'ADS entry added.';
            }
            break;
        case 'create_sdpk_center':
            $code = trim($input['code'] ?? '');
            $name = trim($input['name'] ?? '');
            $address = trim($input['address'] ?? '');
            $district = (int) ($input['district_id'] ?? 0);
            $latitude = (float) ($input['latitude'] ?? 0);
            $longitude = (float) ($input['longitude'] ?? 0);
            $activeStatus = ($input['active_status'] ?? '1') === '1' ? 1 : 0;

            if ($code && $name && $address && $district) {
                $stmt = $conn->prepare('INSERT INTO sdpk_centers (code, name, address, district_id, latitude, longitude, active_status) VALUES (?, ?, ?, ?, ?, ?, ?)');
                $stmt->bind_param('sssiddi', $code, $name, $address, $district, $latitude, $longitude, $activeStatus);
                $stmt->execute();
                $message = 'SDPK center added.';
            }
            break;
    }

    return $message;
}

function user_dashboard_counts(mysqli $conn, array $user): array
{
    return match ($user['role']) {
        ROLE_SUPER_ADMIN => [
            [
                'label' => 'State level users',
                'count' => count_users_by_role($conn, ROLE_STATE_USER),
                'description' => 'Total state-level accounts managed by the super admin.',
            ],
            [
                'label' => 'District level users',
                'count' => count_users_by_role($conn, ROLE_DISTRICT_USER),
                'description' => 'District user accounts created by state-level teams.',
            ],
            [
                'label' => 'Localbody level users',
                'count' => count_users_by_role($conn, ROLE_LOCALBODY_USER),
                'description' => 'Localbody operators nested under district managers.',
            ],
        ],
        ROLE_STATE_USER => [
            [
                'label' => 'District level users',
                'count' => count_created_users($conn, (int) $user['id'], ROLE_DISTRICT_USER),
                'description' => 'District accounts created by you for your state.',
            ],
            [
                'label' => 'Localbody level users',
                'count' => count_localbody_under_state($conn, (int) $user['id']),
                'description' => 'Localbody accounts created under your district teams.',
            ],
        ],
        ROLE_DISTRICT_USER => [
            [
                'label' => 'Localbody level users',
                'count' => count_created_users($conn, (int) $user['id'], ROLE_LOCALBODY_USER),
                'description' => 'Localbody users operating in your district.',
            ],
        ],
        default => [],
    };
}

function count_users_by_role(mysqli $conn, string $role): int
{
    $stmt = $conn->prepare('SELECT COUNT(*) AS total FROM users WHERE role = ?');
    $stmt->bind_param('s', $role);
    $stmt->execute();
    $result = $stmt->get_result();

    return (int) $result->fetch_assoc()['total'];
}

function count_created_users(mysqli $conn, int $creatorId, string $role): int
{
    $stmt = $conn->prepare('SELECT COUNT(*) AS total FROM users WHERE created_by = ? AND role = ?');
    $stmt->bind_param('is', $creatorId, $role);
    $stmt->execute();
    $result = $stmt->get_result();

    return (int) $result->fetch_assoc()['total'];
}

function count_localbody_under_state(mysqli $conn, int $stateUserId): int
{
    $stmt = $conn->prepare(
        'SELECT COUNT(*) AS total FROM users WHERE role = ? AND created_by IN (SELECT id FROM users WHERE created_by = ? AND role = ?)' 
    );
    $localbodyRole = ROLE_LOCALBODY_USER;
    $districtRole = ROLE_DISTRICT_USER;
    $stmt->bind_param('sis', $localbodyRole, $stateUserId, $districtRole);
    $stmt->execute();
    $result = $stmt->get_result();

    return (int) $result->fetch_assoc()['total'];
}

function role_label(string $role): string
{
    return match ($role) {
        ROLE_SUPER_ADMIN => 'Super admin',
        ROLE_STATE_USER => 'State level user',
        ROLE_DISTRICT_USER => 'District level user',
        ROLE_LOCALBODY_USER => 'Localbody level user',
        default => ucfirst($role),
    };
}
?>
