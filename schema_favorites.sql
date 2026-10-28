-- =========================================================
-- StudyBuddy — deck favorites (run ONCE in phpMyAdmin,
-- database: hcieinsproject, "SQL" tab).
-- Run AFTER schema_auth.sql and schema_decks.sql.
-- Stores which user "hearted" which deck. One row per
-- (user, deck); the UNIQUE KEY makes re-favoriting a no-op.
-- =========================================================

CREATE TABLE IF NOT EXISTS deck_favorite (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    deck_id    INT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_deck (user_id, deck_id),
    INDEX idx_fav_user (user_id),
    INDEX idx_fav_deck (deck_id)
);

-- Optional foreign keys: keep favorites tidy when an account or deck
-- is removed. If either line errors with "incompatible column", the
-- referenced id is a different type (e.g. INT UNSIGNED) — skip that
-- line; the app works without the constraint.
-- ALTER TABLE deck_favorite
--     ADD CONSTRAINT fk_fav_user
--         FOREIGN KEY (user_id) REFERENCES study_buddy_auth(id) ON DELETE CASCADE;
-- ALTER TABLE deck_favorite
--     ADD CONSTRAINT fk_fav_deck
--         FOREIGN KEY (deck_id) REFERENCES deck(id) ON DELETE CASCADE;
