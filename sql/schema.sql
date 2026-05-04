-- XUBand Digital Filing System Schema
-- Xavier University Band

CREATE DATABASE IF NOT EXISTS xuband CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE xuband;

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('moderator','officer','member') NOT NULL DEFAULT 'member',
    instrument VARCHAR(100) DEFAULT NULL,
    year_level VARCHAR(20) DEFAULT NULL,
    student_id VARCHAR(50) DEFAULT NULL,
    contact_number VARCHAR(20) DEFAULT NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    profile_notes TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Events Table
CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    type ENUM('rehearsal','performance','meeting','competition','other') NOT NULL DEFAULT 'rehearsal',
    event_date DATE NOT NULL,
    event_time TIME DEFAULT NULL,
    location VARCHAR(200) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    created_by INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Attendance Table
CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    event_id INT NOT NULL,
    status ENUM('present','absent','late','excused') NOT NULL DEFAULT 'absent',
    penalty_points DECIMAL(5,1) NOT NULL DEFAULT 0,
    remarks VARCHAR(255) DEFAULT NULL,
    recorded_by INT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_attendance (user_id, event_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Penalties Summary Table
CREATE TABLE IF NOT EXISTS penalty_summary (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    total_points DECIMAL(8,1) NOT NULL DEFAULT 0,
    last_computed DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    notes TEXT DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Scholarships Table
CREATE TABLE IF NOT EXISTS scholarships (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    semester VARCHAR(50) NOT NULL,
    academic_year VARCHAR(20) NOT NULL,
    gpa DECIMAL(4,2) DEFAULT NULL,
    band_participation_score INT DEFAULT NULL COMMENT '0-100',
    status ENUM('active','inactive','probation','terminated') NOT NULL DEFAULT 'inactive',
    monthly_allowance DECIMAL(10,2) DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    updated_by INT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Music Sheets Table
CREATE TABLE IF NOT EXISTS music_sheets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    composer VARCHAR(150) DEFAULT NULL,
    arranger VARCHAR(150) DEFAULT NULL,
    instrument_section VARCHAR(100) DEFAULT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_size INT DEFAULT NULL,
    file_type VARCHAR(50) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    uploaded_by INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Announcements Table
CREATE TABLE IF NOT EXISTS announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    body TEXT NOT NULL,
    created_by INT NOT NULL,
    pinned TINYINT(1) NOT NULL DEFAULT 0,
    expires_at DATE DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================================
-- SEED DATA
-- ============================================================

-- All accounts use password: "password"
-- Hash: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uADnia (PHP bcrypt for "password")
INSERT INTO users (name, email, password_hash, role, instrument, year_level, student_id, status) VALUES
('Band Moderator',          'moderator@xuband.edu.ph',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uADnia', 'moderator', NULL,         NULL,       'MOD-001',    'active'),
('Guin Alexis Gabutin',     'gabutin@xuband.edu.ph',     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uADnia', 'officer',   'Trumpet',    '3rd Year', 'XU-2022-001','active'),
('Jude P. Macalaguing',     'macalaguing@xuband.edu.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uADnia', 'member',    'Trombone',   '2nd Year', 'XU-2023-001','active'),
('Nicole Sai Sophie Gabutan','gabutan@xuband.edu.ph',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uADnia', 'member',    'Flute',      '3rd Year', 'XU-2022-002','active'),
('Christopher Basin',        'basin@xuband.edu.ph',      '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uADnia', 'member',    'Percussion', '1st Year', 'XU-2024-001','active'),
('Kyle Rusty Brazil',        'brazil@xuband.edu.ph',     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uADnia', 'member',    'Saxophone',  '2nd Year', 'XU-2023-002','active'),
('Mayeoh Fay D. Barangot',   'barangot@xuband.edu.ph',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uADnia', 'member',    'Clarinet',   '1st Year', 'XU-2024-002','active');

-- Sample Events
INSERT INTO events (title, type, event_date, event_time, location, description, created_by) VALUES
('Weekly Rehearsal', 'rehearsal', DATE_ADD(CURDATE(), INTERVAL 2 DAY), '16:00:00', 'XU Band Hall', 'Regular weekly rehearsal session.', 2),
('Homecoming Parade', 'performance', DATE_ADD(CURDATE(), INTERVAL 14 DAY), '08:00:00', 'XU Campus', 'Annual homecoming parade performance.', 2),
('Band Meeting', 'meeting', DATE_ADD(CURDATE(), INTERVAL 5 DAY), '17:00:00', 'Band Room 101', 'Monthly general assembly meeting.', 2);

-- Sample Announcements
INSERT INTO announcements (title, body, created_by, pinned) VALUES
('Welcome to XUBand Digital System', 'The Xavier University Band Digital Filing System is now live. All members are encouraged to log in and update their profiles.', 1, 1),
('Upcoming Homecoming Parade', 'All band members are required to attend the Homecoming Parade rehearsals. Please check the events calendar for schedules.', 2, 1),
('Music Sheet Upload Reminder', 'Officers: Please upload all pending music sheets for the upcoming competition before the end of the week.', 2, 0);

-- Sample Attendance
INSERT INTO attendance (user_id, event_id, status, penalty_points, recorded_by) VALUES
(3, 1, 'present', 0, 2),
(4, 1, 'present', 0, 2),
(5, 1, 'absent',  3, 2),
(6, 1, 'late',    1, 2),
(7, 1, 'excused', 0, 2);

-- Sample Penalty Summaries
INSERT INTO penalty_summary (user_id, total_points) VALUES
(3, 0),
(4, 0),
(5, 3),
(6, 1),
(7, 0);

-- Sample Scholarships
INSERT INTO scholarships (user_id, semester, academic_year, gpa, band_participation_score, status, monthly_allowance, updated_by) VALUES
(3, '1st Semester', '2024-2025', 1.75, 90, 'active',    3500.00, 2),
(4, '1st Semester', '2024-2025', 1.50, 95, 'active',    3500.00, 2),
(5, '1st Semester', '2024-2025', 2.25, 70, 'probation', 2000.00, 2),
(6, '1st Semester', '2024-2025', 1.90, 88, 'active',    3500.00, 2),
(7, '1st Semester', '2024-2025', 2.00, 75, 'active',    3500.00, 2);
