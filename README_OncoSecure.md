# OncoSecure — Cybersecurity‑Focused PHP Web App (Final Year Project)

OncoSecure is a role‑based PHP/MySQL web application designed for a healthcare scenario (Admin, Doctor, Patient). 
This project emphasizes **secure coding practices** commonly required in cybersecurity coursework: 
**CSRF protection, CAPTCHA on login, prepared statements (PDO), password hashing, role‑based access control, input sanitization, and security logging**.

> **Audience**: Cybersecurity students and instructors (not necessarily web developers).  
> **Goal**: Make it easy to run, test, and evaluate security features.

---

## ✨ Key Security Features (Implemented)

- **Secure Authentication**
  - Passwords stored with `password_hash()` (bcrypt) and verified via `password_verify()`.
  - Optional **forced password change** support via `force_password_change` flag.
  - **Login attempt logging** and general action **audit logs** (see `logs` table).

- **CSRF Protection**
  - Server‑generated CSRF tokens stored in session; validated on form POSTs. (`includes/functions.php`)

- **CAPTCHA on Login**
  - Basic CAPTCHA check to reduce automated login attempts. (`auth/captcha.php`, `auth/login.php`)

- **Role‑Based Access Control (RBAC)**
  - Roles: `admin`, `doctor`, `patient` enforced via `$_SESSION['role']` checks and guard helpers. 
  - Separate directories for each role (`/admin`, `/doctor`, `/patient`).

- **Prepared Statements Everywhere (PDO)**
  - SQL calls via `$pdo->prepare(...)->execute([...])` to prevent SQL injection.

- **Input Sanitization & Output Encoding**
  - `sanitize()` trims + `htmlspecialchars()` to mitigate XSS on user‑controlled fields.
  - `filter_var()` for safe email validation and other structured inputs.

- **Password Reset Tokens**
  - Time‑limited tokens stored in DB with `expires_at` & `is_used` flags to prevent replay. (`password_resets` table)

- **Security‑Relevant Schema**
  - `users` table with unique emails, role column, and fields for professional metadata.
  - `logs` table for user actions and IP metadata.
  - Foreign keys with cascades to keep referential integrity. See `schema.sql`.

> Code references: `includes/functions.php`, `auth/login.php`, `auth/register.php`, `auth/reset_password.php`, `auth/reset_verify.php`, `includes/db.php`, `schema.sql`.

---

## 🧰 Tech Stack

- **Backend**: PHP 8+ with PDO (MySQL)
- **Database**: MySQL 5.7+/MariaDB
- **Frontend**: Bootstrap, custom CSS
- **Web Server (local)**: XAMPP/WAMP/MAMP (Apache + PHP + MySQL)

---

## 📁 Project Structure (Quick Map)

```
oncosecure/
├── admin/        # Admin dashboard & management (incl. doctor management & uploads)
├── api/          # AJAX/API endpoints
├── assets/       # CSS/JS/images
├── auth/         # Login, Register, Reset Password, CAPTCHA
├── doctor/       # Doctor module
├── includes/     # DB connection, helpers (sanitize, CSRF, guards)
├── patient/      # Patient module
├── uploads/      # Uploaded files (secured via checks)
├── create_admin.php   # One‑time bootstrap to create initial admin (DELETE after use)
├── index.php
└── schema.sql    # Full database schema
```

---

## 🚀 Quick Start (Beginner‑Friendly)

