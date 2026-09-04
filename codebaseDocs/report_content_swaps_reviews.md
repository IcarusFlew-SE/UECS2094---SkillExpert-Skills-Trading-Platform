# Report content — Swap & Review Module

## Website structure (this module's contribution)

This module implements the swap-request lifecycle and the review/comment
system described in the assignment brief: "request swap – connect",
"accept/decline/complete swap", and "leaving reviews and comments".

| File | Role |
|---|---|
| `database/schema.sql` | `swapRequests`, `reviews`, `comments` tables |
| `includes/swap_functions.php` | Shared PDO query helpers, flash-message helper, credit ledger |
| `public/credits.php` | Credit balance + transaction history page |
| `includes/review_form_partial.php` | Reusable star-rating review form, included per completed swap |
| `public/swaps.php` | "My Swaps" (Requests) page — received/sent tabs, all actions |
| `public/details.php` | Skill details page — request-swap form, reviews list, comment thread |
| `actions/swap_request_create.php` | Create a swap request (Create) |
| `actions/swap_request_action.php` | Accept / decline / cancel / complete (Update) |
| `actions/review_submit.php`, `review_delete.php` | Post / delete a review (Create, Delete) |
| `actions/comment_submit.php`, `comment_delete.php` | Post / delete a comment (Create, Delete) |
| `assets/js/swaps.js` | Confirm dialogs, review/comment form validation, char counters |
| `assets/css/style.css` (bottom section) | Status badges, swap cards, CSS-only tabs, star-rating widget, responsive rules |

## Database design decisions

### Why PDO (not mysqli or a framework ORM)

We use **PDO** (PHP Data Objects) as the single database access layer across every
page and action script. PDO was chosen deliberately over the older `mysqli`
extension or a full ORM because it matches the assignment's vanilla-PHP
constraint while still giving us production-grade safety:

- **Prepared statements with bound parameters** on every query — user input is
  never concatenated into SQL strings, which closes the SQL-injection surface.
- **A single connection object (`$pdo`)** initialised once in `config/db.php`
  and passed into helper functions, keeping the same pattern whether we are
  reading swap lists or completing a credit transfer inside a transaction.
- **`PDO::ERRMODE_EXCEPTION`** so constraint violations (duplicate reviews,
  insufficient credits) surface as catchable exceptions instead of silent
  failures.
- **Database-agnostic API** — if the schema were ever moved from MySQL to
  MariaDB or another engine, the PHP layer would need minimal changes.

This is not "framework magic": every `INSERT`, `UPDATE`, and `SELECT` is written
by hand with explicit column names, which makes the report's code snippets easy
to trace back to the schema.

### Credit economy vs. skill barter

SkillExpert supports **two ways to exchange time**, and the code treats them
differently on purpose:

| Swap type | `offeredSkillId` | Credits on complete |
|---|---|---|
| **Straight learn** | `NULL` (open request) | Requester −1, teacher +1 |
| **Skill barter** | set to requester's own skill | No change |

The rule is implemented in `completeSwapWithCredits()` inside a single database
transaction with `SELECT … FOR UPDATE` row locks on the swap and the requester's
balance, so a double-click on "Mark Complete" cannot deduct credits twice.
Completion is **blocked** when the requester has fewer than 1 credit, with a
flash error shown on the swaps page.

New members receive **5 welcome credits** on registration (recorded in the
`creditTransactions` ledger). The nav badge shows the live balance; the
**My Credits** page (`public/credits.php`) lists every debit and credit with
timestamps.

`swapRequests` records who is asking whom for a swap, on which skill, with
an optional skill offered in return, and a `status` enum that models the
whole lifecycle: `pending → accepted → completed`, or `pending → declined`
/ `cancelled`. A `CHECK (requesterId <> receiverId)` constraint stops a user
requesting a swap on their own listing at the database level, not just in
PHP.

`reviews` is deliberately **not** linked to `skillId` directly — it's linked
to `swapId`, and the skill is found by joining through the swap
(`swapRequests.skillId`). This means a review can only exist if its author
actually completed a swap for that skill: a lightweight "verified swap"
guarantee, enforced structurally rather than by a flag. A
`UNIQUE (swapId, userId)` constraint caps each participant to one review per
swap, at the database level (the app also checks this first, for a friendly
error message instead of a raw SQL exception).

`comments` is a separate, open discussion thread on a skill listing — no
completed swap required — matching the brief's "reviews **and** comments"
as two distinct features with different trust levels.

## Key code snippets with explanations

### 1. Preventing a user from acting on someone else's swap request

Every state change (accept/decline/cancel/complete) re-checks both the
current status **and** that the logged-in user is actually one of the two
participants — server-side, regardless of which button the browser sent.
This is the core authorization logic in `actions/swap_request_action.php`:

```php
$isRequester = ((int) $swap['requesterId'] === $currentUserId);
$isReceiver  = ((int) $swap['receiverId'] === $currentUserId);

switch ($action) {
    case 'accept':
        if (!$isReceiver) {
            $errorMsg = 'Only the skill owner can accept a request.';
        } elseif ($swap['status'] !== 'pending') {
            $errorMsg = 'That request is no longer pending.';
        } else {
            $newStatus = 'accepted';
        }
        break;
    // decline / cancel / complete follow the same pattern
}
```

We tested this directly: logging in as a user who is neither the requester
nor receiver of a given swap and POSTing `action=accept` leaves the row's
status untouched — the handler rejects it before any `UPDATE` runs.

### 2. Parameterised queries everywhere (SQL injection prevention)

