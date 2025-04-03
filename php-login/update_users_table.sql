-- Add role column to users table
ALTER TABLE users ADD COLUMN role VARCHAR(20) DEFAULT 'user';

-- Create an admin user (password: admin123)
INSERT INTO users (username, password, Firstname, Lastname, role) 
VALUES ('admin', 'admin123', 'Admin', 'User', 'admin')
ON DUPLICATE KEY UPDATE role = 'admin'; 