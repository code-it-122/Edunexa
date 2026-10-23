<?php
/**
 * CASPA School OS – pages/student/dashboard.php
 *
 * Student Dashboard:
 * Real data view with:
 * - Profile card (name, admission number, class & section, class teacher, email, etc.)
 * - Own attendance percentage & statistics
 * - Recent attendance history (last 10 days)
 * - Upcoming assignments with due dates
 * - School notices/announcements for students
 */

declare(strict_types=1);

define('ROOT', dirname(__DIR__, 2));

require_once ROOT . '/includes/auth.php';
require_once ROOT . '/includes/db.php';

session_boot();
require_login();
require_role('student');

$user       = current_user();
$pageTitle  = 'Dashboard';
$pageRole   = 'student';
$activeMenu = 'Dashboard';

require_once ROOT . '/includes/layout_header.php';
?>

<!-- Header -->
<div class="caspa-page-header d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="mb-1">Student Dashboard</h1>
        <p class="caspa-breadcrumb mb-0">
            Welcome back, <span class="fw-semibold text-navy"><?= htmlspecialchars($user['name'], ENT_QUOTES) ?></span> &mdash; here's your learning and attendance summary.
        </p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="<?= APP_URL ?>/pages/student/assignments.php" class="btn btn-caspa-primary shadow-sm d-flex align-items-center gap-2 px-3">
            <i class="bi bi-journal-text"></i>
            <span>My Assignments</span>
        </a>
        <button type="button" class="btn btn-outline-secondary bg-white shadow-sm d-flex align-items-center gap-2 px-3" id="btnRefreshDashboard">
            <i class="bi bi-arrow-clockwise"></i>
            <span>Refresh</span>
        </button>
    </div>
</div>

<!-- State Container for Loading / Error -->
<div id="dashboardStateContainer" class="p-5 text-center d-none"></div>

