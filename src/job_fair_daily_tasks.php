<?php

require_once __DIR__ . '/db.php';

function fetch_job_fair_daily_tasks(mysqli $conn, array $filters): array
{
    $conditions = [];
    $params = [];
    $types = '';

    $dateFrom = $filters['date_from'] ?? '';
    if ($dateFrom !== '') {
        $conditions[] = 'meeting_date >= ?';
        $types .= 's';
        $params[] = $dateFrom;
    }

    $dateTo = $filters['date_to'] ?? '';
    if ($dateTo !== '') {
        $conditions[] = 'meeting_date <= ?';
        $types .= 's';
        $params[] = $dateTo;
    }

    $search = trim($filters['search'] ?? '');
    if ($search !== '') {
        $conditions[] = '(meeting_number LIKE ? OR job_fair_number LIKE ?)';
        $types .= 'ss';
        $like = '%' . $search . '%';
        $params[] = $like;
        $params[] = $like;
    }

    $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
    $stmt = $conn->prepare(
        "SELECT id, meeting_date, meeting_number, job_fair_date, locations_usual_sdpk, locations_additional, " .
        "locational_functional_requirements, campaign_target, openings, remark_sectoral_preference, " .
        "remark_impact_planned, minutes_file_name, minutes_file_path, minutes_file_type, members_participated, " .
        "job_fair_number, created_at, updated_at " .
        "FROM job_fair_daily_tasks {$where} ORDER BY meeting_date DESC, id DESC"
    );
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_all(MYSQLI_ASSOC);
}

function save_minutes_attachment(?array $file, array &$errors, ?string $existingPath = null): ?array
{
    if (!$file || !isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Unable to upload minutes attachment.';
        return null;
    }

    $allowedMime = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    if (!in_array($mimeType, $allowedMime, true)) {
        $errors[] = 'Minutes attachment must be a PDF or Word document.';
        return null;
    }

    $uploadDir = __DIR__ . '/../public/uploads/job_fair_daily_tasks';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $originalName = $file['name'] ?? 'minutes';
    $extension = pathinfo($originalName, PATHINFO_EXTENSION);
    $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($originalName, PATHINFO_FILENAME));
    $fileName = sprintf('%s_%s.%s', $safeName, uniqid('', true), $extension ?: 'doc');
    $destination = $uploadDir . '/' . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        $errors[] = 'Unable to save minutes attachment.';
        return null;
    }

    if ($existingPath) {
        $oldFilePath = __DIR__ . '/../public' . $existingPath;
        if (is_file($oldFilePath)) {
            unlink($oldFilePath);
        }
    }

    return [
        'file_name' => $originalName,
        'file_path' => '/uploads/job_fair_daily_tasks/' . $fileName,
        'file_type' => strtoupper($extension ?: $mimeType),
    ];
}

