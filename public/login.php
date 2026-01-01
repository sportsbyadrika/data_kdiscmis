<?php
require_once __DIR__ . '/../src/auth.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mobile = trim($_POST['mobile'] ?? '');
    $password = $_POST['password'] ?? '';
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request token.';
    } elseif ($mobile && $password && login($mobile, $password)) {
        header('Location: /admin.php');
        exit();
    } else {
        $error = 'Invalid credentials. Please try again.';
    }
}
include __DIR__ . '/partials/header.php';
?>
<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card table-card">
            <div class="card-body">
                <h1 class="h4 mb-3">User Login</h1>
                <?php if ($error): ?>
                    <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <form method="post" class="vstack gap-3">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    <div>
                        <label class="form-label">Mobile number</label>
                        <input type="text" name="mobile" class="form-control" required>
                    </div>
                    <div>
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <button class="btn btn-primary" type="submit">Login</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>
