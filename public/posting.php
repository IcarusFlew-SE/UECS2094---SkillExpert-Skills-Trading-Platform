<?php

$pageTitle = 'Post a Skill - SkillExpert';

require_once __DIR__ . '/../includes/header.php';

$isLoggedIn = isset($_SESSION['user_id']);

?>

<link rel="stylesheet" href="../assets/css/skills-posting.css">

<main class="post-skill-page">

    <?php if (isset($_GET['published']) && $_GET['published'] === '1'): ?>

        <div class="publish-success" id="publish-success">

            <div class="success-icon">
                ✓
            </div>

            <div class="success-content">
                <strong>Skill published!</strong>

                <p>
                    Your skill has been successfully added
                    to SkillExpert.
                </p>
            </div>

            <button
                type="button"
                id="close-success"
                class="close-success"
                aria-label="Close"
            >
                ×
            </button>

        </div>

    <?php endif; ?>


    <!-- ==========================================
         HERO
         ========================================== -->

    <section class="skill-hero">

        <div class="skill-hero-content">

            <div class="skill-eyebrow">
                Share what you know
            </div>

            <h1>
                Turn your
                <span>skill</span>
                into something more.
            </h1>

            <p class="skill-hero-text">
                Share something you're good at, connect with
                people who want to learn, and create meaningful
                skill exchanges.
            </p>


            <?php if ($isLoggedIn): ?>

                <button
                    type="button"
                    class="hero-scroll-button"
                    id="start-creating"
                >
                    Start creating
                    <span>↓</span>
                </button>

            <?php else: ?>

                <a
                    href="../auth/register.php"
                    class="hero-scroll-button"
                    id="start-creating"
                >
                    Start creating
                    <span>→</span>
                </a>

            <?php endif; ?>

        </div>


        <!-- Hero visual -->

        <div class="skill-hero-visual">

            <div class="hero-glow"></div>

            <div class="mini-floating-card mini-card-one">
                ✦ Share your knowledge
            </div>

            <div class="mini-floating-card mini-card-two">
                ↗ Meet new learners
            </div>


            <div
                class="floating-skill-card"
                id="hero-card"
            >

                <div class="hero-card-image">

                    <div class="hero-card-icon">
                        ✦
                    </div>

                </div>


                <div class="hero-card-content">

                    <div class="hero-card-category">
                        SKILLEXPERT
                    </div>

                    <div class="hero-card-title">
                        Your next skill starts here.
                    </div>

                    <div class="hero-card-description">
                        Teach. Learn. Exchange.
                    </div>

                </div>

            </div>

        </div>

    </section>



    <!-- ==========================================
         CATEGORY SHOWCASE
         ========================================== -->

    <section class="skill-categories">

        <div class="category-heading">

            <h2>
                What can you share?
            </h2>

            <p>
                Choose something you're passionate about.
            </p>

        </div>


        <div class="category-track" id="category-track">


            <div
                class="skill-category-card"
                data-category="Programming"
            >

                <div class="category-icon">
                    &lt;/&gt;
                </div>

                <div class="category-name">
                    Programming
                </div>

                <div class="category-arrow">
                    →
                </div>

            </div>



            <div
                class="skill-category-card"
                data-category="Design"
            >

                <div class="category-icon">
                    ✦
                </div>

                <div class="category-name">
                    Design
                </div>

                <div class="category-arrow">
                    →
                </div>

            </div>



            <div
                class="skill-category-card"
                data-category="Language"
            >

                <div class="category-icon">
                    A
                </div>

                <div class="category-name">
                    Language
                </div>

                <div class="category-arrow">
                    →
                </div>

            </div>



            <div
                class="skill-category-card"
                data-category="Music"
            >

                <div class="category-icon">
                    ♫
                </div>

                <div class="category-name">
                    Music
                </div>

                <div class="category-arrow">
                    →
                </div>

            </div>



            <div
                class="skill-category-card"
                data-category="Sports"
            >

                <div class="category-icon">
                    ◉
                </div>

                <div class="category-name">
                    Sports
                </div>

                <div class="category-arrow">
                    →
                </div>

            </div>



            <div
                class="skill-category-card"
                data-category="Academic"
            >

                <div class="category-icon">
                    ✎
                </div>

                <div class="category-name">
                    Academic
                </div>

                <div class="category-arrow">
                    →
                </div>

            </div>


        </div>


        <button
            type="button"
            class="category-explore"
            id="category-explore"
        >
            Explore more
            <span>→</span>
        </button>

    </section>



    <!-- ==========================================
         CREATE SKILL
         ========================================== -->

    <section
        class="skill-creation-section"
        id="create-skill"
    >


        <?php if (!$isLoggedIn): ?>


            <!-- LOCKED AREA -->

            <div class="creation-locked">

                <div class="locked-icon">
                    ✦
                </div>


                <div class="creation-heading">

                    <h2>
                        Now make it yours.
                    </h2>

                    <p>
                        Create an account to share your skills
                        with the SkillExpert community.
                    </p>

                </div>


                <a
                    href="../auth/register.php"
                    class="unlock-button"
                >
                    Create an account →
                </a>


                <p class="already-account">

                    Already have an account?

                    <a href="../auth/login.php">
                        Log in
                    </a>

                </p>

            </div>


        <?php else: ?>


            <!-- LOGGED-IN CREATION AREA -->

            <div class="creation-heading">

                <h2>
                    Now make it yours.
                </h2>

                <p>
                    Give your skill a name, choose a category,
                    and tell people what they can learn from you.
                </p>

            </div>



            <div class="post-skill-layout">


                <!-- ==================================
                     FORM
                     ================================== -->

                <div class="skill-form-card">

                    <form
                        id="skill-form"
                        method="POST"
                        action="../actions/create_skills.php"
                    >


                        <!-- TITLE -->

                        <div class="form-group">

                            <label for="title">
                                Skill title
                            </label>

                            <input
                                type="text"
                                id="title"
                                name="title"
                                maxlength="150"
                                placeholder="e.g. Python Programming"
                                required
                            >

                        </div>



                        <!-- CATEGORY -->

                        <div class="form-group">

                            <label>
                                Category
                            </label>


                            <input
                                type="hidden"
                                id="category"
                                name="category"
                                required
                            >


                            <div class="category-options">


                                <button
                                    type="button"
                                    class="category-option"
                                    data-category="Programming"
                                >
                                    Programming
                                </button>


                                <button
                                    type="button"
                                    class="category-option"
                                    data-category="Design"
                                >
                                    Design
                                </button>


                                <button
                                    type="button"
                                    class="category-option"
                                    data-category="Language"
                                >
                                    Language
                                </button>


                                <button
                                    type="button"
                                    class="category-option"
                                    data-category="Music"
                                >
                                    Music
                                </button>


                                <button
                                    type="button"
                                    class="category-option"
                                    data-category="Sports"
                                >
                                    Sports
                                </button>


                                <button
                                    type="button"
                                    class="category-option"
                                    data-category="Academic"
                                >
                                    Academic
                                </button>


                                <button
                                    type="button"
                                    class="category-option"
                                    data-category="Other"
                                >
                                    Other
                                </button>


                            </div>

                        </div>



                        <!-- DESCRIPTION -->

                        <div class="form-group">

                            <div class="label-row">

                                <label for="description">
                                    Tell us about your skill
                                </label>

                                <span id="character-counter">
                                    0 / 1000
                                </span>

                            </div>


                            <textarea
                                id="description"
                                name="description"
                                maxlength="1000"
                                placeholder="What can you teach? What will someone learn?"
                                required
                            ></textarea>

                        </div>



                        <!-- BUTTON -->

                        <button
                            type="submit"
                            class="post-skill-button"
                            id="publish-button"
                        >
                            Publish your skill
                            <span>→</span>
                        </button>


                    </form>

                </div>



                <!-- ==================================
                     LIVE PREVIEW
                     ================================== -->

                <div class="skill-preview-card">

                    <div class="preview-label">

                        <span>
                            Live preview
                        </span>

                        <small>
                            Updates as you type
                        </small>

                    </div>


                    <div
                        class="preview-skill"
                        id="preview-card"
                    >


                        <div class="preview-image">

                            <div class="preview-image-placeholder">
                                ✦
                            </div>

                        </div>


                        <div class="preview-content">


                            <span
                                class="preview-category"
                                id="preview-category"
                            >
                                Your category
                            </span>


                            <h3
                                class="preview-title preview-empty"
                                id="preview-title"
                            >
                                Your skill title
                            </h3>


                            <p
                                class="preview-description preview-empty"
                                id="preview-description"
                            >
                                Your skill description will appear here.
                            </p>


                            <div class="preview-meta">

                                <span>
                                    SkillExpert
                                </span>

                                <span>
                                    Available to exchange
                                </span>

                            </div>


                        </div>

                    </div>

                </div>


            </div>


        <?php endif; ?>


    </section>

</main>


<script src="../assets/js/skills-posting.js"></script>


<?php require_once __DIR__ . '/../includes/footer.php'; ?>