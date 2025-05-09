<?php
// game.php - 2D Shooter Game
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>2D Shooter Game</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    canvas { background: #000; display: block; margin: 0 auto; }
    #score {
      position: absolute;
      top: 10px;
      left: 10px;
      color: white;
      font-family: Arial, sans-serif;
      font-size: 20px;
    }
  </style>
</head>
<body>
  <div id="score">Score: 0</div>
  <canvas id="gameCanvas" width="480" height="640"></canvas>

  <script>
    const canvas = document.getElementById('gameCanvas');
    const ctx = canvas.getContext('2d');

    const player = {
      x: canvas.width / 2 - 20,
      y: canvas.height - 60,
      width: 40,
      height: 40,
      speed: 5,
      color: 'lime'
    };

    const bullets = [];
    const enemies = [];
    let score = 0;

    let keys = {};

    document.addEventListener('keydown', e => {
      keys[e.code] = true;
    });

    document.addEventListener('keyup', e => {
      keys[e.code] = false;
    });

    function drawPlayer() {
      ctx.fillStyle = player.color;
      ctx.fillRect(player.x, player.y, player.width, player.height);
    }

    function drawBullets() {
      ctx.fillStyle = 'yellow';
      bullets.forEach((b, index) => {
        b.y -= 7;
        ctx.fillRect(b.x, b.y, 5, 10);

        if (b.y < 0) bullets.splice(index, 1);
      });
    }

    function spawnEnemies() {
      if (Math.random() < 0.03) {
        enemies.push({
          x: Math.random() * (canvas.width - 30),
          y: -30,
          width: 30,
          height: 30,
          color: 'red',
          speed: 2 + Math.random() * 2
        });
      }
    }

    function drawEnemies() {
      enemies.forEach((e, eIndex) => {
        e.y += e.speed;
        ctx.fillStyle = e.color;
        ctx.fillRect(e.x, e.y, e.width, e.height);

        // Check collision with bullets
        bullets.forEach((b, bIndex) => {
          if (
            b.x < e.x + e.width &&
            b.x + 5 > e.x &&
            b.y < e.y + e.height &&
            b.y + 10 > e.y
          ) {
            enemies.splice(eIndex, 1);
            bullets.splice(bIndex, 1);
            score += 10;
            document.getElementById('score').textContent = 'Score: ' + score;
          }
        });

        // Check collision with player
        if (
          player.x < e.x + e.width &&
          player.x + player.width > e.x &&
          player.y < e.y + e.height &&
          player.y + player.height > e.y
        ) {
          alert("Game Over! Your score: " + score);
          document.location.reload();
        }
      });
    }

    function update() {
      ctx.clearRect(0, 0, canvas.width, canvas.height);
      drawPlayer();
      drawBullets();
      spawnEnemies();
      drawEnemies();

      // Move player
      if (keys['ArrowLeft'] || keys['KeyA']) {
        player.x -= player.speed;
        if (player.x < 0) player.x = 0;
      }
      if (keys['ArrowRight'] || keys['KeyD']) {
        player.x += player.speed;
        if (player.x + player.width > canvas.width) player.x = canvas.width - player.width;
      }

      requestAnimationFrame(update);
    }

    document.addEventListener('keydown', e => {
      if (e.code === 'Space') {
        bullets.push({
          x: player.x + player.width / 2 - 2.5,
          y: player.y
        });
      }
    });

    update();
  </script>
</body>
</html>
