# What changed in this update

## Bugs fixed
| Problem | Fix | Files |
|---|---|---|
| `pages/login.php` crashed with a 500 (wrong project root) | `ROOT` now points to the project folder | `pages/login.php` |
| Bootstrap JS blocked by the browser (wrong integrity hash) | Corrected the SHA-384 hash | `includes/layout_footer.php`, `pages/login.php` |
| Login crashed on PHP 8.3+ (`json_validate` already exists in PHP) | Renamed our helper to `validate_input()` | `includes/response.php`, `api/auth/login.php`, `api/classes.php`, `api/students.php` |
| `$ is not defined` on 3 pages (script ran before jQuery loaded) | Page scripts now go through `$inlineJs`, printed by the footer after jQuery | `pages/teacher/dashboard.php`, `pages/student/assignments.php`, `pages/parent/assignments.php` |
| A student or parent could read classmates' profiles | New `restricted_student_ids()` / `can_view_student()`; student = self only, parent = linked children only | `includes/auth.php`, `api/student.php`, `api/students.php`, `pages/shared/student_profile.php` |
| 7 sidebar links led to 404 pages | Built the pages (below) | see "New" |
| No README | Added | `README.md` |

## New features
- **Principal + Admin dashboards** with real KPIs, a 7-day attendance chart, classes not yet marked today, latest announcements
  (`api/dashboard_principal.php`, `pages/shared/_overview_dashboard.php`)
- **Attendance overview** (Principal + Admin): any date, per class and subject
  (`api/attendance/overview.php`, `pages/shared/attendance_overview.php`)
- **Announcements:** Principal/Admin publish to chosen audiences; everyone reads what targets them
  (`api/announcements.php`, `pages/shared/announcements.php`, `pages/{admin,principal}/announcements.php`, `pages/parent/notices.php`)
- **Student + Parent dashboards and attendance pages**, parent child-switcher
  (`api/dashboard_family.php`, `api/my_attendance.php`, `pages/shared/_family_*.php`, `pages/{student,parent}/{dashboard,attendance}.php`)
- **Principal students page** (`pages/principal/students.php`), **admin sidebar entry** for Attendance Overview
- **Optional demo attendance** (`database/seed_attendance.php`)

## Not changed
Database schema, login behaviour, teacher workflows, assignment and class management, colours and fonts.
