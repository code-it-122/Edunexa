<?php
/**
 * CASPA School OS – pages/shared/_family_attendance.php
 *
 * Attendance page for the STUDENT and the PARENT role.
 * Not a page on its own: pages/student/attendance.php and pages/parent/attendance.php
 * check the role, set $pageRole, then include this file.
 *
 * Data: api/my_attendance.php (student → self only, parent → linked children only).
 */

declare(strict_types=1);

if (!defined('ROOT')) {
    define('ROOT', dirname(__DIR__, 2));
}
if (!isset($pageRole) || !in_array($pageRole, ['student', 'parent'], true)) {
    http_response_code(403);
    exit('Forbidden');
}

$user      = current_user();
$pageTitle = $pageRole === 'parent' ? 'Child Attendance' : 'Attendance';
$isParent  = ($pageRole === 'parent');

require_once ROOT . '/includes/layout_header.php';
?>

<div class="caspa-page-header d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="mb-1"><?= $isParent ? "Child's Attendance" : 'My Attendance' ?></h1>
        <p class="caspa-breadcrumb mb-0" id="subtitle">Attendance by subject and by day.</p>
    </div>
    <div id="childSwitcher" class="d-none d-flex align-items-center gap-2">
        <label for="childSelect" class="form-label mb-0 fw-semibold text-navy">Child</label>
        <select id="childSelect" class="form-select" style="width: auto;"></select>
    </div>
</div>

<div id="pageState" class="p-4 text-center d-none"></div>

<div id="pageContent" class="d-none">
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg">
            <div class="caspa-stat-card h-100">
                <div class="caspa-stat-icon" id="rateIcon" style="background-color: rgba(23, 107, 135, 0.12); color: var(--color-teal);"><i class="bi bi-percent"></i></div>
                <div><div class="caspa-stat-value" id="sRate">&mdash;</div><div class="caspa-stat-label">Overall</div></div>
            </div>
        </div>
        <div class="col-6 col-lg">
            <div class="caspa-stat-card h-100">
                <div class="caspa-stat-icon" style="background-color: rgba(22, 163, 74, 0.12); color: var(--color-success);"><i class="bi bi-check-circle-fill"></i></div>
                <div><div class="caspa-stat-value" id="sPresent">&mdash;</div><div class="caspa-stat-label">Present</div></div>
            </div>
        </div>
        <div class="col-6 col-lg">
            <div class="caspa-stat-card h-100">
                <div class="caspa-stat-icon" style="background-color: rgba(220, 38, 38, 0.12); color: var(--color-danger);"><i class="bi bi-x-circle-fill"></i></div>
                <div><div class="caspa-stat-value" id="sAbsent">&mdash;</div><div class="caspa-stat-label">Absent</div></div>
            </div>
        </div>
        <div class="col-6 col-lg">
            <div class="caspa-stat-card h-100">
                <div class="caspa-stat-icon" style="background-color: rgba(217, 119, 6, 0.12); color: var(--color-warning);"><i class="bi bi-clock-fill"></i></div>
                <div><div class="caspa-stat-value" id="sLate">&mdash;</div><div class="caspa-stat-label">Late</div></div>
            </div>
        </div>
        <div class="col-6 col-lg">
            <div class="caspa-stat-card h-100">
                <div class="caspa-stat-icon" style="background-color: rgba(59, 130, 246, 0.12); color: var(--color-sky);"><i class="bi bi-shield-check"></i></div>
                <div><div class="caspa-stat-value" id="sExcused">&mdash;</div><div class="caspa-stat-label">Excused</div></div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12 col-xl-5">
            <div class="caspa-card p-0 overflow-hidden h-100">
                <div class="p-3 border-bottom bg-white d-flex align-items-center gap-2">
                    <i class="bi bi-book text-navy fs-5"></i>
                    <h2 class="h6 mb-0 text-navy fw-bold">By Subject</h2>
                </div>
                <div id="bySubject" class="p-3"></div>
            </div>
        </div>
        <div class="col-12 col-xl-7">
            <div class="caspa-card p-0 overflow-hidden h-100">
                <div class="p-3 border-bottom bg-white d-flex align-items-center gap-2">
                    <i class="bi bi-calendar3 text-navy fs-5"></i>
                    <h2 class="h6 mb-0 text-navy fw-bold">Recent Days</h2>
                </div>
                <div id="records" class="p-3"></div>
            </div>
        </div>
    </div>
    <p class="text-muted small mt-3 mb-0">Attendance is recorded by each subject teacher. Rate = present &divide; all records.</p>
