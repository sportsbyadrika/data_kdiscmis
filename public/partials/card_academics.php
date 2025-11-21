<div class="card table-card mb-3">
    <div class="card-body">
        <h2 class="h6 mb-3">Qualification Categories</h2>
        <form class="row g-2 mb-3" method="post">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <input type="hidden" name="action" value="create_qualification_category">
            <div class="col-md-8">
                <label class="form-label">Category Name</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button class="btn btn-primary w-100" type="submit">Add Category</button>
            </div>
        </form>
        <hr>
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
                <label class="form-label">Qualification Category</label>
                <select name="qualification_category" class="form-select">
                    <option value="">Select category</option>
                    <?php foreach ($options['qualification_categories'] as $category): ?>
                        <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                    <?php endforeach; ?>
                </select>
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
                <label class="form-label">Qualification Category</label>
                <select name="qualification_category" class="form-select">
                    <option value="">Select category</option>
                    <?php foreach ($options['qualification_categories'] as $category): ?>
                        <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12">
                <button class="btn btn-primary" type="submit">Add Course</button>
            </div>
        </form>
    </div>
</div>
