# CASPA School OS – Project Context

## Goal
Role-based school management (EduNexa). Five roles, each sees only their data.
URL: `http://localhost/Edunexa/`

## Stack
XAMPP · PHP 8 · MySQL 8 (`edunexa` DB) · PDO · Bootstrap 5.3 + Bootstrap Icons ·
jQuery 3 + AJAX · Inter font (loaded in `layout_header.php`)

## Roles (`users.role`)
`principal` · `admin` · `teacher` · `student` · `parent`

## Color Palette (CSS vars in `assets/css/app.css`)
| Token | Hex | Use |
|---|---|---|
| Navy `--color-navy` | `#123B5D` | sidebar, headings |
| Teal `--color-teal` | `#176B87` | primary buttons |
| Purple `--color-purple` | `#5B3FA3` | CASPA AI only |
| Sky `--color-sky` | `#3B82F6` | info / accents |
| Success | `#16A34A` | present / ok |
| Warning | `#D97706` | late / caution |
| Danger | `#DC2626` | absent / errors |
| Surface | `#F8FAFC` | page bg |
| Border | `#CBD5E1` | card edges |
| Text | `#25313C` | body copy |

---

## Database (`database/schema.sql`)

```
users            id PK, name, email UNIQUE, password_hash, role ENUM, is_active
academic_years   id, label ("2025-2026"), is_current, starts_on, ends_on
classes          id, academic_year_id→ay, name, section, class_teacher_id→users
subjects         id, name, code UNIQUE
class_subjects   id, class_id→classes, subject_id→subjects, teacher_id→users
                 ⟶ replaces old teacher_assignments concept
students         id, user_id→users, class_id→classes, admission_number UNIQUE
parents          id, user_id→users, phone, occupation
parent_student   parent_id→parents, student_id→students  (M:M)
attendance       id, student_id→students, class_subject_id→class_subjects,
                 date DATE, status ENUM(present|absent|late|excused),
                 recorded_by→users · UNIQUE(student_id, class_subject_id, date)
assignments      id, class_subject_id, title, due_date, max_marks, created_by
grades           id, assignment_id, student_id, marks, feedback, graded_by
announcements    id, title, body, audience SET(roles), created_by, published_at
messages         id, sender_id, receiver_id, subject, body, is_read
```

> Attendance is per **class_subject** per day — not per class per day.

---

## PHP Helpers

**`includes/db.php`** — `db(): PDO` singleton (FETCH_ASSOC, exceptions on, no emulation)

**`includes/auth.php`**
- `session_boot()` — start session (httponly, SameSite=Lax)
- `current_user()` — `['id','name','email','role']` or `null`
- `require_login()` — 401 JSON (API) or redirect (page)
- `require_role($roles)` — checks login + role
- `login_user($user)` — writes session, `session_regenerate_id(true)`
- `logout_user()` — destroys session + expires cookie
- `allowed_class_ids($user): int[]` — principal/admin→all · teacher→via class_subjects · student→their class · parent→children's classes

**`includes/response.php`**
- `json_ok($data, $status=200)` → `{"ok":true,"data":...}` + exit
- `json_fail($error, $status=422)` → `{"ok":false,"error":"..."}` + exit
- `validate_input(['field'=>'required|email|min:6'])` — 422 on failure, returns trimmed values

---

## JS Helpers (loaded globally via `layout_footer.php`)

**`assets/js/api.js`** — `api` object
```js
api.get('/api/some.php', { key: val })    // → Promise → response.data
api.post('/api/some.php', { key: val })   // → Promise → response.data
api.upload('/api/some.php', formData)     // file uploads
// 401 auto-redirects to index.php?error=session_expired
```

**`assets/js/ui.js`** — `ui` object
```js
ui.showToast(msg, 'success'|'danger'|'warning'|'info')
ui.showLoading(el, msg?)   // spinner inside el
ui.showEmpty(el, msg?)     // empty-state placeholder
ui.showError(el, msg?)     // error placeholder
ui.clearState(el)          // empty el
```

---

## Layout Pattern (every authenticated page)
```php
define('ROOT', dirname(__DIR__, N));   // depth to project root
$pageTitle    = 'Page Name';           // required
$pageRole     = 'teacher';             // required
$sidebarItems = [['href'=>'...','icon'=>'bi-house','label'=>'X','active'=>true]];
require_once ROOT . '/includes/layout_header.php';
// page HTML here
require_once ROOT . '/includes/layout_footer.php';
```
Header emits: Bootstrap CSS/Icons, Inter, `app.css`, navbar, toast container
`#toastContainer`, `.caspa-wrapper`, sidebar, `<main>`.
Footer closes `</main></div>`, loads jQuery → Bootstrap JS → `api.js` → `ui.js`.

