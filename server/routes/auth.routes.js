import express from 'express';
import bcrypt from 'bcryptjs';
import jwt from 'jsonwebtoken';
import db from '../db/database.js';
import { JWT_SECRET, authenticateToken } from '../middleware/auth.js';

const router = express.Router();

// 1. LOGIN (Workplace screen)
router.post('/login', (req, res) => {
  const { email, password } = req.body;

  if (!email || !password) {
    return res.status(400).json({ error: 'Please enter both Email/ID and Password.' });
  }

  const user = db.prepare('SELECT * FROM users WHERE email = ?').get(email.toLowerCase().trim());
  if (!user) {
    return res.status(401).json({ error: 'Invalid email/ID or password.' });
  }

  const isPasswordValid = bcrypt.compareSync(password, user.password_hash);
  if (!isPasswordValid) {
    return res.status(401).json({ error: 'Invalid email/ID or password.' });
  }

  // Generate 6-digit OTP for 2FA as per Figma Design
  const otpCode = '123456'; // Standardized demo OTP code for easy testing
  const expiresAt = new Date(Date.now() + 10 * 60 * 1000).toISOString(); // 10 mins

  db.prepare('DELETE FROM otps WHERE email = ? AND purpose = ?').run(user.email, '2fa');
  db.prepare('INSERT INTO otps (email, code, purpose, expires_at) VALUES (?, ?, ?, ?)').run(user.email, otpCode, '2fa', expiresAt);

  // Return temporary token for OTP step
  const tempToken = jwt.sign(
    { userId: user.id, email: user.email, step: '2fa_pending' },
    JWT_SECRET,
    { expiresIn: '15m' }
  );

  return res.json({
    requireOtp: true,
    email: user.email,
    tempToken,
    message: '2FA OTP required. Demonstration code is 123456.',
    demoOtp: otpCode
  });
});

// 2. VERIFY OTP (OTP Screen)
router.post('/verify-otp', (req, res) => {
  const { email, otp, tempToken } = req.body;

  if (!email || !otp) {
    return res.status(400).json({ error: 'Email and 6-digit OTP code are required.' });
  }

  let decoded;
  try {
    decoded = jwt.verify(tempToken, JWT_SECRET);
  } catch (err) {
    return res.status(401).json({ error: 'OTP session expired. Please log in again.' });
  }

  const otpRecord = db.prepare('SELECT * FROM otps WHERE email = ? AND code = ? ORDER BY id DESC').get(email.toLowerCase().trim(), otp.trim());

  if (!otpRecord) {
    return res.status(400).json({ error: 'Invalid 6-digit verification code.' });
  }

  // Clean up used OTP
  db.prepare('DELETE FROM otps WHERE id = ?').run(otpRecord.id);

  const user = db.prepare('SELECT * FROM users WHERE id = ?').get(decoded.userId);
  if (!user) {
    return res.status(404).json({ error: 'User record not found.' });
  }

  // Fetch assigned campuses & workspaces
  const userRoles = db.prepare(`
    SELECT r.key, r.title, r.description, ur.campus_id, c.name as campus_name
    FROM user_roles ur
    JOIN roles r ON ur.role_id = r.id
    JOIN campuses c ON ur.campus_id = c.id
    WHERE ur.user_id = ?
  `).all(user.id);

  const primaryRole = userRoles[0] || { key: 'student', title: 'Student' };
  const primaryCampusId = user.default_campus_id || 1;

  // Issue full Auth Session Token
  const token = jwt.sign(
    {
      userId: user.id,
      email: user.email,
      fullName: user.full_name,
      activeCampusId: primaryCampusId,
      activeRole: primaryRole.key,
      activeRoleTitle: primaryRole.title
    },
    JWT_SECRET,
    { expiresIn: '24h' }
  );

  return res.json({
    token,
    user: {
      id: user.id,
      email: user.email,
      fullName: user.full_name,
      phone: user.phone,
      activeCampusId: primaryCampusId,
      activeRole: primaryRole.key,
      activeRoleTitle: primaryRole.title
    },
    workspaces: userRoles
  });
});

// 3. FORGOT PASSWORD (Forgot Password screen)
router.post('/forgot-password', (req, res) => {
  const { email } = req.body;

  if (!email) {
    return res.status(400).json({ error: 'Please enter your registered email address.' });
  }

  const user = db.prepare('SELECT * FROM users WHERE email = ?').get(email.toLowerCase().trim());
  if (!user) {
    // Return friendly generic response for security
    return res.json({
      success: true,
      message: 'If the email is registered, a password reset link/OTP has been sent.'
    });
  }

  const resetOtp = '123456'; // Standardized demo reset OTP
  const expiresAt = new Date(Date.now() + 15 * 60 * 1000).toISOString();

  db.prepare('DELETE FROM otps WHERE email = ? AND purpose = ?').run(user.email, 'password_reset');
  db.prepare('INSERT INTO otps (email, code, purpose, expires_at) VALUES (?, ?, ?, ?)').run(user.email, resetOtp, 'password_reset', expiresAt);

  return res.json({
    success: true,
    message: 'Reset instructions and code have been generated.',
    email: user.email,
    demoOtp: resetOtp
  });
});

