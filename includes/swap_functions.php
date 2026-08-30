<?php
/**
 * Shared helper functions for the Swap & Review module (Barry).
 *
 * Every function here expects a PDO connection ($pdo, from config/db.php)
 * to be passed in explicitly rather than relying on a global, so these can
 * be unit-tested or reused from any page/action script without surprises.
 *
 * Included by: public/swaps.php, public/details.php, actions/*.php
 */

/**
 * Fetch a single swap request row, joined with the skill title and both
 * users' names, so callers don't have to write the join every time.
 */
function getSwapById(PDO $pdo, int $swapId): array|false
{
    $stmt = $pdo->prepare(
        "SELECT sr.*, sk.title AS skillTitle, sk.userId AS skillOwnerId,
                req.name AS requesterName, rec.name AS receiverName
         FROM swapRequests sr
         JOIN skills sk   ON sr.skillId = sk.id
         JOIN users req   ON sr.requesterId = req.id
         JOIN users rec   ON sr.receiverId = rec.id
         WHERE sr.id = ?
         LIMIT 1"
    );
    $stmt->execute([$swapId]);
    return $stmt->fetch();
}

/**
 * All swap requests where the given user is the RECEIVER (i.e. someone is
 * asking to swap for one of their skills) — the "Received Requests" tab.
 */
