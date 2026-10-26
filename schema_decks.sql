-- =========================================================
-- StudyBuddy — Tier 2: deck ownership (run ONCE in phpMyAdmin,
-- database: hcieinsproject, "SQL" tab). Run AFTER schema_auth.sql.
-- =========================================================

-- 1) Owner column. NULL = legacy/unowned deck (still visible if published,
--    but nobody can edit, publish or delete it).
ALTER TABLE deck
    ADD COLUMN user_id INT NULL AFTER id,
    ADD INDEX idx_deck_user (user_id);

-- 2) Optional foreign key: if the account is deleted, its decks become unowned.
--    If this line errors with "incompatible column", your study_buddy_auth.id is
--    a different type (e.g. INT UNSIGNED) — skip it; the app works without it.
ALTER TABLE deck
    ADD CONSTRAINT fk_deck_user
        FOREIGN KEY (user_id) REFERENCES study_buddy_auth(id) ON DELETE SET NULL;

-- 3) Optional: claim all existing unowned decks for one account.
--    Replace 'your_username' and remove the leading "-- " to run it.
-- UPDATE deck SET user_id = (SELECT id FROM study_buddy_auth WHERE username = 'your_username')
-- WHERE user_id IS NULL;
