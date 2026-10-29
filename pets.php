<?php
/* =========================================================
   StudyBuddy — egg & pet mascot logic
   Require AFTER auth.php and db.php.

   Stages:
     guest      not logged in (or the pet table is unavailable)
     none       logged in, no egg chosen yet  -> welcome overlay
     incubating egg chosen, fewer than PERFECTS_TO_HATCH perfect scores
     ready      enough perfect scores, waiting for the user to tap Hatch
     hatched    pet revealed (permanent)
   ========================================================= */

const PERFECTS_TO_HATCH = 5;

/** Egg config: display name, habitat label, and the pets it can hatch. */
const EGGS = [
    'cozy'   => ['name' => 'Cozy Egg',   'habitat' => 'Home',   'pool' => ['cat', 'dog']],
    'forest' => ['name' => 'Forest Egg', 'habitat' => 'Forest', 'pool' => ['bunny', 'panda']],
    'frost'  => ['name' => 'Frost Egg',  'habitat' => 'Ice',    'pool' => ['penguin', 'ice_bear']],
];

const PET_NAMES = [
    'cat'      => 'Cat',
    'dog'      => 'Dog',
    'bunny'    => 'Bunny',
    'panda'    => 'Panda',
    'penguin'  => 'Penguin',
    'ice_bear' => 'Ice Bear',
];

/** "Cat or Dog" style list of what an egg can hatch into. */
function egg_pool_label(string $egg): string
{
    $names = array_map(static fn ($p) => PET_NAMES[$p], EGGS[$egg]['pool'] ?? []);
    return implode(' or ', $names);
}

/**
 * The user's pet state.
 * @return array{stage:string, egg:?string, progress:int, pet:?string}
 */
function pet_state(mysqli $conn, int $uid): array
{
    $state = ['stage' => 'guest', 'egg' => null, 'progress' => 0, 'pet' => null];
    if ($uid <= 0) {
        return $state;
    }

    try {
        $stmt = $conn->prepare('SELECT egg_type, chosen_at, pet FROM user_pet WHERE user_id = ?');
        $stmt->bind_param('i', $uid);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) {
            $state['stage'] = 'none';
            return $state;
        }

        $state['egg'] = $row['egg_type'];

        if ($row['pet'] !== null) {
            $state['stage']    = 'hatched';
            $state['pet']      = $row['pet'];
            $state['progress'] = PERFECTS_TO_HATCH;
            return $state;
        }

        // Perfect scores earned at or after choosing the egg.
        $cnt = $conn->prepare(
            'SELECT COUNT(*) FROM quiz_attempt
             WHERE user_id = ? AND total > 0 AND score = total AND created_at >= ?'
        );
        $cnt->bind_param('is', $uid, $row['chosen_at']);
        $cnt->execute();
        $perfects = (int) $cnt->get_result()->fetch_row()[0];
        $cnt->close();

        $state['progress'] = min($perfects, PERFECTS_TO_HATCH);
        $state['stage']    = $state['progress'] >= PERFECTS_TO_HATCH ? 'ready' : 'incubating';
        return $state;
    } catch (\Throwable $e) {
        // Missing table / DB hiccup: behave like a guest so pages still render.
        return ['stage' => 'guest', 'egg' => null, 'progress' => 0, 'pet' => null];
    }
}

/** Server-side random pet from the egg's pool (equal odds). */
function pick_pet(string $egg): string
{
    $pool = EGGS[$egg]['pool'];
    return $pool[random_int(0, count($pool) - 1)];
}
