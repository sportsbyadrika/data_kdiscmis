<?php

require_once __DIR__ . '/db.php';

function applicants_template_headers(): array
{
    return ['unique_id', 'name', 'mobile', 'email', 'details_html', 'data_status', 'purpose', 'crm_status'];
}

function applicant_crm_statuses(): array
{
    return ['CRM Pending', 'CRM Completed', 'CRM Postponed', 'CRM Cancelled'];
}

function normalize_crm_status(string $status): string
{
    $normalized = trim($status);
    if ($normalized === '') {
        return 'CRM Pending';
    }

    $allowed = applicant_crm_statuses();
    if (!in_array($normalized, $allowed, true)) {
        return 'CRM Pending';
    }

    return $normalized;
}

function upsert_applicants_from_csv(mysqli $conn, string $filePath): array
{
    $handle = fopen($filePath, 'r');
    if ($handle === false) {
        return ['error' => 'Unable to read the uploaded file.'];
    }

    $header = fgetcsv($handle);
    if ($header === false) {
        fclose($handle);
        return ['error' => 'The CSV file is empty.'];
    }

    $normalizedHeader = array_map(static fn(string $value): string => strtolower(trim($value)), $header);
    $expected = applicants_template_headers();
    $missing = array_diff($expected, $normalizedHeader);
    if (!empty($missing)) {
        fclose($handle);
        return ['error' => 'Missing columns: ' . implode(', ', $missing) . '.'];
    }

    $indexes = array_flip($normalizedHeader);
    $inserted = 0;
    $updated = 0;
    $skipped = 0;

    $stmt = $conn->prepare(
        'INSERT INTO applicants (unique_id, name, mobile, email, details_html, data_status, purpose, crm_status) ' .
        'VALUES (?, ?, ?, ?, ?, ?, ?, ?) ' .
        'ON DUPLICATE KEY UPDATE name = VALUES(name), mobile = VALUES(mobile), email = VALUES(email), ' .
        'details_html = VALUES(details_html), data_status = VALUES(data_status), purpose = VALUES(purpose), ' .
        'crm_status = VALUES(crm_status)'
    );

    while (($row = fgetcsv($handle)) !== false) {
        $uniqueId = trim($row[$indexes['unique_id']] ?? '');
        $name = trim($row[$indexes['name']] ?? '');
        $mobile = trim($row[$indexes['mobile']] ?? '');
        $email = trim($row[$indexes['email']] ?? '');
        $detailsHtml = trim($row[$indexes['details_html']] ?? '');
        $dataStatus = trim($row[$indexes['data_status']] ?? '');
        $purpose = trim($row[$indexes['purpose']] ?? '');
        $crmStatus = normalize_crm_status((string) ($row[$indexes['crm_status']] ?? ''));

        if ($uniqueId === '' || $name === '' || $mobile === '' || $dataStatus === '' || $purpose === '') {
            $skipped++;
            continue;
        }

        $stmt->bind_param('ssssssss', $uniqueId, $name, $mobile, $email, $detailsHtml, $dataStatus, $purpose, $crmStatus);
        $stmt->execute();

        if ($stmt->affected_rows === 1) {
            $inserted++;
        } else {
            $updated++;
        }
    }

    fclose($handle);

    return [
        'inserted' => $inserted,
        'updated' => $updated,
        'skipped' => $skipped,
    ];
}

function fetch_applicant_pivot(mysqli $conn): array
{
    $sql = 'SELECT purpose, ' .
        'SUM(CASE WHEN crm_status = "CRM Pending" THEN 1 ELSE 0 END) AS crm_pending, ' .
        'SUM(CASE WHEN crm_status = "CRM Completed" THEN 1 ELSE 0 END) AS crm_completed, ' .
        'SUM(CASE WHEN crm_status = "CRM Postponed" THEN 1 ELSE 0 END) AS crm_postponed, ' .
        'SUM(CASE WHEN crm_status = "CRM Cancelled" THEN 1 ELSE 0 END) AS crm_cancelled, ' .
        'COUNT(*) AS total ' .
        'FROM applicants GROUP BY purpose ORDER BY purpose';
    $result = $conn->query($sql);

    $rows = [];
    $totals = [
        'crm_pending' => 0,
        'crm_completed' => 0,
        'crm_postponed' => 0,
        'crm_cancelled' => 0,
        'total' => 0,
    ];

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
            $totals['crm_pending'] += (int) $row['crm_pending'];
            $totals['crm_completed'] += (int) $row['crm_completed'];
            $totals['crm_postponed'] += (int) $row['crm_postponed'];
            $totals['crm_cancelled'] += (int) $row['crm_cancelled'];
            $totals['total'] += (int) $row['total'];
        }
    }

    return ['rows' => $rows, 'totals' => $totals];
}

