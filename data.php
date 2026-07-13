<?php
session_start();
$is_logged_in = isset($_SESSION['user_id']);
$username = $is_logged_in ? $_SESSION['username'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smash Pro Badminton | Premium Court Equipment</title>
    <style>
        :root {
            --bg-gradient: linear-gradient(135deg, #f3f7fc 0%, #e6effa 100%);
            --text-main: #2d3748;
            --text-muted: #64748b;
            --card-bg: #ffffff;
            --card-border: rgba(227, 238, 252, 0.9);
            --header-bg: rgba(255, 255, 255, 0.85);
            --title-color: #1a202c;
            --hero-overlay: rgba(30, 41, 59, 0.6);
        }

        [data-theme="dark"] {
            --bg-gradient: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            --text-main: #f1f5f9;
            --text-muted: #94a3b8;
            --card-bg: #1e293b;
            --card-border: rgba(51, 65, 85, 0.5);
            --header-bg: rgba(15, 23, 42, 0.85);
            --title-color: #ffffff;
            --hero-overlay: rgba(15, 23, 42, 0.75);
        }

        body {
            background: var(--bg-gradient);
            color: var(--text-main);
            font-family: 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 0;
            transition: background 0.3s, color 0.3s;
        }

        header {
            background: var(--header-bg);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            padding: 1.2rem 3rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(47, 199, 52, 0.2);
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);
        }
        .logo { font-size: 24px; font-weight: 800; color: #1e8a4b; text-decoration: none; }
        .nav-links { display: flex; align-items: center; gap: 20px; }
        .nav-links a { color: #5e3b96; text-decoration: none; font-weight: 700; font-size: 15px; }
        [data-theme="dark"] .nav-links a { color: #a78bfa; }

        .theme-toggle {
            background: rgba(94, 59, 150, 0.1); color: #5e3b96; border: none;
            padding: 8px 14px; border-radius: 12px; cursor: pointer; font-weight: 700;
        }

        .hero {
            position: relative;
            background-image: url('https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?w=1600&auto=format&fit=crop&q=80');
            background-size: cover;
            background-position: center;
            height: 500px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: #ffffff;
        }
        .hero::before {
            content: ''; position: absolute; top:0; left:0; right:0; bottom:0;
            background: var(--hero-overlay); z-index: 1; transition: background 0.3s;
        }
        .hero-content { position: relative; z-index: 2; max-width: 800px; padding: 0 1.5rem; }
        .hero h1 { font-size: 44px; font-weight: 900; margin: 0 0 1rem 0; letter-spacing: -1px; }
        .hero p { font-size: 18px; margin: 0 0 2rem 0; opacity: 0.9; line-height: 1.6; }

        .btn-cta {
            background: linear-gradient(135deg, #2fc734 0%, #1e8a4b 100%);
            color: #fff; text-decoration: none; padding: 16px 36px; border-radius: 14px;
            font-weight: 700; font-size: 16px; display: inline-block; box-shadow: 0 4px 15px rgba(47,199,52,0.4);
            transition: transform 0.2s, box-shadow 0.2s; border: none; cursor: pointer;
        }
        .btn-cta:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(47,199,52,0.6); }

        .container { max-width: 1250px; margin: 4rem auto; padding: 0 1.5rem; }
        .section-title { font-size: 32px; font-weight: 800; color: var(--title-color); margin-bottom: 2.5rem; text-align: center; }

        /* MATCHING GRID FROM PAGE.PHP */
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(290px, 1fr)); gap: 2rem; margin-bottom: 5rem; }

        .feature-card {
            background: var(--card-bg); border-radius: 24px; padding: 2rem 1.5rem;
            display: flex; flex-direction: column; text-align: center;
            transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.02);
            border: 1px solid var(--card-border);
        }
        .feature-card:hover { transform: translateY(-6px); box-shadow: 0 20px 40px rgba(0, 0, 0, 0.08); }
        .feature-icon { font-size: 3rem; margin-bottom: 1.25rem; display: inline-block; }
        .feature-card h3 { font-size: 20px; font-weight: 700; color: var(--title-color); margin: 0 0 10px 0; }
        .feature-card p { font-size: 14px; color: var(--text-muted); margin: 0; line-height: 1.6; }

        /* COLLECTION TILES */
        .category-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; }
        .cat-tile {
            position: relative; border-radius: 24px; height: 260px; overflow: hidden;
            display: flex; align-items: flex-end; padding: 1.5rem; text-decoration: none; color: #fff; font-weight: 700; font-size: 20px;
            transition: transform 0.3s ease, box-shadow 0.3s ease; border: 1px solid var(--card-border);
        }
        .cat-tile:hover { transform: translateY(-6px); box-shadow: 0 15px 30px rgba(0,0,0,0.15); }
        .cat-tile::before { content: ''; position: absolute; top:0; left:0; right:0; bottom:0; background: linear-gradient(to top, rgba(0,0,0,0.85), transparent 70%); z-index: 1; }
        .cat-tile-img { position: absolute; top:0; left:0; width:100%; height:100%; object-fit: cover; transition: transform 0.5s ease; z-index: 0; }
        .cat-tile:hover .cat-tile-img { transform: scale(1.05); }
        .cat-tile span { position: relative; z-index: 2; display: flex; align-items: center; gap: 8px; }

        footer { background: var(--card-bg); text-align: center; padding: 3rem 1.5rem; border-top: 1px solid var(--card-border); color: var(--text-muted); font-size: 14px; margin-top: 5rem; }
    </style>
