CREATE TABLE IF NOT EXISTS districts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(180) NOT NULL,
    mobile VARCHAR(20) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('super_admin','state_user','district_user','localbody_user') NOT NULL DEFAULT 'localbody_user',
    team_id INT NULL,
    team_role ENUM('operator','verifier','approver') NOT NULL DEFAULT 'operator',
    district_id INT NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_users_created_by FOREIGN KEY (created_by) REFERENCES users(id),
    CONSTRAINT fk_users_district FOREIGN KEY (district_id) REFERENCES districts(id)
);

CREATE TABLE IF NOT EXISTS teams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL UNIQUE
);

ALTER TABLE users
    ADD CONSTRAINT fk_users_team FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE SET NULL;

CREATE TABLE IF NOT EXISTS user_functionalities (
    user_id INT NOT NULL,
    functionality VARCHAR(120) NOT NULL,
    PRIMARY KEY (user_id, functionality),
    CONSTRAINT fk_user_functionalities_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS applicants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    unique_id VARCHAR(120) NOT NULL UNIQUE,
    name VARCHAR(180) NOT NULL,
    mobile VARCHAR(20) NOT NULL,
    email VARCHAR(180) NULL,
    details_html MEDIUMTEXT NULL,
    data_status VARCHAR(120) NOT NULL,
    purpose VARCHAR(200) NOT NULL,
    crm_status VARCHAR(50) NOT NULL DEFAULT 'CRM Pending',
    crm_remarks TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS call_statuses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL UNIQUE,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active'
);

CREATE TABLE IF NOT EXISTS applicant_crm_calls (
    id INT AUTO_INCREMENT PRIMARY KEY,
    applicant_id INT NOT NULL,
    call_date DATETIME NOT NULL,
    duration VARCHAR(50) NULL,
    call_status_id INT NULL,
    remarks TEXT NULL,
    contacted_by VARCHAR(150) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_applicant_crm_calls_applicant FOREIGN KEY (applicant_id) REFERENCES applicants(id) ON DELETE CASCADE,
    CONSTRAINT fk_applicant_crm_calls_status FOREIGN KEY (call_status_id) REFERENCES call_statuses(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS qualification_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL UNIQUE,
    criteria TEXT NULL
);

CREATE TABLE IF NOT EXISTS local_body_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS block_panchayats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS local_bodies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lb_code VARCHAR(50) NOT NULL UNIQUE,
    block_lb_code VARCHAR(50) DEFAULT NULL,
    name VARCHAR(200) NOT NULL,
    district_id INT NOT NULL,
    local_body_type_id INT NOT NULL,
    FOREIGN KEY (district_id) REFERENCES districts(id),
    FOREIGN KEY (local_body_type_id) REFERENCES local_body_types(id)
);

CREATE TABLE IF NOT EXISTS job_stations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    district_id INT NOT NULL,
    latitude DECIMAL(10, 6) NOT NULL,
    longitude DECIMAL(10, 6) NOT NULL,
    block_panchayat_id INT NULL,
    FOREIGN KEY (district_id) REFERENCES districts(id),
    FOREIGN KEY (block_panchayat_id) REFERENCES block_panchayats(id)
);

CREATE TABLE IF NOT EXISTS facilitation_centers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    district_id INT NOT NULL,
    latitude DECIMAL(10, 6) NOT NULL,
    longitude DECIMAL(10, 6) NOT NULL,
    block_panchayat_id INT NULL,
    local_body_id INT NOT NULL,
    FOREIGN KEY (district_id) REFERENCES districts(id),
    FOREIGN KEY (block_panchayat_id) REFERENCES block_panchayats(id),
    FOREIGN KEY (local_body_id) REFERENCES local_bodies(id)
);

CREATE TABLE IF NOT EXISTS academic_institutions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    district_id INT NOT NULL,
    latitude DECIMAL(10, 6) NOT NULL,
    longitude DECIMAL(10, 6) NOT NULL,
    qualification_category INT NULL,
    institution_type VARCHAR(120) DEFAULT '',
    FOREIGN KEY (district_id) REFERENCES districts(id),
    FOREIGN KEY (qualification_category) REFERENCES qualification_categories(id)
);

CREATE TABLE IF NOT EXISTS education_courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    district_id INT NOT NULL,
    qualification_category INT NULL,
    FOREIGN KEY (district_id) REFERENCES districts(id),
    FOREIGN KEY (qualification_category) REFERENCES qualification_categories(id)
);

CREATE TABLE IF NOT EXISTS academic_authorities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(200) NOT NULL,
    authority_type VARCHAR(120) NOT NULL,
    district_id INT NOT NULL,
    local_body_code VARCHAR(80) NULL,
    website VARCHAR(255) NULL,
    address VARCHAR(255) NULL,
    latitude DECIMAL(10, 6) NULL,
    longitude DECIMAL(10, 6) NULL,
    year_established INT NULL,
    sub_category VARCHAR(120) NULL,
    FOREIGN KEY (district_id) REFERENCES districts(id)
);

