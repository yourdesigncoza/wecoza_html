-- WeCoza Class Management Database Schema for MySQL
-- Based on classes_schema_1.sql adapted for MySQL with JSON fields

-- Create database (run this separately if needed)
-- CREATE DATABASE wecoza_classes CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE wecoza_classes;

-- Create classes table with JSON fields for flexibility
CREATE TABLE IF NOT EXISTS classes (
    class_id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT,
    site_id VARCHAR(50),
    class_address_line TEXT,
    class_type VARCHAR(50),
    class_subject VARCHAR(255),
    class_code VARCHAR(100) UNIQUE NOT NULL,
    class_duration INT, -- Duration in days
    original_start_date DATE,
    seta_funded BOOLEAN DEFAULT FALSE,
    seta VARCHAR(50),
    exam_class BOOLEAN DEFAULT FALSE,
    exam_type VARCHAR(100),
    qa_visit_dates TEXT,
    class_agent INT,
    initial_class_agent INT,
    initial_agent_start_date DATE,
    project_supervisor_id INT,
    delivery_date DATE,
    
    -- JSON fields for flexible data storage
    learner_ids JSON DEFAULT ('[]'),
    backup_agent_ids JSON DEFAULT ('[]'),
    schedule_data JSON DEFAULT ('{}'),
    stop_restart_dates JSON DEFAULT ('[]'),
    class_notes_data JSON DEFAULT ('[]'),
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create indexes for better performance
CREATE INDEX idx_classes_client_id ON classes(client_id);
CREATE INDEX idx_classes_class_type ON classes(class_type);
CREATE INDEX idx_classes_class_agent ON classes(class_agent);
CREATE INDEX idx_classes_supervisor ON classes(project_supervisor_id);
CREATE INDEX idx_classes_start_date ON classes(original_start_date);
CREATE INDEX idx_classes_created_at ON classes(created_at);
CREATE INDEX idx_classes_class_code ON classes(class_code);

-- Create users table for authentication (basic implementation)
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    role VARCHAR(50) DEFAULT 'user',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create indexes for users table
CREATE INDEX idx_users_username ON users(username);
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_role ON users(role);

-- Create file uploads table for tracking uploaded files
CREATE TABLE IF NOT EXISTS file_uploads (
    file_id INT AUTO_INCREMENT PRIMARY KEY,
    class_id INT,
    original_filename VARCHAR(255) NOT NULL,
    stored_filename VARCHAR(255) NOT NULL,
    file_path TEXT NOT NULL,
    file_size INT,
    mime_type VARCHAR(100),
    upload_type VARCHAR(50), -- 'qa_report', 'document', etc.
    uploaded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (class_id) REFERENCES classes(class_id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create indexes for file uploads
CREATE INDEX idx_file_uploads_class_id ON file_uploads(class_id);
CREATE INDEX idx_file_uploads_upload_type ON file_uploads(upload_type);

-- Create audit log table for tracking changes
CREATE TABLE IF NOT EXISTS audit_log (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    table_name VARCHAR(100) NOT NULL,
    record_id INT NOT NULL,
    action VARCHAR(20) NOT NULL, -- 'INSERT', 'UPDATE', 'DELETE'
    old_values JSON,
    new_values JSON,
    changed_by INT,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (changed_by) REFERENCES users(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create indexes for audit log
CREATE INDEX idx_audit_log_table_record ON audit_log(table_name, record_id);
CREATE INDEX idx_audit_log_changed_at ON audit_log(changed_at);

-- Insert default admin user (password: 'admin123' - change in production!)
INSERT INTO users (username, email, password_hash, first_name, last_name, role) 
VALUES (
    'admin', 
    'admin@wecoza.co.za', 
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: 'admin123'
    'System', 
    'Administrator', 
    'admin'
) ON DUPLICATE KEY UPDATE username = username;

-- Insert sample data for testing (optional)
INSERT INTO classes (
    client_id, 
    site_id, 
    class_address_line,
    class_type, 
    class_subject, 
    class_code, 
    class_duration, 
    original_start_date,
    seta_funded,
    seta,
    exam_class,
    exam_type,
    class_agent,
    project_supervisor_id,
    learner_ids,
    backup_agent_ids,
    schedule_data,
    class_notes_data
) VALUES (
    11, -- Aspen Pharmacare
    '11_1', -- Head Office
    '100 Pharma Rd, Durban, 4001',
    'employed',
    'Basic Computer Skills',
    'EMP-011-0001',
    30,
    '2025-02-01',
    true,
    'HWSETA',
    false,
    null,
    1, -- Michael M. van der Berg
    1, -- Ethan J. Williams
    JSON_ARRAY(1, 2, 3), -- John, Nosipho, Ahmed
    JSON_ARRAY(2, 3), -- Thandi, Rajesh as backup
    JSON_OBJECT(
        'days', JSON_ARRAY('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'),
        'start_time', '09:00',
        'end_time', '16:00',
        'break_times', JSON_ARRAY('10:30-10:45', '12:00-13:00', '14:30-14:45')
    ),
    JSON_ARRAY(
        JSON_OBJECT(
            'type', 'Venue Confirmed',
            'note', 'Training room booked and confirmed',
            'date', '2025-01-15',
            'user', 'admin'
        )
    )
) ON DUPLICATE KEY UPDATE class_code = class_code;

-- Display success message
SELECT 'Database schema initialized successfully!' as status;
