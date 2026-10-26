-- =========================================================
-- StudyBuddy — auth migration (run ONCE in phpMyAdmin,
-- database: hcieinsproject, "SQL" tab).
-- password_hash() output needs up to 255 characters.
-- =========================================================
ALTER TABLE study_buddy_auth MODIFY password VARCHAR(255) NOT NULL;

-- Optional: only if usernames are not already unique
-- (register.php relies on duplicate-key error 1062 to show "username taken").
-- ALTER TABLE study_buddy_auth ADD UNIQUE KEY uq_username (username);
