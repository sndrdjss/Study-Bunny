<?php

require_once __DIR__ . '/auth.php';
require 'db.php';

require_login();

// Only act on POST; otherwise go back to the create page.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: create.php');
    exit;
}

verify_csrf();

$deckId = isset($_POST['deck_id']) ? (int) $_POST['deck_id'] : 0;
$type   = $_POST['type'] ?? '';
$question = trim($_POST['question'] ?? '');

// Helper to bounce back to the manage page with an error reason.
function fail(int $deckId, string $reason): void
{
    header('Location: deck_items.php?id=' . $deckId . '&error=' . urlencode($reason));
    exit;
}

if ($deckId <= 0) {
    header('Location: create.php?error=notfound');
    exit;
}

// Type must be one of the two allowed values.
if ($type !== 'flashcard' && $type !== 'multiple_choice') {
    fail($deckId, 'type');
}

// Question is always required.
if ($question === '') {
    fail($deckId, 'question');
}

try {
    // Confirm the deck exists AND belongs to the logged-in user before inserting anything.
    if (!fetch_owned_deck($conn, $deckId)) {
        header('Location: create.php?error=forbidden');
        exit;
    }

    // Gather the data specific to each item type (and validate) BEFORE any insert,
    // so we never write a partial row.
    $answer = null;
    $validOptions = [];   // list of ['text' => string, 'is_correct' => int]

    if ($type === 'flashcard') {
        $answer = trim($_POST['answer'] ?? '');
        if ($answer === '') {
            fail($deckId, 'answer');
        }
    } else {
        // multiple_choice
        $optionTexts = $_POST['option'] ?? [];
        if (!is_array($optionTexts)) {
            $optionTexts = [];
        }

        // The radio value is the (string) index of the correct option.
        $correctIndex = isset($_POST['correct']) ? (int) $_POST['correct'] : -1;

        // Build the list of non-empty options, preserving their original index
        // so we can match the correct radio selection.
        $correctCount = 0;
        $position = 1;
        foreach ($optionTexts as $idx => $text) {
            $text = trim((string) $text);
            if ($text === '') {
                continue;
            }
            $isCorrect = ((int) $idx === $correctIndex) ? 1 : 0;
            if ($isCorrect === 1) {
                $correctCount++;
            }
            $validOptions[] = [
                'text'       => $text,
                'is_correct' => $isCorrect,
                'position'   => $position++,
            ];
        }

        if (count($validOptions) < 2) {
            fail($deckId, 'options');
        }
        if (count($validOptions) > 4) {
            fail($deckId, 'options');
        }
        if ($correctCount !== 1) {
            fail($deckId, 'correct');
        }
    }

    // Determine the next position for this deck.
    $stmt = $conn->prepare('SELECT COALESCE(MAX(position), 0) + 1 AS next_pos FROM quiz_item WHERE deck_id = ?');
    $stmt->bind_param('i', $deckId);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $nextPos = (int) $row['next_pos'];
    $stmt->close();

    // Insert the quiz_item. Flashcards store the answer; MC stores NULL.
    $stmt = $conn->prepare(
        'INSERT INTO quiz_item (deck_id, type, question, answer, position) VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->bind_param('isssi', $deckId, $type, $question, $answer, $nextPos);
    $stmt->execute();
    $itemId = (int) $conn->insert_id;
    $stmt->close();

    // For multiple_choice, insert each option.
    if ($type === 'multiple_choice') {
        $stmt = $conn->prepare(
            'INSERT INTO quiz_option (quiz_item_id, option_text, is_correct, position) VALUES (?, ?, ?, ?)'
        );
        foreach ($validOptions as $opt) {
            $stmt->bind_param('isii', $itemId, $opt['text'], $opt['is_correct'], $opt['position']);
            $stmt->execute();
        }
        $stmt->close();
    }

    $conn->close();
} catch (mysqli_sql_exception $e) {
    fail($deckId, 'db');
}

header('Location: deck_items.php?id=' . $deckId . '&added=1');
exit;
