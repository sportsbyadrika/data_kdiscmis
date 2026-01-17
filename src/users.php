<?php

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

function child_role_for(string $role): ?string
{
    return match ($role) {
        ROLE_SUPER_ADMIN => ROLE_STATE_USER,
        ROLE_STATE_USER => ROLE_DISTRICT_USER,
        ROLE_DISTRICT_USER => ROLE_LOCALBODY_USER,
        default => null,
    };
}

function role_label(string $role): string
{
    return match ($role) {
        ROLE_SUPER_ADMIN => 'Super admin',
        ROLE_STATE_USER => 'State level user',
        ROLE_DISTRICT_USER => 'District level user',
        ROLE_LOCALBODY_USER => 'Localbody level user',
        default => ucfirst($role),
    };
}

function team_roles(): array
{
    return [
        'operator' => 'Operator',
        'verifier' => 'Verifier',
        'approver' => 'Approver',
    ];
}

function team_role_label(string $role): string
{
    return team_roles()[$role] ?? ucfirst($role);
}

function available_functionalities(): array
{
    return [
        'Applicant details',
        'Tickets',
        'Files',
    ];
}

function create_subordinate_user(mysqli $conn, array $currentUser, array $input): string
{
    $childRole = child_role_for($currentUser['role']);
    if ($childRole === null) {
        return 'You cannot create users at this level.';
    }

    $name = trim($input['name'] ?? '');
    $email = trim($input['email'] ?? '');
    $mobile = trim($input['mobile'] ?? '');
    $password = $input['password'] ?? '';
    $status = ($input['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';
    $districtId = (int) ($input['district_id'] ?? 0);
    $teamId = (int) ($input['team_id'] ?? 0);
    $teamRole = $input['team_role'] ?? '';
    $functionalities = array_values(array_filter($input['functionalities'] ?? [], 'is_string'));
    $allowedRoles = array_keys(team_roles());
    $allowedFunctionalities = available_functionalities();
    $requiresDistrict = in_array($childRole, [ROLE_DISTRICT_USER, ROLE_LOCALBODY_USER], true);

    if ($childRole === ROLE_LOCALBODY_USER && $districtId === 0 && $currentUser['district_id']) {
        $districtId = (int) $currentUser['district_id'];
    }

    if ($name === '' || $email === '' || $mobile === '' || $password === '') {
        return 'All user fields are required.';
    }

    if ($teamId === 0 || !in_array($teamRole, $allowedRoles, true)) {
        return 'Team and team role are required.';
    }

    if (empty($functionalities)) {
        return 'Select at least one accessible functionality.';
    }

    $invalidFunctionalities = array_diff($functionalities, $allowedFunctionalities);
    if (!empty($invalidFunctionalities)) {
        return 'Invalid functionality selection.';
    }

    if ($requiresDistrict && $districtId === 0) {
        return 'District selection is required for this user role.';
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $districtValue = $districtId ?: null;

    try {
        $conn->begin_transaction();
        $stmt = $conn->prepare(
            'INSERT INTO users (name, email, mobile, password_hash, role, team_id, team_role, district_id, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param(
            'sssssisisi',
            $name,
            $email,
            $mobile,
            $hash,
            $childRole,
            $teamId,
            $teamRole,
            $districtValue,
            $status,
            $currentUser['id']
        );
        $stmt->execute();

        $userId = $stmt->insert_id;
        $functionStmt = $conn->prepare('INSERT INTO user_functionalities (user_id, functionality) VALUES (?, ?)');
        foreach ($functionalities as $functionality) {
            $functionStmt->bind_param('is', $userId, $functionality);
            $functionStmt->execute();
        }
        $conn->commit();
    } catch (mysqli_sql_exception $exception) {
        $conn->rollback();
        if ($exception->getCode() === 1062) {
            return 'A user with this mobile number already exists.';
        }
        return 'Unable to create user. Please try again.';
    }

    return ucfirst(str_replace('_', ' ', $childRole)) . ' created successfully.';
}

function count_users_by_role(mysqli $conn, string $role): int
{
    $stmt = $conn->prepare('SELECT COUNT(*) AS total FROM users WHERE role = ?');
    $stmt->bind_param('s', $role);
    $stmt->execute();
    $result = $stmt->get_result();

    return (int) $result->fetch_assoc()['total'];
}

function count_created_users(mysqli $conn, int $creatorId, string $role): int
{
    $stmt = $conn->prepare('SELECT COUNT(*) AS total FROM users WHERE created_by = ? AND role = ?');
    $stmt->bind_param('is', $creatorId, $role);
    $stmt->execute();
    $result = $stmt->get_result();

    return (int) $result->fetch_assoc()['total'];
}

function count_localbody_under_state(mysqli $conn, int $stateUserId): int
{
    $stmt = $conn->prepare(
        'SELECT COUNT(*) AS total FROM users WHERE role = ? AND created_by IN (SELECT id FROM users WHERE created_by = ? AND role = ?)'
    );
    $localbodyRole = ROLE_LOCALBODY_USER;
    $districtRole = ROLE_DISTRICT_USER;
    $stmt->bind_param('sis', $localbodyRole, $stateUserId, $districtRole);
    $stmt->execute();
    $result = $stmt->get_result();

    return (int) $result->fetch_assoc()['total'];
}

function user_dashboard_counts(mysqli $conn, array $user): array
{
    return match ($user['role']) {
        ROLE_SUPER_ADMIN => [
            [
                'label' => 'State level users',
                'count' => count_users_by_role($conn, ROLE_STATE_USER),
                'description' => 'Total state-level accounts managed by the super admin.',
            ],
            [
                'label' => 'District level users',
                'count' => count_users_by_role($conn, ROLE_DISTRICT_USER),
                'description' => 'District user accounts created by state-level teams.',
            ],
            [
                'label' => 'Localbody level users',
                'count' => count_users_by_role($conn, ROLE_LOCALBODY_USER),
                'description' => 'Localbody operators nested under district managers.',
            ],
        ],
        ROLE_STATE_USER => [
            [
                'label' => 'District level users',
                'count' => count_created_users($conn, (int) $user['id'], ROLE_DISTRICT_USER),
                'description' => 'District accounts created by you for your state.',
            ],
            [
                'label' => 'Localbody level users',
                'count' => count_localbody_under_state($conn, (int) $user['id']),
                'description' => 'Localbody accounts created under your district teams.',
            ],
        ],
        ROLE_DISTRICT_USER => [
            [
                'label' => 'Localbody level users',
                'count' => count_created_users($conn, (int) $user['id'], ROLE_LOCALBODY_USER),
                'description' => 'Localbody users operating in your district.',
            ],
        ],
        default => [],
    };
}

function fetch_subordinate_users(mysqli $conn, array $user, string $search = ''): array
{
    $childRole = child_role_for($user['role']);
    if ($childRole === null) {
        return [];
    }

    $conditions = ['u.role = ?'];
    $params = [$childRole];
    $types = 's';

    if ($user['role'] !== ROLE_SUPER_ADMIN) {
        $conditions[] = 'u.created_by = ?';
        $types .= 'i';
        $params[] = (int) $user['id'];
    }

    if ($search !== '') {
        $conditions[] = '(u.name LIKE ? OR u.mobile LIKE ? OR u.email LIKE ?)';
        $types .= 'sss';
        $term = '%' . $search . '%';
        $params[] = $term;
        $params[] = $term;
        $params[] = $term;
    }

    $where = 'WHERE ' . implode(' AND ', $conditions);
    $query = 'SELECT u.id, u.name, u.email, u.mobile, u.status, u.role, u.created_at, ' .
        'd.name AS district_name, t.name AS team_name, u.team_role, ' .
        'GROUP_CONCAT(uf.functionality ORDER BY uf.functionality SEPARATOR \', \') AS functionalities ' .
        'FROM users u ' .
        'LEFT JOIN districts d ON u.district_id = d.id ' .
        'LEFT JOIN teams t ON u.team_id = t.id ' .
        'LEFT JOIN user_functionalities uf ON u.id = uf.user_id ' .
        $where . ' GROUP BY u.id ORDER BY u.created_at DESC';

    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();

    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function fetch_subordinate_user(mysqli $conn, array $user, int $userId): ?array
{
    $childRole = child_role_for($user['role']);
    if ($childRole === null || $userId <= 0) {
        return null;
    }

    $conditions = ['u.role = ?', 'u.id = ?'];
    $params = [$childRole, $userId];
    $types = 'si';

    if ($user['role'] !== ROLE_SUPER_ADMIN) {
        $conditions[] = 'u.created_by = ?';
        $types .= 'i';
        $params[] = (int) $user['id'];
    }

    $where = 'WHERE ' . implode(' AND ', $conditions);
    $query = 'SELECT u.id, u.name, u.email, u.mobile, u.status, u.role, u.created_at, u.district_id, ' .
        'u.team_id, u.team_role, d.name AS district_name, t.name AS team_name ' .
        'FROM users u ' .
        'LEFT JOIN districts d ON u.district_id = d.id ' .
        'LEFT JOIN teams t ON u.team_id = t.id ' .
        $where . ' LIMIT 1';

    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    return $row ?: null;
}

function fetch_user_functionalities(mysqli $conn, int $userId): array
{
    if ($userId <= 0) {
        return [];
    }

    $stmt = $conn->prepare('SELECT functionality FROM user_functionalities WHERE user_id = ?');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = $result->fetch_all(MYSQLI_ASSOC);

    return array_map(static fn(array $row): string => $row['functionality'], $rows);
}

function update_subordinate_user(mysqli $conn, array $currentUser, int $userId, array $input): string
{
    $childRole = child_role_for($currentUser['role']);
    if ($childRole === null) {
        return 'You cannot update users at this level.';
    }

    $name = trim($input['name'] ?? '');
    $email = trim($input['email'] ?? '');
    $mobile = trim($input['mobile'] ?? '');
    $password = trim($input['password'] ?? '');
    $status = ($input['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';
    $districtId = (int) ($input['district_id'] ?? 0);
    $teamId = (int) ($input['team_id'] ?? 0);
    $teamRole = $input['team_role'] ?? '';
    $functionalities = array_values(array_filter($input['functionalities'] ?? [], 'is_string'));
    $allowedRoles = array_keys(team_roles());
    $allowedFunctionalities = available_functionalities();
    $requiresDistrict = in_array($childRole, [ROLE_DISTRICT_USER, ROLE_LOCALBODY_USER], true);

    if ($childRole === ROLE_LOCALBODY_USER && $districtId === 0 && $currentUser['district_id']) {
        $districtId = (int) $currentUser['district_id'];
    }

    if ($name === '' || $email === '' || $mobile === '') {
        return 'All user fields are required.';
    }

    if ($teamId === 0 || !in_array($teamRole, $allowedRoles, true)) {
        return 'Team and team role are required.';
    }

    if (empty($functionalities)) {
        return 'Select at least one accessible functionality.';
    }

    $invalidFunctionalities = array_diff($functionalities, $allowedFunctionalities);
    if (!empty($invalidFunctionalities)) {
        return 'Invalid functionality selection.';
    }

    if ($requiresDistrict && $districtId === 0) {
        return 'District selection is required for this user role.';
    }

    try {
        $conn->begin_transaction();
        $fields = 'name = ?, email = ?, mobile = ?, status = ?, team_id = ?, team_role = ?, district_id = ?';
        $types = 'ssssisi';
        $params = [$name, $email, $mobile, $status, $teamId, $teamRole, $districtId ?: null];

        if ($password !== '') {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $fields .= ', password_hash = ?';
            $types .= 's';
            $params[] = $hash;
        }

        $conditions = 'WHERE id = ? AND role = ?';
        $types .= 'is';
        $params[] = $userId;
        $params[] = $childRole;

        if ($currentUser['role'] !== ROLE_SUPER_ADMIN) {
            $conditions .= ' AND created_by = ?';
            $types .= 'i';
            $params[] = (int) $currentUser['id'];
        }

        $stmt = $conn->prepare("UPDATE users SET {$fields} {$conditions}");
        $stmt->bind_param($types, ...$params);
        $stmt->execute();

        $stmt = $conn->prepare('DELETE FROM user_functionalities WHERE user_id = ?');
        $stmt->bind_param('i', $userId);
        $stmt->execute();

        $functionStmt = $conn->prepare('INSERT INTO user_functionalities (user_id, functionality) VALUES (?, ?)');
        foreach ($functionalities as $functionality) {
            $functionStmt->bind_param('is', $userId, $functionality);
            $functionStmt->execute();
        }
        $conn->commit();
    } catch (mysqli_sql_exception $exception) {
        $conn->rollback();
        if ($exception->getCode() === 1062) {
            return 'A user with this mobile number already exists.';
        }
        return 'Unable to update user. Please try again.';
    }

    return ucfirst(str_replace('_', ' ', $childRole)) . ' updated successfully.';
}
