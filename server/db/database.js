import Database from 'better-sqlite3';
import bcrypt from 'bcryptjs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const dbPath = path.resolve(__dirname, '../../edunexa.sqlite');
const db = new Database(dbPath);

db.pragma('journal_mode = WAL');

export function initDatabase() {
  // Create Campuses Table
  db.exec(`
    CREATE TABLE IF NOT EXISTS campuses (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      name TEXT NOT NULL,
      code TEXT UNIQUE NOT NULL,
      location TEXT NOT NULL,
      is_main INTEGER DEFAULT 0,
      status TEXT DEFAULT 'active'
    );
  `);

  // Create Roles Table
  db.exec(`
    CREATE TABLE IF NOT EXISTS roles (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      key TEXT UNIQUE NOT NULL,
      title TEXT NOT NULL,
      description TEXT,
      category TEXT NOT NULL
    );
  `);

  // Create Users Table
  db.exec(`
    CREATE TABLE IF NOT EXISTS users (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      email TEXT UNIQUE NOT NULL,
      password_hash TEXT NOT NULL,
      full_name TEXT NOT NULL,
      phone TEXT,
      avatar_url TEXT,
      default_campus_id INTEGER DEFAULT 1,
      is_2fa_enabled INTEGER DEFAULT 1,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );
  `);

  // Create User Workspaces / Roles Mapping
  db.exec(`
    CREATE TABLE IF NOT EXISTS user_roles (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      user_id INTEGER NOT NULL,
      role_id INTEGER NOT NULL,
      campus_id INTEGER DEFAULT 1,
      is_primary INTEGER DEFAULT 0,
      FOREIGN KEY (user_id) REFERENCES users (id),
      FOREIGN KEY (role_id) REFERENCES roles (id)
    );
  `);

  // Create OTP Verification Table
  db.exec(`
    CREATE TABLE IF NOT EXISTS otps (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      email TEXT NOT NULL,
      code TEXT NOT NULL,
      purpose TEXT NOT NULL,
      expires_at DATETIME NOT NULL,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );
  `);

  // Create Audit Logs Table
  db.exec(`
    CREATE TABLE IF NOT EXISTS audit_logs (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      user_id INTEGER,
      action TEXT NOT NULL,
      details TEXT,
      ip_address TEXT,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );
  `);

  seedData();
}

function seedData() {
  // Seed Campuses if empty
  const campusCount = db.prepare('SELECT COUNT(*) as count FROM campuses').get().count;
  if (campusCount === 0) {
    const insertCampus = db.prepare('INSERT INTO campuses (name, code, location, is_main) VALUES (?, ?, ?, ?)');
    insertCampus.run('Main Campus', 'CAMP-01', 'Green Park, City Center', 1);
    insertCampus.run('North Campus', 'CAMP-02', 'Green Park, Trust Area', 0);
    insertCampus.run('Lakeside Campus', 'CAMP-03', 'Knowledge Park II', 0);
    insertCampus.run('Riverside Campus', 'CAMP-04', 'South Hub Road', 0);
    insertCampus.run('East Learning Center', 'CAMP-05', 'Innovation Hub, Pune', 0);
  }

  // Seed Roles if empty
  const roleCount = db.prepare('SELECT COUNT(*) as count FROM roles').get().count;
  if (roleCount === 0) {
    const insertRole = db.prepare('INSERT INTO roles (key, title, description, category) VALUES (?, ?, ?, ?)');
    insertRole.run('principal', 'Principal', 'School command center, approvals, academics, staff & finance', 'Leadership');
    insertRole.run('admin', 'Admin', 'Students, parents, classes, documents, operations & RBAC', 'Administration');
    insertRole.run('teacher', 'Teacher', 'Classes, attendance, lesson plans, homework, marks', 'Academics');
    insertRole.run('student', 'Student', 'Learning, timetable, assignments, exams & CASPA AI', 'Student');
    insertRole.run('parent', 'Parent', 'Children progress, attendance, fees & notices', 'Family');
    insertRole.run('hr', 'HR', 'Employees, recruitment, attendance, leave & payroll', 'Operations');
    insertRole.run('accounts', 'Accounts', 'Fees, receipts, dues, expenses, payroll & budgets', 'Finance');
    insertRole.run('admissions', 'Admission Team', 'Leads, counselling, applications, tests & interviews', 'Operations');
    insertRole.run('transport', 'Transport Manager', 'Buses, routes, drivers, live tracking & maintenance', 'Operations');
    insertRole.run('warden', 'Warden', 'Hostel rooms, attendance, movement, visitors & safety', 'Operations');
    insertRole.run('safety', 'Safety / Anti-Ragging Officer', 'Confidential incidents, investigation & escalation', 'Safety');
    insertRole.run('counsellor', 'Counsellor', 'Appointments, referrals & confidential counselling', 'Safety');
    insertRole.run('librarian', 'Librarian', 'Books, issue/return, fines & digital resources', 'Operations');
    insertRole.run('security', 'Security', 'Visitors, gate entry & pickup verification', 'Safety');
  }

  // Seed Default Users if empty
  const userCount = db.prepare('SELECT COUNT(*) as count FROM users').get().count;
  if (userCount === 0) {
    const defaultPassword = bcrypt.hashSync('password123', 10);
    
    const insertUser = db.prepare(`
      INSERT INTO users (email, password_hash, full_name, phone, default_campus_id, is_2fa_enabled)
      VALUES (?, ?, ?, ?, ?, ?)
    `);
    
    const insertUserRole = db.prepare(`
      INSERT INTO user_roles (user_id, role_id, campus_id, is_primary)
      VALUES (?, ?, ?, 1)
    `);

    const usersToCreate = [
      { email: 'principal@edunexa.edu', name: 'Dr. Arthur Vance', roleKey: 'principal' },
      { email: 'admin@edunexa.edu', name: 'Sarah Jenkins', roleKey: 'admin' },
      { email: 'teacher@edunexa.edu', name: 'Robert Miller', roleKey: 'teacher' },
      { email: 'student@edunexa.edu', name: 'Alex Rivera', roleKey: 'student' },
      { email: 'parent@edunexa.edu', name: 'David Rivera', roleKey: 'parent' },
      { email: 'hr@edunexa.edu', name: 'Elena Rostova', roleKey: 'hr' },
      { email: 'accounts@edunexa.edu', name: 'Michael Sterling', roleKey: 'accounts' },
      { email: 'safety@edunexa.edu', name: 'Marcus Vance', roleKey: 'safety' },
    ];

    for (const u of usersToCreate) {
      const res = insertUser.run(u.email, defaultPassword, u.name, '+1-555-0192', 1, 1);
      const userId = res.lastInsertRowid;
      const role = db.prepare('SELECT id FROM roles WHERE key = ?').get(u.roleKey);
      if (role) {
        insertUserRole.run(userId, role.id, 1);
      }
    }

    // Give 'admin@edunexa.edu' multi-role access to test Workspace Selector!
    const adminUser = db.prepare('SELECT id FROM users WHERE email = ?').get('admin@edunexa.edu');
    if (adminUser) {
      const rolesToAdd = ['principal', 'teacher', 'student', 'parent'];
      for (const rKey of rolesToAdd) {
        const r = db.prepare('SELECT id FROM roles WHERE key = ?').get(rKey);
        if (r) {
          db.prepare('INSERT OR IGNORE INTO user_roles (user_id, role_id, campus_id, is_primary) VALUES (?, ?, 1, 0)').run(adminUser.id, r.id);
        }
      }
    }
  }
}

export default db;
