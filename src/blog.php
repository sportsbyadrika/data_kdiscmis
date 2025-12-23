<?php

require_once __DIR__ . '/db.php';

function fetch_blog_posts(mysqli $conn, ?int $limit = null): array
{
    $sql = 'SELECT id, title, content_html, published_at FROM blog_posts ORDER BY published_at DESC, id DESC';
    if ($limit !== null) {
        $sql .= ' LIMIT ?';
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $limit);
    } else {
        $stmt = $conn->prepare($sql);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $posts = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $posts;
}

function fetch_blog_post(mysqli $conn, int $id): ?array
{
    $stmt = $conn->prepare('SELECT id, title, content_html, published_at FROM blog_posts WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $post = $result->fetch_assoc();
    $stmt->close();

    if (!$post) {
        return null;
    }

    return $post;
}
