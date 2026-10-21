<?php
/**
 * CASPA School OS – pages/teacher/assignments.php
 *
 * Teacher Assignments Management:
 * List assignments + "Create assignment" modal (class, subject, title, description, due date).
 */

declare(strict_types=1);

define('ROOT', dirname(__DIR__, 2));

require_once ROOT . '/includes/auth.php';
require_once ROOT . '/includes/db.php';

session_boot();
require_login();
require_role('teacher');

$user       = current_user();
$pageTitle  = 'Assignments';
$pageRole   = 'teacher';
$activeMenu = 'Assignments';

require_once ROOT . '/includes/layout_header.php';
?>

<div class="caspa-page-header d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
    <div>
        <h1 class="mb-1">Assignments Management</h1>
        <p class="caspa-breadcrumb mb-0">Create, monitor, and manage homework and coursework for your classes.</p>
    </div>
    <button type="button" class="btn btn-caspa-primary shadow-sm d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#createAssignmentModal">
        <i class="bi bi-plus-circle-fill"></i>
        <span>Create Assignment</span>
    </button>
</div>

<!-- Filter Bar -->
<div class="caspa-card mb-4 p-3">
    <div class="row g-2 align-items-center">
        <div class="col-12 col-md-5">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                <input type="text" id="filterSearchInput" class="form-control caspa-input border-start-0 ps-0" placeholder="Filter assignments by title or subject…">
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-4">
            <select id="filterClassSelect" class="form-select caspa-input">
                <option value="">All Your Classes</option>
            </select>
        </div>
        <div class="col-12 col-sm-6 col-md-3 d-flex justify-content-sm-end align-items-center">
            <span id="assignmentsCountBadge" class="badge bg-secondary-subtle text-secondary px-3 py-2">
                Loading…
            </span>
        </div>
    </div>
</div>

<!-- Assignments Table Card -->
<div class="caspa-card p-0 overflow-hidden mb-4">
    <div class="table-responsive">
        <table class="table caspa-table align-middle mb-0" id="assignmentsTable">
            <thead>
                <tr>
                    <th scope="col">Title &amp; Description</th>
                    <th scope="col">Class &amp; Section</th>
                    <th scope="col">Subject</th>
                    <th scope="col">Due Date</th>
                    <th scope="col">Max Marks</th>
                    <th scope="col" class="text-end" style="width: 120px;">Actions</th>
                </tr>
            </thead>
            <tbody id="assignmentsTableBody">
                <!-- Populated via AJAX -->
            </tbody>
        </table>
    </div>

    <!-- State placeholder container -->
    <div id="assignmentsStateContainer" class="p-5 text-center"></div>
</div>

<!-- Create Assignment Modal -->
<div class="modal fade" id="createAssignmentModal" tabindex="-1" aria-labelledby="createAssignmentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-white" style="background-color: var(--color-navy);">
                <h5 class="modal-title d-flex align-items-center gap-2 text-white" id="createAssignmentModalLabel">
                    <i class="bi bi-journal-plus"></i>
                    <span>Create New Assignment</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createAssignmentForm" novalidate>
                <div class="modal-body p-4">
                    <div id="modalAlert" class="alert alert-danger d-none py-2 small" role="alert"></div>

                    <div class="row g-3">
                        <!-- Class & Subject Pair -->
                        <div class="col-12 col-md-6">
                            <label for="modalClassSubjectSelect" class="caspa-form-label">Class &amp; Subject <span class="text-danger">*</span></label>
                            <select id="modalClassSubjectSelect" name="class_subject_id" class="form-select caspa-input" required>
                                <option value="">Select class &amp; subject…</option>
                            </select>
                            <div class="invalid-feedback">Please choose the target class and subject.</div>
                        </div>

                        <!-- Due Date -->
                        <div class="col-12 col-md-6">
                            <label for="modalDueDate" class="caspa-form-label">Due Date <span class="text-danger">*</span></label>
                            <input
                                type="date"
                                id="modalDueDate"
                                name="due_date"
                                class="form-control caspa-input"
                                min="<?= date('Y-m-d') ?>"
                                value="<?= date('Y-m-d', strtotime('+7 days')) ?>"
                                required
                            >
                            <div class="invalid-feedback">Due date is required and cannot be in the past.</div>
                        </div>

                        <!-- Title -->
                        <div class="col-12">
                            <label for="modalTitle" class="caspa-form-label">Assignment Title <span class="text-danger">*</span></label>
                            <input
                                type="text"
                                id="modalTitle"
                                name="title"
                                class="form-control caspa-input"
                                placeholder="e.g. Chapter 4 Trigonometry Problem Set"
                                required
                                maxlength="200"
                            >
                            <div class="invalid-feedback">Assignment title is required (max 200 chars).</div>
                        </div>

                        <!-- Max Marks -->
                        <div class="col-12 col-md-4">
                            <label for="modalMaxMarks" class="caspa-form-label">Max Marks</label>
                            <input
                                type="number"
                                id="modalMaxMarks"
                                name="max_marks"
                                class="form-control caspa-input"
                                min="1"
                                max="1000"
                                step="1"
                                value="100"
                            >
                        </div>

                        <!-- Description -->
                        <div class="col-12">
                            <label for="modalDescription" class="caspa-form-label">Instructions / Description</label>
                            <textarea
                                id="modalDescription"
                                name="description"
                                class="form-control caspa-input"
                                rows="3"
                                placeholder="Describe the assignment details, reference textbook pages, or submission instructions…"
                            ></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light p-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="btnSubmitAssignment" class="btn btn-caspa-primary px-4">
                        <i class="bi bi-check-lg me-1"></i>Publish Assignment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Page Script -->
