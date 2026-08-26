<?php
/**
 * My Skills dashboard — owner-only listing management with edit and delete.
 */
require_once __DIR__ . '/../auth/session_check.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/swap_functions.php';

$pageTitle = 'My Skills - SkillExpert';
$currentUserId = (int) $_SESSION['user_id'];
$editingId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
$editingSkill = null;

if ($editingId) {
    $stmt = $pdo->prepare('SELECT * FROM skills WHERE id = ? AND userId = ? LIMIT 1');
    $stmt->execute([$editingId, $currentUserId]);
    $editingSkill = $stmt->fetch();
    if (!$editingSkill) {
        setFlash('error', 'That skill was not found or you do not have permission to edit it.');
        header('Location: /main/public/my_skills.php');
        exit;
    }
}

$stmt = $pdo->prepare(
    "SELECT sk.*,
            COUNT(DISTINCT sr.id) AS requestCount,
            COUNT(DISTINCT ss.id) AS savedCount
     FROM skills sk
     LEFT JOIN swapRequests sr ON sr.skillId = sk.id
     LEFT JOIN savedSkills ss ON ss.skillId = sk.id
     WHERE sk.userId = ?
     GROUP BY sk.id
     ORDER BY sk.createdAt DESC"
);
$stmt->execute([$currentUserId]);
$mySkills = $stmt->fetchAll();
$flash = getAndClearFlash();
$categories = ['Programming', 'Design', 'Language', 'Music', 'Sports', 'Academic', 'Other'];

require_once __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="/main/assets/css/saved.css?v=<?php echo filemtime(__DIR__ . '/../assets/css/saved.css'); ?>">

<section class="container saved-page">
    <div class="saved-page-header">
        <h1>My Skills</h1>
        <p>Create, review, update, and delete the skills you offer to the SkillExpert community.</p>
    </div>

    <?php if ($flash): ?>
        <div class="flash-msg flash-<?php echo htmlspecialchars($flash['type']); ?>">
            <?php echo htmlspecialchars($flash['text']); ?>
        </div>
    <?php endif; ?>

    <?php if ($editingSkill): ?>
        <div class="skill-form-card manage-skill-form" id="edit-skill">
            <h2>Edit Skill</h2>
            <form method="POST" action="/main/actions/skill_update.php" data-validate-skill-form>
                <input type="hidden" name="skill_id" value="<?php echo (int) $editingSkill['id']; ?>">
                <div class="form-group">
                    <label for="title">Skill title</label>
                    <input type="text" id="title" name="title" class="form-control" maxlength="150" required value="<?php echo htmlspecialchars($editingSkill['title']); ?>">
                </div>
                <div class="form-group">
                    <label for="category">Category</label>
                    <select id="category" name="category" class="form-control" required>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo htmlspecialchars($category); ?>" <?php echo $editingSkill['category'] === $category ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" class="form-control" maxlength="1000" required><?php echo htmlspecialchars($editingSkill['description']); ?></textarea>
                </div>
                <div class="inline-actions">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                    <a href="/main/public/my_skills.php" class="btn btn-ghost">Cancel</a>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <?php if (empty($mySkills)): ?>
        <div class="empty-state">
            <p>You have not posted any skills yet.</p>
            <p class="mt-3"><a href="/main/public/posting.php" class="btn btn-primary">Post Your First Skill</a></p>
        </div>
    <?php else: ?>
        <div class="saved-grid">
            <?php foreach ($mySkills as $skill): ?>
                <article class="saved-card">
                    <div>
                        <div class="saved-card-top">
                            <span class="skill-cat-pill"><?php echo htmlspecialchars($skill['category']); ?></span>
                            <span class="text-muted"><?php echo (int) $skill['savedCount']; ?> saved</span>
                        </div>
                        <h3><a href="/main/public/details.php?id=<?php echo (int) $skill['id']; ?>"><?php echo htmlspecialchars($skill['title']); ?></a></h3>
                        <p class="saved-card-meta"><?php echo htmlspecialchars(mb_strimwidth($skill['description'], 0, 140, '...')); ?></p>
                        <p class="saved-card-meta"><?php echo (int) $skill['requestCount']; ?> swap request(s)</p>
                    </div>
                    <div class="saved-card-actions">
                        <a href="/main/public/my_skills.php?edit=<?php echo (int) $skill['id']; ?>#edit-skill" class="btn btn-primary btn-sm">Edit</a>
                        <form method="POST" action="/main/actions/skill_delete.php" class="inline-form">
                            <input type="hidden" name="skill_id" value="<?php echo (int) $skill['id']; ?>">
                            <button type="submit" class="btn btn-decline btn-sm-narrow" data-confirm="Delete this skill and related swaps, comments, reviews, and saves?">Delete</button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<script src="/main/assets/js/skills-posting.js?v=<?php echo filemtime(__DIR__ . '/../assets/js/skills-posting.js'); ?>"></script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
