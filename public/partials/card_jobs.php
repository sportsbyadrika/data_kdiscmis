<div class="card table-card mb-3">
    <div class="card-body">
        <h2 class="h6 mb-3">Job Stations</h2>
        <form class="row g-2" method="post">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <input type="hidden" name="action" value="create_job_station">
            <div class="col-12">
                <label class="form-label">Job Station Name</label>
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
            <div class="col-md-3">
                <label class="form-label">Latitude</label>
                <input type="number" name="latitude" class="form-control" step="0.000001" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Longitude</label>
                <input type="number" name="longitude" class="form-control" step="0.000001" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Block Panchayat (optional)</label>
                <select name="block_panchayat_id" class="form-select">
                    <option value="">None</option>
                    <?php foreach ($options['block_panchayats'] as $block): ?>
                        <option value="<?php echo $block['id']; ?>"><?php echo htmlspecialchars($block['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12">
                <button class="btn btn-primary" type="submit">Add Job Station</button>
            </div>
        </form>
        <hr class="my-4">
        <h2 class="h6 mb-3">Facilitation Centers</h2>
        <form class="row g-2" method="post">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <input type="hidden" name="action" value="create_facilitation_center">
            <div class="col-12">
                <label class="form-label">Facilitation Center Name</label>
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
            <div class="col-md-3">
                <label class="form-label">Latitude</label>
                <input type="number" name="latitude" class="form-control" step="0.000001" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Longitude</label>
                <input type="number" name="longitude" class="form-control" step="0.000001" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Block Panchayat (optional)</label>
                <select name="block_panchayat_id" class="form-select">
                    <option value="">None</option>
                    <?php foreach ($options['block_panchayats'] as $block): ?>
                        <option value="<?php echo $block['id']; ?>"><?php echo htmlspecialchars($block['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12">
                <label class="form-label">Local Body</label>
                <select name="local_body_id" class="form-select" required>
                    <option value="">Select local body</option>
                    <?php foreach ($options['local_bodies'] as $localBody): ?>
                        <option value="<?php echo $localBody['id']; ?>"><?php echo htmlspecialchars($localBody['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12">
                <button class="btn btn-primary" type="submit">Add Facilitation Center</button>
            </div>
        </form>
    </div>
</div>
