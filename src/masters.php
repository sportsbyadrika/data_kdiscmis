<?php

require_once __DIR__ . '/db.php';

function fetch_counts(mysqli $conn, ?string $group = null): array
{
    $definitions = master_definitions();
    $counts = [];
    $normalizedGroup = $group ? strtolower($group) : null;

    foreach ($definitions as $definition) {
        if ($normalizedGroup && strtolower($definition['group']) !== $normalizedGroup) {
            continue;
        }

        $table = $definition['table'];
        $result = $conn->query("SELECT COUNT(*) as total FROM {$table}");
        $counts[] = [
            'table' => $table,
            'label' => $definition['title'],
            'count' => (int) $result->fetch_assoc()['total'],
            'group' => $definition['group'],
        ];
    }

    return $counts;
}

function fetch_filter_options(mysqli $conn): array
{
    return [
        'districts' => fetch_named($conn, 'districts'),
        'local_body_types' => fetch_named($conn, 'local_body_types'),
        'qualification_categories' => fetch_named($conn, 'qualification_categories'),
        'institution_types' => fetch_distinct($conn, 'academic_institutions', 'institution_type'),
        'authority_types' => fetch_distinct($conn, 'academic_authorities', 'authority_type'),
        'local_bodies' => fetch_named($conn, 'local_bodies'),
        'block_panchayats' => fetch_named($conn, 'block_panchayats'),
        'sdpk_phases' => fetch_distinct($conn, 'sdpk_centers', 'phase'),
        'teams' => fetch_named($conn, 'teams'),
    ];
}

function fetch_named(mysqli $conn, string $table): array
{
    $result = $conn->query("SELECT id, name FROM {$table} ORDER BY name ASC");
    return $result->fetch_all(MYSQLI_ASSOC);
}

function fetch_distinct(mysqli $conn, string $table, string $column): array
{
    $stmt = $conn->prepare(
        "SELECT DISTINCT {$column} AS name FROM {$table} " .
        "WHERE {$column} IS NOT NULL AND {$column} <> '' ORDER BY {$column}"
    );
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = $result->fetch_all(MYSQLI_ASSOC);

    return array_map(
        static fn(array $row): array => ['id' => $row['name'], 'name' => $row['name']],
        $rows
    );
}

