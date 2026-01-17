<div class="card table-card mb-3">
    <div class="card-body">
        <h2 class="h6 mb-3">Team master</h2>
        <form class="row g-2 mb-4" method="post">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <input type="hidden" name="action" value="create_team">
            <div class="col-md-8">
                <label class="form-label">Team name</label>
                <input type="text" name="name" class="form-control" placeholder="Enter team name" required>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button class="btn btn-primary w-100" type="submit">Add team</button>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col">Team</th>
                        <th scope="col" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($teams as $team): ?>
                        <tr>
                            <td>
                                <div class="row g-2 align-items-center">
                                    <div class="col-md-8">
                                        <form class="d-flex gap-2" method="post">
                                            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                            <input type="hidden" name="action" value="update_team">
                                            <input type="hidden" name="team_id" value="<?php echo (int) $team['id']; ?>">
                                            <input type="text" name="name" class="form-control form-control-sm" value="<?php echo htmlspecialchars($team['name']); ?>" required>
                                            <button class="btn btn-sm btn-outline-primary" type="submit">Save</button>
                                        </form>
                                    </div>
                                    <div class="col-md-4">
                                        <form method="post">
                                            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                            <input type="hidden" name="action" value="delete_team">
                                            <input type="hidden" name="team_id" value="<?php echo (int) $team['id']; ?>">
                                            <button class="btn btn-sm btn-outline-danger w-100" type="submit">Delete</button>
                                        </form>
                                    </div>
                                </div>
                            </td>
                            <td class="text-end text-muted small">ID: <?php echo (int) $team['id']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($teams)): ?>
                        <tr>
                            <td colspan="2" class="text-center py-3">No teams available.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
