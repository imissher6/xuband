-- XUBand Digital Filing System Schema v2
-- Xavier University Band


-- Users
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

-- Events
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

-- Attendance (linked to events, not dates directly)
CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    event_id INT NOT NULL,
    status ENUM('present','absent','late') NOT NULL DEFAULT 'absent',
    penalty_points INT NOT NULL DEFAULT 0,
    remarks VARCHAR(255) DEFAULT NULL,
    recorded_by INT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_attendance (user_id, event_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Penalty Summary
CREATE TABLE IF NOT EXISTS penalty_summary (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    total_points INT NOT NULL DEFAULT 0,
    last_computed DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- School Years (for scholarship)
CREATE TABLE IF NOT EXISTS school_years (
    id INT AUTO_INCREMENT PRIMARY KEY,
    label VARCHAR(50) NOT NULL UNIQUE,
    created_by INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Scholarship Terms (auto-created: 1st Sem, 2nd Sem, Summer per school year)
CREATE TABLE IF NOT EXISTS scholarship_terms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    school_year_id INT NOT NULL,
    term ENUM('1st Semester','2nd Semester','Summer') NOT NULL,
    UNIQUE KEY unique_term (school_year_id, term),
    FOREIGN KEY (school_year_id) REFERENCES school_years(id) ON DELETE CASCADE
);

-- Scholarship Records (per member per term)
CREATE TABLE IF NOT EXISTS scholarships (
    id INT AUTO_INCREMENT PRIMARY KEY,
    term_id INT NOT NULL,
    user_id INT NOT NULL,
    status ENUM('Full Scholar','Half Scholar','Not Scholar') NOT NULL DEFAULT 'Not Scholar',
    updated_by INT DEFAULT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_scholarship (term_id, user_id),
    FOREIGN KEY (term_id) REFERENCES scholarship_terms(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Music Sheet Folders
CREATE TABLE IF NOT EXISTS music_folders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    description TEXT DEFAULT NULL,
    created_by INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Music Sheet Files
CREATE TABLE IF NOT EXISTS music_sheets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    folder_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    instrument VARCHAR(100) DEFAULT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_size INT DEFAULT NULL,
    file_type VARCHAR(50) DEFAULT NULL,
    uploaded_by INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (folder_id) REFERENCES music_folders(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Music Sheet Assignments (member → specific sheets)
CREATE TABLE IF NOT EXISTS music_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sheet_id INT NOT NULL,
    user_id INT NOT NULL,
    assigned_by INT NOT NULL,
    assigned_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_assign (sheet_id, user_id),
    FOREIGN KEY (sheet_id) REFERENCES music_sheets(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Announcements
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
-- All passwords: "password"
-- Hash: $2y$10$bgJSY64smLKwTYVtv0SXhuXDjij9BzVnpm45cPQqrcOg8OWjIrIia
-- ============================================================

INSERT INTO users (name, email, password_hash, role, instrument, year_level, student_id, status) VALUES
('Band Moderator',           'moderator@xuband.edu.ph',   '$2y$10$bgJSY64smLKwTYVtv0SXhuXDjij9BzVnpm45cPQqrcOg8OWjIrIia', 'moderator', NULL,         NULL,       'MOD-001',     'active'),
('Guin Alexis Gabutin',      'gabutin@xuband.edu.ph',     '$2y$10$bgJSY64smLKwTYVtv0SXhuXDjij9BzVnpm45cPQqrcOg8OWjIrIia', 'officer',   'Trumpet',    '3rd Year', 'XU-2022-001', 'active'),
('Jude P. Macalaguing',      'macalaguing@xuband.edu.ph', '$2y$10$bgJSY64smLKwTYVtv0SXhuXDjij9BzVnpm45cPQqrcOg8OWjIrIia', 'member',    'Trombone',   '2nd Year', 'XU-2023-001', 'active'),
('Nicole Sai Sophie Gabutan','gabutan@xuband.edu.ph',     '$2y$10$bgJSY64smLKwTYVtv0SXhuXDjij9BzVnpm45cPQqrcOg8OWjIrIia', 'member',    'Flute',      '3rd Year', 'XU-2022-002', 'active'),
('Christopher Basin',        'basin@xuband.edu.ph',       '$2y$10$bgJSY64smLKwTYVtv0SXhuXDjij9BzVnpm45cPQqrcOg8OWjIrIia', 'member',    'Percussion', '1st Year', 'XU-2024-001', 'active'),
('Kyle Rusty Brazil',        'brazil@xuband.edu.ph',      '$2y$10$bgJSY64smLKwTYVtv0SXhuXDjij9BzVnpm45cPQqrcOg8OWjIrIia', 'member',    'Saxophone',  '2nd Year', 'XU-2023-002', 'active'),
('Mayeoh Fay D. Barangot',   'barangot@xuband.edu.ph',    '$2y$10$bgJSY64smLKwTYVtv0SXhuXDjij9BzVnpm45cPQqrcOg8OWjIrIia', 'member',    'Clarinet',   '1st Year', 'XU-2024-002', 'active');

INSERT INTO events (title, type, event_date, event_time, location, description, created_by) VALUES
('Weekly Rehearsal',   'rehearsal',   DATE_ADD(CURDATE(), INTERVAL 2 DAY),  '16:00:00', 'XU Band Hall',  'Regular weekly rehearsal.', 2),
('Homecoming Parade',  'performance', DATE_ADD(CURDATE(), INTERVAL 14 DAY), '08:00:00', 'XU Campus',     'Annual homecoming parade.', 2),
('Band Meeting',       'meeting',     DATE_ADD(CURDATE(), INTERVAL 5 DAY),  '17:00:00', 'Band Room 101', 'Monthly general assembly.', 2);

INSERT INTO announcements (title, body, created_by, pinned) VALUES
('Welcome to XUBand Digital System', 'The Xavier University Band Digital Filing System is now live. All members are encouraged to log in and update their profiles.', 1, 1),
('Upcoming Homecoming Parade', 'All band members are required to attend the Homecoming Parade rehearsals. Please check the events calendar for schedules.', 2, 1),
('Music Sheet Upload Reminder', 'Officers: Please upload all pending music sheets for the upcoming competition before the end of the week.', 2, 0);

-- Sample school year with all 3 terms
INSERT INTO school_years (label, created_by) VALUES ('2024-2025', 1);
INSERT INTO scholarship_terms (school_year_id, term) VALUES (1,'1st Semester'),(1,'2nd Semester'),(1,'Summer');

-- Sample scholarships
INSERT INTO scholarships (term_id, user_id, status, updated_by) VALUES
(1,3,'Full Scholar',2),(1,4,'Full Scholar',2),(1,5,'Half Scholar',2),(1,6,'Full Scholar',2),(1,7,'Half Scholar',2);

-- Sample music folder
INSERT INTO music_folders (name, description, created_by) VALUES ('ABBA Medley', 'ABBA arrangements for the homecoming parade', 2);

-- Sample attendance
INSERT INTO attendance (user_id, event_id, status, penalty_points, recorded_by) VALUES
(3,1,'present',0,2),(4,1,'present',0,2),(5,1,'absent',150,2),(6,1,'late',75,2),(7,1,'present',0,2);

INSERT INTO penalty_summary (user_id, total_points) VALUES
(3,0),(4,0),(5,150),(6,75),(7,0);