function master_definitions(): array
{
    return [
        'teams' => [
            'title' => 'Teams',
            'group' => 'Administration',
            'table' => 'teams',
            'filters' => [],
            'columns' => ['name' => 'Team name'],
        ],
        'districts' => [
            'title' => 'Districts',
            'group' => 'Local body',
            'table' => 'districts',
            'filters' => [],
            'columns' => ['name' => 'District'],
        ],
        'local_bodies' => [
            'title' => 'Local Bodies',
            'group' => 'Local body',
            'table' => 'local_bodies',
            'filters' => ['district_id' => 'District', 'local_body_type_id' => 'Type'],
            'columns' => [
                'lb_code' => 'LB Code',
                'block_lb_code' => 'Block LB Code',
                'name' => 'Local Body',
                'district_name' => 'District',
                'type_name' => 'Type',
            ],
        ],
        'job_stations' => [
            'title' => 'Job Stations',
            'group' => 'Local body',
            'table' => 'job_stations',
            'filters' => ['district_id' => 'District', 'block_panchayat_id' => 'Block Panchayat'],
            'columns' => [
                'name' => 'Job Station',
                'district_name' => 'District',
                'block_panchayat_name' => 'Block Panchayat',
                'latitude' => 'Latitude',
                'longitude' => 'Longitude',
            ],
        ],
        'sdpk_centers' => [
            'title' => 'SDPK Centers',
            'group' => 'Local body',
            'table' => 'sdpk_centers',
            'filters' => ['district_id' => 'District', 'phase' => 'Phase'],
            'multi_filters' => ['phase'],
            'columns' => [
                'code' => 'Code',
                'name' => 'Center Name',
                'address' => 'Address',
                'district_name' => 'District',
                'phase' => 'Phase',
                'latitude' => 'Latitude',
                'longitude' => 'Longitude',
                'active_status_label' => 'Active Status',
            ],
        ],
        'facilitation_centers' => [
            'title' => 'Facilitation Centers',
            'group' => 'Local body',
            'table' => 'facilitation_centers',
            'filters' => [
                'district_id' => 'District',
                'block_panchayat_id' => 'Block Panchayat',
                'local_body_id' => 'Local Body',
            ],
            'columns' => [
                'name' => 'Facilitation Center',
                'district_name' => 'District',
                'block_panchayat_name' => 'Block Panchayat',
                'local_body_name' => 'Local Body',
                'latitude' => 'Latitude',
                'longitude' => 'Longitude',
            ],
        ],
        'qualification_categories' => [
            'title' => 'Qualification Categories',
            'group' => 'Academic',
            'table' => 'qualification_categories',
            'filters' => [],
            'columns' => ['name' => 'Qualification Category'],
        ],
        'academic_institutions' => [
            'title' => 'Academic Institutions',
            'group' => 'Academic',
            'table' => 'academic_institutions',
            'filters' => ['district_id' => 'District', 'qualification_category' => 'Qualification Category', 'institution_type' => 'Institution Type'],
            'columns' => [
                'name' => 'Institution',
                'district_name' => 'District',
                'qualification_category_name' => 'Qualification Category',
                'institution_type' => 'Type',
                'latitude' => 'Latitude',
                'longitude' => 'Longitude',
            ],
        ],
        'academic_authorities' => [
            'title' => 'Academic Authorities',
            'group' => 'Academic',
            'table' => 'academic_authorities',
            'filters' => ['district_id' => 'District', 'authority_type' => 'Type (Category)'],
            'columns' => [
                'code' => 'Code',
                'name' => 'Authority Name',
                'authority_type' => 'Authority Type',
                'district_name' => 'District',
                'year_established' => 'Year of Establishment',
                'sub_category' => 'Sub Category',
            ],
        ],
        'education_courses' => [
            'title' => 'Education Courses/Trades',
            'group' => 'Academic',
            'table' => 'education_courses',
            'filters' => ['district_id' => 'District', 'qualification_category' => 'Qualification Category'],
            'columns' => ['name' => 'Course/Trade', 'district_name' => 'District', 'qualification_category_name' => 'Qualification Category'],
        ],
        'cds_list' => [
            'title' => 'CDS',
            'group' => 'Kudumbasree',
            'table' => 'cds_list',
            'filters' => ['district_id' => 'District', 'local_body_type_id' => 'Local Body Type'],
            'columns' => ['name' => 'CDS', 'district_name' => 'District', 'type_name' => 'Local Body Type'],
        ],
        'ads_list' => [
            'title' => 'ADS',
            'group' => 'Kudumbasree',
            'table' => 'ads_list',
            'filters' => ['district_id' => 'District', 'local_body_type_id' => 'Local Body Type', 'local_body_id' => 'Local Body'],
            'columns' => ['name' => 'ADS', 'district_name' => 'District', 'type_name' => 'Local Body Type', 'local_body_name' => 'Local Body'],
        ],
    ];
}

function master_groups(): array
{
    $definitions = master_definitions();
    $groups = [];

    foreach ($definitions as $definition) {
        $groups[$definition['group']] = true;
    }

    return array_keys($groups);
}

