<?php
require('../config/autoload.php');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Already logged in - skip straight to the dashboard
if (isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

function h($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$dao = new DataAccess();
$error = '';

if (isset($_POST['btn_login'])) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Please enter both email and password';
    } else {
        // Email is escaped safely
        $admins = $dao->getData(
            array('id', 'email', 'password'),
            'admins',
            "email='" . addslashes($email) . "'"
        );

        $isValid = false;
        if (!empty($admins)) {
            $stored = $admins[0]['password'];
            if (password_verify($password, $stored)) {
                $isValid = true;
            } elseif ($password === $stored) {
                // Auto-upgrade legacy plain-text password to Bcrypt hash
                $isValid = true;
                $newHash = password_hash($password, PASSWORD_BCRYPT);
                $dao->update(array('password' => $newHash), 'admins', 'id=' . (int)$admins[0]['id']);
            }
        }

        if ($isValid) {
            // Regenerate session ID to prevent session fixation
            session_regenerate_id(true);
            $_SESSION['admin_id'] = $admins[0]['id'];
            header('Location: index.php');
            exit;
        } else {
            $error = 'Invalid username or password';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Login - Online Exam Management System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="assets/css/font-awesome.css" rel="stylesheet" />
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #0c0d14;
            color: #ffffff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow-x: hidden;
            padding: 20px;
        }

        /* Constellation Background Canvas */
        #constellation-canvas {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
            pointer-events: none;
        }

        /* Radial Vignette & Cosmic Glow */
        .ambient-glow {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 700px;
            height: 700px;
            background: radial-gradient(circle, rgba(147, 51, 234, 0.18) 0%, rgba(99, 102, 241, 0.08) 45%, transparent 70%);
            border-radius: 50%;
            filter: blur(80px);
            z-index: 2;
            pointer-events: none;
        }

        /* Centered Login Card with Purple Neon Glow */
        .login-card-container {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 440px;
        }

        .login-card {
            background: rgba(18, 20, 31, 0.85);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(168, 85, 247, 0.35);
            border-radius: 24px;
            padding: 44px 36px 36px;
            box-shadow: 
                0 0 55px rgba(168, 85, 247, 0.35),
                0 0 20px rgba(139, 92, 246, 0.2),
                inset 0 1px 1px rgba(255, 255, 255, 0.12);
            position: relative;
            overflow: hidden;
            transition: box-shadow 0.3s ease, border-color 0.3s ease;
        }

        .login-card:hover {
            box-shadow: 
                0 0 70px rgba(168, 85, 247, 0.45),
                0 0 25px rgba(139, 92, 246, 0.3),
                inset 0 1px 1px rgba(255, 255, 255, 0.15);
            border-color: rgba(168, 85, 247, 0.5);
        }

        /* Title */
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-title {
            font-size: 28px;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: -0.4px;
            margin-bottom: 6px;
        }
        .login-subtitle {
            font-size: 13px;
            color: #94a3b8;
            font-weight: 400;
        }

        /* Alerts */
        .alert-error {
            background: rgba(220, 38, 38, 0.16);
            border: 1px solid rgba(239, 68, 68, 0.4);
            color: #fca5a5;
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 13px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
            backdrop-filter: blur(8px);
        }
        .alert-error i {
            color: #ef4444;
            font-size: 16px;
        }

        /* Form elements */
        .form-group {
            margin-bottom: 20px;
            position: relative;
        }
        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: #cbd5e1;
            margin-bottom: 8px;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }
        .input-icon {
            position: absolute;
            left: 16px;
            color: #818cf8;
            font-size: 15px;
            pointer-events: none;
            transition: color 0.2s ease;
        }

        .form-input {
            width: 100%;
            height: 48px;
            background: rgba(27, 30, 46, 0.7);
            border: 1px solid rgba(139, 92, 246, 0.32);
            border-radius: 12px;
            padding: 0 16px 0 46px;
            font-size: 14px;
            color: #ffffff;
            font-family: inherit;
            transition: all 0.25s ease;
            outline: none;
        }
        .form-input::placeholder {
            color: #64748b;
        }
        .form-input:focus {
            background: rgba(30, 34, 54, 0.9);
            border-color: #a855f7;
            box-shadow: 0 0 16px rgba(168, 85, 247, 0.45);
        }
        .form-input:focus + .input-icon,
        .input-wrapper:focus-within .input-icon {
            color: #c084fc;
        }

        /* Password toggle button */
        .btn-toggle-pwd {
            position: absolute;
            right: 14px;
            background: transparent;
            border: none;
            color: #64748b;
            cursor: pointer;
            padding: 6px;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color 0.2s ease;
        }
        .btn-toggle-pwd:hover {
            color: #cbd5e1;
        }

        /* Log In Button */
        .btn-submit {
            width: 100%;
            height: 48px;
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 50%, #9333ea 100%);
            color: #ffffff;
            border: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            box-shadow: 0 4px 20px rgba(124, 58, 237, 0.45);
            transition: all 0.25s ease;
            margin-top: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .btn-submit:hover {
            background: linear-gradient(135deg, #4338ca 0%, #6d28d9 50%, #7e22ce 100%);
            box-shadow: 0 6px 26px rgba(147, 51, 234, 0.65);
            transform: translateY(-1px);
        }
        .btn-submit:active {
            transform: translateY(1px);
            box-shadow: 0 2px 10px rgba(124, 58, 237, 0.4);
        }

        /* Polygonal Wireframe Mesh Graphic at Bottom of Card */
        .wireframe-mesh {
            width: 100%;
            height: 65px;
            margin-top: 24px;
            opacity: 0.7;
            display: block;
            pointer-events: none;
        }

        /* Portal Footer */
        .login-footer-links {
            text-align: center;
            margin-top: 22px;
            position: relative;
            z-index: 10;
        }
        .back-link {
            color: #94a3b8;
            font-size: 13px;
            text-decoration: none;
            transition: color 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .back-link:hover {
            color: #c084fc;
        }
    </style>
</head>
<body>

    <!-- Interactive Constellation Particles -->
    <canvas id="constellation-canvas"></canvas>

    <!-- Ambient Cosmic Blur -->
    <div class="ambient-glow"></div>

    <div class="login-card-container">
        <div class="login-card">
            <div class="login-header">
                <h1 class="login-title">Admin Login</h1>
                <p class="login-subtitle">Enter your credentials to access the admin portal</p>
            </div>

            <?php if ($error !== '') { ?>
                <div class="alert-error" role="alert">
                    <i class="fa fa-exclamation-circle"></i>
                    <span><?= h($error); ?></span>
                </div>
            <?php } ?>

            <form action="" method="POST" autocomplete="on">
                <div class="form-group">
                    <label class="form-label" for="admin-email">Email</label>
                    <div class="input-wrapper">
                        <i class="fa fa-envelope-o input-icon"></i>
                        <input 
                            type="email" 
                            name="email" 
                            id="admin-email"
                            class="form-input" 
                            placeholder="admin@exam.com"
                            value="<?= isset($_POST['email']) ? h($_POST['email']) : ''; ?>"
                            required 
                            autofocus 
                        />
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="admin-password">Password</label>
                    <div class="input-wrapper">
                        <i class="fa fa-lock input-icon"></i>
                        <input 
                            type="password" 
                            name="password" 
                            id="admin-password" 
                            class="form-input" 
                            placeholder="••••••••"
                            required 
                        />
                        <button type="button" class="btn-toggle-pwd" id="toggle-pwd-btn" title="Show or hide password" aria-label="Toggle password visibility">
                            <i class="fa fa-eye" id="toggle-pwd-icon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" name="btn_login" class="btn-submit">
                    <span>Log In</span>
                    <i class="fa fa-arrow-right" style="font-size: 13px;"></i>
                </button>
            </form>

            <!-- Polygonal wireframe accent matching the user's reference -->
            <svg class="wireframe-mesh" viewBox="0 0 400 80" preserveAspectRatio="none">
                <defs>
                    <linearGradient id="meshGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" stop-color="#818cf8" stop-opacity="0.25"/>
                        <stop offset="50%" stop-color="#a855f7" stop-opacity="0.45"/>
                        <stop offset="100%" stop-color="#c084fc" stop-opacity="0.25"/>
                    </linearGradient>
                </defs>
                <path d="M 0,80 L 30,40 L 70,60 L 110,25 L 150,65 L 200,20 L 250,60 L 290,25 L 330,65 L 370,35 L 400,80 Z" 
                      fill="none" stroke="url(#meshGradient)" stroke-width="1.2" />
                <path d="M 0,80 L 70,60 L 150,65 L 250,60 L 330,65 L 400,80" 
                      fill="none" stroke="url(#meshGradient)" stroke-width="0.8" stroke-dasharray="3,3" />
                <path d="M 30,40 L 110,25 L 200,20 L 290,25 L 370,35" 
                      fill="none" stroke="url(#meshGradient)" stroke-width="0.9" />
                <path d="M 30,40 L 0,80 M 110,25 L 70,60 M 200,20 L 150,65 M 290,25 L 250,60 M 370,35 L 330,65" 
                      fill="none" stroke="url(#meshGradient)" stroke-width="0.8" />
            </svg>
        </div>

        <div class="login-footer-links">
            <a href="../examinee/index.php" class="back-link">
                <i class="fa fa-angle-left"></i>
                <span>Back to Exam Portal</span>
            </a>
        </div>
    </div>

    <!-- Interactive Constellation Network Script -->
    <script>
        (function() {
            var canvas = document.getElementById('constellation-canvas');
            var ctx = canvas.getContext('2d');
            var particles = [];
            var particleCount = 55;
            var maxDistance = 140;
            var width, height;

            var mouse = {
                x: null,
                y: null,
                radius: 120
            };

            function resize() {
                width = canvas.width = window.innerWidth;
                height = canvas.height = window.innerHeight;
            }

            window.addEventListener('resize', resize);
            resize();

            window.addEventListener('mousemove', function(e) {
                mouse.x = e.clientX;
                mouse.y = e.clientY;
            });

            window.addEventListener('mouseout', function() {
                mouse.x = null;
                mouse.y = null;
            });

            function Particle() {
                this.x = Math.random() * width;
                this.y = Math.random() * height;
                this.vx = (Math.random() - 0.5) * 0.7;
                this.vy = (Math.random() - 0.5) * 0.7;
                this.radius = Math.random() * 2.2 + 1.2;
                this.color = Math.random() > 0.4 ? '#c084fc' : '#818cf8';
            }

            Particle.prototype.update = function() {
                this.x += this.vx;
                this.y += this.vy;

                if (this.x < 0 || this.x > width) this.vx = -this.vx;
                if (this.y < 0 || this.y > height) this.vy = -this.vy;

                // Mouse repel / interaction
                if (mouse.x !== null && mouse.y !== null) {
                    var dx = mouse.x - this.x;
                    var dy = mouse.y - this.y;
                    var dist = Math.sqrt(dx * dx + dy * dy);
                    if (dist < mouse.radius) {
                        var force = (mouse.radius - dist) / mouse.radius;
                        this.x -= (dx / dist) * force * 2;
                        this.y -= (dy / dist) * force * 2;
                    }
                }
            };

            Particle.prototype.draw = function() {
                ctx.beginPath();
                ctx.arc(this.x, this.y, this.radius, 0, Math.PI * 2);
                ctx.fillStyle = this.color;
                ctx.shadowBlur = 8;
                ctx.shadowColor = this.color;
                ctx.fill();
                ctx.shadowBlur = 0;
            };

            for (var i = 0; i < particleCount; i++) {
                particles.push(new Particle());
            }

            function connect() {
                for (var i = 0; i < particles.length; i++) {
                    for (var j = i + 1; j < particles.length; j++) {
                        var dx = particles[i].x - particles[j].x;
                        var dy = particles[i].y - particles[j].y;
                        var dist = Math.sqrt(dx * dx + dy * dy);

                        if (dist < maxDistance) {
                            var opacity = (1 - dist / maxDistance) * 0.45;
                            ctx.beginPath();
                            ctx.moveTo(particles[i].x, particles[i].y);
                            ctx.lineTo(particles[j].x, particles[j].y);
                            ctx.strokeStyle = 'rgba(168, 85, 247, ' + opacity + ')';
                            ctx.lineWidth = 1;
                            ctx.stroke();
                        }
                    }
                }
            }

            function animate() {
                ctx.clearRect(0, 0, width, height);
                for (var i = 0; i < particles.length; i++) {
                    particles[i].update();
                    particles[i].draw();
                }
                connect();
                requestAnimationFrame(animate);
            }

            animate();

            // Password Toggle Visibility
            var pwdInput = document.getElementById('admin-password');
            var pwdBtn = document.getElementById('toggle-pwd-btn');
            var pwdIcon = document.getElementById('toggle-pwd-icon');

            if (pwdBtn && pwdInput) {
                pwdBtn.addEventListener('click', function() {
                    var isPassword = pwdInput.type === 'password';
                    pwdInput.type = isPassword ? 'text' : 'password';
                    pwdIcon.className = isPassword ? 'fa fa-eye-slash' : 'fa fa-eye';
                });
            }
        })();
    </script>
</body>
</html>