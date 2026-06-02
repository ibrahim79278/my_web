<?php
// پەیوەندی بە داتابەیس
$conn = @mysqli_connect("localhost", "root", "", "beauty_salon");
if (!$conn) {
    die("<p style='color:red; text-align:center;'>ناتوانرێت پەیوەندی بە داتابەیسەوە بکرێت.</p>");
}

// خوێندنەوەی دۆخی حجزکردن لە فایلی دەقی
$status_file = "booking_status.txt";
$booking_status = "ON";
if (file_exists($status_file)) {
    $booking_status = trim(file_get_contents($status_file));
}

// وەرگرتنی فلتەری بەشەکان لە ڕێگەی گۆڕدراوی GET ئەگەر کلیک لەسەر دوگمەیەک کرابێت
$category_filter = isset($_GET['cat']) ? mysqli_real_escape_string($conn, $_GET['cat']) : '';

// هێنانی نوێترین ڕیکلامە وێنەییەکان بۆ سلایدەرەکە
$ads_images_query = mysqli_query($conn, "SELECT * FROM ads WHERE media_type='image' ORDER BY id DESC LIMIT 5");

// هێنانی نوێترین ڕیکلامی ڤیدیۆیی بۆ بەشی ڤیدیۆکە
$ads_video_query = mysqli_query($conn, "SELECT * FROM ads WHERE media_type='video' ORDER BY id DESC LIMIT 1");
$latest_video = mysqli_fetch_assoc($ads_video_query);
?>
<!DOCTYPE html>
<html lang="ku" dir="rtl" id="htmlPage">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title id="pageTitle">Va Beauty | سەنتەرێ جوانکاریێ</title>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;700;800&family=Readex+Pro:wght@300;400;600;700&family=Cinzel:wght@500;700;900&display=swap" rel="stylesheet">
    <!-- زیادکردنی کتێبخانەی ئایکۆنەکان بۆ پیشاندانی لۆگۆی کۆمپانیاکان -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            /* دیزاینی لۆکس: Luxury Burgundy & Royal Gold */
            --primary: #5c061e; 
            --primary-light: #fff0f3;
            --accent: #c5a059; 
            --accent-dark: #a37f3d;
            --bg-light: #faf6f6;
            --bg-card: #ffffff;
            --text-dark: #221114;
            --text-muted: #7a6669;
            --border-color: #ede0e2;
            --shadow: 0 10px 30px rgba(92, 6, 30, 0.05);
            --radius: 16px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            
            /* ڕەنگەکانی دوگمەی کاتژمێر لە مۆدی ڕۆژدا */
            --time-btn-bg: #f5eded;
            --time-btn-text: #221114;
        }

        [data-theme="dark"] {
            --primary: #ff4d79;
            --primary-light: #2d1218;
            --accent: #e6c280;
            --accent-dark: #c5a059;
            --bg-light: #120709;
            --bg-card: #1c0f12;
            --text-dark: #f5ebed;
            --text-muted: #b39da1;
            --border-color: #331f23;
            --shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            
            /* چاککردنی ڕەنگی دوگمەی کاتژمێر لە مۆدی شەودا بۆ ئەوەی ڕەش لەسەر ڕەش نەبێت */
            --time-btn-bg: #3d1f25;
            --time-btn-text: #ffffff;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Vazirmatn', 'Readex Pro', sans-serif;
            background-color: var(--bg-light);
            color: var(--text-dark);
            transition: var(--transition);
            line-height: 1.6;
            padding-bottom: 60px;
        }

        /* Header / Hero */
        header {
            background: linear-gradient(135deg, #3a0211 0%, #5c061e 100%);
            color: #fff;
            padding: 40px 20px;
            position: relative;
            border-bottom-left-radius: 40px;
            border-bottom-right-radius: 40px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            display: flex;
            justify-content: space-between;
            align-items: center;
            overflow: hidden;
            gap: 20px;
        }

        /* ڕێکخستنی ناوەڕاست بۆ لۆگۆکە */
        .header-center {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            z-index: 2;
            text-align: center;
        }

        .header-logo {
            font-family: 'Cinzel', serif;
            font-size: 2.8rem;
            font-weight: 900;
            letter-spacing: 4px;
            color: var(--accent);
            text-shadow: 0 4px 10px rgba(0,0,0,0.3);
            margin-bottom: 5px;
        }

        .header-tagline {
            font-size: 1.1rem;
            font-weight: 400;
            color: #f5ebed;
            opacity: 0.9;
            letter-spacing: 1px;
        }

        /* شاشەی ڕیکلامی یەکگرتوو - گواستراوەتەوە بۆ لای ڕاست و گەورەتر کراوە */
        .header-combined-ad {
            display: flex;
            gap: 10px;
            width: 560px; /* پانییەکەی زیاتر کرا بۆ ئەوەی گەورەتر بێت */
            height: 160px; /* بەرزییەکەی زیاتر کرا */
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            border: 2px solid var(--accent);
            z-index: 2;
            order: 1; /* هێنانە پێشەوەی بۆ لای ڕاست لە مۆدی RTL */
        }

        .ad-half-images, .ad-half-video {
            width: 50%;
            height: 100%;
            position: relative;
            overflow: hidden;
        }

        /* ستایلی گۆڕینی وێنەکان بە نەرمی */
        .ad-slider {
            width: 100%;
            height: 100%;
            position: relative;
        }

        .ad-slider img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            position: absolute;
            top: 0;
            left: 0;
            opacity: 0;
            transition: opacity 0.8s ease-in-out;
        }

        .ad-slider img.active {
            opacity: 1;
        }

        .ad-half-video video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* دوگمەی گۆڕینی مۆد لە ناو هێدەر */
        .theme-btn {
            position: absolute;
            top: 10px;
            left: 20px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            cursor: pointer;
            font-family: inherit;
            font-size: 0.8rem;
            transition: var(--transition);
            z-index: 10;
        }
        .theme-btn:hover {
            background: var(--accent);
            color: #3a0211;
            border-color: var(--accent);
        }

        /* 📱 گونجاندن بۆ شاشەی مۆبایل و تەلەفۆنەکان */
        @media (max-width: 768px) {
            header {
                flex-direction: column;
                text-align: center;
                padding-top: 50px;
                gap: 15px;
            }
            .header-center {
                position: relative;
                left: 0;
                transform: none;
                text-align: center;
                order: 1;
            }
            /* لەسەر تەلەفۆن دەکەوێتە ژێر ناوەکە و قەبارەی ڕێکدەخرێت */
            .header-combined-ad {
                order: 2;
                width: 100%;
                max-width: 340px;
                height: 110px;
                margin: 0 auto;
            }
            .theme-btn {
                left: 50%;
                transform: translateX(-50%);
                top: 10px;
            }
        }

        /* Categories Bar */
        .categories-container {
            max-width: 1200px;
            margin: -25px auto 30px auto;
            padding: 0 20px;
            position: relative;
            z-index: 10;
        }

        .categories-wrapper {
            display: flex;
            gap: 12px;
            overflow-x: auto;
            padding: 10px 5px;
            scrollbar-width: none;
        }
        .categories-wrapper::-webkit-scrollbar {
            display: none;
        }

        .cat-btn {
            background: var(--bg-card);
            color: var(--text-dark);
            padding: 12px 24px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 500;
            white-space: nowrap;
            box-shadow: 0 4px 15px rgba(0,0,0,0.03);
            border: 1px solid var(--border-color);
            transition: var(--transition);
        }
        .cat-btn:hover, .cat-btn.active {
            background: var(--primary);
            color: #fff;
            border-color: var(--primary);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(92, 6, 30, 0.15);
        }

        /* Main Grid */
        .main-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .section-title {
            font-size: 1.4rem;
            color: var(--primary);
            margin-bottom: 20px;
            position: relative;
            padding-right: 15px;
            font-weight: 700;
        }
        .section-title::before {
            content: '';
            position: absolute;
            right: 0;
            top: 4px;
            bottom: 4px;
            width: 5px;
            background: var(--accent);
            border-radius: 4px;
        }

        .grid-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 5px;
        }

        .card {
            background: var(--bg-card);
            border-radius: var(--radius);
            border: 1px solid var(--border-color);
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: var(--transition);
            display: flex;
            flex-direction: column;
            position: relative;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(92, 6, 30, 0.08);
        }

        .card-media {
            width: 100%;
            height: 240px;
            background: #eee;
            position: relative;
            overflow: hidden;
        }
        .card-media img, .card-media video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: var(--transition);
        }

        .vip-tag {
            position: absolute;
            top: 15px;
            right: 15px;
            background: linear-gradient(135deg, #a37f3d 0%, #c5a059 100%);
            color: #fff;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
            z-index: 2;
        }

        .card-content {
            padding: 20px;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }

        .card-category {
            font-size: 0.8rem;
            color: var(--accent-dark);
            text-transform: uppercase;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .card-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 12px;
        }

        .price-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            margin-top: auto;
        }

        .current-price {
            font-size: 1.3rem;
            font-weight: 800;
            color: var(--primary);
        }

        .old-price {
            font-size: 0.95rem;
            color: var(--text-muted);
            text-decoration: line-through;
        }

        .discount-badge {
            background: #e74c3c;
            color: #fff;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 700;
        }

        .book-btn {
            background: var(--primary);
            color: #fff;
            border: none;
            width: 100%;
            padding: 14px;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
            transition: var(--transition);
        }
        .book-btn:hover {
            background: #3a0211;
        }

        /* Modal Overlay */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(34, 17, 20, 0.6);
            backdrop-filter: blur(8px);
            z-index: 100;
            display: flex;
            justify-content: center;
            align-items: center;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
            padding: 20px;
        }
        .modal-overlay.active {
            opacity: 1;
            pointer-events: auto;
        }

        .modal-box {
            background: var(--bg-card);
            border-radius: 24px;
            width: 100%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            border: 1px solid var(--border-color);
            box-shadow: 0 20px 50px rgba(0,0,0,0.15);
            transform: scale(0.95);
            transition: transform 0.3s ease;
            position: relative;
        }
        .modal-overlay.active .modal-box {
            transform: scale(1);
        }

        .modal-header {
            padding: 20px 25px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-title {
            font-size: 1.3rem;
            color: var(--primary);
            font-weight: 700;
        }
        .close-modal {
            background: none;
            border: none;
            font-size: 1.8rem;
            color: var(--text-muted);
            cursor: pointer;
        }

        .modal-body {
            padding: 25px;
        }

        .wizard-step {
            display: none;
        }
        .wizard-step.active-step {
            display: block;
        }

        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--text-dark);
        }
        .form-control {
            width: 100%;
            padding: 14px;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            background: var(--bg-light);
            color: var(--text-dark);
            font-family: inherit;
            font-size: 1rem;
            transition: var(--transition);
        }
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
        }

        /* Time Slots Grid */
        .time-slots-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-top: 10px;
        }
        
        .btn-time {
            background-color: var(--time-btn-bg) !important;
            color: var(--time-btn-text) !important;
            border: 1px solid var(--border-color);
            padding: 12px;
            border-radius: 10px;
            font-family: inherit;
            cursor: pointer;
            font-weight: 600;
            transition: var(--transition);
            text-align: center;
        }
        .btn-time:hover {
            border-color: var(--accent);
            opacity: 0.9;
        }
        
        .btn-time.selected-time {
            background-color: var(--primary) !important;
            color: #ffffff !important;
            border-color: var(--primary) !important;
            box-shadow: 0 4px 12px rgba(92, 6, 30, 0.2);
        }

        .wizard-actions {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            margin-top: 30px;
        }
        .btn-next, .btn-submit {
            background: var(--primary);
            color: white;
            border: none;
            padding: 14px 28px;
            border-radius: 12px;
            cursor: pointer;
            font-family: inherit;
            font-weight: 600;
            font-size: 1rem;
            flex-grow: 1;
            transition: var(--transition);
        }
        .btn-back {
            background: transparent;
            border: 1px solid var(--border-color);
            color: var(--text-muted);
            padding: 14px 20px;
            border-radius: 12px;
            cursor: pointer;
            font-family: inherit;
        }

        .receipt-card {
            background: var(--primary-light);
            border: 1px dashed var(--primary);
            border-radius: var(--radius);
            padding: 20px;
            margin-bottom: 25px;
        }
        .receipt-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            font-size: 0.95rem;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            padding-bottom: 8px;
        }

        .no-data {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-muted);
            grid-column: 1 / -1;
        }

        /* 👑 گۆڕینی شێوازی دوگمەکان بۆ ئایکۆنی بازنەیی ڕێک و پرۆفیشناڵ */
        .custom-footer {
            background: linear-gradient(135deg, #3a0211 0%, #5c061e 100%);
            border-top: 3px solid var(--accent);
            padding: 30px 20px;
            margin-top: 50px;
            text-align: center;
        }

        .footer-buttons-container {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 18px;
            flex-wrap: wrap;
            max-width: 1200px;
            margin: 0 auto;
        }

        /* دیزاینکردنی ئایکۆنەکان بە بازنەیی هاوشێوەی کۆمپانیا جەیهانییەکان */
        .footer-social-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 50px; /* پانی جێگیر بۆ دروستکردنی بازنە */
            height: 50px; /* بەرزی جێگیر بۆ دروستکردنی بازنە */
            border-radius: 50%; /* بازنەیی تەواو */
            font-size: 1.4rem; /* گەورەکردنی قەبارەی ئایکۆنەکان */
            text-decoration: none;
            color: #ffffff;
            transition: var(--transition);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        /* ڕەنگە فەرمییەکانی هەر ئەپێک بەپێی ستاندارد */
        .btn-tiktok { background: #000000; color: #fff; }
        .btn-snapchat { background: #FFFC00; color: #000; }
        .btn-instagram { background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%); color: #fff; }
        .btn-whatsapp { background: #25D366; color: #fff; }
        .btn-location { background: #EA4335; color: #fff; }

        .footer-social-btn:hover {
            transform: translateY(-5px) scale(1.05);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.35);
        }

        /* 📋 ستایلی شریتی دەقە گۆڕاوەکە بۆ ناوی پڕۆگرامەر و تەلەفۆن */
        .programmer-ticker-container {
            margin-top: 25px;
            padding: 10px;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 8px;
            display: inline-block;
            min-width: 280px;
            border: 1px solid rgba(197, 160, 89, 0.3);
        }

        .programmer-ticker-text {
            font-size: 1rem;
            font-weight: bold;
            color: var(--accent);
            letter-spacing: 1px;
            transition: opacity 0.5s ease-in-out;
        }
    </style>
</head>
<body>

    <button class="theme-btn" id="modeToggleBtn" onclick="toggleTheme()">🌙 مۆدی شەڤ</button>

    <header>
        <!-- شاشەی ڕیکلامی یەکگرتوو -->
        <div class="header-combined-ad">
            <!-- نیوەی وێنەکان (داینامیکی لۆد دەکرێت) -->
            <div class="ad-half-images">
                <div class="ad-slider" id="adSlider">
                    <?php 
                    $is_first = true;
                    if (mysqli_num_rows($ads_images_query) > 0) {
                        while($ad_img = mysqli_fetch_assoc($ads_images_query)) { 
                            $active_class = $is_first ? 'active' : '';
                            $is_first = false;
                            ?>
                            <img src="<?php echo htmlspecialchars($ad_img['media_url']); ?>" class="<?php echo $active_class; ?>" alt="<?php echo htmlspecialchars($ad_img['title'] ? $ad_img['title'] : 'ڕیکلام'); ?>">
                            <?php 
                        }
                    } else {
                        echo '<img src="ad1.jpg" class="active" alt="ڕیکلامی گشتی">';
                    }
                    ?>
                </div>
            </div>
            <!-- نیوەی ڤیدیۆ (داینامیکی لۆد دەکرێت) -->
            <div class="ad-half-video">
                <?php if ($latest_video): ?>
                    <video src="<?php echo htmlspecialchars($latest_video['media_url']); ?>" autoplay muted loop playsinline></video>
                <?php else: ?>
                    <video src="ad_video.mp4" autoplay muted loop playsinline></video>
                <?php endif; ?>
            </div>
        </div>

        <!-- ناوەڕاستی هێدەرەکە -->
        <div class="header-center">
            <div class="header-logo">VA BEAUTY</div>
            <div class="header-tagline">ناڤونیشان زاخو </div>
        </div>
    </header>

    <div class="categories-container">
        <div class="categories-wrapper">
            <a href="index.php" class="cat-btn <?php echo $category_filter == '' ? 'active' : ''; ?>">هەمی پێکڤە</a>
            <?php
            $cats_query = mysqli_query($conn, "SELECT * FROM categories");
            while ($c = mysqli_fetch_assoc($cats_query)) {
                $slug = $c['category_name'];
                $active_class = ($category_filter === $slug) ? 'active' : '';
                echo "<a href='index.php?cat=" . urlencode($slug) . "' class='cat-btn $active_class'>" . htmlspecialchars($slug) . "</a>";
            }
            ?>
        </div>
    </div>

    <main class="main-container">
        <h2 class="section-title">
            <?php echo $category_filter != '' ? "خزمەتگوزاریێن بەشێ: " . htmlspecialchars($category_filter) : "هەمی خزمەتگورری "; ?>
        </h2>

        <div class="grid-container">
            <?php
            if ($category_filter != '') {
                $services_query = mysqli_query($conn, "SELECT * FROM services WHERE category='$category_filter' ORDER BY id DESC");
            } else {
                $services_query = mysqli_query($conn, "SELECT * FROM services ORDER BY id DESC");
            }

            if (mysqli_num_rows($services_query) > 0) {
                while ($row = mysqli_fetch_assoc($services_query)) {
                    ?>
                    <div class="card">
                        <?php if (isset($row['is_vip']) && $row['is_vip'] == 1): ?>
                            <div class="vip-tag">🌟 VIP</div>
                        <?php endif; ?>

                        <div class="card-media">
                            <?php if ($row['media_type'] === 'video'): ?>
                                <video src="<?php echo htmlspecialchars($row['media_url']); ?>" controls preload="metadata"></video>
                            <?php else: ?>
                                <img src="<?php echo htmlspecialchars($row['media_url']); ?>" alt="Service" loading="lazy">
                            <?php endif; ?>
                        </div>

                        <div class="card-content">
                            <span class="card-category"><?php echo htmlspecialchars($row['category']); ?></span>
                            <h3 class="card-title"><?php echo htmlspecialchars($row['name']); ?></h3>
                            
                            <div class="price-row">
                                <span class="current-price"><?php echo htmlspecialchars($row['price']); ?> د.ع</span>
                                <?php if (!empty($row['old_price'])): ?>
                                    <span class="old-price"><?php echo htmlspecialchars($row['old_price']); ?> د.ع</span>
                                    <span class="discount-badge">داشکاندن!</span>
                                <?php endif; ?>
                            </div>

                            <!-- پشکنینی ئەوەی ئایا حجزکردن چالاکە یان نا -->
                            <?php if ($booking_status === "ON"): ?>
                                <button class="book-btn" onclick="openBookingModal('<?php echo htmlspecialchars(addslashes($row['name'])); ?>', '<?php echo htmlspecialchars(addslashes($row['price'])); ?>')">
                                    📅 حجز بکە (بەروارێ دیار بکە )
                                </button>
                            <?php else: ?>
                                <button class="book-btn" style="background: #7a6669; cursor: not-allowed;" disabled>
                                    🔒 حجز کرن نوکە ڕاگیرتیە (OFF)
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php
                }
            } else {
                ?>
                <div class="no-data">
                    <p>چو خزمەتگوزاری د ئۆفەرا ڤێ بەشێ دا نینن نوکە.</p>
                </div>
                <?php
            }
            ?>
        </div>
    </main>

    <div class="modal-overlay" id="bookingModalOverlay">
        <div class="modal-box">
            
            <div class="modal-header">
                <span class="modal-title" id="modalServiceTitle">ناونیشانی کارەکە</span>
                <button class="close-modal" onclick="closeBookingModal()">&times;</button>
            </div>

            <span id="modalServiceName" style="display:none;"></span>
            <span id="modalServicePrice" style="display:none;"></span>

            <div class="modal-body">
                <form id="appointmentForm" onsubmit="event.preventDefault();">
                    
                    <div class="wizard-step active-step" id="stepCustomerInfo">
                        <div class="form-group">
                            <label>ناڤێ تە یێ سیانی (👤):</label>
                            <input type="text" id="clientName" class="form-control" placeholder="ناڤێ تە" required>
                        </div>
                        <div class="form-group">
                            <label>ژمارا تەلەفۆنێ (📞):</label>
                            <input type="tel" id="clientPhone" class="form-control" placeholder="0750XXXXXXX" required>
                        </div>
                        <div class="wizard-actions">
                            <button type="button" class="btn-next" onclick="validateStep1()">بەردەوامبە بۆ دیاریکرنا دەمی ➔</button>
                        </div>
                    </div>

                    <div class="wizard-step" id="stepDateTime">
                        <div class="form-group">
                            <label>بەروار و ڕۆژا حجزێ هەلبژێره (📅):</label>
                            <input type="date" id="appointmentDate" class="form-control" required onchange="onDateChanged()">
                        </div>
                        
                        <div class="form-group">
                            <label>دەمژمێرێن بەردەست (⏰):</label>
                            <input type="hidden" id="hiddenSelectedTime" value="">
                            
                            <div class="time-slots-grid" id="timeSlotsContainer">
                            </div>
                        </div>

                        <div class="wizard-actions">
                            <button type="button" class="btn-back" onclick="goToStep('stepCustomerInfo')">گۆڕین</button>
                            <button type="button" class="btn-submit" onclick="submitAppointment()">تۆمارکرنا حجزێ ✔</button>
                        </div>
                    </div>

                    <div class="wizard-step" id="stepReport">
                        <div style="text-align: center; margin-bottom: 20px;">
                            <h3 style="color: #27ae60; font-weight:700;"> ب سەرکەفتیانە حجز کر!</h3>
                        </div>

                        <div class="receipt-card">
                            <div class="receipt-row"><span class="receipt-label">👤 ناڤێ کڕیاری:</span><span class="receipt-value" id="repName">-</span></div>
                            <div class="receipt-row"><span class="receipt-label">📞 تەلەفون:</span><span class="receipt-value" id="repPhone">-</span></div>
                            <div class="receipt-row"><span class="receipt-label">💅 خزمەتگوزاری:</span><span class="receipt-value" id="repService">-</span></div>
                            <div class="receipt-row"><span class="receipt-label">📅 بەروار:</span><span class="receipt-value" id="repDate">-</span></div>
                            <div class="receipt-row"><span class="receipt-label">⏰ دەمژمێر:</span><span class="receipt-value" id="repTime">-</span></div>
                            <div class="receipt-row"><span class="receipt-label">💰 بها:</span><span class="receipt-value" id="repPrice">-</span></div>
                        </div>

                        <div class="wizard-actions">
                            <button type="button" class="btn-next" style="background:#27ae60;" onclick="closeBookingModal()">داخستن</button>
                        </div>
                    </div>

                </form>
            </div>
        </div>
    </div>

    <!-- 📥 بەشی گۆڕدراوی ئایکۆنەکانی فووتەر بۆ شێوازی بازنەیی فەرمی کۆمپانیاکان -->
    <footer class="custom-footer">
        <div class="footer-buttons-container">
            <a href="#" target="_blank" class="footer-social-btn btn-tiktok" title="Tik Tok"><i class="fab fa-tiktok"></i></a>
            <a href="#" target="_blank" class="footer-social-btn btn-snapchat" title="Snapchat"><i class="fab fa-snapchat-ghost"></i></a>
            <a href="#" target="_blank" class="footer-social-btn btn-instagram" title="Instagram"><i class="fab fa-instagram"></i></a>
            <a href="https://wa.me/0750xxxxxxx" target="_blank" class="footer-social-btn btn-whatsapp" title="WhatsApp"><i class="fab fa-whatsapp"></i></a>
            <a href="#" target="_blank" class="footer-social-btn btn-location" title="Location"><i class="fas fa-map-marker-alt"></i></a>
        </div>

        <div class="programmer-ticker-container">
            <span class="programmer-ticker-text" id="tickerText">Programmer. Ibrahim Tawfiq</span>
        </div>
    </footer>

    <script>
        // کودی سکریپت بۆ گۆڕینی دەقی ناو شریتەکە لەنێوان ناو و ژمارەی مۆبایل لە ١٠ چرکەدا
        document.addEventListener("DOMContentLoaded", function() {
            const ticker = document.getElementById('tickerText');
            let showName = true;

            setInterval(() => {
                ticker.style.opacity = 0; 
                setTimeout(() => {
                    if (showName) {
                        ticker.innerText = "07507927893";
                    } else {
                        ticker.innerText = "Programmer. Ibrahim Tawfiq";
                    }
                    showName = !showName;
                    ticker.style.opacity = 1; 
                }, 500); 
            }, 10000); 
        });

        // کودی سکریپت بۆ گۆڕینی خۆکاری وێنەکانی ڕیکلامەکە لە ٣ چرکەدا
        document.addEventListener("DOMContentLoaded", function() {
            const images = document.querySelectorAll('#adSlider img');
            let currentIndex = 0;

            if(images.length > 0) {
                setInterval(() => {
                    images[currentIndex].classList.remove('active');
                    currentIndex = (currentIndex + 1) % images.length;
                    images[currentIndex].classList.add('active');
                }, 3000);
            }
        });

        function openBookingModal(serviceName, servicePrice) {
            document.getElementById("modalServiceTitle").innerText = "حجزکرنا: " + serviceName;
            document.getElementById("modalServiceName").innerText = serviceName;
            document.getElementById("modalServicePrice").innerText = servicePrice;
            
            goToStep('stepCustomerInfo');
            document.getElementById("bookingModalOverlay").classList.add("active");

            const today = new Date().toISOString().split('T')[0];
            const dateInput = document.getElementById("appointmentDate");
            dateInput.min = today;
            dateInput.value = today;
            
            onDateChanged();
        }

        function closeBookingModal() {
            document.getElementById("bookingModalOverlay").classList.remove("active");
        }

        function goToStep(stepId) {
            document.querySelectorAll('.wizard-step').forEach(step => {
                step.classList.remove('active-step');
            });
            document.getElementById(stepId).classList.add('active-step');
        }

        function validateStep1() {
            const name = document.getElementById("clientName").value.trim();
            const phone = document.getElementById("clientPhone").value.trim();
            if(!name || !phone) {
                alert("هێڤیە ناڤ و ژمارا مۆبایل  بنڤیسە!");
                return;
            }
            goToStep('stepDateTime');
        }

        function onDateChanged() {
            const dateValue = document.getElementById("appointmentDate").value;
            const container = document.getElementById("timeSlotsContainer");
            container.innerHTML = ""; 
            document.getElementById("hiddenSelectedTime").value = ""; 

            if(!dateValue) return;

            const workingHours = [
                "09:00 AM", "10:00 AM", "11:00 AM", 
                "12:00 PM", "01:00 PM", "02:00 PM", 
                "03:00 PM", "04:00 PM", "05:00 PM"
            ];

            workingHours.forEach(hour => {
                const btn = document.createElement("button");
                btn.type = "button";
                btn.className = "btn-time";
                btn.innerText = hour;
                
                btn.onclick = function() {
                    document.querySelectorAll('.btn-time').forEach(b => b.classList.remove('selected-time'));
                    btn.classList.add('selected-time');
                    document.getElementById("hiddenSelectedTime").value = hour; 
                };

                container.appendChild(btn);
            });
        }

        function submitAppointment() {
            const name = document.getElementById("clientName").value.trim();
            const phone = document.getElementById("clientPhone").value.trim();
            const date = document.getElementById("appointmentDate").value;
            const time = document.getElementById("hiddenSelectedTime").value;
            const service = document.getElementById("modalServiceName").innerText;
            const price = document.getElementById("modalServicePrice").innerText;

            if(!time) {
                alert("هێڤیە دەمژمیر بو حجزئا خو دەستنیشان بکە!");
                return;
            }

            const formData = new FormData();
            formData.append("name", name);
            formData.append("phone", phone);
            formData.append("date", date);
            formData.append("time", time);
            formData.append("service_name", service);
            formData.append("service_price", price);

            fetch("save_appointment.php", {
                method: "POST",
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if(data.status === "success") {
                    document.getElementById("repName").innerText = name;
                    document.getElementById("repPhone").innerText = phone;
                    document.getElementById("repService").innerText = service;
                    document.getElementById("repDate").innerText = date;
                    document.getElementById("repTime").innerText = time;
                    document.getElementById("repPrice").innerText = price + " د.ع";
                    
                    goToStep('stepReport');
                    document.getElementById("appointmentForm").reset();
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error("Error:", error);
                alert("ئاریشەیەک دروست بویە  ل دەمی ناردنا حجز!");
            });
        }

        function toggleTheme() {
            const body = document.body;
            const btn = document.getElementById("modeToggleBtn");
            if (body.getAttribute("data-theme") === "dark") {
                body.setAttribute("data-theme", "light");
                btn.innerText = "🌙 مۆدی شەو";
            } else {
                body.setAttribute("data-theme", "dark");
                btn.innerText = "☀️ مۆدی ڕۆژ";
            }
        }
    </script>
</body>
</html>