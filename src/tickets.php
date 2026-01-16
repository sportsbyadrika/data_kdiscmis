<?php

require_once __DIR__ . '/db.php';

function fetch_ticket_categories(mysqli $conn): array
{
    $result = $conn->query('SELECT id, name FROM issue_categories ORDER BY name ASC');
    return $result->fetch_all(MYSQLI_ASSOC);
}

function fetch_ticket_status_counts(mysqli $conn): array
{
    $query = "SELECT COUNT(*) AS total, " .
        "SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) AS pending, " .
        "SUM(CASE WHEN status = 'Resolved' THEN 1 ELSE 0 END) AS resolved " .
        "FROM tickets";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();

    return [
        'total' => (int) ($row['total'] ?? 0),
        'pending' => (int) ($row['pending'] ?? 0),
        'resolved' => (int) ($row['resolved'] ?? 0),
    ];
}

function fetch_ticket_category_summary(mysqli $conn): array
{
    $query = "SELECT c.id, c.name, " .
        "COUNT(t.id) AS total_count, " .
        "SUM(CASE WHEN t.status = 'Resolved' THEN 1 ELSE 0 END) AS resolved_count, " .
        "SUM(CASE WHEN t.status = 'Pending' THEN 1 ELSE 0 END) AS pending_count " .
        "FROM issue_categories c " .
        "LEFT JOIN tickets t ON t.category_id = c.id " .
        "GROUP BY c.id, c.name " .
        "ORDER BY c.name";

    $result = $conn->query($query);
    return $result->fetch_all(MYSQLI_ASSOC);
}

function fetch_ticket_list(
    mysqli $conn,
    ?int $categoryId,
    ?string $status,
    int $page,
    int $perPage,
    array $searchFilters = []
): array
{
    $conditions = [];
    $params = [];
    $types = '';

    if ($categoryId) {
        $conditions[] = 't.category_id = ?';
        $types .= 'i';
        $params[] = $categoryId;
    }

    if ($status && $status !== 'all') {
        $normalized = strtolower($status) === 'resolved' ? 'Resolved' : 'Pending';
        $conditions[] = 't.status = ?';
        $types .= 's';
        $params[] = $normalized;
    }

    if (!empty($searchFilters['reference'])) {
        $conditions[] = 't.reference_institution LIKE ?';
        $types .= 's';
        $params[] = '%' . $searchFilters['reference'] . '%';
    }

    if (!empty($searchFilters['reported'])) {
        $conditions[] = 't.reported_by LIKE ?';
        $types .= 's';
        $params[] = '%' . $searchFilters['reported'] . '%';
    }

    if (!empty($searchFilters['mobile'])) {
        $conditions[] = 't.reported_mobile LIKE ?';
        $types .= 's';
        $params[] = '%' . $searchFilters['mobile'] . '%';
    }

    if (!empty($searchFilters['issue_id'])) {
        $issueId = trim((string) $searchFilters['issue_id']);
        if ($issueId !== '') {
            if (ctype_digit($issueId)) {
                $conditions[] = '(t.tracker_number = ? OR t.id = ?)';
                $types .= 'si';
                $params[] = $issueId;
                $params[] = (int) $issueId;
            } else {
                $conditions[] = 't.tracker_number = ?';
                $types .= 's';
                $params[] = $issueId;
            }
        }
    }

    $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

    $countQuery = "SELECT COUNT(*) AS total FROM tickets t {$where}";
    $countStmt = $conn->prepare($countQuery);
    if ($params) {
        $countStmt->bind_param($types, ...$params);
    }
    $countStmt->execute();
    $total = (int) $countStmt->get_result()->fetch_assoc()['total'];

    $offset = ($page - 1) * $perPage;
    $query = "SELECT t.id, t.tracker_number, t.created_at, t.reference_institution, t.reported_by, " .
        "t.reported_mobile, t.reported_email, t.issue_details, t.status, t.resolution_text, " .
        "c.name AS category_name, d.name AS district_name " .
        "FROM tickets t " .
        "JOIN issue_categories c ON t.category_id = c.id " .
        "LEFT JOIN districts d ON t.district_id = d.id " .
        "{$where} ORDER BY t.created_at DESC LIMIT ? OFFSET ?";

    $stmt = $conn->prepare($query);
    $typesWithLimit = $types . 'ii';
    $paramsWithLimit = array_merge($params, [$perPage, $offset]);
    $stmt->bind_param($typesWithLimit, ...$paramsWithLimit);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    return ['rows' => $rows, 'total' => $total];
}

function update_ticket_resolution(mysqli $conn, int $ticketId, string $status, string $resolutionText): bool
{
    $normalizedStatus = $status === 'Resolved' ? 'Resolved' : 'Pending';
    $stmt = $conn->prepare('UPDATE tickets SET status = ?, resolution_text = ? WHERE id = ?');
    $stmt->bind_param('ssi', $normalizedStatus, $resolutionText, $ticketId);
    return $stmt->execute();
}

function append_ticket_attachments(mysqli $conn, int $ticketId, array $attachments): void
{
    if (empty($attachments)) {
        return;
    }
    $attachmentStmt = $conn->prepare(
        'INSERT INTO ticket_attachments (ticket_id, file_name, file_path, file_type) VALUES (?, ?, ?, ?)'
    );
    foreach ($attachments as $attachment) {
        $attachmentStmt->bind_param(
            'isss',
            $ticketId,
            $attachment['file_name'],
            $attachment['file_path'],
            $attachment['file_type']
        );
        $attachmentStmt->execute();
    }
}

function fetch_ticket_attachments(mysqli $conn, int $ticketId): array
{
    $stmt = $conn->prepare('SELECT id, file_name, file_path, file_type FROM ticket_attachments WHERE ticket_id = ?');
    $stmt->bind_param('i', $ticketId);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_all(MYSQLI_ASSOC);
}

function create_ticket(mysqli $conn, array $payload, array $attachments): string
{
    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare(
            'INSERT INTO tickets (category_id, district_id, reference_institution, reported_by, reported_mobile, reported_email, issue_details) ' .
            'VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param(
            'iisssss',
            $payload['category_id'],
            $payload['district_id'],
            $payload['reference_institution'],
            $payload['reported_by'],
            $payload['reported_mobile'],
            $payload['reported_email'],
            $payload['issue_details']
        );
        $stmt->execute();
        $ticketId = $conn->insert_id;

        $tracker = sprintf('ISS-%s-%05d', date('Ymd'), $ticketId);
        $updateStmt = $conn->prepare('UPDATE tickets SET tracker_number = ? WHERE id = ?');
        $updateStmt->bind_param('si', $tracker, $ticketId);
        $updateStmt->execute();

        if (!empty($attachments)) {
            $attachmentStmt = $conn->prepare(
                'INSERT INTO ticket_attachments (ticket_id, file_name, file_path, file_type) VALUES (?, ?, ?, ?)'
            );
            foreach ($attachments as $attachment) {
                $attachmentStmt->bind_param(
                    'isss',
                    $ticketId,
                    $attachment['file_name'],
                    $attachment['file_path'],
                    $attachment['file_type']
                );
                $attachmentStmt->execute();
            }
        }

        $conn->commit();

        return $tracker;
    } catch (Throwable $error) {
        $conn->rollback();
        throw $error;
    }
}
