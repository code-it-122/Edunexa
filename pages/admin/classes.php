<?php
/**
 * CASPA School OS – pages/admin/classes.php
 *
 * Classes List & Management
 * Access: Admin (manage), Principal & Teachers (read-only for assigned classes).
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
$pageTitle = 'Classes';
$pageRole  = $user['role'];
$isAdmin   = ($user['role'] === 'admin');

require_once ROOT . '/includes/layout_header.php';
?>

<div class="caspa-page-header d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
    <div>
        <h1 class="mb-1">Classes &amp; Sections</h1>
        <p class="caspa-breadcrumb mb-0">
            <?= $isAdmin ? 'Manage school grade divisions, sections, and class teachers.' : 'Classes and sections assigned to your account.' ?>
        </p>
    </div>
    <?php if ($isAdmin): ?>
    <button type="button" class="btn btn-caspa-primary shadow-sm d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#addClassModal">
        <i class="bi bi-plus-circle-fill"></i>
        <span>Add Class</span>
    </button>
    <?php endif; ?>
</div>

<!-- Table Card -->
<div class="caspa-card p-0 overflow-hidden mb-4">
    <div class="table-responsive">
        <table class="table caspa-table align-middle mb-0" id="classesTable">
            <thead>
                <tr>
                    <th scope="col">Class &amp; Section</th>
                    <th scope="col">Academic Year</th>
                    <th scope="col">Class Teacher</th>
                    <th scope="col">Enrolled Students</th>
                    <th scope="col" class="text-end" style="width: 160px;">Actions</th>
                </tr>
            </thead>
            <tbody id="classesTableBody">
                <!-- Populated via AJAX -->
            </tbody>
        </table>
    </div>

    <!-- State placeholder container -->
    <div id="classesStateContainer" class="p-4"></div>
</div>

<?php if ($isAdmin): ?>
<!-- Add Class Modal -->
<div class="modal fade" id="addClassModal" tabindex="-1" aria-labelledby="addClassModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-white" style="background-color: var(--color-navy);">
                <h5 class="modal-title d-flex align-items-center gap-2 text-white" id="addClassModalLabel">
                    <i class="bi bi-building-add"></i>
                    <span>Create New Class</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addClassForm" novalidate>
                <div class="modal-body p-4">
                    <div id="classModalAlert" class="alert alert-danger d-none py-2 small" role="alert"></div>

                    <div class="mb-3">
                        <label for="classNameInput" class="caspa-form-label">Class / Grade Name <span class="text-danger">*</span></label>
                        <input type="text" id="classNameInput" name="name" class="form-control caspa-input" placeholder="e.g. Grade 10" required minlength="2" maxlength="80">
                        <div class="invalid-feedback">Please enter a class name (e.g. Grade 10).</div>
                    </div>

                    <div class="mb-3">
                        <label for="classSectionInput" class="caspa-form-label">Section <span class="text-danger">*</span></label>
                        <input type="text" id="classSectionInput" name="section" class="form-control caspa-input text-uppercase" placeholder="e.g. A" required maxlength="10" value="A">
                        <div class="invalid-feedback">Please enter section label (e.g. A, B).</div>
                    </div>

                    <div class="mb-3">
                        <label for="classAySelect" class="caspa-form-label">Academic Year <span class="text-danger">*</span></label>
                        <select id="classAySelect" name="academic_year_id" class="form-select caspa-input" required>
                            <!-- Populated dynamically -->
                        </select>
                        <div class="invalid-feedback">Please select an academic year.</div>
                    </div>

                    <div class="mb-3">
                        <label for="classTeacherSelect" class="caspa-form-label">Class Teacher (Optional)</label>
                        <select id="classTeacherSelect" name="class_teacher_id" class="form-select caspa-input">
                            <option value="">No class teacher assigned</option>
                            <!-- Populated dynamically -->
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light p-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="btnSubmitClass" class="btn btn-caspa-primary px-4">
                        <i class="bi bi-check-lg me-1"></i>Create Class
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Script -->
<?php
$inlineJs = <<<JS
$(function () {
    function loadClasses() {
        $('#classesStateContainer').removeClass('d-none');
        ui.showLoading('#classesStateContainer', 'Loading classes…');
        $('#classesTable').addClass('d-none');

        api.get('/api/classes.php')
            .then(res => {
                const classes = res.classes || [];
                const academicYears = res.academic_years || [];
                const teachers = res.teachers || [];

                // Populate modal dropdowns if present
                if ($('#classAySelect').length) {
                    const \$ay = $('#classAySelect').empty();
                    academicYears.forEach(ay => {
                        const opt = $('<option>').val(ay.id).text(ay.label + (ay.is_current ? ' (Current)' : ''));
                        if (ay.is_current) opt.prop('selected', true);
                        \$ay.append(opt);
                    });
                }

                if ($('#classTeacherSelect').length) {
                    const \$t = $('#classTeacherSelect');
                    \$t.find('option:not(:first)').remove();
                    teachers.forEach(t => {
                        \$t.append($('<option>').val(t.id).text(t.name + ' (' + t.email + ')'));
                    });
                }

                if (classes.length === 0) {
                    ui.showEmpty('#classesStateContainer', 'No classes found in your accessible scope.');
                    $('#classesTable').addClass('d-none');
                    return;
                }

                ui.clearState('#classesStateContainer');
                $('#classesStateContainer').addClass('d-none');
                $('#classesTable').removeClass('d-none');

                renderClassesTable(classes);
            })
            .catch(err => {
                ui.showError('#classesStateContainer', 'Failed to load classes: ' + err.message);
                $('#classesTable').addClass('d-none');
            });
    }

    function renderClassesTable(classes) {
        const \$tbody = $('#classesTableBody').empty();

        classes.forEach(c => {
            const count = parseInt(c.student_count || 0, 10);
            const teacher = c.class_teacher_name
                ? '<i class="bi bi-person-check text-success me-1"></i>' + escapeHtml(c.class_teacher_name)
                : '<span class="text-muted fst-italic">None assigned</span>';

            const studentsUrl = APP_URL + '/pages/admin/students.php?class_id=' + c.id;

            const \$tr = $('<tr>').html(`
                <td>
                    <div class="fw-bold text-navy fs-6">
                        ` + escapeHtml(c.name) + ` &ndash; Section ` + escapeHtml(c.section) + `
                    </div>
                    <div class="small text-muted font-monospace">Class ID: #` + c.id + `</div>
                </td>
                <td>
                    <span class="badge bg-light text-secondary border">
                        <i class="bi bi-calendar3 me-1"></i>` + escapeHtml(c.academic_year || '') + `
                    </span>
                </td>
                <td class="small">` + teacher + `</td>
                <td>
                    <span class="badge ` + (count > 0 ? 'bg-primary-subtle text-primary' : 'bg-secondary-subtle text-secondary') + ` px-2 py-1">
                        <i class="bi bi-people me-1"></i>` + count + ` student` + (count === 1 ? '' : 's') + `
                    </span>
                </td>
                <td class="text-end">
                    <a href="` + studentsUrl + `" class="btn btn-sm btn-outline-primary py-1 px-2 d-inline-flex align-items-center gap-1">
                        <i class="bi bi-people-fill"></i>
                        <span>View Students</span>
                    </a>
                </td>
            `);

            \$tbody.append(\$tr);
        });
    }

    // Add Class Form submission (Admin only)
    $('#addClassForm').on('submit', function (e) {
        e.preventDefault();
        const form = this;
        const \$alert = $('#classModalAlert').addClass('d-none').text('');

        if (!form.checkValidity()) {
            form.classList.add('was-validated');
            return;
        }

        const \$btn = $('#btnSubmitClass');
        const origText = \$btn.html();
        \$btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Saving…');

        const payload = {
            name: $('#classNameInput').val().trim(),
            section: $('#classSectionInput').val().trim(),
            academic_year_id: $('#classAySelect').val(),
            class_teacher_id: $('#classTeacherSelect').val(),
        };

        api.post('/api/classes.php', payload)
            .then(res => {
                ui.showToast(res.message || 'Class created successfully!', 'success');
                const modal = bootstrap.Modal.getInstance(document.getElementById('addClassModal'));
                if (modal) modal.hide();
                form.reset();
                form.classList.remove('was-validated');
                loadClasses();
            })
            .catch(err => {
                \$alert.removeClass('d-none').text(err.message || 'Failed to create class.');
            })
            .finally(() => {
                \$btn.prop('disabled', false).html(origText);
            });
    });

    function escapeHtml(str) {
        return String(str || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    loadClasses();
});
JS;
?>

<?php require_once ROOT . '/includes/layout_footer.php'; ?>
