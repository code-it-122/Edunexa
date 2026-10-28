<?php
/**
 * CASPA School OS – pages/teacher/dashboard.php
 *
 * Teacher Dashboard:
 * Real data view with KPI cards, assigned classes with today's attendance status,
 * "Mark attendance" quick action, upcoming assignments list, and announcements list.
 */

declare(strict_types=1);

define('ROOT', dirname(__DIR__, 2));

require_once ROOT . '/includes/auth.php';
require_once ROOT . '/includes/db.php';

session_boot();
require_login();
require_role('teacher');

$user       = current_user();
$pageTitle  = 'Dashboard';
$pageRole   = 'teacher';
$activeMenu = 'Dashboard';

require_once ROOT . '/includes/layout_header.php';
?>

<!-- Header & Quick Actions -->
<div class="caspa-page-header d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="mb-1">Teacher Dashboard</h1>
        <p class="caspa-breadcrumb mb-0">
            Welcome back, <span class="fw-semibold text-navy"><?= htmlspecialchars($user['name'], ENT_QUOTES) ?></span> &mdash; here is your daily schedule and teaching overview.
        </p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="<?= APP_URL ?>/pages/teacher/attendance.php" class="btn btn-caspa-primary shadow-sm d-flex align-items-center gap-2 px-3">
            <i class="bi bi-calendar-check-fill"></i>
            <span>Mark Attendance</span>
        </a>
        <a href="<?= APP_URL ?>/pages/teacher/assignments.php" class="btn btn-outline-secondary bg-white shadow-sm d-flex align-items-center gap-2 px-3">
            <i class="bi bi-journal-plus"></i>
            <span>New Assignment</span>
        </a>
    </div>
</div>

<!-- Global Loading / Error State -->
<div id="dashboardStateContainer" class="p-5 text-center d-none"></div>

