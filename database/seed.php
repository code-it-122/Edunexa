<?php
/**
 * CASPA School OS – Demo seed data  (v2)
 * -----------------------------------------------
 * Safe to run multiple times: deletes all demo rows first, then re-inserts.
 *
 * GUARD: only runs from CLI or localhost (127.0.0.1 / ::1).
 *
 * CLI:     php database/seed.php
 * Browser: http://localhost/Edunexa/database/seed.php
 *
 * All data is fictional and labelled [DEMO].
 * All passwords: demo123
 */

declare(strict_types=1);

// ── Access guard ─────────────────────────────────────────────────────────────
$isCli = PHP_SAPI === 'cli';

if (!$isCli) {
    $remote = $_SERVER['REMOTE_ADDR'] ?? '';
    if (!in_array($remote, ['127.0.0.1', '::1'], true)) {
        http_response_code(403);
        die('403 Forbidden – seed.php may only run from localhost or the CLI.');
    }
}

// ── Bootstrap ─────────────────────────────────────────────────────────────────
define('ROOT', dirname(__DIR__));
require_once ROOT . '/includes/db.php';

$pdo = db();

// Disable FK checks so we can delete in any order
$pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

// Helper: print a line
function out(string $msg): void {
    global $isCli;
    echo $isCli ? $msg . PHP_EOL : "<p style='font-family:monospace;margin:2px 0'>{$msg}</p>";
}

out('=== CASPA School OS — Seeder v2 ===');
out('Clearing previous demo data…');

// ── Wipe demo rows (identified by email domain @demo.caspa) ──────────────────
// Order: leaf tables first
$pdo->exec("DELETE FROM grades      WHERE student_id IN (SELECT id FROM students WHERE user_id IN (SELECT id FROM users WHERE email LIKE '%@demo.caspa'))");
$pdo->exec("DELETE FROM attendance  WHERE student_id IN (SELECT id FROM students WHERE user_id IN (SELECT id FROM users WHERE email LIKE '%@demo.caspa'))");
$pdo->exec("DELETE FROM parent_student");        // small table, wipe fully
$pdo->exec("DELETE FROM parents     WHERE user_id IN (SELECT id FROM users WHERE email LIKE '%@demo.caspa')");
$pdo->exec("DELETE FROM students    WHERE user_id IN (SELECT id FROM users WHERE email LIKE '%@demo.caspa')");
$pdo->exec("DELETE FROM class_subjects");        // wipe then re-seed
$pdo->exec("DELETE FROM assignments WHERE created_by IN (SELECT id FROM users WHERE email LIKE '%@demo.caspa')");
$pdo->exec("DELETE FROM announcements WHERE created_by IN (SELECT id FROM users WHERE email LIKE '%@demo.caspa')");
// Wipe all demo classes (linked to demo academic year)
$pdo->exec("DELETE FROM classes     WHERE academic_year_id IN (SELECT id FROM academic_years WHERE label = '2025-2026')");
$pdo->exec("DELETE FROM academic_years WHERE label = '2025-2026'");
$pdo->exec("DELETE FROM subjects    WHERE code LIKE 'DEMO-%'");
$pdo->exec("DELETE FROM users       WHERE email LIKE '%@demo.caspa'");

$pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
out('Done clearing.');

// ── Password ──────────────────────────────────────────────────────────────────
$hash = password_hash('demo123', PASSWORD_BCRYPT);

// ── Helper: insert a user, return id ─────────────────────────────────────────
function insertUser(PDO $pdo, string $name, string $email, string $role, string $hash): int {
    $st = $pdo->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (:n,:e,:h,:r)");
    $st->execute([':n' => $name, ':e' => $email, ':h' => $hash, ':r' => $role]);
    return (int) $pdo->lastInsertId();
}

