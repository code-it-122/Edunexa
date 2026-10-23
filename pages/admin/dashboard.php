<?php
/**
 * CASPA School OS – pages/admin/dashboard.php
 *
 * Admin Dashboard: same school-wide data as principal's dashboard
 * plus quick-link cards to Students and Classes management pages.
 */

declare(strict_types=1);

define('ROOT', dirname(__DIR__, 2));

require_once ROOT . '/includes/auth.php';
require_once ROOT . '/includes/db.php';

session_boot();
require_login();
require_role('admin');

$user       = current_user();
$pageTitle  = 'Dashboard';
$pageRole   = 'admin';
$activeMenu = 'Dashboard';

require_once ROOT . '/includes/layout_header.php';
?>
<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js" crossorigin="anonymous"></script>

<!-- ── Page Header ── -->
<div class="caspa-page-header d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="mb-1">Admin Dashboard</h1>
        <p class="caspa-breadcrumb mb-0">
            Welcome back, <span class="fw-semibold text-navy"><?= htmlspecialchars($user['name'], ENT_QUOTES) ?></span>
            &mdash; school overview for <span id="todayLabel" class="fw-semibold text-navy"></span>.
        </p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="<?= APP_URL ?>/pages/admin/students.php"
           class="btn btn-outline-secondary bg-white shadow-sm d-flex align-items-center gap-2 px-3">
            <i class="bi bi-mortarboard"></i><span>Students</span>
        </a>
        <a href="<?= APP_URL ?>/pages/admin/classes.php"
           class="btn btn-outline-secondary bg-white shadow-sm d-flex align-items-center gap-2 px-3">
            <i class="bi bi-building"></i><span>Classes</span>
        </a>
        <button type="button" class="btn btn-caspa-primary shadow-sm d-flex align-items-center gap-2 px-3"
                data-bs-toggle="modal" data-bs-target="#newAnnouncementModal">
            <i class="bi bi-megaphone-fill"></i><span>New Announcement</span>
        </button>
    </div>
</div>

<!-- Loading / error state -->
<div id="dashStateContainer" class="p-5 text-center d-none"></div>

