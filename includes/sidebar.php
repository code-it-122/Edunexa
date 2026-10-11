<?php
// includes/sidebar.php - Collapsible Left Sidebar with Role Filtering
$user = getCurrentUser();
$activeRoleKey = $user['active_role']['key'] ?? 'student';
$roleTitle = $user['active_role']['title'] ?? 'Student';

$menuSections = [
    [
        'title' => 'Main Workspace',
        'items' => [
            ['title' => 'Command Center', 'icon' => 'bi-speedometer2', 'url' => 'index.php?page=dashboard', 'roles' => ['all']],
        ]
    ],
    [
        'title' => 'People & Community',
        'items' => [
            ['title' => 'Student Directory', 'icon' => 'bi-people', 'url' => 'index.php?page=dashboard', 'roles' => ['principal', 'admin', 'teacher']],
            ['title' => 'Teachers & Staff', 'icon' => 'bi-mortarboard', 'url' => 'index.php?page=dashboard', 'roles' => ['principal', 'admin', 'hr']],
            ['title' => 'Parent Portal', 'icon' => 'bi-person-heart', 'url' => 'index.php?page=dashboard', 'roles' => ['admin', 'parent']],
        ]
    ],
    [
        'title' => 'Academics & Exams',
        'items' => [
            ['title' => 'Attendance', 'icon' => 'bi-calendar-check', 'url' => 'pages/attendance-mark.php', 'roles' => ['principal', 'admin', 'teacher']],
            ['title' => 'Classes & Sections', 'icon' => 'bi-building-gear', 'url' => 'index.php?page=dashboard', 'roles' => ['principal', 'admin', 'teacher']],
            ['title' => 'Timetable Schedule', 'icon' => 'bi-calendar3', 'url' => 'index.php?page=dashboard', 'roles' => ['all']],
            ['title' => 'Homework & Tasks', 'icon' => 'bi-journal-check', 'url' => 'index.php?page=dashboard', 'roles' => ['teacher', 'student', 'parent']],
            ['title' => 'Exams & Marks Entry', 'icon' => 'bi-clipboard-data', 'url' => 'index.php?page=dashboard', 'roles' => ['principal', 'teacher', 'student']],
        ]
    ],
    [
        'title' => 'Operations & Business',
        'items' => [
            ['title' => 'Accounts & Fees', 'icon' => 'bi-receipt-cutoff', 'url' => 'index.php?page=dashboard', 'roles' => ['principal', 'accounts', 'parent']],
            ['title' => 'Transport Fleet', 'icon' => 'bi-bus-front', 'url' => 'index.php?page=dashboard', 'roles' => ['principal', 'transport', 'parent']],
            ['title' => 'Hostel & Housing', 'icon' => 'bi-house-door', 'url' => 'index.php?page=dashboard', 'roles' => ['principal', 'warden', 'parent']],
        ]
    ],
    [
        'title' => 'Safety & Safeguarding',
        'items' => [
            ['title' => 'Anti-Ragging Reports', 'icon' => 'bi-shield-exclamation', 'url' => 'index.php?page=dashboard', 'roles' => ['principal', 'safety', 'counsellor']],
            ['title' => 'Emergency Center', 'icon' => 'bi-bell-fill', 'url' => 'index.php?page=dashboard', 'roles' => ['principal', 'admin', 'safety']],
        ]
    ],
    [
        'title' => 'Intelligence & Platform',
        'items' => [
            ['title' => 'Communication Hub', 'icon' => 'bi-chat-left-dots', 'url' => 'index.php?page=dashboard', 'roles' => ['all']],
            ['title' => 'Analytics & Reports', 'icon' => 'bi-graph-up', 'url' => 'index.php?page=dashboard', 'roles' => ['principal', 'admin', 'accounts', 'hr']],
            ['title' => 'RBAC Roles Engine', 'icon' => 'bi-sliders', 'url' => 'index.php?page=dashboard', 'roles' => ['admin', 'principal']],
        ]
    ]
];
?>
<aside class="d-none d-lg-flex flex-column bg-white border-end p-3" style="width: var(--sidebar-width); min-height: calc(100vh - var(--topbar-height)); flex-shrink: 0;">
    <!-- Active Workspace Pill -->
    <div class="p-2 mb-3 rounded bg-light border">
        <div class="text-uppercase fw-bold text-muted" style="font-size: 10px; letter-spacing: 0.8px; color: var(--caspa-teal) !important;">
            CURRENT WORKSPACE
        </div>
        <div class="fw-bold small text-dark mt-1">
            <?= htmlspecialchars($roleTitle) ?> Workspace
        </div>
    </div>

    <!-- Navigation Items -->
    <div class="d-flex flex-column gap-3 overflow-y-auto">
        <?php foreach ($menuSections as $sec): 
            $visibleItems = array_filter($sec['items'], function($it) use ($activeRoleKey) {
                return in_array('all', $it['roles']) || in_array($activeRoleKey, $it['roles']);
            });

            if (empty($visibleItems)) continue;
        ?>
            <div>
                <div class="text-uppercase text-muted fw-bold px-2 mb-1" style="font-size: 10px; letter-spacing: 0.8px;">
                    <?= $sec['title'] ?>
                </div>
                <div class="d-flex flex-column gap-1">
                    <?php foreach ($visibleItems as $item): ?>
                        <a href="<?= $item['url'] ?>" class="d-flex align-items-center gap-2 px-3 py-2 rounded text-dark text-decoration-none small fw-semibold sidebar-link <?= ($item['title'] === 'Command Center') ? 'active-nav-link' : '' ?>">
                            <i class="bi <?= $item['icon'] ?>" style="color: var(--caspa-teal);"></i>
                            <span><?= $item['title'] ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</aside>

<style>
.sidebar-link {
    transition: background-color 0.15s ease, color 0.15s ease;
}
.sidebar-link:hover {
    background-color: var(--caspa-surface);
    color: var(--caspa-teal);
}
.sidebar-link.active-nav-link {
    background-color: var(--caspa-teal-light);
    color: var(--caspa-teal);
    font-weight: 700;
}
</style>
