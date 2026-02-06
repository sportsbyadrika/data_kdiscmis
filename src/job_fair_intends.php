<?php

require_once __DIR__ . '/db.php';

function fetch_job_fair_intends(mysqli $conn, array $filters): array
{
    $conditions = [];
    $params = [];
    $types = '';

    $dateFrom = $filters['date_from'] ?? '';
    if ($dateFrom !== '') {
        $conditions[] = 'ji.intend_date >= ?';
        $types .= 's';
        $params[] = $dateFrom;
    }

    $dateTo = $filters['date_to'] ?? '';
    if ($dateTo !== '') {
        $conditions[] = 'ji.intend_date <= ?';
        $types .= 's';
        $params[] = $dateTo;
    }

    $search = trim($filters['search'] ?? '');
    if ($search !== '') {
        $conditions[] = '(CAST(ji.id AS CHAR) LIKE ? OR ji.reference_job_fair_number LIKE ?)';
        $types .= 'ss';
        $like = '%' . $search . '%';
        $params[] = $like;
        $params[] = $like;
    }

    $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
    $stmt = $conn->prepare(
        'SELECT ji.id, ji.intend_date, ji.reference_committee_number, ji.reference_date, ji.reference_job_fair_number, ' .
        'ji.job_fair_date, ji.created_at, ji.updated_at, COUNT(jil.id) AS location_count ' .
        'FROM job_fair_intends ji ' .
        'LEFT JOIN job_fair_intend_locations jil ON ji.id = jil.intend_id ' .
        "{$where} GROUP BY ji.id ORDER BY ji.intend_date DESC, ji.id DESC"
    );
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_all(MYSQLI_ASSOC);
}

function fetch_job_fair_intend(mysqli $conn, int $intendId): ?array
{
    $stmt = $conn->prepare('SELECT * FROM job_fair_intends WHERE id = ?');
    $stmt->bind_param('i', $intendId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    return $row ?: null;
}

function create_job_fair_intend(mysqli $conn, array $data, array &$errors): int
{
    $intendDate = $data['intend_date'] ?? null;
    $referenceCommitteeNumber = trim($data['reference_committee_number'] ?? '');
    $referenceDate = ($data['reference_date'] ?? '') !== '' ? $data['reference_date'] : null;
    $referenceJobFairNumber = trim($data['reference_job_fair_number'] ?? '');
    $jobFairDate = ($data['job_fair_date'] ?? '') !== '' ? $data['job_fair_date'] : null;

    if (!$intendDate) {
        $errors[] = 'Intend date is required.';
    }
    if ($referenceCommitteeNumber === '') {
        $errors[] = 'Reference co-ordination committee number is required.';
    }
    if ($referenceJobFairNumber === '') {
        $errors[] = 'Reference job fair number is required.';
    }

    if (!empty($errors)) {
        return 0;
    }

    $stmt = $conn->prepare(
        'INSERT INTO job_fair_intends (intend_date, reference_committee_number, reference_date, reference_job_fair_number, job_fair_date) ' .
        'VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->bind_param('sssss', $intendDate, $referenceCommitteeNumber, $referenceDate, $referenceJobFairNumber, $jobFairDate);
    $stmt->execute();

    return $stmt->insert_id;
}

function update_job_fair_intend(mysqli $conn, int $intendId, array $data, array &$errors): void
{
    $intendDate = $data['intend_date'] ?? null;
    $referenceCommitteeNumber = trim($data['reference_committee_number'] ?? '');
    $referenceDate = ($data['reference_date'] ?? '') !== '' ? $data['reference_date'] : null;
    $referenceJobFairNumber = trim($data['reference_job_fair_number'] ?? '');
    $jobFairDate = ($data['job_fair_date'] ?? '') !== '' ? $data['job_fair_date'] : null;

    if (!$intendDate) {
        $errors[] = 'Intend date is required.';
    }
    if ($referenceCommitteeNumber === '') {
        $errors[] = 'Reference co-ordination committee number is required.';
    }
    if ($referenceJobFairNumber === '') {
        $errors[] = 'Reference job fair number is required.';
    }

    if (!empty($errors)) {
        return;
    }

    $stmt = $conn->prepare(
        'UPDATE job_fair_intends SET intend_date = ?, reference_committee_number = ?, reference_date = ?, reference_job_fair_number = ?, job_fair_date = ? ' .
        'WHERE id = ?'
    );
    $stmt->bind_param('sssssi', $intendDate, $referenceCommitteeNumber, $referenceDate, $referenceJobFairNumber, $jobFairDate, $intendId);
    $stmt->execute();
}

function fetch_sdpk_centers_for_intend(mysqli $conn): array
{
    $stmt = $conn->prepare(
        'SELECT sc.id, sc.code, sc.name, d.name AS district_name ' .
        'FROM sdpk_centers sc JOIN districts d ON sc.district_id = d.id ORDER BY sc.name'
    );
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_all(MYSQLI_ASSOC);
}

function fetch_intend_location_counts(mysqli $conn, int $intendId): array
{
    $stmt = $conn->prepare(
        'SELECT location_type, COUNT(*) AS total FROM job_fair_intend_locations WHERE intend_id = ? GROUP BY location_type'
    );
    $stmt->bind_param('i', $intendId);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = $result->fetch_all(MYSQLI_ASSOC);

    $counts = [];
    foreach ($rows as $row) {
        $counts[$row['location_type']] = (int) $row['total'];
    }

    return $counts;
}

function fetch_intend_location_ids_by_type(mysqli $conn, int $intendId): array
{
    $stmt = $conn->prepare(
        'SELECT location_type, sdpk_center_id FROM job_fair_intend_locations WHERE intend_id = ?'
    );
    $stmt->bind_param('i', $intendId);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = $result->fetch_all(MYSQLI_ASSOC);

    $mapping = [];
    foreach ($rows as $row) {
        $mapping[$row['location_type']][] = (int) $row['sdpk_center_id'];
    }

    return $mapping;
}

function replace_intend_locations(mysqli $conn, int $intendId, string $locationType, array $centerIds): void
{
    $conn->begin_transaction();
    $deleteStmt = $conn->prepare('DELETE FROM job_fair_intend_locations WHERE intend_id = ? AND location_type = ?');
    $deleteStmt->bind_param('is', $intendId, $locationType);
    $deleteStmt->execute();

    if (!empty($centerIds)) {
        $insertStmt = $conn->prepare(
            'INSERT INTO job_fair_intend_locations (intend_id, location_type, sdpk_center_id) VALUES (?, ?, ?)'
        );
        foreach ($centerIds as $centerId) {
            $centerValue = (int) $centerId;
            $insertStmt->bind_param('isi', $intendId, $locationType, $centerValue);
            $insertStmt->execute();
        }
    }

    $conn->commit();
}
