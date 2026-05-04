# XUBand Digital Filing System

Xavier University Band — Digital Filing System  
Stack: PHP 8.2 · MySQL 8 · Apache · Vanilla JS

---

## Features

| Module | Description |
|---|---|
| 🎼 Music Sheets | Upload/download PDFs, images, audio. Filter by section. |
| 🎓 Scholarships | Track GPA, band scores, status, allowance per member. |
| ✅ Attendance | Mark per event, auto-compute penalty points. |
| 📢 Announcements | Post/pin announcements with expiry dates. |
| 📅 Events & Calendar | Monthly calendar view, CRUD events. |
| 👥 Members | Add/edit/delete members, assign roles. |
| 👤 Profile | Members view/edit their own data, change password. |

## Roles

| Role | Permissions |
|---|---|
| **Moderator** | Full access, manage all users including officers |
| **Officer** | Manage members, attendance, scholarships, announcements, events |
| **Member** | View own data, browse music sheets & announcements |

---

## Deploy to Railway

### 1. Push to GitHub
```bash
git init
git add .
git commit -m "Initial commit"
git remote add origin <your-repo-url>
git push -u origin main
```

### 2. Create Railway Project
1. Go to [railway.app](https://railway.app)
2. New Project → Deploy from GitHub repo
3. Add a **MySQL** service (click + → Database → MySQL)

### 3. Link MySQL to PHP service
In your PHP service settings → Variables, Railway auto-injects:
- `MYSQLHOST`, `MYSQLPORT`, `MYSQLDATABASE`, `MYSQLUSER`, `MYSQLPASSWORD`

No manual env vars needed for DB — Railway handles it.

### 4. Initialize Database
Visit: `https://your-app.railway.app/setup.php?token=xuband_setup_2024`

✅ This creates all tables and seeds demo data.

**Delete setup.php after running!**

### 5. Login
- Moderator: `moderator@xuband.edu.ph` / `password`
- Officer: `gabutin@xuband.edu.ph` / `password`
- Member: `macalaguing@xuband.edu.ph` / `password`

---

## Local Development

### With Docker
```bash
docker build -t xuband .
docker run -p 8080:80 \
  -e MYSQLHOST=host.docker.internal \
  -e MYSQLDATABASE=xuband \
  -e MYSQLUSER=root \
  -e MYSQLPASSWORD=yourpassword \
  xuband
```

### Without Docker (XAMPP/WAMP)
1. Copy `xuband/` to `htdocs/xuband/`
2. Create MySQL DB: `xuband`
3. Run `sql/schema.sql`
4. Point Apache DocumentRoot to `public/`
5. Visit `http://localhost/setup.php?token=xuband_setup_2024`

---

## Project Structure

```
xuband/
├── Dockerfile          ← PHP 8.2 + Apache
├── apache.conf         ← Virtual host config
├── railway.json        ← Railway deployment config
├── sql/schema.sql      ← DB schema + seed data
├── includes/
│   ├── config.php      ← DB config, constants
│   ├── db.php          ← PDO helpers
│   ├── auth.php        ← Session, login, roles
│   ├── helpers.php     ← Utilities, file upload
│   └── layout.php      ← Shared HTML layout
└── public/
    ├── index.php       ← Entry point (redirects)
    ├── login.php
    ├── dashboard.php
    ├── music-sheets.php
    ├── scholarships.php
    ├── attendance.php
    ├── announcements.php
    ├── events.php
    ├── members.php
    ├── profile.php
    ├── setup.php       ← DB initializer (delete after use)
    ├── uploads/        ← File storage
    └── assets/         ← CSS + JS
```
