<?php
/**
 * Credit balance & transaction history for the logged-in user.
 */

require_once __DIR__ . '/../auth/session_check.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/swap_functions.php';

$pageTitle = 'My Credits - SkillExpert';
$currentUserId = (int) $_SESSION['user_id'];
$balance = getUserCreditsBalance($pdo, $currentUserId);
$transactions = getCreditTransactionsForUser($pdo, $currentUserId);
$flash = getAndClearFlash();
?>

<?php require_once __DIR__ . '/../includes/header.php'; ?>

<link rel="stylesheet" href="/main/assets/css/credits.css?v=<?php echo filemtime(__DIR__ . '/../assets/css/credits.css'); ?>">

<section class="container credits-page">

    <div class="credits-page-header">
        <p class="eyebrow">Time-credit economy</p>
        <h1>My Credits</h1>
        <p>Teach to earn credits. Learn without a barter offer by spending 1 credit per completed swap.</p>
    </div>

    <?php if ($flash): ?>
        <div class="flash-msg flash-<?php echo htmlspecialchars($flash['type']); ?>">
            <?php echo htmlspecialchars($flash['text']); ?>
        </div>
    <?php endif; ?>

    <div class="credits-summary-grid">
        <article class="credits-balance-card">
            <span class="credits-balance-label">Current balance</span>
            <strong class="credits-balance-value"><?php echo (int) $balance; ?></strong>
            <span class="credits-balance-unit">credit<?php echo (int) $balance === 1 ? '' : 's'; ?> available</span>
        </article>

        <article class="credits-rules-card">
            <h2>How credits work</h2>
            <ul>
                <li><strong>New members</strong> receive 5 welcome credits.</li>
                <li><strong>Learn (no barter):</strong> spend 1 credit when a swap is marked complete.</li>
                <li><strong>Teach:</strong> earn 1 credit when you complete a straight learning request.</li>
                <li><strong>Skill barter:</strong> offer your own skill — no credits change hands.</li>
            </ul>
            <a href="/main/public/browse.php" class="btn btn-primary btn-sm">Browse skills to trade</a>
        </article>
    </div>

    <div class="credits-history-section">
        <h2>Transaction history</h2>

        <?php if (empty($transactions)): ?>
            <div class="empty-state">
                <p>No credit activity yet. Complete a swap or register to see your ledger here.</p>
            </div>
        <?php else: ?>
            <ul class="credits-ledger">
                <?php foreach ($transactions as $tx): ?>
                    <?php
                    $amount = (int) $tx['amount'];
                    $isPositive = $amount > 0;
                    ?>
                    <li class="credits-ledger-item <?php echo $isPositive ? 'is-credit' : 'is-debit'; ?>">
                        <div class="ledger-main">
                            <span class="ledger-amount"><?php echo $isPositive ? '+' : ''; ?><?php echo $amount; ?></span>
                            <div>
                                <strong><?php echo htmlspecialchars($tx['description']); ?></strong>
                                <?php if (!empty($tx['skillTitle'])): ?>
                                    <span class="ledger-skill">Skill: <?php echo htmlspecialchars($tx['skillTitle']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <time class="ledger-date" datetime="<?php echo htmlspecialchars($tx['createdAt']); ?>">
                            <?php echo htmlspecialchars(date('d M Y, g:ia', strtotime($tx['createdAt']))); ?>
                        </time>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