### 1) Install Local Environment
- **Option A** (Windows/Linux/Mac): Install [XAMPP](https://www.apachefriends.org/)
- **Option B** (Windows): Install WAMP
- **Option C** (Mac): Install MAMP

Ensure Apache and MySQL are running.

### 2) Place the Project
- Copy the project folder `oncosecure` into your web root:
  - **XAMPP**: `htdocs/`
  - **WAMP**: `www/`
  - **MAMP**: `htdocs/`

Your path should look like: `.../htdocs/oncosecure`

### 3) Create the Database
1. Open **phpMyAdmin** → click **SQL** tab.
2. Open the file `schema.sql` and run its contents.
   - This creates the **oncosecure** database and all required tables.

### 4) Configure DB Connection
Open `includes/db.php` and verify credentials:
```php
$DB_HOST = '127.0.0.1';
$DB_NAME = 'oncosecure';
$DB_USER = 'root';
$DB_PASS = '';
```
> Adjust `DB_USER`/`DB_PASS` if your local MySQL has a password.

### 5) Create the First Admin
Open `http://localhost/oncosecure/create_admin.php` **once** to create:
- **Email**: `admin@gmail.com`
- **Password**: `Admin@1234` (hashed in DB)

> 🔴 **Important**: **DELETE** the file `create_admin.php` immediately after this step.

### 6) Run the App
Visit: `http://localhost/oncosecure/`  
Use the admin credentials above to log in, explore modules, and create Doctors/Patients.

---

## 🧑‍💻 How to Test Security Features (Checklist)

- **Authentication**
  - Try wrong passwords → see error and logs.
  - Verify password changes enforce hashing and (optionally) forced change on next login.

- **CSRF**
  - Submit a form with a missing/invalid CSRF token → request should be rejected.

- **CAPTCHA**
  - Deliberately enter a wrong CAPTCHA on login → login should fail.

- **RBAC**
  - Log in as each role (admin/doctor/patient) → verify that cross‑role pages are blocked.

- **SQL Injection**
  - Attempt to use quotes or `OR '1'='1'` in input fields → should be neutralized by prepared statements.

- **XSS**
  - Try submitting `<script>alert(1)</script>` in text inputs → should be neutralized in views by `htmlspecialchars`.

- **Password Reset Flow**
  - Request password reset → ensure tokens expire and cannot be reused.

- **Uploads (Admin)**
  - Try uploading a file with a dangerous extension → should be blocked; only whitelisted types should pass.

---

## 🔒 Known Limitations / Risks (for Cybersecurity Review)

- **Session Security Hardening**
  - Consider regenerating session ID upon login, setting secure cookie flags (`HttpOnly`, `SameSite`, `Secure`), and shorter session lifetimes.

- **CAPTCHA Strength**
  - Current CAPTCHA is basic; replace with a stronger service (e.g., reCAPTCHA) if possible.

- **Uploads Directory**
  - Ensure uploads are stored **outside** public web root or served via download script with strict MIME checks.
  - Enforce size/type checks and randomize filenames.

- **HTTPS**
  - Local dev often runs on HTTP; production should force **HTTPS** with HSTS.

- **Security Headers**
  - Add headers like: `Content-Security-Policy` (CSP), `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, `Referrer-Policy`, etc.

- **Rate Limiting / Lockout**
  - Add login throttling or lockouts after repeated failures; record IP/user‑agent in logs (schema already supports IP).

- **Audit Log Coverage**
  - Expand logging coverage and review procedures (e.g., admin viewing sensitive records).

---

## 📌 Suggested Future Enhancements (Feature Requirements)

1. **2‑Factor Authentication (2FA)**  
   - TOTP (Google Authenticator) during login; backup codes.

2. **Role‑Scoped Access Policies**  
   - Centralized middleware/guard functions with least‑privilege defaults.

3. **Session Management Hardening**  
   - `session_regenerate_id(true)` on login, strict cookie params, idle & absolute timeouts.

4. **Account Lockout & Login Throttling**  
   - Temporary lock after N failed attempts; exponential backoff; IP‑based rate limiting.

5. **Advanced Password Policy**  
   - Enforce length/complexity, breach checks with k‑Anon HIBP API, password history, rotation when needed.

6. **Comprehensive Audit Logging**  
   - Tamper‑resistant logs, separate storage, admin UI for reviews & exports.

7. **Secure File Upload Pipeline**  
   - Move uploads outside web root, MIME sniff + extension whitelist, virus scan integration (ClamAV), signed download URLs.

8. **Email Verification & Trusted Devices**  
   - Verify email on registration; device fingerprinting and notify on new device login.

9. **Security Headers & CSP**  
   - Strict CSP with nonces; headers middleware.

10. **Backup & Disaster Recovery**  
    - Regular DB backups, restore drills, and encryption at rest (disk‑level or column‑level for sensitive data).

11. **Secrets Management**  
    - Move DB credentials to environment variables; avoid hard‑coding secrets; `.env` file support (e.g., `vlucas/phpdotenv`).

12. **Automated Tests (Security‑Focused)**  
    - PHPUnit tests for auth, CSRF, RBAC, and API endpoints; GitHub Actions CI with static analysis (PHPStan, Psalm) and SAST (Semgrep).

13. **Monitoring & Alerts**  
    - Failed login spikes, suspicious IP ranges, admin activity alerts.

14. **Privacy & Compliance**  
    - Data retention policies, consent management, and data export/delete endpoints.

---

## 🧭 Where to Find Important Code

- `includes/db.php` — PDO DB connection (edit credentials here)
- `includes/functions.php` — CSRF token helpers, sanitization, session guards
- `auth/login.php` — login + CSRF + CAPTCHA
- `auth/register.php` — validation + hashing (`password_hash`)
- `auth/reset_password.php`, `auth/reset_verify.php` — tokenized password reset
- `admin/add_doctor.php`, `admin/edit_doctor.php` — file uploads and validations
- `schema.sql` — full schema (users, logs, password_resets, etc.)
- `create_admin.php` — one‑time bootstrap (delete after use)

---

## 👤 Author / Course

- **Student**: (Add your name here)
- **Course**: Final Year Project — Cybersecurity
- **Supervisor**: (Add your supervisor/department)
- **Email**: (Add a contact email if permitted)

> If you use this in a demo, **remove default admin credentials**, and create fresh users.

---

## 🧾 License

Educational use. You may adapt for academic demonstrations. For production deployments, conduct a full security review and penetration testing.
