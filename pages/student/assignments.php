<?php
/**
 * CASPA School OS – pages/student/assignments.php
 *
 * Student Assignments View:
 * Cards layout with due dates, overdue highlighted in Warning color (#D97706).
 */

declare(strict_types=1);

define('ROOT', dirname(__DIR__, 2));

require_once ROOT . '/includes/auth.php';
require_once ROOT . '/includes/db.php';

session_boot();
require_login();
require_role('student');

$user       = current_user();
$pageTitle  = 'Assignments';
$pageRole   = 'student';
$activeMenu = 'Assignments';

// Fetch student enrollment details for header info
$pdo = db();
$studentStmt = $pdo->prepare("
    SELECT s.id, s.admission_number, c.id as class_id, c.name as class_name, c.section as class_section
    FROM students s
    JOIN classes c ON c.id = s.class_id
    WHERE s.user_id = :uid
    LIMIT 1
");
$studentStmt->execute([':uid' => (int) $user['id']]);
$studentInfo = $studentStmt->fetch();

require_once ROOT . '/includes/layout_header.php';
?>

<div class="caspa-page-header d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-4">
    <div>
        <h1 class="mb-1">My Assignments</h1>
        <p class="caspa-breadcrumb mb-0">
            Track and complete your homework and class projects
            <?php if ($studentInfo): ?>
                &mdash; <span class="badge bg-light text-navy border fw-medium px-2 py-1"><?= htmlspecialchars($studentInfo['class_name'] . ' - ' . $studentInfo['class_section'], ENT_QUOTES) ?></span>
            <?php endif; ?>
        </p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <span id="assignmentsCountSummary" class="badge bg-secondary-subtle text-secondary px-3 py-2 fs-6 fw-normal">
            Loading…
        </span>
    </div>
</div>

<!-- Filter Bar -->
<div class="caspa-card mb-4 p-3">
    <div class="row g-2 align-items-center">
        <div class="col-12 col-md-5">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                <input type="text" id="filterSearchInput" class="form-control caspa-input border-start-0 ps-0" placeholder="Search by title, subject, or description…">
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-4">
            <select id="filterSubjectSelect" class="form-select caspa-input">
                <option value="">All Subjects</option>
            </select>
        </div>
        <div class="col-12 col-sm-6 col-md-3">
            <select id="filterStatusSelect" class="form-select caspa-input">
                <option value="all">All Statuses</option>
                <option value="pending">Upcoming &amp; Today</option>
                <option value="overdue">Overdue Only</option>
            </select>
        </div>
    </div>
</div>

<!-- State Container for Loading / Error / Empty -->
<div id="cardsStateContainer" class="p-5 text-center d-none"></div>

<!-- Assignments Cards Grid -->
<div class="row g-3" id="assignmentsCardsRow">
    <!-- Populated via AJAX -->
</div>

<script>
$(function () {
    'use strict';

    let allAssignments = [];
    const $row = $('#assignmentsCardsRow');
    const $state = $('#cardsStateContainer');
    const $search = $('#filterSearchInput');
    const $subjectSelect = $('#filterSubjectSelect');
    const $statusSelect = $('#filterStatusSelect');
    const $countSummary = $('#assignmentsCountSummary');

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function formatDate(dateStr) {
        if (!dateStr) return '';
        const parts = dateStr.split('-');
        if (parts.length === 3) {
            const date = new Date(parts[0], parts[1] - 1, parts[2]);
            return date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        }
        return dateStr;
    }

    function getTodayString() {
        const d = new Date();
        const year = d.getFullYear();
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    function loadAssignments() {
        $row.empty();
        $state.removeClass('d-none');
        ui.showLoading($state);

        api.get('/api/assignments.php')
            .then(data => {
                allAssignments = (data && data.assignments) ? data.assignments : [];
                ui.clearState($state);
                $state.addClass('d-none');

                populateSubjectOptions();
                renderCards();
            })
            .catch(err => {
                $state.removeClass('d-none');
                ui.showError($state, err.message || 'Failed to load assignments.');
                $countSummary.text('Error loading');
            });
    }

    function populateSubjectOptions() {
        const subjects = new Set();
        allAssignments.forEach(a => {
            if (a.subject_name) {
                subjects.add(a.subject_name);
            }
        });

        $subjectSelect.find('option:not(:first)').remove();
        Array.from(subjects).sort().forEach(sub => {
            $subjectSelect.append(`<option value="${escapeHtml(sub)}">${escapeHtml(sub)}</option>`);
        });
    }

    function renderCards() {
        const q = $search.val().trim().toLowerCase();
        const sub = $subjectSelect.val();
        const status = $statusSelect.val();
        const todayStr = getTodayString();

        const filtered = allAssignments.filter(item => {
            const isOverdue = item.due_date < todayStr;
            const isDueToday = item.due_date === todayStr;

            if (status === 'overdue' && !isOverdue) return false;
            if (status === 'pending' && isOverdue) return false;

            if (sub && item.subject_name !== sub) return false;

            if (q) {
                const title = (item.title || '').toLowerCase();
                const desc = (item.description || '').toLowerCase();
                const subject = (item.subject_name || '').toLowerCase();
                const teacher = (item.creator_name || '').toLowerCase();
                if (!title.includes(q) && !desc.includes(q) && !subject.includes(q) && !teacher.includes(q)) {
                    return false;
                }
            }

            return true;
        });

        // Update count summary
        let overdueCount = 0;
        allAssignments.forEach(a => {
            if (a.due_date < todayStr) overdueCount++;
        });

        let summaryHtml = `${allAssignments.length} total`;
        if (overdueCount > 0) {
            summaryHtml += ` &bull; <span style="color: var(--color-warning); font-weight: 600;">${overdueCount} overdue</span>`;
        }
        $countSummary.html(summaryHtml);

        $row.empty();

        if (filtered.length === 0) {
            $state.removeClass('d-none');
            if (allAssignments.length === 0) {
                ui.showEmpty($state, 'No assignments posted yet for your class. Great job staying caught up!');
            } else {
                ui.showEmpty($state, 'No assignments match your search or filter criteria.');
            }
            return;
        }

        $state.addClass('d-none');

        filtered.forEach(item => {
            const isOverdue = item.due_date < todayStr;
            const isDueToday = item.due_date === todayStr;

            let borderStyle = 'border: 1px solid var(--color-border);';
            let headerBadge = '';
            let dueBadge = '';

            if (isOverdue) {
                borderStyle = 'border: 2px solid var(--color-warning); background-color: #FFFDF8;';
                headerBadge = `
                    <span class="badge" style="background-color: var(--color-warning); color: #fff;">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i> Overdue
                    </span>`;
                dueBadge = `
                    <span style="color: var(--color-warning); font-weight: 700;">
                        <i class="bi bi-clock-history me-1"></i> Due ${formatDate(item.due_date)}
                    </span>`;
            } else if (isDueToday) {
                borderStyle = 'border: 1px solid var(--color-sky); background-color: #F8FAFF;';
                headerBadge = `
                    <span class="badge bg-primary text-white">
                        <i class="bi bi-alarm me-1"></i> Due Today
                    </span>`;
                dueBadge = `
                    <span class="text-primary fw-semibold">
                        <i class="bi bi-calendar-check me-1"></i> Today (${formatDate(item.due_date)})
                    </span>`;
            } else {
                headerBadge = `
                    <span class="badge bg-secondary-subtle text-secondary border">
                        Upcoming
                    </span>`;
                dueBadge = `
                    <span class="text-muted">
                        <i class="bi bi-calendar3 me-1"></i> Due ${formatDate(item.due_date)}
                    </span>`;
            }

            const descText = item.description 
                ? escapeHtml(item.description) 
                : '<em class="text-muted">No description provided.</em>';

            const cardHtml = `
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="caspa-card h-100 d-flex flex-column p-3" style="${borderStyle}">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                            <span class="badge bg-teal-subtle text-teal fw-semibold" style="background-color: rgba(23, 107, 135, 0.1); color: var(--color-teal);">
                                <i class="bi bi-book me-1"></i>${escapeHtml(item.subject_name)}
                            </span>
                            ${headerBadge}
                        </div>

                        <h5 class="card-title text-navy fw-bold mb-2 fs-6">
                            ${escapeHtml(item.title)}
                        </h5>

                        <div class="card-text text-muted small mb-3 flex-grow-1" style="white-space: pre-wrap; max-height: 120px; overflow-y: auto;">
                            ${descText}
                        </div>

                        <div class="pt-2 border-top mt-auto small">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                ${dueBadge}
                                <span class="badge bg-light text-dark border">
                                    ${Number(item.max_marks)} pts
                                </span>
                            </div>
                            <div class="text-muted" style="font-size: 0.75rem;">
                                <i class="bi bi-person me-1"></i>Teacher: ${escapeHtml(item.creator_name)}
                            </div>
                        </div>
                    </div>
                </div>
            `;
            $row.append(cardHtml);
        });
    }

    $search.on('input', renderCards);
    $subjectSelect.on('change', renderCards);
    $statusSelect.on('change', renderCards);

    // Initial load
    loadAssignments();
});
</script>

<?php require_once ROOT . '/includes/layout_footer.php'; ?>
