<?php
/**
 * CASPA School OS – pages/shared/_family_dashboard.php
 *
 * Dashboard for the STUDENT and the PARENT role (same layout, different scope).
 * Not a page on its own: pages/student/dashboard.php and pages/parent/dashboard.php
 * check the role, set $pageRole, then include this file.
 *
 * Data: api/dashboard_family.php. The API decides which children are visible
 * (student → self, parent → linked children only); this page just displays them.
 */

declare(strict_types=1);

if (!defined('ROOT')) {
    define('ROOT', dirname(__DIR__, 2));
}
if (!isset($pageRole) || !in_array($pageRole, ['student', 'parent'], true)) {
    http_response_code(403);
    exit('Forbidden');
}

$user       = current_user();
$pageTitle  = 'Dashboard';
$isParent   = ($pageRole === 'parent');
$heading    = $isParent ? 'Parent Dashboard' : 'Student Dashboard';
$noun       = $isParent ? "your child's" : 'your';

$attendUrl  = APP_URL . "/pages/{$pageRole}/attendance.php";
$assignUrl  = APP_URL . "/pages/{$pageRole}/assignments.php";
$noticeUrl  = $isParent ? APP_URL . '/pages/parent/notices.php' : '';
$profileUrl = APP_URL . '/pages/shared/student_profile.php';

require_once ROOT . '/includes/layout_header.php';
?>

<div class="caspa-page-header d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="mb-1"><?= htmlspecialchars($heading, ENT_QUOTES) ?></h1>
        <p class="caspa-breadcrumb mb-0">
            Welcome back, <span class="fw-semibold text-navy"><?= htmlspecialchars($user['name'], ENT_QUOTES) ?></span>
            &mdash; here is <?= $noun ?> school summary.
        </p>
    </div>
    <div id="childSwitcher" class="d-none d-flex align-items-center gap-2">
        <label for="childSelect" class="form-label mb-0 fw-semibold text-navy">Child</label>
        <select id="childSelect" class="form-select" style="width: auto;"></select>
    </div>
</div>

<div id="dashboardStateContainer" class="p-5 text-center d-none"></div>

<div id="dashboardContent" class="d-none">

    <!-- Child summary bar -->
    <div class="caspa-card p-3 mb-4 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div class="d-flex align-items-center gap-3">
            <div class="caspa-stat-icon" style="background-color: rgba(23, 107, 135, 0.12); color: var(--color-teal);"><i class="bi bi-person-fill"></i></div>
            <div>
                <div class="fw-bold text-navy fs-5" id="childName">&mdash;</div>
                <div class="text-muted small"><span id="childClass"></span> &middot; Admission no. <span id="childAdm"></span></div>
            </div>
        </div>
        <a href="#" id="profileLink" class="btn btn-sm btn-outline-secondary"><i class="bi bi-person-lines-fill me-1"></i>View profile</a>
    </div>

    <!-- KPI cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <a href="<?= $attendUrl ?>" class="text-decoration-none kpi-link">
                <div class="caspa-stat-card h-100">
                    <div class="caspa-stat-icon" id="rateIcon" style="background-color: rgba(23, 107, 135, 0.12); color: var(--color-teal);"><i class="bi bi-percent"></i></div>
                    <div><div class="caspa-stat-value" id="kpiRate">&mdash;</div><div class="caspa-stat-label">Attendance</div></div>
                </div>
            </a>
        </div>
        <div class="col-6 col-xl-3">
            <div class="caspa-stat-card h-100">
                <div class="caspa-stat-icon" style="background-color: rgba(22, 163, 74, 0.12); color: var(--color-success);"><i class="bi bi-check-circle-fill"></i></div>
                <div><div class="caspa-stat-value" id="kpiPresent">&mdash;</div><div class="caspa-stat-label">Present</div></div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="caspa-stat-card h-100">
                <div class="caspa-stat-icon" style="background-color: rgba(220, 38, 38, 0.12); color: var(--color-danger);"><i class="bi bi-x-circle-fill"></i></div>
                <div><div class="caspa-stat-value" id="kpiAbsent">&mdash;</div><div class="caspa-stat-label">Absent</div></div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <a href="<?= $assignUrl ?>" class="text-decoration-none kpi-link">
                <div class="caspa-stat-card h-100">
                    <div class="caspa-stat-icon" style="background-color: rgba(91, 63, 163, 0.12); color: var(--color-purple);"><i class="bi bi-journal-bookmark-fill"></i></div>
                    <div><div class="caspa-stat-value" id="kpiUpcoming">&mdash;</div><div class="caspa-stat-label">Upcoming Assignments</div></div>
                </div>
            </a>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- Recent attendance -->
        <div class="col-12 col-xl-6">
            <div class="caspa-card h-100 p-0 overflow-hidden d-flex flex-column">
                <div class="p-3 border-bottom bg-white d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-calendar-check text-navy fs-5"></i>
                        <h2 class="h6 mb-0 text-navy fw-bold">Recent Attendance</h2>
                    </div>
                    <a href="<?= $attendUrl ?>" id="attendanceLink" class="btn btn-sm btn-outline-secondary">See all</a>
                </div>
                <div id="recentAttendance" class="p-3 flex-grow-1"></div>
            </div>
        </div>

        <!-- Upcoming assignments -->
        <div class="col-12 col-xl-6">
            <div class="caspa-card h-100 p-0 overflow-hidden d-flex flex-column">
                <div class="p-3 border-bottom bg-white d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-journal-text text-navy fs-5"></i>
                        <h2 class="h6 mb-0 text-navy fw-bold">Upcoming Assignments</h2>
                    </div>
                    <a href="<?= $assignUrl ?>" class="btn btn-sm btn-outline-secondary">View all</a>
                </div>
                <div id="upcomingAssignments" class="p-3 flex-grow-1"></div>
            </div>
        </div>
    </div>

    <!-- Announcements -->
    <div class="caspa-card p-0 overflow-hidden">
        <div class="p-3 border-bottom bg-white d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-megaphone text-navy fs-5"></i>
                <h2 class="h6 mb-0 text-navy fw-bold"><?= $isParent ? 'Notices' : 'Announcements' ?></h2>
            </div>
            <?php if ($noticeUrl): ?><a href="<?= $noticeUrl ?>" class="btn btn-sm btn-outline-secondary">View all</a><?php endif; ?>
        </div>
        <div id="announcementsList" class="p-3"></div>
    </div>
