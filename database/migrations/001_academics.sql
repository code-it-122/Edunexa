-- CASPA School OS – Academic-phase migration
-- Run once: mysql -u root edunexa < database/migrations/001_academics.sql
-- Safe to re-run (IF NOT EXISTS / INSERT IGNORE).

SET FOREIGN_KEY_CHECKS = 0;

-- ─────────────────────────────────────────────
-- 1. TIMETABLE
-- ─────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS periods (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    period_number TINYINT      NOT NULL,
    label         VARCHAR(30)  NOT NULL,
    start_time    TIME         NOT NULL,
    end_time      TIME         NOT NULL,
    is_break      TINYINT(1)   NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    UNIQUE KEY uq_period_num (period_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS timetable_slots (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    class_id         INT UNSIGNED NOT NULL,
    class_subject_id INT UNSIGNED NULL,
    period_id        INT UNSIGNED NOT NULL,
    day_of_week      TINYINT      NOT NULL COMMENT '1=Mon 2=Tue 3=Wed 4=Thu 5=Fri',
    academic_year_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_slot (class_id, period_id, day_of_week, academic_year_id),
    KEY idx_ts_cs  (class_subject_id),
    KEY idx_ts_ay  (academic_year_id),
    CONSTRAINT fk_ts_class   FOREIGN KEY (class_id)         REFERENCES classes(id)        ON DELETE CASCADE,
    CONSTRAINT fk_ts_cs      FOREIGN KEY (class_subject_id) REFERENCES class_subjects(id) ON DELETE SET NULL,
    CONSTRAINT fk_ts_period  FOREIGN KEY (period_id)        REFERENCES periods(id)        ON DELETE CASCADE,
    CONSTRAINT fk_ts_ay      FOREIGN KEY (academic_year_id) REFERENCES academic_years(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────
-- 2. EXAMS
-- ─────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS exams (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    academic_year_id INT UNSIGNED NOT NULL,
    name             VARCHAR(100) NOT NULL,
    term             VARCHAR(30)  NOT NULL DEFAULT 'Term 1',
    exam_type        ENUM('midterm','final','unit','other') NOT NULL DEFAULT 'midterm',
    is_published     TINYINT(1)   NOT NULL DEFAULT 0,
    created_by       INT UNSIGNED NOT NULL,
    created_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_exam_ay (academic_year_id),
    CONSTRAINT fk_exam_ay      FOREIGN KEY (academic_year_id) REFERENCES academic_years(id),
    CONSTRAINT fk_exam_creator FOREIGN KEY (created_by)       REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS exam_subjects (
    id               INT UNSIGNED   NOT NULL AUTO_INCREMENT,
    exam_id          INT UNSIGNED   NOT NULL,
    class_subject_id INT UNSIGNED   NOT NULL,
    max_marks        DECIMAL(6,2)   NOT NULL DEFAULT 100,
    exam_date        DATE           NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_exam_cs (exam_id, class_subject_id),
    CONSTRAINT fk_es_exam FOREIGN KEY (exam_id)          REFERENCES exams(id)         ON DELETE CASCADE,
    CONSTRAINT fk_es_cs   FOREIGN KEY (class_subject_id) REFERENCES class_subjects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS exam_marks (
    id               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    exam_subject_id  INT UNSIGNED  NOT NULL,
    student_id       INT UNSIGNED  NOT NULL,
    marks            DECIMAL(6,2)  NULL COMMENT 'NULL = absent or not yet entered',
    remarks          VARCHAR(200)  NULL,
    entered_by       INT UNSIGNED  NOT NULL,
    entered_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_mark (exam_subject_id, student_id),
    CONSTRAINT fk_em_es      FOREIGN KEY (exam_subject_id) REFERENCES exam_subjects(id) ON DELETE CASCADE,
    CONSTRAINT fk_em_student FOREIGN KEY (student_id)      REFERENCES students(id)      ON DELETE CASCADE,
    CONSTRAINT fk_em_entered FOREIGN KEY (entered_by)      REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS grade_bands (
    id               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    academic_year_id INT UNSIGNED  NOT NULL,
    min_pct          DECIMAL(5,2)  NOT NULL,
    max_pct          DECIMAL(5,2)  NOT NULL,
    grade_letter     VARCHAR(5)    NOT NULL,
    description      VARCHAR(50)   NULL,
    PRIMARY KEY (id),
    CONSTRAINT fk_gb_ay FOREIGN KEY (academic_year_id) REFERENCES academic_years(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────
-- 3. SYLLABUS & LESSON PLANS
-- ─────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS syllabus (
    id               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    class_subject_id INT UNSIGNED  NOT NULL,
    unit_number      TINYINT UNSIGNED NOT NULL,
    title            VARCHAR(150)  NOT NULL,
    description      TEXT          NULL,
    created_by       INT UNSIGNED  NOT NULL,
    created_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_syl (class_subject_id, unit_number),
    CONSTRAINT fk_syl_cs      FOREIGN KEY (class_subject_id) REFERENCES class_subjects(id) ON DELETE CASCADE,
    CONSTRAINT fk_syl_creator FOREIGN KEY (created_by)       REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS lesson_plans (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    class_subject_id INT UNSIGNED NOT NULL,
    syllabus_id      INT UNSIGNED NULL,
    title            VARCHAR(200) NOT NULL,
    planned_date     DATE         NOT NULL,
    objectives       TEXT         NULL,
    content          TEXT         NULL,
    materials        TEXT         NULL,
    status           ENUM('draft','submitted','approved','rejected') NOT NULL DEFAULT 'draft',
    reviewed_by      INT UNSIGNED NULL,
    reviewed_at      DATETIME     NULL,
    review_notes     TEXT         NULL,
    created_by       INT UNSIGNED NOT NULL,
    created_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_lp_cs     (class_subject_id),
    KEY idx_lp_status (status),
    CONSTRAINT fk_lp_cs       FOREIGN KEY (class_subject_id) REFERENCES class_subjects(id) ON DELETE CASCADE,
    CONSTRAINT fk_lp_syl      FOREIGN KEY (syllabus_id)      REFERENCES syllabus(id)        ON DELETE SET NULL,
    CONSTRAINT fk_lp_creator  FOREIGN KEY (created_by)       REFERENCES users(id),
    CONSTRAINT fk_lp_reviewer FOREIGN KEY (reviewed_by)      REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

-- ─────────────────────────────────────────────
-- 4. SEED DATA
-- ─────────────────────────────────────────────

-- Default periods (INSERT IGNORE = safe to re-run)
INSERT IGNORE INTO periods (period_number, label, start_time, end_time, is_break) VALUES
(1, 'Period 1', '08:00:00', '08:45:00', 0),
(2, 'Period 2', '08:45:00', '09:30:00', 0),
(3, 'Period 3', '09:30:00', '10:15:00', 0),
(4, 'Break',    '10:15:00', '10:30:00', 1),
(5, 'Period 4', '10:30:00', '11:15:00', 0),
(6, 'Period 5', '11:15:00', '12:00:00', 0),
(7, 'Period 6', '12:00:00', '12:45:00', 0);

-- Default grade bands for academic_year_id = 1
INSERT IGNORE INTO grade_bands (academic_year_id, min_pct, max_pct, grade_letter, description) VALUES
(1,  90, 100, 'A+', 'Outstanding'),
(1,  80,  90, 'A',  'Excellent'),
(1,  70,  80, 'B+', 'Very Good'),
(1,  60,  70, 'B',  'Good'),
(1,  50,  60, 'C',  'Average'),
(1,  40,  50, 'D',  'Below Average'),
(1,   0,  40, 'F',  'Fail');
