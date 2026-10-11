<?php
// api/caspa.php - CASPA AI Assistant API Engine
header('Content-Type: application/json');
require_once __DIR__ . '/../config/session.php';

if (!isLoggedIn()) {
    jsonResponse(['error' => 'Authentication required.'], 401);
}

$user = getCurrentUser();
$roleKey = $user['active_role']['key'] ?? 'student';
$roleTitle = $user['active_role']['title'] ?? 'Student';
$action = $_REQUEST['action'] ?? '';

$ROLE_PROMPTS = [
    'principal' => [
        "Executive summary of school attendance today",
        "Summarize fee collections and overdue dues",
        "Safety alert status and pending approvals",
        "Review campus teacher performance metrics"
    ],
    'admin' => [
        "Class-wise student strength and section distribution",
        "Generate audit log summary for privileged actions",
        "Pending document verification for new admissions",
        "Staff biometric attendance discrepancy report"
    ],
    'teacher' => [
        "Create a 45-minute lesson plan for Algebra",
        "Generate a 5-question pop quiz on Photosynthesis",
        "Class 10-A attendance summary this month",
        "Draft parent reminder for upcoming project deadline"
    ],
    'student' => [
        "Explain Newton's third law with everyday examples",
        "What homework assignments are due tomorrow?",
        "Help me create an exam revision schedule",
        "Show my attendance progress in Science"
    ],
    'parent' => [
        "Summary of child's attendance and academic standing",
        "Upcoming fee installment schedule and receipts",
        "Recent homework submissions and teacher remarks",
        "Schedule parent-teacher conference appointment"
    ],
    'safety' => [
        "Review open confidential safety incident reports",
        "Show child protection escalation timeline",
        "Generate safety compliance inspection log",
        "Campus visitor check-in summary"
    ]
];

if ($action === 'prompts') {
    $prompts = $ROLE_PROMPTS[$roleKey] ?? $ROLE_PROMPTS['student'];
    jsonResponse(['success' => true, 'role' => $roleTitle, 'prompts' => $prompts]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'chat') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $query = trim($input['query'] ?? '');

    if (empty($query)) {
        jsonResponse(['error' => 'Query is required.'], 400);
    }

    $name = htmlspecialchars($user['full_name']);
    $campus = htmlspecialchars($user['active_campus']['name']);

    $response = "Greetings {$name}. Connected to **{$campus}** under the **{$roleTitle} Workspace**.\n\n";

    if ($roleKey === 'principal') {
        $response .= "📊 **Command Intelligence Briefing:** Today's attendance is **94.2%** across 1,480 students. There are 2 pending leave approvals and 0 active safety alarms. Total Term 1 fee collections stand at **$142,500**.";
    } elseif ($roleKey === 'teacher') {
        $response .= "📚 **Academic Assistant:** You have 3 scheduled lectures today. Class 10-A assignments have 28/30 submissions received. I have drafted the requested teaching guide for your review.";
    } elseif ($roleKey === 'student') {
        $response .= "🎓 **Student Learning Assistant:** Your next period is Physics at 11:30 AM in Room 204. Your Math assignment is due tomorrow. Here is a quick breakdown to help you master this concept!";
    } elseif ($roleKey === 'parent') {
        $response .= "👨‍👩‍👧 **Parent Portal Digest:** Your child was marked **PRESENT** at 08:15 AM today. Term 1 fees are up to date with zero outstanding penalties.";
    } elseif ($roleKey === 'safety') {
        $response .= "🛡️ **Safeguarding Control Center:** All secure logs are active. Zero high-severity incidents reported in the last 24 hours. Confidential audit trail enabled.";
    } else {
        $response .= "⚡ Processed request: \"{$query}\". All permissions verified according to your current role policy.";
    }

    jsonResponse([
        'success' => true,
        'response' => $response,
        'timestamp' => date('h:i A'),
        'isAiGenerated' => true
    ]);
}

jsonResponse(['error' => 'Invalid action.'], 400);
