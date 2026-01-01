<?php
require_once __DIR__ . '/../src/masters.php';
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/blog.php';

$conn = db_connect();
$groups = master_groups();
$requestedGroup = $_GET['group'] ?? '';
$activeGroup = in_array($requestedGroup, $groups, true) ? $requestedGroup : '';
$counts = fetch_counts($conn, $activeGroup ?: null);
$blogPosts = fetch_blog_posts($conn, 3);
include __DIR__ . '/partials/header.php';
?>
<div class="row align-items-center mb-4">
    <div class="col">
        <h1 class="h3 mb-0">Master Directory</h1>
        <p class="text-muted mb-1">Discover public master data and manage them with secure administrator access.</p>
        <?php if ($activeGroup): ?>
            <span class="badge bg-light text-primary border">Showing <?php echo htmlspecialchars($activeGroup); ?> masters</span>
        <?php endif; ?>
    </div>
    <?php if (is_logged_in()): ?>
        <div class="col-auto">
            <a class="btn btn-outline-primary" href="/admin.php">Go to Dashboard</a>
        </div>
    <?php endif; ?>
</div>
<div class="row g-3">
    <?php foreach ($counts as $card): ?>
        <div class="col-md-4">
            <div class="card h-100 table-card">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="badge text-primary bg-light badge-outline">Master</span>
                        <span class="fs-4 fw-bold text-primary"><?php echo $card['count']; ?></span>
                    </div>
                    <h2 class="h5 mb-2"><?php echo htmlspecialchars($card['label']); ?></h2>
                    <p class="text-muted small mb-3">Browse and search within the <?php echo strtolower(htmlspecialchars($card['label'])); ?> catalogue.</p>
                    <div class="mt-auto">
                        <a class="btn btn-primary w-100" href="/master.php?type=<?php echo urlencode($card['table']); ?>">View <?php echo htmlspecialchars($card['label']); ?></a>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<div class="row align-items-center mt-5 mb-3">
    <div class="col">
        <h2 class="h4 mb-0">Blog</h2>
        <p class="text-muted mb-0">Recent updates from the KDISC MIS blog.</p>
    </div>
    <div class="col-auto">
        <a class="btn btn-outline-secondary" href="/blog.php">View all</a>
    </div>
</div>
<?php if (empty($blogPosts)): ?>
    <div class="alert alert-info">No blog posts are available yet. Please check back soon.</div>
<?php else: ?>
    <div class="row g-3">
        <?php foreach ($blogPosts as $post): ?>
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body d-flex flex-column">
                        <h3 class="h6 mb-2"><?php echo htmlspecialchars($post['title']); ?></h3>
                        <p class="text-muted small mb-2">
                            <?php echo date('F j, Y', strtotime($post['published_at'])); ?>
                        </p>
                        <p class="text-muted mb-3">
                            <?php echo htmlspecialchars(mb_strimwidth(strip_tags($post['content_html']), 0, 120, '...')); ?>
                        </p>
                        <div class="mt-auto">
                            <a class="btn btn-outline-primary w-100" href="/blog_post.php?id=<?php echo urlencode((string) $post['id']); ?>">Read more</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?php include __DIR__ . '/partials/footer.php'; ?>
