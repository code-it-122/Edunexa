# EduNexa (CASPA School OS)

A role-based school management web app (Pre-Primary to Grade 12) built from the
*CASPA School OS UI/UX Design Guidelines*. This repository is the **working MVP**:
real login, real database, real permissions, and the core school workflows for five roles.

**Stack:** PHP 8.1 or newer (no framework) · MySQL/MariaDB · PDO · Bootstrap 5 · jQuery + AJAX (JSON) · XAMPP

---

## 1. Run it locally (XAMPP)

1. Copy this folder to `C:\xampp\htdocs\Edunexa` (so the app is at `http://localhost/Edunexa`).
2. Start **Apache** and **MySQL** in the XAMPP control panel.
3. Copy `config/config.sample.php` to `config/config.php` and check the values
   (`DB_USER` is `root` and `DB_PASS` is empty on a default XAMPP). `config/config.php` is git-ignored.
4. In phpMyAdmin, **Import** `database/schema.sql` (it creates the `edunexa` database).
5. Load the demo data by opening each link once:
   - `http://localhost/Edunexa/database/seed.php`  (users, classes, students, announcements)
   - `http://localhost/Edunexa/database/seed_attendance.php`  (optional: 14 weekdays of fake attendance so the dashboards have numbers)
6. Open `http://localhost/Edunexa/` and sign in.

The two seed scripts only run from `localhost` or the command line. **Delete them before putting the app on a real server.**
`seed.php` wipes the demo rows each time it runs.

### Demo logins (password for all: `demo123`)

| Role | Email | Sees |
|---|---|---|
| Principal | `principal@demo.caspa` | School overview, attendance, announcements |
| Admin | `admin@demo.caspa` | Same overview + manage students and classes |
| Teacher | `teacher1@demo.caspa` (also `teacher2`, `teacher3`) | Own classes, attendance, assignments |
| Student | `student1@demo.caspa` (also `student2`) | Own attendance, assignments, notices |
| Parent | `parent1@demo.caspa` (also `parent2`) | Linked child's attendance, assignments, notices |

---

## 2. What is built

| Area | Status |
|---|---|
| Login / logout, session security, role redirect | Done |
| Role-based sidebar, top bar, mobile menu, CASPA AI button | Done (the AI itself is a placeholder) |
| Teacher: dashboard, attendance marking (per class + subject), assignments | Done |
| Student directory, student profile, class and section management (admin) | Done |
| Principal and Admin dashboards (KPIs, 7-day attendance chart, classes not yet marked, announcements) | Done |
| Attendance overview for Principal and Admin | Done |
| Announcements with audience targeting (publish: Principal/Admin; read: everyone it targets) | Done |
| Student dashboard + attendance + assignments | Done |
| Parent dashboard + attendance + assignments + notices, with child switcher | Done |
| Server-side permissions: teachers only their classes, students only themselves, parents only linked children | Done |

## 3. What is NOT built yet

From the design guidelines, these are **not** implemented:

- **P0 gaps:** timetable, exams / marks entry / report cards, school-campus selector, role/workspace selector,
  forgot-password, OTP/MFA, global search, notification center, teacher / employee profile screens,
  subject, curriculum and lesson-plan screens
- **P1:** admissions CRM, fees and accounts, HR and payroll, transport, hostel, library, inventory, certificates,
  anti-ragging / safeguarding / grievance workflows
- **P2:** events, sports, clubs, alumni, career, reports and analytics, working CASPA AI
- **Platform:** custom role builder, permission matrix editor, workflow engine, audit logs, integrations

The database already has tables for `grades` and `messages`, but there is no screen for them yet.

---

## 4. Project layout

```
api/            JSON endpoints (every one checks login + role + data scope on the server)
  auth/         login, logout
  attendance/   roster, save, a teacher's class list, school-wide overview
pages/          one folder per role, plus pages/shared for pages used by several roles
  shared/_*.php partials: not opened directly, each role's own file checks the role first
includes/       db, auth (sessions, roles, scope helpers), response (JSON helpers), layout
assets/         app.css (design system), api.js (AJAX wrapper), ui.js (toasts, loading, empty states)
database/       schema.sql, seed.php, seed_attendance.php
docs/           PROJECT_CONTEXT.md (conventions, helpers, DB tables) for developers and AI tools
```

## 5. Security notes

- Passwords are hashed (`password_hash`); sessions are regenerated on login; login has a 5-try throttle per session.
- Every query uses PDO prepared statements; every value shown from the database is HTML-escaped.
- **Permissions are enforced on the server.** Hiding a menu item is not security. Access to a student's record goes through
  `can_view_student()` in `includes/auth.php`.
- Scripts loaded from a CDN use integrity (SRI) hashes.

Known limitations: login throttling is per session (clearing cookies resets it); there are no CSRF tokens yet
(session cookies are `SameSite=Lax`); seed scripts must be removed for production.

## 6. Tested

Verified with an automated Chromium test (real login through the form, every sidebar link of every role,
cross-role page blocking, HTML-injection check, mobile menu) on PHP 8.3 with MariaDB 10.11: **70 checks passed**.
The same code has **not** been tested on other PHP versions (8.1 is the minimum, because the code uses the `never` return type) or on a live server.
