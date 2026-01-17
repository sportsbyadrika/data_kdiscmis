<?php

require_once __DIR__ . '/db.php';

function applicants_template_headers(): array
{
    return ['unique_id', 'name', 'mobile', 'email', 'details_html', 'data_status', 'purpose'];
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
        'INSERT INTO applicants (unique_id, name, mobile, email, details_html, data_status, purpose) ' .
        'VALUES (?, ?, ?, ?, ?, ?, ?) ' .
        'ON DUPLICATE KEY UPDATE name = VALUES(name), mobile = VALUES(mobile), email = VALUES(email), ' .
        'details_html = VALUES(details_html), data_status = VALUES(data_status), purpose = VALUES(purpose)'
    );

    while (($row = fgetcsv($handle)) !== false) {
        $uniqueId = trim($row[$indexes['unique_id']] ?? '');
        $name = trim($row[$indexes['name']] ?? '');
        $mobile = trim($row[$indexes['mobile']] ?? '');
        $email = trim($row[$indexes['email']] ?? '');
        $detailsHtml = trim($row[$indexes['details_html']] ?? '');
        $dataStatus = trim($row[$indexes['data_status']] ?? '');
        $purpose = trim($row[$indexes['purpose']] ?? '');

        if ($uniqueId === '' || $name === '' || $mobile === '' || $dataStatus === '' || $purpose === '') {
            $skipped++;
            continue;
        }

        $stmt->bind_param('sssssss', $uniqueId, $name, $mobile, $email, $detailsHtml, $dataStatus, $purpose);
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
