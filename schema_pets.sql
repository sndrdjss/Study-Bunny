-- =========================================================
-- StudyBuddy — egg & pet mascot (run ONCE in phpMyAdmin,
-- database: hcieinsproject, "SQL" tab).
-- Run AFTER schema_auth.sql and schema_quiz_attempts.sql.
-- One row per user: the egg they picked, and (once hatched)
-- the pet it became. Hatch progress is NOT stored; it is
-- counted live from quiz_attempt (perfect scores made at or
-- after chosen_at).
-- =========================================================
CREATE TABLE IF NOT EXISTS user_pet (
    user_id    INT NOT NULL PRIMARY KEY,
    egg_type   ENUM('cozy', 'forest', 'frost') NOT NULL,
    chosen_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    pet        ENUM('cat', 'dog', 'bunny', 'panda', 'penguin', 'ice_bear') NULL DEFAULT NULL,
    hatched_at TIMESTAMP NULL DEFAULT NULL,
    CONSTRAINT fk_pet_user FOREIGN KEY (user_id) REFERENCES study_buddy_auth(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