function fetch_master_rows(mysqli $conn, string $key, array $filters, string $search = ''): array
{
    $definitions = master_definitions();
    if (!isset($definitions[$key])) {
        return [];
    }

    $def = $definitions[$key];
    $params = [];
    $conditions = [];
    $types = '';

    foreach ($filters as $field => $value) {
        $values = is_array($value) ? array_values(array_filter($value, static fn($entry): bool => $entry !== '' && $entry !== null)) : [$value];
        if (empty($values) || (count($values) === 1 && $values[0] === '')) {
            continue;
        }
        $isIdField = str_ends_with($field, '_id') || $field === 'qualification_category' || $field === 'phase';
        if (count($values) === 1) {
            $conditions[] = "{$field} = ?";
            $types .= $isIdField ? 'i' : 's';
            $params[] = $isIdField ? (int) $values[0] : $values[0];
            continue;
        }

        $placeholders = implode(', ', array_fill(0, count($values), '?'));
        $conditions[] = "{$field} IN ({$placeholders})";
        foreach ($values as $entry) {
            $types .= $isIdField ? 'i' : 's';
            $params[] = $isIdField ? (int) $entry : $entry;
        }
    }

    if ($search !== '') {
        $conditions[] = 'name LIKE ?';
        $types .= 's';
        $params[] = '%' . $search . '%';
    }

    $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
    $query = build_master_query($key, $where);

    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_all(MYSQLI_ASSOC);
}

function build_master_query(string $key, string $where): string
{
    switch ($key) {
        case 'teams':
            return "SELECT id, name FROM teams {$where} ORDER BY name";
        case 'local_bodies':
            return "SELECT lb.id, lb.lb_code, lb.block_lb_code, lb.name, d.name AS district_name, lbt.name AS type_name FROM local_bodies lb " .
                "JOIN districts d ON lb.district_id = d.id " .
                "JOIN local_body_types lbt ON lb.local_body_type_id = lbt.id {$where} ORDER BY lb.name";
        case 'job_stations':
            return "SELECT js.id, js.name, js.latitude, js.longitude, d.name AS district_name, bp.name AS block_panchayat_name FROM job_stations js " .
                "JOIN districts d ON js.district_id = d.id " .
                "LEFT JOIN block_panchayats bp ON js.block_panchayat_id = bp.id {$where} ORDER BY js.name";
        case 'facilitation_centers':
            return "SELECT fc.id, fc.name, fc.latitude, fc.longitude, d.name AS district_name, bp.name AS block_panchayat_name, lb.name AS local_body_name FROM facilitation_centers fc " .
                "JOIN districts d ON fc.district_id = d.id " .
                "LEFT JOIN block_panchayats bp ON fc.block_panchayat_id = bp.id " .
                "JOIN local_bodies lb ON fc.local_body_id = lb.id {$where} ORDER BY fc.name";
        case 'sdpk_centers':
            return "SELECT sc.id, sc.code, sc.name, sc.address, sc.latitude, sc.longitude, sc.phase, sc.active_status, " .
                "CASE WHEN sc.active_status = 1 THEN 'Active' ELSE 'Inactive' END AS active_status_label, d.name AS district_name " .
                "FROM sdpk_centers sc JOIN districts d ON sc.district_id = d.id {$where} ORDER BY sc.name";
        case 'qualification_categories':
            return "SELECT id, name FROM qualification_categories {$where} ORDER BY name";
        case 'academic_institutions':
            return "SELECT ai.id, ai.name, ai.latitude, ai.longitude, d.name AS district_name, qc.name AS qualification_category_name, ai.institution_type FROM academic_institutions ai " .
                "JOIN districts d ON ai.district_id = d.id " .
                "LEFT JOIN qualification_categories qc ON ai.qualification_category = qc.id {$where} ORDER BY ai.name";
        case 'education_courses':
            return "SELECT ec.id, ec.name, d.name AS district_name, qc.name AS qualification_category_name FROM education_courses ec " .
                "JOIN districts d ON ec.district_id = d.id " .
                "LEFT JOIN qualification_categories qc ON ec.qualification_category = qc.id {$where} ORDER BY ec.name";
        case 'academic_authorities':
            return "SELECT aa.id, aa.code, aa.name, aa.authority_type, d.name AS district_name, lb.name AS local_body_name, aa.local_body_code, " .
                "aa.website, aa.address, aa.latitude, aa.longitude, aa.year_established, aa.sub_category " .
                "FROM academic_authorities aa " .
                "JOIN districts d ON aa.district_id = d.id " .
                "LEFT JOIN local_bodies lb ON aa.local_body_code = lb.lb_code {$where} ORDER BY aa.name";
        case 'cds_list':
            return "SELECT cds.id, cds.name, d.name AS district_name, lbt.name AS type_name FROM cds_list cds " .
                "JOIN districts d ON cds.district_id = d.id " .
                "JOIN local_body_types lbt ON cds.local_body_type_id = lbt.id {$where} ORDER BY cds.name";
        case 'ads_list':
            return "SELECT ads.id, ads.name, d.name AS district_name, lbt.name AS type_name, lb.name AS local_body_name FROM ads_list ads " .
                "JOIN districts d ON ads.district_id = d.id " .
                "JOIN local_body_types lbt ON ads.local_body_type_id = lbt.id " .
                "JOIN local_bodies lb ON ads.local_body_id = lb.id {$where} ORDER BY ads.name";
        case 'districts':
        default:
            return "SELECT id, name FROM districts {$where} ORDER BY name";
    }
}

