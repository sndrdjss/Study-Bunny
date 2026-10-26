-- =========================================================
-- StudyBuddy — Tier 3 (start): quiz attempts.
-- Records one row per finished quiz so streaks / XP can be
-- built later. Run ONCE in phpMyAdmin (database hcieinsproject).
-- =========================================================
CREATE TABLE IF NOT EXISTS quiz_attempt (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    deck_id    INT NOT NULL,
    score      INT NOT NULL,
    total      INT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_attempt_user (user_id),
    INDEX idx_attempt_deck (deck_id),
    CONSTRAINT fk_attempt_user FOREIGN KEY (user_id) REFERENCES study_buddy_auth(id) ON DELETE CASCADE,
    CONSTRAINT fk_attempt_deck FOREIGN KEY (deck_id) REFERENCES deck(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
