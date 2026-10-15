# CASPA School OS — Edunexa

Role-based school management system built with PHP 8, MySQL, Bootstrap 5, and jQuery.

---

## Tech Stack

| Layer     | Technology                        |
|-----------|-----------------------------------|
| Server    | PHP 8 on XAMPP (Apache + MySQL)   |
| Database  | MySQL 8 via PDO (prepared stmts)  |
| Frontend  | Bootstrap 5 · jQuery · Vanilla JS |
| Auth      | PHP sessions · `password_hash`    |

---

## Local Setup (XAMPP)

### 1 – Prerequisites
- XAMPP ≥ 8.2 with Apache and MySQL running
- Git for Windows

### 2 – Clone / copy the project
```bash
# Inside C:\xampp\htdocs\
git clone <repo-url> Edunexa
```
Or place the folder manually so the path is `C:\xampp\htdocs\Edunexa`.

### 3 – Create the database
Open **phpMyAdmin** → `http://localhost/phpmyadmin` and run:

```sql
SOURCE C:/xampp/htdocs/Edunexa/database/schema.sql;
```

Or via MySQL CLI:
```bash
mysql -u root -p < C:\xampp\htdocs\Edunexa\database\schema.sql
```

### 4 – Configure the app
```bash
cp config/config.sample.php config/config.php
```
Edit `config/config.php` and set your `DB_USER`, `DB_PASS`, and `APP_URL`.

Default XAMPP values (no changes needed for a fresh install):
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'edunexa');
define('DB_USER', 'root');
define('DB_PASS', '');
define('APP_URL', 'http://localhost/Edunexa');
```

### 5 – Seed demo data
```bash
php database/seed.php
```
Or visit `http://localhost/Edunexa/database/seed.php` in your browser.

Demo accounts (all use password `Demo@1234`):

| Role      | Email                        |
|-----------|------------------------------|
| Principal | principal@demo.caspa         |
| Admin     | admin@demo.caspa             |
| Teacher   | teacher@demo.caspa           |
| Student   | student@demo.caspa           |
| Parent    | parent@demo.caspa            |

> **Important:** Delete or restrict `database/seed.php` before deploying.

### 6 – Open in browser
```
http://localhost/Edunexa/
```

---

## Project Structure

```
Edunexa/
├── api/                    # AJAX endpoints (JSON only)
├── assets/
│   ├── css/app.css         # Design system & CSS variables
│   └── js/
│       ├── api.js          # $.ajax wrapper (returns Promises)
│       └── ui.js           # showToast / showLoading / showEmpty / showError
├── config/
│   ├── config.sample.php   # Template – safe to commit
│   └── config.php          # Real credentials – git-ignored
├── database/
│   ├── schema.sql          # Full DDL
│   └── seed.php            # Demo data seeder
├── includes/
│   ├── auth.php            # Session management, role guards
│   ├── db.php              # PDO singleton
│   ├── layout_footer.php   # Shared HTML footer
│   ├── layout_header.php   # Shared HTML header + nav
│   └── response.php        # json_ok / json_fail / json_validate
├── pages/
│   ├── admin/
│   ├── parent/
│   ├── principal/
│   ├── student/
│   └── teacher/
├── .gitignore
└── README.md
```

---

## API Contract

All endpoints under `api/` return JSON:

```json
// Success
{ "ok": true, "data": { ... } }

// Error
{ "ok": false, "error": "Human-readable message" }
```

HTTP status codes used: `200 OK`, `201 Created`, `401 Unauthorized`, `403 Forbidden`, `422 Unprocessable Entity`, `500 Internal Server Error`.

---

## Security Checklist

- [x] PDO prepared statements everywhere (no string interpolation in SQL)
- [x] `password_hash` / `password_verify` for credentials
- [x] `session_regenerate_id(true)` on login
- [x] `httponly` session cookies
- [x] Role guards enforced **server-side** on every endpoint
- [x] All output escaped with `htmlspecialchars`
- [x] `config.php` git-ignored

---

## Roles

| Role      | Capabilities (summary)                            |
|-----------|---------------------------------------------------|
| Principal | Full read access + announcements + reports        |
| Admin     | User management, classes, timetable, fees         |
| Teacher   | Attendance, grades, assignments for own classes   |
| Student   | Own grades, attendance, assignments, messages     |
| Parent    | Children's grades, attendance, messages           |

---

## Colour Palette

| Token     | Hex      | Usage                    |
|-----------|----------|--------------------------|
| Navy      | #123B5D  | Sidebar, headings        |
| Teal      | #176B87  | Primary buttons, links   |
| Purple    | #5B3FA3  | CASPA AI features only   |
| Sky       | #3B82F6  | Info / accent            |
| Success   | #16A34A  | Success states           |
| Warning   | #D97706  | Warning states           |
| Danger    | #DC2626  | Errors only              |
| Surface   | #F8FAFC  | Page backgrounds         |
| Border    | #CBD5E1  | Borders, dividers        |
| Text      | #25313C  | Body text                |
