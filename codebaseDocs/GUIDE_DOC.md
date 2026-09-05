# SkillExpert — Beginner's Guide to the Codebase

This document teaches you how SkillExpert works from the ground up. No prior PHP experience required — read it in order if you are new to web development.

---

## 1. What is SkillExpert?

SkillExpert is a **peer-to-peer skills exchange website**. Users can:

- **Post** skills they can teach (e.g. guitar, Excel, Spanish)
- **Browse** other people's skills
- **Request a swap** — ask to learn from someone
- **Complete** the swap after the session happens
- **Leave reviews** (only after a real completed swap)
- **Save** skills to a wishlist
- **Spend or earn credits** when learning without offering a skill in return

**Tech stack (assignment rules):**

| Layer | Technology |
|-------|------------|
| Pages | HTML + PHP |
| Styling | CSS (no Bootstrap/Tailwind) |
| Interactivity | Vanilla JavaScript |
| Database | MySQL via PHP **PDO** |

There is **no framework** involved. Every file is plain PHP that the server runs when you visit a URL.

---

## 2. How a web request works (big picture)

When you open `http://localhost/main/public/browse.php` in your browser:

```
Browser  →  Apache (WAMP)  →  runs browse.php  →  talks to MySQL  →  sends HTML back
```

1. **Browser** asks for a URL.
2. **Apache** finds the matching `.php` file and executes it on the server.
3. **PHP** can read the database, check if you are logged in, build HTML.
4. **Browser** receives HTML (+ CSS/JS) and displays the page.

When you **submit a form** (e.g. "Send Swap Request"), the form usually posts to an **`actions/`** file. That file updates the database and **redirects** you back — this is called **Post/Redirect/Get (PRG)** and stops accidental double-submits on refresh.

```
Form POST  →  actions/swap_request_create.php  →  INSERT into DB  →  redirect to details.php
```

---

## 3. Folder structure (where everything lives)

```
main/
├── public/          ← Pages users SEE (browse, details, my_skills, swaps…)
├── actions/         ← Form handlers that WRITE to the database (PRG: create, update, delete)
├── auth/            ← Login, register, logout, session guard
├── includes/        ← Reusable chunks: header, nav, footer, helper functions
├── config/          ← Database connection (db.php)
├── database/        ← schema.sql (tables + demo data)
├── assets/
│   ├── css/         ← One stylesheet per page (including my-skills.css) + shared layout/components
│   ├── js/          ← browse.js, skills-posting.js, swaps.js
│   └── img/         ← Logo
└── codebaseDocs/    ← Project documentation and technical reports (like this file)
```

**Rule of thumb:**

| Folder | Job |
|--------|-----|
| `public/` | Show data (Read) |
| `actions/` | Change data (Create, Update, Delete) then redirect |
| `includes/` | Shared PHP/HTML pieces used by many pages |
| `auth/` | Who is logged in? |

---

## 4. The database (8 tables)

All data lives in MySQL database **`swapexpert`**. Defined in `database/schema.sql`.

| Table | Stores |
|-------|--------|
| `users` | Name, email, password hash, **creditsBalance** |
| `skills` | Skill listings (title, category, description, owner) |
| `swapRequests` | Swap proposals and status (`pending`, `accepted`, `completed`, …) |
| `reviews` | Star ratings tied to a **completed swap** (verified reviews) |
| `comments` | Public Q&A on a skill (no swap required) |
| `savedSkills` | Wishlist: which user saved which skill |
| `contactMessages` | Contact form submissions |
| `creditTransactions` | Optional log of credit changes (welcome bonus, learn/teach) |

**Relationships & Foreign Key Cascades:**

```
users ──< skills          (ON DELETE CASCADE: deleting account removes their skills)
users ──< swapRequests    (ON DELETE CASCADE for both requesterId and receiverId)
skills ──< swapRequests   (ON DELETE CASCADE for requested skillId; ON DELETE SET NULL for offeredSkillId)
swapRequests ──< reviews  (ON DELETE CASCADE: reviews are tied to completed swaps)
skills ──< comments       (ON DELETE CASCADE: deleting skill cleans up discussion)
users + skills ──< savedSkills (ON DELETE CASCADE: wishlist junction entries)
```

