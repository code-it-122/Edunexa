import express from 'express';
import { authenticateToken } from '../middleware/auth.js';

const router = express.Router();

const ROLE_CAPABILITIES = {
  student: [
    "Explain standard 10th science concepts in simple terms",
    "Generate 5 practice questions for upcoming Math exam",
    "Show my attendance summary and pending assignments",
    "Help me prepare a revision plan for final exams"
  ],
  teacher: [
    "Generate a 45-minute lesson plan for Algebra",
    "Create a 10-question multiple choice quiz on Photosynthesis",
    "Summarize class 10-A attendance performance this month",
    "Draft a parent notification regarding upcoming science project"
  ],
  parent: [
    "Summarize Alex's overall academic progress this quarter",
    "Check Alex's attendance record and fee due dates",
    "View recent teacher feedback for homework submissions",
    "Request an appointment with the Class Teacher"
  ],
  principal: [
    "Give me an executive summary of school attendance today",
    "Summarize outstanding fee collections across all campuses",
    "Are there any safety alerts or pending approvals requiring action?",
    "Generate campus performance report for board review"
  ],
  hr: [
    "Summarize staff attendance trends across departments",
    "Show pending leave applications for approval",
    "Generate monthly teacher payroll overview",
    "List open recruitment vacancies for next term"
  ],
  accounts: [
    "Show total fee collection vs outstanding dues for current term",
    "List pending expense approval requests above $500",
    "Generate monthly balance sheet and cash flow summary",
    "View active scholarship concessions granted"
  ]
};

router.get('/prompts', authenticateToken, (req, res) => {
  const role = req.user.activeRole || 'student';
  const prompts = ROLE_CAPABILITIES[role] || ROLE_CAPABILITIES['student'];
  res.json({ role, prompts });
});

router.post('/chat', authenticateToken, (req, res) => {
  const { query } = req.body;
  const role = req.user.activeRole || 'student';
  const name = req.user.fullName || 'User';

  if (!query) {
    return res.status(400).json({ error: 'Query parameter is required.' });
  }

  // Simulated AI response tailored to active role
  let responseText = `Hello ${name} (${role.toUpperCase()} Workspace). `;

  if (role === 'principal') {
    responseText += `Campus Command Analysis: Today's school attendance is **94.2%**. There are 2 pending leave approvals and 0 critical safety alerts. Fee collection is currently at **$142,500** for this month.`;
  } else if (role === 'teacher') {
    responseText += `Academic Assistant: I have prepared your requested materials. You have 3 classes scheduled today. 2 homework submissions are ready for evaluation.`;
  } else if (role === 'student') {
    responseText += `Learning Assistant: Your next class is **Physics at 11:30 AM**. Your Math homework is due tomorrow. Here is a quick summary of key concepts.`;
  } else if (role === 'parent') {
    responseText += `Parent Portal Assistant: Your child Alex is marked **PRESENT** today. Next fee installment of $450 is due on Oct 15th.`;
  } else {
    responseText += `CASPA Intelligence: I have processed your request for "${query}". Access level confirmed for ${role} role.`;
  }

  res.json({
    query,
    response: responseText,
    role,
    timestamp: new Date().toISOString(),
    isAiGenerated: true
  });
});

export default router;