</div>

<div id="noChildren" class="caspa-card p-5 text-center d-none">
    <i class="bi bi-person-exclamation text-muted" style="font-size: 2.5rem;"></i>
    <p class="text-muted mt-3 mb-0">No student record is linked to your account yet. Please contact the school office.</p>
</div>

<?php ob_start(); // printed by layout_footer.php after jQuery loads ?>
$(function () {
    'use strict';

    const IS_PARENT = <?= $isParent ? 'true' : 'false' ?>;
    const $state = $('#pageState');
    const $content = $('#pageContent');

    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }
    function fmtDate(d) {
        const p = d.split('-');
        return new Date(p[0], p[1] - 1, p[2]).toLocaleDateString('en-US', { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' });
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
        const s = d.summary, sel = d.selected;

        const $sel = $('#childSelect').empty();
        if (IS_PARENT && d.children.length > 1) {
            d.children.forEach(c => $sel.append(
                $('<option>').val(c.id).text(c.name + (c.class_name ? ' (' + c.class_name + ')' : '')).prop('selected', c.id === sel.id)));
            $('#childSwitcher').removeClass('d-none');
        } else {
            $('#childSwitcher').addClass('d-none');
        }
        $('#subtitle').text(sel.name + (sel.class_name ? ' · ' + sel.class_name : '') + ' — attendance by subject and by day.');

        if (s.rate === null) {
            $('#sRate').addClass('fs-5').text('No records');
        } else {
            $('#sRate').removeClass('fs-5').text(s.rate + '%');
            const good = s.rate >= 90;
            $('#rateIcon').css('background-color', good ? 'rgba(22, 163, 74, 0.12)' : 'rgba(217, 119, 6, 0.12)')
                          .css('color', good ? 'var(--color-success)' : 'var(--color-warning)');
        }
        $('#sPresent').text(s.present);
        $('#sAbsent').text(s.absent);
        $('#sLate').text(s.late);
        $('#sExcused').text(s.excused);

        const $bs = $('#bySubject').empty();
        if (!d.by_subject.length) {
            ui.showEmpty($bs, 'No attendance has been recorded yet.');
        } else {
            $bs.html(d.by_subject.map(x => {
                const pct = x.rate === null ? 0 : x.rate;
                const color = pct >= 90 ? 'bg-success' : (pct >= 75 ? 'bg-warning' : 'bg-danger');
                return `<div class="mb-3">
                    <div class="d-flex justify-content-between"><span class="fw-semibold text-navy">${escapeHtml(x.subject_name)}</span><span class="fw-semibold">${x.rate === null ? '&ndash;' : x.rate + '%'}</span></div>
                    <div class="progress my-1" style="height: 8px;" role="progressbar" aria-label="${escapeHtml(x.subject_name)} attendance" aria-valuenow="${pct}" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar ${color}" style="width: ${pct}%"></div></div>
                    <div class="text-muted small">${x.present} present &middot; ${x.absent} absent &middot; ${x.late} late &middot; ${x.excused} excused (${x.total} records)</div>
                </div>`;
            }).join(''));
        }

        const $rec = $('#records').empty();
        if (!d.records.length) {
            ui.showEmpty($rec, 'No attendance has been recorded yet.');
        } else {
            const rows = d.records.map(r => `<tr><td>${escapeHtml(fmtDate(r.date))}</td><td>${escapeHtml(r.subject_name)}</td><td class="text-end">${statusBadge(r.status)}</td></tr>`).join('');
            $rec.html(`<div class="table-responsive" style="max-height: 480px; overflow-y: auto;"><table class="table table-sm align-middle mb-0"><thead class="table-light"><tr><th>Date</th><th>Subject</th><th class="text-end">Status</th></tr></thead><tbody>${rows}</tbody></table></div>`);
        }
    }

    function load(studentId) {
        $state.removeClass('d-none');
        $content.addClass('d-none');
        $('#noChildren').addClass('d-none');
        ui.showLoading($state);

        api.get('/api/my_attendance.php', studentId ? { student_id: studentId } : {})
            .then(d => {
                ui.clearState($state);
                $state.addClass('d-none');
                if (!d.selected) { $('#noChildren').removeClass('d-none'); return; }
                $content.removeClass('d-none');
                render(d);
            })
            .catch(err => {
                ui.clearState($state);
                $state.removeClass('d-none');
                ui.showError($state, 'Could not load attendance: ' + (err.message || 'Unknown error'));
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
