<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

function session_get($key, $default = null) {
    return isset($_SESSION[$key]) ? $_SESSION[$key] : $default;
}

function session_set($key, $value) {
    $_SESSION[$key] = $value;
}

function normalize_history(array $history): array {
    return array_values(array_slice($history, 0, 10));
}

$patterns = [
    'odd' => ['label' => 'Odd', 'points' => 10],
    'even' => ['label' => 'Even', 'points' => 10],
    'low' => ['label' => 'Low (2–6)', 'points' => 10],
    'high' => ['label' => 'High (7–12)', 'points' => 10],
];

function matchesPattern(string $pattern, int $value): bool {
    switch ($pattern) {
        case 'odd':
            return $value % 2 !== 0;
        case 'even':
            return $value % 2 === 0;
        case 'low':
            return $value >= 2 && $value <= 6;
        case 'high':
            return $value >= 7 && $value <= 12;
        default:
            return false;
    }
}

function jsonResponse(array $payload) {
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

$rawInput = file_get_contents('php://input');
$body = $rawInput ? json_decode($rawInput, true) : null;

if (!is_array($body)) {
    $body = $_POST;
}

$action = isset($body['action']) ? trim((string)$body['action']) : 'status';

$score = (int) session_get('score', 100);
$history = session_get('history', []);

if ($action === 'reset') {
    $score = 100;
    $history = [];
    session_set('score', $score);
    session_set('history', $history);
    jsonResponse(['success' => true, 'score' => $score, 'history' => $history]);
}

if ($action === 'play') {
    $pattern = isset($body['pattern']) ? (string)$body['pattern'] : '';
    $bet = isset($body['bet']) ? (float)$body['bet'] : 1;
    $bet = max(1, $bet);

    // Use available score as max stake
    if ($bet > $score) {
        jsonResponse(['success' => false, 'message' => 'Not enough score for that bet.', 'score' => $score, 'history' => $history]);
    }

    if (!array_key_exists($pattern, $patterns) && $pattern !== 'number') {
        jsonResponse(['success' => false, 'message' => 'Invalid pattern selected.', 'score' => $score, 'history' => $history]);
    }

    $specificNumber = null;
    if ($pattern === 'number') {
        $specificNumber = isset($body['number']) ? (int)$body['number'] : null;
        if ($specificNumber < 2 || $specificNumber > 12) {
            jsonResponse(['success' => false, 'message' => 'Invalid number selected.', 'score' => $score, 'history' => $history]);
        }
    }

    $die1 = random_int(1, 6);
    $die2 = random_int(1, 6);
    $generated = $die1 + $die2;

    $win = false;
    $multiplier = 2;

    if ($pattern === 'number') {
        $win = $generated === $specificNumber;
        $multiplier = 10;
    } else {
        $win = matchesPattern($pattern, $generated);
        $multiplier = 2;
    }

    // Deduct stake first
    $score -= $bet;

    $points = 0;
    if ($win) {
        $points = (int)round($bet * $multiplier);
        $score += $points;
    }

    $round = [
        'timestamp' => date('c'),
        'pattern' => $patterns[$pattern]['label'],
        'bet' => $bet,
        'dice' => [$die1, $die2],
        'generated' => $generated,
        'win' => $win,
        'points' => $points,
    ];

    array_unshift($history, $round);
    $history = normalize_history($history);

    session_set('score', $score);
    session_set('history', $history);

    jsonResponse([
        'success' => true,
        'score' => $score,
        'history' => $history,
        'generated' => $generated,
        'dice' => [$die1, $die2],
        'win' => $win,
        'points' => $points,
    ]);
}

// Default: return status
jsonResponse(['success' => true, 'score' => $score, 'history' => $history]);
