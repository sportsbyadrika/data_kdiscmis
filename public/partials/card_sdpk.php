<div class="card table-card mb-3">
    <div class="card-body">
        <h2 class="h6 mb-3">SDPK Centers</h2>
        <form class="row g-2" method="post">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <input type="hidden" name="action" value="create_sdpk_center">
            <div class="col-md-6">
                <label class="form-label">Center Code</label>
                <input type="text" name="code" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Center Name</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Center Type</label>
                <input type="text" name="center_type" class="form-control" maxlength="5" placeholder="Type code">
            </div>
            <div class="col-12">
                <label class="form-label">Address</label>
                <textarea name="address" class="form-control" rows="2" required></textarea>
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
            <div class="col-md-3">
                <label class="form-label">Latitude</label>
                <input type="number" name="latitude" class="form-control" step="0.000001" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Longitude</label>
                <input type="number" name="longitude" class="form-control" step="0.000001" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Status</label>
                <select name="active_status" class="form-select">
                    <option value="1" selected>Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
            <div class="col-12">
                <button class="btn btn-primary" type="submit">Add SDPK Center</button>
            </div>
        </form>
    </div>
</div>