**What happens when a skill is deleted?**
- Dependents in `swapRequests`, `comments`, and `savedSkills` are removed automatically by MySQL.
- Any reviews linked to those swaps are cleanly removed through the `swapRequests` cascade.
- If someone else offered this skill in a barter proposal, `offeredSkillId` is safely updated to `NULL` (`ON DELETE SET NULL`) without deleting their swap.
- Completed swap audit records in `creditTransactions` retain ledger integrity with `swapId = NULL`.

**Connecting in PHP** — every file that needs the DB starts with:

```php
require_once __DIR__ . '/../config/db.php';
// Now $pdo is ready — a PDO connection object
```

**Example query (prepared statement — safe from SQL injection):**

```php
$stmt = $pdo->prepare("SELECT * FROM skills WHERE id = ?");
$stmt->execute([$skillId]);
$skill = $stmt->fetch();
```

The `?` is a **placeholder**. User input never gets stitched into the SQL string.

---

## 5. Sessions and login

PHP **sessions** remember who is logged in between page loads (via a cookie `PHPSESSID`).

After login, these keys are set:

```php
$_SESSION['user_id']  // integer — user's id in the database
$_SESSION['name']      // display name
```

**Important files:**

| File | Purpose |
|------|---------|
| `auth/login.php` | Shows login form; checks password; sets session |
| `auth/register.php` | Creates user; gives 5 welcome credits; logs in |
| `auth/logout.php` | Destroys session |
| `auth/session_check.php` | If not logged in → redirect to login (used on protected pages) |

**Protected page pattern:**

```php
require_once __DIR__ . '/../auth/session_check.php';
// If we get past this line, user is logged in
```

**Security highlights:**

- Passwords stored as **bcrypt hashes** (`password_hash` / `password_verify`)
- `session_regenerate_id(true)` on login stops session fixation attacks
- Output escaped with `htmlspecialchars()` to prevent XSS

---

## 6. How every page is built (includes)

Most `public/` pages follow this pattern:

```php
<?php
require_once __DIR__ . '/../config/db.php';        // 1. Database
// ... fetch data with $pdo ...

$pageTitle = 'Browse Skills';
require_once __DIR__ . '/../includes/header.php';  // 2. <html>, <head>, opens <main>
?>

<link rel="stylesheet" href="/main/assets/css/browse.css?v=...">  <!-- 3. Page CSS -->

<!-- 4. Your page HTML here -->

<?php require_once __DIR__ . '/../includes/footer.php'; ?>  <!-- 5. Footer, closes </html> -->
```

**`includes/header.php`** starts the session, sets `$isLoggedIn`, prints `<head>`, and includes **`nav.php`**.

**`includes/nav.php`** shows different links for logged-in vs guest users:
- **Logged in:** Browse, Saved (Wishlist), **My Skills** (creator dashboard), Post a Skill, My Teaching Requests, My Swaps, Contact, plus the **credit badge** (links to My Credits) and Logout.
- **Logged out:** Home, Browse, Contact, Login, Register.

**`includes/footer.php`** closes `</main>`, prints the site footer, and loads **`swaps.js`** once globally.

---

## 7. Features walkthrough (user journey → code)

### 7.1 Home — `public/index.php`

- Queries the 6 newest skills from MySQL.
- Shows hero, featured cards, "How it works".
- **No login required.**

### 7.2 Browse — `public/browse.php` + `assets/js/browse.js`

- PHP loads **all** skills + teacher names in one query.
- JavaScript filters cards by search text and category pill — **no extra server round-trip** when you type.

### 7.3 Skill details — `public/details.php`

One page, many features:

