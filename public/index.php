<?php require_once __DIR__ . '/../includes/db.php';
$pageTitle = 'SkillExpert - Trade Skills, Not Money';

$stmt = $pdo->query(
    'SELECT skills.*, users.name AS teacher name
    FROM skills JOIN users ON skills.user_id = users.id
    ORDER BY skills.created_at DESC LIMIT 5'
);

$featuredSkills = $stmt->fetchAll();

require_once __DIR__ .'/../includes/header.php';?>

<section class="hero-split">
    <div class="hero-statement">
        <p class="eyebrow">Peer Skills Exchange</p>
        <h1>Teach and Share What You Know.
            <br>
            Learn From Others On What You Can't.
        </h1>
        <p class="hero-sub">Why use Money? When you can just swap time, earn credits, spend them learning from experts.</p>
        <div class="hero-actions">
            <a href="listing.php" class="btn btn-primary">Browse Skills Now</a>
            <a href="/auth/register.php" class="btn btn-ghost">Join For Free!</a>
        </div>
    </div>

    <div class="hero-ticker">
        <p class="ticker-label">Recent Exchnages</p>
        <ul class="ticker-list">
            <li></li>
            <li></li>
            <li></li>
            <li></li>
        </ul>
    </div>
</section>

<section class="skill-directory">
    <div class="section-header">
        <h2>Featured Skills</h2>
        <a href="listing.php" class="see-all">Browse All &rarr;</a>
    </div>

    <?php if(empty($featuredSkills)): ?>
        <p class="empty-state">No Skills Posted Yet. <a href="/auth/register.php">Join and post the first one.</a></p>
        <?php else: ?>
            <?php foreach($featuredSkills as $skill): ?>
                <div class="skill-row">

                </div>
        <?php endforeach; ?>
    <?php endif; ?>
</section>