CREATE TABLE IF NOT EXISTS cds_list (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    district_id INT NOT NULL,
    local_body_type_id INT NOT NULL,
    FOREIGN KEY (district_id) REFERENCES districts(id),
    FOREIGN KEY (local_body_type_id) REFERENCES local_body_types(id)
);

CREATE TABLE IF NOT EXISTS ads_list (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    district_id INT NOT NULL,
    local_body_type_id INT NOT NULL,
    local_body_id INT NOT NULL,
    FOREIGN KEY (district_id) REFERENCES districts(id),
    FOREIGN KEY (local_body_type_id) REFERENCES local_body_types(id),
    FOREIGN KEY (local_body_id) REFERENCES local_bodies(id)
);

CREATE TABLE IF NOT EXISTS sdpk_centers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(200) NOT NULL,
    center_type VARCHAR(5) NULL,
    address VARCHAR(255) NOT NULL,
    district_id INT NOT NULL,
    latitude DECIMAL(10, 6) NOT NULL,
    longitude DECIMAL(10, 6) NOT NULL,
    phase TINYINT(2) NOT NULL DEFAULT 1,
    active_status TINYINT(1) NOT NULL DEFAULT 1,
    FOREIGN KEY (district_id) REFERENCES districts(id)
);

CREATE TABLE IF NOT EXISTS job_fair_intends (
    id INT AUTO_INCREMENT PRIMARY KEY,
    intend_date DATE NOT NULL,
    reference_committee_number VARCHAR(120) NOT NULL,
    reference_date DATE NULL,
    reference_job_fair_number VARCHAR(120) NOT NULL,
    job_fair_date DATE NULL,
    target_openings INT NULL,
    minimum_hr_required INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS job_fair_intend_locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    intend_id INT NOT NULL,
    location_type VARCHAR(80) NOT NULL,
    sdpk_center_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (intend_id) REFERENCES job_fair_intends(id) ON DELETE CASCADE,
    FOREIGN KEY (sdpk_center_id) REFERENCES sdpk_centers(id)
);

CREATE TABLE IF NOT EXISTS aggregators (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(200) NOT NULL,
    spoc_name VARCHAR(150) NULL,
    spoc_mobile VARCHAR(30) NULL,
    spoc_email VARCHAR(180) NULL
);

CREATE TABLE IF NOT EXISTS employers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(200) NOT NULL,
    spoc_name VARCHAR(150) NULL,
    spoc_mobile VARCHAR(30) NULL,
    spoc_email VARCHAR(180) NULL,
    aggregator_id INT NULL,
    FOREIGN KEY (aggregator_id) REFERENCES aggregators(id)
);

CREATE TABLE IF NOT EXISTS education_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS salary_ranges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS job_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS job_titles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_code VARCHAR(50) NOT NULL UNIQUE,
    job_title VARCHAR(200) NOT NULL,
    employer_id INT NOT NULL,
    openings INT NOT NULL DEFAULT 0,
    education_category_id INT NULL,
    salary_range_id INT NULL,
    job_category_id INT NULL,
    job_location VARCHAR(200) NULL,
    job_description TEXT NULL,
    job_details TEXT NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    FOREIGN KEY (employer_id) REFERENCES employers(id),
    FOREIGN KEY (education_category_id) REFERENCES education_categories(id),
    FOREIGN KEY (salary_range_id) REFERENCES salary_ranges(id),
    FOREIGN KEY (job_category_id) REFERENCES job_categories(id)
);

CREATE TABLE IF NOT EXISTS job_fair_intend_employers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    intend_id INT NOT NULL,
    employer_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (intend_id) REFERENCES job_fair_intends(id) ON DELETE CASCADE,
    FOREIGN KEY (employer_id) REFERENCES employers(id)
);

CREATE TABLE IF NOT EXISTS job_fair_intend_job_titles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    intend_id INT NOT NULL,
    job_title_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (intend_id) REFERENCES job_fair_intends(id) ON DELETE CASCADE,
    FOREIGN KEY (job_title_id) REFERENCES job_titles(id)
);


