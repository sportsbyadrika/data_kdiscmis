<?php

require_once __DIR__ . '/db.php';

function fetch_aggregators_for_dsm(mysqli $conn): array
{
    $result = $conn->query('SELECT id, name FROM aggregators ORDER BY name');
    return $result->fetch_all(MYSQLI_ASSOC);
}

function search_employers_for_dsm(mysqli $conn, string $search): array
{
    $query = trim($search);
    if ($query === '') {
        $result = $conn->query(
            'SELECT e.id, e.code, e.name, e.spoc_name, e.spoc_mobile, e.spoc_email, e.aggregator_id, a.name AS aggregator_name ' .
            'FROM employers e LEFT JOIN aggregators a ON e.aggregator_id = a.id ORDER BY e.name LIMIT 100'
        );
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    $stmt = $conn->prepare(
        'SELECT e.id, e.code, e.name, e.spoc_name, e.spoc_mobile, e.spoc_email, e.aggregator_id, a.name AS aggregator_name ' .
        'FROM employers e LEFT JOIN aggregators a ON e.aggregator_id = a.id ' .
        'WHERE e.name LIKE ? OR e.code LIKE ? ORDER BY e.name LIMIT 100'
    );
    $like = '%' . $query . '%';
    $stmt->bind_param('ss', $like, $like);
    $stmt->execute();

    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function fetch_employer_for_dsm(mysqli $conn, int $employerId): ?array
{
    $stmt = $conn->prepare(
        'SELECT id, code, name, spoc_name, spoc_mobile, spoc_email, aggregator_id FROM employers WHERE id = ? LIMIT 1'
    );
    $stmt->bind_param('i', $employerId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    return $result ?: null;
}

function create_employer_for_dsm(mysqli $conn, array $data, int $userId, array &$errors): void
{
    $code = trim($data['code'] ?? '');
    $name = trim($data['name'] ?? '');
    $spocName = trim($data['spoc_name'] ?? '');
    $spocMobile = trim($data['spoc_mobile'] ?? '');
    $spocEmail = trim($data['spoc_email'] ?? '');
    $aggregatorId = ($data['aggregator_id'] ?? '') !== '' ? (int) $data['aggregator_id'] : null;

    if ($code === '') {
        $errors[] = 'Employer code is required.';
    }
    if ($name === '') {
        $errors[] = 'Employer name is required.';
    }
    if (!empty($errors)) {
        return;
    }

    $stmt = $conn->prepare(
        'INSERT INTO employers (code, name, spoc_name, spoc_mobile, spoc_email, aggregator_id) VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->bind_param('sssssi', $code, $name, $spocName, $spocMobile, $spocEmail, $aggregatorId);

    if (!$stmt->execute()) {
        $errors[] = 'Unable to create employer. Please verify unique code.';
        return;
    }

    $employerId = (int) $conn->insert_id;
    log_employer_dsm_activity($conn, [
        'employer_id' => $employerId,
        'activity_type' => 'create',
        'activity_date' => date('Y-m-d'),
        'changed_by_user_id' => $userId,
        'change_notes' => json_encode(['new_values' => [
            'code' => $code,
            'name' => $name,
            'spoc_name' => $spocName,
            'spoc_mobile' => $spocMobile,
            'spoc_email' => $spocEmail,
            'aggregator_id' => $aggregatorId,
        ]], JSON_UNESCAPED_UNICODE),
    ]);
}

function update_employer_for_dsm(mysqli $conn, int $employerId, array $data, int $userId, array &$errors): void
{
    $existing = fetch_employer_for_dsm($conn, $employerId);
    if (!$existing) {
        $errors[] = 'Employer not found.';
        return;
    }

    $code = trim($data['code'] ?? '');
    $name = trim($data['name'] ?? '');
    $spocName = trim($data['spoc_name'] ?? '');
    $spocMobile = trim($data['spoc_mobile'] ?? '');
    $spocEmail = trim($data['spoc_email'] ?? '');
    $aggregatorId = ($data['aggregator_id'] ?? '') !== '' ? (int) $data['aggregator_id'] : null;

    if ($code === '') {
        $errors[] = 'Employer code is required.';
    }
    if ($name === '') {
        $errors[] = 'Employer name is required.';
    }
    if (!empty($errors)) {
        return;
    }

    $stmt = $conn->prepare(
        'UPDATE employers SET code = ?, name = ?, spoc_name = ?, spoc_mobile = ?, spoc_email = ?, aggregator_id = ? WHERE id = ?'
    );
    $stmt->bind_param('sssssii', $code, $name, $spocName, $spocMobile, $spocEmail, $aggregatorId, $employerId);

    if (!$stmt->execute()) {
        $errors[] = 'Unable to update employer. Please verify unique code.';
        return;
    }

    log_employer_dsm_activity($conn, [
        'employer_id' => $employerId,
        'activity_type' => 'edit',
        'activity_date' => date('Y-m-d'),
        'changed_by_user_id' => $userId,
        'change_notes' => json_encode([
            'previous_values' => $existing,
            'new_values' => [
                'code' => $code,
                'name' => $name,
                'spoc_name' => $spocName,
                'spoc_mobile' => $spocMobile,
                'spoc_email' => $spocEmail,
                'aggregator_id' => $aggregatorId,
            ],
        ], JSON_UNESCAPED_UNICODE),
    ]);
}

function log_employer_dsm_activity(mysqli $conn, array $data): void
{
    $stmt = $conn->prepare(
        'INSERT INTO dsm_employer_activity_logs (employer_id, activity_type, activity_date, changed_by_user_id, change_notes) VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->bind_param(
        'issis',
        $data['employer_id'],
        $data['activity_type'],
        $data['activity_date'],
        $data['changed_by_user_id'],
        $data['change_notes']
    );
    $stmt->execute();
}

function search_job_titles_for_dsm(mysqli $conn, string $search): array
{
    $query = trim($search);
    if ($query === '') {
        $result = $conn->query(
            'SELECT jt.id, jt.job_code, jt.job_title, jt.employer_id, jt.openings, jt.job_location, jt.status, e.name AS employer_name ' .
            'FROM job_titles jt JOIN employers e ON jt.employer_id = e.id ORDER BY jt.job_title LIMIT 100'
        );
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    $stmt = $conn->prepare(
        'SELECT jt.id, jt.job_code, jt.job_title, jt.employer_id, jt.openings, jt.job_location, jt.status, e.name AS employer_name ' .
        'FROM job_titles jt JOIN employers e ON jt.employer_id = e.id ' .
        'WHERE jt.job_title LIKE ? OR jt.job_code LIKE ? OR e.name LIKE ? ORDER BY jt.job_title LIMIT 100'
    );
    $like = '%' . $query . '%';
    $stmt->bind_param('sss', $like, $like, $like);
    $stmt->execute();

    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function fetch_job_title_for_dsm(mysqli $conn, int $jobTitleId): ?array
{
    $stmt = $conn->prepare(
        'SELECT id, job_code, job_title, employer_id, openings, education_category_id, salary_range_id, job_category_id, job_location, job_description, job_details, status ' .
        'FROM job_titles WHERE id = ? LIMIT 1'
    );
    $stmt->bind_param('i', $jobTitleId);
    $stmt->execute();

    $row = $stmt->get_result()->fetch_assoc();
    return $row ?: null;
}

function create_job_title_for_dsm(mysqli $conn, array $data, int $userId, array &$errors): void
{
    $jobCode = trim($data['job_code'] ?? '');
    $jobTitle = trim($data['job_title'] ?? '');
    $employerId = (int) ($data['employer_id'] ?? 0);
    $openings = ($data['openings'] ?? '') !== '' ? (int) $data['openings'] : 0;
    $jobLocation = trim($data['job_location'] ?? '');
    $jobDescription = trim($data['job_description'] ?? '');
    $jobDetails = trim($data['job_details'] ?? '');
    $status = ($data['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';

    if ($jobCode === '') {
        $errors[] = 'Job code is required.';
    }
    if ($jobTitle === '') {
        $errors[] = 'Job title is required.';
    }
    if ($employerId <= 0) {
        $errors[] = 'Employer is required.';
    }
    if (!empty($errors)) {
        return;
    }

    $stmt = $conn->prepare(
        'INSERT INTO job_titles (job_code, job_title, employer_id, openings, job_location, job_description, job_details, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->bind_param('ssiissss', $jobCode, $jobTitle, $employerId, $openings, $jobLocation, $jobDescription, $jobDetails, $status);

    if (!$stmt->execute()) {
        $errors[] = 'Unable to create job title. Please verify unique job code and valid employer.';
        return;
    }

    $jobTitleId = (int) $conn->insert_id;
    log_job_title_dsm_activity($conn, [
        'job_title_id' => $jobTitleId,
        'activity_type' => 'create',
        'activity_date' => date('Y-m-d'),
        'changed_by_user_id' => $userId,
        'previous_values' => null,
        'new_values' => json_encode([
            'job_code' => $jobCode,
            'job_title' => $jobTitle,
            'employer_id' => $employerId,
            'openings' => $openings,
            'job_location' => $jobLocation,
            'job_description' => $jobDescription,
            'job_details' => $jobDetails,
            'status' => $status,
        ], JSON_UNESCAPED_UNICODE),
    ]);
}

function update_job_title_for_dsm(mysqli $conn, int $jobTitleId, array $data, int $userId, array &$errors): void
{
    $existing = fetch_job_title_for_dsm($conn, $jobTitleId);
    if (!$existing) {
        $errors[] = 'Job title not found.';
        return;
    }

    $jobCode = trim($data['job_code'] ?? '');
    $jobTitle = trim($data['job_title'] ?? '');
    $employerId = (int) ($data['employer_id'] ?? 0);
    $openings = ($data['openings'] ?? '') !== '' ? (int) $data['openings'] : 0;
    $jobLocation = trim($data['job_location'] ?? '');
    $jobDescription = trim($data['job_description'] ?? '');
    $jobDetails = trim($data['job_details'] ?? '');
    $status = ($data['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';

    if ($jobCode === '') {
        $errors[] = 'Job code is required.';
    }
    if ($jobTitle === '') {
        $errors[] = 'Job title is required.';
    }
    if ($employerId <= 0) {
        $errors[] = 'Employer is required.';
    }
    if (!empty($errors)) {
        return;
    }

    $stmt = $conn->prepare(
        'UPDATE job_titles SET job_code = ?, job_title = ?, employer_id = ?, openings = ?, job_location = ?, job_description = ?, job_details = ?, status = ? WHERE id = ?'
    );
    $stmt->bind_param('ssiissssi', $jobCode, $jobTitle, $employerId, $openings, $jobLocation, $jobDescription, $jobDetails, $status, $jobTitleId);

    if (!$stmt->execute()) {
        $errors[] = 'Unable to update job title. Please verify unique job code and selected employer.';
        return;
    }

    log_job_title_dsm_activity($conn, [
        'job_title_id' => $jobTitleId,
        'activity_type' => 'edit',
        'activity_date' => date('Y-m-d'),
        'changed_by_user_id' => $userId,
        'previous_values' => json_encode($existing, JSON_UNESCAPED_UNICODE),
        'new_values' => json_encode([
            'job_code' => $jobCode,
            'job_title' => $jobTitle,
            'employer_id' => $employerId,
            'openings' => $openings,
            'job_location' => $jobLocation,
            'job_description' => $jobDescription,
            'job_details' => $jobDetails,
            'status' => $status,
        ], JSON_UNESCAPED_UNICODE),
    ]);
}

function log_job_title_dsm_activity(mysqli $conn, array $data): void
{
    $stmt = $conn->prepare(
        'INSERT INTO dsm_job_title_activity_logs (job_title_id, activity_type, activity_date, changed_by_user_id, previous_values, new_values) VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->bind_param(
        'ississ',
        $data['job_title_id'],
        $data['activity_type'],
        $data['activity_date'],
        $data['changed_by_user_id'],
        $data['previous_values'],
        $data['new_values']
    );
    $stmt->execute();
}