All database access goes through PDO prepared statements with `?`
placeholders — user input is never concatenated into SQL. Example from
`includes/swap_functions.php`:

```php
function getReceivedRequests(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare(
        "SELECT sr.*, sk.title AS skillTitle, req.name AS requesterName, ...
         FROM swapRequests sr
         JOIN skills sk ON sr.skillId = sk.id
         JOIN users req ON sr.requesterId = req.id
         WHERE sr.receiverId = ?
         ORDER BY FIELD(sr.status, 'pending','accepted','completed','declined','cancelled'), sr.createdAt DESC"
    );
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}
```

The `FIELD(...)` ordering pushes actionable requests (pending, then
accepted) to the top of the list, so the user doesn't have to scroll past
old completed/declined ones to find what needs a response.

### 3. Preventing duplicate reviews (defence in depth)

The database enforces one review per user per swap via a unique constraint;
the application layer checks the same rule first, so the user sees a clear
message instead of a raw database error — and if a double-click race
condition slips past the first check, the `catch` block still handles it
gracefully:

```php
if (userHasReviewed($pdo, $swapId, $currentUserId)) {
    setFlash('error', "You've already reviewed this swap.");
    header("Location: /main/public/swaps.php");
    exit;
}

try {
    $stmt = $pdo->prepare(
        "INSERT INTO reviews (swapId, userId, rating, comment) VALUES (?, ?, ?, ?)"
    );
    $stmt->execute([$swapId, $currentUserId, $rating, $comment ?: null]);
} catch (PDOException $e) {
    if ($e->getCode() === '23000') { // unique constraint violation
        setFlash('error', "You've already reviewed this swap.");
    } else {
        throw $e;
    }
}
```

### 4. CSS-only tab switching on the Requests page (event-driven, no JS)

"Received" and "Sent" requests are shown as tabs using the checkbox/radio +
`:checked` sibling-selector trick already established by the project's
mobile nav (`includes/nav.php`), so the whole UI works even with JavaScript
disabled — consistent with the assignment's CO2 "event-driven client-side"
requirement while keeping the interaction purely declarative:

```css
.swap-tab-panel { display: none; }
#tab-received:checked ~ #panel-received,
#tab-sent:checked ~ #panel-sent { display: block; }
```

```html
<input type="radio" name="swap-tab" id="tab-received" checked>
<input type="radio" name="swap-tab" id="tab-sent">
<div class="swap-tab-labels">
    <label for="tab-received">Received</label>
    <label for="tab-sent">Sent</label>
</div>
<div class="swap-tab-panel" id="panel-received">...</div>
<div class="swap-tab-panel" id="panel-sent">...</div>
```

### 5. Event-driven client-side validation (`assets/js/swaps.js`)

Beyond the CSS-only tabs, genuine JavaScript event listeners add
instant feedback before a form ever reaches the server — confirmation
dialogs on destructive actions, and a check that a star rating was
actually picked:

```js
function attachConfirmGuards() {
    document.querySelectorAll('[data-confirm]').forEach(function (button) {
        button.addEventListener('click', function (event) {
            if (!window.confirm(button.getAttribute('data-confirm'))) {
                event.preventDefault(); // stops the form submitting
            }
        });
    });
}
```

### 6. The star-rating widget (pure CSS visual, radio inputs underneath)

Reviews collect a 1–5 star rating using five radio inputs rendered in
descending DOM order (5,4,3,2,1) inside a `flex-direction: row-reverse`
container — a well-known CSS-only star-rating pattern that needs no
JavaScript to look and feel right, and still submits as a normal HTML form
field:

```css
.star-rating { display: flex; flex-direction: row-reverse; }
.star-rating input:checked ~ label,
.star-rating label:hover,
.star-rating label:hover ~ label { color: #f59e0b; }
```

### 7. Atomic credit transfer on swap completion

When a swap is marked `completed` and no skill was offered in return, credits
move in the same transaction as the status update — requester loses 1, teacher
gains 1 — and both sides get a ledger row in `creditTransactions`:

```php
$pdo->beginTransaction();
// lock swap row + requester balance …
if (empty($lockedSwap['offeredSkillId'])) {
    // deduct 1 from requester, add 1 to receiver
    recordCreditTransaction($pdo, $requesterId, -1, 'swap_learn', '…', $swapId, $receiverId);
    recordCreditTransaction($pdo, $receiverId,  1, 'swap_teach', '…', $swapId, $requesterId);
}
// UPDATE swapRequests SET status = 'completed' …
$pdo->commit();
```

Barter swaps skip the credit branch entirely, so exchanging "guitar for Spanish"
never touches either user's balance.

## Testing performed

Ran the module end-to-end against a live MySQL instance with the seeded
demo data (not just PHP's `php -l` syntax check): logged in as two
different demo users, created a new swap request, accepted it, marked it
complete, submitted a review, confirmed the duplicate-review guard rejects
a second attempt, confirmed a non-participant is blocked from
accepting/declining someone else's request, declined a separate request,
and posted then deleted a comment — verifying the database state after
every step matched what the UI showed. No PHP warnings, notices, or errors
were logged during any of this.

## Conclusion (module-specific)

This module covers the full "connect → negotiate → complete → review"
loop that sits at the centre of a skills-exchange platform: a user can
request a swap, the other party can accept or decline it, either side can
mark it complete once the exchange has happened, and only participants of
a genuinely completed swap can leave a review — with a separate, more open
comment thread for general discussion. All four CRUD operations are
demonstrated across the three new tables (create/read/update on
`swapRequests`; create/read/delete on `reviews` and `comments`), matching
the assignment's requirement for meaningful, non-read-only database
functionality.