// ── 1. Staff ──────────────────────────────────────────────────────────────────
$principalId = insertUser($pdo, 'Dr. Priya Nair',     'principal@demo.caspa', 'principal', $hash);
$adminId     = insertUser($pdo, 'Ravi Menon',          'admin@demo.caspa',     'admin',     $hash);
$t1Id        = insertUser($pdo, 'Ms. Ananya Sharma',   'teacher1@demo.caspa',  'teacher',   $hash);
$t2Id        = insertUser($pdo, 'Mr. Kiran Bose',      'teacher2@demo.caspa',  'teacher',   $hash);
$t3Id        = insertUser($pdo, 'Ms. Divya Pillai',    'teacher3@demo.caspa',  'teacher',   $hash);
out("Users inserted: 1 principal, 1 admin, 3 teachers");

// ── 2. Academic year ─────────────────────────────────────────────────────────
$pdo->exec("INSERT INTO academic_years (label, is_current, starts_on, ends_on) VALUES ('2025-2026', 1, '2025-06-01', '2026-03-31')");
$ayId = (int) $pdo->lastInsertId();
out("Academic year 2025-2026 → id={$ayId}");

// ── 3. Classes ────────────────────────────────────────────────────────────────
$pdo->prepare("INSERT INTO classes (academic_year_id, name, section, class_teacher_id) VALUES (:ay,'Grade 8','A',:t)")
    ->execute([':ay' => $ayId, ':t' => $t1Id]);
$class8A = (int) $pdo->lastInsertId();

$pdo->prepare("INSERT INTO classes (academic_year_id, name, section, class_teacher_id) VALUES (:ay,'Grade 9','A',:t)")
    ->execute([':ay' => $ayId, ':t' => $t2Id]);
$class9A = (int) $pdo->lastInsertId();

out("Classes: Grade 8-A (id={$class8A}), Grade 9-A (id={$class9A})");

// ── 4. Subjects ───────────────────────────────────────────────────────────────
$subjectDefs = [
    ['Mathematics',      'DEMO-MATH'],
    ['English Language', 'DEMO-ENG'],
    ['Science',          'DEMO-SCI'],
    ['Social Studies',   'DEMO-SOC'],
    ['Computer Science', 'DEMO-CS'],
];
$subjectIds = [];
$stSub = $pdo->prepare("INSERT INTO subjects (name, code) VALUES (:n,:c)");
foreach ($subjectDefs as [$name, $code]) {
    $stSub->execute([':n' => $name, ':c' => $code]);
    $subjectIds[$code] = (int) $pdo->lastInsertId();
}
out("Subjects inserted: " . implode(', ', array_column($subjectDefs, 1)));

// ── 5. class_subjects / teacher_assignments ───────────────────────────────────
// Grade 8-A: t1=Math,Eng,Sci | t3=Soc | CS has NO teacher (tests access limits)
// Grade 9-A: t2=Math,Sci,CS  | t1=Eng | Soc has NO teacher
$csAssignments = [
    // [class_id, subject_code, teacher_id|null]
    [$class8A, 'DEMO-MATH', $t1Id],
    [$class8A, 'DEMO-ENG',  $t1Id],
    [$class8A, 'DEMO-SCI',  $t1Id],
    [$class8A, 'DEMO-SOC',  $t3Id],
    [$class8A, 'DEMO-CS',   null],   // ← no teacher (access limit test)
    [$class9A, 'DEMO-MATH', $t2Id],
    [$class9A, 'DEMO-SCI',  $t2Id],
    [$class9A, 'DEMO-CS',   $t2Id],
    [$class9A, 'DEMO-ENG',  $t1Id],
    [$class9A, 'DEMO-SOC',  null],   // ← no teacher
];
$stCS = $pdo->prepare("INSERT INTO class_subjects (class_id, subject_id, teacher_id) VALUES (:c,:s,:t)");
$csIds = [];  // key: "classId-subjectCode" → class_subject.id
foreach ($csAssignments as [$cid, $scode, $tid]) {
    $stCS->execute([':c' => $cid, ':s' => $subjectIds[$scode], ':t' => $tid]);
    $csIds["{$cid}-{$scode}"] = (int) $pdo->lastInsertId();
}
out("class_subjects (teacher_assignments) inserted: " . count($csAssignments) . " rows");
out("  → Grade 8-A / CS and Grade 9-A / Social Studies have NO teacher assigned (for access-limit testing)");

