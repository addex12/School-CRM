CREATE TABLE IF NOT EXISTS permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    label VARCHAR(255) NOT NULL
);

CREATE TABLE IF NOT EXISTS role_permissions (
    role_id INT NOT NULL,
    permission_id INT NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
);

-- Insert permissions (features/modules)
INSERT INTO permissions (name, label) VALUES
('dashboard', 'Access Dashboard'),
('users_view', 'View Users'),
('users_add', 'Add User'),
('users_edit', 'Edit User'),
('users_delete', 'Delete User'),
('surveys_view', 'View Surveys'),
('surveys_create', 'Create Survey'),
('surveys_edit', 'Edit Survey'),
('surveys_delete', 'Delete Survey'),
('survey_results', 'View Survey Results'),
('survey_categories', 'Manage Survey Categories'),
('students_view', 'View Students'),
('students_add', 'Add Student'),
('students_edit', 'Edit Student'),
('students_delete', 'Delete Student'),
('teachers_view', 'View Teachers'),
('teachers_add', 'Add Teacher'),
('teachers_edit', 'Edit Teacher'),
('teachers_delete', 'Delete Teacher'),
('parents_view', 'View Parents'),
('parents_add', 'Add Parent'),
('parents_edit', 'Edit Parent'),
('parents_delete', 'Delete Parent'),
('messages', 'Send/Receive Messages'),
('announcements', 'Manage Announcements'),
('bulk_email', 'Send Bulk Emails'),
('support_tickets', 'Manage Support Tickets'),
('knowledge_base', 'Access Knowledge Base'),
('feedback_manage', 'Manage Feedback'),
('feedback_view', 'View Feedback'),
('settings_general', 'General Settings'),
('settings_roles', 'Manage User Roles'),
('settings_permissions', 'Manage Permissions'),
('settings_backup', 'Backup & Restore'),
('settings_logs', 'View System Logs'),
('settings_audit', 'View Audit Trail'),
('role_management', 'Manage Roles'),
('user_permissions', 'Manage User Permissions'),
('classes_manage', 'Manage Classes'),
('sections_manage', 'Manage Sections'),
('grades_manage', 'Manage Grades'),
('reports_view', 'View Reports');
