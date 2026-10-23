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
| Teacher (Gr 8-A) | `teacher1@demo.caspa` |
| Teacher (Gr 9-A) | `teacher2@demo.caspa` |
| Students | `student1@demo.caspa` … `student8@demo.caspa` |
| Parent | `parent1@demo.caspa` |

Re-seed: `php database/seed.php` or `http://localhost/Edunexa/database/seed.php`

---

## DONE ✅
- Full MySQL schema (`database/schema.sql`) + idempotent seeder
- `config/`, `includes/` helpers (db, auth, response, layout)
- Design system CSS + `api.js` + `ui.js`
- Common responsive layout with collapsible/mobile offcanvas sidebar & CASPA AI drawer
- Auth system: `api/auth/login.php`, `api/auth/logout.php`, `pages/login.php`
- Role dashboards (x5): `pages/{principal,admin,teacher,student,parent}/dashboard.php`
- Student & Class Management:
  - `api/classes.php` (GET scoped, POST admin create)
  - `api/students.php` (GET search/filter/pagination scoped, POST admin create)
  - `api/student.php` (GET profile + attendance summary, 403 scope check)
  - `pages/admin/students.php` (search, class filter, pagination, add student modal)
  - `pages/admin/classes.php` (classes list + add class modal)
  - `pages/shared/student_profile.php` (profile card, parents, attendance rate & history, 403 URL guard)
- Attendance Marking (per class_subject per date):
  - `api/attendance/my_class_subjects.php` (teacher assigned class+subject pairs)
  - `api/attendance/list.php` (roster + existing marks + counts, 403 authorization guard)
  - `api/attendance/save.php` (upsert with transaction, future date & class membership validation)
  - `pages/teacher/attendance.php` (dropdown selector, date picker, segmented toggles, mark-all-present, live summary chips)
- Assignments Module:
  - `api/assignments.php` (GET role-scoped by allowed classes/taught subjects, POST teacher create with due date >= today validation)
  - `api/assignment_delete.php` (POST teacher delete own assignment with 403 creator guard)
  - `pages/teacher/assignments.php` (list + search/class filter + create modal with validation + delete prompt)
  - `pages/student/assignments.php` (cards view with due dates, overdue highlighted in warning color #D97706, subject/status filters)
  - `pages/parent/assignments.php` (linked child class assignments cards view, child selector for multiple children)
- Teacher Dashboard (Real Data):
  - `api/dashboard_teacher.php` (assigned classes with student counts, today's attendance status per class, upcoming assignments, latest 3 announcements, KPIs)
  - `pages/teacher/dashboard.php` (KPI cards, mark attendance quick action, assigned classes attendance tracker, upcoming assignments, school announcements)
- Principal & Admin Dashboards (Real Data):
  - `api/dashboard_principal.php` (principal + admin only; KPIs, 7-day attendance trend per class, pending attendance classes, weekly assignments count, announcements)
  - `api/announcements.php` (GET role-scoped by audience SET, POST principal/admin only with validation)
  - `pages/principal/dashboard.php` (KPI cards, Chart.js 7-day trend line chart, pending attendance alert table, announcements panel, announcement composer modal)
  - `pages/admin/dashboard.php` (same as principal plus quick links to Student and Class management)
- Student & Parent Dashboards (Real Data):
  - `api/dashboard_student.php` (own data only, attendance %, last 10 records, upcoming assignments, notices; strict 403 on ID tampering)
  - `pages/student/dashboard.php` (profile card, attendance summary & statistics, last 10 days history table, upcoming assignments, notices modal)
  - `api/dashboard_parent.php` (returns linked children via parent_student, selected child details, attendance %, last 10 records, assignments, notices; strict 403 on unlinked child)
  - `pages/parent/dashboard.php` (child switcher pills for multi-child parents, selected child profile card, attendance summary & history, assignments, notices modal)
- `.gitignore` — secrets, OS files, logs, uploads excluded

## NEXT 🔜 (suggested order)
1. Grades & Grading workflow
2. Announcements (audience-filtered)
3. Internal Messaging
4. Forgot / Reset Password (token-based)
