<?php
require_once __DIR__ . '/../src/auth.php';
include __DIR__ . '/partials/header.php';
?>
<div class="row align-items-center mb-4">
    <div class="col">
        <h1 class="h3 mb-0">K-DISC Data Portal</h1>
        <p class="text-muted mb-1">Quick access to core datasets, updates, and ticket workflows.</p>
    </div>
    <?php if (is_logged_in()): ?>
        <div class="col-auto">
            <a class="btn btn-outline-primary" href="/admin.php">Go to Dashboard</a>
        </div>
    <?php endif; ?>
</div>
<div class="row g-3">
    <div class="col-md-4">
        <div class="card h-100 table-card">
            <div class="card-body d-flex flex-column">
                <div class="home-card-icon bg-primary-subtle text-primary mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
                        <path d="M4 4.5a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.5.5h-7a.5.5 0 0 1-.5-.5z"/>
                        <path d="M4.5 3A1.5 1.5 0 0 0 3 4.5v2A1.5 1.5 0 0 0 4.5 8h7A1.5 1.5 0 0 0 13 6.5v-2A1.5 1.5 0 0 0 11.5 3zM4 9.5a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.5.5h-7a.5.5 0 0 1-.5-.5z"/>
                        <path d="M4.5 8A1.5 1.5 0 0 0 3 9.5v2A1.5 1.5 0 0 0 4.5 13h7A1.5 1.5 0 0 0 13 11.5v-2A1.5 1.5 0 0 0 11.5 8z"/>
                    </svg>
                </div>
                <h2 class="h5 mb-2">Master Dataset</h2>
                <p class="text-muted mb-3">Browse structured master data catalogs grouped for quick discovery.</p>
                <div class="mt-auto">
                    <a class="btn btn-primary w-100" href="/datasets.php">Explore datasets</a>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100 table-card">
            <div class="card-body d-flex flex-column">
                <div class="home-card-icon bg-success-subtle text-success mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
                        <path d="M5.5 7.5a.5.5 0 0 1 .5-.5h4a.5.5 0 0 1 0 1H6a.5.5 0 0 1-.5-.5"/>
                        <path d="M5.5 9.5a.5.5 0 0 1 .5-.5h4a.5.5 0 0 1 0 1H6a.5.5 0 0 1-.5-.5"/>
                        <path d="M2 2.5A1.5 1.5 0 0 1 3.5 1h6.086a1.5 1.5 0 0 1 1.06.44l2.914 2.914A1.5 1.5 0 0 1 14 5.414V13.5a1.5 1.5 0 0 1-1.5 1.5h-9A1.5 1.5 0 0 1 2 13.5zM3.5 2a.5.5 0 0 0-.5.5v11a.5.5 0 0 0 .5.5h9a.5.5 0 0 0 .5-.5V5.5H10A1 1 0 0 1 9 4.5V2z"/>
                    </svg>
                </div>
                <h2 class="h5 mb-2">Blog</h2>
                <p class="text-muted mb-3">Read official updates, announcements, and release notes.</p>
                <div class="mt-auto">
                    <a class="btn btn-success w-100" href="/blog.php">Visit blog</a>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100 table-card">
            <div class="card-body d-flex flex-column">
                <div class="home-card-icon bg-warning-subtle text-warning mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
                        <path d="M4.5 2A1.5 1.5 0 0 0 3 3.5v1A1.5 1.5 0 0 0 4.5 6h7A1.5 1.5 0 0 0 13 4.5v-1A1.5 1.5 0 0 0 11.5 2zM4 3.5a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-7a.5.5 0 0 1-.5-.5z"/>
                        <path d="M4.5 7A1.5 1.5 0 0 0 3 8.5v1A1.5 1.5 0 0 0 4.5 11h7A1.5 1.5 0 0 0 13 9.5v-1A1.5 1.5 0 0 0 11.5 7zM4 8.5a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-7a.5.5 0 0 1-.5-.5z"/>
                        <path d="M4.5 12A1.5 1.5 0 0 0 3 13.5v1A1.5 1.5 0 0 0 4.5 16h7A1.5 1.5 0 0 0 13 14.5v-1A1.5 1.5 0 0 0 11.5 12zM4 13.5a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-7a.5.5 0 0 1-.5-.5z"/>
                    </svg>
                </div>
                <h2 class="h5 mb-2">Tickets</h2>
                <p class="text-muted mb-3">Submit and track support tickets for data requests or issues.</p>
                <div class="mt-auto">
                    <a class="btn btn-warning w-100" href="/ticket_status_check.php">Check tickets</a>
                </div>
            </div>
        </div>
    </div>
</div>
<section class="mt-5">
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h2 class="h5 mb-3">Disclaimer</h2>
            <p class="text-muted mb-3">
                This web portal has been developed exclusively for internal testing, training, and process validation purposes of the Kerala Development and Innovation Strategic Council (K-DISC).
            </p>
            <p class="text-muted mb-3">
                The portal is not a production system and shall not be used for official, operational, or decision-making purposes. The data, records, values, reports, and outputs displayed or generated through this portal may be incomplete, inaccurate, simulated, or erroneous, and do not represent official or final information of K-DISC.
            </p>
            <p class="text-muted mb-3">
                Access to this portal is strictly restricted to authorized Training and Testing Teams, including Headquarters officials and designated field personnel, for the limited purpose of system testing, training, and workflow familiarization.
            </p>
            <p class="text-muted mb-0">
                K-DISC shall not be held responsible for any direct or indirect consequences arising from the use, interpretation, or reliance on the information available on this portal.
            </p>
        </div>
    </div>
</section>
<?php include __DIR__ . '/partials/footer.php'; ?>
