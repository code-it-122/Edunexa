<?php
/**
 * CASPA School OS – pages/shared/announcements.php
 *
 * Announcements / Notices.
 *   principal + admin : see everything, can publish (audience targeting by role)
 *   everyone else     : read-only list of announcements targeted at their role
 *
 * Not opened directly: pages/admin/announcements.php, pages/principal/announcements.php
 * and pages/parent/notices.php check the role and then include this file.
 * Data: api/announcements.php (the API enforces who may read / publish).
 */

declare(strict_types=1);

if (!defined('ROOT')) {
    define('ROOT', dirname(__DIR__, 2));
}

require_once ROOT . '/includes/auth.php';
require_once ROOT . '/includes/db.php';

session_boot();
require_login();

$user      = current_user();
$pageRole  = $user['role'];
$isManager = in_array($user['role'], ['principal', 'admin'], true);
$pageTitle = $pageTitle ?? ($isManager ? 'Announcements' : 'Notices');

require_once ROOT . '/includes/layout_header.php';
?>

<div class="caspa-page-header d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="mb-1"><?= htmlspecialchars($pageTitle, ENT_QUOTES) ?></h1>
        <p class="caspa-breadcrumb mb-0">
            <?= $isManager
                ? 'Publish messages to teachers, students and parents.'
                : 'Messages from your school that are meant for you.' ?>
        </p>
    </div>
    <?php if ($isManager): ?>
    <button type="button" class="btn btn-caspa-primary shadow-sm d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#composeModal">
        <i class="bi bi-plus-circle-fill"></i><span>New Announcement</span>
    </button>
    <?php endif; ?>
</div>

<div id="listState" class="p-4 text-center d-none"></div>
<div id="announcementList" class="d-flex flex-column gap-3"></div>

<?php if ($isManager): ?>
<!-- Compose modal -->
<div class="modal fade" id="composeModal" tabindex="-1" aria-labelledby="composeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <form id="composeForm" novalidate>
                <div class="modal-header">
                    <h2 class="modal-title h5 text-navy fw-bold" id="composeModalLabel">New Announcement</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="composeAlert" class="alert alert-danger d-none py-2 small" role="alert"></div>

                    <div class="mb-3">
                        <label for="annTitle" class="form-label fw-semibold">Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="annTitle" maxlength="200" required>
                    </div>
                    <div class="mb-3">
                        <label for="annBody" class="form-label fw-semibold">Message <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="annBody" rows="5" maxlength="5000" required></textarea>
                    </div>
                    <fieldset class="mb-1">
                        <legend class="form-label fw-semibold fs-6">Send to <span class="text-danger">*</span></legend>
                        <div class="d-flex flex-wrap gap-3">
                            <?php foreach (['teacher' => 'Teachers', 'student' => 'Students', 'parent' => 'Parents', 'admin' => 'Admins', 'principal' => 'Principal'] as $val => $label): ?>
                            <div class="form-check">
                                <input class="form-check-input aud-check" type="checkbox" value="<?= $val ?>" id="aud_<?= $val ?>" <?= in_array($val, ['teacher', 'student', 'parent'], true) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="aud_<?= $val ?>"><?= $label ?></label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </fieldset>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-caspa-primary" id="btnPublish">
                        <span class="spinner-border spinner-border-sm d-none me-1" id="publishSpinner" aria-hidden="true"></span>
                        <span>Publish</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php ob_start(); // printed by layout_footer.php after jQuery loads ?>
$(function () {
    'use strict';

    const IS_MANAGER = <?= $isManager ? 'true' : 'false' ?>;
    const $list = $('#announcementList');
    const $state = $('#listState');

    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function fmtDateTime(dt) {
        if (!dt) return '';
        return new Date(String(dt).replace(' ', 'T')).toLocaleString('en-US',
            { day: 'numeric', month: 'short', year: 'numeric', hour: 'numeric', minute: '2-digit' });
    }

    function render(items) {
        $list.empty();
        if (!items.length) {
            $state.removeClass('d-none');
            ui.showEmpty($state, IS_MANAGER
                ? 'No announcements yet. Click "New Announcement" to publish the first one.'
                : 'No notices for you right now.');
            return;
        }
        $state.addClass('d-none');
        items.forEach(a => {
            const aud = IS_MANAGER
                ? '<div class="d-flex flex-wrap gap-1 mt-2">' + (a.audience || []).map(r =>
                    `<span class="badge bg-light text-secondary border fw-normal text-capitalize">${escapeHtml(r)}</span>`).join('') + '</div>'
                : '';
            $list.append(`
                <div class="caspa-card p-3">
                    <h2 class="h6 mb-1 text-navy fw-bold">${escapeHtml(a.title)}</h2>
                    <div class="text-muted mb-2" style="font-size: 0.78rem;">
                        <i class="bi bi-person me-1"></i>${escapeHtml(a.author_name)} &bull;
                        <i class="bi bi-clock me-1"></i>${escapeHtml(fmtDateTime(a.published_at))}
                    </div>
                    <p class="text-secondary mb-0" style="white-space: pre-wrap; line-height: 1.55;">${escapeHtml(a.body)}</p>
                    ${aud}
                </div>`);
        });
    }

    function load() {
        $state.removeClass('d-none');
        ui.showLoading($state);
        api.get('/api/announcements.php', { limit: 50 })
            .then(data => { ui.clearState($state); render(data.announcements || []); })
            .catch(err => {
                $list.empty();
                $state.removeClass('d-none');
                ui.showError($state, 'Could not load announcements: ' + (err.message || 'Unknown error'));
            });
    }

    <?php if ($isManager): ?>
    const $form = $('#composeForm');
    const $alert = $('#composeAlert');

    $('#composeModal').on('show.bs.modal', function () {
        $alert.addClass('d-none').text('');
    });

    $form.on('submit', function (e) {
        e.preventDefault();
        $alert.addClass('d-none').text('');

        const title = $('#annTitle').val().trim();
        const body = $('#annBody').val().trim();
        const audience = $('.aud-check:checked').map(function () { return this.value; }).get();

        if (!title) { return $alert.removeClass('d-none').text('Please enter a title.'); }
        if (!body) { return $alert.removeClass('d-none').text('Please enter the message.'); }
        if (!audience.length) { return $alert.removeClass('d-none').text('Choose at least one audience.'); }

        $('#btnPublish').prop('disabled', true);
        $('#publishSpinner').removeClass('d-none');

        api.post('/api/announcements.php', { title: title, body: body, audience: audience })
            .then(() => {
                bootstrap.Modal.getInstance(document.getElementById('composeModal')).hide();
                $form[0].reset();
                $('#aud_teacher, #aud_student, #aud_parent').prop('checked', true);
                ui.showToast('Announcement published.', 'success');
                load();
            })
            .catch(err => $alert.removeClass('d-none').text(err.message || 'Could not publish. Please try again.'))
            .finally(() => {
                $('#btnPublish').prop('disabled', false);
                $('#publishSpinner').addClass('d-none');
            });
    });
    <?php endif; ?>

    load();
});
<?php $inlineJs = ob_get_clean(); ?>

<?php require_once ROOT . '/includes/layout_footer.php'; ?>
