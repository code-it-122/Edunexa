<?php
// includes/command-palette.php - Command Palette Modal (Ctrl+K)
?>
<div class="modal fade" id="commandPaletteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 560px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="p-3 border-bottom d-flex align-items-center gap-2">
                <i class="bi bi-search text-teal fs-5" style="color: var(--caspa-teal);"></i>
                <input type="text" id="cmdSearchInput" class="form-control border-0 shadow-none fs-6" placeholder="Type a command or jump to module... (Esc to close)">
                <span class="badge bg-light text-muted border">ESC</span>
            </div>
            <div class="p-2" style="max-height: 320px; overflow-y: auto;">
                <div class="cmd-item p-2 rounded d-flex align-items-center justify-content-between" style="cursor: pointer;" onclick="location.href='index.php?page=dashboard'">
                    <div class="d-flex align-items-center gap-3">
                        <div class="p-2 rounded bg-light text-teal"><i class="bi bi-speedometer2"></i></div>
                        <div>
                            <div class="fw-bold small cmd-title">School Command Center</div>
                            <div class="text-muted small cmd-tag" style="font-size: 11px;">Leadership Dashboard</div>
                        </div>
                    </div>
                    <i class="bi bi-arrow-right text-muted"></i>
                </div>

                <div class="cmd-item p-2 rounded d-flex align-items-center justify-content-between" style="cursor: pointer;" onclick="location.href='index.php?page=choose-workspace'">
                    <div class="d-flex align-items-center gap-3">
                        <div class="p-2 rounded bg-light text-teal"><i class="bi bi-grid-fill"></i></div>
                        <div>
                            <div class="fw-bold small cmd-title">Switch Workspace Role</div>
                            <div class="text-muted small cmd-tag" style="font-size: 11px;">RBAC Workspaces</div>
                        </div>
                    </div>
                    <i class="bi bi-arrow-right text-muted"></i>
                </div>

                <div class="cmd-item p-2 rounded d-flex align-items-center justify-content-between" style="cursor: pointer;" onclick="location.href='index.php?page=select-campus'">
                    <div class="d-flex align-items-center gap-3">
                        <div class="p-2 rounded bg-light text-teal"><i class="bi bi-building"></i></div>
                        <div>
                            <div class="fw-bold small cmd-title">Switch Campus Location</div>
                            <div class="text-muted small cmd-tag" style="font-size: 11px;">Multi-Campus Network</div>
                        </div>
                    </div>
                    <i class="bi bi-arrow-right text-muted"></i>
                </div>

                <div class="cmd-item p-2 rounded d-flex align-items-center justify-content-between trigger-caspa" style="cursor: pointer;">
                    <div class="d-flex align-items-center gap-3">
                        <div class="p-2 rounded bg-light" style="color: var(--caspa-purple);"><i class="bi bi-stars"></i></div>
                        <div>
                            <div class="fw-bold small cmd-title">Ask CASPA AI Assistant</div>
                            <div class="text-muted small cmd-tag" style="font-size: 11px;">School Intelligence Engine</div>
                        </div>
                    </div>
                    <i class="bi bi-arrow-right text-muted"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.cmd-item:hover {
    background-color: var(--caspa-surface);
}
</style>
