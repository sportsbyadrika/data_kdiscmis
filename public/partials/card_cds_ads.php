<div class="card table-card mb-3">
    <div class="card-body">
        <h2 class="h6 mb-3">CDS & ADS</h2>
        <form class="row g-2" method="post">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <input type="hidden" name="action" value="create_cds">
            <div class="col-12">
                <label class="form-label">CDS Name</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">District</label>
                <select name="district_id" class="form-select" required>
                    <option value="">Select district</option>
                    <?php foreach ($options['districts'] as $district): ?>
                        <option value="<?php echo $district['id']; ?>"><?php echo htmlspecialchars($district['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Local Body Type</label>
                <select name="local_body_type_id" class="form-select" required>
                    <option value="">Select type</option>
                    <?php foreach ($options['local_body_types'] as $type): ?>
                        <option value="<?php echo $type['id']; ?>"><?php echo htmlspecialchars($type['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button class="btn btn-primary w-100" type="submit">Add CDS</button>
            </div>
        </form>
        <hr>
        <form class="row g-2" method="post">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <input type="hidden" name="action" value="create_ads">
            <div class="col-12">
                <label class="form-label">ADS Name</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">District</label>
                <select name="district_id" class="form-select" required>
                    <option value="">Select district</option>
                    <?php foreach ($options['districts'] as $district): ?>
                        <option value="<?php echo $district['id']; ?>"><?php echo htmlspecialchars($district['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Local Body Type</label>
                <select name="local_body_type_id" class="form-select" required>
                    <option value="">Select type</option>
                    <?php foreach ($options['local_body_types'] as $type): ?>
                        <option value="<?php echo $type['id']; ?>"><?php echo htmlspecialchars($type['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Local Body</label>
                <select name="local_body_id" class="form-select" required>
                    <option value="">Select local body</option>
                    <?php foreach ($options['local_bodies'] as $local): ?>
                        <option value="<?php echo $local['id']; ?>"><?php echo htmlspecialchars($local['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button class="btn btn-primary w-100" type="submit">Add ADS</button>
            </div>
        </form>
    </div>
</div>
