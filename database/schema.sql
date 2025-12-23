CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin','editor') NOT NULL DEFAULT 'editor',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS districts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL UNIQUE
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
    authority_type VARCHAR(120) NOT NULL
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
    active_status TINYINT(1) NOT NULL DEFAULT 1,
    FOREIGN KEY (district_id) REFERENCES districts(id)
);

CREATE TABLE IF NOT EXISTS blog_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    content_html MEDIUMTEXT NOT NULL,
    published_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO users (username, password_hash, role)
VALUES ('admin', '$2y$10$vvYhCcaDKGStkW2iULv9Ku0r6MQlQLd0ArwdlS.gY91cgs5leYeYm', 'admin')
ON DUPLICATE KEY UPDATE username = username;