CREATE TABLE IF NOT EXISTS dsm_employer_activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employer_id INT NOT NULL,
    activity_type ENUM('create','edit') NOT NULL,
    activity_date DATE NOT NULL,
    changed_by_user_id INT NOT NULL,
    change_notes JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employer_id) REFERENCES employers(id),
    FOREIGN KEY (changed_by_user_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS dsm_job_title_activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_title_id INT NOT NULL,
    activity_type ENUM('create','edit') NOT NULL,
    activity_date DATE NOT NULL,
    changed_by_user_id INT NOT NULL,
    previous_values JSON NULL,
    new_values JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (job_title_id) REFERENCES job_titles(id),
    FOREIGN KEY (changed_by_user_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS dsm_task_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL UNIQUE,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active'
);

INSERT IGNORE INTO dsm_task_types (name, status) VALUES
    ('New Employer', 'active'),
    ('Employer details edit', 'active'),
    ('New Job title', 'active'),
    ('Modify Job details', 'active'),
    ('Employer meetings', 'active'),
    ('Aggregator meetings', 'active'),
    ('Team meetings', 'active');

CREATE TABLE IF NOT EXISTS dsm_daily_tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_date DATE NOT NULL,
    task_type_id INT NOT NULL,
    task_title VARCHAR(255) NOT NULL,
    task_details TEXT NULL,
    job_fair_number VARCHAR(120) NULL,
    aggregator_id INT NULL,
    employer_id INT NULL,
    job_title_id INT NULL,
    meeting_owner VARCHAR(150) NULL,
    meeting_members TEXT NULL,
    duration VARCHAR(50) NULL,
    result ENUM('Closed','Pending','Cancelled') NOT NULL DEFAULT 'Pending',
    result_details TEXT NULL,
    call_status ENUM('Connected','Not responding','Rescheduled') NULL,
    created_by_user_id INT NOT NULL,
    updated_by_user_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (task_type_id) REFERENCES dsm_task_types(id),
    FOREIGN KEY (aggregator_id) REFERENCES aggregators(id),
    FOREIGN KEY (employer_id) REFERENCES employers(id),
    FOREIGN KEY (job_title_id) REFERENCES job_titles(id),
    FOREIGN KEY (created_by_user_id) REFERENCES users(id),
    FOREIGN KEY (updated_by_user_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS blog_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    content_html MEDIUMTEXT NOT NULL,
    published_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS issue_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL UNIQUE,
    description VARCHAR(255) NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tracker_number VARCHAR(30) NULL UNIQUE,
    category_id INT NOT NULL,
    district_id INT NOT NULL,
    reference_institution VARCHAR(200) NOT NULL,
    event_name VARCHAR(200) NULL,
    reported_by VARCHAR(150) NOT NULL,
    reported_mobile VARCHAR(20) NOT NULL,
    reported_email VARCHAR(180) NOT NULL,
    issue_details TEXT NOT NULL,
    status ENUM('Pending','Resolved') NOT NULL DEFAULT 'Pending',
    resolution_text TEXT NULL,
    resolved_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES issue_categories(id),
    FOREIGN KEY (district_id) REFERENCES districts(id)
);

CREATE TABLE IF NOT EXISTS ticket_attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_type VARCHAR(50) NOT NULL,
    uploaded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id)
);

CREATE TABLE IF NOT EXISTS job_fair_daily_tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    meeting_date DATE NOT NULL,
    meeting_number VARCHAR(120) NOT NULL,
    job_fair_date DATE NULL,
    locations_usual_sdpk INT NULL,
    locations_additional INT NULL,
    locational_functional_requirements TEXT NULL,
    campaign_target TEXT NULL,
    openings INT NULL,
    remark_sectoral_preference TEXT NULL,
    remark_impact_planned TEXT NULL,
    minutes_file_name VARCHAR(255) NULL,
    minutes_file_path VARCHAR(255) NULL,
    minutes_file_type VARCHAR(50) NULL,
    members_participated TEXT NULL,
    job_fair_number VARCHAR(120) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO users (name, email, mobile, password_hash, role, status)
VALUES (
    'Super Admin',
    'superadmin@example.com',
    '9999999999',
    '$2y$12$I8ll1x/NL2kw.jRWyLt2Wu5C.9reb9.e4uYKsnVf7ZZmNbJVlQ4sm',
    'super_admin',
    'active'
)
ON DUPLICATE KEY UPDATE mobile = mobile;

INSERT INTO issue_categories (name, description)
VALUES
    ('Data Correction', 'Request to correct or update master data'),
    ('Login Issue', 'Unable to access the portal or credentials not working'),
    ('System Bug', 'Unexpected errors or performance issues')
ON DUPLICATE KEY UPDATE name = name;

INSERT INTO teams (name)
VALUES
    ('Member Secretary Office'),
    ('State CRM'),
    ('Platform'),
    ('Data'),
    ('DSM')
ON DUPLICATE KEY UPDATE name = name;

INSERT INTO call_statuses (name, status)
VALUES
    ('Connected', 'active'),
    ('No Answer', 'active'),
    ('Busy', 'active'),
    ('Follow Up', 'active'),
    ('Cancelled', 'active')
ON DUPLICATE KEY UPDATE name = name;
