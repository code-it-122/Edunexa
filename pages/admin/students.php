<?php
/**
 * CASPA School OS – pages/admin/students.php
 *
 * Students Directory & Management
 * Access: Admin (manage), Principal & Teachers (read-only, scoped to assigned classes).
 */

declare(strict_types=1);

if (!defined('ROOT')) {
    define('ROOT', dirname(__DIR__, 2));
}

require_once ROOT . '/includes/auth.php';
require_once ROOT . '/includes/db.php';

session_boot();
require_role(['admin', 'principal', 'teacher']);

$user      = current_user();
$pageTitle = 'Students';
$pageRole  = $user['role'];
$isAdmin   = ($user['role'] === 'admin');

// Pre-selected class from query parameter if provided
$selectedClassId = isset($_GET['class_id']) ? (int) $_GET['class_id'] : 0;

require_once ROOT . '/includes/layout_header.php';
?>

<div class="caspa-page-header d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
    <div>
        <h1 class="mb-1">Students Directory</h1>
        <p class="caspa-breadcrumb mb-0">
            <?= $isAdmin ? 'Manage enrolled students and admission profiles.' : 'View student directory for your assigned classes (read-only).' ?>
        </p>
    </div>
    <?php if ($isAdmin): ?>
    <button type="button" class="btn btn-caspa-primary shadow-sm d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#addStudentModal">
        <i class="bi bi-person-plus-fill"></i>
        <span>Add Student</span>
    </button>
    <?php endif; ?>
</div>

<!-- Filters Bar -->
<div class="caspa-card mb-4 p-3">
    <div class="row g-2 align-items-center">
        <!-- Search Input -->
        <div class="col-12 col-md-5">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0 text-muted">
                    <i class="bi bi-search"></i>
                </span>
                <input
                    type="text"
                    id="searchStudentInput"
                    class="form-control caspa-input border-start-0 ps-0"
                    placeholder="Search by student name, admission no, or email…"
                >
            </div>
        </div>

        <!-- Class Filter Dropdown -->
        <div class="col-12 col-sm-6 col-md-4">
            <select id="classFilterSelect" class="form-select caspa-input">
                <option value="">All Accessible Classes</option>
                <!-- Populated dynamically via api/classes.php -->
            </select>
        </div>

        <!-- Reset & Count -->
        <div class="col-12 col-sm-6 col-md-3 d-flex justify-content-sm-end gap-2 align-items-center">
            <button type="button" id="btnResetFilters" class="btn btn-outline-secondary btn-sm px-3" title="Clear filters">
                <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
            </button>
            <span id="totalStudentsBadge" class="badge bg-secondary-subtle text-secondary px-2 py-2">
                Loading…
            </span>
        </div>
    </div>
</div>

<!-- Table Card -->
<div class="caspa-card p-0 overflow-hidden mb-4">
    <div class="table-responsive">
        <table class="table caspa-table align-middle mb-0" id="studentsTable">
            <thead>
                <tr>
                    <th scope="col" style="width: 140px;">Admission No</th>
                    <th scope="col">Student</th>
                    <th scope="col">Class &amp; Section</th>
                    <th scope="col">Gender</th>
                    <th scope="col">Date of Birth</th>
                    <th scope="col" class="text-end" style="width: 120px;">Action</th>
                </tr>
            </thead>
            <tbody id="studentsTableBody">
                <!-- Populated via AJAX -->
            </tbody>
        </table>
    </div>

    <!-- Empty / Loading state placeholder container -->
    <div id="tableStateContainer" class="d-none"></div>

    <!-- Pagination Footer -->
    <div id="paginationFooter" class="p-3 border-top d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
        <div id="paginationInfo" class="small text-muted">
            Showing page 1
        </div>
        <nav aria-label="Students pagination">
            <ul class="pagination pagination-sm mb-0" id="paginationControls">
                <!-- Populated via JS -->
            </ul>
        </nav>
    </div>
</div>

