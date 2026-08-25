<?php
/**
 * Shared helper functions for the Saved/Wishlist feature (Barry).
 * Same pattern as includes/swap_functions.php — plain functions over a
 * PDO connection passed in explicitly.
 */

/**
 * All skills the given user has saved, newest-saved first, joined with the
 * skill's own details and the teacher's name so the Saved page can render
 * a full card without extra queries per row.
 */
function getSavedSkillsForUser(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare(
        "SELECT sk.*, u.name AS teacherName, ss.createdAt AS savedAt
         FROM savedSkills ss
         JOIN skills sk ON ss.skillId = sk.id
         JOIN users u   ON sk.userId = u.id
         WHERE ss.userId = ?
         ORDER BY ss.createdAt DESC"
    );
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

/**
 * Has this user already saved this skill? Used to decide whether details.php
 * shows a "Save" or "Unsave" button (the DB's unique_save constraint is the
 * real guarantee against duplicates — this is just for the UI state).
 */
function isSkillSavedByUser(PDO $pdo, int $userId, int $skillId): bool
{
    $stmt = $pdo->prepare("SELECT 1 FROM savedSkills WHERE userId = ? AND skillId = ? LIMIT 1");
    $stmt->execute([$userId, $skillId]);
    return (bool) $stmt->fetch();
}