<!-- Dashboard Content Container -->
<div id="dashboardContent">
    <!-- 1. Profile Card -->
    <div class="caspa-card mb-4 p-4">
        <div class="row align-items-center g-3">
            <div class="col-12 col-md-auto text-center text-md-start">
                <div class="rounded-circle d-inline-flex align-items-center justify-content-center text-white fw-bold shadow-sm"
                     id="studentAvatar"
                     style="width: 72px; height: 72px; font-size: 1.6rem; background: linear-gradient(135deg, var(--color-navy) 0%, var(--color-teal) 100%);">
                    <?= htmlspecialchars(mb_strtoupper(mb_substr($user['name'], 0, 2)), ENT_QUOTES) ?>
                </div>
            </div>
            <div class="col-12 col-md">
                <div class="d-flex flex-wrap align-items-center gap-2 mb-1 justify-content-center justify-content-md-start">
                    <h3 class="mb-0 fw-bold text-navy" id="profileName"><?= htmlspecialchars($user['name'], ENT_QUOTES) ?></h3>
                    <span class="badge px-3 py-1 bg-primary-subtle text-primary border border-primary-subtle rounded-pill fs-7" id="profileClassBadge">
                        Loading class…
                    </span>
                    <span class="badge px-2 py-1 bg-light text-muted border rounded-pill fs-7" id="profileAdmBadge">
                        Adm: —
                    </span>
                </div>
                <div class="text-muted small d-flex flex-wrap align-items-center gap-3 justify-content-center justify-content-md-start">
                    <span><i class="bi bi-person-badge me-1"></i>Class Teacher: <strong class="text-navy" id="profileTeacher">—</strong></span>
                    <span><i class="bi bi-envelope me-1"></i><?= htmlspecialchars($user['email'], ENT_QUOTES) ?></span>
                    <span id="profileGenderSpan"><i class="bi bi-gender-ambiguous me-1"></i><span id="profileGender">—</span></span>
                    <span id="profileDobSpan"><i class="bi bi-calendar3 me-1"></i><span id="profileDob">—</span></span>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. KPI Cards Row -->
    <div class="row g-3 mb-4">
        <!-- Attendance Percentage -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="caspa-stat-card h-100">
                <div class="caspa-stat-icon" id="kpiAttIcon" style="background-color: rgba(22, 163, 74, 0.12); color: var(--color-success);">
                    <i class="bi bi-pie-chart-fill"></i>
                </div>
                <div>
                    <div class="caspa-stat-value" id="kpiAttPercentage">—%</div>
                    <div class="caspa-stat-label">Attendance Rate</div>
                </div>
            </div>
        </div>

        <!-- Total Present Days -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="caspa-stat-card h-100">
                <div class="caspa-stat-icon" style="background-color: rgba(23, 107, 135, 0.12); color: var(--color-teal);">
                    <i class="bi bi-calendar-check-fill"></i>
                </div>
                <div>
                    <div class="caspa-stat-value" id="kpiPresentDays">—</div>
                    <div class="caspa-stat-label">Days Present (inc. Late)</div>
                </div>
            </div>
        </div>

        <!-- Upcoming Assignments -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="caspa-stat-card h-100">
                <div class="caspa-stat-icon" style="background-color: rgba(91, 63, 163, 0.12); color: var(--color-purple);">
                    <i class="bi bi-journal-bookmark-fill"></i>
                </div>
                <div>
                    <div class="caspa-stat-value" id="kpiAssignmentsCount">—</div>
                    <div class="caspa-stat-label">Upcoming Assignments</div>
                </div>
            </div>
        </div>

        <!-- Notices Count -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="caspa-stat-card h-100">
                <div class="caspa-stat-icon" style="background-color: rgba(18, 59, 93, 0.12); color: var(--color-navy);">
                    <i class="bi bi-megaphone-fill"></i>
                </div>
                <div>
                    <div class="caspa-stat-value" id="kpiNoticesCount">—</div>
                    <div class="caspa-stat-label">School Notices</div>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. Main Two-Column Layout -->
    <div class="row g-4">
        <!-- Left Column: Attendance History & Upcoming Assignments -->
        <div class="col-12 col-lg-7">
            <!-- Recent Attendance (Last 10 Days) -->
            <div class="caspa-card mb-4">
                <div class="caspa-card-header d-flex justify-content-between align-items-center p-3 border-bottom">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-clock-history text-teal fs-5"></i>
                        <h5 class="mb-0 fw-semibold text-navy">Recent Attendance (Last 10 Records)</h5>
                    </div>
                    <div id="attBreakdownBadges" class="d-none d-sm-flex align-items-center gap-1">
                        <!-- Populated by JS -->
                    </div>
                </div>
                <div class="p-0">
                    <div class="table-responsive">
                        <table class="caspa-table mb-0" id="attendanceTable">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Subject</th>
                                    <th>Status</th>
                                    <th>Recorded By</th>
                                </tr>
                            </thead>
                            <tbody id="attendanceTableBody">
                                <!-- Populated by JS -->
                            </tbody>
                        </table>
                    </div>
                    <div id="attendanceEmptyState" class="p-4 text-center d-none text-muted">
                        <i class="bi bi-calendar-x fs-2 d-block mb-2"></i>
                        <span>No attendance recorded yet.</span>
                    </div>
                </div>
            </div>

            <!-- Upcoming Assignments -->
            <div class="caspa-card">
                <div class="caspa-card-header d-flex justify-content-between align-items-center p-3 border-bottom">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-journal-text text-teal fs-5"></i>
                        <h5 class="mb-0 fw-semibold text-navy">Upcoming Assignments</h5>
                    </div>
                    <a href="<?= APP_URL ?>/pages/student/assignments.php" class="btn btn-sm btn-outline-primary">
                        View All
                    </a>
                </div>
                <div class="p-3" id="assignmentsContainer">
                    <!-- Populated by JS -->
                </div>
            </div>
        </div>

        <!-- Right Column: Notices & Quick Links -->
        <div class="col-12 col-lg-5">
            <!-- Notices / Announcements -->
            <div class="caspa-card mb-4">
                <div class="caspa-card-header d-flex justify-content-between align-items-center p-3 border-bottom">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-bell-fill text-warning fs-5"></i>
                        <h5 class="mb-0 fw-semibold text-navy">School Notices</h5>
                    </div>
                    <span class="badge bg-light text-navy border" id="noticesBadge">0 Notices</span>
                </div>
                <div class="p-3" id="noticesContainer">
                    <!-- Populated by JS -->
                </div>
            </div>

            <!-- Quick Info / Help Card -->
            <div class="caspa-card p-4" style="background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="rounded-circle p-2 d-flex align-items-center justify-content-center text-white"
                         style="background-color: var(--color-purple); width: 42px; height: 42px;">
                        <i class="bi bi-stars fs-5"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 fw-bold text-navy">Need Assistance?</h6>
                        <small class="text-muted">CASPA School OS Student Portal</small>
                    </div>
                </div>
                <p class="text-muted small mb-3">
                    Check your assignments regularly and submit your work before due dates. For attendance discrepancies, contact your class teacher.
                </p>
                <div class="d-grid gap-2">
                    <a href="<?= APP_URL ?>/pages/student/assignments.php" class="btn btn-outline-secondary btn-sm text-start d-flex justify-content-between align-items-center py-2">
                        <span><i class="bi bi-journal-check me-2 text-teal"></i>Review Homework Tasks</span>
                        <i class="bi bi-chevron-right text-muted"></i>
                    </a>
                    <button type="button" class="btn btn-outline-secondary btn-sm text-start d-flex justify-content-between align-items-center py-2" id="btnCaspaAiStudent">
                        <span><i class="bi bi-robot me-2 text-purple" style="color: var(--color-purple);"></i>Ask CASPA AI</span>
                        <i class="bi bi-chevron-right text-muted"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Notice Detail Modal -->
