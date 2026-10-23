<?php
/**
 * CASPA School OS – pages/parent/notices.php
 *
 * Parent Notices View:
 * Lists school notices and circulars targeted to parents.
 */

declare(strict_types=1);

define('ROOT', dirname(__DIR__, 2));

require_once ROOT . '/includes/auth.php';
require_once ROOT . '/includes/db.php';

session_boot();
require_login();
require_role('parent');

$user       = current_user();
$pageTitle  = 'Notices & Circulars';
$pageRole   = 'parent';
$activeMenu = 'Notices';

require_once ROOT . '/includes/layout_header.php';
?>

<div class="caspa-page-header d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="mb-1">Notices &amp; Circulars</h1>
        <p class="caspa-breadcrumb mb-0">
            Important announcements, exam timetables, and school circulars for parents.
        </p>
    </div>
    <a href="<?= APP_URL ?>/pages/parent/dashboard.php" class="btn btn-outline-secondary bg-white shadow-sm d-flex align-items-center gap-2 px-3">
        <i class="bi bi-speedometer2"></i>
        <span>Dashboard</span>
    </a>
</div>

<!-- State Container for Loading / Error -->
<div id="noticesStateContainer" class="p-5 text-center d-none"></div>

<!-- Notices Container -->
<div id="noticesContentContainer">
    <div class="row g-3" id="noticesListRow">
        <!-- Populated via JS -->
    </div>
    <div id="noticesEmptyState" class="caspa-card p-5 text-center text-muted d-none">
        <i class="bi bi-bell-slash fs-1 d-block mb-2"></i>
        <h5 class="fw-bold text-navy">No Notices Available</h5>
        <p class="mb-0">There are no active school notices or circulars at this moment.</p>
    </div>
</div>

<!-- Notice Detail Modal -->
<div class="modal fade" id="noticeDetailModal" tabindex="-1" aria-labelledby="noticeDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold text-navy" id="noticeDetailModalLabel">Notice</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex align-items-center gap-2 text-muted small mb-3">
                    <span id="modalNoticeDate"><i class="bi bi-calendar3 me-1"></i>—</span>
                    <span>&bull;</span>
                    <span id="modalNoticeAuthor"><i class="bi bi-person me-1"></i>—</span>
                </div>
                <div id="modalNoticeBody" class="text-secondary" style="white-space: pre-wrap; line-height: 1.6;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
$(function () {
    'use strict';

    const $state = $('#noticesStateContainer');
    const $row = $('#noticesListRow');
    const $empty = $('#noticesEmptyState');
    let noticesList = [];

    function formatDate(dateStr) {
        if (!dateStr) return '—';
        const d = new Date(dateStr.replace(' ', 'T'));
        if (isNaN(d.getTime())) return dateStr;
        return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    }

    function escapeHtml(str) {
        if (!str) return '';
        return $('<div>').text(String(str)).html();
    }

    $state.removeClass('d-none');
    ui.showLoading($state);

    api.get('/api/dashboard_parent.php')
        .then(data => {
            ui.clearState($state);
            $state.addClass('d-none');

            noticesList = data.notices || [];

            if (noticesList.length === 0) {
                $row.addClass('d-none');
                $empty.removeClass('d-none');
                return;
            }

            $row.removeClass('d-none');
            $empty.addClass('d-none');
            $row.empty();

            noticesList.forEach((n, idx) => {
                const cardHtml = `
                    <div class="col-12 col-md-6">
                        <div class="caspa-card h-100 p-4 d-flex flex-column justify-content-between shadow-sm cursor-pointer notice-card" data-index="${idx}">
                            <div>
                                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1">Notice</span>
                                    <small class="text-muted">${formatDate(n.published_at || n.created_at)}</small>
                                </div>
                                <h5 class="fw-bold text-navy mb-2">${escapeHtml(n.title)}</h5>
                                <p class="text-secondary small mb-3" style="max-height: 60px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical;">
                                    ${escapeHtml(n.body)}
                                </p>
                            </div>
                            <div class="d-flex justify-content-between align-items-center pt-2 border-top text-muted small">
                                <span><i class="bi bi-person me-1"></i>${escapeHtml(n.author_name || 'Administration')}</span>
                                <span class="text-teal fw-medium">View Circular &rarr;</span>
                            </div>
                        </div>
                    </div>
                `;
                $row.append(cardHtml);
            });

            $('.notice-card').on('click', function () {
                const idx = $(this).data('index');
                const notice = noticesList[idx];
                if (!notice) return;

                $('#noticeDetailModalLabel').text(notice.title);
                $('#modalNoticeDate').html('<i class="bi bi-calendar3 me-1"></i>' + formatDate(notice.published_at || notice.created_at));
                $('#modalNoticeAuthor').html('<i class="bi bi-person me-1"></i>' + escapeHtml(notice.author_name || 'Administration'));
                $('#modalNoticeBody').text(notice.body);

                const modal = new bootstrap.Modal(document.getElementById('noticeDetailModal'));
                modal.show();
            });
        })
        .catch(err => {
            ui.clearState($state);
            $state.removeClass('d-none');
            ui.showError($state, 'Failed to load notices: ' + (err.message || 'Unknown error'));
        });
});
</script>

<?php require_once ROOT . '/includes/layout_footer.php'; ?>