</head>
<body>

    <header>
        <a href="home.php" class="logo">SmashPro Badminton</a>
        <div class="nav-links">
            <button class="theme-toggle" id="themeBtn">🌓 Mode</button>
            <a href="page.php">Shop Equipment</a>

            <?php if ($is_logged_in): ?>
                <a href="../login/dashboard.php">My Account</a>
            <?php else: ?>
                <a href="../login/login.php">Sign In</a>
            <?php endif; ?>
        </div>
    </header>

    <section class="hero">
        <div class="hero-content">
            <h1>Engineered for Ultimate Court Performance</h1>
            <p>Explore an elite catalog of professional badminton rackets, high-end court footwear, and tournament-grade gear chosen by champions.</p>
            <a href="page.php" class="btn-cta">Explore Shop Catalog</a>
        </div>
    </section>

    <main class="container">
        <!-- ADVANTAGES GRID (MATCHES PRODUCT CARD STYLES) -->
        <h2 class="section-title">Why Choose Smash Pro?</h2>
        <div class="grid">
            <div class="feature-card">
                <span class="feature-icon">🛡️</span>
                <h3>Genuine Authenticity</h3>
                <p>Every single product item in our warehouse is sourced directly from manufacturers with certified factory warrantees.</p>
            </div>
            <div class="feature-card">
                <span class="feature-icon">⚡</span>
                <h3>Lightning Dispatch</h3>
                <p>Orders processed within 24 hours with premium packaging architecture to ensure string tension is preserved perfectly.</p>
            </div>
            <div class="feature-card">
                <span class="feature-icon">🏆</span>
                <h3>Pro-Curated Setup</h3>
                <p>Our catalog configurations are tested and graded by master stringers and high-performance tournament players.</p>
            </div>
        </div>

        <!-- SPECIALIZED COLLECTIONS GRID -->
        <h2 class="section-title">Shop by Specialized Collection</h2>
        <div class="category-grid">
            <a href="page.php?category=rackets" class="cat-tile">
                <img src="https://media.istockphoto.com/id/2183835587/vector/badminton-racket-sports.jpg?s=612x612&w=0&k=20&c=GQEBnUD0MytafRNZ03_NhDqjUhl8RzrMnjw88alETWk=" alt="Rackets" class="cat-tile-img">
                <span>Professional Rackets →</span>
            </a>
            <a href="page.php?category=shuttlecocks" class="cat-tile">
                <img src="https://t3.ftcdn.net/jpg/17/65/35/80/360_F_1765358003_w1SteAB4SHRuK76LZiaf2XeMsmF5rCth.jpg" alt="Shuttlecocks" class="cat-tile-img">
                <span>Tournament Shuttles →</span>
            </a>
            <a href="page.php?category=footwear" class="cat-tile">
                <img src="https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=400&q=80" alt="Footwear" class="cat-tile-img">
                <span>Court Footwear →</span>
            </a>
            <a href="page.php?category=bags %26 accessories" class="cat-tile">
                <img src="https://noahrabbi.com/wp-content/uploads/2024/03/19_220c9d67-78b6-43cd-a091-2cab0a201ecb.webp" alt="Bags" class="cat-tile-img">
                <span>Pro Gear & Bags →</span>
            </a>
        </div>
    </main>

    <footer>
        <p>&copy; 2026 Smash Pro Badminton Equipment. All Rights Reserved.</p>
    </footer>

    <script>
        const themeBtn = document.getElementById('themeBtn');
        if (localStorage.getItem('theme') === 'dark') document.documentElement.setAttribute('data-theme', 'dark');

        themeBtn.addEventListener('click', () => {
            if (document.documentElement.getAttribute('data-theme') === 'dark') {
                document.documentElement.removeAttribute('data-theme');
                localStorage.setItem('theme', 'light');
            } else {
                document.documentElement.setAttribute('data-theme', 'dark');
                localStorage.setItem('theme', 'dark');
            }
        });
    </script>
</body>
</html>