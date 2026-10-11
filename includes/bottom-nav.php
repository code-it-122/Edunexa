<?php
// includes/bottom-nav.php - Mobile Navigation Bar (PDF Section 7)
?>
<nav class="d-flex d-lg-none position-fixed bottom-0 start-0 end-0 bg-white border-top justify-content-around align-items-center shadow-lg" style="height: 60px; z-index: 1030;">
    <a href="index.php?page=dashboard" class="d-flex flex-column align-items-center text-decoration-none small" style="color: var(--caspa-teal);">
        <i class="bi bi-house-door-fill fs-5"></i>
        <span style="font-size: 10px; font-weight: 600;">Home</span>
    </a>

    <a href="index.php?page=choose-workspace" class="d-flex flex-column align-items-center text-decoration-none small text-muted">
        <i class="bi bi-grid-fill fs-5"></i>
        <span style="font-size: 10px; font-weight: 600;">Roles</span>
    </a>

    <!-- Center CASPA Floating Trigger -->
    <button type="button" id="btnBottomCaspa" class="btn btn-caspa-purple rounded-circle d-flex align-items-center justify-content-center shadow" style="width: 44px; height: 44px; margin-top: -15px;">
        <i class="bi bi-stars fs-5"></i>
    </button>

    <a href="index.php?page=select-campus" class="d-flex flex-column align-items-center text-decoration-none small text-muted">
        <i class="bi bi-building fs-5"></i>
        <span style="font-size: 10px; font-weight: 600;">Campus</span>
    </a>

    <a href="#" class="d-flex flex-column align-items-center text-decoration-none small text-muted btn-action-logout">
        <i class="bi bi-box-arrow-right fs-5"></i>
        <span style="font-size: 10px; font-weight: 600;">Logout</span>
    </a>
</nav>
