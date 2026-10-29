-- =========================================================
-- StudyBuddy — per-question answer log (run ONCE in phpMyAdmin,
-- database: hcieinsproject, "SQL" tab).
-- Run AFTER schema_auth.sql, schema_decks.sql, schema_quiz_items.sql.
-- One row per answered question so the Progress page can show
-- "mistakes to review". question_text / correct_answer are stored
-- as SNAPSHOTS so past answers stay readable even if the deck is
-- later edited or deleted. quiz_item_id links rows for the
-- "latest attempt per question" view (NULL if the item is gone).
-- =========================================================
CREATE TABLE IF NOT EXISTS quiz_answer (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    user_id        INT NOT NULL,
    deck_id        INT NOT NULL,
    quiz_item_id   INT NULL,
    question_text  TEXT NOT NULL,
    your_answer    TEXT NULL,
    correct_answer TEXT NULL,
    is_correct     TINYINT(1) NOT NULL,
    answered_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_qa_user_item (user_id, quiz_item_id, answered_at),
    INDEX idx_qa_user_deck (user_id, deck_id),
    CONSTRAINT fk_qa_user FOREIGN KEY (user_id) REFERENCES study_buddy_auth(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
