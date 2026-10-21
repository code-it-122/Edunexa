<?php
/**
 * CASPA School OS – pages/shared/student_profile.php
 *
 * Detailed Student Profile & Attendance Summary
 * Access: Scoped via allowed_class_ids($user).
 *         If student's class is outside scope: returns 403 Access Denied.
 */

declare(strict_types=1);

if (!defined('ROOT')) {
    define('ROOT', dirname(__DIR__, 2));
}

require_once ROOT . '/includes/auth.php';
require_once ROOT . '/includes/db.php';

session_boot();
require_login();

$user = current_user();
$pdo  = db();

$studentId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// 1. Fetch student
$sql = "
    SELECT s.id, s.user_id, s.class_id, s.admission_number, s.date_of_birth, s.gender, s.address, s.created_at,
           u.name, u.email, u.is_active,
           c.name as class_name, c.section as class_section,
           ay.label as academic_year,
           ct.name as class_teacher_name
    FROM students s
    JOIN users u ON u.id = s.user_id
    LEFT JOIN classes c ON c.id = s.class_id
    LEFT JOIN academic_years ay ON ay.id = c.academic_year_id
    LEFT JOIN users ct ON ct.id = c.class_teacher_id
    WHERE s.id = :id
    LIMIT 1
";
$st = $pdo->prepare($sql);
$st->execute([':id' => $studentId]);
$student = $st->fetch();

// 2. Scope check: verify allowed_class_ids
$allowedClasses = allowed_class_ids($user);
$isAllowed = false;

if ($student && $student['class_id'] !== null && in_array((int) $student['class_id'], $allowedClasses, true)) {
    $isAllowed = true;
}

if ($student && !$isAllowed) {
    http_response_code(403);
}

// Custom page metadata
$pageTitle = $student ? $student['name'] : 'Student Profile';
$pageRole  = $user['role'];

// Breadcrumbs
$breadcrumbs = [
    ['label' => ucfirst($user['role']), 'href' => APP_URL . '/pages/' . $user['role'] . '/dashboard.php'],
    ['label' => 'Students', 'href' => APP_URL . '/pages/admin/students.php'],
    ['label' => $pageTitle, 'href' => ''],
];

require_once ROOT . '/includes/layout_header.php';
?>

<?php if (!$student): ?>
    <div class="caspa-card text-center py-5">
        <div class="fs-1 text-muted mb-2">🔍</div>
        <h3 class="text-navy fw-bold">Student Not Found</h3>
        <p class="text-muted">The student profile you requested does not exist or may have been removed.</p>
        <a href="<?= APP_URL ?>/pages/admin/students.php" class="btn btn-caspa-primary mt-2">
            <i class="bi bi-arrow-left me-1"></i>Back to Students Directory
        </a>
    </div>
<?php elseif (!$isAllowed): ?>
    <?php http_response_code(403); ?>
    <div class="caspa-card text-center py-5 border-danger-subtle">
        <div class="fs-1 text-danger mb-2"><i class="bi bi-shield-lock-fill"></i></div>
        <h3 class="text-danger fw-bold">403 – Access Denied</h3>
        <p class="text-muted max-w-md mx-auto" style="max-width: 500px;">
            You do not have permission to view this student.
            <?php if ($user['role'] === 'teacher'): ?>
                Teachers are strictly restricted to students in their assigned classes.
            <?php else: ?>
                Your current role access scope does not include this student's class.
            <?php endif; ?>
        </p>
        <a href="<?= APP_URL ?>/pages/admin/students.php" class="btn btn-outline-secondary mt-2">
            <i class="bi bi-arrow-left me-1"></i>Return to Accessible Directory
        </a>
    </div>
