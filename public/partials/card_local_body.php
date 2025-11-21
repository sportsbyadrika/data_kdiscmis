<div class="card table-card mb-3">
    <div class="card-body">
        <h2 class="h6 mb-3">Local Body Masters</h2>
        <form class="row g-2" method="post">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <input type="hidden" name="action" value="create_local_body_type">
            <div class="col-8">
                <label class="form-label">Local Body Type</label>
                <input type="text" name="name" class="form-control" placeholder="Corporation, Municipality" required>
            </div>
            <div class="col-4">
                <label class="form-label">&nbsp;</label>
                <button class="btn btn-outline-primary w-100" type="submit">Add Type</button>
            </div>
        </form>
        <hr>
        <form class="row g-2" method="post">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <input type="hidden" name="action" value="create_local_body">
            <div class="col-12">
                <label class="form-label">Local Body Name</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">District</label>
                <select name="district_id" class="form-select" required>
                    <option value="">Select district</option>
                    <?php foreach ($options['districts'] as $district): ?>
                        <option value="<?php echo $district['id']; ?>"><?php echo htmlspecialchars($district['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Local Body Type</label>
                <select name="local_body_type_id" class="form-select" required>
                    <option value="">Select type</option>
                    <?php foreach ($options['local_body_types'] as $type): ?>
                        <option value="<?php echo $type['id']; ?>"><?php echo htmlspecialchars($type['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12">
                <button class="btn btn-primary" type="submit">Add Local Body</button>
            </div>
        </form>
    </div>
</div>