function getReceivedRequests(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare(
        "SELECT sr.*, sk.title AS skillTitle,
                req.name AS requesterName,
                offSk.title AS offeredSkillTitle
         FROM swapRequests sr
         JOIN skills sk        ON sr.skillId = sk.id
         JOIN users req        ON sr.requesterId = req.id
         LEFT JOIN skills offSk ON sr.offeredSkillId = offSk.id
         WHERE sr.receiverId = ?
         ORDER BY FIELD(sr.status, 'pending', 'accepted', 'completed', 'declined', 'cancelled'),
                  sr.createdAt DESC"
    );
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

/**
 * All swap requests the given user SENT — the "Sent Requests" tab.
 */
function getSentRequests(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare(
        "SELECT sr.*, sk.title AS skillTitle,
                rec.name AS receiverName,
                offSk.title AS offeredSkillTitle
         FROM swapRequests sr
         JOIN skills sk        ON sr.skillId = sk.id
         JOIN users rec        ON sr.receiverId = rec.id
         LEFT JOIN skills offSk ON sr.offeredSkillId = offSk.id
         WHERE sr.requesterId = ?
         ORDER BY FIELD(sr.status, 'pending', 'accepted', 'completed', 'declined', 'cancelled'),
                  sr.createdAt DESC"
    );
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

/**
 * Reviews for a specific skill, found via the swap that unlocked them
 * (swapRequests.skillId). Newest first.
 */
function getReviewsForSkill(PDO $pdo, int $skillId): array
{
    $stmt = $pdo->prepare(
        "SELECT rv.*, u.name AS reviewerName
         FROM reviews rv
         JOIN swapRequests sr ON rv.swapId = sr.id
         JOIN users u         ON rv.userId = u.id
         WHERE sr.skillId = ?
         ORDER BY rv.createdAt DESC"
    );
    $stmt->execute([$skillId]);
    return $stmt->fetchAll();
}

/**
 * Average rating + review count for a skill, for the summary shown at the
 * top of the reviews section (e.g. "4.5 ★ (2 reviews)").
 */
function getSkillRatingSummary(PDO $pdo, int $skillId): array
{
    $stmt = $pdo->prepare(
        "SELECT ROUND(AVG(rv.rating), 1) AS avgRating, COUNT(*) AS reviewCount
         FROM reviews rv
         JOIN swapRequests sr ON rv.swapId = sr.id
         WHERE sr.skillId = ?"
    );
    $stmt->execute([$skillId]);
    $row = $stmt->fetch();
    return [
        'avgRating'   => $row['avgRating'] !== null ? (float) $row['avgRating'] : null,
        'reviewCount' => (int) $row['reviewCount'],
    ];
}

/**
 * Has this user already left a review for this swap? Used to hide the
 * "Leave a review" form once they've submitted one (DB also enforces this
 * via the unique_review constraint — this is just for a friendlier UI).
 */
function userHasReviewed(PDO $pdo, int $swapId, int $userId): bool
{
    $stmt = $pdo->prepare("SELECT 1 FROM reviews WHERE swapId = ? AND userId = ? LIMIT 1");
    $stmt->execute([$swapId, $userId]);
    return (bool) $stmt->fetch();
}

/**
 * All comments on a skill's discussion thread, oldest first (reads like a
 * conversation).
 */
function getCommentsForSkill(PDO $pdo, int $skillId): array
{
    $stmt = $pdo->prepare(
        "SELECT c.*, u.name AS authorName
         FROM comments c
         JOIN users u ON c.userId = u.id
         WHERE c.skillId = ?
         ORDER BY c.createdAt ASC"
    );
    $stmt->execute([$skillId]);
    return $stmt->fetchAll();
}

/**
 * Maps a swapRequests.status value to a CSS class suffix, so the badge
 * styling in assets/css/style.css (.status-badge.status-pending etc.) stays
 * in one place instead of being string-built all over the templates.
 */
function statusBadgeClass(string $status): string
{
    $allowed = ['pending', 'accepted', 'declined', 'completed', 'cancelled'];
    return in_array($status, $allowed, true) ? $status : 'pending';
}

/**
 * Small helper to render a static 1-5 star rating as a string of ★/☆,
 * used wherever we display (not collect) a rating.
 */
function renderStars(int $rating): string
{
    $rating = max(0, min(5, $rating));
    return str_repeat('★', $rating) . str_repeat('☆', 5 - $rating);
}

/**
 * ---- Tiny flash-message helper -----------------------------------------
 * One-time success/error banners after a redirect (Post/Redirect/Get).
 * Stored in the session, read once, then cleared — so a page refresh
 * doesn't keep re-showing "Request sent!" forever.
 */
function setFlash(string $type, string $text): void
{
    $_SESSION['flash'] = ['type' => $type, 'text' => $text];
}

function getAndClearFlash(): ?array
{
    if (empty($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

/**
 * Live credit balance for the nav badge and swap-cost checks.
 */
function getUserCreditsBalance(PDO $pdo, int $userId): int
{
    $stmt = $pdo->prepare('SELECT creditsBalance FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    return $row ? (int) $row['creditsBalance'] : 0;
}

/**
 * Mark a swap complete and apply the credit economy in one transaction.
 *
 * Straight request (no offeredSkillId): requester −1, receiver +1.
 * Barter (offeredSkillId set): no credit change.
 * Blocks completion when the requester has fewer than 1 credit.
 *
 * @return array{ok: bool, message: string}
 */
function completeSwapWithCredits(PDO $pdo, array $swap, int $completedByUserId): array
{
    if ($swap['status'] !== 'accepted') {
        return ['ok' => false, 'message' => 'Only an accepted swap can be marked complete.'];
    }

    try {
        $pdo->beginTransaction();

        $lockStmt = $pdo->prepare(
            'SELECT id, status, requesterId, receiverId, offeredSkillId
             FROM swapRequests
             WHERE id = ?
             FOR UPDATE'
        );
        $lockStmt->execute([(int) $swap['id']]);
        $lockedSwap = $lockStmt->fetch();

        if (!$lockedSwap || $lockedSwap['status'] !== 'accepted') {
            $pdo->rollBack();
            return ['ok' => false, 'message' => 'That swap is no longer in an accepted state.'];
        }

        $isCreditSwap = empty($lockedSwap['offeredSkillId']);

        if ($isCreditSwap) {
            $requesterId = (int) $lockedSwap['requesterId'];
            $receiverId  = (int) $lockedSwap['receiverId'];

            $balanceStmt = $pdo->prepare(
                'SELECT creditsBalance FROM users WHERE id = ? FOR UPDATE'
            );
            $balanceStmt->execute([$requesterId]);
            $requesterRow = $balanceStmt->fetch();

            if (!$requesterRow || (int) $requesterRow['creditsBalance'] < 1) {
                $pdo->rollBack();
                return [
                    'ok' => false,
                    'message' => 'The requester does not have enough credits to complete this swap.',
                ];
            }

            $deductStmt = $pdo->prepare(
                'UPDATE users SET creditsBalance = creditsBalance - 1 WHERE id = ?'
            );
            $deductStmt->execute([$requesterId]);

            $creditStmt = $pdo->prepare(
                'UPDATE users SET creditsBalance = creditsBalance + 1 WHERE id = ?'
            );
            $creditStmt->execute([$receiverId]);

            $swapId = (int) $swap['id'];
            $receiverNameStmt = $pdo->prepare('SELECT name FROM users WHERE id = ? LIMIT 1');
            $receiverNameStmt->execute([$receiverId]);
            $receiverName = $receiverNameStmt->fetchColumn() ?: 'the teacher';
            $requesterNameStmt = $pdo->prepare('SELECT name FROM users WHERE id = ? LIMIT 1');
            $requesterNameStmt->execute([$requesterId]);
            $requesterName = $requesterNameStmt->fetchColumn() ?: 'the learner';

            recordCreditTransaction(
                $pdo,
                $requesterId,
                -1,
                'swap_learn',
                'Learned a skill — 1 credit to ' . $receiverName,
                $swapId,
                $receiverId
            );
            recordCreditTransaction(
                $pdo,
                $receiverId,
                1,
                'swap_teach',
                'Taught a skill — 1 credit from ' . $requesterName,
                $swapId,
                $requesterId
            );
        }

        $completeStmt = $pdo->prepare(
            'UPDATE swapRequests
             SET status = ?, completedBy = ?, completedAt = NOW()
             WHERE id = ?'
        );
        $completeStmt->execute(['completed', $completedByUserId, (int) $swap['id']]);

        $pdo->commit();

        if ($isCreditSwap) {
            return [
                'ok' => true,
                'message' => 'Swap marked as complete — 1 credit transferred to the teacher.',
            ];
        }

        return [
            'ok' => true,
            'message' => 'Swap marked as complete — you can leave a review now.',
        ];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['ok' => false, 'message' => 'Unable to complete that swap right now. Please try again.'];
    }
}

/**
 * Record a single credit ledger entry (amount positive = earned, negative = spent).
 */
function recordCreditTransaction(
    PDO $pdo,
    int $userId,
    int $amount,
    string $type,
    string $description,
    ?int $swapId = null,
    ?int $relatedUserId = null
): void {
    $allowedTypes = ['welcome_bonus', 'swap_learn', 'swap_teach'];
    if (!in_array($type, $allowedTypes, true)) {
        return;
    }

    $stmt = $pdo->prepare(
        'INSERT INTO creditTransactions (userId, amount, type, description, swapId, relatedUserId)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([$userId, $amount, $type, $description, $swapId, $relatedUserId]);
}

/**
 * Credit history for the account page, newest first.
 */
function getCreditTransactionsForUser(PDO $pdo, int $userId, int $limit = 50): array
{
    $limit = max(1, min(100, $limit));
    $stmt = $pdo->prepare(
        "SELECT ct.*, u.name AS relatedUserName, sk.title AS skillTitle
         FROM creditTransactions ct
         LEFT JOIN users u ON ct.relatedUserId = u.id
         LEFT JOIN swapRequests sr ON ct.swapId = sr.id
         LEFT JOIN skills sk ON sr.skillId = sk.id
         WHERE ct.userId = ?
         ORDER BY ct.createdAt DESC
         LIMIT {$limit}"
    );
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}
