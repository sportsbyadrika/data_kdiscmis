<div class="card table-card mb-3">
    <div class="card-body">
        <h2 class="h6 mb-3">Academic Institutions</h2>
        <form class="row g-2" method="post">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <input type="hidden" name="action" value="create_institution">
            <div class="col-12">
                <label class="form-label">Institution Name</label>
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
                <label class="form-label">Education Category</label>
                <input type="text" name="education_category" class="form-control" placeholder="Higher Secondary, ITI" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Institution Type</label>
                <input type="text" name="institution_type" class="form-control" placeholder="Public, Private" required>
            </div>
            <div class="col-12">
                <button class="btn btn-primary" type="submit">Add Institution</button>
            </div>
        </form>
        <hr>
        <h2 class="h6 mb-3">Courses/Trades</h2>
        <form class="row g-2" method="post">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <input type="hidden" name="action" value="create_course">
            <div class="col-12">
                <label class="form-label">Course or Trade</label>
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
                <label class="form-label">Education Category</label>
                <input type="text" name="education_category" class="form-control" required>
            </div>
            <div class="col-12">
                <button class="btn btn-primary" type="submit">Add Course</button>
            </div>
        </form>
    </div>
</div>