function fetch_applicant_list(mysqli $conn, ?string $purpose, ?string $crmStatus): array
{
    $conditions = [];
    $types = '';
    $params = [];

    if ($purpose && $purpose !== 'all') {
        $conditions[] = 'purpose = ?';
        $types .= 's';
        $params[] = $purpose;
    }

    if ($crmStatus && $crmStatus !== 'all') {
        $conditions[] = 'crm_status = ?';
        $types .= 's';
        $params[] = $crmStatus;
    }

    $sql = 'SELECT id, unique_id, name, mobile, email, data_status, purpose, crm_status ' .
        'FROM applicants';
    if (!empty($conditions)) {
        $sql .= ' WHERE ' . implode(' AND ', $conditions);
    }
    $sql .= ' ORDER BY purpose, name';

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [];
    }

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    if (!$result) {
        return [];
    }

    return $result->fetch_all(MYSQLI_ASSOC);
}

function fetch_applicant_purposes(mysqli $conn): array
{
    $result = $conn->query('SELECT DISTINCT purpose FROM applicants ORDER BY purpose');
    if (!$result) {
        return [];
    }

    return array_map(static fn(array $row): string => $row['purpose'], $result->fetch_all(MYSQLI_ASSOC));
}

function fetch_applicants_for_crm(mysqli $conn, ?string $purpose, ?string $crmStatus): array
{
    $conditions = [];
    $types = '';
    $params = [];

    if ($purpose && $purpose !== 'all') {
        $conditions[] = 'purpose = ?';
        $types .= 's';
        $params[] = $purpose;
    }

    if ($crmStatus && $crmStatus !== 'all') {
        $conditions[] = 'crm_status = ?';
        $types .= 's';
        $params[] = $crmStatus;
    }

    $sql = 'SELECT id, unique_id, name, mobile, email, purpose, crm_status, crm_remarks ' .
        'FROM applicants';
    if (!empty($conditions)) {
        $sql .= ' WHERE ' . implode(' AND ', $conditions);
    }
    $sql .= ' ORDER BY name';

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [];
    }

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    if (!$result) {
        return [];
    }

    return $result->fetch_all(MYSQLI_ASSOC);
}

function fetch_applicant_details(mysqli $conn, int $applicantId): ?array
{
    $stmt = $conn->prepare(
        'SELECT id, unique_id, name, mobile, email, details_html, data_status, purpose, crm_status, crm_remarks ' .
        'FROM applicants WHERE id = ?'
    );
    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('i', $applicantId);
    $stmt->execute();
    $result = $stmt->get_result();
    if (!$result) {
        return null;
    }

    $data = $result->fetch_assoc();
    if (!$data) {
        return null;
    }

    return $data;
}

function update_applicant_crm(mysqli $conn, int $applicantId, string $crmStatus, string $crmRemarks): bool
{
    $normalizedStatus = normalize_crm_status($crmStatus);
    $stmt = $conn->prepare('UPDATE applicants SET crm_status = ?, crm_remarks = ? WHERE id = ?');
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('ssi', $normalizedStatus, $crmRemarks, $applicantId);
    return $stmt->execute();
}

function create_applicant_call(mysqli $conn, int $applicantId, string $callDate, string $duration, string $status, string $remarks, string $contactedBy): bool
{
    $stmt = $conn->prepare(
        'INSERT INTO applicant_crm_calls (applicant_id, call_date, duration, status, remarks, contacted_by) ' .
        'VALUES (?, ?, ?, ?, ?, ?)'
    );
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('isssss', $applicantId, $callDate, $duration, $status, $remarks, $contactedBy);
    return $stmt->execute();
}

function fetch_applicant_calls(mysqli $conn, int $applicantId): array
{
    $stmt = $conn->prepare(
        'SELECT call_date, duration, status, remarks, contacted_by, created_at ' .
        'FROM applicant_crm_calls WHERE applicant_id = ? ORDER BY call_date DESC, created_at DESC'
    );
    if (!$stmt) {
        return [];
    }

    $stmt->bind_param('i', $applicantId);
    $stmt->execute();
    $result = $stmt->get_result();
    if (!$result) {
        return [];
    }

    return $result->fetch_all(MYSQLI_ASSOC);
}