<div id="dashContent">
    <!-- ── Admin Quick-Links ── -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-md-4">
            <a href="<?= APP_URL ?>/pages/admin/students.php"
               class="caspa-card d-flex align-items-center gap-3 p-3 text-decoration-none" style="border-left:4px solid var(--color-teal)">
                <div class="caspa-stat-icon flex-shrink-0" style="background:rgba(23,107,135,.12);color:var(--color-teal)">
                    <i class="bi bi-mortarboard-fill"></i>
                </div>
                <div>
                    <div class="fw-bold text-navy">Manage Students</div>
                    <div class="text-muted small">Add, search, view profiles</div>
                </div>
                <i class="bi bi-arrow-right ms-auto text-muted"></i>
            </a>
        </div>
        <div class="col-12 col-sm-6 col-md-4">
            <a href="<?= APP_URL ?>/pages/admin/classes.php"
               class="caspa-card d-flex align-items-center gap-3 p-3 text-decoration-none" style="border-left:4px solid var(--color-navy)">
                <div class="caspa-stat-icon flex-shrink-0" style="background:rgba(18,59,93,.12);color:var(--color-navy)">
                    <i class="bi bi-building-fill"></i>
                </div>
                <div>
                    <div class="fw-bold text-navy">Manage Classes</div>
                    <div class="text-muted small">Create and configure classes</div>
                </div>
                <i class="bi bi-arrow-right ms-auto text-muted"></i>
            </a>
        </div>
        <div class="col-12 col-sm-6 col-md-4">
            <button type="button" data-bs-toggle="modal" data-bs-target="#newAnnouncementModal"
               class="caspa-card d-flex align-items-center gap-3 p-3 w-100 text-start text-decoration-none border-0 bg-white"
               style="border-left:4px solid var(--color-purple)!important;cursor:pointer">
                <div class="caspa-stat-icon flex-shrink-0" style="background:rgba(91,63,163,.12);color:var(--color-purple)">
                    <i class="bi bi-megaphone-fill"></i>
                </div>
                <div>
                    <div class="fw-bold text-navy">Post Announcement</div>
                    <div class="text-muted small">Notify teachers, students & parents</div>
                </div>
                <i class="bi bi-arrow-right ms-auto text-muted"></i>
            </button>
        </div>
    </div>

    <!-- ── KPI Row ── -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-2">
            <div class="caspa-stat-card h-100">
                <div class="caspa-stat-icon" style="background:rgba(18,59,93,.12);color:var(--color-navy)">
                    <i class="bi bi-mortarboard-fill"></i>
                </div>
                <div>
                    <div class="caspa-stat-value" id="kpiStudents">&mdash;</div>
                    <div class="caspa-stat-label">Students</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-2">
            <div class="caspa-stat-card h-100">
                <div class="caspa-stat-icon" style="background:rgba(23,107,135,.12);color:var(--color-teal)">
                    <i class="bi bi-person-badge-fill"></i>
                </div>
                <div>
                    <div class="caspa-stat-value" id="kpiTeachers">&mdash;</div>
                    <div class="caspa-stat-label">Teachers</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-2">
            <div class="caspa-stat-card h-100">
                <div class="caspa-stat-icon" style="background:rgba(91,63,163,.12);color:var(--color-purple)">
                    <i class="bi bi-building-fill"></i>
                </div>
                <div>
                    <div class="caspa-stat-value" id="kpiClasses">&mdash;</div>
                    <div class="caspa-stat-label">Classes</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-2">
            <div class="caspa-stat-card h-100">
                <div class="caspa-stat-icon" id="kpiAttIcon" style="background:rgba(22,163,74,.12);color:var(--color-success)">
                    <i class="bi bi-calendar2-check-fill"></i>
                </div>
                <div>
                    <div class="caspa-stat-value" id="kpiAttPct">&mdash;</div>
                    <div class="caspa-stat-label">Today's Attendance</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-2">
            <div class="caspa-stat-card h-100">
                <div class="caspa-stat-icon" id="kpiPendIcon" style="background:rgba(217,119,6,.12);color:var(--color-warning)">
                    <i class="bi bi-hourglass-split"></i>
                </div>
                <div>
                    <div class="caspa-stat-value" id="kpiPending">&mdash;</div>
                    <div class="caspa-stat-label">Pending Attendance</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-2">
            <div class="caspa-stat-card h-100">
                <div class="caspa-stat-icon" style="background:rgba(59,130,246,.12);color:var(--color-sky)">
                    <i class="bi bi-journal-bookmark-fill"></i>
                </div>
                <div>
                    <div class="caspa-stat-value" id="kpiAssignments">&mdash;</div>
                    <div class="caspa-stat-label">Assignments This Week</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Pending Attendance Alert ── -->
    <div id="pendingAlertPanel" class="d-none mb-4">
        <div class="alert alert-warning border border-warning-subtle d-flex align-items-start gap-3 py-3 mb-0">
            <i class="bi bi-exclamation-triangle-fill fs-4 flex-shrink-0 mt-1" style="color:var(--color-warning)"></i>
            <div class="flex-grow-1">
                <div class="fw-bold mb-2" style="color:var(--color-warning)">
                    <span id="pendingAlertCount"></span> class(es) have not marked attendance today
                </div>
                <div class="d-flex flex-wrap gap-2" id="pendingClassBadges"></div>
            </div>
        </div>
    </div>

    <!-- ── Chart + Announcements ── -->
    <div class="row g-4 mb-4">
        <div class="col-12 col-lg-8">
            <div class="caspa-card h-100 p-0 overflow-hidden d-flex flex-column">
                <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-graph-up-arrow" style="color:var(--color-teal);font-size:1.1rem"></i>
                        <h2 class="h6 mb-0 text-navy fw-bold">7-Day Attendance Trend</h2>
                    </div>
                    <span class="badge bg-secondary-subtle text-secondary small">% present + late per class</span>
                </div>
                <div class="p-3 flex-grow-1 d-flex flex-column">
                    <div id="chartState" class="p-4 text-center d-none"></div>
                    <div class="flex-grow-1" style="min-height:260px;position:relative">
                        <canvas id="attendanceChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="caspa-card h-100 p-0 overflow-hidden d-flex flex-column">
                <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-megaphone text-navy" style="font-size:1.1rem"></i>
                        <h2 class="h6 mb-0 text-navy fw-bold">Announcements</h2>
                    </div>
                    <button class="btn btn-sm btn-caspa-primary d-flex align-items-center gap-1"
                            data-bs-toggle="modal" data-bs-target="#newAnnouncementModal">
                        <i class="bi bi-plus"></i><span>New</span>
                    </button>
                </div>
                <div id="announcementsContainer" class="p-3 flex-grow-1 overflow-auto" style="max-height:360px"></div>
            </div>
        </div>
    </div>

    <!-- ── Pending classes table ── -->
    <div class="caspa-card p-0 overflow-hidden">
        <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-calendar-x" style="font-size:1.1rem;color:var(--color-warning)"></i>
                <h2 class="h6 mb-0 text-navy fw-bold">Classes Pending Today's Attendance</h2>
            </div>
            <span id="pendingTableBadge" class="badge bg-warning-subtle text-warning border border-warning-subtle px-3"></span>
        </div>
        <div id="pendingTableContainer" class="p-3"></div>
    </div>
