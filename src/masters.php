<?php

require_once __DIR__ . '/db.php';

function fetch_counts(mysqli $conn): array
{
    $tables = [
        'districts' => 'Districts',
        'local_bodies' => 'Local Bodies',
        'job_stations' => 'Job Stations',
        'academic_institutions' => 'Academic Institutions',
        'education_courses' => 'Education Courses/Trades',
        'cds_list' => 'CDS',
        'ads_list' => 'ADS',
    ];

    $counts = [];
    foreach ($tables as $table => $label) {
        $result = $conn->query("SELECT COUNT(*) as total FROM {$table}");
        $counts[] = [
            'table' => $table,
            'label' => $label,
            'count' => (int) $result->fetch_assoc()['total'],
        ];
    }

    return $counts;
}

function fetch_filter_options(mysqli $conn): array
{
    return [
        'districts' => fetch_named($conn, 'districts'),
        'local_body_types' => fetch_named($conn, 'local_body_types'),
        'education_categories' => fetch_distinct($conn, 'academic_institutions', 'education_category'),
        'institution_types' => fetch_distinct($conn, 'academic_institutions', 'institution_type'),
        'local_bodies' => fetch_named($conn, 'local_bodies'),
        'block_panchayats' => fetch_named($conn, 'block_panchayats'),
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
        'districts' => [
            'title' => 'Districts',
            'table' => 'districts',
            'filters' => [],
            'columns' => ['name' => 'District'],
        ],
        'local_bodies' => [
            'title' => 'Local Bodies',
            'table' => 'local_bodies',
            'filters' => ['district_id' => 'District', 'local_body_type_id' => 'Type'],
            'columns' => ['name' => 'Local Body', 'district_name' => 'District', 'type_name' => 'Type'],
        ],
        'job_stations' => [
            'title' => 'Job Stations',
            'table' => 'job_stations',
            'filters' => ['district_id' => 'District', 'block_panchayat_id' => 'Block Panchayat'],
            'columns' => ['name' => 'Job Station', 'district_name' => 'District', 'block_panchayat_name' => 'Block Panchayat'],
        ],
        'academic_institutions' => [
            'title' => 'Academic Institutions',
            'table' => 'academic_institutions',
            'filters' => ['district_id' => 'District', 'education_category' => 'Education Category', 'institution_type' => 'Institution Type'],
            'columns' => ['name' => 'Institution', 'district_name' => 'District', 'education_category' => 'Category', 'institution_type' => 'Type'],
        ],
        'education_courses' => [
            'title' => 'Education Courses/Trades',
            'table' => 'education_courses',
            'filters' => ['district_id' => 'District', 'education_category' => 'Education Category'],
            'columns' => ['name' => 'Course/Trade', 'district_name' => 'District', 'education_category' => 'Category'],
        ],
        'cds_list' => [
            'title' => 'CDS',
            'table' => 'cds_list',
            'filters' => ['district_id' => 'District', 'local_body_type_id' => 'Local Body Type'],
            'columns' => ['name' => 'CDS', 'district_name' => 'District', 'type_name' => 'Local Body Type'],
        ],
        'ads_list' => [
            'title' => 'ADS',
            'table' => 'ads_list',
            'filters' => ['district_id' => 'District', 'local_body_type_id' => 'Local Body Type', 'local_body_id' => 'Local Body'],
            'columns' => ['name' => 'ADS', 'district_name' => 'District', 'type_name' => 'Local Body Type', 'local_body_name' => 'Local Body'],
        ],
    ];
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
        if ($value === '') {
            continue;
        }
        $conditions[] = "{$field} = ?";
        $isIdField = str_ends_with($field, '_id');
        $types .= $isIdField ? 'i' : 's';
        $params[] = $isIdField ? (int) $value : $value;
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
        case 'local_bodies':
            return "SELECT lb.id, lb.name, d.name AS district_name, lbt.name AS type_name FROM local_bodies lb " .
                "JOIN districts d ON lb.district_id = d.id " .
                "JOIN local_body_types lbt ON lb.local_body_type_id = lbt.id {$where} ORDER BY lb.name";
        case 'job_stations':
            return "SELECT js.id, js.name, d.name AS district_name, bp.name AS block_panchayat_name FROM job_stations js " .
                "JOIN districts d ON js.district_id = d.id " .
                "LEFT JOIN block_panchayats bp ON js.block_panchayat_id = bp.id {$where} ORDER BY js.name";
        case 'academic_institutions':
            return "SELECT ai.id, ai.name, d.name AS district_name, ai.education_category, ai.institution_type FROM academic_institutions ai " .
                "JOIN districts d ON ai.district_id = d.id {$where} ORDER BY ai.name";
        case 'education_courses':
            return "SELECT ec.id, ec.name, d.name AS district_name, ec.education_category FROM education_courses ec " .
                "JOIN districts d ON ec.district_id = d.id {$where} ORDER BY ec.name";
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
