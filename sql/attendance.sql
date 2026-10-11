-- ===================================================================
-- CASPA AI / EduNexa School OS - Attendance Marking Database Schema
-- Safe to re-run multiple times (CREATE TABLE IF NOT EXISTS & ON DUPLICATE KEY)
-- ===================================================================

-- 1. Classes Table (if not exists)
CREATE TABLE IF NOT EXISTS classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    section VARCHAR(10) NOT NULL,
    teacher_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_class_sec (name, section)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Students Table (if not exists)
CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    roll_no VARCHAR(20) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    class_id INT NOT NULL,
    gender VARCHAR(10) DEFAULT 'male',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_class_roll (class_id, roll_no),
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Attendance Table
CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    class_id INT NOT NULL,
    att_date DATE NOT NULL,
    status ENUM('present','absent','late','leave') NOT NULL DEFAULT 'present',
    remark VARCHAR(255) NULL,
    marked_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_student_date (student_id, att_date),
    INDEX idx_class_date (class_id, att_date),
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================================
-- SEED DATA
-- 3 Classes:
--   Class 1: Grade 8 - Section A (Assigned to Teacher ID 3 - Robert Miller)
--   Class 2: Grade 9 - Section A (Assigned to Teacher ID 3 - Robert Miller)
--   Class 3: Grade 10 - Section B (Assigned to another Teacher ID 99)
-- ===================================================================

INSERT INTO classes (id, name, section, teacher_id) VALUES
(1, 'Grade 8', 'A', 3),
(2, 'Grade 9', 'A', 3),
(3, 'Grade 10', 'B', 99)
ON DUPLICATE KEY UPDATE name=VALUES(name), section=VALUES(section), teacher_id=VALUES(teacher_id);

-- ===================================================================
-- 8 Students for Class 1 (Grade 8-A)
-- ===================================================================
INSERT INTO students (id, roll_no, full_name, class_id, gender) VALUES
(1, '01', 'Aarav Sharma', 1, 'male'),
(2, '02', 'Ananya Patel', 1, 'female'),
(3, '03', 'Ethan Clark', 1, 'male'),
(4, '04', 'Ishita Sen', 1, 'female'),
(5, '05', 'Liam Johnson', 1, 'male'),
(6, '06', 'Mia Martinez', 1, 'female'),
(7, '07', 'Rohan Verma', 1, 'male'),
(8, '08', 'Sophia Taylor', 1, 'female')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), class_id=VALUES(class_id), gender=VALUES(gender);

-- ===================================================================
-- 8 Students for Class 2 (Grade 9-A)
-- ===================================================================
INSERT INTO students (id, roll_no, full_name, class_id, gender) VALUES
(9,  '01', 'Aditya Nair', 2, 'male'),
(10, '02', 'Chloe Bennett', 2, 'female'),
(11, '03', 'Daniel Wright', 2, 'male'),
(12, '04', 'Emily Davis', 2, 'female'),
(13, '05', 'Kabir Joshi', 2, 'male'),
(14, '06', 'Olivia White', 2, 'female'),
(15, '07', 'Ryan Cooper', 2, 'male'),
(16, '08', 'Zara Khan', 2, 'female')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), class_id=VALUES(class_id), gender=VALUES(gender);

-- ===================================================================
-- 8 Students for Class 3 (Grade 10-B)
-- ===================================================================
INSERT INTO students (id, roll_no, full_name, class_id, gender) VALUES
(17, '01', 'Arjun Reddy', 3, 'male'),
(18, '02', 'Emma Watson', 3, 'female'),
(19, '03', 'Lucas Gray', 3, 'male'),
(20, '04', 'Maya Gupta', 3, 'female'),
(21, '05', 'Noah Miller', 3, 'male'),
(22, '06', 'Priya Mehra', 3, 'female'),
(23, '07', 'Sam Wilson', 3, 'male'),
(24, '08', 'Tanvi Roy', 3, 'female')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), class_id=VALUES(class_id), gender=VALUES(gender);

