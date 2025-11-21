<?php
require_once __DIR__ . '/../../src/auth.php';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>KDISC Master Data</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/styles.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom shadow-sm sticky-top">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="/">
            <span class="bg-primary text-white rounded-circle d-inline-flex justify-content-center align-items-center me-2" style="width:40px; height:40px;">K</span>
            <span class="fw-bold">KDISC MIS</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center">
                <li class="nav-item"><a class="nav-link" href="/">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="/?group=Local+body">Local body</a></li>
                <li class="nav-item"><a class="nav-link" href="/?group=Academic">Academic</a></li>
                <li class="nav-item"><a class="nav-link" href="/?group=Kudumbasree">Kudumbasree</a></li>
                <?php if (is_logged_in()): ?>
                    <li class="nav-item"><a class="nav-link" href="/admin.php">Admin</a></li>
                    <li class="nav-item"><a class="btn btn-outline-secondary ms-lg-2" href="/logout.php">Logout</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="btn btn-primary ms-lg-2" href="/login.php">Login</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
<div class="container my-4">
