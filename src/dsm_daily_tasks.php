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

function fetch_dsm_task_types(mysqli $conn): array
{
    $result = $conn->query("SELECT id, name FROM dsm_task_types WHERE status = 'active' ORDER BY name");
    return $result->fetch_all(MYSQLI_ASSOC);
}

function fetch_employers_by_aggregator_for_dsm(mysqli $conn, ?int $aggregatorId): array
{
    if (!$aggregatorId) {
        return [];
    }

    $stmt = $conn->prepare('SELECT id, name, code FROM employers WHERE aggregator_id = ? ORDER BY name');
    $stmt->bind_param('i', $aggregatorId);
    $stmt->execute();

    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function fetch_job_titles_by_employer_for_dsm(mysqli $conn, ?int $employerId): array
{
    if (!$employerId) {
        return [];
    }

    $stmt = $conn->prepare('SELECT id, job_title, job_code FROM job_titles WHERE employer_id = ? ORDER BY job_title');
    $stmt->bind_param('i', $employerId);
    $stmt->execute();

    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function fetch_dsm_daily_tasks(mysqli $conn, array $filters): array
{
    $conditions = [];
    $params = [];
    $types = '';

    $dateFrom = trim($filters['date_from'] ?? '');
    if ($dateFrom !== '') {
        $conditions[] = 't.task_date >= ?';
        $types .= 's';
        $params[] = $dateFrom;
    }

    $dateTo = trim($filters['date_to'] ?? '');
    if ($dateTo !== '') {
        $conditions[] = 't.task_date <= ?';
        $types .= 's';
        $params[] = $dateTo;
    }

    $jobFairNumber = trim($filters['job_fair_number'] ?? '');
    if ($jobFairNumber !== '') {
        $conditions[] = 't.job_fair_number LIKE ?';
        $types .= 's';
        $params[] = '%' . $jobFairNumber . '%';
    }

    $employerName = trim($filters['employer_name'] ?? '');
    if ($employerName !== '') {
        $conditions[] = 'e.name LIKE ?';
        $types .= 's';
        $params[] = '%' . $employerName . '%';
    }

    $jobTitle = trim($filters['job_title'] ?? '');
    if ($jobTitle !== '') {
        $conditions[] = 'jt.job_title LIKE ?';
        $types .= 's';
        $params[] = '%' . $jobTitle . '%';
    }

    $meetingOwner = trim($filters['meeting_owner'] ?? '');
    if ($meetingOwner !== '') {
        $conditions[] = 't.meeting_owner LIKE ?';
        $types .= 's';
        $params[] = '%' . $meetingOwner . '%';
    }

    $whereClause = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

    $sql = 'SELECT t.id, t.task_date, tt.name AS task_type_name, t.task_title, t.result ' .
        'FROM dsm_daily_tasks t ' .
        'JOIN dsm_task_types tt ON t.task_type_id = tt.id ' .
        'LEFT JOIN employers e ON t.employer_id = e.id ' .
        'LEFT JOIN job_titles jt ON t.job_title_id = jt.id ' .
        "{$whereClause} ORDER BY t.task_date DESC, t.id DESC";

    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();

    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function fetch_dsm_daily_task_by_id(mysqli $conn, int $taskId): ?array
{
    $stmt = $conn->prepare('SELECT * FROM dsm_daily_tasks WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $taskId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    return $row ?: null;
}

function create_dsm_daily_task(mysqli $conn, array $data, int $userId, array &$errors): void
{
    $taskDate = $data['task_date'] ?? '';
    $taskTypeId = (int) ($data['task_type_id'] ?? 0);
    $taskTitle = trim($data['task_title'] ?? '');

    if ($taskDate === '') {
        $errors[] = 'Date is required.';
    }
    if ($taskTypeId <= 0) {
        $errors[] = 'Task type is required.';
    }
    if ($taskTitle === '') {
        $errors[] = 'Task title is required.';
    }
    if (!empty($errors)) {
        return;
    }

    $taskDetails = trim($data['task_details'] ?? '');
    $jobFairNumber = trim($data['job_fair_number'] ?? '');
    $aggregatorId = ($data['aggregator_id'] ?? '') !== '' ? (int) $data['aggregator_id'] : null;
    $employerId = ($data['employer_id'] ?? '') !== '' ? (int) $data['employer_id'] : null;
    $jobTitleId = ($data['job_title_id'] ?? '') !== '' ? (int) $data['job_title_id'] : null;
    $meetingOwner = trim($data['meeting_owner'] ?? '');
    $meetingMembers = trim($data['meeting_members'] ?? '');
    $duration = trim($data['duration'] ?? '');
    $result = in_array(($data['result'] ?? ''), ['Closed', 'Pending', 'Cancelled'], true) ? $data['result'] : 'Pending';
    $resultDetails = trim($data['result_details'] ?? '');
    $callStatus = in_array(($data['call_status'] ?? ''), ['Connected', 'Not responding', 'Rescheduled'], true) ? $data['call_status'] : null;

    $stmt = $conn->prepare(
        'INSERT INTO dsm_daily_tasks ' .
        '(task_date, task_type_id, task_title, task_details, job_fair_number, aggregator_id, employer_id, job_title_id, meeting_owner, meeting_members, duration, result, result_details, call_status, created_by_user_id, updated_by_user_id) ' .
        'VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->bind_param(
        'sisssiiissssssii',
        $taskDate,
        $taskTypeId,
        $taskTitle,
        $taskDetails,
        $jobFairNumber,
        $aggregatorId,
        $employerId,
        $jobTitleId,
        $meetingOwner,
        $meetingMembers,
        $duration,
        $result,
        $resultDetails,
        $callStatus,
        $userId,
        $userId
    );
    $stmt->execute();
}

function update_dsm_daily_task(mysqli $conn, int $taskId, array $data, int $userId, array &$errors): void
{
    $existing = fetch_dsm_daily_task_by_id($conn, $taskId);
    if (!$existing) {
        $errors[] = 'Task not found.';
        return;
    }

    $taskDate = $data['task_date'] ?? '';
    $taskTypeId = (int) ($data['task_type_id'] ?? 0);
    $taskTitle = trim($data['task_title'] ?? '');

    if ($taskDate === '') {
        $errors[] = 'Date is required.';
    }
    if ($taskTypeId <= 0) {
        $errors[] = 'Task type is required.';
    }
    if ($taskTitle === '') {
        $errors[] = 'Task title is required.';
    }
    if (!empty($errors)) {
        return;
    }

    $taskDetails = trim($data['task_details'] ?? '');
    $jobFairNumber = trim($data['job_fair_number'] ?? '');
    $aggregatorId = ($data['aggregator_id'] ?? '') !== '' ? (int) $data['aggregator_id'] : null;
    $employerId = ($data['employer_id'] ?? '') !== '' ? (int) $data['employer_id'] : null;
    $jobTitleId = ($data['job_title_id'] ?? '') !== '' ? (int) $data['job_title_id'] : null;
    $meetingOwner = trim($data['meeting_owner'] ?? '');
    $meetingMembers = trim($data['meeting_members'] ?? '');
    $duration = trim($data['duration'] ?? '');
    $result = in_array(($data['result'] ?? ''), ['Closed', 'Pending', 'Cancelled'], true) ? $data['result'] : 'Pending';
    $resultDetails = trim($data['result_details'] ?? '');
    $callStatus = in_array(($data['call_status'] ?? ''), ['Connected', 'Not responding', 'Rescheduled'], true) ? $data['call_status'] : null;

    $stmt = $conn->prepare(
        'UPDATE dsm_daily_tasks SET task_date = ?, task_type_id = ?, task_title = ?, task_details = ?, job_fair_number = ?, ' .
        'aggregator_id = ?, employer_id = ?, job_title_id = ?, meeting_owner = ?, meeting_members = ?, duration = ?, result = ?, result_details = ?, call_status = ?, updated_by_user_id = ? ' .
        'WHERE id = ?'
    );
    $stmt->bind_param(
        'sisssiiissssssii',
        $taskDate,
        $taskTypeId,
        $taskTitle,
        $taskDetails,
        $jobFairNumber,
        $aggregatorId,
        $employerId,
        $jobTitleId,
        $meetingOwner,
        $meetingMembers,
        $duration,
        $result,
        $resultDetails,
        $callStatus,
        $userId,
        $taskId
    );
    $stmt->execute();
}

function fetch_all_employers_for_dsm(mysqli $conn): array
{
    $result = $conn->query('SELECT id, name, aggregator_id FROM employers ORDER BY name');
    return $result->fetch_all(MYSQLI_ASSOC);
}

function fetch_all_job_titles_for_dsm(mysqli $conn): array
{
    $result = $conn->query('SELECT id, job_title, employer_id FROM job_titles ORDER BY job_title');
    return $result->fetch_all(MYSQLI_ASSOC);
}
