<div class="row">
    <div class="col-md-6 mb-3">
        <label for="form-first_name" class="form-label">First Name</label>
        <input type="text" name="first_name" class="form-control" id="form-first_name" required>
    </div>
    <div class="col-md-6 mb-3">
        <label for="form-last_name" class="form-label">Last Name</label>
        <input type="text" name="last_name" class="form-control" id="form-last_name" required>
    </div>
</div>
<div class="row">
    <div class="col-md-6 mb-3">
        <label for="form-email" class="form-label">Email</label>
        <input type="email" name="email" class="form-control" id="form-email" required>
    </div>
    <div class="col-md-6 mb-3">
        <label for="form-phone" class="form-label">Phone</label>
        <input type="tel" name="phone" class="form-control" id="form-phone" required>
    </div>
</div>
<div class="row">
    <div class="col-md-6 mb-3 password-group">
        <label for="form-password" class="form-label">Password <small class="text-muted">(Optional for edit)</small></label>
        <input type="password" id="form-password" name="password" class="form-control">
        <i class="fas fa-eye toggle-eye" data-bs-target="#form-password"></i>
    </div>
    <div class="col-md-6 mb-3 password-group">
        <label for="form-confirm_password" class="form-label">Confirm Password</label>
        <input type="password" id="form-confirm_password" name="confirm_password" class="form-control">
        <i class="fas fa-eye toggle-eye" data-bs-target="#form-confirm_password"></i>
    </div>
</div>
<div class="mb-3">
    <label for="form-address" class="form-label">Address</label>
    <textarea name="address" class="form-control" id="form-address" rows="2"></textarea>
</div>
<div class="row">
    <div class="col-md-6 mb-3">
        <label for="form-license_no" class="form-label">Driving License No.</label>
        <input type="text" name="driving_license_no" class="form-control" id="form-license_no">
    </div>
    <div class="col-md-6 mb-3">
        <label for="form-blood_group" class="form-label">Blood Group</label>
        <select name="blood_group" id="form-blood_group" class="form-select">
            <option value="">-- Select --</option>
            <?php 
            $bloodGroups = ['A+','A-','B+','B-','AB+','AB-','O+','O-'];
            foreach ($bloodGroups as $bg) echo "<option value='$bg'>$bg</option>"; 
            ?>
        </select>
    </div>
</div>
<div class="row">
    <div class="col-md-4 mb-3">
        <label for="form-agent_image" class="form-label">Agent Image</label>
        <input type="file" name="agent_image" class="form-control" id="form-agent_image" accept="image/*">
        <div id="form-agent-image-preview"></div>
    </div>
    <div class="col-md-4 mb-3">
        <label for="form-license_image" class="form-label">Driving License Image</label>
        <input type="file" name="license_image" class="form-control" id="form-license_image" accept="image/*">
    </div>
    <div class="col-md-4 mb-3">
        <label for="form-clearance_image" class="form-label">Police Clearance Image</label>
        <input type="file" name="police_clearance_image" class="form-control" id="form-clearance_image" accept="image/*">
    </div>
</div>
<div>
    <label for="form-status" class="form-label">Status</label>
    <select name="status" id="form-status" class="form-select">
        <option value="active">Active</option>
        <option value="inactive">Inactive</option>
    </select>
</div>