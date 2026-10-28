<?php
/**
 * CASPA School OS – pages/shared/attendance_overview.php
 *
 * School-wide attendance for one day, per class + subject.
 * Not opened directly: pages/principal/attendance.php and pages/admin/attendance.php
 * check the role and then include this file.
 * Data: api/attendance/overview.php (principal + admin only).
 */

declare(strict_types=1);

if (!defined('ROOT')) {
    define('ROOT', dirname(__DIR__, 2));
}

require_once ROOT . '/includes/auth.php';
require_once ROOT . '/includes/db.php';

session_boot();
require_role(['principal', 'admin']);

$user      = current_user();
$pageRole  = $user['role'];
$pageTitle = $pageTitle ?? 'Attendance Overview';
$today     = date('Y-m-d');

require_once ROOT . '/includes/layout_header.php';
?>

<div class="caspa-page-header d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="mb-1">Attendance Overview</h1>
        <p class="caspa-breadcrumb mb-0">See which classes have marked attendance, and how many students were present.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <label for="overviewDate" class="form-label mb-0 fw-semibold text-navy">Date</label>
        <input type="date" id="overviewDate" class="form-control" value="<?= $today ?>" max="<?= $today ?>" style="width: auto;">
    </div>
</div>

<div id="overviewState" class="p-4 text-center d-none"></div>

<div id="overviewContent" class="d-none">
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="caspa-stat-card h-100">
                <div class="caspa-stat-icon" id="rateIcon" style="background-color: rgba(23, 107, 135, 0.12); color: var(--color-teal);"><i class="bi bi-percent"></i></div>
                <div><div class="caspa-stat-value" id="totRate">&mdash;</div><div class="caspa-stat-label">Overall Attendance</div></div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="caspa-stat-card h-100">
                <div class="caspa-stat-icon" style="background-color: rgba(22, 163, 74, 0.12); color: var(--color-success);"><i class="bi bi-check-circle-fill"></i></div>
                <div><div class="caspa-stat-value" id="totMarked">&mdash;</div><div class="caspa-stat-label">Periods Marked</div></div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="caspa-stat-card h-100">
                <div class="caspa-stat-icon" style="background-color: rgba(217, 119, 6, 0.12); color: var(--color-warning);"><i class="bi bi-hourglass-split"></i></div>
                <div><div class="caspa-stat-value" id="totPending">&mdash;</div><div class="caspa-stat-label">Periods Not Marked</div></div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="caspa-stat-card h-100">
                <div class="caspa-stat-icon" style="background-color: rgba(220, 38, 38, 0.12); color: var(--color-danger);"><i class="bi bi-person-x-fill"></i></div>
                <div><div class="caspa-stat-value" id="totAbsent">&mdash;</div><div class="caspa-stat-label">Absent Records</div></div>
            </div>
        </div>
    </div>

    <div class="caspa-card p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col">Class</th>
                        <th scope="col">Subject</th>
                        <th scope="col">Teacher</th>
                        <th scope="col">Status</th>
                        <th scope="col" class="text-end">Present</th>
                        <th scope="col" class="text-end">Absent</th>
                        <th scope="col" class="text-end">Late</th>
                        <th scope="col" class="text-end">Excused</th>
                        <th scope="col" class="text-end">Rate</th>
                    </tr>
                </thead>
                <tbody id="overviewBody"></tbody>
            </table>
        </div>
    </div>
    <p class="text-muted small mt-2 mb-0">Attendance is recorded per class and subject. Rate = present &divide; all records for that period.</p>
</div>

<?php ob_start(); // printed by layout_footer.php after jQuery loads ?>
$(function () {
    'use strict';

    const $state = $('#overviewState');
    const $content = $('#overviewContent');

    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function render(data) {
        const t = data.totals;
        $('#totRate').text(t.rate === null ? '—' : t.rate + '%');
        $('#totMarked').text(t.marked + ' of ' + (t.marked + t.pending));
        $('#totPending').text(t.pending);
        $('#totAbsent').text(t.absent);

        const $body = $('#overviewBody').empty();
        if (!data.periods.length) {
            $body.html('<tr><td colspan="9" class="text-center text-muted py-4">No class and subject assignments exist yet.</td></tr>');
            return;
        }
        data.periods.forEach(p => {
            const status = p.marked
                ? '<span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-check-circle-fill me-1"></i>Marked</span>'
                : '<span class="badge bg-warning-subtle text-warning border border-warning-subtle"><i class="bi bi-exclamation-circle-fill me-1"></i>Not marked</span>';
            const num = v => p.marked ? v : '<span class="text-muted">&ndash;</span>';
            $body.append(`
                <tr>
                    <td class="fw-semibold text-navy">${escapeHtml(p.class_name)} - ${escapeHtml(p.class_section)}</td>
                    <td>${escapeHtml(p.subject_name)}</td>
                    <td>${escapeHtml(p.teacher_name)}</td>
                    <td>${status}</td>
                    <td class="text-end">${num(p.present)}</td>
                    <td class="text-end">${num(p.absent)}</td>
                    <td class="text-end">${num(p.late)}</td>
                    <td class="text-end">${num(p.excused)}</td>
                    <td class="text-end fw-semibold">${p.rate === null ? '<span class="text-muted">&ndash;</span>' : p.rate + '%'}</td>
                </tr>`);
        });
    }

    function load() {
        const date = $('#overviewDate').val();
        $content.addClass('d-none');
        $state.removeClass('d-none');
        ui.showLoading($state);

        api.get('/api/attendance/overview.php', { date: date })
            .then(data => {
                ui.clearState($state);
                $state.addClass('d-none');
                $content.removeClass('d-none');
                render(data);
            })
            .catch(err => {
                ui.clearState($state);
                $state.removeClass('d-none');
                ui.showError($state, 'Could not load attendance: ' + (err.message || 'Unknown error'));
            });
    }

    $('#overviewDate').on('change', function () {
        if (!this.value) return;
        load();
    });

    load();
});
<?php $inlineJs = ob_get_clean(); ?>

<?php require_once ROOT . '/includes/layout_footer.php'; ?>