---

## API Rules (every `api/*.php`)
1. Call `require_login()` or `require_role()` first.
2. Use `validate_input()` for input; PDO prepared statements only — no raw SQL.
3. Verify class ownership with `allowed_class_ids()` — never trust POST `class_id` blindly.
4. Return only `json_ok()` / `json_fail()`.
5. `config/config.php` is git-ignored — copy `config.sample.php` to set it up.

---

## Demo Logins — password `demo123`
| Role | Email |
|---|---|
| Principal | `principal@demo.caspa` |
| Admin | `admin@demo.caspa` |
| Teachers | `teacher1@demo.caspa` (Gr 8-A, Gr 9-A English), `teacher2@demo.caspa` (Gr 9-A), `teacher3@demo.caspa` (Gr 8-A Social Studies) |
| Students | `student1@demo.caspa` (Gr 8-A), `student2@demo.caspa` (Gr 9-A) |
| Parents | `parent1@demo.caspa` (child: student1), `parent2@demo.caspa` (child: student2) |

Seed: `php database/seed.php` then optionally `php database/seed_attendance.php` (or open them in the browser from localhost).

---|---|
| Principal | `principal@demo.caspa` |
| Admin | `admin@demo.caspa` |
| Teacher (Gr 8-A) | `teacher1@demo.caspa` |
| Teacher (Gr 9-A) | `teacher2@demo.caspa` |
| Students | `student1@demo.caspa` … `student8@demo.caspa` |
| Parent | `parent1@demo.caspa` |

Re-seed: `php database/seed.php` or `http://localhost/Edunexa/database/seed.php`

---

## DONE ✅
- Full MySQL schema (`database/schema.sql`) + idempotent seeders (`seed.php`, optional `seed_attendance.php`)
- `config/`, `includes/` helpers (db, auth, response, layout), design-system CSS, `api.js`, `ui.js`
- Responsive layout: collapsible/mobile offcanvas sidebar, CASPA AI placeholder drawer
- Auth: `api/auth/login.php`, `api/auth/logout.php`, `pages/login.php`
- **Teacher:** dashboard (`api/dashboard_teacher.php`), attendance marking per class_subject (`api/attendance/*`), assignments (`api/assignments.php`, `api/assignment_delete.php`)
- **Admin / shared:** student directory + profile (`api/students.php`, `api/student.php`, `pages/shared/student_profile.php`), class management (`api/classes.php`)
- **Principal + Admin:** dashboard (`api/dashboard_principal.php`, `pages/shared/_overview_dashboard.php`), attendance overview (`api/attendance/overview.php`, `pages/shared/attendance_overview.php`), announcements (`api/announcements.php`, `pages/shared/announcements.php`)
- **Student + Parent:** dashboard (`api/dashboard_family.php`, `pages/shared/_family_dashboard.php`), attendance (`api/my_attendance.php`, `pages/shared/_family_attendance.php`), assignments, parent notices (`pages/parent/notices.php`)
- Per-student scope: `restricted_student_ids()` and `can_view_student()` in `includes/auth.php` (student = self only, parent = linked children only, teacher = their classes, principal/admin = all)

## Conventions added
- **Page scripts:** never put a raw `<script>` before the footer (jQuery is loaded by the footer, so `$` would be undefined).
  Wrap the page JS in `<?php ob_start(); ?> ... <?php $inlineJs = ob_get_clean(); ?>`; `layout_footer.php` prints it after jQuery.
  Extra libraries go in `$extraJs = [...]` (printed before `$inlineJs`).
- **Shared pages** live in `pages/shared/`. Files starting with `_` are partials: the role's own entry file calls `require_role(...)` first, sets `$pageRole`, then includes the partial.
- **Input validation helper is `validate_input()`** (it was `json_validate()`, which clashes with the built-in function in PHP 8.3+).
- **Attendance rate** everywhere = present records / all records.
- **Student/parent data:** use `restricted_student_ids($user)`; never scope by class alone.

## NEXT 🔜 (remaining from the design guidelines, by priority)
1. P0: timetable; exams, marks entry and report card (the `grades` table exists); campus + role/workspace selector; forgot-password / OTP; global search; notification center
2. P0: teacher / employee profiles; subjects, curriculum and lesson plans
3. P1: admissions, fees/accounts, HR/payroll, transport, hostel, library, safety/safeguarding workflows
4. P2: events/clubs/alumni, analytics, working CASPA AI (server-side key; role-aware)
5. Platform: role builder, permission matrix, audit logs, CSRF tokens
