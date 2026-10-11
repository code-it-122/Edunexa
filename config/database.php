<?php
// config/database.php - PDO SQLite Database Connection & Auto-Migration

function getDbConnection() {
    static $pdo = null;
    if ($pdo === null) {
        $dbPath = __DIR__ . '/../database.sqlite';
        $needsInit = !file_exists($dbPath);

        try {
            $pdo = new PDO("sqlite:" . $dbPath);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            // Enable WAL mode for performance & concurrent reads
            $pdo->exec('PRAGMA journal_mode = WAL;');

            if ($needsInit || filesize($dbPath) === 0) {
                initDatabaseSchema($pdo);
            }
            initAttendanceSchema($pdo);
        } catch (PDOException $e) {
            die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
        }
    }
    return $pdo;
}

function initAttendanceSchema($pdo) {
    // 7. Classes Table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS classes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            section TEXT NOT NULL,
            teacher_id INTEGER,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(name, section)
        );
    ");

    // 8. Students Table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS students (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            roll_no TEXT NOT NULL,
            full_name TEXT NOT NULL,
            class_id INTEGER NOT NULL,
            gender TEXT DEFAULT 'male',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(class_id, roll_no)
        );
    ");

    // 9. Attendance Table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS attendance (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            student_id INTEGER NOT NULL,
            class_id INTEGER NOT NULL,
            att_date DATE NOT NULL,
            status TEXT NOT NULL DEFAULT 'present',
            remark TEXT,
            marked_by INTEGER,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(student_id, att_date)
        );
    ");

    // Check if classes exist
    $classCount = $pdo->query("SELECT COUNT(*) FROM classes")->fetchColumn();
    if ($classCount == 0) {
        $cStmt = $pdo->prepare("INSERT OR IGNORE INTO classes (id, name, section, teacher_id) VALUES (?, ?, ?, ?)");
        $cStmt->execute([1, 'Grade 8', 'A', 3]); // Teacher Robert Miller (User ID 3)
        $cStmt->execute([2, 'Grade 9', 'A', 3]); // Teacher Robert Miller (User ID 3)
        $cStmt->execute([3, 'Grade 10', 'B', 99]); // Other Teacher (ID 99)

        // Seed 8 students for Grade 8-A
        $sStmt = $pdo->prepare("INSERT OR IGNORE INTO students (id, roll_no, full_name, class_id, gender) VALUES (?, ?, ?, ?, ?)");
        $c1Students = [
            [1, '01', 'Aarav Sharma', 1, 'male'],
            [2, '02', 'Ananya Patel', 1, 'female'],
            [3, '03', 'Ethan Clark', 1, 'male'],
            [4, '04', 'Ishita Sen', 1, 'female'],
            [5, '05', 'Liam Johnson', 1, 'male'],
            [6, '06', 'Mia Martinez', 1, 'female'],
            [7, '07', 'Rohan Verma', 1, 'male'],
            [8, '08', 'Sophia Taylor', 1, 'female']
        ];
        foreach ($c1Students as $st) {
            $sStmt->execute($st);
        }

        // Seed 8 students for Grade 9-A
        $c2Students = [
            [9,  '01', 'Aditya Nair', 2, 'male'],
            [10, '02', 'Chloe Bennett', 2, 'female'],
            [11, '03', 'Daniel Wright', 2, 'male'],
            [12, '04', 'Emily Davis', 2, 'female'],
            [13, '05', 'Kabir Joshi', 2, 'male'],
            [14, '06', 'Olivia White', 2, 'female'],
            [15, '07', 'Ryan Cooper', 2, 'male'],
            [16, '08', 'Zara Khan', 2, 'female']
        ];
        foreach ($c2Students as $st) {
            $sStmt->execute($st);
        }

        // Seed 8 students for Grade 10-B
        $c3Students = [
            [17, '01', 'Arjun Reddy', 3, 'male'],
            [18, '02', 'Emma Watson', 3, 'female'],
            [19, '03', 'Lucas Gray', 3, 'male'],
            [20, '04', 'Maya Gupta', 3, 'female'],
            [21, '05', 'Noah Miller', 3, 'male'],
            [22, '06', 'Priya Mehra', 3, 'female'],
            [23, '07', 'Sam Wilson', 3, 'male'],
            [24, '08', 'Tanvi Roy', 3, 'female']
        ];
        foreach ($c3Students as $st) {
            $sStmt->execute($st);
        }

        // Seed 7 days of attendance for Grade 8-A (2026-10-04 to 2026-10-10)
        $aStmt = $pdo->prepare("
            INSERT OR REPLACE INTO attendance (student_id, class_id, att_date, status, remark, marked_by, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'))
        ");

        $records = [
            // 2026-10-04
            [1, 1, '2026-10-04', 'present', null, 3],
            [2, 1, '2026-10-04', 'present', null, 3],
            [3, 1, '2026-10-04', 'absent', 'Viral fever reported', 3],
            [4, 1, '2026-10-04', 'present', null, 3],
            [5, 1, '2026-10-04', 'late', 'Bus delayed by 15 mins', 3],
            [6, 1, '2026-10-04', 'present', null, 3],
            [7, 1, '2026-10-04', 'present', null, 3],
            [8, 1, '2026-10-04', 'leave', 'Pre-approved medical appointment', 3],
            // 2026-10-05
            [1, 1, '2026-10-05', 'present', null, 3],
            [2, 1, '2026-10-05', 'present', null, 3],
            [3, 1, '2026-10-05', 'present', null, 3],
            [4, 1, '2026-10-05', 'present', null, 3],
            [5, 1, '2026-10-05', 'present', null, 3],
            [6, 1, '2026-10-05', 'late', 'Traffic congestion', 3],
            [7, 1, '2026-10-05', 'absent', 'Unexcused absence', 3],
            [8, 1, '2026-10-05', 'present', null, 3],
            // 2026-10-06
            [1, 1, '2026-10-06', 'present', null, 3],
            [2, 1, '2026-10-06', 'late', 'Doctor visit morning', 3],
            [3, 1, '2026-10-06', 'present', null, 3],
            [4, 1, '2026-10-06', 'present', null, 3],
            [5, 1, '2026-10-06', 'present', null, 3],
            [6, 1, '2026-10-06', 'present', null, 3],
            [7, 1, '2026-10-06', 'present', null, 3],
            [8, 1, '2026-10-06', 'present', null, 3],
            // 2026-10-07
            [1, 1, '2026-10-07', 'present', null, 3],
            [2, 1, '2026-10-07', 'present', null, 3],
            [3, 1, '2026-10-07', 'present', null, 3],
            [4, 1, '2026-10-07', 'absent', 'Family function', 3],
            [5, 1, '2026-10-07', 'present', null, 3],
            [6, 1, '2026-10-07', 'present', null, 3],
            [7, 1, '2026-10-07', 'leave', 'Sports tournament representation', 3],
            [8, 1, '2026-10-07', 'present', null, 3],
            // 2026-10-08
            [1, 1, '2026-10-08', 'present', null, 3],
            [2, 1, '2026-10-08', 'present', null, 3],
            [3, 1, '2026-10-08', 'present', null, 3],
            [4, 1, '2026-10-08', 'present', null, 3],
            [5, 1, '2026-10-08', 'present', null, 3],
            [6, 1, '2026-10-08', 'present', null, 3],
            [7, 1, '2026-10-08', 'present', null, 3],
            [8, 1, '2026-10-08', 'present', null, 3],
            // 2026-10-09
            [1, 1, '2026-10-09', 'present', null, 3],
            [2, 1, '2026-10-09', 'late', 'Heavy rainfall delay', 3],
            [3, 1, '2026-10-09', 'present', null, 3],
            [4, 1, '2026-10-09', 'present', null, 3],
            [5, 1, '2026-10-09', 'absent', 'Sick leave', 3],
            [6, 1, '2026-10-09', 'present', null, 3],
            [7, 1, '2026-10-09', 'present', null, 3],
            [8, 1, '2026-10-09', 'present', null, 3],
            // 2026-10-10
            [1, 1, '2026-10-10', 'present', null, 3],
            [2, 1, '2026-10-10', 'present', null, 3],
            [3, 1, '2026-10-10', 'present', null, 3],
            [4, 1, '2026-10-10', 'present', null, 3],
            [5, 1, '2026-10-10', 'present', null, 3],
            [6, 1, '2026-10-10', 'present', null, 3],
            [7, 1, '2026-10-10', 'late', '10 mins late', 3],
            [8, 1, '2026-10-10', 'present', null, 3]
        ];

        foreach ($records as $r) {
            $aStmt->execute($r);
        }
    }
}

function initDatabaseSchema($pdo) {
    // 1. Campuses Table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS campuses (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            code TEXT UNIQUE NOT NULL,
            location TEXT NOT NULL,
            is_main INTEGER DEFAULT 0,
            status TEXT DEFAULT 'active'
        );
    ");

    // 2. Roles Table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS roles (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            key TEXT UNIQUE NOT NULL,
            title TEXT NOT NULL,
            description TEXT,
            category TEXT NOT NULL
        );
    ");

    // 3. Users Table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email TEXT UNIQUE NOT NULL,
            password_hash TEXT NOT NULL,
            full_name TEXT NOT NULL,
            phone TEXT,
            avatar_url TEXT,
            default_campus_id INTEGER DEFAULT 1,
            is_2fa_enabled INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
    ");

    // 4. User Roles Table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS user_roles (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            role_id INTEGER NOT NULL,
            campus_id INTEGER DEFAULT 1,
            is_primary INTEGER DEFAULT 0,
            FOREIGN KEY (user_id) REFERENCES users (id),
            FOREIGN KEY (role_id) REFERENCES roles (id)
        );
    ");

    // 5. OTP Table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS otps (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email TEXT NOT NULL,
            code TEXT NOT NULL,
            purpose TEXT NOT NULL,
            expires_at DATETIME NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
    ");

    // 6. Audit Logs Table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS audit_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            action TEXT NOT NULL,
            details TEXT,
            ip_address TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
    ");

    // Seed Campuses
    $stmt = $pdo->prepare("INSERT INTO campuses (name, code, location, is_main) VALUES (?, ?, ?, ?)");
    $stmt->execute(['Main Campus', 'CAMP-01', 'Green Park, City Center', 1]);
    $stmt->execute(['North Campus', 'CAMP-02', 'Green Park, Trust Area', 0]);
    $stmt->execute(['Lakeside Campus', 'CAMP-03', 'Knowledge Park II', 0]);
    $stmt->execute(['Riverside Campus', 'CAMP-04', 'South Hub Road', 0]);
    $stmt->execute(['East Learning Center', 'CAMP-05', 'Innovation Hub, Pune', 0]);

    // Seed Roles (PDF Section 3)
    $stmt = $pdo->prepare("INSERT INTO roles (key, title, description, category) VALUES (?, ?, ?, ?)");
    $stmt->execute(['principal', 'Principal', 'School command center, approvals, academics, staff & finance', 'Leadership']);
    $stmt->execute(['admin', 'Admin', 'Students, parents, classes, documents, operations & RBAC', 'Administration']);
    $stmt->execute(['teacher', 'Teacher', 'Classes, attendance, lesson plans, homework, marks', 'Academics']);
    $stmt->execute(['student', 'Student', 'Learning, timetable, assignments, exams & CASPA AI', 'Student']);
    $stmt->execute(['parent', 'Parent', 'Children progress, attendance, fees & notices', 'Family']);
    $stmt->execute(['hr', 'HR', 'Employees, recruitment, attendance, leave & payroll', 'Operations']);
    $stmt->execute(['accounts', 'Accounts', 'Fees, receipts, dues, expenses, payroll & budgets', 'Finance']);
    $stmt->execute(['admissions', 'Admission Team', 'Leads, counselling, applications, tests & interviews', 'Operations']);
    $stmt->execute(['transport', 'Transport Manager', 'Buses, routes, drivers, live tracking & maintenance', 'Operations']);
    $stmt->execute(['warden', 'Warden', 'Hostel rooms, attendance, movement, visitors & safety', 'Operations']);
    $stmt->execute(['safety', 'Safety Officer', 'Confidential incidents, investigation & escalation', 'Safety']);
    $stmt->execute(['counsellor', 'Counsellor', 'Appointments, referrals & confidential counselling', 'Safety']);
    $stmt->execute(['librarian', 'Librarian', 'Books, issue/return, fines & digital resources', 'Operations']);
    $stmt->execute(['security', 'Security', 'Visitors, gate entry & pickup verification', 'Safety']);

    // Seed Users (password: password123)
    $hash = password_hash('password123', PASSWORD_BCRYPT);
    $userStmt = $pdo->prepare("INSERT INTO users (email, password_hash, full_name, phone, default_campus_id, is_2fa_enabled) VALUES (?, ?, ?, ?, ?, ?)");
    $urStmt = $pdo->prepare("INSERT INTO user_roles (user_id, role_id, campus_id, is_primary) VALUES (?, ?, ?, ?)");

    $users = [
        ['admin@edunexa.edu', 'Sarah Jenkins (Super Admin)', 'admin', 1],
        ['principal@edunexa.edu', 'Dr. Arthur Vance (Principal)', 'principal', 1],
        ['teacher@edunexa.edu', 'Robert Miller (Teacher)', 'teacher', 1],
        ['student@edunexa.edu', 'Alex Rivera (Student)', 'student', 1],
        ['parent@edunexa.edu', 'David Rivera (Parent)', 'parent', 1],
        ['safety@edunexa.edu', 'Marcus Vance (Safety Officer)', 'safety', 1],
        ['hr@edunexa.edu', 'Elena Rostova (HR Lead)', 'hr', 1],
        ['accounts@edunexa.edu', 'Michael Sterling (Finance)', 'accounts', 1],
    ];

    foreach ($users as $u) {
        $userStmt->execute([$u[0], $hash, $u[1], '+1-555-0192', $u[3], 1]);
        $uid = $pdo->lastInsertId();
        
        $roleRow = $pdo->query("SELECT id FROM roles WHERE key = '{$u[2]}'")->fetch();
        if ($roleRow) {
            $urStmt->execute([$uid, $roleRow['id'], 1, 1]);
        }
    }

    // Give admin user multi-role access so they can test the Workspace Selector!
    $adminUser = $pdo->query("SELECT id FROM users WHERE email = 'admin@edunexa.edu'")->fetch();
    if ($adminUser) {
        $extraRoles = ['principal', 'teacher', 'student', 'parent', 'safety', 'accounts'];
        foreach ($extraRoles as $rKey) {
            $r = $pdo->query("SELECT id FROM roles WHERE key = '{$rKey}'")->fetch();
            if ($r) {
                $urStmt->execute([$adminUser['id'], $r['id'], 1, 0]);
            }
        }
    }
}
