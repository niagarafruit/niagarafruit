<?php

// ============================================
// PHP BACKEND - VISITOR COUNTER
// ============================================
$dataDir = __DIR__ . '/visitor_data';
$onlineDir = $dataDir . '/online';
$totalFile = $dataDir . '/total.txt';
$historyDir = $dataDir . '/history';

// Buat direktori jika belum ada
if (!file_exists($dataDir)) { mkdir($dataDir, 0755, true); }
if (!file_exists($onlineDir)) { mkdir($onlineDir, 0755, true); }
if (!file_exists($historyDir)) { mkdir($historyDir, 0755, true); }

// Jika ada parameter 'visitor_api', proses sebagai API
if (isset($_GET['visitor_api']) || isset($_POST['visitor_api'])) {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit();
    }
    
    $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'status';
    
    function cleanupStaleVisitors($onlineDir) {
        $files = glob($onlineDir . '/*.txt');
        $now = time();
        foreach ($files as $file) {
            $mtime = filemtime($file);
            if ($now - $mtime > 120) { @unlink($file); }
        }
    }
    
    function getOnlineCount($onlineDir) {
        cleanupStaleVisitors($onlineDir);
        $files = glob($onlineDir . '/*.txt');
        return count($files);
    }
    
    function getTotalVisits($totalFile) {
        if (file_exists($totalFile)) { return (int)file_get_contents($totalFile); }
        return 0;
    }
    
    function incrementTotal($totalFile) {
        $total = getTotalVisits($totalFile);
        $total++;
        file_put_contents($totalFile, $total);
        return $total;
    }
    
    function isNewVisitor($historyDir, $visitorId) {
        $file = $historyDir . '/' . md5($visitorId) . '.txt';
        if (file_exists($file)) {
            file_put_contents($file, time());
            return false;
        }
        file_put_contents($file, time());
        return true;
    }
    
    switch ($action) {
        case 'register':
            $visitorId = isset($_POST['visitor_id']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['visitor_id']) : 'unknown';
            $onlineFile = $onlineDir . '/' . $visitorId . '.txt';
            file_put_contents($onlineFile, time());
            $isNew = isNewVisitor($historyDir, $visitorId);
            $total = getTotalVisits($totalFile);
            if ($isNew) { $total = incrementTotal($totalFile); }
            echo json_encode(['success' => true, 'online' => getOnlineCount($onlineDir), 'total' => $total, 'is_new' => $isNew]);
            break;
        case 'ping':
            $visitorId = isset($_POST['visitor_id']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['visitor_id']) : 'unknown';
            $onlineFile = $onlineDir . '/' . $visitorId . '.txt';
            if (file_exists($onlineFile)) { touch($onlineFile); } else { file_put_contents($onlineFile, time()); }
            echo json_encode(['success' => true, 'online' => getOnlineCount($onlineDir), 'total' => getTotalVisits($totalFile)]);
            break;
        case 'unregister':
            $visitorId = isset($_POST['visitor_id']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['visitor_id']) : 'unknown';
            $onlineFile = $onlineDir . '/' . $visitorId . '.txt';
            if (file_exists($onlineFile)) { @unlink($onlineFile); }
            echo json_encode(['success' => true, 'online' => getOnlineCount($onlineDir), 'total' => getTotalVisits($totalFile)]);
            break;
        default:
            echo json_encode(['success' => true, 'online' => getOnlineCount($onlineDir), 'total' => getTotalVisits($totalFile)]);
            break;
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UMKM Niaga Fruit | Home</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        :root {
            --logo-green: #8dc63f; 
            --logo-brown: #6d4c41;
            --text-dark: #2d3436;
            --text-light: #636e72;
            --bg-light: #fafff7;
            --bg-white: #ffffff;
            --border-color: #e0e0e0;
            --header-height: 80px;
            --container-max: 1200px;
            --border-radius: 20px;
            --box-shadow: 0 10px 40px rgba(0,0,0,0.06);
            --transition: all 0.3s ease;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { scroll-behavior: smooth; }

        body {
            font-family: 'Poppins', sans-serif;
            line-height: 1.6;
            color: var(--text-dark);
            background-color: var(--bg-light);
            overflow-x: hidden;
        }

        .container { max-width: var(--container-max); margin: 0 auto; padding: 0 20px; }

        .header {
            position: fixed; top: 0; left: 0; width: 100%;
            height: var(--header-height); background-color: var(--bg-white);
            box-shadow: 0 2px 10px rgba(0,0,0,0.05); z-index: 1000; transition: var(--transition);
        }

        .navbar {
            display: flex; justify-content: space-between; align-items: center;
            height: 100%; max-width: var(--container-max); margin: 0 auto; padding: 0 20px;
        }

        .logo {
            display: flex; align-items: center; gap: 12px; text-decoration: none;
            font-size: 1.5rem; font-weight: 700; 
        }
        .logo-img {
            height: 50px; width: auto; display: block;
            object-fit: contain; border: 2px dashed #ccc;
            padding: 4px; background: #f9f9f9; border-radius: 8px;
            transition: all 0.3s ease;
        }
        .logo-img-loaded { 
            border: none !important; padding: 0 !important; 
            background: transparent !important; border-radius: 0 !important;
            height: 50px; width: auto;
        }
        
        .logo-text-wrap { display: flex; flex-direction: column; line-height: 1; padding-top: 6px; }
        .logo-text-green { color: var(--logo-green); font-size: 1.4rem; font-weight: 800; letter-spacing: -1px; }
        .logo-text-brown { color: var(--logo-brown); font-size: 1.4rem; font-weight: 800; letter-spacing: -1px; margin-top: -4px; }

        .nav-menu { display: flex; list-style: none; gap: 5px; align-items: center; }
        .nav-menu li a {
            text-decoration: none; color: var(--text-dark); padding: 8px 18px;
            border-radius: 25px; transition: var(--transition); font-weight: 500; font-size: 0.95rem; 
        }
        .nav-menu li a:hover, .nav-menu li a.active { background-color: var(--logo-green); color: white; }
        .nav-cta-btn {
            background-color: var(--logo-green); color: white !important; 
            border-radius: 25px; padding: 8px 18px; display: flex; align-items: center; gap: 8px;
        }
        .nav-cta-btn:hover { background-color: #7ab532 !important; }

        .hamburger { display: none; flex-direction: column; cursor: pointer; gap: 5px; }
        .hamburger span { width: 28px; height: 3px; background-color: var(--text-dark); transition: var(--transition); border-radius: 5px; }
        .hamburger.active span:nth-child(1) { transform: rotate(45deg) translate(5px, 5px); }
        .hamburger.active span:nth-child(2) { opacity: 0; }
        .hamburger.active span:nth-child(3) { transform: rotate(-45deg) translate(5px, -5px); }

        .section { padding: 80px 0; }
        .section:nth-child(even) { background-color: var(--bg-white); }

        .section-title { text-align: center; font-size: 2.2rem; font-weight: 700; margin-bottom: 15px; color: var(--text-dark); }
        .section-subtitle { text-align: center; color: var(--text-light); font-size: 1rem; margin-bottom: 40px; }
        .section-tag { display: inline-block; background: #f0f7e6; color: var(--logo-green); padding: 5px 15px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; margin-bottom: 10px; }

        #home {
            position: relative; padding-top: 140px; padding-bottom: 80px; min-height: auto; overflow: hidden;
            background-image: url('hero-juice.png'); background-size: cover; background-position: center center; background-repeat: no-repeat;
        }
        #home::before {
            content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            background: linear-gradient(to right, rgba(255,255,255,0.85) 0%, rgba(255,255,255,0.4) 50%, rgba(255,255,255,0.1) 100%);
            z-index: 1; pointer-events: none;
        }

        .hero-wrapper { display: flex; align-items: center; justify-content: flex-start; position: relative; z-index: 2; min-height: 450px; padding: 40px 0; }
        .hero-text { flex: 1; max-width: 600px; }
        .hero-text .sub-text { color: var(--logo-brown); font-weight: 600; margin-bottom: 10px; font-size: 1.3rem; letter-spacing: 1px; }
        .hero-title { font-size: 3.2rem; font-weight: 800; margin-bottom: 20px; line-height: 1.1; color: var(--text-dark); }
        .hero-title span { color: var(--logo-green); }
        .hero-desc { font-size: 1.15rem; margin-bottom: 35px; color: var(--text-dark); line-height: 1.7; font-weight: 400; }
        .hero-buttons { display: flex; gap: 15px; flex-wrap: wrap; }
        .btn-primary { background: var(--logo-green); color: white; box-shadow: 0 8px 20px rgba(141, 198, 63, 0.3); border-radius: 50px; padding: 14px 32px; font-weight: 600; }
        .btn-primary:hover { background: #7ab532; transform: translateY(-3px); }
        .btn-outline { background: white; color: var(--text-dark); border: 1px solid #c0c0c0; box-shadow: 0 5px 15px rgba(0,0,0,0.03); border-radius: 50px; padding: 14px 32px; font-weight: 600; }
        .btn-outline:hover { background: #f9f9f9; transform: translateY(-3px); }

        .reveal { opacity: 0; transform: translateY(40px); transition: all 0.9s cubic-bezier(0.2, 0.8, 0.2, 1); }
        .reveal.visible { opacity: 1; transform: translateY(0); }
        .delay-1 { transition-delay: 0.1s; } .delay-2 { transition-delay: 0.2s; } .delay-3 { transition-delay: 0.3s; }
        .delay-4 { transition-delay: 0.4s; } .delay-5 { transition-delay: 0.5s; } .delay-6 { transition-delay: 0.6s; } .delay-7 { transition-delay: 0.7s; }

        #profile .profile-content { display: grid; grid-template-columns: 1fr 1.2fr; gap: 50px; align-items: center; }
        .profile-image { width: 100%; max-width: 450px; height: 350px; background: #f0f0f0; border-radius: 20px; display: flex; align-items: center; justify-content: center; margin: 0 auto; overflow: hidden; position: relative; }
        .profile-image img { position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; }
        .profile-text h3 { font-size: 1.8rem; margin-bottom: 15px; color: var(--text-dark); }
        .profile-text p { color: var(--text-light); margin-bottom: 15px; line-height: 1.8; font-size: 0.95rem; }
        .profile-stats { display: flex; gap: 30px; margin-top: 30px; background: var(--bg-white); padding: 20px; border-radius: var(--border-radius); box-shadow: var(--box-shadow); }
        .stat-item { display: flex; align-items: center; gap: 10px; }
        .stat-icon { color: var(--logo-green); font-size: 1.5rem; }
        .stat-number { font-size: 1.5rem; font-weight: 700; color: var(--text-dark); }
        .stat-label { color: var(--text-light); font-size: 0.85rem; }

        .services-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
        .service-card { background: var(--bg-white); padding: 30px; border-radius: var(--border-radius); display: flex; align-items: flex-start; gap: 15px; box-shadow: var(--box-shadow); transition: var(--transition); }
        .service-card:hover { transform: translateY(-5px); box-shadow: 0 15px 30px rgba(0,0,0,0.08); }
        .service-icon { font-size: 1.5rem; color: white; background: var(--logo-green); padding: 12px; border-radius: 50%; min-width: 45px; height: 45px; display: flex; align-items: center; justify-content: center; }
        .service-card h3 { font-size: 1.1rem; margin-bottom: 5px; color: var(--text-dark); }
        .service-card p { color: var(--text-light); font-size: 0.9rem; line-height: 1.5; }

        .products-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 25px; }
        .product-card { background: var(--bg-white); border-radius: var(--border-radius); overflow: hidden; box-shadow: var(--box-shadow); transition: var(--transition); }
        .product-card:hover { transform: translateY(-5px); box-shadow: 0 15px 40px rgba(0,0,0,0.08); }
        .product-inner { display: flex; gap: 0; height: 100%; }
        .product-image { width: 45%; min-height: 250px; background: var(--bg-light); display: flex; align-items: center; justify-content: center; position: relative; overflow: hidden; flex-shrink: 0; }
        .product-image img { position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; }
        .product-info { padding: 20px 25px; flex: 1; display: flex; flex-direction: column; justify-content: center; text-align: left; }
        .product-category { display: inline-block; background: #f0f7e6; color: var(--logo-green); padding: 4px 12px; border-radius: 15px; font-size: 0.75rem; font-weight: 600; margin-bottom: 10px; width: fit-content; }
        .product-info h3 { font-size: 1.2rem; margin-bottom: 8px; color: var(--text-dark); }
        .product-info p { color: var(--text-light); font-size: 0.9rem; margin-bottom: 15px; line-height: 1.5; }
        .product-btn { width: 100%; padding: 10px; background: var(--logo-green); color: white; border: none; border-radius: 25px; font-weight: 600; transition: var(--transition); font-size: 0.9rem; display: block; text-align: center; text-decoration: none; }
        .product-btn:hover { background: #7ab532; }

        .gallery-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 30px; }
        .gallery-item { position: relative; border-radius: var(--border-radius); overflow: hidden; cursor: pointer; height: 200px; background: #f0f0f0; transition: var(--transition); }
        .gallery-item:nth-child(4), .gallery-item:nth-child(5) { grid-column: span 1.5; height: 220px; }
        .gallery-item img { position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; }
        .gallery-item:hover { transform: scale(1.02); }
        .btn-gallery-more { display: table; margin: 0 auto; background: var(--logo-green); color: white; padding: 10px 30px; border-radius: 25px; text-decoration: none; font-weight: 500; }

        .contact-content { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; }
        .contact-info h3 { font-size: 1.5rem; margin-bottom: 20px; color: var(--text-dark); }
        .contact-details { margin: 20px 0; }
        .contact-item { display: flex; align-items: center; gap: 15px; margin-bottom: 15px; }
        .contact-icon { width: 40px; height: 40px; background: #f0f7e6; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--logo-green); font-size: 1rem; }
        .contact-item strong { display: block; font-size: 0.9rem; color: var(--text-dark); }
        .contact-item p { color: var(--text-light); font-size: 0.9rem; margin: 0; }
        .contact-form { background: var(--bg-white); padding: 30px; border-radius: var(--border-radius); box-shadow: var(--box-shadow); border: 1px solid #f0f0f0; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 500; font-size: 0.9rem; color: var(--text-dark); }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 10px 15px; border: 1px solid var(--border-color); border-radius: var(--border-radius); font-family: 'Poppins', sans-serif; font-size: 0.95rem; transition: var(--transition); background: var(--bg-light); }
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus { outline: none; border-color: var(--logo-green); box-shadow: 0 0 0 3px rgba(141, 198, 63, 0.1); }
        .form-group textarea { height: 120px; resize: vertical; }
        .submit-btn { width: 100%; padding: 12px; background: var(--logo-green); color: white; border: none; border-radius: 25px; font-weight: 600; font-size: 1rem; cursor: pointer; transition: var(--transition); }
        .submit-btn:hover { background: #7ab532; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(141, 198, 63, 0.3); }

        .footer { background: var(--logo-green); color: white; padding: 30px 0; }
        .footer .container { display: flex; justify-content: space-between; align-items: center; }
        .footer-logo { font-weight: 700; font-size: 1.2rem; display: flex; align-items: center; gap: 10px; }
        .footer p { opacity: 0.9; font-size: 0.9rem; }
        .footer a { color: white; text-decoration: none; font-weight: 600; }
        .footer-socials a { margin-left: 15px; font-size: 1.2rem; }

        .btn { display: inline-block; padding: 12px 30px; border-radius: 50px; text-decoration: none; font-weight: 600; transition: var(--transition); border: none; cursor: pointer; font-size: 0.95rem; }

        .scroll-top { position: fixed; bottom: 80px; right: 20px; width: 45px; height: 45px; background: var(--logo-green); color: white; border: none; border-radius: 50%; cursor: pointer; display: none; align-items: center; justify-content: center; font-size: 1.2rem; box-shadow: 0 5px 20px rgba(0,0,0,0.1); transition: var(--transition); z-index: 998; }
        .scroll-top:hover { transform: translateY(-3px); background: #7ab532; }
        .scroll-top.show { display: flex; }

        #liveVisitorCounter { position: fixed; bottom: 20px; left: 20px; background: rgba(255,255,255,0.95); padding: 8px 12px; border-radius: 8px; z-index: 9999; font-family: 'Poppins', sans-serif; box-shadow: 0 2px 10px rgba(0,0,0,0.1); border: 1px solid #e0e0e0; display: flex; flex-direction: column; gap: 5px; backdrop-filter: blur(5px); font-size: 0.75rem; cursor: default; user-select: none; min-width: 100px; }
        #liveVisitorCounter .vc-row { display: flex; align-items: center; gap: 6px; }
        #liveVisitorCounter .vc-dot { width: 8px; height: 8px; background: #27ae60; border-radius: 50%; animation: vcPulse 1.5s infinite; }
        @keyframes vcPulse { 0%, 100% { box-shadow: 0 0 0 0 rgba(39,174,96,0.7); } 50% { box-shadow: 0 0 0 6px rgba(39,174,96,0); } }

        @media (max-width: 1024px) {
            .gallery-grid { grid-template-columns: repeat(2, 1fr); }
            .gallery-item:nth-child(4), .gallery-item:nth-child(5) { grid-column: span 1; height: 200px; }
        }
        @media (max-width: 992px) {
            .hero-title { font-size: 2.5rem; }
            .hero-text { max-width: 500px; }
            #profile .profile-content, .contact-content { grid-template-columns: 1fr; gap: 30px; }
            .profile-image { height: 300px; }
        }
        @media (max-width: 768px) {
            .hamburger { display: flex; }
            .nav-menu { position: fixed; top: var(--header-height); left: -100%; width: 100%; height: calc(100vh - var(--header-height)); background: var(--bg-white); flex-direction: column; padding: 20px; transition: var(--transition); box-shadow: var(--box-shadow); }
            .nav-menu.active { left: 0; }
            .nav-menu li { width: 100%; }
            .nav-menu li a { display: block; padding: 12px 20px; text-align: left; border-radius: 10px; }
            .hero-wrapper { justify-content: center; text-align: center; min-height: auto; padding: 20px 0; }
            .hero-text { max-width: 100%; padding: 20px; border-radius: 20px; }
            .hero-buttons { justify-content: center; }
            .hero-title { font-size: 2.2rem; }
            .services-grid, .products-grid { grid-template-columns: 1fr; }
            .product-inner { flex-direction: column; }
            .product-image { width: 100%; height: 200px; }
            .product-info { text-align: center; }
            .product-category { margin: 0 auto 10px auto; }
            .profile-stats { flex-direction: column; gap: 10px; align-items: flex-start; padding: 15px; }
            .footer .container { flex-direction: column; gap: 20px; text-align: center; }
        }
        @media (max-width: 480px) {
            .hero-title { font-size: 1.8rem; }
            .gallery-grid { grid-template-columns: 1fr; }
            .logo-img { height: 38px; }
        }
    </style>
</head>
<body>

    <header class="header" id="header">
        <nav class="navbar">
            <a href="#home" class="logo reveal delay-1">
                <img src="niagara.jpg" alt="Logo Niagara Fruit" class="logo-img" id="logoImage" onload="this.classList.add('logo-img-loaded')" onerror="this.style.display='block';"> 
                <div class="logo-text-wrap">
                    <span class="logo-text-green">Niagara</span>
                    <span class="logo-text-brown">Fruit</span>
                </div>
            </a>
            <ul class="nav-menu" id="navMenu">
                <li class="reveal delay-2"><a href="#home" class="active">Home</a></li>
                <li class="reveal delay-2"><a href="#profile">Profile</a></li>
                <li class="reveal delay-2"><a href="#service">Service</a></li>
                <li class="reveal delay-2"><a href="#product">Product</a></li>
                <li class="reveal delay-2"><a href="#gallery">Gallery</a></li>
                <li class="reveal delay-2"><a href="#contact">Contact Us</a></li>
                <li class="reveal delay-3"><a href="#contact" class="nav-cta-btn"><i class="fas fa-phone-alt"></i> Hubungi Kami</a></li>
            </ul>
            <div class="hamburger" id="hamburger">
                <span></span><span></span><span></span>
            </div>
        </nav>
    </header>

    <section class="section" id="home">
        <div class="container">
            <div class="hero-wrapper">
                <div class="hero-text">
                    <div class="sub-text reveal delay-4">Segarnya Buah Asli,</div>
                    <h1 class="hero-title reveal delay-5">Serunya <span>Niagara Fruit</span></h1>
                    <p class="hero-desc reveal delay-6">Kami menyediakan produk minuman buah segar berkualitas tinggi dengan harga terjangkau.<br>Mendukung usaha kecil menengah untuk Indonesia yang lebih maju.</p>
                    <div class="hero-buttons reveal delay-7">
                        <a href="#product" class="btn btn-primary"><i class="fas fa-shopping-cart"></i> Lihat Produk</a>
                        <a href="#contact" class="btn btn-outline"><i class="fas fa-phone-alt"></i> Hubungi Kami</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section" id="profile">
        <div class="container">
            <div class="profile-content">
                <div class="profile-image reveal delay-3">
                    <img id="profileImg" src="profile.jpg" alt="Profile UMKM" onerror="this.style.display='none'; this.parentElement.style.background='#f0f7e6'; this.parentElement.innerHTML='<i class=\'fas fa-store\' style=\'font-size:6rem; color:#8dc63f;\'></i>';">
                </div>
                <div class="profile-text reveal delay-4">
                    <span class="section-tag">Tentang Kami</span>
                    <h3>UMKM Mang Ucup Niagara Fruit</h3>
                    <p>Cerita di Balik Niagara Fruit. Niagara Fruit adalah UMKM minuman buah segar yang berkembang melalui kualitas produk dan kedekatan dengan pelanggan. Dikenal luas melalui sosok Bang Ucup, Niagara Fruit berhasil membangun identitas yang berbeda dari usaha jus buah pada umumnya.</p>
                    <p>Dengan pengalaman lebih dari 4 tahun, kami telah melayani ratusan pelanggan dan terus berkembang hingga saat ini.</p>
                    <div class="profile-stats reveal delay-5">
                        <div class="stat-item"><div class="stat-icon"><i class="fas fa-users"></i></div><div><div class="stat-number">500+</div><div class="stat-label">Pelanggan</div></div></div>
                        <div class="stat-item"><div class="stat-icon"><i class="fas fa-box"></i></div><div><div class="stat-number">100+</div><div class="stat-label">Produk</div></div></div>
                        <div class="stat-item"><div class="stat-icon"><i class="fas fa-calendar-alt"></i></div><div><div class="stat-number">4+</div><div class="stat-label">Tahun</div></div></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section" id="service">
        <div class="container">
            <span class="section-tag reveal delay-2" style="display:table; margin:0 auto 10px auto;">Layanan Kami</span>
            <h2 class="section-title reveal delay-3">Berbagai Layanan Unggulan Untuk Anda</h2>
            <div class="services-grid">
                <div class="service-card reveal delay-4"><div class="service-icon"><i class="fas fa-truck-fast"></i></div><div><h3>Pengiriman Cepat</h3><p>Kami menyediakan layanan pengiriman cepat ke seluruh Indonesia dengan jaminan ketepatan waktu.</p></div></div>
                <div class="service-card reveal delay-5"><div class="service-icon"><i class="fas fa-medal"></i></div><div><h3>Kualitas Terjamin</h3><p>Setiap produk kami melalui proses quality control yang ketat untuk memastikan kepuasan pelanggan.</p></div></div>
                <div class="service-card reveal delay-6"><div class="service-icon"><i class="fas fa-headset"></i></div><div><h3>Support 24/7</h3><p>Tim customer service kami siap membantu Anda kapan saja melalui berbagai saluran komunikasi.</p></div></div>
            </div>
        </div>
    </section>

    <section class="section" id="product">
        <div class="container">
            <span class="section-tag reveal delay-2" style="display:table; margin:0 auto 10px auto;">Produk Kami</span>
            <h2 class="section-title reveal delay-3">Produk Jus Buah Unggulan Kami</h2>
            <div class="products-grid">
                <div class="product-card reveal delay-4"><div class="product-inner"><div class="product-image"><img src="jus-special.jpg" alt="Jus Special" onerror="this.style.display='none'; this.parentElement.innerHTML='<i class=\'fas fa-glass-whiskey\' style=\'font-size: 4rem; color: #f39c12;\'></i>';"></div><div class="product-info"><span class="product-category">Jus Special</span><h3>Jus Special</h3><p>Jus spesial pilihan dengan kombinasi buah-buahan premium dan rasa yang istimewa.</p><a href="produk.html?kategori=special" class="product-btn"><i class="fas fa-shopping-cart"></i> Pilih Pesanan</a></div></div></div>
                <div class="product-card reveal delay-5"><div class="product-inner"><div class="product-image"><img src="jus-mix.jpg" alt="Jus Mix" onerror="this.style.display='none'; this.parentElement.innerHTML='<i class=\'fas fa-glass-whiskey\' style=\'font-size: 4rem; color: #8dc63f;\'></i>';"></div><div class="product-info"><span class="product-category">Jus Mix</span><h3>Jus Mix</h3><p>Perpaduan berbagai buah segar yang diblend sempurna menciptakan rasa unik.</p><a href="produk.html?kategori=mix" class="product-btn"><i class="fas fa-shopping-cart"></i> Pilih Pesanan</a></div></div></div>
                <div class="product-card reveal delay-6"><div class="product-inner"><div class="product-image"><img src="jus-regular.jpg" alt="Jus Regular" onerror="this.style.display='none'; this.parentElement.innerHTML='<i class=\'fas fa-glass-whiskey\' style=\'font-size: 4rem; color: #e74c3c;\'></i>';"></div><div class="product-info"><span class="product-category">Jus Regular</span><h3>Jus Regular</h3><p>Jus buah segar klasik dengan rasa autentik dan harga terjangkau.</p><a href="produk.html?kategori=regular" class="product-btn"><i class="fas fa-shopping-cart"></i> Pilih Pesanan</a></div></div></div>
            </div>
        </div>
    </section>

    <section class="section" id="gallery">
        <div class="container">
            <span class="section-tag reveal delay-2" style="display:table; margin:0 auto 10px auto;">Galeri</span>
            <h2 class="section-title reveal delay-3">Dokumentasi Kegiatan Usaha</h2>
            <div class="gallery-grid">
                <div class="gallery-item reveal delay-4"><img src="gallery1.jpg" alt="Galeri 1" onerror="this.style.display='none'; this.style.background='#e0e0e0'; this.innerHTML='<i class=\'fas fa-image\' style=\'font-size:3rem; color:#999;\'></i>';"></div>
                <div class="gallery-item reveal delay-4"><img src="gallery2.jpg" alt="Galeri 2" onerror="this.style.display='none'; this.style.background='#e0e0e0'; this.innerHTML='<i class=\'fas fa-image\' style=\'font-size:3rem; color:#999;\'></i>';"></div>
                <div class="gallery-item reveal delay-5"><img src="gallery3.jpg" alt="Galeri 3" onerror="this.style.display='none'; this.style.background='#e0e0e0'; this.innerHTML='<i class=\'fas fa-image\' style=\'font-size:3rem; color:#999;\'></i>';"></div>
                <div class="gallery-item reveal delay-5"><img src="gallery4.jpg" alt="Galeri 4" onerror="this.style.display='none'; this.style.background='#e0e0e0'; this.innerHTML='<i class=\'fas fa-image\' style=\'font-size:3rem; color:#999;\'></i>';"></div>
            </div>
            <a href="#gallery" class="btn-gallery-more reveal delay-7">Lihat Semua Galeri</a>
        </div>
    </section>

    <section class="section" id="contact">
        <div class="container">
            <div class="contact-content">
                <div class="contact-info reveal delay-3">
                    <span class="section-tag">Informasi Kontak</span>
                    <h3>Silakan hubungi kami melalui informasi di bawah ini</h3>
                    <p>atau isi form kontak untuk pertanyaan lebih lanjut.</p>
                    <div class="contact-details">
                        <div class="contact-item"><div class="contact-icon"><i class="fas fa-map-marker-alt"></i></div><div><strong>Alamat</strong><p>Jl. Dr. Muwardi I, RT.15/RW.3, Grogol, Kec. Grogol Petamburan, Kota Jakarta Barat, DKI Jakarta 11450</p></div></div>
                        <div class="contact-item"><div class="contact-icon"><i class="fas fa-phone"></i></div><div><strong>Telepon</strong><p>+62 852-1160-0274</p></div></div>
                        <div class="contact-item"><div class="contact-icon"><i class="fas fa-envelope"></i></div><div><strong>Email</strong><p>syuhadarobbani5@gmail.com</p></div></div>
                        <div class="contact-item"><div class="contact-icon"><i class="fas fa-clock"></i></div><div><strong>Jam Operasional</strong><p>Senin - Sabtu: 08:00 - 17:00 WIB</p></div></div>
                        <div class="contact-item reveal delay-4" style="flex-direction: column; align-items: flex-start;">
                            <div class="contact-icon" style="margin-bottom: 10px;"><i class="fas fa-map-location-dot"></i></div>
                            <div style="width: 100%; position: relative;">
                                <strong>Lokasi Kami (Google Maps)</strong>
                                <p style="margin-bottom: 10px;">Kunjungi toko kami di Niagara Fruit (Jus Ucup Baru)</p>
                                
                                <div style="border-radius: 15px; overflow: hidden; box-shadow: var(--box-shadow); border: 2px solid var(--bg-light); position: relative;">
                                    <!-- Peta Google Maps - RQPV+9PH, Jl. Dr. Muwardi I, RT.15/RW.3, Grogol, Jakarta Barat -->
                                    <iframe 
                                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3966.6942012345678!2d106.7891234!3d-6.1712345!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e69f669cda50fd9%3A0xf1656c36441d4feb!2sRQPV%2B9PH%2C%20Jl.%20Dr.%20Muwardi%20I%2C%20RT.15%2FRW.3%2C%20Grogol%2C%20Kec.%20Grogol%20Petamburan%2C%20Kota%20Jakarta%20Barat%2C%20Daerah%20Khusus%20Ibukota%20Jakarta%2011450!5e0!3m2!1sid!2sid!4v1700000000000!5m2!1sid!2sid"
                                        width="100%" 
                                        height="300" 
                                        style="border:0; width: 100%; height: 300px;" 
                                        allowfullscreen="" 
                                        loading="lazy" 
                                        referrerpolicy="no-referrer-when-downgrade">
                                    </iframe>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="contact-form reveal delay-4">
                    <h3 style="margin-bottom: 20px; color: var(--text-dark); font-size: 1.3rem;">Kirim Pesan</h3>
                    <form id="contactForm">
                        <div class="form-group"><label for="name">Nama Lengkap *</label><input type="text" id="name" name="name" placeholder="Masukkan nama lengkap Anda" required></div>
                        <div class="form-group"><label for="email">Email *</label><input type="email" id="email" name="email" placeholder="Masukkan alamat email Anda" required></div>
                        <div class="form-group"><label for="subject">Subjek *</label><select id="subject" name="subject" required><option value="">Pilih subjek pesan</option><option value="pertanyaan">Pertanyaan Umum</option><option value="pemesanan">Pemesanan Produk</option><option value="kerjasama">Kerjasama</option><option value="lainnya">Lainnya</option></select></div>
                        <div class="form-group"><label for="message">Pesan *</label><textarea id="message" name="message" placeholder="Tulis pesan Anda di sini..." required></textarea></div>
                        <button type="submit" class="submit-btn"><i class="fas fa-paper-plane"></i> Kirim Pesan</button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <div class="footer-logo"><i class="fas fa-seedling"></i> Niagara Fruit</div>
            <p>&copy; 2024 Niaga Fruit. Semua Hak Dilindungi.</p>
            <div class="footer-socials">
                <a href="https://www.instagram.com/anak_polos101" target="_blank" rel="noopener noreferrer"><i class="fab fa-instagram"></i></a>
                <a href="https://wa.me/6285211600274?text=assalamualaikum+mas+Syuhada+" target="_blank" rel="noopener noreferrer"><i class="fab fa-whatsapp"></i></a>
            </div>
        </div>
    </footer>

    <button class="scroll-top" id="scrollTop"><i class="fas fa-arrow-up"></i></button>

    <script>
        const sections = document.querySelectorAll('.section');
        const navLinks = document.querySelectorAll('.nav-menu li a');
        function updateActiveLink() {
            let current = '';
            sections.forEach(section => { const sectionTop = section.offsetTop; if (pageYOffset >= sectionTop - 200) { current = section.getAttribute('id'); } });
            navLinks.forEach(link => { link.classList.remove('active'); if (link.getAttribute('href') === `#${current}`) { link.classList.add('active'); } });
        }
        window.addEventListener('scroll', updateActiveLink);
        const hamburger = document.getElementById('hamburger');
        const navMenu = document.getElementById('navMenu');
        hamburger.addEventListener('click', () => { hamburger.classList.toggle('active'); navMenu.classList.toggle('active'); });
        navLinks.forEach(link => { link.addEventListener('click', () => { hamburger.classList.remove('active'); navMenu.classList.remove('active'); }); });
        const scrollTopBtn = document.getElementById('scrollTop');
        window.addEventListener('scroll', () => { if (window.pageYOffset > 500) { scrollTopBtn.classList.add('show'); } else { scrollTopBtn.classList.remove('show'); } });
        scrollTopBtn.addEventListener('click', () => { window.scrollTo({ top: 0, behavior: 'smooth' }); });
        const headerEl = document.getElementById('header');
        window.addEventListener('scroll', () => { if (window.pageYOffset > 50) { headerEl.classList.add('scrolled'); } else { headerEl.classList.remove('scrolled'); } });
        
        const contactForm = document.getElementById('contactForm');
        contactForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const name = document.getElementById('name').value;
            const email = document.getElementById('email').value;
            const subject = document.getElementById('subject').value;
            const message = document.getElementById('message').value;
            const mailtoLink = `mailto:syuhadarobbani5@gmail.com?subject=${encodeURIComponent(subject + ' - ' + name)}&body=${encodeURIComponent('Nama: ' + name + '\nEmail: ' + email + '\n\nPesan:\n' + message)}`;
            window.location.href = mailtoLink;
            alert('Pesan Anda berhasil dikirim!');
            contactForm.reset();
        });

        document.querySelectorAll('a[href^="#"]').forEach(anchor => { anchor.addEventListener('click', function(e) { e.preventDefault(); const target = document.querySelector(this.getAttribute('href')); if (target) { target.scrollIntoView({ behavior: 'smooth', block: 'start' }); } }); });
        console.log('🚀 UMKM Niaga Fruit Website Ready!');

        document.addEventListener("DOMContentLoaded", function() {
            const reveals = document.querySelectorAll(".reveal");
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => { if (entry.isIntersecting) { entry.target.classList.add("visible"); } });
            }, { threshold: 0.1 });
            reveals.forEach(el => { observer.observe(el); if (el.getBoundingClientRect().top < window.innerHeight) { el.classList.add("visible"); } });
        });
    </script>

    <script>
        (function() {
            const API_URL = '?visitor_api=1';
            const VISITOR_ID_KEY = 'umkm_vid';
            function getVisitorId() { let id = localStorage.getItem(VISITOR_ID_KEY); if (!id) { id = 'v_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9); localStorage.setItem(VISITOR_ID_KEY, id); } return id; }
            const visitorId = getVisitorId();
            const counter = document.createElement('div');
            counter.id = 'liveVisitorCounter';
            counter.innerHTML = '<div class="vc-row"><span class="vc-dot"></span><span id="vcOnline" style="font-weight:700;color:#27ae60;">...</span><span style="font-size:0.65rem;color:#666;">online</span></div><div class="vc-row"><span style="font-size:0.75rem;">👥</span><span id="vcTotal" style="font-weight:700;color:#e67e22;">...</span><span style="font-size:0.65rem;color:#666;">total</span></div>';
            document.body.appendChild(counter);
            function apiCall(action, data = {}) { const formData = new FormData(); formData.append('visitor_api', '1'); formData.append('action', action); formData.append('visitor_id', visitorId); for (const [key, value] of Object.entries(data)) { formData.append(key, value); } return fetch(window.location.pathname, { method: 'POST', body: formData }).then(res => res.json()).catch(() => ({ online: '?', total: '?' })); }
            function updateCounter() { apiCall('status').then(data => { document.getElementById('vcOnline').textContent = data.online || 0; document.getElementById('vcTotal').textContent = (data.total || 0).toLocaleString('id-ID'); }); }
            function registerVisitor() { apiCall('register').then(data => { console.log('✅ Visitor registered'); }); }
            function pingVisitor() { apiCall('ping'); }
            function unregisterVisitor() { const formData = new FormData(); formData.append('visitor_api', '1'); formData.append('action', 'unregister'); formData.append('visitor_id', visitorId); navigator.sendBeacon(window.location.pathname, formData); }
            registerVisitor(); updateCounter(); setInterval(updateCounter, 5000); setInterval(pingVisitor, 30000);
            window.addEventListener('beforeunload', unregisterVisitor);
            document.addEventListener('visibilitychange', () => { if (document.hidden) { unregisterVisitor(); } else { registerVisitor(); updateCounter(); } });
        })();
    </script>

</body>
</html>
