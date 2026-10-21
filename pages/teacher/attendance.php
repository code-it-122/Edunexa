<?php
/**
 * CASPA School OS – pages/teacher/attendance.php
 *
 * Daily Attendance Marking for Teachers (and Admin/Principal overview)
 * Uses shared layout: includes/layout_header.php & layout_footer.php
 */

declare(strict_types=1);

define('ROOT', dirname(__DIR__, 2));

require_once ROOT . '/includes/auth.php';
require_once ROOT . '/includes/db.php';

session_boot();
require_login();
require_role(['teacher', 'admin', 'principal']);

$user       = current_user();
$pageTitle  = 'Attendance';
$pageRole   = $user['role'];
$activeMenu = 'Attendance';

require_once ROOT . '/includes/layout_header.php';
?>

<div class="caspa-page-header d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
    <div>
        <h1 class="mb-1">Daily Attendance Marking</h1>
        <p class="caspa-breadcrumb mb-0">Record and review student attendance per subject and date.</p>
    </div>
    <!-- Quick action: Mark all present & Save -->
    <div class="d-flex align-items-center gap-2">
        <button type="button" id="btnMarkAllPresent" class="btn btn-outline-success btn-sm d-flex align-items-center gap-1 shadow-sm">
            <i class="bi bi-check2-all"></i>
            <span>Mark All Present</span>
        </button>
        <button type="button" id="btnSaveAttendance" class="btn btn-caspa-primary btn-sm d-flex align-items-center gap-1 shadow-sm px-3">
            <i class="bi bi-cloud-arrow-up-fill"></i>
            <span id="btnSaveText">Save Attendance</span>
        </button>
    </div>
</div>

<!-- Selector & Controls Card -->
<div class="caspa-card mb-4 p-3">
    <div class="row g-3 align-items-end">
        <!-- Class + Subject Pair Dropdown -->
        <div class="col-12 col-md-6 col-lg-5">
            <label for="classSubjectSelect" class="caspa-form-label">Class &amp; Subject <span class="text-danger">*</span></label>
            <select id="classSubjectSelect" class="form-select caspa-input">
                <option value="">Loading assignments…</option>
            </select>
        </div>

        <!-- Date Picker (defaults to today, max today) -->
        <div class="col-12 col-sm-6 col-md-3 col-lg-3">
            <label for="attendanceDatePicker" class="caspa-form-label">Date <span class="text-danger">*</span></label>
            <input
                type="date"
                id="attendanceDatePicker"
                class="form-control caspa-input"
                value="<?= date('Y-m-d') ?>"
                max="<?= date('Y-m-d') ?>"
            >
        </div>

        <!-- Reload / Refresh button -->
        <div class="col-12 col-sm-6 col-md-3 col-lg-2">
            <button type="button" id="btnLoadRoster" class="btn btn-outline-secondary w-100">
                <i class="bi bi-arrow-repeat me-1"></i>Fetch Roster
            </button>
        </div>
    </div>
</div>

<!-- Live Summary KPI Chips Bar -->
<div class="row g-2 mb-4" id="summaryChipsRow" aria-live="polite">
    <div class="col-6 col-md-3 col-xl-2">
        <div class="caspa-card p-2 text-center bg-white border">
            <div class="small text-muted fw-semibold">Total Students</div>
            <div class="fs-4 fw-bold text-navy" id="chipTotal">0</div>
        </div>
    </div>
    <div class="col-6 col-md-3 col-xl-2">
        <div class="caspa-card p-2 text-center border border-success-subtle bg-success-subtle">
            <div class="small text-success fw-semibold"><i class="bi bi-check-circle me-1"></i>Present</div>
            <div class="fs-4 fw-bold text-success" id="chipPresent">0</div>
        </div>
    </div>
    <div class="col-6 col-md-3 col-xl-2">
        <div class="caspa-card p-2 text-center border border-danger-subtle bg-danger-subtle">
            <div class="small text-danger fw-semibold"><i class="bi bi-x-circle me-1"></i>Absent</div>
            <div class="fs-4 fw-bold text-danger" id="chipAbsent">0</div>
        </div>
    </div>
    <div class="col-6 col-md-3 col-xl-2">
        <div class="caspa-card p-2 text-center border border-warning-subtle bg-warning-subtle">
            <div class="small text-warning fw-semibold"><i class="bi bi-clock me-1"></i>Late</div>
            <div class="fs-4 fw-bold text-warning" id="chipLate">0</div>
        </div>
    </div>
    <div class="col-6 col-md-3 col-xl-2">
        <div class="caspa-card p-2 text-center border border-info-subtle bg-info-subtle">
            <div class="small text-info fw-semibold"><i class="bi bi-shield-check me-1"></i>Excused</div>
            <div class="fs-4 fw-bold text-info" id="chipExcused">0</div>
        </div>
    </div>
    <div class="col-6 col-md-3 col-xl-2">
        <div class="caspa-card p-2 text-center bg-light border">
            <div class="small text-secondary fw-semibold">Unmarked</div>
            <div class="fs-4 fw-bold text-secondary" id="chipUnmarked">0</div>
        </div>
    </div>
