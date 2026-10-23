<?php
/**
 * CASPA School OS – pages/admin/announcements.php
 *
 * Announcements Management
 * Access: Principal & Admin (view all published, compose new).
 */

declare(strict_types=1);

if (!defined('ROOT')) {
    define('ROOT', dirname(__DIR__, 2));
}

require_once ROOT . '/includes/auth.php';
require_once ROOT . '/includes/db.php';

session_boot();
require_login();
require_role(['admin', 'principal']);

$user      = current_user();
$pageTitle = 'Announcements';
$pageRole  = $user['role'];

require_once ROOT . '/includes/layout_header.php';
?>

<div class="caspa-page-header d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="mb-1">School Announcements</h1>
        <p class="caspa-breadcrumb mb-0">
            Publish circulars, updates, and school notices targeted to specific user roles.
        </p>
    </div>
    <button type="button" class="btn btn-caspa-primary shadow-sm d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#newAnnouncementModal">
        <i class="bi bi-plus-circle-fill"></i>
        <span>New Announcement</span>
    </button>
</div>

<!-- State Container for Loading / Error / Empty -->
<div id="annStateContainer" class="p-5 text-center d-none"></div>

<!-- Announcements Table / List Card -->
<div class="caspa-card p-0 overflow-hidden mb-4" id="annCardContainer">
    <div class="table-responsive">
        <table class="table caspa-table align-middle mb-0" id="announcementsTable">
            <thead>
                <tr>
                    <th scope="col" style="width: 25%;">Title</th>
                    <th scope="col">Content</th>
                    <th scope="col" style="width: 18%;">Audience</th>
                    <th scope="col" style="width: 14%;">Published</th>
                    <th scope="col" style="width: 10%;">Author</th>
                </tr>
            </thead>
            <tbody id="announcementsTableBody">
                <!-- Populated via AJAX -->
            </tbody>
        </table>
    </div>
    <div id="annEmptyState" class="p-5 text-center text-muted d-none">
        <i class="bi bi-megaphone fs-1 d-block mb-2"></i>
        <h5 class="fw-bold text-navy">No Announcements Found</h5>
        <p class="mb-0">No announcements have been published yet. Click "New Announcement" to create one.</p>
    </div>
</div>