// 4. RESET PASSWORD (Reset Password screen)
router.post('/reset-password', (req, res) => {
  const { email, password, confirmPassword } = req.body;

  if (!email || !password || !confirmPassword) {
    return res.status(400).json({ error: 'Please fill in all fields.' });
  }

  if (password !== confirmPassword) {
    return res.status(400).json({ error: 'Passwords do not match.' });
  }

  if (password.length < 6) {
    return res.status(400).json({ error: 'Password must be at least 6 characters long.' });
  }

  const user = db.prepare('SELECT * FROM users WHERE email = ?').get(email.toLowerCase().trim());
  if (!user) {
    return res.status(404).json({ error: 'User account not found.' });
  }

  const newHash = bcrypt.hashSync(password, 10);
  db.prepare('UPDATE users SET password_hash = ? WHERE id = ?').run(newHash, user.id);

  return res.json({
    success: true,
    message: 'Password reset successfully. You can now log in.'
  });
});

// 5. GET CURRENT USER & WORKSPACES
router.get('/me', authenticateToken, (req, res) => {
  const user = db.prepare('SELECT id, email, full_name, phone, default_campus_id FROM users WHERE id = ?').get(req.user.userId);
  if (!user) {
    return res.status(404).json({ error: 'User not found.' });
  }

  const userRoles = db.prepare(`
    SELECT r.id as role_id, r.key, r.title, r.description, r.category, ur.campus_id, c.name as campus_name
    FROM user_roles ur
    JOIN roles r ON ur.role_id = r.id
    JOIN campuses c ON ur.campus_id = c.id
    WHERE ur.user_id = ?
  `).all(user.id);

  const campuses = db.prepare('SELECT * FROM campuses WHERE status = "active"').all();

  const activeCampus = campuses.find(c => c.id === req.user.activeCampusId) || campuses[0];
  const activeRole = userRoles.find(r => r.key === req.user.activeRole) || userRoles[0] || { key: 'student', title: 'Student' };

  return res.json({
    user: {
      id: user.id,
      email: user.email,
      fullName: user.full_name,
      phone: user.phone,
      activeCampusId: activeCampus ? activeCampus.id : 1,
      activeCampusName: activeCampus ? activeCampus.name : 'Main Campus',
      activeCampusCode: activeCampus ? activeCampus.code : 'CAMP-01',
      activeRoleKey: activeRole.key,
      activeRoleTitle: activeRole.title
    },
    workspaces: userRoles,
    campuses
  });
});

// 6. SELECT CAMPUS
router.post('/select-campus', authenticateToken, (req, res) => {
  const { campusId } = req.body;

  const campus = db.prepare('SELECT * FROM campuses WHERE id = ?').get(campusId);
  if (!campus) {
    return res.status(404).json({ error: 'Campus not found.' });
  }

  // Update default campus
  db.prepare('UPDATE users SET default_campus_id = ? WHERE id = ?').run(campus.id, req.user.userId);

  // Issue updated JWT token
  const token = jwt.sign(
    {
      userId: req.user.userId,
      email: req.user.email,
      fullName: req.user.fullName,
      activeCampusId: campus.id,
      activeRole: req.user.activeRole,
      activeRoleTitle: req.user.activeRoleTitle
    },
    JWT_SECRET,
    { expiresIn: '24h' }
  );

  return res.json({
    token,
    campus
  });
});

// 7. SELECT WORKSPACE / ROLE
router.post('/select-workspace', authenticateToken, (req, res) => {
  const { roleKey } = req.body;

  const role = db.prepare('SELECT * FROM roles WHERE key = ?').get(roleKey);
  if (!role) {
    return res.status(404).json({ error: 'Role workspace not found.' });
  }

  // Issue updated JWT token with selected role
  const token = jwt.sign(
    {
      userId: req.user.userId,
      email: req.user.email,
      fullName: req.user.fullName,
      activeCampusId: req.user.activeCampusId,
      activeRole: role.key,
      activeRoleTitle: role.title
    },
    JWT_SECRET,
    { expiresIn: '24h' }
  );

  return res.json({
    token,
    role
  });
});

export default router;