</div>

<!-- Notice Banner if already recorded -->
<div id="alreadyMarkedAlert" class="alert alert-info d-none py-2 mb-3 d-flex align-items-center gap-2" role="status">
    <i class="bi bi-info-circle-fill"></i>
    <span>Attendance has already been recorded for this date. Modifying and saving will update existing records.</span>
</div>

<!-- Students Roster Card & Table -->
<div class="caspa-card p-0 overflow-hidden mb-4">
    <div class="table-responsive">
        <table class="table caspa-table align-middle mb-0" id="attendanceTable">
            <thead>
                <tr>
                    <th scope="col" style="width: 140px;">Admission No</th>
                    <th scope="col">Student Name</th>
                    <th scope="col" class="text-center" style="width: 380px;">Attendance Status</th>
                </tr>
            </thead>
            <tbody id="attendanceTableBody">
                <!-- Populated via AJAX -->
            </tbody>
        </table>
    </div>

    <!-- State placeholder container for loading/empty/error -->
    <div id="attendanceStateContainer" class="p-5 text-center"></div>

    <!-- Bottom Save Action Bar inside card -->
    <div id="attendanceBottomBar" class="p-3 bg-light border-top d-flex justify-content-between align-items-center d-none">
        <div class="small text-muted" id="unsavedChangesText">
            <i class="bi bi-exclamation-circle text-warning me-1"></i>Ensure all student statuses are set before saving.
        </div>
        <button type="button" id="btnBottomSave" class="btn btn-caspa-primary px-4 shadow-sm">
            <i class="bi bi-cloud-arrow-up-fill me-1"></i>Save Attendance
        </button>
    </div>
</div>