| UI section | Backend |
|------------|---------|
| Skill info | `SELECT` from `skills` JOIN `users` |
| Owner actions (if owner) | Direct **Edit Skill** (`my_skills.php?edit=...`) & **Delete Skill** (POST → `actions/skill_delete.php`) |
| Swap proposal form (if visitor) | POST → `actions/swap_request_create.php` |
| Save / Wishlist toggle (if visitor) | POST → `actions/skill_save.php` or `skill_unsave.php` |
| Reviews list | `getReviewsForSkill()` in `swap_functions.php` |
| Comment thread | `getCommentsForSkill()` + `comment_submit.php` |

**Swap form logic:** If you offer one of your own skills, it's a **barter** (no credits). If you leave the offer empty, completing the swap costs **1 credit**.

### 7.4 Post a skill — `public/posting.php` + `actions/create_skills.php`

- Guests see "create an account" CTA.
- Logged-in users fill the form; POST goes to `create_skills.php`.
- `skills-posting.js` powers live preview and 3D card tilt.
- Post-publish success banner includes an instant shortcut link to view and manage listings on My Skills.

### 7.5 My skills — `public/my_skills.php` + `assets/css/my-skills.css`

The central creator dashboard for skill owners:

- **Metrics ribbon:** Real-time summary counts for Active Listings, Swap Inquiries, Active Exchanges (with a pulsating green status light), and Wishlist Saves.
- **Skill cards with live metrics:** Each card displays its category badge, wishlist saves counter, swap request counts, and an impact alert if active exchanges are linked.
- **Direct actions:**
  - **View:** Jump to public `details.php?id=...` page.
  - **Edit:** Opens the inline glassmorphic edit card (`#edit-skill`) to modify title, category, and description → saved via `actions/skill_update.php`.
  - **Delete:** Protected by a `[data-confirm]` warning modal; executes cascade deletion and rollback protection via `actions/skill_delete.php`.
- **Empty state:** Welcoming onboarding container when a user has not published skills yet.

### 7.6 Swaps — `public/swaps.php`

- **Received** tab: requests on your skills.
- **Sent** tab: requests you made.
- Actions POST to `actions/swap_request_action.php` with `action=accept|decline|cancel|complete`.
- Completed swaps include `includes/review_form_partial.php` for star ratings.

### 7.7 Teaching requests — `public/teaching_requests.php`

Same swap data as "Received" on My Swaps, but focused on **skill owners** with a lifecycle guide UI. Uses the same review partial and action handlers.

### 7.8 Reviews — `actions/review_submit.php`

- Only swap **participants** can review.
- Only when swap status is **`completed`**.
- One review per user per swap (DB unique constraint + `userHasReviewed()` check).

### 7.9 Wishlist — `public/saved.php`

- Helpers in `includes/saved_functions.php`.
- `skill_save.php` / `skill_unsave.php` add/remove rows in `savedSkills`.

### 7.10 Contact — `public/contact.php`

- Form POST → `actions/contact_submit.php` → row in `contactMessages`.
- Works for guests and logged-in users.

### 7.11 Credits — `public/credits.php`

- Shows `users.creditsBalance` and rows from `creditTransactions`.
- Credit moves happen inside `completeSwapWithCredits()` when a swap without barter is completed.

---

## 8. Swap lifecycle (core business logic)

```
pending ──accept──> accepted ──complete──> completed
   │                    │
   ├──decline──> declined
   └──cancel───> cancelled  (requester only, while pending)
```

**Key file:** `includes/swap_functions.php`

| Function | What it does |
|----------|----------------|
| `getSwapById()` | One swap row with names joined |
| `getReceivedRequests()` | Swaps where you are the teacher |
| `getSentRequests()` | Swaps you requested |
| `getReviewsForSkill()` | Reviews via swap → skill link |
| `completeSwapWithCredits()` | Marks complete + moves credits in a transaction |
| `recordCreditTransaction()` | Writes ledger row |
| `setFlash()` / `getAndClearFlash()` | One-time success/error messages after redirect |
| `renderStars()` | Turns `4` into `★★★★☆` for display |

