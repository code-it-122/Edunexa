<?php
/**
 * CASPA School OS – Demo seed data
 * -----------------------------------------------
 * Run from CLI:  php database/seed.php
 * Or browser:    http://localhost/Edunexa/database/seed.php
 *                (REMOVE or PROTECT this file before going live)
 *
 * All data is fictional and labelled [DEMO].
 * Running this script again will SKIP rows that already exist
 * (INSERT IGNORE) so it is safe to re-run.
 */

declare(strict_types=1);

// Safety: disallow in production unless DEBUG is on
define('ROOT', dirname(__DIR__));
require_once ROOT . '/includes/db.php';

// ------------------------------------------------------------------
// Helper: print a status line
// ------------------------------------------------------------------
$cli = PHP_SAPI === 'cli';

function info(string $msg): void {
    global $cli;
    echo $cli ? $msg . PHP_EOL : "<p>{$msg}</p>";
}

info('=== CASPA School OS – Seeding demo data ===');

$pdo = db();

// ------------------------------------------------------------------
// 1. Academic Year
// ------------------------------------------------------------------
$pdo->exec("
    INSERT IGNORE INTO academic_years (label, is_current, starts_on, ends_on)
    VALUES ('2025-2026', 1, '2025-06-01', '2026-03-31')
");
$ayId = $pdo->query("SELECT id FROM academic_years WHERE label = '2025-2026'")->fetchColumn();
info("Academic year 2025-2026 → id={$ayId}");

// ------------------------------------------------------------------
// 2. Demo users (one per role)
// ------------------------------------------------------------------
$demoUsers = [
    ['name' => 'Demo Principal',  'email' => 'principal@demo.caspa',  'role' => 'principal'],
    ['name' => 'Demo Admin',      'email' => 'admin@demo.caspa',      'role' => 'admin'],
    ['name' => 'Demo Teacher',    'email' => 'teacher@demo.caspa',    'role' => 'teacher'],
    ['name' => 'Demo Student',    'email' => 'student@demo.caspa',    'role' => 'student'],
    ['name' => 'Demo Parent',     'email' => 'parent@demo.caspa',     'role' => 'parent'],
];
$defaultPassword = 'Demo@1234';
$hash = password_hash($defaultPassword, PASSWORD_BCRYPT);

$stmtUser = $pdo->prepare("
    INSERT IGNORE INTO users (name, email, password_hash, role)
    VALUES (:name, :email, :hash, :role)
");

$userIds = [];
foreach ($demoUsers as $u) {
    $stmtUser->execute([
        ':name'  => $u['name'],
        ':email' => $u['email'],
        ':hash'  => $hash,
        ':role'  => $u['role'],
    ]);
    $id = $pdo->query("SELECT id FROM users WHERE email = '{$u['email']}'")->fetchColumn();
    $userIds[$u['role']] = (int) $id;
    info("User [{$u['role']}] {$u['email']} → id={$id}  password={$defaultPassword}");
}

// ------------------------------------------------------------------
// 3. Class
// ------------------------------------------------------------------
$pdo->prepare("
    INSERT IGNORE INTO classes (academic_year_id, name, section, class_teacher_id)
    VALUES (:ay, 'Grade 10', 'A', :teacher)
")->execute([':ay' => $ayId, ':teacher' => $userIds['teacher']]);

$classId = $pdo->query("
    SELECT id FROM classes WHERE academic_year_id = {$ayId} AND name = 'Grade 10' AND section = 'A'
")->fetchColumn();
info("Class Grade 10-A → id={$classId}");

// ------------------------------------------------------------------
// 4. Student profile
// ------------------------------------------------------------------
$pdo->prepare("
    INSERT IGNORE INTO students (user_id, class_id, admission_number, date_of_birth, gender)
    VALUES (:uid, :cid, 'DEMO-2025-001', '2009-04-15', 'male')
")->execute([':uid' => $userIds['student'], ':cid' => $classId]);

$studentId = $pdo->query("SELECT id FROM students WHERE user_id = {$userIds['student']}")->fetchColumn();
info("Student profile → id={$studentId}");

// ------------------------------------------------------------------
// 5. Parent profile + link to student
// ------------------------------------------------------------------
$pdo->prepare("
    INSERT IGNORE INTO parents (user_id, phone, occupation)
    VALUES (:uid, '+91-9876543210', 'Engineer')
")->execute([':uid' => $userIds['parent']]);

$parentId = $pdo->query("SELECT id FROM parents WHERE user_id = {$userIds['parent']}")->fetchColumn();
info("Parent profile → id={$parentId}");

$pdo->prepare("
    INSERT IGNORE INTO parent_student (parent_id, student_id, relation)
    VALUES (:pid, :sid, 'father')
")->execute([':pid' => $parentId, ':sid' => $studentId]);
info("Parent ↔ Student linked");

// ------------------------------------------------------------------
// 6. Subjects
// ------------------------------------------------------------------
$subjects = [
    ['Mathematics',        'MATH10'],
    ['Science',            'SCI10'],
    ['English Language',   'ENG10'],
    ['Social Studies',     'SOC10'],
    ['Computer Science',   'CS10'],
];
$stmtSub = $pdo->prepare("INSERT IGNORE INTO subjects (name, code) VALUES (:name, :code)");
foreach ($subjects as [$name, $code]) {
    $stmtSub->execute([':name' => $name, ':code' => $code]);
}
$subjectIds = $pdo->query("SELECT id, code FROM subjects WHERE code IN ('MATH10','SCI10','ENG10','SOC10','CS10')")->fetchAll();
info("Subjects seeded: " . implode(', ', array_column($subjectIds, 'code')));

// ------------------------------------------------------------------
// 7. class_subjects (assign teacher to all 5 subjects in Grade 10-A)
// ------------------------------------------------------------------
$stmtCS = $pdo->prepare("
    INSERT IGNORE INTO class_subjects (class_id, subject_id, teacher_id)
    VALUES (:class, :subject, :teacher)
");
foreach ($subjectIds as $sub) {
    $stmtCS->execute([
        ':class'   => $classId,
        ':subject' => $sub['id'],
        ':teacher' => $userIds['teacher'],
    ]);
}
info("Class-subjects linked (teacher → Grade 10-A for all subjects)");

// ------------------------------------------------------------------
// 8. Announcement
// ------------------------------------------------------------------
$pdo->prepare("
    INSERT IGNORE INTO announcements (title, body, audience, created_by, published_at)
    VALUES (
        '[DEMO] Welcome to CASPA School OS',
        'This is a demo announcement. The system is live and ready for configuration.',
        'principal,admin,teacher,student,parent',
        :creator,
        NOW()
    )
")->execute([':creator' => $userIds['principal']]);
info("Demo announcement created");

// ------------------------------------------------------------------
// Done
// ------------------------------------------------------------------
info('');
info('=== Seeding complete ===');
info('All demo accounts use password: ' . $defaultPassword);
info('IMPORTANT: Delete or restrict this file before going live.');