<div id="dashboardContent">
    <!-- 1. KPI Cards Row -->
    <div class="row g-3 mb-4">
        <!-- Assigned Classes -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="caspa-stat-card h-100">
                <div class="caspa-stat-icon" style="background-color: rgba(23, 107, 135, 0.12); color: var(--color-teal);">
                    <i class="bi bi-building"></i>
                </div>
                <div>
                    <div class="caspa-stat-value" id="kpiClassesCount">&mdash;</div>
                    <div class="caspa-stat-label">Assigned Classes</div>
                </div>
            </div>
        </div>

        <!-- Total Students -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="caspa-stat-card h-100">
                <div class="caspa-stat-icon" style="background-color: rgba(18, 59, 93, 0.12); color: var(--color-navy);">
                    <i class="bi bi-people-fill"></i>
                </div>
                <div>
                    <div class="caspa-stat-value" id="kpiStudentsCount">&mdash;</div>
                    <div class="caspa-stat-label">Total Students Taught</div>
                </div>
            </div>
        </div>

        <!-- Today's Attendance Status -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="caspa-stat-card h-100">
                <div class="caspa-stat-icon" id="kpiAttendanceIcon" style="background-color: rgba(217, 119, 6, 0.12); color: var(--color-warning);">
                    <i class="bi bi-calendar2-check-fill"></i>
                </div>
                <div>
                    <div class="caspa-stat-value fs-5" id="kpiAttendanceStatus">&mdash;</div>
                    <div class="caspa-stat-label">Today's Attendance</div>
                </div>
            </div>
        </div>

        <!-- Active Assignments -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="caspa-stat-card h-100">
                <div class="caspa-stat-icon" style="background-color: rgba(91, 63, 163, 0.12); color: var(--color-purple);">
                    <i class="bi bi-journal-bookmark-fill"></i>
                </div>
                <div>
                    <div class="caspa-stat-value" id="kpiAssignmentsCount">&mdash;</div>
                    <div class="caspa-stat-label">Active Assignments</div>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Assigned Classes with Today's Attendance Status -->
    <div class="caspa-card mb-4 p-0 overflow-hidden">
        <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-white">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-easel text-teal fs-5" style="color: var(--color-teal);"></i>
                <h2 class="h6 mb-0 text-navy fw-bold">My Classes &amp; Daily Attendance</h2>
            </div>
            <a href="<?= APP_URL ?>/pages/teacher/attendance.php" class="btn btn-sm btn-caspa-primary d-flex align-items-center gap-1">
                <i class="bi bi-check2-square"></i>
                <span>Mark Attendance</span>
            </a>
        </div>

        <div id="classesListContainer" class="p-3">
            <!-- Populated via AJAX -->
        </div>
    </div>

    <!-- 3. Bottom Grid: Upcoming Assignments & Latest Announcements -->
    <div class="row g-4">
        <!-- Upcoming Assignments -->
        <div class="col-12 col-lg-7">
            <div class="caspa-card h-100 p-0 overflow-hidden d-flex flex-column">
                <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-white">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-journal-text text-navy fs-5"></i>
                        <h2 class="h6 mb-0 text-navy fw-bold">Upcoming Assignments</h2>
                    </div>
                    <a href="<?= APP_URL ?>/pages/teacher/assignments.php" class="text-teal small fw-semibold d-flex align-items-center gap-1">
                        <span>View All</span>
                        <i class="bi bi-arrow-right"></i>
                    </a>
                </div>

                <div id="assignmentsListContainer" class="p-3 flex-grow-1">
                    <!-- Populated via AJAX -->
                </div>
            </div>
        </div>

        <!-- Latest Announcements -->
        <div class="col-12 col-lg-5">
            <div class="caspa-card h-100 p-0 overflow-hidden d-flex flex-column">
                <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-white">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-megaphone text-navy fs-5"></i>
                        <h2 class="h6 mb-0 text-navy fw-bold">School Announcements</h2>
                    </div>
                    <span class="badge bg-secondary-subtle text-secondary small">Teachers</span>
                </div>

                <div id="announcementsListContainer" class="p-3 flex-grow-1">
                    <!-- Populated via AJAX -->
                </div>
            </div>
        </div>
    </div>
</div>

