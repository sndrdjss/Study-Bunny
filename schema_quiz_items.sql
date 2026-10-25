-- =========================================================
-- StudyBuddy — Quiz items schema
-- Adds quiz_item (flashcard or multiple_choice) and quiz_option.
-- Safe to run multiple times (IF NOT EXISTS).
-- =========================================================

CREATE TABLE IF NOT EXISTS quiz_item (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    deck_id    INT NOT NULL,
    type       ENUM('flashcard', 'multiple_choice') NOT NULL DEFAULT 'flashcard',
    question   TEXT NOT NULL,
    answer     TEXT NULL,                 -- used by flashcard; NULL for multiple_choice
    position   INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_quiz_item_deck
        FOREIGN KEY (deck_id) REFERENCES deck(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS quiz_option (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    quiz_item_id INT NOT NULL,
    option_text  TEXT NOT NULL,
    is_correct   TINYINT(1) NOT NULL DEFAULT 0,
    position     INT NOT NULL DEFAULT 0,
    CONSTRAINT fk_quiz_option_item
        FOREIGN KEY (quiz_item_id) REFERENCES quiz_item(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