// ── 6. Student login accounts (2) ────────────────────────────────────────────
$stu1UserId = insertUser($pdo, 'Aarav Mehta',  'student1@demo.caspa', 'student', $hash);
$stu2UserId = insertUser($pdo, 'Sanya Kapoor', 'student2@demo.caspa', 'student', $hash);
out("Student login accounts: student1@demo.caspa (Aarav, Grade 8-A), student2@demo.caspa (Sanya, Grade 9-A)");

// ── 7. Parent login accounts (2) ─────────────────────────────────────────────
$par1UserId = insertUser($pdo, 'Raj Mehta',    'parent1@demo.caspa',  'parent',  $hash);
$par2UserId = insertUser($pdo, 'Sunita Kapoor','parent2@demo.caspa',  'parent',  $hash);
out("Parent accounts: parent1@demo.caspa (Raj Mehta), parent2@demo.caspa (Sunita Kapoor)");

// ── 8. 20 students across 2 classes ──────────────────────────────────────────
// 10 in Grade 8-A, 10 in Grade 9-A.
// Slots 0 and 10 are the login accounts (stu1, stu2).
$studentNames8A = [
    ['Aarav Mehta',   'student1@demo.caspa', $stu1UserId],   // login account
    ['Arjun Singh',   null, null],
    ['Pooja Desai',   null, null],
    ['Rohan Verma',   null, null],
    ['Meera Iyer',    null, null],
    ['Siddharth Rao', null, null],
    ['Ananya Gupta',  null, null],
    ['Kabir Joshi',   null, null],
    ['Priya Kumar',   null, null],
    ['Riya Shah',     null, null],
];
$studentNames9A = [
    ['Sanya Kapoor',  'student2@demo.caspa', $stu2UserId],   // login account
    ['Dev Malhotra',  null, null],
    ['Tanya Bose',    null, null],
    ['Ishaan Nair',   null, null],
    ['Kavya Pillai',  null, null],
    ['Aditya Sharma', null, null],
    ['Nisha Patel',   null, null],
    ['Vikram Menon',  null, null],
    ['Lavanya Das',   null, null],
    ['Rahul Tiwari',  null, null],
];

$stStuUser = $pdo->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (:n,:e,:h,'student')");
$stStuProf = $pdo->prepare("INSERT INTO students (user_id, class_id, admission_number, gender) VALUES (:u,:c,:a,:g)");

$studentProfileIds = [];   // [userId => studentProfileId]

function seedStudents(
    PDO $pdo,
    array $names,
    int $classId,
    string $hash,
    array &$studentProfileIds,
    int &$admCounter
): void {
    global $stStuUser, $stStuProf;
    $genders = ['male','female','male','male','female','male','female','male','female','female'];
    $i = 0;
    foreach ($names as [$name, $email, $existingUserId]) {
        $gender = $genders[$i % count($genders)];
        $admNo  = 'DEMO-' . str_pad((string)$admCounter, 4, '0', STR_PAD_LEFT);
        $admCounter++;

        if ($existingUserId !== null) {
            // Login-account student: user row already exists
            $userId = $existingUserId;
        } else {
            $demoEmail = 'stu_' . strtolower(str_replace(' ', '_', $name)) . '@demo.caspa';
            $stStuUser->execute([':n' => $name, ':e' => $demoEmail, ':h' => $hash]);
            $userId = (int) $pdo->lastInsertId();
        }
        $stStuProf->execute([':u' => $userId, ':c' => $classId, ':a' => $admNo, ':g' => $gender]);
        $studentProfileIds[$userId] = (int) $pdo->lastInsertId();
        $i++;
    }
}