</div>

<div id="noChildren" class="caspa-card p-5 text-center d-none">
    <i class="bi bi-person-exclamation text-muted" style="font-size: 2.5rem;"></i>
    <p class="text-muted mt-3 mb-0">
        <?= $isParent
            ? 'No student is linked to your account yet. Please ask the school office to link your child.'
            : 'Your account is not linked to a student record yet. Please contact the school office.' ?>
    </p>
</div>

<?php ob_start(); // printed by layout_footer.php after jQuery loads ?>
$(function () {
    'use strict';

    const IS_PARENT = <?= $isParent ? 'true' : 'false' ?>;
    const PROFILE_URL = <?= json_encode($profileUrl) ?>;
    const ATTEND_URL = <?= json_encode($attendUrl) ?>;
    const $state = $('#dashboardStateContainer');
    const $content = $('#dashboardContent');

    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }
    function fmtDate(d) {
        if (!d) return '';
        const p = d.split('-');
        return new Date(p[0], p[1] - 1, p[2]).toLocaleDateString('en-US', { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' });
    }
    function fmtDateTime(dt) {
        if (!dt) return '';
        return new Date(String(dt).replace(' ', 'T')).toLocaleString('en-US',
            { day: 'numeric', month: 'short', year: 'numeric', hour: 'numeric', minute: '2-digit' });
    }
    function statusBadge(s) {
        const map = {
            present: ['bg-success-subtle text-success border-success-subtle', 'Present'],
            absent:  ['bg-danger-subtle text-danger border-danger-subtle', 'Absent'],
            late:    ['bg-warning-subtle text-warning border-warning-subtle', 'Late'],
            excused: ['bg-info-subtle text-info border-info-subtle', 'Excused']
        };
        const m = map[s] || ['bg-light text-secondary border', escapeHtml(s)];
        return `<span class="badge border ${m[0]}">${m[1]}</span>`;
    }
    function currentChild() {
        const v = new URLSearchParams(window.location.search).get('student_id');
        return v ? parseInt(v, 10) : null;
    }

    function render(d) {
        const s = d.selected, a = d.attendance, asg = d.assignments;

        // child switcher (parents with more than one child)
        const $sel = $('#childSelect').empty();
        if (IS_PARENT && d.children.length > 1) {
            d.children.forEach(c => $sel.append(
                $('<option>').val(c.id).text(c.name + (c.class_name ? ' (' + c.class_name + ')' : '')).prop('selected', c.id === s.id)));
            $('#childSwitcher').removeClass('d-none');
        } else {
            $('#childSwitcher').addClass('d-none');
        }

        $('#childName').text(s.name);
        $('#childClass').text(s.class_name || 'No class');
        $('#childAdm').text(s.admission_number);
        $('#profileLink').attr('href', PROFILE_URL + '?id=' + s.id);
        $('.kpi-link, #attendanceLink').each(function () {
            const base = $(this).attr('href').split('?')[0];
            $(this).attr('href', IS_PARENT ? base + '?student_id=' + s.id : base);
        });

        // KPIs
        if (a.rate === null) {
            $('#kpiRate').addClass('fs-5').text('No records');
        } else {
            $('#kpiRate').removeClass('fs-5').text(a.rate + '%');
            const good = a.rate >= 90;
            $('#rateIcon').css('background-color', good ? 'rgba(22, 163, 74, 0.12)' : 'rgba(217, 119, 6, 0.12)')
                          .css('color', good ? 'var(--color-success)' : 'var(--color-warning)');
        }
        $('#kpiPresent').text(a.present);
        $('#kpiAbsent').text(a.absent);
        $('#kpiUpcoming').text(asg.upcoming.length);

        // recent attendance
        const $ra = $('#recentAttendance').empty();
        if (!a.recent.length) {
            ui.showEmpty($ra, 'No attendance has been recorded yet.');
        } else {
            const rows = a.recent.map(r => `
                <tr><td>${escapeHtml(fmtDate(r.date))}</td><td>${escapeHtml(r.subject_name)}</td><td class="text-end">${statusBadge(r.status)}</td></tr>`).join('');
            $ra.html(`<div class="table-responsive"><table class="table table-sm align-middle mb-0"><tbody>${rows}</tbody></table></div>`);
        }

        // upcoming assignments
        const $ua = $('#upcomingAssignments').empty();
        if (!asg.upcoming.length) {
            ui.showEmpty($ua, 'No assignments are due soon.' + (asg.overdue_count ? ' (' + asg.overdue_count + ' past due)' : ''));
        } else {
            const items = asg.upcoming.map(x => {
                const due = x.due_date === asg.today
                    ? '<span class="badge bg-primary">Due today</span>'
                    : '<span class="badge bg-light text-secondary border">Due ' + escapeHtml(fmtDate(x.due_date)) + '</span>';
                return `<div class="list-group-item px-0 py-2 d-flex justify-content-between align-items-start gap-2">
                    <div><div class="fw-semibold text-navy">${escapeHtml(x.title)}</div><div class="text-muted small">${escapeHtml(x.subject_name)} &middot; ${Number(x.max_marks)} pts</div></div>
                    ${due}</div>`;
            }).join('');
            $ua.html('<div class="list-group list-group-flush">' + items + '</div>');
        }

        // announcements
        const $an = $('#announcementsList').empty();
        if (!d.announcements.length) {
            ui.showEmpty($an, 'No announcements right now.');
        } else {
            const wrap = $('<div class="d-flex flex-column gap-3"></div>');
            d.announcements.forEach(n => wrap.append(`
                <div class="p-3 border rounded-3 bg-light">
                    <h3 class="h6 mb-1 text-navy fw-bold">${escapeHtml(n.title)}</h3>
                    <div class="text-muted mb-2" style="font-size: 0.75rem;"><i class="bi bi-person me-1"></i>${escapeHtml(n.author_name)} &bull; <i class="bi bi-clock me-1"></i>${escapeHtml(fmtDateTime(n.published_at))}</div>
                    <p class="text-secondary small mb-0" style="white-space: pre-wrap; line-height: 1.5;">${escapeHtml(n.body)}</p>
                </div>`));
            $an.append(wrap);
        }
    }

    function load(studentId) {
        $state.removeClass('d-none');
        $content.addClass('d-none');
        $('#noChildren').addClass('d-none');
        ui.showLoading($state);

        api.get('/api/dashboard_family.php', studentId ? { student_id: studentId } : {})
            .then(d => {
                ui.clearState($state);
                $state.addClass('d-none');
                if (!d.selected) {
                    $('#noChildren').removeClass('d-none');
                    return;
                }
                $content.removeClass('d-none');
                render(d);
            })
            .catch(err => {
                ui.clearState($state);
                $state.removeClass('d-none');
                ui.showError($state, 'Failed to load dashboard: ' + (err.message || 'Unknown error'));
            });
    }

    $('#childSelect').on('change', function () {
        const id = parseInt(this.value, 10);
        history.replaceState(null, '', '?student_id=' + id);
        load(id);
    });

    load(currentChild());
});
<?php $inlineJs = ob_get_clean(); ?>

<?php require_once ROOT . '/includes/layout_footer.php'; ?>