-- ===================================================================
-- SEED 7 DAYS OF ATTENDANCE (Grade 8-A, Class ID 1)
-- Dates: 2026-10-04 to 2026-10-10
-- ===================================================================
INSERT INTO attendance (student_id, class_id, att_date, status, remark, marked_by) VALUES
-- Day 1: 2026-10-04
(1, 1, '2026-10-04', 'present', NULL, 3),
(2, 1, '2026-10-04', 'present', NULL, 3),
(3, 1, '2026-10-04', 'absent', 'Viral fever reported', 3),
(4, 1, '2026-10-04', 'present', NULL, 3),
(5, 1, '2026-10-04', 'late', 'Bus delayed by 15 mins', 3),
(6, 1, '2026-10-04', 'present', NULL, 3),
(7, 1, '2026-10-04', 'present', NULL, 3),
(8, 1, '2026-10-04', 'leave', 'Pre-approved medical appointment', 3),

-- Day 2: 2026-10-05
(1, 1, '2026-10-05', 'present', NULL, 3),
(2, 1, '2026-10-05', 'present', NULL, 3),
(3, 1, '2026-10-05', 'present', NULL, 3),
(4, 1, '2026-10-05', 'present', NULL, 3),
(5, 1, '2026-10-05', 'present', NULL, 3),
(6, 1, '2026-10-05', 'late', 'Traffic congestion', 3),
(7, 1, '2026-10-05', 'absent', 'Unexcused absence', 3),
(8, 1, '2026-10-05', 'present', NULL, 3),

-- Day 3: 2026-10-06
(1, 1, '2026-10-06', 'present', NULL, 3),
(2, 1, '2026-10-06', 'late', 'Doctor visit morning', 3),
(3, 1, '2026-10-06', 'present', NULL, 3),
(4, 1, '2026-10-06', 'present', NULL, 3),
(5, 1, '2026-10-06', 'present', NULL, 3),
(6, 1, '2026-10-06', 'present', NULL, 3),
(7, 1, '2026-10-06', 'present', NULL, 3),
(8, 1, '2026-10-06', 'present', NULL, 3),

-- Day 4: 2026-10-07
(1, 1, '2026-10-07', 'present', NULL, 3),
(2, 1, '2026-10-07', 'present', NULL, 3),
(3, 1, '2026-10-07', 'present', NULL, 3),
(4, 1, '2026-10-07', 'absent', 'Family function', 3),
(5, 1, '2026-10-07', 'present', NULL, 3),
(6, 1, '2026-10-07', 'present', NULL, 3),
(7, 1, '2026-10-07', 'leave', 'Sports tournament representation', 3),
(8, 1, '2026-10-07', 'present', NULL, 3),

-- Day 5: 2026-10-08
(1, 1, '2026-10-08', 'present', NULL, 3),
(2, 1, '2026-10-08', 'present', NULL, 3),
(3, 1, '2026-10-08', 'present', NULL, 3),
(4, 1, '2026-10-08', 'present', NULL, 3),
(5, 1, '2026-10-08', 'present', NULL, 3),
(6, 1, '2026-10-08', 'present', NULL, 3),
(7, 1, '2026-10-08', 'present', NULL, 3),
(8, 1, '2026-10-08', 'present', NULL, 3),

-- Day 6: 2026-10-09
(1, 1, '2026-10-09', 'present', NULL, 3),
(2, 1, '2026-10-09', 'late', 'Heavy rainfall delay', 3),
(3, 1, '2026-10-09', 'present', NULL, 3),
(4, 1, '2026-10-09', 'present', NULL, 3),
(5, 1, '2026-10-09', 'absent', 'Sick leave', 3),
(6, 1, '2026-10-09', 'present', NULL, 3),
(7, 1, '2026-10-09', 'present', NULL, 3),
(8, 1, '2026-10-09', 'present', NULL, 3),

-- Day 7: 2026-10-10
(1, 1, '2026-10-10', 'present', NULL, 3),
(2, 1, '2026-10-10', 'present', NULL, 3),
(3, 1, '2026-10-10', 'present', NULL, 3),
(4, 1, '2026-10-10', 'present', NULL, 3),
(5, 1, '2026-10-10', 'present', NULL, 3),
(6, 1, '2026-10-10', 'present', NULL, 3),
(7, 1, '2026-10-10', 'late', '10 mins late', 3),
(8, 1, '2026-10-10', 'present', NULL, 3)

ON DUPLICATE KEY UPDATE status=VALUES(status), remark=VALUES(remark), marked_by=VALUES(marked_by);