function create_job_fair_daily_task(mysqli $conn, array $data, ?array $file, array &$errors): void
{
    $minutes = save_minutes_attachment($file, $errors);
    if (!empty($errors)) {
        return;
    }

    $stmt = $conn->prepare(
        'INSERT INTO job_fair_daily_tasks ' .
        '(meeting_date, meeting_number, job_fair_date, locations_usual_sdpk, locations_additional, ' .
        'locational_functional_requirements, campaign_target, openings, remark_sectoral_preference, ' .
        'remark_impact_planned, minutes_file_name, minutes_file_path, minutes_file_type, members_participated, job_fair_number) ' .
        'VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );

    $meetingDate = $data['meeting_date'] ?? null;
    $meetingNumber = trim($data['meeting_number'] ?? '');
    $jobFairDate = $data['job_fair_date'] !== '' ? $data['job_fair_date'] : null;
    $locationsUsual = ($data['locations_usual_sdpk'] ?? '') !== '' ? (int) $data['locations_usual_sdpk'] : null;
    $locationsAdditional = ($data['locations_additional'] ?? '') !== '' ? (int) $data['locations_additional'] : null;
    $functionalRequirements = trim($data['locational_functional_requirements'] ?? '');
    $campaignTarget = trim($data['campaign_target'] ?? '');
    $openings = ($data['openings'] ?? '') !== '' ? (int) $data['openings'] : null;
    $sectoralPreference = trim($data['remark_sectoral_preference'] ?? '');
    $impactPlanned = trim($data['remark_impact_planned'] ?? '');
    $membersParticipated = trim($data['members_participated'] ?? '');
    $jobFairNumber = trim($data['job_fair_number'] ?? '');
    $minutesName = $minutes['file_name'] ?? null;
    $minutesPath = $minutes['file_path'] ?? null;
    $minutesType = $minutes['file_type'] ?? null;

    $stmt->bind_param(
        'sssiisssisssssss',
        $meetingDate,
        $meetingNumber,
        $jobFairDate,
        $locationsUsual,
        $locationsAdditional,
        $functionalRequirements,
        $campaignTarget,
        $openings,
        $sectoralPreference,
        $impactPlanned,
        $minutesName,
        $minutesPath,
        $minutesType,
        $membersParticipated,
        $jobFairNumber
    );
    $stmt->execute();
}

function update_job_fair_daily_task(mysqli $conn, int $taskId, array $data, ?array $file, array &$errors): void
{
    $existingPath = $data['existing_minutes_path'] ?? null;
    $minutes = save_minutes_attachment($file, $errors, $existingPath);
    if (!empty($errors)) {
        return;
    }

    $meetingDate = $data['meeting_date'] ?? null;
    $meetingNumber = trim($data['meeting_number'] ?? '');
    $jobFairDate = $data['job_fair_date'] !== '' ? $data['job_fair_date'] : null;
    $locationsUsual = ($data['locations_usual_sdpk'] ?? '') !== '' ? (int) $data['locations_usual_sdpk'] : null;
    $locationsAdditional = ($data['locations_additional'] ?? '') !== '' ? (int) $data['locations_additional'] : null;
    $functionalRequirements = trim($data['locational_functional_requirements'] ?? '');
    $campaignTarget = trim($data['campaign_target'] ?? '');
    $openings = ($data['openings'] ?? '') !== '' ? (int) $data['openings'] : null;
    $sectoralPreference = trim($data['remark_sectoral_preference'] ?? '');
    $impactPlanned = trim($data['remark_impact_planned'] ?? '');
    $membersParticipated = trim($data['members_participated'] ?? '');
    $jobFairNumber = trim($data['job_fair_number'] ?? '');

    $minutesName = $minutes['file_name'] ?? ($data['existing_minutes_name'] ?? null);
    $minutesPath = $minutes['file_path'] ?? $existingPath;
    $minutesType = $minutes['file_type'] ?? ($data['existing_minutes_type'] ?? null);

    $stmt = $conn->prepare(
        'UPDATE job_fair_daily_tasks SET meeting_date = ?, meeting_number = ?, job_fair_date = ?, ' .
        'locations_usual_sdpk = ?, locations_additional = ?, locational_functional_requirements = ?, ' .
        'campaign_target = ?, openings = ?, remark_sectoral_preference = ?, remark_impact_planned = ?, ' .
        'minutes_file_name = ?, minutes_file_path = ?, minutes_file_type = ?, members_participated = ?, ' .
        'job_fair_number = ? WHERE id = ?'
    );
    $stmt->bind_param(
        'sssiisssisssssssi',
        $meetingDate,
        $meetingNumber,
        $jobFairDate,
        $locationsUsual,
        $locationsAdditional,
        $functionalRequirements,
        $campaignTarget,
        $openings,
        $sectoralPreference,
        $impactPlanned,
        $minutesName,
        $minutesPath,
        $minutesType,
        $membersParticipated,
        $jobFairNumber,
        $taskId
    );
    $stmt->execute();
}
