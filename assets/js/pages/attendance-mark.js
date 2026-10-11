/**
 * assets/js/pages/attendance-mark.js
 * CASPA AI / EduNexa - Daily Attendance Marking Controller
 */

$(document).ready(function () {
    // State
    let classesList = [];
    let currentClassId = null;
    let currentDate = $('#attDate').val() || new Date().toISOString().split('T')[0];
    let students = [];
    let initialState = [];
    let hasUnsavedChanges = false;
    let csrfToken = $('meta[name="csrf-token"]').attr('content') || '';

    // Initialize Toast
    const toastSuccessEl = document.getElementById('toastSuccess');
    const toastErrorEl = document.getElementById('toastError');
    const toastSuccess = toastSuccessEl ? new bootstrap.Toast(toastSuccessEl, { delay: 4000 }) : null;
    const toastError = toastErrorEl ? new bootstrap.Toast(toastErrorEl, { delay: 5000 }) : null;

    function showToast(type, message) {
        if (type === 'success' && toastSuccess) {
            $('#toastSuccessMsg').text(message);
            toastSuccess.show();
        } else if (toastError) {
            $('#toastErrorMsg').text(message);
            toastError.show();
        }
    }

    // 1. Load Accessible Classes
    function loadClasses() {
        $.ajax({
            url: 'api/attendance.php?action=classes',
            type: 'GET',
            dataType: 'json',
            success: function (res) {
                if (res.ok && res.data && res.data.classes) {
                    classesList = res.data.classes;
                    populateClassDropdown();
                } else {
                    showErrorState(res.error || 'Failed to load classes.');
                }
            },
            error: function (xhr) {
                const msg = xhr.responseJSON && xhr.responseJSON.error ? xhr.responseJSON.error : 'Network error loading classes.';
                showErrorState(msg);
            }
        });
    }

    function populateClassDropdown() {
        const $classSelect = $('#classSelect');
        const $sectionSelect = $('#sectionSelect');

        $classSelect.empty();
        $sectionSelect.empty();

        if (classesList.length === 0) {
            $classSelect.append('<option value="">No classes assigned</option>');
            $sectionSelect.append('<option value="">--</option>');
            showEmptyState('No classes found assigned to your account.');
            return;
        }

        // Group by class name for dual dropdowns
        const uniqueNames = [...new Set(classesList.map(c => c.name))];
        uniqueNames.forEach(function (name) {
            $classSelect.append(`<option value="${name}">${name}</option>`);
        });

        // Trigger change to populate sections
        updateSectionsForClass($classSelect.val());
    }

    function updateSectionsForClass(className) {
        const $sectionSelect = $('#sectionSelect');
        $sectionSelect.empty();

        const matching = classesList.filter(c => c.name === className);
        matching.forEach(function (c) {
            $sectionSelect.append(`<option value="${c.id}" data-section="${c.section}">Section ${c.section}</option>`);
        });

        currentClassId = parseInt($sectionSelect.val());
        fetchRoster();
    }

    // Events for Dropdowns & Date
    $('#classSelect').on('change', function () {
        if (checkUnsavedPrompt()) {
            updateSectionsForClass($(this).val());
        } else {
            // Revert selection
            const activeClass = classesList.find(c => c.id === currentClassId);
            if (activeClass) $(this).val(activeClass.name);
        }
    });

    $('#sectionSelect').on('change', function () {
        if (checkUnsavedPrompt()) {
            currentClassId = parseInt($(this).val());
            fetchRoster();
        } else {
            $(this).val(currentClassId);
        }
    });

    $('#attDate').on('change', function () {
        const selected = $(this).val();
        const today = new Date().toISOString().split('T')[0];

        if (selected > today) {
            showToast('error', 'Cannot select future dates.');
            $(this).val(currentDate);
            return;
        }

        if (checkUnsavedPrompt()) {
            currentDate = selected;
            fetchRoster();
        } else {
            $(this).val(currentDate);
        }
    });

    function checkUnsavedPrompt() {
        if (hasUnsavedChanges) {
            return confirm('You have unsaved attendance changes. Discard them and switch view?');
        }
        return true;
    }

    // 2. Fetch Roster with Skeleton Loader
    function fetchRoster() {
        if (!currentClassId) return;

        showSkeletonLoader();
        $('#alreadyMarkedBanner').addClass('d-none');
        $('#errorState').addClass('d-none');
        $('#emptyState').addClass('d-none');
        $('#attendanceTableContainer').removeClass('d-none');

        $.ajax({
            url: `api/attendance.php?action=roster&class_id=${currentClassId}&date=${currentDate}`,
            type: 'GET',
            dataType: 'json',
            success: function (res) {
                if (res.ok && res.data) {
                    students = res.data.students || [];
                    // Deep copy initial state
                    initialState = JSON.parse(JSON.stringify(students));
                    setUnsavedChanges(false);

                    if (students.length === 0) {
                        showEmptyState('No students enrolled in this class section.');
                    } else {
                        renderStudentRoster();
                        updateSummaryChips();

                        if (res.data.is_marked) {
                            $('#alreadyMarkedBanner').removeClass('d-none');
                        }
                    }
                } else {
                    showErrorState(res.error || 'Failed to fetch roster.');
                }
            },
            error: function (xhr) {
                const msg = xhr.responseJSON && xhr.responseJSON.error ? xhr.responseJSON.error : 'Server connection error.';
                showErrorState(msg);
            }
        });
    }

    // Skeleton Loader
    function showSkeletonLoader() {
        let skeletonHtml = '';
        for (let i = 0; i < 5; i++) {
            skeletonHtml += `
                <tr class="skeleton-row">
                    <td class="text-center"><div class="skeleton-box" style="width: 28px; height: 18px; margin: 0 auto;"></div></td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="skeleton-box rounded-circle" style="width: 34px; height: 34px;"></div>
                            <div class="skeleton-box" style="width: 140px; height: 16px;"></div>
                        </div>
                    </td>
                    <td><div class="skeleton-box" style="width: 260px; height: 34px; border-radius: 20px;"></div></td>
                    <td><div class="skeleton-box" style="width: 120px; height: 30px;"></div></td>
                </tr>
            `;
        }
        $('#studentRosterTbody').html(skeletonHtml);
        $('#mobileCardsList').html('<div class="p-4 text-center text-muted"><span class="spinner-border spinner-border-sm me-2"></span>Loading student roster...</div>');
    }

    // 3. Render Student Roster (Desktop Table + Mobile Cards)
    function renderStudentRoster() {
        const $tbody = $('#studentRosterTbody');
        const $mobileList = $('#mobileCardsList');

        $tbody.empty();
        $mobileList.empty();

        students.forEach(function (st, idx) {
            const initials = getInitials(st.full_name);
            const showRemark = (st.status !== 'present');
            const remarkVal = escapeHtml(st.remark || '');

            // Desktop Row
            const rowHtml = `
                <tr class="student-row" data-id="${st.student_id}" data-name="${escapeHtml(st.full_name.toLowerCase())}" data-roll="${escapeHtml(st.roll_no)}">
                    <td class="align-middle text-center text-muted fw-bold font-monospace" style="width: 70px;">
                        #${st.roll_no}
                    </td>
                    <td class="align-middle">
                        <div class="d-flex align-items-center gap-3">
                            <div class="student-avatar-circle" title="${escapeHtml(st.full_name)}">
                                ${initials}
                            </div>
                            <div>
                                <div class="fw-bold text-dark text-truncate" style="max-width: 220px;">${escapeHtml(st.full_name)}</div>
                                <div class="small text-muted font-monospace d-sm-none">Roll #${st.roll_no}</div>
                            </div>
                        </div>
                    </td>
                    <td class="align-middle">
                        <div class="status-segmented-control" role="radiogroup" aria-label="Attendance for ${escapeHtml(st.full_name)}">
                            <button type="button" class="btn-segment btn-seg-present ${st.status === 'present' ? 'active' : ''}" data-status="present" aria-checked="${st.status === 'present'}" tabindex="0">
                                <i class="bi bi-check-lg me-1"></i>Present
                            </button>
                            <button type="button" class="btn-segment btn-seg-absent ${st.status === 'absent' ? 'active' : ''}" data-status="absent" aria-checked="${st.status === 'absent'}" tabindex="0">
                                <i class="bi bi-x-lg me-1"></i>Absent
                            </button>
                            <button type="button" class="btn-segment btn-seg-late ${st.status === 'late' ? 'active' : ''}" data-status="late" aria-checked="${st.status === 'late'}" tabindex="0">
                                <i class="bi bi-clock-history me-1"></i>Late
                            </button>
                            <button type="button" class="btn-segment btn-seg-leave ${st.status === 'leave' ? 'active' : ''}" data-status="leave" aria-checked="${st.status === 'leave'}" tabindex="0">
                                <i class="bi bi-calendar-event me-1"></i>Leave
                            </button>
                        </div>
                    </td>
                    <td class="align-middle" style="min-width: 180px;">
                        <div class="remark-wrapper ${showRemark ? '' : 'd-none'}">
                            <input type="text" class="form-control form-control-sm remark-input" placeholder="Add remark (optional)..." value="${remarkVal}" maxlength="255" aria-label="Remark for ${escapeHtml(st.full_name)}">
                        </div>
                    </td>
                </tr>
            `;
            $tbody.append(rowHtml);

            // Mobile Card (<768px)
            const cardHtml = `
                <div class="card student-mobile-card border-0 shadow-sm mb-3 rounded-3 p-3 bg-white" data-id="${st.student_id}" data-name="${escapeHtml(st.full_name.toLowerCase())}" data-roll="${escapeHtml(st.roll_no)}">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="student-avatar-circle">
                                ${initials}
                            </div>
                            <div>
                                <div class="fw-bold text-dark fs-6">${escapeHtml(st.full_name)}</div>
                                <div class="small text-muted font-monospace">Roll No #${st.roll_no}</div>
                            </div>
                        </div>
                        <span class="badge status-pill-${st.status} text-uppercase px-2 py-1" style="font-size: 11px;">
                            ${st.status}
                        </span>
                    </div>

                    <div class="status-segmented-control mb-2" role="radiogroup" aria-label="Attendance status for ${escapeHtml(st.full_name)}">
                        <button type="button" class="btn-segment btn-seg-present ${st.status === 'present' ? 'active' : ''}" data-status="present" aria-checked="${st.status === 'present'}">
                            <i class="bi bi-check-lg me-1"></i>Present
                        </button>
                        <button type="button" class="btn-segment btn-seg-absent ${st.status === 'absent' ? 'active' : ''}" data-status="absent" aria-checked="${st.status === 'absent'}">
                            <i class="bi bi-x-lg me-1"></i>Absent
                        </button>
                        <button type="button" class="btn-segment btn-seg-late ${st.status === 'late' ? 'active' : ''}" data-status="late" aria-checked="${st.status === 'late'}">
                            <i class="bi bi-clock-history me-1"></i>Late
                        </button>
                        <button type="button" class="btn-segment btn-seg-leave ${st.status === 'leave' ? 'active' : ''}" data-status="leave" aria-checked="${st.status === 'leave'}">
                            <i class="bi bi-calendar-event me-1"></i>Leave
                        </button>
                    </div>

                    <div class="remark-wrapper mt-2 ${showRemark ? '' : 'd-none'}">
                        <input type="text" class="form-control form-control-sm remark-input" placeholder="Add remark (e.g., bus delay, medical)..." value="${remarkVal}" maxlength="255">
                    </div>
                </div>
            `;
            $mobileList.append(cardHtml);
        });

        applySearchFilter();
    }

    // Helper: Initials
    function getInitials(name) {
        if (!name) return 'ST';
        const parts = name.trim().split(/\s+/);
        if (parts.length === 1) return parts[0].substring(0, 2).toUpperCase();
        return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
    }

    // 4. Segmented Control Selection (Event Delegation)
    $(document).on('click', '.btn-segment', function () {
        const $btn = $(this);
        const status = $btn.data('status');
        const $container = $btn.closest('.student-row, .student-mobile-card');
        const studentId = parseInt($container.data('id'));

        // Update active class on both desktop and mobile views
        const $bothViews = $(`[data-id="${studentId}"]`);
        $bothViews.find('.btn-segment').removeClass('active').attr('aria-checked', 'false');
        $bothViews.find(`[data-status="${status}"]`).addClass('active').attr('aria-checked', 'true');

        // Update mobile pill if present
        $bothViews.find('.badge[class*="status-pill-"]')
            .attr('class', `badge status-pill-${status} text-uppercase px-2 py-1`)
            .text(status);

        // Toggle Remark field: show if absent, late, or leave
        const $remarks = $bothViews.find('.remark-wrapper');
        if (status === 'present') {
            $remarks.addClass('d-none');
        } else {
            $remarks.removeClass('d-none');
            $remarks.find('.remark-input').first().focus();
        }

        // Update in-memory state
        const student = students.find(s => s.student_id === studentId);
        if (student) {
            student.status = status;
        }

        updateSummaryChips();
        setUnsavedChanges(true);
    });

    // Keyboard support for segmented buttons (Space / Enter)
    $(document).on('keydown', '.btn-segment', function (e) {
        if (e.key === ' ' || e.key === 'Enter') {
            e.preventDefault();
            $(this).trigger('click');
        }
    });

    // Remark input change handler
    $(document).on('input', '.remark-input', function () {
        const val = $(this).val();
        const studentId = parseInt($(this).closest('[data-id]').data('id'));

        // Sync desktop & mobile inputs
        $(`[data-id="${studentId}"] .remark-input`).val(val);

        const student = students.find(s => s.student_id === studentId);
        if (student) {
            student.remark = val;
        }

        setUnsavedChanges(true);
    });

    // 5. Summary Chips Calculation (Live update with aria-live)
    function updateSummaryChips() {
        const total = students.length;
        let present = 0, absent = 0, late = 0, leave = 0;

        students.forEach(function (s) {
            if (s.status === 'present') present++;
            else if (s.status === 'absent') absent++;
            else if (s.status === 'late') late++;
            else if (s.status === 'leave') leave++;
        });

        $('#chipTotal').text(total);
        $('#chipPresent').text(present);
        $('#chipAbsent').text(absent);
        $('#chipLate').text(late);
        $('#chipLeave').text(leave);

        // Aria-live announcement for screen readers
        $('#summaryAriaLive').text(`Summary updated: ${present} present, ${absent} absent, ${late} late, ${leave} on leave out of ${total} total.`);
    }

    // 6. Toolbar: Search Filter
    $('#studentSearchInput').on('input', function () {
        applySearchFilter();
    });

    function applySearchFilter() {
        const q = ($('#studentSearchInput').val() || '').toLowerCase().trim();
        let matchCount = 0;

        $('.student-row, .student-mobile-card').each(function () {
            const name = $(this).data('name') || '';
            const roll = $(this).data('roll') || '';

            if (!q || name.includes(q) || roll.includes(q)) {
                $(this).removeClass('d-none');
                matchCount++;
            } else {
                $(this).addClass('d-none');
            }
        });

        if (matchCount === 0 && students.length > 0) {
            $('#noSearchMatch').removeClass('d-none');
        } else {
            $('#noSearchMatch').addClass('d-none');
        }
    }

    // 7. Toolbar: Mark All Present
    $('#btnMarkAllPresent').on('click', function () {
        if (students.length === 0) return;

        students.forEach(function (s) {
            s.status = 'present';
        });

        // Update all UI controls
        $('.btn-segment').removeClass('active').attr('aria-checked', 'false');
        $('.btn-seg-present').addClass('active').attr('aria-checked', 'true');
        $('.remark-wrapper').addClass('d-none');
        $('.badge[class*="status-pill-"]')
            .attr('class', 'badge status-pill-present text-uppercase px-2 py-1')
            .text('present');

        updateSummaryChips();
        setUnsavedChanges(true);
        showToast('success', 'All students marked as Present.');
    });

    // 8. Toolbar: Reset to Initial Loaded State
    $('#btnResetMarks, #btnDiscardBottom').on('click', function () {
        if (!hasUnsavedChanges) return;

        if (confirm('Discard changes and restore previously saved marks?')) {
            students = JSON.parse(JSON.stringify(initialState));
            renderStudentRoster();
            updateSummaryChips();
            setUnsavedChanges(false);
            showToast('success', 'Changes discarded. Restored original attendance marks.');
        }
    });

    // 9. Sticky Bottom Bar & Dirty State Management
    function setUnsavedChanges(isDirty) {
        hasUnsavedChanges = isDirty;
        if (isDirty) {
            $('#stickyBottomBar').addClass('show');
            $('#unsavedNotice').removeClass('d-none');
        } else {
            $('#stickyBottomBar').removeClass('show');
            $('#unsavedNotice').addClass('d-none');
        }
    }

    // Warn on leaving with unsaved changes
    window.addEventListener('beforeunload', function (e) {
        if (hasUnsavedChanges) {
            e.preventDefault();
            e.returnValue = 'You have unsaved attendance changes.';
            return e.returnValue;
        }
    });

    // 10. Save Attendance via AJAX
    $('#btnSaveAttendance').on('click', function () {
        if (!currentClassId || students.length === 0) return;

        const $btn = $(this);
        const originalText = $btn.html();
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Saving Attendance...');

        const payload = {
            csrf_token: csrfToken,
            class_id: currentClassId,
            date: currentDate,
            records: students.map(s => ({
                student_id: s.student_id,
                status: s.status,
                remark: s.remark
            }))
        };

        $.ajax({
            url: 'api/attendance.php?action=save',
            type: 'POST',
            contentType: 'application/json',
            headers: {
                'X-CSRF-Token': csrfToken
            },
            data: JSON.stringify(payload),
            dataType: 'json',
            success: function (res) {
                $btn.prop('disabled', false).html(originalText);
                if (res.ok) {
                    // Update initial state to match current state
                    initialState = JSON.parse(JSON.stringify(students));
                    setUnsavedChanges(false);
                    $('#alreadyMarkedBanner').removeClass('d-none');
                    showToast('success', res.data.message || 'Attendance saved successfully!');
                } else {
                    showToast('error', res.error || 'Failed to save attendance.');
                }
            },
            error: function (xhr) {
                $btn.prop('disabled', false).html(originalText);
                const msg = xhr.responseJSON && xhr.responseJSON.error ? xhr.responseJSON.error : 'Network error saving attendance.';
                showToast('error', msg);
            }
        });
    });

    // Error & Empty State Helpers
    function showErrorState(msg) {
        $('#attendanceTableContainer').addClass('d-none');
        $('#emptyState').addClass('d-none');
        $('#errorState').removeClass('d-none');
        $('#errorMsgText').text(msg);
    }

    function showEmptyState(msg) {
        $('#attendanceTableContainer').addClass('d-none');
        $('#errorState').addClass('d-none');
        $('#emptyState').removeClass('d-none');
        $('#emptyMsgText').text(msg);
    }

    $('#btnRetryLoad').on('click', function () {
        fetchRoster();
    });

    function escapeHtml(text) {
        if (!text) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // Initial Load
    loadClasses();
});
