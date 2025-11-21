<?php
require_once __DIR__ . '/../src/masters.php';
require_once __DIR__ . '/../src/auth.php';

$conn = db_connect();
$options = fetch_filter_options($conn);
$type = $_GET['type'] ?? 'districts';
$definitions = master_definitions();

if (!isset($definitions[$type])) {
    http_response_code(404);
    echo 'Unknown master';
    exit();
}

$filters = [];
foreach ($definitions[$type]['filters'] as $field => $label) {
    $filters[$field] = $_GET[$field] ?? '';
}
$search = trim($_GET['search'] ?? '');
$rows = fetch_master_rows($conn, $type, $filters, $search);
include __DIR__ . '/partials/header.php';
?>
<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
    <div>
        <h1 class="h4 mb-1"><?php echo htmlspecialchars($definitions[$type]['title']); ?></h1>
        <p class="text-muted mb-0">Filter by location or category and search instantly.</p>
    </div>
    <form class="d-flex gap-2" method="get">
        <input type="hidden" name="type" value="<?php echo htmlspecialchars($type); ?>">
        <input type="search" class="form-control" name="search" placeholder="Search by name" value="<?php echo htmlspecialchars($search); ?>">
        <button class="btn btn-outline-primary" type="submit">Search</button>
    </form>
</div>
<form class="row g-2 mb-3" method="get">
    <input type="hidden" name="type" value="<?php echo htmlspecialchars($type); ?>">
    <?php foreach ($definitions[$type]['filters'] as $field => $label): ?>
        <div class="col-sm-6 col-lg-4">
            <label class="form-label small text-muted"><?php echo htmlspecialchars($label); ?></label>
            <select name="<?php echo htmlspecialchars($field); ?>" class="form-select">
                <option value="">Any <?php echo htmlspecialchars($label); ?></option>
                <?php foreach (build_filter_options($field, $options) as $item): ?>
                    <option value="<?php echo $item['id']; ?>" <?php echo $filters[$field] == $item['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($item['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    <?php endforeach; ?>
    <div class="col-12">
        <button class="btn btn-primary" type="submit">Apply Filters</button>
        <a class="btn btn-link" href="/master.php?type=<?php echo urlencode($type); ?>">Reset</a>
    </div>
</form>
<div class="card table-card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span class="fw-semibold">Results</span>
        <span class="text-muted small"><?php echo count($rows); ?> records</span>
    </div>
    <div class="card-body card-table p-0">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th scope="col">#</th>
                        <?php foreach ($definitions[$type]['columns'] as $columnLabel): ?>
                            <th scope="col"><?php echo htmlspecialchars($columnLabel); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $index => $row): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <?php foreach (array_keys($definitions[$type]['columns']) as $keyName): ?>
                                <td><?php echo htmlspecialchars($row[$keyName] ?? ''); ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($rows)): ?>
                        <tr>
                            <td colspan="<?php echo count($definitions[$type]['columns']) + 1; ?>" class="text-center py-4">No records found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>
<?php
function build_filter_options(string $field, array $options): array
{
    return match ($field) {
        'district_id' => $options['districts'],
        'local_body_type_id' => $options['local_body_types'],
        'education_category' => $options['education_categories'],
        'institution_type' => $options['institution_types'],
        'local_body_id' => $options['local_bodies'],
        'block_panchayat_id' => $options['block_panchayats'],
        default => [],
    };
}
?>
