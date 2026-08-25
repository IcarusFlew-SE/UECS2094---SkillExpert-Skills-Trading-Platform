-- SkillExpert Platform – Database Schema
-- Run once against a local MySQL server: mysql -u root -p < database/schema.sql
--
-- NOTE (Barry, swap & review module): the `skills` table below had a bug —
-- its foreign key pointed at a table called `user` (singular), which doesn't
-- exist anywhere in this file, so the ORIGINAL script could not run past that
-- CREATE TABLE. Fixed it to reference `users` (plural), which is what's
-- actually declared above it. Flagging this here so nobody re-introduces it.

CREATE DATABASE IF NOT EXISTS swapexpert
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci; -- multi-language sorting, ci = case-insensitive, emoji support

USE swapexpert;

-- Table: users
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    passwordHash VARCHAR(255) NOT NULL,
    creditsBalance INT NOT NULL DEFAULT 0,
    createdAt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: skills
CREATE TABLE IF NOT EXISTS skills (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    userId INT UNSIGNED NOT NULL,
    title VARCHAR(150) NOT NULL,
    category VARCHAR(50) NOT NULL,
    description TEXT NOT NULL,
    imagePath VARCHAR(255) DEFAULT NULL,
    createdAt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE, -- was `user(id)` (bug), fixed to `users(id)`
    INDEX idx_category (category),
    INDEX idx_user (userId)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Swap & Review module (Barry) — swapRequests, reviews, comments
-- ============================================================

-- Table: swapRequests
-- Tracks a skill-swap request from one user (requester) to another
-- (receiver, the owner of the skill). Optionally the requester offers one
-- of their own skills in exchange (offeredSkillId). Status moves through:
--   pending -> accepted -> completed
--   pending -> declined
--   pending -> cancelled (withdrawn by requester)
CREATE TABLE IF NOT EXISTS swapRequests (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    skillId INT UNSIGNED NOT NULL,            -- the skill being requested
    requesterId INT UNSIGNED NOT NULL,        -- user asking for the swap
    receiverId INT UNSIGNED NOT NULL,         -- owner of skillId (denormalised for fast lookups)
    offeredSkillId INT UNSIGNED DEFAULT NULL, -- optional skill offered in exchange, must belong to requester
    message TEXT DEFAULT NULL,                -- optional note from the requester
    status ENUM('pending', 'accepted', 'declined', 'completed', 'cancelled')
        NOT NULL DEFAULT 'pending',
    completedBy INT UNSIGNED DEFAULT NULL,    -- which user marked it complete
    completedAt TIMESTAMP NULL DEFAULT NULL,
    createdAt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    FOREIGN KEY (skillId) REFERENCES skills(id) ON DELETE CASCADE,
    FOREIGN KEY (requesterId) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiverId) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (offeredSkillId) REFERENCES skills(id) ON DELETE SET NULL,
    FOREIGN KEY (completedBy) REFERENCES users(id) ON DELETE SET NULL,
    CHECK (requesterId <> receiverId),        -- can't request a swap on your own skill
    INDEX idx_requester (requesterId),
    INDEX idx_receiver (receiverId),
    INDEX idx_status (status),
    INDEX idx_skill (skillId)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: reviews
-- A review is left by a swap participant once that swap is 'completed'.
-- It is NOT tied directly to a skill id — instead it is traced back to the
-- skill via swapRequests.skillId. This means only users who actually went
-- through a completed swap for that skill can leave a review for it
-- ("verified swap" style review), and each participant may leave at most
-- one review per swap (see unique_review below).
CREATE TABLE IF NOT EXISTS reviews (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    swapId INT UNSIGNED NOT NULL,
    userId INT UNSIGNED NOT NULL,
    rating TINYINT UNSIGNED NOT NULL,
    comment TEXT DEFAULT NULL,
    createdAt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    FOREIGN KEY (swapId) REFERENCES swapRequests(id) ON DELETE CASCADE,
    FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_review (swapId, userId),
    CHECK (rating BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: comments
-- Open discussion thread on a skill listing (separate from `reviews`).
-- Any logged-in user may comment on any skill listing — no completed swap
-- required. This is the "Q&A / discussion" layer under Item Details.
CREATE TABLE IF NOT EXISTS comments (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    skillId INT UNSIGNED NOT NULL,
    userId INT UNSIGNED NOT NULL,
    commentText TEXT NOT NULL,
    createdAt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    FOREIGN KEY (skillId) REFERENCES skills(id) ON DELETE CASCADE,
    FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_skill (skillId)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Contact & Saved-items module (Barry)
-- ============================================================

-- Table: savedSkills
-- A logged-in user's wishlist/bookmark list. Users must register and log in
-- before accessing saved items (per the assignment's login requirement).
-- unique_save stops the same skill being saved twice by the same user.
CREATE TABLE IF NOT EXISTS savedSkills (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    userId INT UNSIGNED NOT NULL,
    skillId INT UNSIGNED NOT NULL,
    createdAt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (skillId) REFERENCES skills(id) ON DELETE CASCADE,
    UNIQUE KEY unique_save (userId, skillId),
    INDEX idx_user (userId)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: contactMessages
-- Submissions from the public Contact page. No login required to submit —
-- userId is filled in when the sender happens to be logged in, purely so a
-- message can be traced back to an account later; NULL otherwise.
CREATE TABLE IF NOT EXISTS contactMessages (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    userId INT UNSIGNED DEFAULT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    subject VARCHAR(150) DEFAULT NULL,
    message TEXT NOT NULL,
    createdAt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    FOREIGN KEY (userId) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_createdAt (createdAt)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Demo / seed data
-- Lets you log in and click through the swap + review + comment flow
-- immediately after running this file, without registering by hand first.
-- All demo accounts use the password:  Password123!
-- ============================================================

INSERT INTO users (name, email, passwordHash, creditsBalance) VALUES
('Alice Tan',   'alice@example.com',  '$2y$12$yBfxdqRwQuT52oij.pcxU.cKjyOHk3psEF13FCnpwSmSiOhwhBoSC', 5),
('Ben Osman',   'ben@example.com',    '$2y$12$aW.qddW4CnnUBWlvvU9u0uHeuLosd5AQrph6ZHVzyNAchrVJ0SQ0m', 5),
('Chandra Lee', 'chandra@example.com','$2y$12$NOMTfVQWghk305zm2XvitOm.TTjUnxpVcSVAw40EV1/rsI3QqZM3y', 5),
('Divya Nair',  'divya@example.com',  '$2y$12$PXLWLSR6QWKosw.yzTiLgORY9V6xeDaaeHWQehuFjN/li1xBNXkHy', 5);

INSERT INTO skills (userId, title, category, description, imagePath) VALUES
(1, 'Beginner Guitar Lessons',      'Music',       'Learn open chords, strumming patterns, and your first song in a few sessions.', NULL),
(2, 'Conversational Spanish',       'Languages',   'Practice everyday Spanish conversation with a patient, friendly tutor.', NULL),
(3, 'Excel for Data Analysis',      'Technology',  'Pivot tables, formulas, and dashboards for everyday spreadsheet work.', NULL),
(4, 'Watercolour Painting Basics',  'Art',         'An introduction to washes, colour mixing, and simple landscapes.', NULL);

-- Alice requests Ben's Spanish skill, offering her guitar lessons in return — already completed
INSERT INTO swapRequests (skillId, requesterId, receiverId, offeredSkillId, message, status, completedBy, completedAt) VALUES
(2, 1, 2, 1, 'Would love to trade guitar lessons for some Spanish practice!', 'completed', 2, NOW());

-- Chandra requests Divya's painting skill — accepted, not yet completed
INSERT INTO swapRequests (skillId, requesterId, receiverId, offeredSkillId, message, status) VALUES
(4, 3, 4, 3, 'I can walk you through Excel dashboards in exchange.', 'accepted');

-- Ben requests Alice's guitar skill — still pending
INSERT INTO swapRequests (skillId, requesterId, receiverId, offeredSkillId, message, status) VALUES
(1, 2, 1, 2, 'Happy to trade Spanish conversation practice for guitar basics.', 'pending');

-- Reviews for the completed swap (id 1): both sides review the experience
INSERT INTO reviews (swapId, userId, rating, comment) VALUES
(1, 1, 5, 'Ben was a fantastic Spanish tutor, patient and clear. Highly recommend!'),
(1, 2, 4, 'Alice picked up chords quickly and was great to teach.');

-- Comments (open discussion, no completed swap required)
INSERT INTO comments (skillId, userId, commentText) VALUES
(1, 3, 'Do you cover fingerstyle at all, or mostly strumming?'),
(2, 4, 'What level of Spanish do you start from — total beginner okay?');

-- Chandra has saved two skills to look at later
INSERT INTO savedSkills (userId, skillId) VALUES
(3, 1),
(3, 2);

-- A sample contact form submission, one from a logged-in user and one anonymous
INSERT INTO contactMessages (userId, name, email, subject, message) VALUES
(1, 'Alice Tan', 'alice@example.com', 'Question about categories', 'Is there a category for cooking skills, or should I use "Other"?'),
(NULL, 'Guest Visitor', 'guest@example.com', 'General feedback', 'Really like the concept of swapping skills instead of paying — nice work!');
