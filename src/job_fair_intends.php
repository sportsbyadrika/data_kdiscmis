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
    $targetOpenings = ($data['target_openings'] ?? '') !== '' ? (int) $data['target_openings'] : null;
    $minimumHrRequired = ($data['minimum_hr_required'] ?? '') !== '' ? (int) $data['minimum_hr_required'] : null;

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
        'INSERT INTO job_fair_intends (intend_date, reference_committee_number, reference_date, reference_job_fair_number, job_fair_date, target_openings, minimum_hr_required) ' .
        'VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->bind_param(
        'sssssii',
        $intendDate,
        $referenceCommitteeNumber,
        $referenceDate,
        $referenceJobFairNumber,
        $jobFairDate,
        $targetOpenings,
        $minimumHrRequired
    );
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

function update_job_fair_intend_targets(mysqli $conn, int $intendId, array $data, array &$errors): void
{
    $targetOpenings = ($data['target_openings'] ?? '') !== '' ? (int) $data['target_openings'] : null;
    $minimumHrRequired = ($data['minimum_hr_required'] ?? '') !== '' ? (int) $data['minimum_hr_required'] : null;

    if ($targetOpenings !== null && $targetOpenings < 0) {
        $errors[] = 'Target openings must be zero or more.';
    }
    if ($minimumHrRequired !== null && $minimumHrRequired < 0) {
        $errors[] = 'Minimum HR required must be zero or more.';
    }

    if (!empty($errors)) {
        return;
    }

    $stmt = $conn->prepare(
        'UPDATE job_fair_intends SET target_openings = ?, minimum_hr_required = ? WHERE id = ?'
    );
    $stmt->bind_param('iii', $targetOpenings, $minimumHrRequired, $intendId);
    $stmt->execute();
}

function fetch_employers_for_intend(mysqli $conn): array
{
    $stmt = $conn->prepare(
        'SELECT e.id, e.code, e.name, e.spoc_name, e.spoc_mobile, e.spoc_email, a.name AS aggregator_name ' .
        'FROM employers e LEFT JOIN aggregators a ON e.aggregator_id = a.id ORDER BY e.name'
    );
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_all(MYSQLI_ASSOC);
}

function fetch_job_titles_for_intend(mysqli $conn): array
{
    $stmt = $conn->prepare(
        'SELECT jt.id, jt.job_code, jt.job_title, jt.openings, e.name AS employer_name ' .
        'FROM job_titles jt JOIN employers e ON jt.employer_id = e.id ORDER BY jt.job_title'
    );
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_all(MYSQLI_ASSOC);
}

function fetch_intend_employer_ids(mysqli $conn, int $intendId): array
{
    $stmt = $conn->prepare('SELECT employer_id FROM job_fair_intend_employers WHERE intend_id = ?');
    $stmt->bind_param('i', $intendId);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = $result->fetch_all(MYSQLI_ASSOC);

    return array_map(static fn(array $row): int => (int) $row['employer_id'], $rows);
}

function fetch_intend_job_title_ids(mysqli $conn, int $intendId): array
{
    $stmt = $conn->prepare('SELECT job_title_id FROM job_fair_intend_job_titles WHERE intend_id = ?');
    $stmt->bind_param('i', $intendId);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = $result->fetch_all(MYSQLI_ASSOC);

    return array_map(static fn(array $row): int => (int) $row['job_title_id'], $rows);
}

function replace_intend_employers(mysqli $conn, int $intendId, array $employerIds): void
{
    $conn->begin_transaction();
    $deleteStmt = $conn->prepare('DELETE FROM job_fair_intend_employers WHERE intend_id = ?');
    $deleteStmt->bind_param('i', $intendId);
    $deleteStmt->execute();

    if (!empty($employerIds)) {
        $insertStmt = $conn->prepare(
            'INSERT INTO job_fair_intend_employers (intend_id, employer_id) VALUES (?, ?)'
        );
        foreach ($employerIds as $employerId) {
            $value = (int) $employerId;
            $insertStmt->bind_param('ii', $intendId, $value);
            $insertStmt->execute();
        }
    }

    $conn->commit();
}

function replace_intend_job_titles(mysqli $conn, int $intendId, array $jobTitleIds): void
{
    $conn->begin_transaction();
    $deleteStmt = $conn->prepare('DELETE FROM job_fair_intend_job_titles WHERE intend_id = ?');
    $deleteStmt->bind_param('i', $intendId);
    $deleteStmt->execute();

    if (!empty($jobTitleIds)) {
        $insertStmt = $conn->prepare(
            'INSERT INTO job_fair_intend_job_titles (intend_id, job_title_id) VALUES (?, ?)'
        );
        foreach ($jobTitleIds as $jobTitleId) {
            $value = (int) $jobTitleId;
            $insertStmt->bind_param('ii', $intendId, $value);
            $insertStmt->execute();
        }
    }

    $conn->commit();
}

function fetch_latest_intend_id_by_job_fair_number(mysqli $conn, string $jobFairNumber): int
{
    $value = trim($jobFairNumber);
    if ($value === '') {
        return 0;
    }

    $stmt = $conn->prepare(
        'SELECT id FROM job_fair_intends WHERE reference_job_fair_number = ? ORDER BY id DESC LIMIT 1'
    );
    $stmt->bind_param('s', $value);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    return $row ? (int) $row['id'] : 0;
}

function fetch_selected_employers_for_intend(mysqli $conn, int $intendId): array
{
    if ($intendId <= 0) {
        return [];
    }

    $stmt = $conn->prepare(
        'SELECT e.id, e.name, e.code, a.id AS aggregator_id, a.name AS aggregator_name ' .
        'FROM job_fair_intend_employers jie ' .
        'JOIN employers e ON e.id = jie.employer_id ' .
        'LEFT JOIN aggregators a ON a.id = e.aggregator_id ' .
        'WHERE jie.intend_id = ? ORDER BY e.name'
    );
    $stmt->bind_param('i', $intendId);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_all(MYSQLI_ASSOC);
}

function fetch_selected_aggregators_for_intend(mysqli $conn, int $intendId): array
{
    if ($intendId <= 0) {
        return [];
    }

    $stmt = $conn->prepare(
        'SELECT DISTINCT a.id, a.name ' .
        'FROM job_fair_intend_employers jie ' .
        'JOIN employers e ON e.id = jie.employer_id ' .
        'JOIN aggregators a ON a.id = e.aggregator_id ' .
        'WHERE jie.intend_id = ? ORDER BY a.name'
    );
    $stmt->bind_param('i', $intendId);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_all(MYSQLI_ASSOC);
}

function fetch_selected_job_titles_for_intend(mysqli $conn, int $intendId): array
{
    if ($intendId <= 0) {
        return [];
    }

    $stmt = $conn->prepare(
        'SELECT jt.id, jt.job_title AS name, jt.job_code, e.name AS employer_name ' .
        'FROM job_fair_intend_job_titles jijt ' .
        'JOIN job_titles jt ON jt.id = jijt.job_title_id ' .
        'JOIN employers e ON e.id = jt.employer_id ' .
        'WHERE jijt.intend_id = ? ORDER BY jt.job_title'
    );
    $stmt->bind_param('i', $intendId);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_all(MYSQLI_ASSOC);
}
