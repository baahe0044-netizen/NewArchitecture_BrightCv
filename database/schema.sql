CREATE DATABASE IF NOT EXISTS lunettistar_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE lunettistar_db;

CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    auth_version INT UNSIGNED NOT NULL DEFAULT 1,
    role VARCHAR(30) NOT NULL DEFAULT 'user',
    plan VARCHAR(30) NOT NULL DEFAULT 'free',
    locale VARCHAR(10) NOT NULL DEFAULT 'en',
    job_title VARCHAR(120) NULL,
    avatar_path VARCHAR(255) NULL,
    email_verified_at DATETIME NULL,
    last_login_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_users_created_at (created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS resume_templates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    template_key VARCHAR(60) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    category VARCHAR(60) NOT NULL,
    description VARCHAR(255) NOT NULL,
    color VARCHAR(20) NOT NULL DEFAULT '#5b4df7',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    is_premium TINYINT(1) NOT NULL DEFAULT 0,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_templates_active (is_active, sort_order)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS resumes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    template_key VARCHAR(60) NOT NULL DEFAULT 'modern',
    language VARCHAR(10) NOT NULL DEFAULT 'en',
    accent_color VARCHAR(20) NOT NULL DEFAULT '#5b4df7',
    font_family VARCHAR(60) NOT NULL DEFAULT 'Inter',
    content_json LONGTEXT NOT NULL,
    job_description LONGTEXT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'draft',
    completion TINYINT UNSIGNED NOT NULL DEFAULT 0,
    ats_score TINYINT UNSIGNED NOT NULL DEFAULT 0,
    version INT UNSIGNED NOT NULL DEFAULT 1,
    last_exported_at DATETIME NULL,
    deleted_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_resumes_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_resumes_user_updated (user_id, updated_at),
    INDEX idx_resumes_user_status (user_id, status, deleted_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS resume_versions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    resume_id BIGINT UNSIGNED NOT NULL,
    version INT UNSIGNED NOT NULL,
    content_json LONGTEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_versions_resume FOREIGN KEY (resume_id) REFERENCES resumes(id) ON DELETE CASCADE,
    UNIQUE KEY uq_resume_version (resume_id, version)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS resume_ats_scores (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    resume_id BIGINT UNSIGNED NOT NULL,
    score TINYINT UNSIGNED NOT NULL,
    keyword_score TINYINT UNSIGNED NOT NULL DEFAULT 0,
    report_json LONGTEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ats_resume FOREIGN KEY (resume_id) REFERENCES resumes(id) ON DELETE CASCADE,
    INDEX idx_ats_resume_created (resume_id, created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS resume_generations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    resume_id BIGINT UNSIGNED NOT NULL,
    format VARCHAR(20) NOT NULL DEFAULT 'pdf',
    language VARCHAR(10) NOT NULL DEFAULT 'en',
    file_name VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_generations_resume FOREIGN KEY (resume_id) REFERENCES resumes(id) ON DELETE CASCADE,
    INDEX idx_generations_resume_created (resume_id, created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS resume_ai_history (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    resume_id BIGINT UNSIGNED NOT NULL,
    action VARCHAR(60) NOT NULL,
    input_text LONGTEXT NULL,
    output_text LONGTEXT NOT NULL,
    provider VARCHAR(30) NOT NULL DEFAULT 'local',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ai_resume FOREIGN KEY (resume_id) REFERENCES resumes(id) ON DELETE CASCADE,
    INDEX idx_ai_resume_created (resume_id, created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS user_activity (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    resume_id BIGINT UNSIGNED NULL,
    action VARCHAR(60) NOT NULL,
    description VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_activity_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_activity_resume FOREIGN KEY (resume_id) REFERENCES resumes(id) ON DELETE SET NULL,
    INDEX idx_activity_user_created (user_id, created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(190) NOT NULL,
    token_hash VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_reset_email_expires (email, expires_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS rate_limits (
    key_hash CHAR(64) PRIMARY KEY,
    attempts INT UNSIGNED NOT NULL DEFAULT 0,
    expires_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_rate_limits_expires (expires_at)
) ENGINE=InnoDB;

INSERT INTO resume_templates
    (template_key, name, category, description, color, is_active, is_premium, sort_order)
VALUES
    ('modern', 'Modern Focus', 'Modern', 'Clean hierarchy with an ATS-friendly single-column body.', '#5b4df7', 1, 0, 10),
    ('executive', 'Executive Edge', 'Professional', 'Refined two-column layout for experienced professionals.', '#16324f', 1, 0, 20),
    ('minimal', 'Quiet Minimal', 'Classic', 'Elegant typography and generous white space.', '#202124', 1, 0, 30),
    ('creative', 'Creative Spark', 'Creative', 'Expressive sidebar design for creative portfolios.', '#e25241', 1, 0, 40),
    ('graduate', 'Graduate Start', 'Modern', 'Skills-forward format for students and new graduates.', '#087f5b', 1, 0, 50),
    ('tech', 'Technical Pro', 'Professional', 'Compact project and impact layout for technology roles.', '#075985', 1, 0, 60)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    category = VALUES(category),
    description = VALUES(description),
    color = VALUES(color),
    is_active = VALUES(is_active),
    is_premium = VALUES(is_premium),
    sort_order = VALUES(sort_order);
