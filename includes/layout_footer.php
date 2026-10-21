<?php
/**
 * CASPA School OS – HTML layout footer
 *
 * Must be included at the bottom of every page that uses layout_header.php.
 * Closes the tags opened in layout_header.php.
 */
declare(strict_types=1);
?>
    </div><!-- /.container-fluid -->
</main><!-- /#mainContent -->

</div><!-- /.caspa-wrapper -->

<!-- ===================== FOOTER BAR ===================== -->
<footer class="caspa-footer">
    <div class="container-fluid d-flex justify-content-between align-items-center">
        <span>&copy; <?= date('Y') ?> CASPA School OS &mdash; All rights reserved.</span>
        <span class="text-muted small">v0.1.0</span>
    </div>
</footer>

<!-- jQuery -->
<script
    src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"
    integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo="
    crossorigin="anonymous"
></script>

<!-- Bootstrap 5 JS bundle (includes Popper) -->
<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-YvpcrYf0tY3lHB60NNkmXc4s9bIOgUxi8T/jzmA5e7XoPRMNb3OdoSt17f3PqaB"
    crossorigin="anonymous"
></script>

<!-- CASPA JS -->
<script src="<?= APP_URL ?>/assets/js/ui.js"></script>
<script src="<?= APP_URL ?>/assets/js/api.js"></script>

<!-- Desktop Sidebar Toggle Script -->
<script>
$(function () {
    // Desktop sidebar collapse toggle
    const $sidebar = $('#sidebarOffcanvas');
    const $wrapper = $('.caspa-wrapper');
    const storageKey = 'caspa_sidebar_collapsed';

    // Restore preference
    if (localStorage.getItem(storageKey) === '1') {
        $sidebar.addClass('collapsed');
        $wrapper.addClass('sidebar-collapsed');
    }

    $('#sidebarToggleBtn').on('click', function () {
        $sidebar.toggleClass('collapsed');
        $wrapper.toggleClass('sidebar-collapsed');
        const isCollapsed = $sidebar.hasClass('collapsed');
        localStorage.setItem(storageKey, isCollapsed ? '1' : '0');
    });
});
</script>

<?php if (isset($extraJs) && is_array($extraJs)): ?>
    <?php foreach ($extraJs as $jsFile): ?>
    <script src="<?= htmlspecialchars($jsFile, ENT_QUOTES) ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Inline page scripts (set $inlineJs in the page before footer) -->
<?php if (!empty($inlineJs)): ?>
<script><?= $inlineJs ?></script>
<?php endif; ?>

</body>
</html>
