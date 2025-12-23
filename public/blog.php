<?php
require_once __DIR__ . '/../src/blog.php';

$conn = db_connect();
$posts = fetch_blog_posts($conn);
include __DIR__ . '/partials/header.php';
?>
<div class="row align-items-center mb-4">
    <div class="col">
        <h1 class="h3 mb-0">Blog</h1>
        <p class="text-muted mb-1">Latest updates and highlights from KDISC MIS.</p>
    </div>
</div>
<?php if (empty($posts)): ?>
    <div class="alert alert-info">No blog posts are available yet. Please check back soon.</div>
<?php else: ?>
    <div class="row g-3">
        <?php foreach ($posts as $post): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100">
                    <div class="card-body d-flex flex-column">
                        <h2 class="h5 mb-2"><?php echo htmlspecialchars($post['title']); ?></h2>
                        <p class="text-muted small mb-3">
                            <?php echo date('F j, Y', strtotime($post['published_at'])); ?>
                        </p>
                        <p class="text-muted mb-3">
                            <?php echo htmlspecialchars(mb_strimwidth(strip_tags($post['content_html']), 0, 140, '...')); ?>
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