<!-- Page JavaScript -->
<?php
$inlineJs = <<<JS
$(function () {
    let currentRoster = [];
    let isSaving = false;

    // Status config mapping: label, color classes, button active classes
    const statusConfig = {
        present: { label: 'Present', btnClass: 'btn-outline-success', activeClass: 'btn-success text-white' },
        absent:  { label: 'Absent',  btnClass: 'btn-outline-danger',  activeClass: 'btn-danger text-white' },
        late:    { label: 'Late',    btnClass: 'btn-outline-warning', activeClass: 'btn-warning text-dark' },
        excused: { label: 'Excused', btnClass: 'btn-outline-info',    activeClass: 'btn-info text-white' }
    };

    // 1. Load teacher's assigned class_subjects dropdown
    function loadClassSubjects() {
        const \$select = $('#classSubjectSelect');

        api.get('/api/attendance/my_class_subjects.php')
            .then(rows => {
                \$select.empty();
                if (!rows || rows.length === 0) {
                    \$select.append('<option value="">No class subjects assigned to you</option>');
                    ui.showEmpty('#attendanceStateContainer', 'You have no class subjects assigned. Please contact the administrator.');
                    $('#attendanceTable, #attendanceBottomBar').addClass('d-none');
                    return;
                }

                \$select.append('<option value="">Select a class and subject…</option>');
                const urlParams = new URLSearchParams(window.location.search);
                const targetCsId = urlParams.get('class_subject_id');

                rows.forEach((r, idx) => {
                    const label = r.class_name + ' - Section ' + r.class_section + ' (' + r.subject_name + ')';
                    const opt = $('<option>').val(r.class_subject_id).text(label);
                    if (targetCsId && String(r.class_subject_id) === String(targetCsId)) {
                        opt.prop('selected', true);
                    } else if (!targetCsId && idx === 0) {
                        opt.prop('selected', true);
                    }
                    \$select.append(opt);
                });

                // Trigger load for the first selection
                if (\$select.val()) {
                    loadRoster();
                }
            })
            .catch(err => {
                ui.showError('#attendanceStateContainer', 'Failed to load assigned subjects: ' + err.message);
                $('#attendanceTable, #attendanceBottomBar').addClass('d-none');
            });
    }

    // 2. Load attendance roster for selected class_subject and date
    function loadRoster() {
        const csId = $('#classSubjectSelect').val();
        const date = $('#attendanceDatePicker').val();

        if (!csId) {
            ui.showEmpty('#attendanceStateContainer', 'Please select a class and subject to view the attendance roster.');
            $('#attendanceTable, #attendanceBottomBar').addClass('d-none');
            updateSummaryCounters([]);
            return;
        }

        if (!date) {
            ui.showError('#attendanceStateContainer', 'Please select a valid date.');
            return;
        }

        $('#attendanceStateContainer').removeClass('d-none');
        ui.showLoading('#attendanceStateContainer', 'Fetching class roster and records…');
        $('#attendanceTable, #attendanceBottomBar, #alreadyMarkedAlert').addClass('d-none');

        api.get('/api/attendance/list.php', { class_subject_id: csId, date: date })
            .then(res => {
                const students = res.students || [];
                currentRoster = students;

                if (students.length === 0) {
                    ui.showEmpty('#attendanceStateContainer', 'No students enrolled in this class.');
                    $('#attendanceTable, #attendanceBottomBar').addClass('d-none');
                    updateSummaryCounters([]);
                    return;
                }

                ui.clearState('#attendanceStateContainer');
                $('#attendanceStateContainer').addClass('d-none');
                $('#attendanceTable, #attendanceBottomBar').removeClass('d-none');

                // If any student has recorded attendance, show the notice banner
                const hasExisting = students.some(s => s.attendance_status !== null);
                if (hasExisting) {
                    $('#alreadyMarkedAlert').removeClass('d-none');
                } else {
                    $('#alreadyMarkedAlert').addClass('d-none');
                }

                renderRosterTable(students);
                updateSummaryCounters(students);
            })
            .catch(err => {
                ui.showError('#attendanceStateContainer', err.message || 'Failed to load roster.');
                $('#attendanceTable, #attendanceBottomBar').addClass('d-none');
                updateSummaryCounters([]);
            });
    }

    // 3. Render table with segmented status toggles
    function renderRosterTable(students) {
        const \$tbody = $('#attendanceTableBody').empty();

        students.forEach(s => {
            // Default to 'present' if unmarked
            const currentStatus = s.attendance_status ? s.attendance_status.toLowerCase() : 'present';

            const \$tr = $('<tr>').attr('data-student-id', s.student_id);

            // Admission no
            const \$tdAdm = $('<td>').html(
                '<span class="fw-semibold text-navy font-monospace small">' + escapeHtml(s.admission_number) + '</span>'
            );

            // Student name & email
            const \$tdName = $('<td>').html(
                '<div class="fw-semibold text-navy">' + escapeHtml(s.student_name) + '</div>' +
                '<div class="small text-muted">' + escapeHtml(s.student_email) + '</div>'
            );

            // Status Segmented Button Group
            const \$tdStatus = $('<td>').addClass('text-center');
            const \$btnGroup = $('<div>').addClass('btn-group btn-group-sm w-100 shadow-sm').attr('role', 'group');

            ['present', 'absent', 'late', 'excused'].forEach(status => {
                const cfg = statusConfig[status];
                const isActive = (status === currentStatus);
                const btnClass = isActive ? ('btn ' + cfg.activeClass) : ('btn ' + cfg.btnClass);

                const \$btn = $('<button>')
                    .attr('type', 'button')
                    .addClass(btnClass + ' status-toggle-btn')
                    .attr('data-status', status)
                    .attr('data-student-id', s.student_id)
                    .text(cfg.label);

                \$btnGroup.append(\$btn);
            });

            \$tdStatus.append(\$btnGroup);
            \$tr.append(\$tdAdm, \$tdName, \$tdStatus);
            \$tbody.append(\$tr);
        });
    }

    // 4. Handle status button toggle click
    $(document).on('click', '.status-toggle-btn', function () {
        const \$btn = $(this);
        const newStatus = \$btn.data('status');
        const studentId = parseInt(\$btn.data('student-id'), 10);
        const \$group = \$btn.closest('.btn-group');

        // Reset all buttons in this group to outline
        \$group.find('.status-toggle-btn').each(function () {
            const st = $(this).data('status');
            const cfg = statusConfig[st];
            $(this).removeClass(cfg.activeClass).addClass(cfg.btnClass);
        });

        // Activate clicked button
        const activeCfg = statusConfig[newStatus];
        \$btn.removeClass(activeCfg.btnClass).addClass(activeCfg.activeClass);

        // Update in-memory roster
        const studentObj = currentRoster.find(s => parseInt(s.student_id, 10) === studentId);
        if (studentObj) {
            studentObj.attendance_status = newStatus;
        }

        updateSummaryCounters(currentRoster);
    });

    // 5. "Mark All Present" button click
    $('#btnMarkAllPresent').on('click', function () {
        if (!currentRoster || currentRoster.length === 0) return;

        currentRoster.forEach(s => {
            s.attendance_status = 'present';
        });

        // Update UI buttons
        $('#attendanceTableBody tr').each(function () {
            const \$group = $(this).find('.btn-group');
            \$group.find('.status-toggle-btn').each(function () {
                const st = $(this).data('status');
                const cfg = statusConfig[st];
                if (st === 'present') {
                    $(this).removeClass(cfg.btnClass).addClass(cfg.activeClass);
                } else {
                    $(this).removeClass(cfg.activeClass).addClass(cfg.btnClass);
                }
            });
        });

        updateSummaryCounters(currentRoster);
        ui.showToast('All students marked as Present.', 'info');
    });

    // 6. Update Live Summary Counters
    function updateSummaryCounters(students) {
        let total = students ? students.length : 0;
        let counts = { present: 0, absent: 0, late: 0, excused: 0, unmarked: 0 };

        if (students) {
            students.forEach(s => {
                const st = s.attendance_status ? s.attendance_status.toLowerCase() : 'present';
                if (counts[st] !== undefined) {
                    counts[st]++;
                } else {
                    counts.unmarked++;
                }
            });
        }

        $('#chipTotal').text(total);
        $('#chipPresent').text(counts.present);
        $('#chipAbsent').text(counts.absent);
        $('#chipLate').text(counts.late);
        $('#chipExcused').text(counts.excused);
        $('#chipUnmarked').text(counts.unmarked);
    }

    // 7. Save Attendance action
    function saveAttendance() {
        if (isSaving) return;

        const csId = $('#classSubjectSelect').val();
        const date = $('#attendanceDatePicker').val();

        if (!csId) {
            ui.showToast('Please select a class and subject.', 'danger');
            return;
        }

        if (!date) {
            ui.showToast('Please select a date.', 'danger');
            return;
        }

        // Build records payload from table rows
        const records = [];
        $('#attendanceTableBody tr').each(function () {
            const sid = parseInt($(this).data('student-id'), 10);
            let chosenStatus = 'present';

            // Find whichever button has the active class
            $(this).find('.status-toggle-btn').each(function () {
                const st = $(this).data('status');
                const cfg = statusConfig[st];
                if ($(this).hasClass(cfg.activeClass.split(' ')[0])) {
                    chosenStatus = st;
                }
            });

            if (sid > 0) {
                records.push({ student_id: sid, status: chosenStatus });
            }
        });

        if (records.length === 0) {
            ui.showToast('No students to save.', 'warning');
            return;
        }

        // Lock button and set spinner
        isSaving = true;
        const \$btnTop = $('#btnSaveAttendance');
        const \$btnBottom = $('#btnBottomSave');
        const origTopHtml = \$btnTop.html();
        const origBottomHtml = \$btnBottom.html();

        const spinnerHtml = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Saving…';
        \$btnTop.prop('disabled', true).html(spinnerHtml);
        \$btnBottom.prop('disabled', true).html(spinnerHtml);

        api.post('/api/attendance/save.php', {
            class_subject_id: parseInt(csId, 10),
            date: date,
            records: records
        })
        .then(res => {
            ui.showToast(res.message || 'Attendance saved successfully!', 'success');
            $('#alreadyMarkedAlert').removeClass('d-none');
            // Refresh roster to confirm persistence
            loadRoster();
        })
        .catch(err => {
            ui.showToast(err.message || 'Failed to save attendance.', 'danger');
        })
        .finally(() => {
            isSaving = false;
            \$btnTop.prop('disabled', false).html(origTopHtml);
            \$btnBottom.prop('disabled', false).html(origBottomHtml);
        });
    }

    $('#btnSaveAttendance, #btnBottomSave').on('click', function () {
        saveAttendance();
    });

    // 8. Event listeners for dropdowns
    $('#classSubjectSelect').on('change', function () {
        loadRoster();
    });

    $('#attendanceDatePicker').on('change', function () {
        loadRoster();
    });

    $('#btnLoadRoster').on('click', function () {
        loadRoster();
    });

    function escapeHtml(str) {
        return String(str || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // Initialize
    loadClassSubjects();
});
JS;
?>

<?php require_once ROOT . '/includes/layout_footer.php'; ?>
