<?php
require_once __DIR__ . '/../src/blog.php';

$conn = db_connect();
$postId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$post = $postId > 0 ? fetch_blog_post($conn, $postId) : null;

include __DIR__ . '/partials/header.php';
?>
<div class="row align-items-center mb-4">
    <div class="col">
        <?php if ($post): ?>
            <h1 class="h3 mb-2"><?php echo htmlspecialchars($post['title']); ?></h1>
            <p class="text-muted mb-0"><?php echo date('F j, Y', strtotime($post['published_at'])); ?></p>
        <?php else: ?>
            <h1 class="h3 mb-2">Blog post not found</h1>
            <p class="text-muted mb-0">The blog post you are looking for does not exist.</p>
        <?php endif; ?>
    </div>
    <div class="col-auto">
        <a class="btn btn-outline-secondary" href="/blog.php">Back to Blog</a>
    </div>
</div>

<?php if ($post): ?>
    <div class="card">
        <div class="card-body">
            <?php echo $post['content_html']; ?>
        </div>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/partials/footer.php'; ?>
