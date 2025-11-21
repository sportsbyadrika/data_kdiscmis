<div class="card table-card mb-3">
    <div class="card-body">
        <h2 class="h6 mb-3">Geography Masters</h2>
        <div class="row g-2 align-items-end">
            <form class="col-md-6" method="post">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                <input type="hidden" name="action" value="create_district">
                <label class="form-label">New District</label>
                <div class="input-group">
                    <input type="text" name="name" class="form-control" placeholder="District name" required>
                    <button class="btn btn-outline-primary" type="submit">Add</button>
                </div>
            </form>
            <form class="col-md-6" method="post">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                <input type="hidden" name="action" value="create_block_panchayat">
                <label class="form-label">New Block Panchayat</label>
                <div class="input-group">
                    <input type="text" name="name" class="form-control" placeholder="Block name" required>
                    <button class="btn btn-outline-primary" type="submit">Add</button>
                </div>
            </form>
        </div>
    </div>
</div>
