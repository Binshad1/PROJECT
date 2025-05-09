<?php
session_start();

if (!isset($_SESSION['game'])) {
    $_SESSION['game'] = [
        'board' => array_fill(0, 9, ''),
        'turn' => 'X',
        'winner' => '',
        'mode' => 'ai',
        'theme' => 'dark',
        'score' => ['X' => 0, 'O' => 0, 'draw' => 0],
        'stats' => ['rounds' => 0]
    ];
}

$game = &$_SESSION['game'];

function checkWinner($b) {
    $w = [
        [0,1,2],[3,4,5],[6,7,8],
        [0,3,6],[1,4,7],[2,5,8],
        [0,4,8],[2,4,6]
    ];
    foreach ($w as $line) {
        if ($b[$line[0]] && $b[$line[0]] === $b[$line[1]] && $b[$line[1]] === $b[$line[2]]) {
            return $b[$line[0]];
        }
    }
    return in_array('', $b) ? '' : 'draw';
}

function minimax($board, $isMax) {
    $res = checkWinner($board);
    if ($res === 'X') return -10;
    if ($res === 'O') return 10;
    if ($res === 'draw') return 0;

    $scores = [];
    foreach ($board as $i => $val) {
        if ($val === '') {
            $board[$i] = $isMax ? 'O' : 'X';
            $scores[] = minimax($board, !$isMax);
            $board[$i] = '';
        }
    }

    return $isMax ? max($scores) : min($scores);
}

function bestMove(&$board) {
    $best = -INF;
    $move = null;
    foreach ($board as $i => $val) {
        if ($val === '') {
            $board[$i] = 'O';
            $score = minimax($board, false);
            $board[$i] = '';
            if ($score > $best) {
                $best = $score;
                $move = $i;
            }
        }
    }
    return $move;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['cell']) && $game['winner'] === '') {
        $i = intval($_POST['cell']);
        if ($game['board'][$i] === '') {
            $game['board'][$i] = $game['turn'];
            $game['winner'] = checkWinner($game['board']);
            if ($game['winner']) {
                $game['score'][$game['winner']] += 1;
                $game['stats']['rounds'] += 1;
            } else {
                $game['turn'] = $game['turn'] === 'X' ? 'O' : 'X';
            }
        }
    }

    if ($game['mode'] === 'ai' && $game['turn'] === 'O' && $game['winner'] === '') {
        $m = bestMove($game['board']);
        if ($m !== null) {
            $game['board'][$m] = 'O';
            $game['winner'] = checkWinner($game['board']);
            if ($game['winner']) {
                $game['score'][$game['winner']] += 1;
                $game['stats']['rounds'] += 1;
            } else {
                $game['turn'] = 'X';
            }
        }
    }

    if (isset($_POST['reset'])) {
        $game['board'] = array_fill(0, 9, '');
        $game['turn'] = 'X';
        $game['winner'] = '';
    }

    if (isset($_POST['reset_all'])) {
        session_destroy();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    if (isset($_POST['mode'])) {
        $game['mode'] = $game['mode'] === 'ai' ? '2p' : 'ai';
        $game['board'] = array_fill(0, 9, '');
        $game['turn'] = 'X';
        $game['winner'] = '';
    }

    if (isset($_POST['theme'])) {
        $game['theme'] = $game['theme'] === 'dark' ? 'light' : 'dark';
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Tic Tac Toe Ultimate</title>
    <style>
        @keyframes pop {
            0% { transform: scale(0.5); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            background: <?= $game['theme'] === 'dark' ? '#0f172a' : '#f1f5f9' ?>;
            color: <?= $game['theme'] === 'dark' ? '#fff' : '#111827' ?>;
            text-align: center;
            padding: 20px;
        }

        .board {
            display: grid;
            grid-template-columns: repeat(3, minmax(80px, 100px));
            gap: 10px;
            margin: 20px auto;
            justify-content: center;
        }

        .cell {
            font-size: 2rem;
            padding: 20px;
            border-radius: 10px;
            background: <?= $game['theme'] === 'dark' ? '#1e293b' : '#cbd5e1' ?>;
            border: none;
            cursor: pointer;
            transition: 0.2s ease;
            animation: pop 0.3s ease;
        }

        .cell:hover {
            background: <?= $game['theme'] === 'dark' ? '#334155' : '#94a3b8' ?>;
        }

        .x { color: red; font-weight: bold; }
        .o { color: gold; font-weight: bold; }

        .info, .score {
            margin: 10px 0;
        }

        .btn {
            margin: 5px;
            padding: 10px 20px;
            background: #ef4444;
            color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
        }

        .btn:hover {
            background: #dc2626;
        }

        @media (max-width: 600px) {
            .cell {
                font-size: 1.5rem;
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <h1>Tic Tac Toe ⚔️</h1>

    <form method="POST">
        <div class="board">
            <?php foreach ($game['board'] as $i => $v): ?>
                <button name="cell" value="<?= $i ?>" class="cell" <?= $v || $game['winner'] ? 'disabled' : '' ?>>
                    <span class="<?= strtolower($v) ?>">
                        <?= $v === 'X' ? '❌' : ($v === 'O' ? '🟡' : '') ?>
                    </span>
                </button>
            <?php endforeach; ?>
        </div>
    </form>

    <div class="info">
        <?= $game['winner'] === 'draw' ? "Draw! 🤝" : ($game['winner'] ? "$game[winner] wins! 🎉" : "$game[turn]'s Turn") ?>
    </div>

    <div class="score">
        <strong>Wins:</strong> X - <?= $game['score']['X'] ?> | O - <?= $game['score']['O'] ?> | Draws - <?= $game['score']['draw'] ?><br>
        Rounds Played: <?= $game['stats']['rounds'] ?> |
        Win %: <?= $game['stats']['rounds'] ? round(($game['score']['X'] / $game['stats']['rounds']) * 100, 1) : 0 ?>%
    </div>

    <form method="POST">
        <button name="reset" class="btn">Next Round</button>
        <button name="reset_all" class="btn">Reset All</button>
        <button name="mode" class="btn">Switch Mode (<?= $game['mode'] === 'ai' ? 'Vs Player' : 'Vs AI' ?>)</button>
        <button name="theme" class="btn">Toggle Theme</button>
    </form>
</body>
</html>
