<?php
/**
 * CASPA School OS – pages/shared/_overview_dashboard.php
 *
 * School-wide overview used by BOTH the Principal and the Admin dashboards.
 * Not a page on its own: pages/principal/dashboard.php and pages/admin/dashboard.php
 * check the role, set $pageRole, then include this file.
 *
 * Data comes from api/dashboard_principal.php (principal + admin only).
 */

declare(strict_types=1);

if (!defined('ROOT')) {
    define('ROOT', dirname(__DIR__, 2));
}
if (!isset($pageRole) || !in_array($pageRole, ['principal', 'admin'], true)) {
    http_response_code(403);
    exit('Forbidden');
}

$user       = current_user();
$pageTitle  = 'Dashboard';
$isPrincipal = ($pageRole === 'principal');
$heading    = $isPrincipal ? 'Principal Dashboard' : 'Admin Dashboard';
$subtitle   = $isPrincipal
    ? "Here's your school at a glance."
    : "Here's what is happening across the school today.";

$annUrl      = APP_URL . "/pages/{$pageRole}/announcements.php";
$studentsUrl = APP_URL . "/pages/{$pageRole}/students.php";
$attendUrl   = APP_URL . "/pages/{$pageRole}/attendance.php";
$classesUrl  = APP_URL . '/pages/admin/classes.php';

$extraJs = ['https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.js'];

require_once ROOT . '/includes/layout_header.php';
?>

<div class="caspa-page-header d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="mb-1"><?= htmlspecialchars($heading, ENT_QUOTES) ?></h1>
        <p class="caspa-breadcrumb mb-0">
            Welcome back, <span class="fw-semibold text-navy"><?= htmlspecialchars($user['name'], ENT_QUOTES) ?></span> &mdash; <?= htmlspecialchars($subtitle, ENT_QUOTES) ?>
        </p>
    </div>
    <div class="d-flex flex-wrap align-items-center gap-2">
        <a href="<?= $annUrl ?>" class="btn btn-caspa-primary shadow-sm d-flex align-items-center gap-2 px-3">
            <i class="bi bi-megaphone-fill"></i><span>New Announcement</span>
        </a>
        <a href="<?= $attendUrl ?>" class="btn btn-outline-secondary bg-white shadow-sm d-flex align-items-center gap-2 px-3">
            <i class="bi bi-calendar-check"></i><span>Attendance Overview</span>
        </a>
    </div>
</div>

<div id="dashboardStateContainer" class="p-5 text-center d-none"></div>

