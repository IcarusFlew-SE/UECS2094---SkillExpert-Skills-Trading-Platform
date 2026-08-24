# SkillExpert — Skills Trading Platform

UECS2094 / UECS2194 / EECS2194 Web Application Development group assignment.
A peer-to-peer skills exchange site: users post skills they can teach, browse
and request swaps with other users, and leave reviews once a swap is complete.

Built entirely with vanilla HTML, CSS, JavaScript, PHP and MySQL — no
frameworks or libraries, per the assignment's technology restrictions.

## 1. Requirements

- PHP 8.x with the `pdo_mysql` extension
- MySQL 5.7+ or MariaDB 10.x
- Any local server stack that can run PHP (XAMPP, MAMP, WAMP, `php -S`, etc.)

## 2. Database setup

1. Start your MySQL/MariaDB server.
2. Import the schema (this creates the `swapexpert` database, every table,
   and a small set of demo/seed data so the site is clickable immediately):

   ```
   mysql -u root -p < database/schema.sql
   ```

3. Check `config/db.php` matches your local credentials:

   ```php
   $host     = 'localhost';
   $dbname   = 'swapexpert';
   $username = 'root';
   $password = '';
   ```

### Demo accounts (from the seed data)

| Email | Password |
|---|---|
| alice@example.com | Password123! |
| ben@example.com | Password123! |
| chandra@example.com | Password123! |
| divya@example.com | Password123! |

Alice ↔ Ben already have a **completed** swap with reviews on both sides;
Chandra ↔ Divya have an **accepted** (in-progress) swap; Ben has a **pending**
request on Alice's guitar listing — so every swap status and the review flow
can be seen without doing anything first.

## 3. Running the project

The site expects to be served from a folder named `main` on your web root
(every internal link is an absolute path like `/main/public/index.php`,
`/main/auth/login.php`, etc.).

**Option A — PHP's built-in server (quickest for local testing):**

```
# from the folder that CONTAINS this project folder:
mkdir -p webroot
ln -s /path/to/this-project webroot/main   # Windows: use mklink /D instead of ln -s
cd webroot
php -S localhost:8000
```

Then visit `http://localhost:8000/main/public/index.php`.

**Option B — XAMPP/WAMP/MAMP:** copy (or symlink) this project folder into
`htdocs`/`www` and rename it (or the symlink) to `main`, then visit
`http://localhost/main/public/index.php`.

## 4. Project structure

```
actions/    POST-only handlers (swap requests, accept/decline/complete, reviews, comments)
auth/       Login, register, logout, session guard
config/     Database connection (config/db.php)
database/   schema.sql — full schema + demo data
includes/   Shared header/footer/nav + swap/review helper functions
public/     User-facing pages (home, browse, details, swaps/requests)
assets/     css/, js/, img/
```

## 5. Module ownership

- **Auth, navigation, skills (post/browse/details layout)** — teammate.
- **Swap requests, accept/decline/complete, reviews, comments** — Barry.
  See `docs/report_content_swaps_reviews.md` for the code walkthrough used
  in the project report.

## 6. Known issues in shared code (flag for the team, not yet fixed here)
**Currently None**
