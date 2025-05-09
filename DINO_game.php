<?php
// Score handling logic (API)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $score = intval($_POST['score'] ?? 0);
    $file = 'scores.txt';

    if ($score > 0) {
        file_put_contents($file, $score . PHP_EOL, FILE_APPEND);
    }

    $scores = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $scores = array_map('intval', $scores);
    rsort($scores); // Highest first
    $topScores = array_slice($scores, 0, 5);
    echo json_encode(['topScores' => $topScores]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Dino Deluxe</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      background-color: white; /* Default body background color */
      font-family: 'Courier New', monospace;
      user-select: none;
      overflow-x: hidden;
    }

    #game {
      position: relative;
      width: 100%;
      max-width: 800px;
      height: 300px;
      margin: 40px auto;
      background-color: white; /* In-game background */
      border: 4px solid #333;
      overflow: hidden;
      border-radius: 10px;
    }

    #dino {
      width: 60px;
      height: 60px;
      text-align: center;
      font-size: 40px; /* Larger emoji size */
      position: absolute;
      bottom: 10px;
      left: 60px;
      z-index: 10;
      color: #FFF;
    }

    #obstacle {
      font-size: 40px; /* Cactus emoji size */
      position: absolute;
      bottom: 10px;
      z-index: 5;
      left: 100%; /* Start at the right edge */
      display: block;
      color: green; /* Cactus color */
    }

    .jump {
      animation: jump 0.6s ease-out;
    }

    @keyframes jump {
      0% { bottom: 10px; }
      50% { bottom: 120px; }
      100% { bottom: 10px; }
    }

    #hud {
      text-align: center;
      font-size: 20px;
      margin-top: 10px;
    }

    #hud span {
      font-weight: bold;
    }

    #leaderboard {
      margin: 20px auto;
      max-width: 300px;
      text-align: center;
    }

    #leaderboard h3 {
      margin-bottom: 5px;
    }

    #restartBtn {
      display: none;
      text-align: center;
      margin-top: 20px;
    }

    #restartBtn button {
      padding: 10px 20px;
      background: #333;
      color: #fff;
      font-size: 16px;
      border: none;
      border-radius: 5px;
      cursor: pointer;
    }

    #settings {
      text-align: center;
      margin-top: 15px;
    }

    #settings label {
      margin: 0 10px;
    }

    @media (max-width: 600px) {
      #dino, #obstacle {
        width: 40px;
        height: 40px;
      }
    }
  </style>
</head>
<body>

  <div id="game">
    <div id="dino">🦄</div> <!-- Emoji Dino -->
    <div id="obstacle">🌵</div> <!-- Cactus emoji as obstacle -->
  </div>

  <div id="hud">
    🎯 Score: <span id="score">0</span> | 🏆 High: <span id="highScore">0</span>
  </div>

  <div id="leaderboard">
    <h3>🏅 Top 5 Scores</h3>
    <ul id="scoresList"></ul>
  </div>

  <div id="restartBtn">
    <button onclick="location.reload()">🔁 Play Again</button>
  </div>

  <div id="settings">
    <label><input type="checkbox" id="toggleMusic" checked> Music</label>
  </div>

  <!-- Audio -->
  <audio id="bgMusic" src="https://cdn.pixabay.com/download/audio/2022/03/10/audio_21b948852f.mp3?filename=8-bit-arcade-115856.mp3" loop></audio>

  <script>
    const dino = document.getElementById("dino");
    const obstacle = document.getElementById("obstacle");
    const scoreText = document.getElementById("score");
    const highScoreText = document.getElementById("highScore");
    const scoresList = document.getElementById("scoresList");
    const restartBtn = document.getElementById("restartBtn");

    const bgMusic = document.getElementById("bgMusic");

    const toggleMusic = document.getElementById("toggleMusic");

    let score = 0;
    let highScore = 0;
    let isJumping = false;
    let gameRunning = true;

    // Start music
    bgMusic.volume = 0.3;
    if (toggleMusic.checked) bgMusic.play();

    // Jump action
    function jump() {
      if (isJumping) return;
      dino.classList.add("jump");
      isJumping = true;
      setTimeout(() => {
        dino.classList.remove("jump");
        isJumping = false;
      }, 600);
    }

    // Touch support
    document.addEventListener("keydown", (e) => {
      if (e.code === "Space") jump();
    });

    document.addEventListener("touchstart", () => {
      jump();
    });

    // Game loop
    const gameLoop = setInterval(() => {
      if (!gameRunning) return;

      const dinoBox = dino.getBoundingClientRect();
      const obstacleBox = obstacle.getBoundingClientRect();

      if (
        obstacleBox.left < dinoBox.right &&
        obstacleBox.right > dinoBox.left &&
        obstacleBox.bottom > dinoBox.top
      ) {
        gameOver();
      }

      score++;
      scoreText.innerText = score;
      if (score > highScore) {
        highScore = score;
        highScoreText.innerText = highScore;
      }

      // Change in-game background color at each 100 points
      if (score % 100 === 0 && score > 0) {
        document.getElementById("game").style.backgroundColor = 
          document.getElementById("game").style.backgroundColor === 'white' ? 'black' : 'white';
      }

      // Move obstacle (cactus emoji)
      let obstacleLeft = parseInt(obstacle.style.left);
      if (obstacleLeft < -60) {
        obstacle.style.left = '100%'; // Reset the obstacle to start
      }
      obstacle.style.left = obstacleLeft - 5 + 'px'; // Move obstacle to the left

    }, 50);

    function gameOver() {
      gameRunning = false;
      clearInterval(gameLoop);
      bgMusic.pause();
      restartBtn.style.display = "block";
      saveScore(score);
    }

    function saveScore(score) {
      fetch("dino_deluxe.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "score=" + score
      })
      .then(res => res.json())
      .then(data => {
        updateLeaderboard(data.topScores);
      });
    }

    function updateLeaderboard(scores) {
      scoresList.innerHTML = "";
      scores.forEach((s, i) => {
        const li = document.createElement("li");
        li.textContent = `#${i+1}: ${s}`;
        scoresList.appendChild(li);
      });
    }

    // Settings toggles
    toggleMusic.addEventListener("change", () => {
      if (toggleMusic.checked) {
        bgMusic.play();
      } else {
        bgMusic.pause();
      }
    });

  </script>
</body>
</html>
