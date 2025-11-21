<div class="card table-card mb-3">
    <div class="card-body">
        <h2 class="h6 mb-3">Users</h2>
        <form method="post" class="row g-2 align-items-end">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <input type="hidden" name="action" value="create_user">
            <div class="col-12">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" required>
            </div>
            <div class="col-12">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <div class="col-6">
                <label class="form-label">Role</label>
                <select name="role" class="form-select">
                    <option value="admin">Admin</option>
                    <option value="editor">Editor</option>
                </select>
            </div>
            <div class="col-6">
                <button class="btn btn-primary w-100" type="submit">Add User</button>
            </div>
        </form>
    </div>
</div>