**Credit rules in `completeSwapWithCredits()`:**

```
IF offeredSkillId is empty (credit learn):
    requester balance -= 1  (blocked if < 1)
    receiver balance += 1
    log both sides in creditTransactions
ELSE (barter):
    no credit change
```

---

## 9. JavaScript files (what runs in the browser)

| File | Loaded on | Purpose |
|------|-----------|---------|
| `browse.js` | `browse.php` | Search + category filter |
| `skills-posting.js` | `posting.php` | Live preview, category picker, 3D tilt |
| `swaps.js` | **Every page** (via `footer.php`) | Confirm dialogs, review validation, **character counters** |

### Bug you had: duplicate character counters

On **My Teaching Requests**, the review textarea showed **"1000 characters left"** twice (or more).

**Cause:** `swaps.js` was loaded **twice**:

1. Explicitly in `teaching_requests.php` (line 125 — now removed)
2. Globally in `includes/footer.php` (line 15)

Each load ran `attachCharCounters()`, which inserted a new `<p class="char-counter">` under every `textarea[maxlength]`.

**Fix:**

- Removed the extra `<script>` from `teaching_requests.php` — footer already loads `swaps.js`.
- Added a guard in `swaps.js` so a textarea only gets one counter (`data-char-counter-attached`).

**Rule:** Load each JS file **once per page**. Use `footer.php` for site-wide scripts; only add page-specific scripts (like `browse.js`) on that page.

---

## 10. CSS organization

Shared (every page via `header.php`):

- `style.css` — colors, fonts, design tokens, CSS variables
- `layout.css` — navbar, footer, credit badge, responsive mobile toggle
- `components.css` — buttons, flash messages, forms, status pills

Page-specific (linked in each `public/*.php`):

- `home.css` — landing hero & feature grid
- `browse.css` — catalog, search bar & category filters
- `details.css` — full skill details & proposal form
- `skills-posting.css` — 3D card tilt & live post form
- `my-skills.css` — creator dashboard, metrics ribbon & inline editor
- `swaps.css` — received/sent swap requests & teaching dashboard
- `saved.css` — user wishlist
- `contact.css` — public contact form & cards
- `auth.css` — login & register panels
- `credits.css` — credit economy ledger & balance card

**Cache busting:** `?v=<?php echo filemtime(...); ?>` forces browsers to reload CSS after you edit files.

---

## 11. Security checklist (what to look for when reading code)

| Threat | How SkillExpert handles it |
|--------|---------------------------|
| SQL injection | PDO prepared statements (`?` placeholders) |
| XSS | `htmlspecialchars()` on all user-visible output |
| Unauthorized edits | `session_check.php` + ownership checks in actions before `UPDATE` / `DELETE` |
| Wrong person accepting swap | `swap_request_action.php` verifies requester/receiver |
| Double review | DB unique key + `userHasReviewed()` |
| Session fixation | `session_regenerate_id(true)` on login/register |
| Transaction failure | Transaction rollback (`$pdo->rollBack()`) on constraint or database exceptions |

---

## 12. How to trace a feature (learning exercises)

### Example 1: "What happens when I click Send Swap Request?"

1. Open `public/details.php` — find the form `action="/main/actions/swap_request_create.php"`.
2. Open `actions/swap_request_create.php` — read top to bottom:
   - `session_check.php` → must be logged in
   - Validate `skill_id`, optional `offered_skill_id`
   - Check not your own skill
   - Check no duplicate pending request
   - `INSERT INTO swapRequests`
   - `setFlash('success', ...)`
   - `header('Location: details.php?id=...')`
3. Browser loads details again — `getAndClearFlash()` shows green banner.

### Example 2: "What happens when I delete one of my skills?"

1. Click **Delete** on `public/my_skills.php` or `public/details.php`.
2. Browser displays confirmation dialog (`data-confirm` handled in `swaps.js`):
   *"Delete this skill permanently? This removes it for other users too, including saved entries, comments, reviews, and related swap requests."*
