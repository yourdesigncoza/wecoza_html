-- WeCoza Class Management Database Schema
-- Based on classes_schema_1.sql with JSONB approach

-- Create database (run this separately if needed)
-- CREATE DATABASE wecoza_classes;

-- Use the database
-- \c wecoza_classes;

-- Enable UUID extension if needed
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- Create classes table with JSONB fields for flexibility
CREATE TABLE IF NOT EXISTS classes (
    class_id SERIAL PRIMARY KEY,
    client_id INTEGER,
    site_id INTEGER,
    class_address_line TEXT,
    class_type VARCHAR(50),
    class_subject VARCHAR(255),
    class_code VARCHAR(100) UNIQUE NOT NULL,
    class_duration INTEGER, -- Duration in days
    original_start_date DATE,
    seta_funded BOOLEAN DEFAULT FALSE,
    seta VARCHAR(50),
    exam_class BOOLEAN DEFAULT FALSE,
    exam_type VARCHAR(100),
    qa_visit_dates TEXT,
    class_agent INTEGER,
    initial_class_agent INTEGER,
    initial_agent_start_date DATE,
    project_supervisor_id INTEGER,
    delivery_date DATE,
    
    -- JSONB fields for flexible data storage
    learner_ids JSONB DEFAULT '[]'::jsonb,
    backup_agent_ids JSONB DEFAULT '[]'::jsonb,
    schedule_data JSONB DEFAULT '{}'::jsonb,
    stop_restart_dates JSONB DEFAULT '[]'::jsonb,
    class_notes_data JSONB DEFAULT '[]'::jsonb,
    
    -- Timestamps
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_classes_client_id ON classes(client_id);
CREATE INDEX IF NOT EXISTS idx_classes_class_type ON classes(class_type);
CREATE INDEX IF NOT EXISTS idx_classes_class_agent ON classes(class_agent);
CREATE INDEX IF NOT EXISTS idx_classes_supervisor ON classes(project_supervisor_id);
CREATE INDEX IF NOT EXISTS idx_classes_start_date ON classes(original_start_date);
CREATE INDEX IF NOT EXISTS idx_classes_created_at ON classes(created_at);
CREATE INDEX IF NOT EXISTS idx_classes_class_code ON classes(class_code);

-- JSONB indexes for better query performance
CREATE INDEX IF NOT EXISTS idx_classes_learner_ids ON classes USING GIN(learner_ids);
CREATE INDEX IF NOT EXISTS idx_classes_backup_agent_ids ON classes USING GIN(backup_agent_ids);
CREATE INDEX IF NOT EXISTS idx_classes_schedule_data ON classes USING GIN(schedule_data);

-- Create users table for authentication (basic implementation)
CREATE TABLE IF NOT EXISTS users (
    user_id SERIAL PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    role VARCHAR(50) DEFAULT 'user',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Create indexes for users table
CREATE INDEX IF NOT EXISTS idx_users_username ON users(username);
CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);
CREATE INDEX IF NOT EXISTS idx_users_role ON users(role);

-- Create file uploads table for tracking uploaded files
CREATE TABLE IF NOT EXISTS file_uploads (
    file_id SERIAL PRIMARY KEY,
    class_id INTEGER REFERENCES classes(class_id) ON DELETE CASCADE,
    original_filename VARCHAR(255) NOT NULL,
    stored_filename VARCHAR(255) NOT NULL,
    file_path TEXT NOT NULL,
    file_size INTEGER,
    mime_type VARCHAR(100),
    upload_type VARCHAR(50), -- 'qa_report', 'document', etc.
    uploaded_by INTEGER REFERENCES users(user_id),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Create indexes for file uploads
CREATE INDEX IF NOT EXISTS idx_file_uploads_class_id ON file_uploads(class_id);
CREATE INDEX IF NOT EXISTS idx_file_uploads_upload_type ON file_uploads(upload_type);

-- Create audit log table for tracking changes
CREATE TABLE IF NOT EXISTS audit_log (
    log_id SERIAL PRIMARY KEY,
    table_name VARCHAR(100) NOT NULL,
    record_id INTEGER NOT NULL,
    action VARCHAR(20) NOT NULL, -- 'INSERT', 'UPDATE', 'DELETE'
    old_values JSONB,
    new_values JSONB,
    changed_by INTEGER REFERENCES users(user_id),
    changed_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Create indexes for audit log
CREATE INDEX IF NOT EXISTS idx_audit_log_table_record ON audit_log(table_name, record_id);
CREATE INDEX IF NOT EXISTS idx_audit_log_changed_at ON audit_log(changed_at);

-- Insert default admin user (password: 'admin123' - change in production!)
INSERT INTO users (username, email, password_hash, first_name, last_name, role) 
VALUES (
    'admin', 
    'admin@wecoza.co.za', 
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: 'admin123'
    'System', 
    'Administrator', 
    'admin'
) ON CONFLICT (username) DO NOTHING;

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
    '[1, 2, 3]'::jsonb, -- John, Nosipho, Ahmed
    '[2, 3]'::jsonb, -- Thandi, Rajesh as backup
    '{
        "days": ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday"],
        "start_time": "09:00",
        "end_time": "16:00",
        "break_times": ["10:30-10:45", "12:00-13:00", "14:30-14:45"]
    }'::jsonb,
    '[
        {
            "type": "Venue Confirmed",
            "note": "Training room booked and confirmed",
            "date": "2025-01-15",
            "user": "admin"
        }
    ]'::jsonb
) ON CONFLICT (class_code) DO NOTHING;