</div>

<!-- ── New Announcement Modal ── -->
<div class="modal fade" id="newAnnouncementModal" tabindex="-1" aria-labelledby="newAnnModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-white" style="background:var(--color-navy)">
                <h5 class="modal-title text-white d-flex align-items-center gap-2" id="newAnnModalLabel">
                    <i class="bi bi-megaphone-fill"></i> New Announcement
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="newAnnForm" novalidate>
                <div class="modal-body p-4">
                    <div id="annModalAlert" class="alert alert-danger d-none small py-2"></div>
                    <div class="mb-3">
                        <label class="caspa-form-label" for="annTitle">Title <span class="text-danger">*</span></label>
                        <input type="text" id="annTitle" name="title" class="form-control caspa-input"
                               placeholder="e.g. Exam Schedule Updated" maxlength="200" required>
                    </div>
                    <div class="mb-3">
                        <label class="caspa-form-label" for="annBody">Message <span class="text-danger">*</span></label>
                        <textarea id="annBody" name="body" class="form-control caspa-input" rows="5"
                                  placeholder="Write the announcement message…" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="caspa-form-label d-block mb-1">Audience <span class="text-danger">*</span></label>
                        <div class="d-flex flex-wrap gap-3">
                            <?php foreach (['principal','admin','teacher','student','parent'] as $r): ?>
                            <div class="form-check">
                                <input class="form-check-input ann-audience-cb" type="checkbox"
                                       id="aud_<?= htmlspecialchars($r, ENT_QUOTES) ?>" value="<?= htmlspecialchars($r, ENT_QUOTES) ?>"
                                       <?= in_array($r, ['teacher','student','parent']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="aud_<?= htmlspecialchars($r, ENT_QUOTES) ?>"><?= htmlspecialchars(ucfirst($r), ENT_QUOTES) ?></label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="mb-1">
                        <label class="caspa-form-label" for="annExpires">Expires On <span class="text-muted small fw-normal">(optional)</span></label>
                        <input type="date" id="annExpires" name="expires_at" class="form-control caspa-input"
                               min="<?= date('Y-m-d') ?>">
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-caspa-primary d-flex align-items-center gap-2" id="annSubmitBtn">
                        <i class="bi bi-send-fill"></i><span>Publish Announcement</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(function () {
    'use strict';

    let chartInstance = null;
    const PALETTE = [
        'rgba(23,107,135,1)','rgba(18,59,93,1)','rgba(91,63,163,1)',
        'rgba(22,163,74,1)','rgba(217,119,6,1)','rgba(59,130,246,1)',
    ];

    function esc(s) {
        if (!s) return '';
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
                        .replace(/"/g,'&quot;').replace(/'/g,'&#39;');
    }
    function fmtDate(d) {
        if (!d) return '';
        const p = d.split('-');
        return new Date(+p[0],+p[1]-1,+p[2]).toLocaleDateString('en-US',{month:'short',day:'numeric'});
    }
    function fmtDateTime(dt) {
        if (!dt) return '';
        const d = new Date(dt.replace(' ','T'));
        if (isNaN(d)) return dt;
        return d.toLocaleDateString('en-US',{month:'short',day:'numeric',year:'numeric'});
    }

    function loadDashboard() {
        const $state   = $('#dashStateContainer');
        const $content = $('#dashContent');
        $state.removeClass('d-none');
        $content.addClass('opacity-50');
        ui.showLoading($state);

        api.get('/api/dashboard_principal.php')
            .then(data => {
                ui.clearState($state);
                $state.addClass('d-none');
                $content.removeClass('opacity-50');
                $('#todayLabel').text(fmtDate(data.today));
                renderKpis(data.kpis);
                renderPendingAlert(data.pending_classes);
                renderChart(data.date_labels, data.class_attendance_trend);
                renderAnnouncements(data.announcements);
                renderPendingTable(data.pending_classes);
            })
            .catch(err => {
                ui.clearState($state);
                $state.removeClass('d-none');
                $content.addClass('d-none');
                ui.showError($state, 'Failed to load dashboard: ' + (err.message || 'Unknown error'));
            });
    }

    function renderKpis(k) {
        $('#kpiStudents').text(k.total_students);
        $('#kpiTeachers').text(k.total_teachers);
        $('#kpiClasses').text(k.total_classes);
        $('#kpiAssignments').text(k.weekly_assignments);
        $('#kpiPending').text(k.pending_classes_count);

        const pct = k.today_attendance_pct;
        let pctHtml, iconBg, iconCol;
        if (pct === 0) {
            pctHtml = '<span class="text-muted">No data</span>';
            iconBg = 'rgba(100,116,139,.12)'; iconCol = 'var(--color-text-muted)';
        } else if (pct >= 85) {
            pctHtml = `<span class="text-success">${pct}%</span>`;
            iconBg = 'rgba(22,163,74,.12)'; iconCol = 'var(--color-success)';
        } else if (pct >= 70) {
            pctHtml = `<span style="color:var(--color-warning)">${pct}%</span>`;
            iconBg = 'rgba(217,119,6,.12)'; iconCol = 'var(--color-warning)';
        } else {
            pctHtml = `<span class="text-danger">${pct}%</span>`;
            iconBg = 'rgba(220,38,38,.12)'; iconCol = 'var(--color-danger)';
        }
        $('#kpiAttPct').html(pctHtml);
        $('#kpiAttIcon').css({'background':iconBg,'color':iconCol});

        const pend = k.pending_classes_count;
        $('#kpiPendIcon').css(pend === 0
            ? {'background':'rgba(22,163,74,.12)','color':'var(--color-success)'}
            : {'background':'rgba(217,119,6,.12)','color':'var(--color-warning)'}
        );
    }

    function renderPendingAlert(pending) {
        const $panel = $('#pendingAlertPanel');
        if (!pending || pending.length === 0) { $panel.addClass('d-none'); return; }
        $panel.removeClass('d-none');
        $('#pendingAlertCount').text(pending.length);
        const $badges = $('#pendingClassBadges').empty();
        pending.forEach(c => {
            $badges.append(`<span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1">
                <i class="bi bi-building me-1"></i>${esc(c.display_name)}</span>`);
        });
    }

    function renderChart(labels, series) {
        const $state = $('#chartState');
        const canvas = document.getElementById('attendanceChart');
        const activeSeries = series.filter(s => s.daily_pcts.some(v => v !== null));
        if (activeSeries.length === 0) {
            $state.removeClass('d-none');
            ui.showEmpty($state, 'No attendance data recorded in the last 7 days.');
            $(canvas).addClass('d-none'); return;
        }
        $(canvas).removeClass('d-none'); $state.addClass('d-none');
        if (chartInstance) { chartInstance.destroy(); }
        chartInstance = new Chart(canvas, {
            type: 'line',
            data: {
                labels: labels.map(fmtDate),
                datasets: activeSeries.map((s, i) => ({
                    label: s.class_name,
                    data: s.daily_pcts,
                    borderColor: PALETTE[i % PALETTE.length],
                    backgroundColor: PALETTE[i % PALETTE.length].replace(',1)',', .08)'),
                    borderWidth: 2.5, pointRadius: 4, pointHoverRadius: 6,
                    tension: 0.35, spanGaps: true, fill: false,
                })),
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                scales: {
                    y: { min: 0, max: 100, ticks: { callback: v => v+'%', font:{size:11} }, grid:{color:'rgba(0,0,0,.06)'} },
                    x: { ticks: { font:{size:11} }, grid:{display:false} },
                },
                plugins: {
                    legend: { position:'bottom', labels:{boxWidth:12,padding:14,font:{size:11}} },
                    tooltip: { callbacks: { label: ctx => ` ${ctx.dataset.label}: ${ctx.parsed.y !== null ? ctx.parsed.y+'%' : '—'}` } },
                },
            },
        });
    }

    function renderAnnouncements(anns) {
        const $c = $('#announcementsContainer').empty();
        if (!anns || anns.length === 0) { ui.showEmpty($c, 'No announcements yet.'); return; }
        const $l = $('<div class="d-flex flex-column gap-3"></div>');
        anns.forEach(a => {
            const audBadges = (a.audience||'').split(',')
                .map(r => `<span class="badge bg-light border text-secondary fw-normal" style="font-size:.65rem">${esc(r.trim())}</span>`)
                .join(' ');
            $l.append(`<div class="p-2 border rounded-3 bg-light" style="font-size:.85rem">
                <div class="fw-bold text-navy mb-1">${esc(a.title)}</div>
                <div class="text-muted mb-1" style="font-size:.72rem"><i class="bi bi-person me-1"></i>${esc(a.author_name)} &bull; ${fmtDateTime(a.published_at)}</div>
                <div class="d-flex flex-wrap gap-1 mb-2">${audBadges}</div>
                <p class="text-secondary mb-0" style="white-space:pre-wrap;line-height:1.45">${esc(a.body)}</p>
            </div>`);
        });
        $c.append($l);
    }

    function renderPendingTable(pending) {
        const $badge = $('#pendingTableBadge');
        const $cont  = $('#pendingTableContainer').empty();
        $badge.text(pending.length + ' pending');
        if (!pending || pending.length === 0) {
            $cont.html(`<div class="text-center py-4"><i class="bi bi-check-circle-fill text-success" style="font-size:2rem"></i>
                <p class="text-success fw-semibold mt-2 mb-0">All classes have marked attendance today!</p></div>`);
            $badge.removeClass('bg-warning-subtle text-warning border-warning-subtle')
                  .addClass('bg-success-subtle text-success border-success-subtle');
            return;
        }
        $badge.removeClass('bg-success-subtle text-success border-success-subtle')
              .addClass('bg-warning-subtle text-warning border-warning-subtle');
        const $tbl = $(`<div class="table-responsive"><table class="table caspa-table align-middle mb-0">
            <thead><tr><th>Class</th><th>Class Teacher</th><th class="text-end">Students</th></tr></thead>
            <tbody id="pendingTbody"></tbody></table></div>`);
        const $tbody = $tbl.find('#pendingTbody');
        pending.forEach(c => {
            $tbody.append(`<tr>
                <td><i class="bi bi-building text-warning me-2"></i><span class="fw-semibold">${esc(c.display_name)}</span></td>
                <td class="text-muted">${esc(c.class_teacher_name)}</td>
                <td class="text-end"><span class="badge bg-secondary-subtle text-secondary border">${c.student_count}</span></td>
            </tr>`);
        });
        $cont.append($tbl);
    }

    // Announcement form
    $('#newAnnForm').on('submit', function (e) {
        e.preventDefault();
        const $btn   = $('#annSubmitBtn');
        const $alert = $('#annModalAlert');
        const title   = $('#annTitle').val().trim();
        const body    = $('#annBody').val().trim();
        const expires = $('#annExpires').val();
        const audience = $('.ann-audience-cb:checked').map(function(){ return this.value; }).get().join(',');
        $alert.addClass('d-none');
        if (!title)    { $alert.removeClass('d-none').text('Title is required.'); return; }
        if (!body)     { $alert.removeClass('d-none').text('Message body is required.'); return; }
        if (!audience) { $alert.removeClass('d-none').text('Select at least one audience.'); return; }
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Publishing…');
        api.post('/api/announcements.php', { title, body, audience, expires_at: expires })
            .then(() => {
                bootstrap.Modal.getInstance(document.getElementById('newAnnouncementModal')).hide();
                $btn.prop('disabled', false).html('<i class="bi bi-send-fill"></i> <span>Publish Announcement</span>');
                ui.showToast('Announcement published!', 'success');
                loadDashboard();
            })
            .catch(err => {
                $alert.removeClass('d-none').text(err.message || 'Failed to publish.');
                $btn.prop('disabled', false).html('<i class="bi bi-send-fill"></i> <span>Publish Announcement</span>');
            });
    });
    document.getElementById('newAnnouncementModal').addEventListener('hidden.bs.modal', function () {
        $('#annModalAlert').addClass('d-none'); $('#newAnnForm')[0].reset();
    });

    loadDashboard();
});
</script>

<?php require_once ROOT . '/includes/layout_footer.php'; ?>
