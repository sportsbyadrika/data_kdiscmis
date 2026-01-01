<div class="card table-card mb-3">
    <div class="card-body">
        <h2 class="h6 mb-3">Users</h2>
        <?php $childRoleLabel = $childRole ? role_label($childRole) : ''; ?>
        <?php $requiresDistrict = in_array($childRole, [ROLE_DISTRICT_USER, ROLE_LOCALBODY_USER], true); ?>
        <?php if ($childRole): ?>
            <p class="text-muted small mb-3">
                You are a <?php echo htmlspecialchars(role_label($user['role'])); ?>. You can create <?php echo htmlspecialchars(strtolower($childRoleLabel)); ?> accounts. The mobile number is used as the username for login.
            </p>
            <form method="post" class="row g-3">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                <input type="hidden" name="action" value="create_user">
                <div class="col-12">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Mobile number (username)</label>
                    <input type="text" name="mobile" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <?php if ($requiresDistrict): ?>
                    <div class="col-12">
                        <label class="form-label">District</label>
                        <select name="district_id" class="form-select" <?php echo $childRole === ROLE_LOCALBODY_USER && $user['district_id'] ? 'disabled' : ''; ?> required>
                            <option value="">Select district</option>
                            <?php foreach ($options['districts'] as $district): ?>
                                <option value="<?php echo (int) $district['id']; ?>" <?php echo $user['district_id'] === (int) $district['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($district['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($childRole === ROLE_LOCALBODY_USER && $user['district_id']): ?>
                            <small class="text-muted">Localbody users will be tied to your district.</small>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <div class="col-md-6">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="col-md-6 d-flex align-items-end">
                    <button class="btn btn-primary w-100" type="submit">Create <?php echo htmlspecialchars(strtolower($childRoleLabel)); ?></button>
                </div>
            </form>
        <?php else: ?>
            <p class="text-muted mb-0">There are no subordinate roles under your level.</p>
        <?php endif; ?>
    </div>
</div>