<div id="dashboardContent">
    <!-- KPI cards -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-4">
            <a href="<?= $studentsUrl ?>" class="text-decoration-none">
                <div class="caspa-stat-card h-100">
                    <div class="caspa-stat-icon" style="background-color: rgba(18, 59, 93, 0.12); color: var(--color-navy);"><i class="bi bi-mortarboard-fill"></i></div>
                    <div><div class="caspa-stat-value" id="kpiStudents">&mdash;</div><div class="caspa-stat-label">Students</div></div>
                </div>
            </a>
        </div>
        <div class="col-12 col-sm-6 col-xl-4">
            <div class="caspa-stat-card h-100">
                <div class="caspa-stat-icon" style="background-color: rgba(23, 107, 135, 0.12); color: var(--color-teal);"><i class="bi bi-person-workspace"></i></div>
                <div><div class="caspa-stat-value" id="kpiTeachers">&mdash;</div><div class="caspa-stat-label">Teachers</div></div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-4">
            <a href="<?= $classesUrl ?>" class="text-decoration-none">
                <div class="caspa-stat-card h-100">
                    <div class="caspa-stat-icon" style="background-color: rgba(59, 130, 246, 0.12); color: var(--color-sky);"><i class="bi bi-building"></i></div>
                    <div><div class="caspa-stat-value" id="kpiClasses">&mdash;</div><div class="caspa-stat-label">Classes</div></div>
                </div>
            </a>
        </div>
        <div class="col-12 col-sm-6 col-xl-4">
            <a href="<?= $attendUrl ?>" class="text-decoration-none">
                <div class="caspa-stat-card h-100">
                    <div class="caspa-stat-icon" id="kpiRateIcon" style="background-color: rgba(217, 119, 6, 0.12); color: var(--color-warning);"><i class="bi bi-calendar2-check-fill"></i></div>
                    <div><div class="caspa-stat-value" id="kpiRate">&mdash;</div><div class="caspa-stat-label" id="kpiRateLabel">Today's Attendance</div></div>
                </div>
            </a>
        </div>
        <div class="col-12 col-sm-6 col-xl-4">
            <a href="<?= $attendUrl ?>" class="text-decoration-none">
                <div class="caspa-stat-card h-100">
                    <div class="caspa-stat-icon" style="background-color: rgba(217, 119, 6, 0.12); color: var(--color-warning);"><i class="bi bi-hourglass-split"></i></div>
                    <div><div class="caspa-stat-value" id="kpiPending">&mdash;</div><div class="caspa-stat-label">Periods Not Marked Today</div></div>
                </div>
            </a>
        </div>
        <div class="col-12 col-sm-6 col-xl-4">
            <div class="caspa-stat-card h-100">
                <div class="caspa-stat-icon" style="background-color: rgba(91, 63, 163, 0.12); color: var(--color-purple);"><i class="bi bi-journal-bookmark-fill"></i></div>
                <div><div class="caspa-stat-value" id="kpiAssignments">&mdash;</div><div class="caspa-stat-label">Assignments (Last 7 Days)</div></div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- Attendance trend -->
        <div class="col-12 col-xl-7">
            <div class="caspa-card h-100 p-0 overflow-hidden d-flex flex-column">
                <div class="p-3 border-bottom bg-white d-flex align-items-center gap-2">
                    <i class="bi bi-graph-up text-navy fs-5"></i>
                    <h2 class="h6 mb-0 text-navy fw-bold">Attendance &mdash; Last 7 Days</h2>
                </div>
                <div class="p-3 flex-grow-1">
                    <div style="position: relative; height: 260px;">
                        <canvas id="trendChart" aria-label="Attendance rate for the last 7 days" role="img"></canvas>
                    </div>
                    <p class="text-muted small mb-0 mt-2" id="trendNote"></p>
                </div>
            </div>
        </div>

        <!-- Pending attendance -->
        <div class="col-12 col-xl-5">
            <div class="caspa-card h-100 p-0 overflow-hidden d-flex flex-column">
                <div class="p-3 border-bottom bg-white d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-exclamation-circle text-warning fs-5"></i>
                        <h2 class="h6 mb-0 text-navy fw-bold">Attendance Not Marked Today</h2>
                    </div>
                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle" id="pendingBadge">0</span>
                </div>
                <div id="pendingList" class="p-3 flex-grow-1" style="max-height: 320px; overflow-y: auto;"></div>
            </div>
        </div>
    </div>

    <!-- Announcements -->
    <div class="caspa-card p-0 overflow-hidden">
        <div class="p-3 border-bottom bg-white d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-megaphone text-navy fs-5"></i>
                <h2 class="h6 mb-0 text-navy fw-bold">Latest Announcements</h2>
            </div>
            <a href="<?= $annUrl ?>" class="btn btn-sm btn-outline-secondary">View all</a>
        </div>
        <div id="announcementsList" class="p-3"></div>
    </div>
</div>

