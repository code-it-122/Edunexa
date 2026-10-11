<?php
// includes/caspa-drawer.php - CASPA AI Assistant Persistent Drawer (PDF Section 14)
$user = getCurrentUser();
$roleTitle = htmlspecialchars($user['active_role']['title'] ?? 'Student');
?>
<!-- CASPA Side Drawer -->
<div id="caspaDrawer" class="caspa-drawer-container">
    <div class="caspa-drawer-header d-flex align-items-center justify-content-between p-3 text-white" style="background-color: var(--caspa-purple);">
        <div class="d-flex align-items-center gap-2">
            <div class="p-1 rounded bg-white bg-opacity-25 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                <i class="bi bi-stars fs-5"></i>
            </div>
            <div>
                <div class="fw-bold fs-6">CASPA AI</div>
                <div style="font-size: 11px; opacity: 0.85;"><?= $roleTitle ?> Intelligence Mode</div>
            </div>
        </div>
        <button type="button" id="btnCloseCaspa" class="btn btn-link text-white p-0">
            <i class="bi bi-x-lg fs-5"></i>
        </button>
    </div>

    <!-- Messages Container -->
    <div id="caspaMessageFeed" class="p-3 flex-grow-1 overflow-y-auto d-flex flex-column gap-3" style="background-color: #FAF9FE; max-height: calc(100vh - 200px);">
        <!-- Welcome Message -->
        <div class="caspa-msg ai">
            <div class="msg-bubble">
                Hello <strong><?= htmlspecialchars($user['full_name']) ?></strong>! I am <strong>CASPA AI</strong>, your school intelligence assistant tuned for the <strong><?= $roleTitle ?> Workspace</strong>. What would you like to review today?
            </div>
            <div class="msg-time"><?= date('h:i A') ?> • <span class="text-purple fw-bold">Active</span></div>
        </div>
    </div>

    <!-- Suggested Prompts Chips -->
    <div class="p-2 bg-white border-top">
        <div class="text-muted fw-bold mb-1" style="font-size: 10px; letter-spacing: 0.5px; color: var(--caspa-purple) !important;">
            SUGGESTED ACTIONS (<?= strtoupper($roleTitle) ?>):
        </div>
        <div id="caspaSuggestedChips" class="d-flex flex-wrap gap-1">
            <!-- Loaded dynamically via AJAX -->
        </div>
    </div>

    <!-- Query Input Bar -->
    <div class="p-3 bg-white border-top">
        <form id="caspaChatForm" class="d-flex gap-2">
            <input type="text" id="caspaQueryInput" class="form-control form-control-sm" placeholder="Ask CASPA (<?= $roleTitle ?> mode)..." required>
            <button type="submit" class="btn btn-caspa-purple btn-sm px-3">
                <i class="bi bi-send-fill"></i>
            </button>
        </form>
    </div>
</div>

<style>
.caspa-drawer-container {
    position: fixed;
    top: 0;
    right: -420px;
    bottom: 0;
    width: 380px;
    max-width: 100vw;
    background-color: #FFFFFF;
    box-shadow: -5px 0 25px rgba(0,0,0,0.15);
    z-index: 1060;
    display: flex;
    flex-direction: column;
    transition: right 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.caspa-drawer-container.show {
    right: 0;
}
.caspa-msg {
    display: flex;
    flex-direction: column;
    max-width: 85%;
}
.caspa-msg.user {
    align-self: flex-end;
}
.caspa-msg.user .msg-bubble {
    background-color: var(--caspa-teal);
    color: #FFFFFF;
    border-radius: 14px 14px 2px 14px;
}
.caspa-msg.ai {
    align-self: flex-start;
}
.caspa-msg.ai .msg-bubble {
    background-color: #FFFFFF;
    color: var(--caspa-text);
    border: 1px solid #E9D5FF;
    border-radius: 14px 14px 14px 2px;
}
.msg-bubble {
    padding: 10px 14px;
    font-size: 13px;
    line-height: 1.5;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}
.msg-time {
    font-size: 10px;
    color: var(--caspa-muted);
    margin-top: 3px;
}
.btn-prompt-chip {
    background-color: var(--caspa-purple-light);
    border: 1px solid #E9D5FF;
    color: var(--caspa-purple);
    font-size: 11px;
    font-weight: 500;
    border-radius: 16px;
    padding: 3px 10px;
    cursor: pointer;
    text-align: left;
    transition: all 0.15s ease;
}
.btn-prompt-chip:hover {
    background-color: var(--caspa-purple);
    color: #FFFFFF;
}
</style>