<?php
$inlineJs = <<<JS
$(function () {
    let allAssignments = [];
    let myClassSubjects = [];

    const todayStr = new Date().toISOString().split('T')[0];

    // Load data
    function loadAssignments() {
        $('#assignmentsStateContainer').removeClass('d-none');
        ui.showLoading('#assignmentsStateContainer', 'Loading assignments…');
        $('#assignmentsTable').addClass('d-none');

        api.get('/api/assignments.php')
            .then(res => {
                allAssignments = res.assignments || [];
                myClassSubjects = res.my_class_subjects || [];

                populateDropdowns(myClassSubjects);
                renderFilteredAssignments();
            })
            .catch(err => {
                ui.showError('#assignmentsStateContainer', err.message || 'Failed to load assignments.');
                $('#assignmentsTable').addClass('d-none');
            });
    }

    function populateDropdowns(classSubjects) {
        // Modal dropdown
        const \$modalSel = $('#modalClassSubjectSelect');
        \$modalSel.find('option:not(:first)').remove();

        // Filter dropdown
        const \$filterSel = $('#filterClassSelect');
        \$filterSel.find('option:not(:first)').remove();

        const uniqueClasses = {};

        classSubjects.forEach(cs => {
            const label = cs.class_name + ' - Section ' + cs.class_section + ' (' + cs.subject_name + ')';
            \$modalSel.append($('<option>').val(cs.class_subject_id).text(label));

            if (!uniqueClasses[cs.class_id]) {
                uniqueClasses[cs.class_id] = cs.class_name + ' - Section ' + cs.class_section;
                \$filterSel.append($('<option>').val(cs.class_id).text(uniqueClasses[cs.class_id]));
            }
        });
    }

    function renderFilteredAssignments() {
        const q = $('#filterSearchInput').val().toLowerCase().trim();
        const filterCid = $('#filterClassSelect').val();

        const filtered = allAssignments.filter(a => {
            const matchQ = !q || (a.title && a.title.toLowerCase().includes(q))
                               || (a.subject_name && a.subject_name.toLowerCase().includes(q))
                               || (a.description && a.description.toLowerCase().includes(q));
            const matchClass = !filterCid || parseInt(a.class_id, 10) === parseInt(filterCid, 10);
            return matchQ && matchClass;
        });

        $('#assignmentsCountBadge').text(filtered.length + ' assignment' + (filtered.length === 1 ? '' : 's'));

        if (filtered.length === 0) {
            ui.showEmpty('#assignmentsStateContainer', allAssignments.length === 0
                ? 'No assignments created yet. Click "Create Assignment" to add one.'
                : 'No assignments match your search or filter.');
            $('#assignmentsTable').addClass('d-none');
            return;
        }

        ui.clearState('#assignmentsStateContainer');
        $('#assignmentsStateContainer').addClass('d-none');
        $('#assignmentsTable').removeClass('d-none');

        const \$tbody = $('#assignmentsTableBody').empty();

        filtered.forEach(a => {
            const isOverdue = a.due_date < todayStr;
            const dueBadge = isOverdue
                ? '<span class="badge border bg-warning-subtle text-dark border-warning-subtle"><i class="bi bi-exclamation-triangle-fill text-warning me-1"></i>' + a.due_date + ' (Past)</span>'
                : '<span class="badge border bg-light text-secondary"><i class="bi bi-calendar3 me-1"></i>' + a.due_date + '</span>';

            const marksBadge = '<span class="badge bg-secondary-subtle text-secondary font-monospace">' + parseFloat(a.max_marks) + ' pts</span>';

            const \$tr = $('<tr>').html(`
                <td>
                    <div class="fw-semibold text-navy fs-6 mb-1">` + escapeHtml(a.title) + `</div>
                    ` + (a.description ? '<div class="text-muted small text-truncate" style="max-width: 320px;">' + escapeHtml(a.description) + '</div>' : '') + `
                </td>
                <td>
                    <span class="badge badge-navy">` + escapeHtml(a.class_name + ' - ' + a.class_section) + `</span>
                </td>
                <td>
                    <div class="fw-semibold small text-navy">` + escapeHtml(a.subject_name) + `</div>
                    <div class="text-muted" style="font-size:0.75rem;">` + escapeHtml(a.subject_code) + `</div>
                </td>
                <td>` + dueBadge + `</td>
                <td>` + marksBadge + `</td>
                <td class="text-end">
                    <button type="button" class="btn btn-sm btn-outline-danger btn-delete-asgn py-1 px-2" data-id="` + a.id + `" data-title="` + escapeHtml(a.title) + `" title="Delete assignment">
                        <i class="bi bi-trash3"></i>
                    </button>
                </td>
            `);

            \$tbody.append(\$tr);
        });
    }

    // Filter event listeners
    $('#filterSearchInput').on('input', renderFilteredAssignments);
    $('#filterClassSelect').on('change', renderFilteredAssignments);

    // Create Assignment Submission
    $('#createAssignmentForm').on('submit', function (e) {
        e.preventDefault();
        const form = this;
        const \$alert = $('#modalAlert').addClass('d-none').text('');

        if (!form.checkValidity()) {
            form.classList.add('was-validated');
            return;
        }

        const \$btn = $('#btnSubmitAssignment');
        const origText = \$btn.html();
        \$btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Publishing…');

        const payload = {
            class_subject_id: $('#modalClassSubjectSelect').val(),
            title: $('#modalTitle').val().trim(),
            due_date: $('#modalDueDate').val(),
            max_marks: $('#modalMaxMarks').val(),
            description: $('#modalDescription').val().trim(),
        };

        api.post('/api/assignments.php', payload)
            .then(res => {
                ui.showToast(res.message || 'Assignment created successfully!', 'success');
                const modal = bootstrap.Modal.getInstance(document.getElementById('createAssignmentModal'));
                if (modal) modal.hide();
                form.reset();
                form.classList.remove('was-validated');
                loadAssignments();
            })
            .catch(err => {
                \$alert.removeClass('d-none').text(err.message || 'Failed to create assignment.');
            })
            .finally(() => {
                \$btn.prop('disabled', false).html(origText);
            });
    });

    // Delete Assignment Handler
    $(document).on('click', '.btn-delete-asgn', function () {
        const asgnId = $(this).data('id');
        const title = $(this).data('title');

        if (!confirm('Are you sure you want to delete assignment "' + title + '"? This will remove all associated marks.')) {
            return;
        }

        api.post('/api/assignment_delete.php', { id: asgnId })
            .then(res => {
                ui.showToast(res.message || 'Assignment deleted.', 'success');
                loadAssignments();
            })
            .catch(err => {
                ui.showToast(err.message || 'Failed to delete assignment.', 'danger');
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

    loadAssignments();
});
JS;
?>

<?php require_once ROOT . '/includes/layout_footer.php'; ?>