<?php ob_start(); // printed by layout_footer.php after jQuery and Chart.js load ?>
$(function () {
    'use strict';

    const $state = $('#dashboardStateContainer');
    const $content = $('#dashboardContent');
    let chart = null;

    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function shortDay(dateStr) {
        const p = dateStr.split('-');
        return new Date(p[0], p[1] - 1, p[2]).toLocaleDateString('en-US', { weekday: 'short', day: 'numeric' });
    }

    function fmtDateTime(dt) {
        if (!dt) return '';
        return new Date(String(dt).replace(' ', 'T')).toLocaleString('en-US',
            { day: 'numeric', month: 'short', year: 'numeric', hour: 'numeric', minute: '2-digit' });
    }

    function renderKpis(k) {
        $('#kpiStudents').text(k.total_students);
        $('#kpiTeachers').text(k.total_teachers);
        $('#kpiClasses').text(k.total_classes);
        $('#kpiAssignments').text(k.assignments_this_week);
        $('#kpiPending').text(k.periods_pending_today + ' of ' + k.periods_total);

        if (k.today_attendance_rate === null) {
            $('#kpiRate').text('Not marked yet');
            $('#kpiRate').addClass('fs-5');
        } else {
            $('#kpiRate').removeClass('fs-5').text(k.today_attendance_rate + '%');
            $('#kpiRateLabel').text("Today's Attendance (" + k.today_records + ' records)');
            const good = k.today_attendance_rate >= 90;
            $('#kpiRateIcon')
                .css('background-color', good ? 'rgba(22, 163, 74, 0.12)' : 'rgba(217, 119, 6, 0.12)')
                .css('color', good ? 'var(--color-success)' : 'var(--color-warning)');
        }
    }

    function renderChart(trend) {
        const labels = trend.map(t => shortDay(t.date));
        const values = trend.map(t => t.rate);       // null = nothing marked that day
        const anyData = values.some(v => v !== null);

        $('#trendNote').text(anyData
            ? 'Attendance rate = present records ÷ all records. Days with no bar have no attendance marked.'
            : 'No attendance has been marked in the last 7 days.');

        if (chart) chart.destroy();
        chart = new Chart(document.getElementById('trendChart'), {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Attendance %',
                    data: values,
                    backgroundColor: '#176B87',
                    borderRadius: 6,
                    maxBarThickness: 44
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function (ctx) {
                                const t = trend[ctx.dataIndex];
                                return t.rate === null ? 'Not marked' : t.rate + '% (' + t.present + ' of ' + t.records + ' present)';
                            }
                        }
                    }
                },
                scales: {
                    y: { min: 0, max: 100, ticks: { callback: v => v + '%' }, grid: { color: '#E2E8F0' } },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    function renderPending(list) {
        const $c = $('#pendingList').empty();
        $('#pendingBadge').text(list.length);
        if (!list.length) {
            $c.html('<div class="text-center text-muted py-4"><i class="bi bi-check-circle-fill text-success fs-2"></i><p class="small mt-2 mb-0">Every class has attendance marked today.</p></div>');
            return;
        }
        const $ul = $('<div class="list-group list-group-flush"></div>');
        list.forEach(p => {
            $ul.append(`
                <div class="list-group-item px-0 py-2">
                    <div class="fw-semibold text-navy">${escapeHtml(p.class_name)} - ${escapeHtml(p.class_section)} &middot; ${escapeHtml(p.subject_name)}</div>
                    <div class="text-muted small"><i class="bi bi-person me-1"></i>${escapeHtml(p.teacher_name)}</div>
                </div>`);
        });
        $c.append($ul);
    }

    function renderAnnouncements(list) {
        const $c = $('#announcementsList').empty();
        if (!list.length) {
            ui.showEmpty($c, 'No announcements yet. Use "New Announcement" to publish one.');
            return;
        }
        const $wrap = $('<div class="d-flex flex-column gap-3"></div>');
        list.forEach(a => {
            const aud = (a.audience || []).map(r => `<span class="badge bg-light text-secondary border fw-normal text-capitalize">${escapeHtml(r)}</span>`).join(' ');
            $wrap.append(`
                <div class="p-3 border rounded-3 bg-light">
                    <h3 class="h6 mb-1 text-navy fw-bold">${escapeHtml(a.title)}</h3>
                    <div class="text-muted mb-2" style="font-size: 0.75rem;">
                        <i class="bi bi-person me-1"></i>${escapeHtml(a.author_name)} &bull;
                        <i class="bi bi-clock me-1"></i>${escapeHtml(fmtDateTime(a.published_at))}
                    </div>
                    <p class="text-secondary small mb-2" style="white-space: pre-wrap; line-height: 1.5;">${escapeHtml(a.body)}</p>
                    <div class="d-flex flex-wrap gap-1">${aud}</div>
                </div>`);
        });
        $c.append($wrap);
    }

    function load() {
        $state.removeClass('d-none');
        $content.addClass('opacity-50');
        ui.showLoading($state);

        api.get('/api/dashboard_principal.php')
            .then(data => {
                ui.clearState($state);
                $state.addClass('d-none');
                $content.removeClass('opacity-50');
                renderKpis(data.kpis);
                renderChart(data.attendance_trend);
                renderPending(data.pending_attendance);
                renderAnnouncements(data.announcements);
            })
            .catch(err => {
                ui.clearState($state);
                $state.removeClass('d-none');
                $content.addClass('d-none');
                ui.showError($state, 'Failed to load dashboard: ' + (err.message || 'Unknown error'));
            });
    }

    load();
});
<?php $inlineJs = ob_get_clean(); ?>

<?php require_once ROOT . '/includes/layout_footer.php'; ?>