<!-- New Announcement Modal -->
<div class="modal fade" id="newAnnouncementModal" tabindex="-1" aria-labelledby="newAnnouncementModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold text-navy" id="newAnnouncementModalLabel">New Announcement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="newAnnouncementForm" novalidate>
                <div class="modal-body">
                    <div id="modalAlert" class="alert alert-danger d-none py-2 mb-3"></div>

                    <div class="mb-3">
                        <label for="annTitle" class="caspa-form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control caspa-input" id="annTitle" name="title"
                               maxlength="200" placeholder="e.g. Mid-Term Examination Schedule" required>
                    </div>

                    <div class="mb-3">
                        <label for="annBody" class="caspa-form-label">Body <span class="text-danger">*</span></label>
                        <textarea class="form-control caspa-input" id="annBody" name="body" rows="4"
                                  placeholder="Write the announcement details here…" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="caspa-form-label d-block">Target Audience <span class="text-danger">*</span></label>
                        <div class="d-flex flex-wrap gap-3 mt-1">
                            <?php foreach (['teacher' => 'Teachers', 'student' => 'Students', 'parent' => 'Parents', 'principal' => 'Principal', 'admin' => 'Admin'] as $val => $lbl): ?>
                            <div class="form-check">
                                <input class="form-check-input ann-aud-check" type="checkbox"
                                       value="<?= htmlspecialchars($val, ENT_QUOTES) ?>" id="aud_<?= htmlspecialchars($val, ENT_QUOTES) ?>"
                                       <?= in_array($val, ['teacher','student','parent'], true) ? 'checked' : '' ?>>
                                <label class="form-check-label small" for="aud_<?= htmlspecialchars($val, ENT_QUOTES) ?>"><?= htmlspecialchars($lbl, ENT_QUOTES) ?></label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label for="annExpires" class="caspa-form-label">Expiry Date <span class="text-muted fw-normal">(optional)</span></label>
                        <input type="date" class="form-control caspa-input" id="annExpires" name="expires_at"
                               min="<?= date('Y-m-d') ?>">
                        <div class="form-text small">Notice will automatically hide after this date.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-caspa-primary d-flex align-items-center gap-1" id="btnSaveAnn">
                        <span id="saveAnnSpinner" class="spinner-border spinner-border-sm d-none" role="status"></span>
                        <i class="bi bi-send-fill" id="saveAnnIcon"></i>
                        <span>Publish</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(function () {
    'use strict';

    const $state = $('#annStateContainer');
    const $tbody = $('#announcementsTableBody');
    const $empty = $('#annEmptyState');
    const $table = $('#announcementsTable');

    function escapeHtml(str) {
        if (!str) return '';
        return $('<div>').text(String(str)).html();
    }

    function formatDate(dtStr) {
        if (!dtStr) return '—';
        const d = new Date(dtStr.replace(' ', 'T'));
        if (isNaN(d.getTime())) return dtStr;
        return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    }

    function loadAnnouncements() {
        $state.removeClass('d-none');
        ui.showLoading($state);
        $tbody.empty();
        $empty.addClass('d-none');

        api.get('/api/announcements.php')
            .then(res => {
                ui.clearState($state);
                $state.addClass('d-none');

                const list = (res && res.announcements) ? res.announcements : (Array.isArray(res) ? res : []);
                if (list.length === 0) {
                    $table.addClass('d-none');
                    $empty.removeClass('d-none');
                    return;
                }

                $table.removeClass('d-none');
                $empty.addClass('d-none');

                list.forEach(a => {
                    const audBadges = (a.audience || '').split(',').map(r => {
                        const colors = {
                            principal: 'bg-danger-subtle text-danger',
                            admin:     'bg-dark-subtle text-dark',
                            teacher:   'bg-primary-subtle text-primary',
                            student:   'bg-info-subtle text-info',
                            parent:    'bg-warning-subtle text-warning',
                        };
                        const cls = colors[r.trim()] || 'bg-secondary-subtle text-secondary';
                        return `<span class="badge ${cls} border me-1">${escapeHtml(r.trim())}</span>`;
                    }).join('');

                    const tr = `
                        <tr>
                            <td class="fw-semibold text-navy">${escapeHtml(a.title)}</td>
                            <td>
                                <p class="mb-0 text-secondary small" style="max-height: 48px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">
                                    ${escapeHtml(a.body)}
                                </p>
                            </td>
                            <td>${audBadges}</td>
                            <td class="text-muted small">${formatDate(a.published_at || a.created_at)}</td>
                            <td class="text-muted small">${escapeHtml(a.author_name || 'Admin')}</td>
                        </tr>
                    `;
                    $tbody.append(tr);
                });
            })
            .catch(err => {
                ui.clearState($state);
                $state.removeClass('d-none');
                ui.showError($state, 'Failed to load announcements: ' + (err.message || 'Unknown error'));
            });
    }

    // Compose announcement
    $('#newAnnouncementForm').on('submit', function (e) {
        e.preventDefault();
        const title = $('#annTitle').val().trim();
        const body = $('#annBody').val().trim();
        const aud = $('.ann-aud-check:checked').map(function () { return this.value; }).get().join(',');
        const exp = $('#annExpires').val().trim();

        if (!title) {
            $('#modalAlert').text('Please enter a title.').removeClass('d-none');
            return;
        }
        if (!body) {
            $('#modalAlert').text('Please enter announcement body.').removeClass('d-none');
            return;
        }
        if (!aud) {
            $('#modalAlert').text('Please select at least one target audience.').removeClass('d-none');
            return;
        }

        $('#modalAlert').addClass('d-none');
        $('#btnSaveAnn').prop('disabled', true);
        $('#saveAnnSpinner').removeClass('d-none');
        $('#saveAnnIcon').addClass('d-none');

        api.post('/api/announcements.php', {
            title: title,
            body: body,
            audience: aud,
            expires_at: exp
        })
        .then(() => {
            $('#btnSaveAnn').prop('disabled', false);
            $('#saveAnnSpinner').addClass('d-none');
            $('#saveAnnIcon').removeClass('d-none');
            bootstrap.Modal.getInstance(document.getElementById('newAnnouncementModal')).hide();
            $('#newAnnouncementForm')[0].reset();
            ui.showToast('Announcement published successfully.', 'success');
            loadAnnouncements();
        })
        .catch(err => {
            $('#btnSaveAnn').prop('disabled', false);
            $('#saveAnnSpinner').addClass('d-none');
            $('#saveAnnIcon').removeClass('d-none');
            $('#modalAlert').text(err.message || 'Failed to publish announcement.').removeClass('d-none');
        });
    });

    loadAnnouncements();
});
</script>

<?php require_once ROOT . '/includes/layout_footer.php'; ?>