$admCounter = 1;
seedStudents($pdo, $studentNames8A, $class8A, $hash, $studentProfileIds, $admCounter);
seedStudents($pdo, $studentNames9A, $class9A, $hash, $studentProfileIds, $admCounter);
out("20 students inserted (10 per class); admission numbers DEMO-0001 … DEMO-0020");

// ── 9. Parent profiles + parent_student links ─────────────────────────────────
$pdo->prepare("INSERT INTO parents (user_id, phone, occupation) VALUES (:u,'+91-9800000001','Engineer')")
    ->execute([':u' => $par1UserId]);
$par1ProfId = (int) $pdo->lastInsertId();

$pdo->prepare("INSERT INTO parents (user_id, phone, occupation) VALUES (:u,'+91-9800000002','Doctor')")
    ->execute([':u' => $par2UserId]);
$par2ProfId = (int) $pdo->lastInsertId();

// Link parent1 → Aarav Mehta (stu1), parent2 → Sanya Kapoor (stu2)
$stu1ProfileId = $studentProfileIds[$stu1UserId];
$stu2ProfileId = $studentProfileIds[$stu2UserId];

$pdo->prepare("INSERT INTO parent_student (parent_id, student_id, relation) VALUES (:p,:s,'father')")
    ->execute([':p' => $par1ProfId, ':s' => $stu1ProfileId]);
$pdo->prepare("INSERT INTO parent_student (parent_id, student_id, relation) VALUES (:p,:s,'mother')")
    ->execute([':p' => $par2ProfId, ':s' => $stu2ProfileId]);
out("Parent profiles + parent_student links inserted");

// ── 10. Announcements (3) ────────────────────────────────────────────────────
$stAnn = $pdo->prepare("
    INSERT INTO announcements (title, body, audience, created_by, published_at)
    VALUES (:t, :b, :a, :c, NOW())
");

$stAnn->execute([
    ':t' => '[DEMO] Welcome to CASPA School OS 2025-2026',
    ':b' => 'The academic year 2025-2026 has begun. All students and parents please check the timetable.',
    ':a' => 'principal,admin,teacher,student,parent',
    ':c' => $principalId,
]);
$stAnn->execute([
    ':t' => '[DEMO] PTM Scheduled – 25 October 2025',
    ':b' => 'Parent-Teacher Meetings will be held on 25 October 2025 between 10 AM and 1 PM. Parents must bring ID.',
    ':a' => 'student,parent',
    ':c' => $adminId,
]);
$stAnn->execute([
    ':t' => '[DEMO] Mid-Term Examination Timetable',
    ':b' => 'Mid-term exams start 5 November 2025. Mathematics on Day 1, Science on Day 2, English on Day 3.',
    ':a' => 'teacher,student,parent',
    ':c' => $principalId,
]);
out("3 announcements inserted");

// ── Summary ───────────────────────────────────────────────────────────────────
out('');
out('=== Seeding complete ===');
out('');
out('Demo login accounts (all passwords: demo123)');
out(str_repeat('-', 52));

$logins = [
    ['principal@demo.caspa', 'principal', 'Dr. Priya Nair'],
    ['admin@demo.caspa',     'admin',     'Ravi Menon'],
    ['teacher1@demo.caspa',  'teacher',   'Ms. Ananya Sharma'],
    ['teacher2@demo.caspa',  'teacher',   'Mr. Kiran Bose'],
    ['teacher3@demo.caspa',  'teacher',   'Ms. Divya Pillai'],
    ['student1@demo.caspa',  'student',   'Aarav Mehta (Grade 8-A)'],
    ['student2@demo.caspa',  'student',   'Sanya Kapoor (Grade 9-A)'],
    ['parent1@demo.caspa',   'parent',    'Raj Mehta → Aarav Mehta'],
    ['parent2@demo.caspa',   'parent',    'Sunita Kapoor → Sanya Kapoor'],
];

foreach ($logins as [$email, $role, $note]) {
    out(sprintf("  %-35s %-12s %s", $email, $role, $note));
}

out('');
out('IMPORTANT: Remove or restrict seed.php before going live.');