<div class="modal fade" id="noticeDetailModal" tabindex="-1" aria-labelledby="noticeDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold text-navy" id="noticeDetailModalLabel">Notice</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex align-items-center gap-2 text-muted small mb-3">
                    <span id="modalNoticeDate"><i class="bi bi-calendar3 me-1"></i>—</span>
                    <span>&bull;</span>
                    <span id="modalNoticeAuthor"><i class="bi bi-person me-1"></i>—</span>
                </div>
                <div id="modalNoticeBody" class="text-secondary" style="white-space: pre-wrap; line-height: 1.6;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
$(function () {
    'use strict';

    const $state = $('#dashboardStateContainer');
    const $content = $('#dashboardContent');

    function formatDate(dateStr) {
        if (!dateStr) return '—';
        const d = new Date(dateStr + 'T00:00:00');
        if (isNaN(d.getTime())) return dateStr;
        return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    }

    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return $('<div>').text(String(str)).html();
    }

    function loadStudentDashboard() {
        $state.removeClass('d-none');
        $content.addClass('opacity-50');
        ui.showLoading($state);

        api.get('/api/dashboard_student.php')
            .then(data => {
                ui.clearState($state);
                $state.addClass('d-none');
                $content.removeClass('opacity-50');

                renderProfile(data.student);
                renderAttendanceSummary(data.attendance_summary);
                renderRecentAttendance(data.recent_attendance);
                renderAssignments(data.upcoming_assignments);
                renderNotices(data.notices);
            })
            .catch(err => {
                ui.clearState($state);
                $state.removeClass('d-none');
                $content.addClass('d-none');
                ui.showError($state, 'Failed to load dashboard: ' + (err.message || 'Unknown error'));
            });
    }

    function renderProfile(student) {
        if (!student) return;

        $('#profileName').text(student.name || 'Student');
        const initials = (student.name || 'ST').split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
        $('#studentAvatar').text(initials);

        if (student.class_name && student.class_name !== '—') {
            $('#profileClassBadge').text(student.class_name + (student.class_section ? ' - ' + student.class_section : ''));
        } else {
            $('#profileClassBadge').text('Unassigned Class');
        }

        $('#profileAdmBadge').text('Adm: ' + (student.admission_number || '—'));
        $('#profileTeacher').text(student.class_teacher_name || 'Unassigned');

        if (student.gender) {
            $('#profileGender').text(student.gender.charAt(0).toUpperCase() + student.gender.slice(1));
            $('#profileGenderSpan').removeClass('d-none');
        } else {
            $('#profileGenderSpan').addClass('d-none');
        }

        if (student.date_of_birth) {
            $('#profileDob').text('DOB: ' + formatDate(student.date_of_birth));
            $('#profileDobSpan').removeClass('d-none');
        } else {
            $('#profileDobSpan').addClass('d-none');
        }
    }

    function renderAttendanceSummary(summary) {
        if (!summary) return;

        const pct = summary.percentage !== undefined ? summary.percentage : 0;
        const total = summary.total_days || 0;
        const present = summary.present_days || 0;
        const late = summary.late_days || 0;
        const absent = summary.absent_days || 0;
        const excused = summary.excused_days || 0;

        $('#kpiAttPercentage').text(pct + '%');
        $('#kpiPresentDays').text((present + late) + ' / ' + total);

        const $attIcon = $('#kpiAttIcon');
        if (pct >= 85) {
            $attIcon.css('background-color', 'rgba(22, 163, 74, 0.12)').css('color', 'var(--color-success)');
            $('#kpiAttPercentage').css('color', 'var(--color-success)');
        } else if (pct >= 75) {
            $attIcon.css('background-color', 'rgba(217, 119, 6, 0.12)').css('color', 'var(--color-warning)');
            $('#kpiAttPercentage').css('color', 'var(--color-warning)');
        } else {
            $attIcon.css('background-color', 'rgba(220, 38, 38, 0.12)').css('color', 'var(--color-danger)');
            $('#kpiAttPercentage').css('color', 'var(--color-danger)');
        }

        const badgesHtml = `
            <span class="badge bg-success-subtle text-success border border-success-subtle">Present: ${present}</span>
            <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Late: ${late}</span>
            <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Absent: ${absent}</span>
            ${excused > 0 ? `<span class="badge bg-info-subtle text-info border border-info-subtle">Excused: ${excused}</span>` : ''}
        `;
        $('#attBreakdownBadges').html(badgesHtml);
    }

    function renderRecentAttendance(records) {
        const $tbody = $('#attendanceTableBody');
        const $empty = $('#attendanceEmptyState');
        const $table = $('#attendanceTable');

        $tbody.empty();

        if (!records || records.length === 0) {
            $table.addClass('d-none');
            $empty.removeClass('d-none');
            return;
        }

        $table.removeClass('d-none');
        $empty.addClass('d-none');

        const statusBadges = {
            present: '<span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-check-circle me-1"></i>Present</span>',
            late:    '<span class="badge bg-warning-subtle text-warning border border-warning-subtle"><i class="bi bi-clock me-1"></i>Late</span>',
            absent:  '<span class="badge bg-danger-subtle text-danger border border-danger-subtle"><i class="bi bi-x-circle me-1"></i>Absent</span>',
            excused: '<span class="badge bg-info-subtle text-info border border-info-subtle"><i class="bi bi-info-circle me-1"></i>Excused</span>'
        };

        records.forEach(r => {
            const statusHtml = statusBadges[r.status] || `<span class="badge bg-secondary-subtle text-secondary">${escapeHtml(r.status)}</span>`;
            const subjectText = r.subject_name ? `${escapeHtml(r.subject_name)} ${r.subject_code ? `<span class="text-muted small">(${escapeHtml(r.subject_code)})</span>` : ''}` : '—';
            const teacherText = r.recorded_by_name ? escapeHtml(r.recorded_by_name) : '—';

            const row = `
                <tr>
                    <td class="fw-medium text-navy">${formatDate(r.date)}</td>
                    <td>${subjectText}</td>
                    <td>${statusHtml}</td>
                    <td class="text-muted">${teacherText}</td>
                </tr>
            `;
            $tbody.append(row);
        });
    }

    function renderAssignments(assignments) {
        const $container = $('#assignmentsContainer');
        $container.empty();

        const count = assignments ? assignments.length : 0;
        $('#kpiAssignmentsCount').text(count);

        if (count === 0) {
            $container.html(`
                <div class="p-4 text-center text-muted">
                    <i class="bi bi-check2-circle fs-2 d-block mb-2 text-success"></i>
                    <p class="mb-1 fw-medium">All caught up!</p>
                    <small>No upcoming assignments due for your class.</small>
                </div>
            `);
            return;
        }

        assignments.forEach(asgn => {
            const daysLeft = parseInt(asgn.days_until_due, 10);
            let dueBadge = '';
            if (daysLeft < 0) {
                dueBadge = `<span class="badge bg-danger-subtle text-danger border border-danger-subtle">Overdue</span>`;
            } else if (daysLeft === 0) {
                dueBadge = `<span class="badge bg-warning-subtle text-warning border border-warning-subtle fw-semibold">Due Today</span>`;
            } else if (daysLeft === 1) {
                dueBadge = `<span class="badge bg-warning-subtle text-warning border border-warning-subtle">Due Tomorrow</span>`;
            } else {
                dueBadge = `<span class="badge bg-light text-navy border">Due in ${daysLeft} days</span>`;
            }

            const itemHtml = `
                <div class="border rounded p-3 mb-3 bg-white shadow-sm transition-base">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                        <div>
                            <span class="badge bg-teal-subtle text-teal border border-teal-subtle px-2 py-1 mb-1">
                                ${escapeHtml(asgn.subject_name || 'Subject')}
                            </span>
                            <h6 class="mb-0 fw-bold text-navy">${escapeHtml(asgn.title)}</h6>
                        </div>
                        <div class="text-end">
                            ${dueBadge}
                            <div class="text-muted small mt-1">${formatDate(asgn.due_date)}</div>
                        </div>
                    </div>
                    ${asgn.description ? `<p class="text-secondary small mb-2 text-truncate" style="max-height: 40px;">${escapeHtml(asgn.description)}</p>` : ''}
                    <div class="d-flex justify-content-between align-items-center text-muted small pt-2 border-top">
                        <span><i class="bi bi-award me-1"></i>Max marks: <strong>${escapeHtml(asgn.max_marks || '100')}</strong></span>
                        ${asgn.teacher_name ? `<span><i class="bi bi-person me-1"></i>${escapeHtml(asgn.teacher_name)}</span>` : ''}
                    </div>
                </div>
            `;
            $container.append(itemHtml);
        });
    }

    let noticesList = [];
    function renderNotices(notices) {
        const $container = $('#noticesContainer');
        $container.empty();

        noticesList = notices || [];
        const count = noticesList.length;
        $('#kpiNoticesCount').text(count);
        $('#noticesBadge').text(`${count} Notice${count === 1 ? '' : 's'}`);

        if (count === 0) {
            $container.html(`
                <div class="p-4 text-center text-muted">
                    <i class="bi bi-bell-slash fs-2 d-block mb-2"></i>
                    <span>No school notices at this time.</span>
                </div>
            `);
            return;
        }

        noticesList.forEach((n, idx) => {
            const itemHtml = `
                <div class="border-bottom pb-3 mb-3 last-no-border cursor-pointer notice-card-item" data-index="${idx}">
                    <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                        <h6 class="mb-0 fw-semibold text-navy notice-title">${escapeHtml(n.title)}</h6>
                        <span class="text-muted small text-nowrap">${formatDate(n.published_at ? n.published_at.substring(0, 10) : '')}</span>
                    </div>
                    <p class="text-secondary small mb-2 text-truncate">${escapeHtml(n.body)}</p>
                    <div class="d-flex justify-content-between align-items-center small">
                        <span class="text-muted"><i class="bi bi-person me-1"></i>${escapeHtml(n.author_name || 'Administration')}</span>
                        <a href="javascript:void(0)" class="text-teal fw-medium text-decoration-none">Read Notice &rarr;</a>
                    </div>
                </div>
            `;
            $container.append(itemHtml);
        });

        $('.notice-card-item').on('click', function () {
            const idx = $(this).data('index');
            const notice = noticesList[idx];
            if (!notice) return;

            $('#noticeDetailModalLabel').text(notice.title);
            $('#modalNoticeDate').html('<i class="bi bi-calendar3 me-1"></i>' + formatDate(notice.published_at ? notice.published_at.substring(0, 10) : ''));
            $('#modalNoticeAuthor').html('<i class="bi bi-person me-1"></i>' + escapeHtml(notice.author_name || 'Administration'));
            $('#modalNoticeBody').text(notice.body);

            const modal = new bootstrap.Modal(document.getElementById('noticeDetailModal'));
            modal.show();
        });
    }

    $('#btnRefreshDashboard').on('click', function () {
        loadStudentDashboard();
    });

    $('#btnCaspaAiStudent').on('click', function () {
        const offcanvasEl = document.getElementById('caspaAiDrawer');
        if (offcanvasEl) {
            const bsOffcanvas = bootstrap.Offcanvas.getOrCreateInstance(offcanvasEl);
            bsOffcanvas.show();
        }
    });

    loadStudentDashboard();
});
</script>

<?php require_once ROOT . '/includes/layout_footer.php'; ?>
