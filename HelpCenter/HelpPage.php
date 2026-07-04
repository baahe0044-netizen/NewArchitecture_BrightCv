<?php
/**
 * help_center.php - Enhanced Interactive Help Center
 * Connected to database with real data and working navigation
 */

// Database connection
$dbname = 'lunettistar_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=localhost;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    $pdo = null;
}

// Get user's resume count
$resumeCount = 0;
$templateCount = 0;

if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM resumes");
        $resumeCount = $stmt->fetchColumn();
        
        $stmt = $pdo->query("SELECT COUNT(*) FROM resume_templates WHERE is_active = 1");
        $templateCount = $stmt->fetchColumn();
    } catch (PDOException $e) {
        // Silent fail
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Help Center - Bright CV Builder</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,300&family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --navy:        #0b1240;
            --navy-light:  #111a55;
            --primary:     #1a56db;
            --primary-dk:  #1447b3;
            --primary-lt:  #eff4ff;
            --secondary:   #6366f1;
            --accent:      #7ba7f7;
            --success:     #0d9a6e;
            --success-lt:  #e6f7f2;
            --warning:     #d97706;
            --ink:         #0f1117;
            --ink-2:       #3d4250;
            --ink-3:       #6b7280;
            --line:        #e4e6ea;
            --line-2:      #f0f1f3;
            --surface:     #ffffff;
            --bg:          #f5f6f8;
            --radius:      10px;
            --radius-lg:   16px;
            --radius-xl:   24px;
            --shadow-sm:   0 1px 3px rgba(0,0,0,.07), 0 1px 2px rgba(0,0,0,.04);
            --shadow-md:   0 4px 16px rgba(0,0,0,.08), 0 1px 4px rgba(0,0,0,.04);
            --shadow-lg:   0 12px 40px rgba(0,0,0,.10), 0 2px 8px rgba(0,0,0,.05);
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { font-size: 15px; -webkit-font-smoothing: antialiased; width: 100%; overflow-x: hidden; }

        body {
            font-family: 'DM Sans', system-ui, -apple-system, sans-serif;
           
            line-height: 1.6;
            width: 100%;
            overflow-x: hidden;

        }

        /* Subtle static background — no animated gradient */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background:
                radial-gradient(ellipse 80% 50% at 10% 0%, rgba(26,86,219,.06) 0%, transparent 60%),
                radial-gradient(ellipse 60% 40% at 90% 100%, rgba(99,102,241,.05) 0%, transparent 60%);
            z-index: -1;
            pointer-events: none;
        }

        /* ── HEADER ─────────────────────────────────────────────── */
        .header {
            background: var(--navy);
            padding: 0;
            box-shadow: 0 2px 16px rgba(0,0,0,.18);
            width: 100%;
            border-radius: 0;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-content {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 32px;
            height: 68px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo-area {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .help-icon {
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,.1);
            border: 1px solid rgba(255,255,255,.15);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--accent);
            font-size: 18px;
        }

        .header h1 {
            font-family: 'DM Serif Display', Georgia, serif;
            font-size: 1.35rem;
            font-weight: 400;
            color: #fff;
            letter-spacing: -0.3px;
        }

        .back-link {
            color: rgba(255,255,255,.75);
            text-decoration: none;
            padding: 8px 18px;
            border: 1.5px solid rgba(255,255,255,.2);
            border-radius: var(--radius);
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            font-size: 0.85rem;
            letter-spacing: 0.01em;
        }

        .back-link:hover {
            background: rgba(255,255,255,.1);
            color: #fff;
            border-color: rgba(255,255,255,.35);
        }

        /* ── HERO ───────────────────────────────────────────────── */
        .hero {
            width: 100%;
            background: var(--navy);
            color: white;
            padding: 72px 24px 100px;
            position: relative;
            overflow: hidden;
            border-bottom: 1px solid rgba(255,255,255,.06);
        }

        .hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse 70% 60% at 50% 110%, rgba(26,86,219,.35) 0%, transparent 70%),
                radial-gradient(ellipse 40% 30% at 80% 20%, rgba(123,167,247,.12) 0%, transparent 60%);
            pointer-events: none;
        }

        .hero-content {
            width: 100%;
            max-width: 760px;
            margin: 0 auto;
            text-align: center;
            position: relative;
        }

        .hero h2 {
            font-family: 'DM Serif Display', Georgia, serif;
            font-size: 2.8rem;
            font-weight: 400;
            margin-bottom: 16px;
            letter-spacing: -0.5px;
            line-height: 1.15;
            animation: fadeInUp 0.5s ease both;
        }

        .hero h2 em {
            font-style: italic;
            color: var(--accent);
        }

        .hero p {
            font-size: 1.05rem;
            opacity: 0.7;
            margin-bottom: 40px;
            font-weight: 400;
            animation: fadeInUp 0.5s ease 0.1s both;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ── SEARCH ─────────────────────────────────────────────── */
        .search-bar {
            max-width: 560px;
            margin: 0 auto;
            position: relative;
            animation: fadeInUp 0.5s ease 0.2s both;
        }

        .search-bar input {
            width: 100%;
            padding: 16px 52px 16px 20px;
            border: 1.5px solid rgba(255,255,255,.15);
            border-radius: var(--radius-lg);
            font-size: 0.95rem;
            font-family: 'DM Sans', sans-serif;
            background: rgba(255,255,255,.08);
            color: #fff;
            transition: all 0.2s;
            backdrop-filter: blur(8px);
        }

        .search-bar input::placeholder { color: rgba(255,255,255,.4); }

        .search-bar input:focus {
            outline: none;
            background: rgba(255,255,255,.13);
            border-color: rgba(255,255,255,.35);
            box-shadow: 0 0 0 3px rgba(123,167,247,.2);
        }

        .search-icon {
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255,255,255,.4);
            font-size: 16px;
            pointer-events: none;
        }

        /* ── STATS BAR ──────────────────────────────────────────── */
        .stats-bar {
            background: var(--surface);
            max-width: 740px;
            margin: -36px auto 0;
            position: relative;
            z-index: 10;
            padding: 0;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-lg);
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            overflow: hidden;
            border: 1px solid var(--line);
        }

        .stat-item {
            text-align: center;
            padding: 28px 20px;
            transition: background 0.2s;
            border-right: 1px solid var(--line);
        }
        .stat-item:last-child { border-right: none; }

        .stat-item:hover { background: var(--line-2); }

        .stat-number {
            font-family: 'DM Serif Display', Georgia, serif;
            font-size: 2rem;
            font-weight: 400;
            color: var(--primary);
            line-height: 1;
            display: block;
            margin-bottom: 6px;
        }

        .stat-label {
            font-size: 0.8rem;
            color: var(--ink-3);
            font-weight: 500;
            letter-spacing: 0.03em;
            text-transform: uppercase;
        }

        /* ── CONTAINER ──────────────────────────────────────────── */
        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 80px 24px;
        }

        /* ── QUICK LINKS ────────────────────────────────────────── */
        .quick-links {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 80px;
        }

        .quick-link-card {
            background: var(--surface);
            padding: 32px 28px;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--line);
            transition: all 0.22s ease;
            cursor: pointer;
            text-decoration: none;
            color: var(--ink);
            position: relative;
            overflow: hidden;
        }

        .quick-link-card::after {
            content: '';
            position: absolute;
            bottom: 0; left: 0; right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.25s ease;
        }

        .quick-link-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
            border-color: #c5d3f0;
        }

        .quick-link-card:hover::after { transform: scaleX(1); }
        .quick-link-card:hover::before { opacity: 0; } /* disable old pseudo */

        .quick-link-icon {
            width: 52px;
            height: 52px;
            border-radius: var(--radius);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            margin-bottom: 18px;
            transition: transform 0.25s ease;
        }

        .quick-link-card:hover .quick-link-icon { transform: scale(1.08); }

        .icon-blue   { background: #dbeafe; color: #1d4ed8; }
        .icon-green  { background: var(--success-lt); color: var(--success); }
        .icon-purple { background: #ede9fe; color: #7c3aed; }
        .icon-orange { background: #fef3c7; color: var(--warning); }

        .quick-link-card h3 {
            font-size: 1.05rem;
            font-weight: 700;
            margin-bottom: 8px;
            color: var(--ink);
        }

        .quick-link-card p {
            color: var(--ink-3);
            font-size: 0.875rem;
            line-height: 1.55;
        }

        /* ── SECTION ────────────────────────────────────────────── */
        .section { margin-bottom: 80px; }

        .section-header {
            margin-bottom: 36px;
            text-align: center;
        }

        .section-header h2 {
            font-family: 'DM Serif Display', Georgia, serif;
            font-size: 2rem;
            font-weight: 400;
            color: var(--ink);
            margin-bottom: 8px;
            letter-spacing: -0.3px;
        }

        .section-header p {
            color: var(--ink-3);
            font-size: 1rem;
        }

        /* ── FAQ ────────────────────────────────────────────────── */
        .faq-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
            max-width: 860px;
            margin: 0 auto;
        }

        .faq-item {
            background: var(--surface);
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--line);
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .faq-item:hover { border-color: #c5d3f0; }
        .faq-item.active { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(26,86,219,.08), var(--shadow-sm); }

        .faq-question {
            padding: 20px 24px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 600;
            font-size: 0.95rem;
            color: var(--ink);
            transition: background 0.15s;
            gap: 16px;
        }

        .faq-question:hover { background: var(--line-2); }
        .faq-item.active .faq-question { background: var(--primary-lt); color: var(--primary); }

        .faq-question i {
            flex-shrink: 0;
            transition: transform 0.25s ease;
            color: var(--ink-3);
            font-size: 14px;
        }

        .faq-item.active .faq-question i { transform: rotate(180deg); color: var(--primary); }

        .faq-answer {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out;
        }

        .faq-item.active .faq-answer { max-height: 600px; }

        .faq-answer-content {
            padding: 4px 24px 24px;
            color: var(--ink-2);
            line-height: 1.75;
            font-size: 0.9rem;
            border-top: 1px solid var(--line);
            margin: 0 0 0 0;
        }

        .faq-answer-content ul {
            margin-top: 12px;
            padding-left: 20px;
        }

        .faq-answer-content li { margin-bottom: 8px; }
        .faq-answer-content a { color: var(--primary); text-decoration: none; font-weight: 600; }
        .faq-answer-content a:hover { text-decoration: underline; }

        /* ── GUIDE CARDS ────────────────────────────────────────── */
        .guide-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 24px;
        }

        .guide-card {
            background: var(--surface);
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--line);
            transition: all 0.22s ease;
            cursor: pointer;
        }

        .guide-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
            border-color: #c5d3f0;
        }

        .guide-image {
            width: 100%;
            height: 180px;
            background: linear-gradient(135deg, var(--navy) 0%, var(--navy-light) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 44px;
            color: var(--accent);
            position: relative;
            overflow: hidden;
        }

        .guide-image::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(ellipse 80% 60% at 50% 110%, rgba(26,86,219,.4) 0%, transparent 70%);
        }

        .guide-image i { position: relative; }

        /* Remove the rotating shine animation — too decorative */
        .guide-image::after { display: none; }

        .guide-content { padding: 24px; }

        .guide-badge {
            display: inline-block;
            padding: 4px 10px;
            background: var(--primary-lt);
            color: var(--primary);
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            margin-bottom: 12px;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            border: 1px solid #c7d7f8;
        }

        .guide-card h3 {
            font-size: 1rem;
            font-weight: 700;
            margin-bottom: 8px;
            color: var(--ink);
        }

        .guide-card p {
            color: var(--ink-3);
            margin-bottom: 18px;
            line-height: 1.55;
            font-size: 0.875rem;
        }

        .guide-link {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.875rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: gap 0.2s;
        }

        .guide-link:hover { gap: 10px; }

        /* ── SUPPORT SECTION ────────────────────────────────────── */
        .support-section {
            background: var(--surface);
            border-radius: var(--radius-xl);
            padding: 56px 40px;
            text-align: center;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--line);
        }

        .support-section h2 {
            font-family: 'DM Serif Display', Georgia, serif;
            font-size: 2rem;
            font-weight: 400;
            color: var(--ink);
            margin-bottom: 12px;
            letter-spacing: -0.3px;
        }

        .support-section > p {
            color: var(--ink-3);
            margin-bottom: 48px;
            font-size: 1rem;
        }

        .support-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 32px;
            margin-bottom: 40px;
        }

        .support-option { text-align: center; }

        .support-icon {
            width: 64px;
            height: 64px;
            margin: 0 auto 16px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            background: var(--primary-lt);
            color: var(--primary);
            border: 1px solid #c7d7f8;
            transition: all 0.2s;
        }

        .support-option:hover .support-icon {
            background: var(--primary);
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(26,86,219,.25);
        }

        .support-option h4 {
            font-size: 1rem;
            font-weight: 700;
            margin-bottom: 8px;
            color: var(--ink);
        }

        .support-option p {
            font-size: 0.85rem;
            color: var(--ink-3);
            margin-bottom: 20px;
        }

        /* ── BUTTONS ────────────────────────────────────────────── */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 10px 22px;
            background: var(--primary);
            color: white;
            text-decoration: none;
            border-radius: var(--radius);
            font-family: 'DM Sans', sans-serif;
            font-weight: 600;
            font-size: 0.875rem;
            transition: all 0.18s ease;
            border: 1.5px solid var(--primary);
            cursor: pointer;
            letter-spacing: 0.01em;
        }

        .btn:hover {
            background: var(--primary-dk);
            border-color: var(--primary-dk);
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(26,86,219,.3);
        }

        .btn-outline {
            background: transparent;
            color: var(--primary);
            border-color: #c7d7f8;
        }

        .btn-outline:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        /* ── VIDEO CARDS ────────────────────────────────────────── */
        .video-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
        }

        .video-card {
            background: var(--surface);
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--line);
            transition: all 0.22s ease;
            cursor: pointer;
        }

        .video-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
            border-color: #c5d3f0;
        }

        .video-thumbnail {
            width: 100%;
            height: 180px;
            background: linear-gradient(135deg, var(--navy) 0%, var(--navy-light) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: rgba(255,255,255,.2);
            font-size: 44px;
            position: relative;
            overflow: hidden;
        }

        .video-thumbnail::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(ellipse 80% 60% at 50% 110%, rgba(26,86,219,.35) 0%, transparent 70%);
        }

        .play-overlay {
            position: absolute;
            width: 60px;
            height: 60px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding-left: 3px;
            color: var(--primary);
            box-shadow: 0 4px 20px rgba(0,0,0,.2);
            transition: transform 0.2s, box-shadow 0.2s;
            font-size: 18px;
        }

        .video-card:hover .play-overlay {
            transform: scale(1.1);
            box-shadow: 0 8px 28px rgba(0,0,0,.25);
        }

        .video-info { padding: 20px 22px; }

        .video-duration {
            display: inline-block;
            padding: 4px 10px;
            background: var(--primary-lt);
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            margin-bottom: 10px;
            color: var(--primary);
            border: 1px solid #c7d7f8;
            letter-spacing: 0.03em;
        }

        .video-card h4 {
            font-size: 0.95rem;
            font-weight: 700;
            margin-bottom: 6px;
            color: var(--ink);
        }

        .video-card p {
            color: var(--ink-3);
            font-size: 0.85rem;
            line-height: 1.5;
        }

        /* ── FOOTER ─────────────────────────────────────────────── */
        .footer {
            background: var(--navy);
            color: white;
            padding: 64px 24px 32px;
            margin-top: 100px;
            width: 100%;
            border-radius: 32px 32px 0 0;
        }

        .footer-content {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 48px;
        }

        .footer h4 {
            font-size: 0.8rem;
            font-weight: 700;
            margin-bottom: 18px;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: rgba(255,255,255,.5);
        }

        .footer a {
            color: rgba(255,255,255,.65);
            text-decoration: none;
            display: block;
            margin-bottom: 10px;
            font-size: 0.9rem;
            transition: color 0.18s, padding-left 0.18s;
        }

        .footer a:hover {
            color: white;
            padding-left: 4px;
        }

        .footer-bottom {
            text-align: center;
            margin-top: 48px;
            padding-top: 28px;
            border-top: 1px solid rgba(255,255,255,.08);
            color: rgba(255,255,255,.4);
            font-size: 0.8rem;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
        }

        /* ── RESPONSIVE ─────────────────────────────────────────── */
        @media (max-width: 768px) {
            .hero h2 { font-size: 2rem; }
            .stats-bar { grid-template-columns: 1fr; }
            .quick-links, .guide-grid, .video-grid { grid-template-columns: 1fr; }
            .support-options { grid-template-columns: 1fr; }
            .container { padding: 48px 20px; }
        }

        /* ── LOADING ANIMATION ──────────────────────────────────── */
        .pulse {
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.6; }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <div class="logo-area">
                <div class="help-icon">
                    <i class="fas fa-question"></i>
                </div>
                <h1>Help Center</h1>
            </div>
            <a href="index.php" class="back-link">
                <i class="fas fa-arrow-left"></i>
                Dashboard
            </a>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h2>How can we <em>help</em> you today?</h2>
            <p>Search our knowledge base or browse by category below</p>
            <div class="search-bar">
                <input type="text" id="searchInput" placeholder="Search for help articles, guides, tutorials...">
                <i class="fas fa-search search-icon"></i>
            </div>
        </div>
    </section>

    <!-- Stats Bar -->
    <div class="container" style="padding-top: 40px;">
        <div class="stats-bar">
            <div class="stat-item">
                <span class="stat-number"><?= $resumeCount ?></span>
                <div class="stat-label">Resumes Created</div>
            </div>
            <div class="stat-item">
                <span class="stat-number"><?= $templateCount ?></span>
                <div class="stat-label">Templates Available</div>
            </div>
            <div class="stat-item">
                <span class="stat-number">24/7</span>
                <div class="stat-label">Support Available</div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <!-- Quick Links -->
        <div class="quick-links">
            <a href="builder.php?resume_id=1" class="quick-link-card">
                <div class="quick-link-icon icon-blue">
                    <i class="fas fa-rocket"></i>
                </div>
                <h3>Getting Started</h3>
                <p>Learn the basics and create your first resume in minutes</p>
            </a>

            <a href="Template.php" class="quick-link-card">
                <div class="quick-link-icon icon-green">
                    <i class="fas fa-palette"></i>
                </div>
                <h3>Browse Templates</h3>
                <p>Choose from <?= $templateCount ?> professional templates</p>
            </a>

            <a href="builder.php?resume_id=1" class="quick-link-card">
                <div class="quick-link-icon icon-purple">
                    <i class="fas fa-edit"></i>
                </div>
                <h3>Resume Builder</h3>
                <p>Edit and customize your resume with live preview</p>
            </a>

            <a href="#export" class="quick-link-card">
                <div class="quick-link-icon icon-orange">
                    <i class="fas fa-download"></i>
                </div>
                <h3>Export &amp; Share</h3>
                <p>Download your resume as PDF and share it</p>
            </a>
        </div>

        <!-- FAQ Section -->
        <section class="section" id="faq">
            <div class="section-header">
                <h2>Frequently Asked Questions</h2>
                <p>Quick answers to common questions</p>
            </div>

            <div class="faq-list">
                <div class="faq-item">
                    <div class="faq-question">
                        <span>How do I create a new resume?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <div class="faq-answer-content">
                            <p><strong>Creating a resume is simple:</strong></p>
                            <ul>
                                <li>Go to <a href="Template.php">Templates page</a> and choose a design</li>
                                <li>Click "Use Template" to start editing</li>
                                <li>Fill in your personal information in the form on the left</li>
                                <li>Watch the live preview update as you type</li>
                                <li>Your changes save automatically every few seconds</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">
                        <span>Can I switch templates after I start?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <div class="faq-answer-content">
                            <p><strong>Yes! Your content is saved separately.</strong> You can:</p>
                            <ul>
                                <li>Go back to the <a href="Template.php">Templates page</a></li>
                                <li>Choose a different template that suits your needs</li>
                                <li>Click "Use Template"</li>
                                <li>All your existing content will automatically populate the new template</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">
                        <span>How do I add work experience or education?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <div class="faq-answer-content">
                            <p><strong>Adding sections is easy:</strong></p>
                            <ul>
                                <li>Use the step navigation on the left side of the <a href="builder.php?resume_id=1">builder</a></li>
                                <li>Click "2. Experience" or "3. Education"</li>
                                <li>Click "+ Add Position" or "+ Add Education"</li>
                                <li>Fill in the details in the form</li>
                                <li>Use the "Delete" button to remove items you don't need</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">
                        <span>Is my data saved automatically?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <div class="faq-answer-content">
                            <p><strong>Yes! We have multiple save mechanisms:</strong></p>
                            <ul>
                                <li><strong>Local Storage:</strong> Changes save to your browser instantly</li>
                                <li><strong>Database Save:</strong> Click "Save Now" button to save to the database</li>
                                <li><strong>Export Backup:</strong> Click "Export" to download a JSON backup file</li>
                                <li>You'll see a green confirmation when data is saved</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">
                        <span>How do I download my resume as PDF?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <div class="faq-answer-content">
                            <p><strong>To download as PDF:</strong></p>
                            <ul>
                                <li>In the <a href="builder.php?resume_id=1">builder</a>, click the "Open" button in the preview panel</li>
                                <li>Your resume will open in a new browser tab</li>
                                <li>Press <strong>Ctrl+P</strong> (Windows) or <strong>Cmd+P</strong> (Mac)</li>
                                <li>Select "Save as PDF" as the destination</li>
                                <li>Adjust margins to "None" for best results</li>
                                <li>Click "Save" and choose where to save your PDF</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="faq-item" id="export">
                    <div class="faq-question">
                        <span>What file formats can I export?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <div class="faq-answer-content">
                            <p><strong>Currently supported formats:</strong></p>
                            <ul>
                                <li><strong>PDF:</strong> Best for job applications (use browser print function)</li>
                                <li><strong>JSON:</strong> Export your data for backup or transfer between devices</li>
                                <li><strong>HTML:</strong> View the full page directly in your browser</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">
                        <span>Can I import my existing resume?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <div class="faq-answer-content">
                            <p><strong>Yes, if you have a JSON export:</strong></p>
                            <ul>
                                <li>Go to the <a href="builder.php?resume_id=1">resume builder</a></li>
                                <li>Click "Import" button</li>
                                <li>Select your JSON file (previously exported from this app)</li>
                                <li>Your data will be loaded into the current resume</li>
                                <li><strong>Note:</strong> Only JSON files created by Bright CV can be imported</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Video Tutorials -->
        <section class="section">
            <div class="section-header">
                <h2>Video Tutorials</h2>
                <p>Watch step-by-step guides</p>
            </div>

            <div class="video-grid">
                <div class="video-card">
                    <div class="video-thumbnail">
                        <div class="play-overlay">
                            <i class="fas fa-play"></i>
                        </div>
                        <i class="fas fa-video"></i>
                    </div>
                    <div class="video-info">
                        <span class="video-duration">3:45</span>
                        <h4>Getting Started</h4>
                        <p>Learn the basics of creating your first professional resume</p>
                    </div>
                </div>

                <div class="video-card">
                    <div class="video-thumbnail">
                        <div class="play-overlay">
                            <i class="fas fa-play"></i>
                        </div>
                        <i class="fas fa-palette"></i>
                    </div>
                    <div class="video-info">
                        <span class="video-duration">5:12</span>
                        <h4>Choosing Templates</h4>
                        <p>Find the perfect template for your industry and style</p>
                    </div>
                </div>

                <div class="video-card">
                    <div class="video-thumbnail">
                        <div class="play-overlay">
                            <i class="fas fa-play"></i>
                        </div>
                        <i class="fas fa-wand-magic-sparkles"></i>
                    </div>
                    <div class="video-info">
                        <span class="video-duration">7:30</span>
                        <h4>Advanced Tips</h4>
                        <p>Master the builder features and create stunning resumes</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Guides -->
        <section class="section">
            <div class="section-header">
                <h2>Helpful Guides</h2>
                <p>In-depth articles and tutorials</p>
            </div>

            <div class="guide-grid">
                <div class="guide-card" id="getting-started" onclick="window.location.href='builder.php?resume_id=1'">
                    <div class="guide-image">
                        <i class="fas fa-rocket"></i>
                    </div>
                    <div class="guide-content">
                        <span class="guide-badge">Beginner</span>
                        <h3>Quick Start Guide</h3>
                        <p>Everything you need to create your first professional resume in just 10 minutes.</p>
                        <span class="guide-link">
                            Start building <i class="fas fa-arrow-right"></i>
                        </span>
                    </div>
                </div>

                <div class="guide-card" id="templates" onclick="window.location.href='Template.php'">
                    <div class="guide-image">
                        <i class="fas fa-palette"></i>
                    </div>
                    <div class="guide-content">
                        <span class="guide-badge">Design</span>
                        <h3>Template Gallery</h3>
                        <p>Browse <?= $templateCount ?> professionally designed templates for every industry.</p>
                        <span class="guide-link">
                            Browse templates <i class="fas fa-arrow-right"></i>
                        </span>
                    </div>
                </div>

                <div class="guide-card" id="editing">
                    <div class="guide-image">
                        <i class="fas fa-lightbulb"></i>
                    </div>
                    <div class="guide-content">
                        <span class="guide-badge">Tips</span>
                        <h3>Writing Best Practices</h3>
                        <p>Write compelling content that gets noticed by recruiters and ATS systems.</p>
                        <span class="guide-link">
                            Learn more <i class="fas fa-arrow-right"></i>
                        </span>
                    </div>
                </div>

                <div class="guide-card">
                    <div class="guide-image">
                        <i class="fas fa-file-pdf"></i>
                    </div>
                    <div class="guide-content">
                        <span class="guide-badge">Export</span>
                        <h3>Download &amp; Share</h3>
                        <p>Learn the best ways to export, download, and share your professional resume.</p>
                        <span class="guide-link">
                            View guide <i class="fas fa-arrow-right"></i>
                        </span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Contact Support -->
        <section class="section">
            <div class="support-section">
                <h2>Still Need Help?</h2>
                <p>Our support team is here to assist you</p>

                <div class="support-options">
                    <div class="support-option">
                        <div class="support-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <h4>Email Support</h4>
                        <p>Get help within 24 hours</p>
                        <a href="mailto:support@brightcv.com" class="btn btn-outline">Send Email</a>
                    </div>

                    <div class="support-option">
                        <div class="support-icon">
                            <i class="fas fa-comments"></i>
                        </div>
                        <h4>Live Chat</h4>
                        <p>Chat with us in real-time</p>
                        <button class="btn btn-outline" onclick="alert('Live chat coming soon! For now, please email us at support@brightcv.com')">Start Chat</button>
                    </div>

                    <div class="support-option">
                        <div class="support-icon">
                            <i class="fas fa-book"></i>
                        </div>
                        <h4>Documentation</h4>
                        <p>Detailed guides &amp; docs</p>
                        <a href="#faq" class="btn btn-outline">View FAQ</a>
                    </div>
                </div>

                <p style="margin-top: 40px; color: var(--ink-3); font-size: 0.875rem;">
                    <i class="fas fa-clock"></i>&nbsp; Support available Monday &ndash; Friday, 9AM &ndash; 5PM GMT
                </p>
            </div>
        </section>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <div>
                <h4>Product</h4>
                <a href="Template.php">Templates</a>
                <a href="builder.php?resume_id=1">Resume Builder</a>
                <a href="#">Cover Letter</a>
                <a href="#">Pricing</a>
            </div>
            <div>
                <h4>Resources</h4>
                <a href="help_center.php">Help Center</a>
                <a href="#">Blog</a>
                <a href="#">Resume Examples</a>
                <a href="#">Career Advice</a>
            </div>
            <div>
                <h4>Company</h4>
                <a href="#">About Us</a>
                <a href="mailto:support@brightcv.com">Contact</a>
                <a href="#">Privacy Policy</a>
                <a href="#">Terms of Service</a>
            </div>
            <div>
                <h4>Connect</h4>
                <a href="#"><i class="fab fa-twitter"></i>&nbsp; Twitter</a>
                <a href="#"><i class="fab fa-facebook"></i>&nbsp; Facebook</a>
                <a href="#"><i class="fab fa-linkedin"></i>&nbsp; LinkedIn</a>
                <a href="#"><i class="fab fa-instagram"></i>&nbsp; Instagram</a>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2026 Bright CV Builder. All rights reserved.</p>
        </div>
    </footer>

    <script>
        // FAQ Accordion
        document.querySelectorAll('.faq-question').forEach(question => {
            question.addEventListener('click', () => {
                const item = question.parentElement;
                const isActive = item.classList.contains('active');
                
                // Close all items
                document.querySelectorAll('.faq-item').forEach(i => i.classList.remove('active'));
                
                // Open clicked item if it wasn't active
                if (!isActive) {
                    item.classList.add('active');
                }
            });
        });

        // Search functionality
        const searchInput = document.getElementById('searchInput');
        searchInput.addEventListener('input', (e) => {
            const searchTerm = e.target.value.toLowerCase();
            
            document.querySelectorAll('.faq-item').forEach(item => {
                const question = item.querySelector('.faq-question span').textContent.toLowerCase();
                const answer = item.querySelector('.faq-answer-content').textContent.toLowerCase();
                
                if (question.includes(searchTerm) || answer.includes(searchTerm)) {
                    item.style.display = 'block';
                    if (searchTerm.length > 2) item.classList.add('active');
                } else {
                    item.style.display = searchTerm.length > 0 ? 'none' : 'block';
                }
            });

            document.querySelectorAll('.guide-card').forEach(card => {
                const title = card.querySelector('h3').textContent.toLowerCase();
                const description = card.querySelector('p').textContent.toLowerCase();
                
                card.style.display = (title.includes(searchTerm) || description.includes(searchTerm)) ? 'block' : (searchTerm.length > 0 ? 'none' : 'block');
            });
        });

        // Smooth scroll
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                const href = this.getAttribute('href');
                if (href !== '#' && document.querySelector(href)) {
                    e.preventDefault();
                    document.querySelector(href).scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Video placeholder
        document.querySelectorAll('.video-card').forEach(card => {
            card.addEventListener('click', () => {
                alert('Video tutorials coming soon! Check back later for step-by-step guides.');
            });
        });

        // Add entrance animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '0';
                    entry.target.style.transform = 'translateY(20px)';
                    setTimeout(() => {
                        entry.target.style.transition = 'all 0.6s ease';
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }, 100);
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);

        document.querySelectorAll('.quick-link-card, .faq-item, .guide-card, .video-card').forEach(el => {
            observer.observe(el);
        });
    </script>
</body>
</html>