-- Create a function to update the updated_at timestamp
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

-- Create triggers to automatically update updated_at
CREATE TRIGGER update_classes_updated_at 
    BEFORE UPDATE ON classes 
    FOR EACH ROW 
    EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_users_updated_at 
    BEFORE UPDATE ON users 
    FOR EACH ROW 
    EXECUTE FUNCTION update_updated_at_column();

-- Create a function for audit logging
CREATE OR REPLACE FUNCTION audit_trigger_function()
RETURNS TRIGGER AS $$
BEGIN
    IF TG_OP = 'DELETE' THEN
        INSERT INTO audit_log (table_name, record_id, action, old_values, changed_by)
        VALUES (TG_TABLE_NAME, OLD.class_id, TG_OP, row_to_json(OLD)::jsonb, 
                COALESCE(current_setting('app.current_user_id', true)::integer, 1));
        RETURN OLD;
    ELSIF TG_OP = 'UPDATE' THEN
        INSERT INTO audit_log (table_name, record_id, action, old_values, new_values, changed_by)
        VALUES (TG_TABLE_NAME, NEW.class_id, TG_OP, row_to_json(OLD)::jsonb, 
                row_to_json(NEW)::jsonb, 
                COALESCE(current_setting('app.current_user_id', true)::integer, 1));
        RETURN NEW;
    ELSIF TG_OP = 'INSERT' THEN
        INSERT INTO audit_log (table_name, record_id, action, new_values, changed_by)
        VALUES (TG_TABLE_NAME, NEW.class_id, TG_OP, row_to_json(NEW)::jsonb,
                COALESCE(current_setting('app.current_user_id', true)::integer, 1));
        RETURN NEW;
    END IF;
    RETURN NULL;
END;
$$ LANGUAGE plpgsql;

-- Create audit triggers for classes table
CREATE TRIGGER classes_audit_trigger
    AFTER INSERT OR UPDATE OR DELETE ON classes
    FOR EACH ROW EXECUTE FUNCTION audit_trigger_function();

-- Grant permissions (adjust as needed for your setup)
-- GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO your_app_user;
-- GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA public TO your_app_user;

COMMENT ON TABLE classes IS 'Main table for storing class information with JSONB fields for flexibility';
COMMENT ON TABLE users IS 'User authentication and authorization table';
COMMENT ON TABLE file_uploads IS 'Tracks files uploaded for classes (QA reports, documents, etc.)';
COMMENT ON TABLE audit_log IS 'Audit trail for tracking changes to important data';

-- Display success message
SELECT 'Database schema initialized successfully!' as status;