3. On confirm, browser POSTs `skill_id` to `actions/skill_delete.php`.
4. `actions/skill_delete.php` runs:
   - `session_check.php` ensures authentication.
   - Verifies ownership: `SELECT id, title FROM skills WHERE id = ? AND userId = ?`.
   - Pre-calculates cascade impact (counts active swap requests, comments, bookmarks, and reviews).
   - Starts a transaction: `$pdo->beginTransaction()`.
   - Executes `DELETE FROM skills WHERE id = ? AND userId = ?`.
   - MySQL's foreign key constraints automatically cascade deletions to dependent `swapRequests`, `comments`, and `savedSkills` (and `reviews` via swap cascade), while setting `offeredSkillId = NULL` for third-party barter swaps.
   - Commits transaction: `$pdo->commit()`.
   - Formats a helpful impact message: *"Skill '[title]' was deleted. Impact: 1 active request(s) closed, 2 saved-list item(s) removed..."*
   - Calls `setFlash('success', $message)` and redirects back to `public/my_skills.php`.
5. Browser reloads `my_skills.php` via GET (`PRG`), displaying the updated skills grid and green flash alert.

---

## 13. Demo accounts (for hands-on learning)

| Email | Password | Good for testing |
|-------|----------|------------------|
| `alice@example.com` | `Password123!` | Skill owner, incoming requests |
| `ben@example.com` | `Password123!` | Requester, swaps with Alice |
| `chandra@example.com` | `Password123!` | Accepted swap with Divya, saved list |
| `divya@example.com` | `Password123!` | Art skill owner |

**Suggested learning path:**

1. Log in as Ben → browse → open a skill → send swap request.
2. Log in as Alice → Teaching Requests → accept → complete.
3. Both users → My Swaps → leave a review.
4. Click credit badge → see ledger on My Credits.
5. Go to My Skills → edit a skill title or delete a listing → review the cascading update.

---

## 14. Assignment requirements (I–IX) at a glance

| # | Requirement | Where |
|---|-------------|-------|
| I | Home | `public/index.php` |
| II | CRUD (Multiple) | **Skills** (Create: `posting.php`, Read: `browse.php`/`details.php`/`my_skills.php`, Update/Delete: `my_skills.php` & `details.php`), **Swaps** (`swaps.php`), **Reviews** (`review_submit.php`/`review_delete.php`), **Comments** (`comment_submit.php`/`comment_delete.php`), **Contact**, **Wishlist** |
| III | Navigation | `includes/nav.php` (login-aware, responsive, includes direct **My Skills** access) |
| IV | Contact | `public/contact.php` |
| V | Listing + filter | `public/browse.php` |
| VI | Item details | `public/details.php` (includes direct owner edit/delete controls) |
| VII | Wishlist | `public/saved.php` |
| VIII | Login | `auth/` |
| IX | Responsive | CSS `@media` in layout + page stylesheets |

---

## 15. Glossary

| Term | Meaning |
|------|---------|
| **PDO** | PHP Data Objects — safe database API |
| **PRG** | Post/Redirect/Get — form submits, then redirect to a GET page |
| **Flash message** | One-time alert stored in `$_SESSION`, shown once after redirect |
| **Partial** | Reusable PHP include (e.g. `review_form_partial.php`) |
| **Barter swap** | Both users trade skills; no credits used |
| **Credit swap** | Learner pays 1 credit to teacher on completion |
| **Verified review** | Review linked to a completed swap, not a free-for-all rating |
| **Cascade delete** | Database engine automatically removes orphaned child records when parent is deleted |

---

## 16. Related docs

| File | Audience |
|------|----------|
| [README.md](../README.md) | Setup, features, architecture, demo data |
| [report_content_swaps_reviews.md](report_content_swaps_reviews.md) | Technical report on swap lifecycle, reviews, and credit transactions |

---

*Last updated after adding the My Skills dashboard, cascade deletion flows, and dedicated my-skills.css styling.*
