<?php
/**
 * CASPA School OS – pages/student/attendance.php
 *
 * Student Attendance View:
 * Full attendance breakdown and history records for the authenticated student.
 */

declare(strict_types=1);

define('ROOT', dirname(__DIR__, 2));

require_once ROOT . '/includes/auth.php';
require_once ROOT . '/includes/db.php';

session_boot();
require_login();
require_role('student');

$user       = current_user();
$pageTitle  = 'My Attendance';
$pageRole   = 'student';
$activeMenu = 'Attendance';

require_once ROOT . '/includes/layout_header.php';
?>

<div class="caspa-page-header d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="mb-1">My Attendance</h1>
        <p class="caspa-breadcrumb mb-0">
            View your attendance rate, present/absent history, and classroom session marks.
        </p>
    </div>
    <a href="<?= APP_URL ?>/pages/student/dashboard.php" class="btn btn-outline-secondary bg-white shadow-sm d-flex align-items-center gap-2 px-3">
        <i class="bi bi-speedometer2"></i>
        <span>Dashboard</span>
    </a>
</div>

<!-- State Container for Loading / Error -->
<div id="attStateContainer" class="p-5 text-center d-none"></div>

<div id="attContent">
    <!-- Attendance Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="caspa-stat-card h-100">
                <div class="caspa-stat-icon" id="statIconPct" style="background-color: rgba(22, 163, 74, 0.12); color: var(--color-success);">
                    <i class="bi bi-pie-chart-fill"></i>
                </div>
                <div>
                    <div class="caspa-stat-value" id="valPercentage">—%</div>
                    <div class="caspa-stat-label">Overall Rate</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="caspa-stat-card h-100">
                <div class="caspa-stat-icon" style="background-color: rgba(23, 107, 135, 0.12); color: var(--color-teal);">
                    <i class="bi bi-calendar-check-fill"></i>
                </div>
                <div>
                    <div class="caspa-stat-value" id="valPresent">—</div>
                    <div class="caspa-stat-label">Present Sessions</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="caspa-stat-card h-100">
                <div class="caspa-stat-icon" style="background-color: rgba(217, 119, 6, 0.12); color: var(--color-warning);">
                    <i class="bi bi-clock-history"></i>
                </div>
                <div>
                    <div class="caspa-stat-value" id="valLate">—</div>
                    <div class="caspa-stat-label">Late Marks</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="caspa-stat-card h-100">
                <div class="caspa-stat-icon" style="background-color: rgba(220, 38, 38, 0.12); color: var(--color-danger);">
                    <i class="bi bi-x-circle-fill"></i>
                </div>
                <div>
                    <div class="caspa-stat-value" id="valAbsent">—</div>
                    <div class="caspa-stat-label">Absences</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Attendance Table Card -->
    <div class="caspa-card p-0 overflow-hidden mb-4">
        <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-semibold text-navy">Attendance Log</h5>
            <span class="badge bg-light text-navy border" id="attCountBadge">0 Sessions</span>
        </div>
        <div class="table-responsive">
            <table class="table caspa-table align-middle mb-0" id="attRecordsTable">
                <thead>
                    <tr>
                        <th scope="col" style="width: 20%;">Date</th>
                        <th scope="col" style="width: 35%;">Subject</th>
                        <th scope="col" style="width: 20%;">Status</th>
                        <th scope="col" style="width: 25%;">Recorded By</th>
                    </tr>
                </thead>
                <tbody id="attTableBody">
                    <!-- Populated via JS -->
                </tbody>
            </table>
        </div>
        <div id="attEmpty" class="p-5 text-center text-muted d-none">
            <i class="bi bi-calendar-x fs-1 d-block mb-2"></i>
            <h5 class="fw-bold text-navy">No Attendance Records</h5>
            <p class="mb-0">No attendance entries have been recorded for your account yet.</p>
        </div>
    </div>
</div>

<script>
$(function () {
    'use strict';

    const $state = $('#attStateContainer');
    const $content = $('#attContent');
    const $tbody = $('#attTableBody');
    const $empty = $('#attEmpty');
    const $table = $('#attRecordsTable');

    function formatDate(dateStr) {
        if (!dateStr) return '—';
        const d = new Date(dateStr + 'T00:00:00');
        if (isNaN(d.getTime())) return dateStr;
        return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    }

    function escapeHtml(str) {
        if (!str) return '';
        return $('<div>').text(String(str)).html();
    }

    $state.removeClass('d-none');
    $content.addClass('opacity-50');
    ui.showLoading($state);

    api.get('/api/dashboard_student.php')
        .then(data => {
            ui.clearState($state);
            $state.addClass('d-none');
            $content.removeClass('opacity-50');

            const s = data.attendance_summary || {};
            const pct = s.percentage !== undefined ? s.percentage : 0;
            $('#valPercentage').text(pct + '%');
            $('#valPresent').text(s.present_days || 0);
            $('#valLate').text(s.late_days || 0);
            $('#valAbsent').text(s.absent_days || 0);

            if (pct >= 85) {
                $('#valPercentage').css('color', 'var(--color-success)');
            } else if (pct >= 75) {
                $('#valPercentage').css('color', 'var(--color-warning)');
            } else {
                $('#valPercentage').css('color', 'var(--color-danger)');
            }

            const recs = data.recent_attendance || [];
            $('#attCountBadge').text(recs.length + ' Recorded');

            if (recs.length === 0) {
                $table.addClass('d-none');
                $empty.removeClass('d-none');
                return;
            }

            $table.removeClass('d-none');
            $empty.addClass('d-none');
            $tbody.empty();

            const statusBadges = {
                present: '<span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-check-circle me-1"></i>Present</span>',
                late:    '<span class="badge bg-warning-subtle text-warning border border-warning-subtle"><i class="bi bi-clock me-1"></i>Late</span>',
                absent:  '<span class="badge bg-danger-subtle text-danger border border-danger-subtle"><i class="bi bi-x-circle me-1"></i>Absent</span>',
                excused: '<span class="badge bg-info-subtle text-info border border-info-subtle"><i class="bi bi-info-circle me-1"></i>Excused</span>'
            };

            recs.forEach(r => {
                const tr = `
                    <tr>
                        <td class="fw-semibold text-navy">${formatDate(r.date)}</td>
                        <td>${escapeHtml(r.subject_name || 'Subject')} ${r.subject_code ? `<span class="text-muted small">(${escapeHtml(r.subject_code)})</span>` : ''}</td>
                        <td>${statusBadges[r.status] || escapeHtml(r.status)}</td>
                        <td class="text-muted">${escapeHtml(r.recorded_by_name || 'Teacher')}</td>
                    </tr>
                `;
                $tbody.append(tr);
            });
        })
        .catch(err => {
            ui.clearState($state);
            $state.removeClass('d-none');
            $content.addClass('d-none');
            ui.showError($state, 'Failed to load attendance: ' + (err.message || 'Unknown error'));
        });
});
</script>

<?php require_once ROOT . '/includes/layout_footer.php'; ?>
