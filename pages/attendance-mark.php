<?php
// pages/attendance-mark.php - Attendance Marking Screen
require_once __DIR__ . '/../config/session.php';
requireAuth();

$user = getCurrentUser();
$roleKey = strtolower($user['active_role']['key'] ?? 'student');
$roleTitle = htmlspecialchars($user['active_role']['title'] ?? 'User');

// Authorization: Only Teacher, Principal, Admin may access
$allowedRoles = ['teacher', 'principal', 'admin'];
if (!in_array($roleKey, $allowedRoles, true)) {
    http_response_code(403);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>403 Access Denied - EduNEXA</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        <style>
            body { font-family: 'Inter', sans-serif; background-color: #F8FAFC; color: #25313C; }
        </style>
    </head>
    <body class="d-flex align-items-center justify-content-center vh-100">
        <div class="card border-0 shadow-sm p-4 text-center rounded-3" style="max-width: 460px;">
            <div class="mb-3 text-danger"><i class="bi bi-shield-lock-fill fs-1"></i></div>
            <h1 class="h5 fw-bold text-dark">403 - Access Restricted</h1>
            <p class="text-muted small mb-4">
                Students, Parents, and unauthorized staff cannot access attendance records. Only assigned Teachers and School Administrators have marking privileges.
            </p>
            <a href="index.php?page=dashboard" class="btn btn-outline-secondary">Return to Command Center</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

$csrfToken = getCSRFToken();
$todayDate = date('Y-m-d');
$inPagesDir = (strpos($_SERVER['SCRIPT_NAME'] ?? '', '/pages/') !== false);
$basePath = $inPagesDir ? '../' : './';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <base href="<?= $basePath ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken) ?>">
    <title>Attendance Marking - EduNEXA School OS</title>
    
    <!-- CSS Dependencies -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/caspa-theme.css">

    <style>
        /* Specific Styles for Attendance Marking matching Figma & Tokens */
        .att-header-panel {
            background-color: #FFFFFF;
            border: 1px solid var(--caspa-border);
            border-radius: 12px;
            padding: 20px 24px;
        }

        .summary-chip-card {
            background-color: #FFFFFF;
            border: 1px solid var(--caspa-border);
            border-radius: 10px;
            padding: 12px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.2s ease;
        }
        .summary-chip-card:hover {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }

        .chip-total { border-left: 4px solid var(--caspa-navy) !important; }
        .chip-present { border-left: 4px solid var(--caspa-success) !important; }
        .chip-absent { border-left: 4px solid var(--caspa-danger) !important; }
        .chip-late { border-left: 4px solid var(--caspa-warning) !important; }
        .chip-leave { border-left: 4px solid var(--caspa-sky) !important; }

        /* Student Avatar Circles */
        .student-avatar-circle {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background-color: #EEF2F6;
            color: var(--caspa-navy);
            font-weight: 700;
            font-size: 13px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            border: 1px solid var(--caspa-border);
        }

        /* Segmented Radio Control */
        .status-segmented-control {
            display: inline-flex;
            background-color: #F1F5F9;
            padding: 4px;
            border-radius: 8px;
            border: 1px solid #E2E8F0;
            gap: 2px;
        }

        .btn-segment {
            border: none;
            background: transparent;
            font-size: 12px;
            font-weight: 600;
            padding: 6px 14px;
            border-radius: 6px;
            color: #64748B;
            cursor: pointer;
            transition: all 0.15s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            outline: none;
        }

        .btn-segment:focus-visible {
            box-shadow: 0 0 0 2px var(--caspa-teal);
            z-index: 2;
        }

        /* Segment Active States */
        .btn-seg-present.active {
            background-color: #FFFFFF;
            color: var(--caspa-success) !important;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            font-weight: 700;
        }
        .btn-seg-absent.active {
            background-color: #FFFFFF;
            color: var(--caspa-danger) !important;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            font-weight: 700;
        }
        .btn-seg-late.active {
            background-color: #FFFFFF;
            color: var(--caspa-warning) !important;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            font-weight: 700;
        }
        .btn-seg-leave.active {
            background-color: #FFFFFF;
            color: var(--caspa-sky) !important;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            font-weight: 700;
        }

        /* Mobile Status Pills */
        .status-pill-present { background-color: #DCFCE7; color: var(--caspa-success); }
        .status-pill-absent { background-color: #FEE2E2; color: var(--caspa-danger); }
        .status-pill-late { background-color: #FEF3C7; color: var(--caspa-warning); }
        .status-pill-leave { background-color: #E0F2FE; color: var(--caspa-sky); }

        /* Skeleton Animation */
        .skeleton-box {
            background: linear-gradient(90deg, #E2E8F0 25%, #EDF2F7 50%, #E2E8F0 75%);
            background-size: 200% 100%;
            animation: skeleton-loading 1.5s infinite;
            border-radius: 4px;
        }
        @keyframes skeleton-loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        /* Sticky Bottom Action Bar */
        .attendance-sticky-bar {
            position: fixed;
            bottom: -90px;
            left: 0;
            right: 0;
            background-color: #FFFFFF;
            border-top: 1px solid var(--caspa-border-dark);
            padding: 14px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 -4px 15px rgba(0, 0, 0, 0.08);
            z-index: 1040;
            transition: bottom 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .attendance-sticky-bar.show {
            bottom: 0;
        }

        /* Mobile Adjustments (<768px) */
        @media (max-width: 767.98px) {
            .attendance-sticky-bar {
                padding: 12px 16px;
                bottom: -120px;
            }
            .attendance-sticky-bar.show {
                bottom: 60px; /* Above bottom nav */
            }
            .status-segmented-control {
                width: 100%;
                display: grid;
                grid-template-columns: 1fr 1fr 1fr 1fr;
            }
            .btn-segment {
                min-height: 42px;
                padding: 8px 4px;
                font-size: 11px;
            }
        }
    </style>
</head>
<body>

<!-- Header (Reused) -->
<?php include __DIR__ . '/../includes/header.php'; ?>

<!-- Body Layout -->
<div class="d-flex">
    <!-- Left Collapsible Sidebar (Reused) -->
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <!-- Main Content Area -->
    <main class="flex-grow-1 p-3 p-md-4 pb-5 mb-5 mb-lg-0 overflow-y-auto" style="min-height: calc(100vh - var(--topbar-height)); background-color: var(--caspa-surface);">
        <div class="container-fluid" style="max-width: 1300px;">
            
            <!-- Breadcrumbs -->
            <nav aria-label="breadcrumb" class="mb-2">
                <ol class="breadcrumb mb-0" style="font-size: 12px; font-weight: 600;">
                    <li class="breadcrumb-item"><a href="index.php?page=dashboard" class="text-decoration-none" style="color: var(--caspa-teal);">Academics</a></li>
                    <li class="breadcrumb-item active text-muted" aria-current="page">Attendance Marking</li>
                </ol>
            </nav>

            <!-- Page Header Panel with Dropdowns & Date Picker -->
            <div class="att-header-panel mb-4 shadow-sm">
                <div class="row align-items-center g-3">
                    <div class="col-12 col-lg-5">
                        <div class="d-flex align-items-center gap-2">
                            <h1 class="h4 fw-bold text-dark mb-0">Attendance Marking</h1>
                            <span class="badge bg-light text-muted border px-2 py-1 small fw-semibold">Daily Register</span>
                        </div>
                        <p class="text-muted small mb-0 mt-1">
                            Record student presence in under 30 seconds. Authorized for <strong style="color: var(--caspa-teal);"><?= $roleTitle ?></strong>.
                        </p>
                    </div>

                    <!-- Filter Controls (Date, Class, Section) -->
                    <div class="col-12 col-lg-7">
                        <div class="row g-2 justify-content-lg-end">
                            <!-- Date Picker (Default Today, Cannot Pick Future Date) -->
                            <div class="col-12 col-sm-4">
                                <label for="attDate" class="form-label small fw-bold text-muted mb-1">Attendance Date</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light"><i class="bi bi-calendar3"></i></span>
                                    <input type="date" id="attDate" class="form-control form-control-sm" value="<?= $todayDate ?>" max="<?= $todayDate ?>" required>
                                </div>
                            </div>

                            <!-- Class Dropdown -->
                            <div class="col-6 col-sm-4">
                                <label for="classSelect" class="form-label small fw-bold text-muted mb-1">Class / Grade</label>
                                <select id="classSelect" class="form-select form-select-sm" aria-label="Select Class">
                                    <option value="">Loading classes...</option>
                                </select>
                            </div>

                            <!-- Section Dropdown -->
                            <div class="col-6 col-sm-4">
                                <label for="sectionSelect" class="form-label small fw-bold text-muted mb-1">Section</label>
                                <select id="sectionSelect" class="form-select form-select-sm" aria-label="Select Section">
                                    <option value="">--</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary Chips (Live Updating with aria-live) -->
            <div class="mb-4">
                <!-- Screen reader announcement live region -->
                <div id="summaryAriaLive" class="visually-hidden" aria-live="polite" aria-atomic="true"></div>

                <div class="row g-2 g-md-3">
                    <!-- Total -->
                    <div class="col-6 col-sm-4 col-md">
                        <div class="summary-chip-card chip-total">
                            <div>
                                <div class="text-muted small fw-semibold">Total</div>
                                <div class="fs-4 fw-bold text-dark lh-1 mt-1" id="chipTotal">0</div>
                            </div>
                            <i class="bi bi-people text-muted fs-4"></i>
                        </div>
                    </div>

                    <!-- Present -->
                    <div class="col-6 col-sm-4 col-md">
                        <div class="summary-chip-card chip-present">
                            <div>
                                <div class="text-muted small fw-semibold">Present</div>
                                <div class="fs-4 fw-bold text-success lh-1 mt-1" id="chipPresent">0</div>
                            </div>
                            <i class="bi bi-check-circle-fill text-success fs-4"></i>
                        </div>
                    </div>

                    <!-- Absent -->
                    <div class="col-6 col-sm-4 col-md">
                        <div class="summary-chip-card chip-absent">
                            <div>
                                <div class="text-muted small fw-semibold">Absent</div>
                                <div class="fs-4 fw-bold text-danger lh-1 mt-1" id="chipAbsent">0</div>
                            </div>
                            <i class="bi bi-x-circle-fill text-danger fs-4"></i>
                        </div>
                    </div>

                    <!-- Late -->
                    <div class="col-6 col-sm-6 col-md">
                        <div class="summary-chip-card chip-late">
                            <div>
                                <div class="text-muted small fw-semibold">Late</div>
                                <div class="fs-4 fw-bold text-warning lh-1 mt-1" id="chipLate">0</div>
                            </div>
                            <i class="bi bi-clock-history text-warning fs-4"></i>
                        </div>
                    </div>

                    <!-- On Leave -->
                    <div class="col-12 col-sm-6 col-md">
                        <div class="summary-chip-card chip-leave">
                            <div>
                                <div class="text-muted small fw-semibold">On Leave</div>
                                <div class="fs-4 fw-bold text-primary lh-1 mt-1" id="chipLeave">0</div>
                            </div>
                            <i class="bi bi-calendar2-minus-fill text-primary fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- "Already Marked" Alert Banner (Shown when record already exists for date) -->
            <div id="alreadyMarkedBanner" class="alert alert-info border-0 shadow-sm d-flex align-items-center gap-2 py-2 px-3 mb-3 d-none rounded-3" style="background-color: var(--caspa-teal-light); color: var(--caspa-teal);">
                <i class="bi bi-info-circle-fill fs-5"></i>
                <div class="small fw-semibold">
                    Attendance already marked for this date. Editing will update it.
                </div>
            </div>

            <!-- Toolbar (Search, Quick Actions) -->
            <div class="card border-0 shadow-sm p-3 mb-3 rounded-3 bg-white">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <!-- Search Input -->
                    <div class="position-relative flex-grow-1" style="max-width: 380px;">
                        <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                        <input type="text" id="studentSearchInput" class="form-control form-control-sm ps-5 rounded-pill" placeholder="Search by name or roll number...">
                    </div>

                    <!-- Quick Batch Actions -->
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" id="btnMarkAllPresent" class="btn btn-sm btn-outline-success d-flex align-items-center gap-1 fw-semibold">
                            <i class="bi bi-check2-all"></i>
                            <span>Mark all present</span>
                        </button>

                        <button type="button" id="btnResetMarks" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1 fw-semibold">
                            <i class="bi bi-arrow-counterclockwise"></i>
                            <span>Reset</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Student Roster Main View -->
            <div id="attendanceTableContainer">
                <!-- Desktop Table View (>= 768px) -->
                <div class="card border-0 shadow-sm rounded-3 bg-white overflow-hidden d-none d-md-block">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light border-bottom text-muted small text-uppercase fw-bold" style="letter-spacing: 0.5px;">
                                <tr>
                                    <th class="py-3 text-center" style="width: 70px;">Roll</th>
                                    <th class="py-3">Student Name</th>
                                    <th class="py-3">Attendance Status</th>
                                    <th class="py-3" style="min-width: 200px;">Remark / Reason</th>
                                </tr>
                            </thead>
                            <tbody id="studentRosterTbody">
                                <!-- Populated dynamically by assets/js/pages/attendance-mark.js -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Mobile Card Stack (< 768px) -->
                <div class="d-md-none" id="mobileCardsList">
                    <!-- Populated dynamically by assets/js/pages/attendance-mark.js -->
                </div>

                <!-- No Search Match -->
                <div id="noSearchMatch" class="card border-0 shadow-sm p-4 text-center rounded-3 bg-white d-none mt-2">
                    <div class="text-muted"><i class="bi bi-search fs-2"></i></div>
                    <div class="fw-semibold text-dark mt-2">No matching students found</div>
                    <div class="small text-muted">Try refining your search keyword.</div>
                </div>
            </div>

            <!-- Empty State -->
            <div id="emptyState" class="card border-0 shadow-sm p-5 text-center rounded-3 bg-white d-none">
                <div class="text-muted"><i class="bi bi-people fs-1"></i></div>
                <h2 class="h6 fw-bold text-dark mt-3">No Students Enrolled</h2>
                <p class="small text-muted mb-0" id="emptyMsgText">No students found in this class section.</p>
            </div>

            <!-- Error State with Retry Button -->
            <div id="errorState" class="card border-0 shadow-sm p-5 text-center rounded-3 bg-white d-none">
                <div class="text-danger"><i class="bi bi-exclamation-triangle-fill fs-1"></i></div>
                <h2 class="h6 fw-bold text-dark mt-3">Failed to Load Roster</h2>
                <p class="small text-muted mb-3" id="errorMsgText">An unexpected error occurred while communicating with the server.</p>
                <div>
                    <button type="button" id="btnRetryLoad" class="btn btn-sm btn-caspa-teal px-4">
                        <i class="bi bi-arrow-clockwise me-1"></i> Retry
                    </button>
                </div>
            </div>

        </div>
    </main>
</div>

<!-- Sticky Bottom Save Action Bar -->
<div id="stickyBottomBar" class="attendance-sticky-bar">
    <div class="d-flex align-items-center gap-2">
        <i class="bi bi-exclamation-circle text-warning fs-5"></i>
        <div>
            <div class="fw-bold small text-dark">Unsaved Attendance Changes</div>
            <div class="text-muted d-none d-sm-block" style="font-size: 11px;">Remember to save your attendance markings before navigating away.</div>
        </div>
    </div>
    <div class="d-flex align-items-center gap-2">
        <button type="button" id="btnDiscardBottom" class="btn btn-sm btn-outline-secondary px-3 py-2 fw-semibold">
            Discard changes
        </button>
        <button type="button" id="btnSaveAttendance" class="btn btn-sm btn-caspa-teal px-4 py-2 fw-semibold shadow-sm">
            <i class="bi bi-check2-circle me-1"></i> Save attendance
        </button>
    </div>
</div>

<!-- Toast Notifications -->
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1090;">
    <!-- Success Toast -->
    <div id="toastSuccess" class="toast align-items-center text-white bg-success border-0 shadow" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body d-flex align-items-center gap-2">
                <i class="bi bi-check-circle-fill fs-5"></i>
                <span id="toastSuccessMsg">Attendance saved successfully!</span>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>

    <!-- Error Toast -->
    <div id="toastError" class="toast align-items-center text-white bg-danger border-0 shadow" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body d-flex align-items-center gap-2">
                <i class="bi bi-exclamation-octagon-fill fs-5"></i>
                <span id="toastErrorMsg">Error saving attendance.</span>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<!-- Mobile Bottom Navigation (Reused) -->
<?php include __DIR__ . '/../includes/bottom-nav.php'; ?>

<!-- Modals & Drawers (Reused) -->
<?php include __DIR__ . '/../includes/caspa-drawer.php'; ?>
<?php include __DIR__ . '/../includes/command-palette.php'; ?>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/auth.js"></script>
<script src="assets/js/app.js"></script>
<script src="assets/js/pages/attendance-mark.js"></script>
</body>
</html>
