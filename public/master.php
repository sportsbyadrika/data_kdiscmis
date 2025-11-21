<?php
require_once __DIR__ . '/../src/masters.php';
require_once __DIR__ . '/../src/auth.php';

$conn = db_connect();
$options = fetch_filter_options($conn);
$type = $_GET['type'] ?? 'districts';
$definitions = master_definitions();

$hasCoordinates = in_array($type, ['job_stations', 'facilitation_centers', 'academic_institutions'], true);
$view = ($_GET['view'] ?? 'table') === 'map' && $hasCoordinates ? 'map' : 'table';

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
$mapRows = $hasCoordinates
    ? array_values(array_filter(
        $rows,
        static fn(array $row): bool => isset($row['latitude'], $row['longitude']) && $row['latitude'] !== null && $row['longitude'] !== null && $row['latitude'] !== '' && $row['longitude'] !== ''
    ))
    : [];

function build_view_url(string $view): string
{
    $params = $_GET;
    $params['view'] = $view;

    return '/master.php?' . http_build_query($params);
}
include __DIR__ . '/partials/header.php';
?>
<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
    <div>
        <h1 class="h4 mb-1"><?php echo htmlspecialchars($definitions[$type]['title']); ?></h1>
        <p class="text-muted mb-0">Filter by location or category and search instantly.</p>
    </div>
    <form class="d-flex gap-2" method="get">
        <input type="hidden" name="type" value="<?php echo htmlspecialchars($type); ?>">
        <input type="hidden" name="view" value="<?php echo htmlspecialchars($view); ?>">
        <input type="search" class="form-control" name="search" placeholder="Search by name" value="<?php echo htmlspecialchars($search); ?>">
        <button class="btn btn-outline-primary" type="submit">Search</button>
    </form>
</div>
<form class="row g-2 mb-3" method="get">
    <input type="hidden" name="type" value="<?php echo htmlspecialchars($type); ?>">
    <input type="hidden" name="view" value="<?php echo htmlspecialchars($view); ?>">
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
        <div class="d-flex align-items-center gap-2">
            <span class="text-muted small"><?php echo count($rows); ?> records</span>
            <?php if ($hasCoordinates): ?>
                <div class="btn-group btn-group-sm" role="group" aria-label="View toggle">
                    <a class="btn btn-outline-primary <?php echo $view === 'table' ? 'active' : ''; ?>" href="<?php echo htmlspecialchars(build_view_url('table')); ?>">Table view</a>
                    <a class="btn btn-outline-primary <?php echo $view === 'map' ? 'active' : ''; ?>" href="<?php echo htmlspecialchars(build_view_url('map')); ?>">Map view</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="card-body card-table p-0">
        <div class="table-responsive <?php echo $view === 'table' ? '' : 'd-none'; ?>">
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
        <?php if ($hasCoordinates): ?>
            <div class="<?php echo $view === 'map' ? '' : 'd-none'; ?>">
                <?php if (empty($mapRows)): ?>
                    <div class="p-4">No records with coordinates to display on the map.</div>
                <?php else: ?>
                    <div id="master-map" style="min-height: 500px;"></div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php if ($view === 'map' && $hasCoordinates && !empty($mapRows)): ?>
    <?php $mapApiKey = env('GOOGLE_MAPS_API_KEY', ''); ?>
    <script>
        const masterLocations = <?php echo json_encode(array_map(
            static fn(array $row): array => [
                'name' => $row['name'] ?? '',
                'latitude' => isset($row['latitude']) ? (float) $row['latitude'] : null,
                'longitude' => isset($row['longitude']) ? (float) $row['longitude'] : null,
                'details' => implode(' • ', array_filter([
                    $row['district_name'] ?? null,
                    $row['block_panchayat_name'] ?? null,
                    $row['local_body_name'] ?? null,
                    $row['qualification_category_name'] ?? null,
                    $row['institution_type'] ?? null,
                ])),
            ],
            $mapRows
        ), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;

        function initMasterMap() {
            const mapElement = document.getElementById('master-map');
            if (!mapElement || !Array.isArray(masterLocations)) {
                return;
            }

            const fallbackCenter = {lat: 10.8505, lng: 76.2711};
            const firstLocation = masterLocations.find((item) =>
                typeof item.latitude === 'number' && !Number.isNaN(item.latitude) &&
                typeof item.longitude === 'number' && !Number.isNaN(item.longitude)
            );
            const mapCenter = firstLocation ? {lat: firstLocation.latitude, lng: firstLocation.longitude} : fallbackCenter;

            const map = new google.maps.Map(mapElement, {
                zoom: 8,
                center: mapCenter,
                mapTypeControl: false,
            });

            masterLocations.forEach((location) => {
                if (typeof location.latitude !== 'number' || Number.isNaN(location.latitude) ||
                    typeof location.longitude !== 'number' || Number.isNaN(location.longitude)) {
                    return;
                }

                const marker = new google.maps.Marker({
                    position: {lat: location.latitude, lng: location.longitude},
                    map,
                    title: location.name,
                });

                if (location.details) {
                    const infoWindow = new google.maps.InfoWindow({
                        content: `<div><strong>${location.name}</strong><div class="text-muted small mt-1">${location.details}</div></div>`,
                    });
                    marker.addListener('click', () => infoWindow.open({anchor: marker, map}));
                }
            });
        }
    </script>
    <script src="https://maps.googleapis.com/maps/api/js<?php echo $mapApiKey ? '?key=' . urlencode($mapApiKey) : ''; ?>&callback=initMasterMap" async defer></script>
<?php endif; ?>
<?php include __DIR__ . '/partials/footer.php'; ?>
<?php
function build_filter_options(string $field, array $options): array
{
    return match ($field) {
        'district_id' => $options['districts'],
        'local_body_type_id' => $options['local_body_types'],
        'qualification_category' => $options['qualification_categories'],
        'institution_type' => $options['institution_types'],
        'local_body_id' => $options['local_bodies'],
        'block_panchayat_id' => $options['block_panchayats'],
        default => [],
    };
}
?>