<?php else: ?>
    <?php
    // Fetch parent info
    $parentSql = "
        SELECT p.id, p.phone, p.occupation, u.name, u.email, ps.relation
        FROM parent_student ps
        JOIN parents p ON p.id = ps.parent_id
        JOIN users u ON u.id = p.user_id
        WHERE ps.student_id = :id
        ORDER BY u.name ASC
    ";
    $pSt = $pdo->prepare($parentSql);
    $pSt->execute([':id' => $studentId]);
    $parents = $pSt->fetchAll();

    // Fetch attendance summary
    $attSql = "
        SELECT
            COUNT(*) as total_days,
            COALESCE(SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END), 0) as present_days,
            COALESCE(SUM(CASE WHEN a.status = 'absent'  THEN 1 ELSE 0 END), 0) as absent_days,
            COALESCE(SUM(CASE WHEN a.status = 'late'    THEN 1 ELSE 0 END), 0) as late_days,
            COALESCE(SUM(CASE WHEN a.status = 'excused' THEN 1 ELSE 0 END), 0) as excused_days
        FROM attendance a
        WHERE a.student_id = :id
    ";
    $aSt = $pdo->prepare($attSql);
    $aSt->execute([':id' => $studentId]);
    $att = $aSt->fetch();

    $totalDays   = (int) ($att['total_days'] ?? 0);
    $presentDays = (int) ($att['present_days'] ?? 0);
    $absentDays  = (int) ($att['absent_days'] ?? 0);
    $lateDays    = (int) ($att['late_days'] ?? 0);
    $excusedDays = (int) ($att['excused_days'] ?? 0);
    $attRate     = $totalDays > 0 ? round(($presentDays / $totalDays) * 100, 1) : 0.0;

    // Recent records
    $recSql = "
        SELECT a.id, a.date, a.status, sub.name as subject_name, sub.code as subject_code,
               rec.name as recorded_by_name
        FROM attendance a
        JOIN class_subjects cs ON cs.id = a.class_subject_id
        JOIN subjects sub ON sub.id = cs.subject_id
        JOIN users rec ON rec.id = a.recorded_by
        WHERE a.student_id = :id
        ORDER BY a.date DESC
        LIMIT 10
    ";
    $rSt = $pdo->prepare($recSql);
    $rSt->execute([':id' => $studentId]);
    $recentAttendance = $rSt->fetchAll();

    // Initials
    $nameParts = explode(' ', trim($student['name']));
    $initials = count($nameParts) > 1
        ? strtoupper(substr($nameParts[0], 0, 1) . substr(end($nameParts), 0, 1))
        : strtoupper(substr($nameParts[0], 0, 2));
    ?>

    <!-- Top Profile Banner -->
    <div class="caspa-card mb-4 p-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold shadow-sm" style="width: 68px; height: 68px; font-size: 1.5rem; background: linear-gradient(135deg, var(--color-navy) 0%, var(--color-teal) 100%);">
                    <?= htmlspecialchars($initials, ENT_QUOTES) ?>
                </div>
                <div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <h2 class="text-navy fw-bold mb-0"><?= htmlspecialchars($student['name'], ENT_QUOTES) ?></h2>
                        <span class="badge badge-navy">
                            <?= htmlspecialchars($student['class_name'] . ' - ' . $student['class_section'], ENT_QUOTES) ?>
                        </span>
                        <span class="badge bg-light text-secondary border font-monospace">
                            <?= htmlspecialchars($student['admission_number'], ENT_QUOTES) ?>
                        </span>
                    </div>
                    <div class="text-muted small mt-1">
                        <i class="bi bi-envelope me-1"></i><?= htmlspecialchars($student['email'], ENT_QUOTES) ?>
                        <?php if (!empty($student['academic_year'])): ?>
                            <span class="mx-2">&bull;</span>
                            <i class="bi bi-calendar3 me-1"></i>Academic Year: <?= htmlspecialchars($student['academic_year'], ENT_QUOTES) ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div>
                <a href="<?= APP_URL ?>/pages/admin/students.php" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i>Back to Directory
                </a>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Left Column: Personal Information & Parents -->
        <div class="col-12 col-lg-5">
            <!-- Student Information Card -->
            <div class="caspa-card mb-4">
                <h5 class="caspa-card-title mb-3 d-flex align-items-center gap-2">
                    <i class="bi bi-person-lines-fill text-teal"></i>
                    <span>Student Information</span>
                </h5>
                <dl class="row mb-0 small">
                    <dt class="col-5 text-muted">Admission No:</dt>
                    <dd class="col-7 font-monospace fw-semibold"><?= htmlspecialchars($student['admission_number'], ENT_QUOTES) ?></dd>

                    <dt class="col-5 text-muted">Class &amp; Section:</dt>
                    <dd class="col-7"><?= htmlspecialchars($student['class_name'] . ' - ' . $student['class_section'], ENT_QUOTES) ?></dd>

                    <dt class="col-5 text-muted">Class Teacher:</dt>
                    <dd class="col-7"><?= htmlspecialchars($student['class_teacher_name'] ?? 'Not assigned', ENT_QUOTES) ?></dd>

                    <dt class="col-5 text-muted">Gender:</dt>
                    <dd class="col-7 text-capitalize"><?= htmlspecialchars($student['gender'] ?? 'Not specified', ENT_QUOTES) ?></dd>

                    <dt class="col-5 text-muted">Date of Birth:</dt>
                    <dd class="col-7"><?= !empty($student['date_of_birth']) ? htmlspecialchars($student['date_of_birth'], ENT_QUOTES) : 'Not provided' ?></dd>

                    <dt class="col-5 text-muted">Address:</dt>
                    <dd class="col-7"><?= !empty($student['address']) ? nl2br(htmlspecialchars($student['address'], ENT_QUOTES)) : 'Not provided' ?></dd>

                    <dt class="col-5 text-muted">Account Status:</dt>
                    <dd class="col-7">
                        <?php if ($student['is_active']): ?>
                            <span class="badge bg-success-subtle text-success"><i class="bi bi-check-circle me-1"></i>Active</span>
                        <?php else: ?>
                            <span class="badge bg-danger-subtle text-danger">Inactive</span>
                        <?php endif; ?>
                    </dd>
                </dl>
            </div>

            <!-- Parents / Guardians Card -->
            <div class="caspa-card">
                <h5 class="caspa-card-title mb-3 d-flex align-items-center gap-2">
                    <i class="bi bi-people-fill text-teal"></i>
                    <span>Parents &amp; Guardians</span>
                </h5>
                <?php if (empty($parents)): ?>
                    <p class="text-muted small mb-0 fst-italic">No linked parent contacts found.</p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($parents as $p): ?>
                        <div class="list-group-item px-0 py-2 border-bottom">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="fw-semibold text-navy"><?= htmlspecialchars($p['name'], ENT_QUOTES) ?></div>
                                    <div class="small text-muted"><i class="bi bi-envelope me-1"></i><?= htmlspecialchars($p['email'], ENT_QUOTES) ?></div>
                                    <?php if (!empty($p['phone'])): ?>
                                        <div class="small text-muted"><i class="bi bi-telephone me-1"></i><?= htmlspecialchars($p['phone'], ENT_QUOTES) ?></div>
                                    <?php endif; ?>
                                </div>
                                <span class="badge bg-primary-subtle text-primary text-capitalize">
                                    <?= htmlspecialchars($p['relation'] ?? 'Guardian', ENT_QUOTES) ?>
                                </span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right Column: Attendance Summary & Log -->
        <div class="col-12 col-lg-7">
            <!-- Attendance KPI Card -->
            <div class="caspa-card mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="caspa-card-title mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-calendar-check-fill text-teal"></i>
                        <span>Attendance Summary</span>
                    </h5>
                    <span class="badge <?= $attRate >= 85 ? 'bg-success' : ($attRate >= 75 ? 'bg-warning text-dark' : 'bg-danger') ?> fs-6 px-3 py-2">
                        <?= $attRate ?>% Attendance Rate
                    </span>
                </div>

                <!-- Progress Bar -->
                <div class="progress mb-4" style="height: 10px;">
                    <div
                        class="progress-bar <?= $attRate >= 85 ? 'bg-success' : ($attRate >= 75 ? 'bg-warning' : 'bg-danger') ?>"
                        role="progressbar"
                        style="width: <?= min(100, max(0, $attRate)) ?>%;"
                        aria-valuenow="<?= $attRate ?>"
                        aria-valuemin="0"
                        aria-valuemax="100"
                    ></div>
                </div>

                <!-- 4 KPI Blocks -->
                <div class="row g-2 text-center">
                    <div class="col-6 col-md-3">
                        <div class="p-2 rounded bg-light border">
                            <div class="fs-4 fw-bold text-navy"><?= $totalDays ?></div>
                            <div class="small text-muted">Total Recorded</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-2 rounded bg-success-subtle border border-success-subtle">
                            <div class="fs-4 fw-bold text-success"><?= $presentDays ?></div>
                            <div class="small text-success">Present Days</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-2 rounded bg-danger-subtle border border-danger-subtle">
                            <div class="fs-4 fw-bold text-danger"><?= $absentDays ?></div>
                            <div class="small text-danger">Absent Days</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-2 rounded bg-warning-subtle border border-warning-subtle">
                            <div class="fs-4 fw-bold text-warning"><?= $lateDays + $excusedDays ?></div>
                            <div class="small text-warning">Late / Excused</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Attendance Table Card -->
            <div class="caspa-card p-0 overflow-hidden">
                <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-navy">Recent Attendance Sessions</h6>
                    <span class="small text-muted">Last 10 records</span>
                </div>
                <div class="table-responsive">
                    <table class="table caspa-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th scope="col">Date</th>
                                <th scope="col">Subject</th>
                                <th scope="col">Status</th>
                                <th scope="col">Recorded By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentAttendance)): ?>
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">
                                    No attendance sessions recorded yet for this student.
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($recentAttendance as $rec): ?>
                                <?php
                                $statusBadges = [
                                    'present' => 'bg-success-subtle text-success border-success-subtle',
                                    'absent'  => 'bg-danger-subtle text-danger border-danger-subtle',
                                    'late'    => 'bg-warning-subtle text-warning border-warning-subtle',
                                    'excused' => 'bg-info-subtle text-info border-info-subtle',
                                ];
                                $badgeClass = $statusBadges[$rec['status']] ?? 'bg-secondary-subtle text-secondary';
                                ?>
                                <tr>
                                    <td class="font-monospace small"><?= htmlspecialchars($rec['date'], ENT_QUOTES) ?></td>
                                    <td>
                                        <div class="fw-semibold text-navy small"><?= htmlspecialchars($rec['subject_name'], ENT_QUOTES) ?></div>
                                        <div class="text-muted" style="font-size: 0.75rem;"><?= htmlspecialchars($rec['subject_code'], ENT_QUOTES) ?></div>
                                    </td>
                                    <td>
                                        <span class="badge border <?= $badgeClass ?> text-capitalize px-2 py-1">
                                            <?= htmlspecialchars($rec['status'], ENT_QUOTES) ?>
                                        </span>
                                    </td>
                                    <td class="small text-muted"><?= htmlspecialchars($rec['recorded_by_name'], ENT_QUOTES) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require_once ROOT . '/includes/layout_footer.php'; ?>
