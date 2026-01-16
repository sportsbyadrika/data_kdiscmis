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
    district_id INT NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_users_created_by FOREIGN KEY (created_by) REFERENCES users(id),
    CONSTRAINT fk_users_district FOREIGN KEY (district_id) REFERENCES districts(id)
);

CREATE TABLE IF NOT EXISTS qualification_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL UNIQUE
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
    address VARCHAR(255) NOT NULL,
    district_id INT NOT NULL,
    latitude DECIMAL(10, 6) NOT NULL,
    longitude DECIMAL(10, 6) NOT NULL,
    phase TINYINT(2) NOT NULL DEFAULT 1,
    active_status TINYINT(1) NOT NULL DEFAULT 1,
    FOREIGN KEY (district_id) REFERENCES districts(id)
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