<?php ob_start(); // page script is printed by layout_footer.php AFTER jQuery loads ?>
$(function () {
    'use strict';

    const $state = $('#dashboardStateContainer');
    const $content = $('#dashboardContent');

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function formatDate(dateStr) {
        if (!dateStr) return '';
        const parts = dateStr.split('-');
        if (parts.length === 3) {
            const date = new Date(parts[0], parts[1] - 1, parts[2]);
            return date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        }
        return dateStr;
    }

    function formatDateTime(dtStr) {
        if (!dtStr) return '';
        const date = new Date(dtStr.replace(' ', 'T'));
        if (isNaN(date.getTime())) return dtStr;
        return date.toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric'
        });
    }

    function loadDashboard() {
        $state.removeClass('d-none');
        $content.addClass('opacity-50');
        ui.showLoading($state);

        api.get('/api/dashboard_teacher.php')
            .then(data => {
                ui.clearState($state);
                $state.addClass('d-none');
                $content.removeClass('opacity-50');

                renderKpis(data.kpis);
                renderClasses(data.assigned_classes);
                renderAssignments(data.upcoming_assignments);
                renderAnnouncements(data.announcements);
            })
            .catch(err => {
                ui.clearState($state);
                $state.removeClass('d-none');
                $content.addClass('d-none');
                ui.showError($state, 'Failed to load teacher dashboard: ' + (err.message || 'Unknown error'));
            });
    }

    function renderKpis(kpis) {
        if (!kpis) return;

        $('#kpiClassesCount').text(kpis.assigned_classes_count || 0);
        $('#kpiStudentsCount').text(kpis.total_students_count || 0);
        $('#kpiAssignmentsCount').text(kpis.active_assignments_count || 0);

        const marked = kpis.classes_marked_today || 0;
        const total = kpis.assigned_classes_count || 0;

        if (total > 0 && marked >= total) {
            $('#kpiAttendanceStatus').html('<span class="text-success"><i class="bi bi-check-circle-fill me-1"></i>All Marked</span>');
            $('#kpiAttendanceIcon')
                .css('background-color', 'rgba(22, 163, 74, 0.12)')
                .css('color', 'var(--color-success)');
        } else if (marked > 0) {
            $('#kpiAttendanceStatus').html(`<span class="text-warning">${marked} of ${total} Marked</span>`);
            $('#kpiAttendanceIcon')
                .css('background-color', 'rgba(217, 119, 6, 0.12)')
                .css('color', 'var(--color-warning)');
        } else {
            $('#kpiAttendanceStatus').html('<span class="text-warning"><i class="bi bi-clock-history me-1"></i>Pending</span>');
            $('#kpiAttendanceIcon')
                .css('background-color', 'rgba(217, 119, 6, 0.12)')
                .css('color', 'var(--color-warning)');
        }
    }

    function renderClasses(classes) {
        const $container = $('#classesListContainer');
        $container.empty();

        if (!classes || classes.length === 0) {
            ui.showEmpty($container, 'No classes assigned to your profile yet. Please contact the administrator.');
            return;
        }

        const $row = $('<div class="row g-3"></div>');

        classes.forEach(c => {
            const isMarked = (c.today_attendance_status === 'marked');
            let attendanceBadge = '';
            let actionBtn = '';

            const attendanceUrl = c.first_class_subject_id 
                ? '<?= APP_URL ?>/pages/teacher/attendance.php?class_subject_id=' + c.first_class_subject_id
                : '<?= APP_URL ?>/pages/teacher/attendance.php';

            if (isMarked) {
                attendanceBadge = `
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                        <i class="bi bi-check-circle-fill me-1"></i>Marked Today (${c.marked_students_count}/${c.student_count})
                    </span>`;
                actionBtn = `
                    <a href="${attendanceUrl}" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1">
                        <i class="bi bi-pencil-square"></i>
                        <span>Review</span>
                    </a>`;
            } else {
                attendanceBadge = `
                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1">
                        <i class="bi bi-exclamation-circle-fill me-1"></i>Not Marked Today
                    </span>`;
                actionBtn = `
                    <a href="${attendanceUrl}" class="btn btn-sm btn-caspa-primary d-flex align-items-center gap-1 shadow-sm">
                        <i class="bi bi-check2-square"></i>
                        <span>Mark Attendance</span>
                    </a>`;
            }

            const teacherRoleBadge = c.is_class_teacher
                ? `<span class="badge bg-navy text-white px-2 py-1" style="background-color: var(--color-navy); font-size: 0.7rem;">Class Teacher</span>`
                : '';

            const subjectTags = (c.subjects || []).map(s => `
                <span class="badge bg-light text-secondary border fw-normal">
                    ${escapeHtml(s.subject_name)}
                </span>
            `).join(' ');

            const card = `
                <div class="col-12 col-md-6 col-xl-6">
                    <div class="caspa-card h-100 p-3 d-flex flex-column justify-content-between border" style="background-color: #fafbfc;">
                        <div>
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                <div>
                                    <h3 class="h6 mb-1 text-navy fw-bold">
                                        ${escapeHtml(c.display_name)}
                                    </h3>
                                    <div class="text-muted small">
                                        <i class="bi bi-people me-1"></i>${Number(c.student_count)} Students enrolled
                                    </div>
                                </div>
                                ${teacherRoleBadge}
                            </div>

                            <div class="mb-3">
                                <div class="text-muted small mb-1" style="font-size: 0.75rem;">SUBJECTS TAUGHT:</div>
                                <div class="d-flex flex-wrap gap-1">
                                    ${subjectTags || '<span class="text-muted small">None</span>'}
                                </div>
                            </div>
                        </div>

                        <div class="pt-2 border-top d-flex justify-content-between align-items-center mt-2">
                            ${attendanceBadge}
                            ${actionBtn}
                        </div>
                    </div>
                </div>
            `;
            $row.append(card);
        });

        $container.append($row);
    }

    function renderAssignments(assignments) {
        const $container = $('#assignmentsListContainer');
        $container.empty();

        if (!assignments || assignments.length === 0) {
            $container.html(`
                <div class="p-4 text-center">
                    <i class="bi bi-journal-check text-muted" style="font-size: 2.2rem;"></i>
                    <p class="text-muted small mt-2 mb-3">No upcoming assignments on record.</p>
                    <a href="<?= APP_URL ?>/pages/teacher/assignments.php" class="btn btn-sm btn-caspa-primary">
                        <i class="bi bi-plus-circle me-1"></i>Create Assignment
                    </a>
                </div>
            `);
            return;
        }

        const $list = $('<div class="list-group list-group-flush"></div>');

        assignments.forEach(a => {
            let dueBadge = '';
            if (Number(a.is_overdue) === 1) {
                dueBadge = `
                    <span class="badge" style="background-color: var(--color-warning); color: #fff;">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>Overdue (${formatDate(a.due_date)})
                    </span>`;
            } else if (Number(a.is_due_today) === 1) {
                dueBadge = `
                    <span class="badge bg-primary text-white">
                        <i class="bi bi-alarm me-1"></i>Due Today
                    </span>`;
            } else {
                dueBadge = `
                    <span class="badge bg-light text-secondary border">
                        <i class="bi bi-calendar3 me-1"></i>Due ${formatDate(a.due_date)}
                    </span>`;
            }

            const itemHtml = `
                <div class="list-group-item px-0 py-2 border-bottom">
                    <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                        <div>
                            <span class="fw-semibold text-navy">${escapeHtml(a.title)}</span>
                            <div class="text-muted small">
                                <span class="badge bg-teal-subtle text-teal me-1" style="background-color: rgba(23, 107, 135, 0.1); color: var(--color-teal);">
                                    ${escapeHtml(a.subject_name)}
                                </span>
                                <span>${escapeHtml(a.class_name)} - ${escapeHtml(a.class_section)}</span>
                            </div>
                        </div>
                        <div class="text-end">
                            ${dueBadge}
                            <div class="text-muted small mt-1" style="font-size: 0.75rem;">
                                ${Number(a.max_marks)} pts
                            </div>
                        </div>
                    </div>
                </div>
            `;
            $list.append(itemHtml);
        });

        $container.append($list);
    }

    function renderAnnouncements(announcements) {
        const $container = $('#announcementsListContainer');
        $container.empty();

        if (!announcements || announcements.length === 0) {
            ui.showEmpty($container, 'No announcements published for teachers at this time.');
            return;
        }

        const $list = $('<div class="d-flex flex-column gap-3"></div>');

        announcements.forEach(ann => {
            const dateStr = formatDateTime(ann.published_at);
            const card = `
                <div class="p-3 border rounded-3 bg-light">
                    <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                        <h4 class="h6 mb-0 text-navy fw-bold">${escapeHtml(ann.title)}</h4>
                    </div>
                    <div class="text-muted mb-2" style="font-size: 0.75rem;">
                        <i class="bi bi-person me-1"></i>${escapeHtml(ann.author_name)} &bull; <i class="bi bi-clock me-1"></i>${escapeHtml(dateStr)}
                    </div>
                    <p class="text-secondary small mb-0" style="white-space: pre-wrap; line-height: 1.5;">
                        ${escapeHtml(ann.body)}
                    </p>
                </div>
            `;
            $list.append(card);
        });

        $container.append($list);
    }

    // Initial load
    loadDashboard();
});
<?php $inlineJs = ob_get_clean(); ?>

<?php require_once ROOT . '/includes/layout_footer.php'; ?>