function fetch_teams(mysqli $conn): array
{
    return fetch_named($conn, 'teams');
}

function create_team(mysqli $conn, string $name, string &$message): void
{
    $trimmed = trim($name);
    if ($trimmed === '') {
        return;
    }

    try {
        $stmt = $conn->prepare('INSERT INTO teams (name) VALUES (?)');
        $stmt->bind_param('s', $trimmed);
        $stmt->execute();
        $message = 'Team created.';
    } catch (mysqli_sql_exception $exception) {
        if ($exception->getCode() === 1062) {
            $message = 'This team already exists.';
        } else {
            $message = 'Unable to create team.';
        }
    }
}

function update_team(mysqli $conn, int $teamId, string $name, string &$message): void
{
    $trimmed = trim($name);
    if ($teamId <= 0 || $trimmed === '') {
        return;
    }

    try {
        $stmt = $conn->prepare('UPDATE teams SET name = ? WHERE id = ?');
        $stmt->bind_param('si', $trimmed, $teamId);
        $stmt->execute();
        $message = 'Team updated.';
    } catch (mysqli_sql_exception $exception) {
        if ($exception->getCode() === 1062) {
            $message = 'This team already exists.';
        } else {
            $message = 'Unable to update team.';
        }
    }
}

function delete_team(mysqli $conn, int $teamId, string &$message): void
{
    if ($teamId <= 0) {
        return;
    }

    $stmt = $conn->prepare('DELETE FROM teams WHERE id = ?');
    $stmt->bind_param('i', $teamId);
    $stmt->execute();
    $message = 'Team deleted.';
}

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
            $districtId = (int) ($input['district_id'] ?? 0);
            $localBodyCode = trim($input['local_body_code'] ?? '');
            $website = trim($input['website'] ?? '');
            $address = trim($input['address'] ?? '');
            $latitude = ($input['latitude'] ?? '') !== '' ? (float) $input['latitude'] : null;
            $longitude = ($input['longitude'] ?? '') !== '' ? (float) $input['longitude'] : null;
            $yearEstablished = ($input['year_established'] ?? '') !== '' ? (int) $input['year_established'] : null;
            $subCategory = trim($input['sub_category'] ?? '');
            if ($code !== '' && $name !== '' && $authorityType !== '' && $districtId) {
                $localBodyValue = $localBodyCode !== '' ? $localBodyCode : null;
                $websiteValue = $website !== '' ? $website : null;
                $addressValue = $address !== '' ? $address : null;
                $subCategoryValue = $subCategory !== '' ? $subCategory : null;
                $stmt = $conn->prepare(
                    'INSERT INTO academic_authorities (code, name, authority_type, district_id, local_body_code, website, address, latitude, longitude, year_established, sub_category) ' .
                    'VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                );
                $stmt->bind_param(
                    'sssisssddis',
                    $code,
                    $name,
                    $authorityType,
                    $districtId,
                    $localBodyValue,
                    $websiteValue,
                    $addressValue,
                    $latitude,
                    $longitude,
                    $yearEstablished,
                    $subCategoryValue
                );
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
