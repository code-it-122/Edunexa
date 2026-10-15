-- ============================================================
-- CASPA School OS – Schema
-- Database: edunexa
-- Engine: InnoDB | Charset: utf8mb4 | Collation: utf8mb4_unicode_ci
-- Run this ONCE to create the database and all tables.
-- ============================================================

CREATE DATABASE IF NOT EXISTS edunexa
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE edunexa;

-- ============================================================
-- users
-- Central identity table for ALL roles.
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id            INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    name          VARCHAR(120)     NOT NULL,
    email         VARCHAR(180)     NOT NULL,
    password_hash VARCHAR(255)     NOT NULL,
    role          ENUM('principal','admin','teacher','student','parent') NOT NULL,
    is_active     TINYINT(1)       NOT NULL DEFAULT 1,
    created_at    DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    KEY idx_users_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- academic_years
-- ============================================================
CREATE TABLE IF NOT EXISTS academic_years (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    label      VARCHAR(20)  NOT NULL,          -- e.g. "2025-2026"
    is_current TINYINT(1)   NOT NULL DEFAULT 0,
    starts_on  DATE         NOT NULL,
    ends_on    DATE         NOT NULL,
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_ay_label (label)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- classes
-- e.g. "Grade 10 – Section A"
-- ============================================================
CREATE TABLE IF NOT EXISTS classes (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    academic_year_id INT UNSIGNED NOT NULL,
    name             VARCHAR(80)  NOT NULL,   -- e.g. "Grade 10"
    section          VARCHAR(10)  NOT NULL DEFAULT 'A',
    class_teacher_id INT UNSIGNED NULL,       -- FK → users(id) role=teacher
    created_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_class_year_name_section (academic_year_id, name, section),
    KEY idx_classes_teacher (class_teacher_id),
    CONSTRAINT fk_classes_ay     FOREIGN KEY (academic_year_id) REFERENCES academic_years(id) ON DELETE RESTRICT,
    CONSTRAINT fk_classes_teacher FOREIGN KEY (class_teacher_id) REFERENCES users(id)          ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- students
-- Extended profile for role=student users.
-- ============================================================
CREATE TABLE IF NOT EXISTS students (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id          INT UNSIGNED NOT NULL,
    class_id         INT UNSIGNED NULL,
    admission_number VARCHAR(40)  NOT NULL,
    date_of_birth    DATE         NULL,
    gender           ENUM('male','female','other') NULL,
    address          TEXT         NULL,
    created_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_students_user   (user_id),
    UNIQUE KEY uq_students_adm_no (admission_number),
    KEY idx_students_class (class_id),
    CONSTRAINT fk_students_user  FOREIGN KEY (user_id)  REFERENCES users(id)   ON DELETE CASCADE,
    CONSTRAINT fk_students_class FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- parents
-- A parent can be linked to multiple students.
-- ============================================================
CREATE TABLE IF NOT EXISTS parents (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id    INT UNSIGNED NOT NULL,
    phone      VARCHAR(30)  NULL,
    occupation VARCHAR(100) NULL,
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_parents_user (user_id),
    CONSTRAINT fk_parents_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- parent_student  (many-to-many)
-- ============================================================
CREATE TABLE IF NOT EXISTS parent_student (
    parent_id  INT UNSIGNED NOT NULL,
    student_id INT UNSIGNED NOT NULL,
    relation   VARCHAR(30)  NOT NULL DEFAULT 'guardian',
    PRIMARY KEY (parent_id, student_id),
    CONSTRAINT fk_ps_parent  FOREIGN KEY (parent_id)  REFERENCES parents(id)  ON DELETE CASCADE,
    CONSTRAINT fk_ps_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- subjects
-- ============================================================
CREATE TABLE IF NOT EXISTS subjects (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name       VARCHAR(100) NOT NULL,
    code       VARCHAR(20)  NOT NULL,
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_subjects_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- class_subjects  (class ↔ subject ↔ teacher assignment)
-- ============================================================
CREATE TABLE IF NOT EXISTS class_subjects (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    class_id   INT UNSIGNED NOT NULL,
    subject_id INT UNSIGNED NOT NULL,
    teacher_id INT UNSIGNED NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_cs_class_subject (class_id, subject_id),
    KEY idx_cs_teacher (teacher_id),
    CONSTRAINT fk_cs_class   FOREIGN KEY (class_id)   REFERENCES classes(id)   ON DELETE CASCADE,
    CONSTRAINT fk_cs_subject FOREIGN KEY (subject_id) REFERENCES subjects(id)  ON DELETE CASCADE,
    CONSTRAINT fk_cs_teacher FOREIGN KEY (teacher_id) REFERENCES users(id)     ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- attendance
-- ============================================================
CREATE TABLE IF NOT EXISTS attendance (
    id             INT UNSIGNED                            NOT NULL AUTO_INCREMENT,
    student_id     INT UNSIGNED                            NOT NULL,
    class_subject_id INT UNSIGNED                          NOT NULL,
    date           DATE                                    NOT NULL,
    status         ENUM('present','absent','late','excused') NOT NULL DEFAULT 'present',
    recorded_by    INT UNSIGNED                            NOT NULL,  -- FK → users(id)
    created_at     DATETIME                                NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_attendance (student_id, class_subject_id, date),
    KEY idx_att_date (date),
    CONSTRAINT fk_att_student FOREIGN KEY (student_id)       REFERENCES students(id)       ON DELETE CASCADE,
    CONSTRAINT fk_att_cs      FOREIGN KEY (class_subject_id) REFERENCES class_subjects(id) ON DELETE CASCADE,
    CONSTRAINT fk_att_rec     FOREIGN KEY (recorded_by)      REFERENCES users(id)          ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- assignments
-- ============================================================
CREATE TABLE IF NOT EXISTS assignments (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    class_subject_id INT UNSIGNED NOT NULL,
    title            VARCHAR(200) NOT NULL,
    description      TEXT         NULL,
    due_date         DATE         NOT NULL,
    max_marks        DECIMAL(6,2) NOT NULL DEFAULT 100,
    created_by       INT UNSIGNED NOT NULL,
    created_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_asgn_cs   (class_subject_id),
    KEY idx_asgn_due  (due_date),
    CONSTRAINT fk_asgn_cs      FOREIGN KEY (class_subject_id) REFERENCES class_subjects(id) ON DELETE CASCADE,
    CONSTRAINT fk_asgn_creator FOREIGN KEY (created_by)       REFERENCES users(id)          ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- grades  (marks for assignments / exams)
-- ============================================================
CREATE TABLE IF NOT EXISTS grades (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    assignment_id INT UNSIGNED NOT NULL,
    student_id    INT UNSIGNED NOT NULL,
    marks         DECIMAL(6,2) NULL,
    feedback      TEXT         NULL,
    graded_by     INT UNSIGNED NOT NULL,
    graded_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_grades (assignment_id, student_id),
    KEY idx_grades_student (student_id),
    CONSTRAINT fk_grades_asgn    FOREIGN KEY (assignment_id) REFERENCES assignments(id) ON DELETE CASCADE,
    CONSTRAINT fk_grades_student FOREIGN KEY (student_id)    REFERENCES students(id)    ON DELETE CASCADE,
    CONSTRAINT fk_grades_grader  FOREIGN KEY (graded_by)     REFERENCES users(id)       ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- announcements
-- ============================================================
CREATE TABLE IF NOT EXISTS announcements (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    title        VARCHAR(200) NOT NULL,
    body         TEXT         NOT NULL,
    audience     SET('principal','admin','teacher','student','parent') NOT NULL DEFAULT 'student,parent',
    created_by   INT UNSIGNED NOT NULL,
    published_at DATETIME     NULL,
    expires_at   DATETIME     NULL,
    created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_ann_pub (published_at),
    CONSTRAINT fk_ann_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- messages  (internal messaging)
-- ============================================================
CREATE TABLE IF NOT EXISTS messages (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    sender_id   INT UNSIGNED NOT NULL,
    receiver_id INT UNSIGNED NOT NULL,
    subject     VARCHAR(200) NOT NULL,
    body        TEXT         NOT NULL,
    is_read     TINYINT(1)   NOT NULL DEFAULT 0,
    sent_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_msg_receiver (receiver_id),
    KEY idx_msg_sender   (sender_id),
    CONSTRAINT fk_msg_sender   FOREIGN KEY (sender_id)   REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_msg_receiver FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