<?php if ($isAdmin): ?>
<!-- Add Student Modal -->
<div class="modal fade" id="addStudentModal" tabindex="-1" aria-labelledby="addStudentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-navy text-white" style="background-color: var(--color-navy);">
                <h5 class="modal-title d-flex align-items-center gap-2 text-white" id="addStudentModalLabel">
                    <i class="bi bi-person-plus-fill"></i>
                    <span>Enrol New Student</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addStudentForm" novalidate>
                <div class="modal-body p-4">
                    <div id="modalAlert" class="alert alert-danger d-none py-2 small" role="alert"></div>

                    <div class="row g-3">
                        <!-- Full Name -->
                        <div class="col-md-6">
                            <label for="studentName" class="caspa-form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" id="studentName" name="name" class="form-control caspa-input" placeholder="e.g. Rahul Sharma" required minlength="2" maxlength="120">
                            <div class="invalid-feedback">Please enter a valid student name (2–120 chars).</div>
                        </div>

                        <!-- Email -->
                        <div class="col-md-6">
                            <label for="studentEmail" class="caspa-form-label">Email Address <span class="text-danger">*</span></label>
                            <input type="email" id="studentEmail" name="email" class="form-control caspa-input" placeholder="student@school.edu" required>
                            <div class="invalid-feedback">Please provide a valid unique email.</div>
                        </div>

                        <!-- Admission Number -->
                        <div class="col-md-6">
                            <label for="studentAdmNo" class="caspa-form-label">Admission Number <span class="text-danger">*</span></label>
                            <input type="text" id="studentAdmNo" name="admission_number" class="form-control caspa-input text-uppercase" placeholder="e.g. ADM-2026-0042" required maxlength="40">
                            <div class="invalid-feedback">Unique admission number is required.</div>
                        </div>

                        <!-- Class Selection -->
                        <div class="col-md-6">
                            <label for="studentClassSelect" class="caspa-form-label">Enrolled Class <span class="text-danger">*</span></label>
                            <select id="studentClassSelect" name="class_id" class="form-select caspa-input" required>
                                <option value="">Select a class…</option>
                                <!-- Populated dynamically -->
                            </select>
                            <div class="invalid-feedback">Please select a class.</div>
                        </div>

                        <!-- Gender -->
                        <div class="col-md-4">
                            <label for="studentGender" class="caspa-form-label">Gender</label>
                            <select id="studentGender" name="gender" class="form-select caspa-input">
                                <option value="">Select gender (optional)</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                        </div>

                        <!-- Date of Birth -->
                        <div class="col-md-4">
                            <label for="studentDob" class="caspa-form-label">Date of Birth</label>
                            <input type="date" id="studentDob" name="date_of_birth" class="form-control caspa-input">
                        </div>

                        <!-- Default Password Notice -->
                        <div class="col-md-4 d-flex flex-column justify-content-end">
                            <div class="small text-muted p-2 rounded bg-light border">
                                <i class="bi bi-info-circle me-1"></i>Initial password defaults to <code>demo123</code>.
                            </div>
                        </div>

                        <!-- Address -->
                        <div class="col-12">
                            <label for="studentAddress" class="caspa-form-label">Address</label>
                            <textarea id="studentAddress" name="address" class="form-control caspa-input" rows="2" placeholder="Residential address (optional)"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light p-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="btnSubmitStudent" class="btn btn-caspa-primary px-4">
                        <i class="bi bi-check-lg me-1"></i>Create Student
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Page Script -->
<?php
$initialClassParam = (int) $selectedClassId;
$inlineJs = <<<JS
$(function () {
    let currentPage = 1;
    let preselectedClassId = {$initialClassParam};
    let debounceTimer = null;

    // Load accessible classes for filters & modal
    function loadClassesDropdown() {
        api.get('/api/classes.php')
            .then(data => {
                const classes = data.classes || [];
                const \$filter = $('#classFilterSelect');
                const \$modalSelect = $('#studentClassSelect');

                \$filter.find('option:not(:first)').remove();
                if (\$modalSelect.length) {
                    \$modalSelect.find('option:not(:first)').remove();
                }

                classes.forEach(c => {
                    const label = c.name + ' - ' + c.section + (c.student_count !== undefined ? ' (' + c.student_count + ' students)' : '');
                    const opt = $('<option>').val(c.id).text(label);
                    \$filter.append(opt.clone());
                    if (\$modalSelect.length) {
                        \$modalSelect.append(opt);
                    }
                });

                if (preselectedClassId > 0) {
                    \$filter.val(preselectedClassId);
                    preselectedClassId = 0; // consumed
                }

                loadStudents(1);
            })
            .catch(err => {
                ui.showError('#tableStateContainer', 'Failed to load classes: ' + err.message);
                $('#tableStateContainer').removeClass('d-none');
                $('#studentsTable, #paginationFooter').addClass('d-none');
            });
    }

    // Load students with pagination & search
    function loadStudents(page = 1) {
        currentPage = page;
        const q = $('#searchStudentInput').val().trim();
        const classId = $('#classFilterSelect').val();

        $('#tableStateContainer').removeClass('d-none');
        ui.showLoading('#tableStateContainer', 'Fetching students…');
        $('#studentsTable, #paginationFooter').addClass('d-none');

        api.get('/api/students.php', { q, class_id: classId, page: currentPage })
            .then(res => {
                const students = res.students || [];
                const total = res.total || 0;
                const totalPages = res.total_pages || 1;

                $('#totalStudentsBadge').text(total + ' student' + (total === 1 ? '' : 's'));

                if (students.length === 0) {
                    ui.showEmpty('#tableStateContainer', q || classId ? 'No students found matching your filters.' : 'No students enrolled in your accessible classes.');
                    $('#studentsTable, #paginationFooter').addClass('d-none');
                    return;
                }

                ui.clearState('#tableStateContainer');
                $('#tableStateContainer').addClass('d-none');
                $('#studentsTable, #paginationFooter').removeClass('d-none');

                renderTableRows(students);
                renderPagination(total, page, res.per_page || 10, totalPages);
            })
            .catch(err => {
                ui.showError('#tableStateContainer', err.message);
                $('#studentsTable, #paginationFooter').addClass('d-none');
            });
    }

    function renderTableRows(students) {
        const \$tbody = $('#studentsTableBody').empty();

        students.forEach(s => {
            const classLabel = (s.class_name && s.class_section)
                ? s.class_name + ' - ' + s.class_section
                : '<span class="text-muted">Unassigned</span>';

            const genderBadge = s.gender
                ? '<span class="badge bg-light text-dark border text-capitalize">' + s.gender + '</span>'
                : '<span class="text-muted small">&mdash;</span>';

            const dob = s.date_of_birth ? s.date_of_birth : '<span class="text-muted small">&mdash;</span>';

            const profileUrl = APP_URL + '/pages/shared/student_profile.php?id=' + s.id;

            const \$tr = $('<tr>').html(`
                <td>
                    <span class="fw-semibold text-navy font-monospace small">` + escapeHtml(s.admission_number) + `</span>
                </td>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <div class="rounded-circle bg-light d-flex align-items-center justify-content-center text-navy fw-bold border" style="width: 34px; height: 34px; font-size: 0.85rem;">
                            ` + getInitials(s.name) + `
                        </div>
                        <div>
                            <a href="` + profileUrl + `" class="fw-semibold text-teal text-decoration-none hover-underline">
                                ` + escapeHtml(s.name) + `
                            </a>
                            <div class="small text-muted">` + escapeHtml(s.email) + `</div>
                        </div>
                    </div>
                </td>
                <td>
                    <span class="badge badge-navy">` + classLabel + `</span>
                </td>
                <td>` + genderBadge + `</td>
                <td class="small text-secondary">` + dob + `</td>
                <td class="text-end">
                    <a href="` + profileUrl + `" class="btn btn-sm btn-outline-primary py-1 px-2 d-inline-flex align-items-center gap-1" title="View Profile">
                        <i class="bi bi-person-bounding-box"></i>
                        <span>Profile</span>
                    </a>
                </td>
            `);

            \$tbody.append(\$tr);
        });
    }

    function renderPagination(total, current, perPage, totalPages) {
        const start = total > 0 ? (current - 1) * perPage + 1 : 0;
        const end = Math.min(current * perPage, total);
        $('#paginationInfo').text('Showing ' + start + '–' + end + ' of ' + total + ' students');

        const \$controls = $('#paginationControls').empty();
        if (totalPages <= 1) return;

        // Previous
        const prevDisabled = current <= 1 ? ' disabled' : '';
        \$controls.append(`
            <li class="page-item` + prevDisabled + `">
                <a class="page-link" href="#" data-page="` + (current - 1) + `">&laquo;</a>
            </li>
        `);

        // Page buttons
        for (let p = 1; p <= totalPages; p++) {
            if (p === 1 || p === totalPages || (p >= current - 1 && p <= current + 1)) {
                const active = p === current ? ' active' : '';
                \$controls.append(`
                    <li class="page-item` + active + `">
                        <a class="page-link" href="#" data-page="` + p + `">` + p + `</a>
                    </li>
                `);
            } else if (p === current - 2 || p === current + 2) {
                \$controls.append('<li class="page-item disabled"><span class="page-link">&hellip;</span></li>');
            }
        }

        // Next
        const nextDisabled = current >= totalPages ? ' disabled' : '';
        \$controls.append(`
            <li class="page-item` + nextDisabled + `">
                <a class="page-link" href="#" data-page="` + (current + 1) + `">&raquo;</a>
            </li>
        `);
    }

    // Pagination click
    $(document).on('click', '#paginationControls a.page-link', function (e) {
        e.preventDefault();
        const p = parseInt($(this).data('page'), 10);
        if (p && p !== currentPage) {
            loadStudents(p);
        }
    });

    // Search with debounce
    $('#searchStudentInput').on('input', function () {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            loadStudents(1);
        }, 300);
    });

    // Class filter change
    $('#classFilterSelect').on('change', function () {
        loadStudents(1);
    });

    // Reset filters
    $('#btnResetFilters').on('click', function () {
        $('#searchStudentInput').val('');
        $('#classFilterSelect').val('');
        loadStudents(1);
    });

    // Add Student Form submission (Admin only)
    $('#addStudentForm').on('submit', function (e) {
        e.preventDefault();
        const form = this;
        const \$alert = $('#modalAlert').addClass('d-none').text('');

        if (!form.checkValidity()) {
            form.classList.add('was-validated');
            return;
        }

        const \$btn = $('#btnSubmitStudent');
        const origText = \$btn.html();
        \$btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Saving…');

        const payload = {
            name: $('#studentName').val().trim(),
            email: $('#studentEmail').val().trim(),
            admission_number: $('#studentAdmNo').val().trim(),
            class_id: $('#studentClassSelect').val(),
            gender: $('#studentGender').val(),
            date_of_birth: $('#studentDob').val(),
            address: $('#studentAddress').val().trim(),
        };

        api.post('/api/students.php', payload)
            .then(res => {
                ui.showToast(res.message || 'Student enrolled successfully!', 'success');
                const modal = bootstrap.Modal.getInstance(document.getElementById('addStudentModal'));
                if (modal) modal.hide();
                form.reset();
                form.classList.remove('was-validated');
                loadStudents(1);
            })
            .catch(err => {
                \$alert.removeClass('d-none').text(err.message || 'Failed to add student.');
            })
            .finally(() => {
                \$btn.prop('disabled', false).html(origText);
            });
    });

    // Helper functions
    function escapeHtml(str) {
        return String(str || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function getInitials(name) {
        if (!name) return 'S';
        const parts = name.trim().split(/\s+/);
        if (parts.length === 1) return parts[0].substring(0, 2).toUpperCase();
        return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
    }

    // Initialize
    loadClassesDropdown();
});
JS;
?>

<?php require_once ROOT . '/includes/layout_footer.php'; ?>
