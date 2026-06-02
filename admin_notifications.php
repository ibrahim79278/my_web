<?php
$conn = @mysqli_connect("localhost", "root", "", "beauty_salon");

$log_file = "admin_notifications.txt";
$status_file = "booking_status.txt";

// وەرگرتنی دۆخی حجزکردن (ئەگەر فایلەکە نەبوو، بە شێوازی بنەڕەتی ON دەبێت)
$booking_status = "ON";
if (file_exists($status_file)) {
    $booking_status = trim(file_get_contents($status_file));
}

// گۆڕینی دۆخی حجزکردن لە ڕێگەی دوگمەکەوە
if (isset($_POST['toggle_booking'])) {
    $new_status = ($booking_status === "ON") ? "OFF" : "ON";
    file_put_contents($status_file, $new_status);
    header("Location: admin_notifications.php");
    exit();
}

// ۱. مارککردنی هەموو وەک خوێندراوە لە داتابەیس
if (isset($_GET['mark_read']) && $conn) {
    mysqli_query($conn, "UPDATE admin_notifications SET status='read' WHERE status='unread'");
    header("Location: admin_notifications.php");
    exit();
}

// ۲. پاککردنەوەی نۆتیفیکەیشنەکانی فایلی دەقی
if (isset($_POST['clear_logs'])) {
    if (file_exists($log_file)) {
        file_put_contents($log_file, json_encode([]));
    }
    header("Location: admin_notifications.php");
    exit();
}

// ۳. پاککردنەوەی ئەرشیفی ناو داتابەیس
if (isset($_POST['clear_db_logs']) && $conn) {
    mysqli_query($conn, "TRUNCATE TABLE admin_notifications");
    header("Location: admin_notifications.php");
    exit();
}

// ٤. وەرگرتنی فلتەری بەروار (ئەگەر دیاری نەکرابوو، بەرواری ئەمڕۆ دادەنێت)
$input_date = isset($_GET['filter_date']) ? $_GET['filter_date'] : date('Y-m-d');
if (empty($input_date)) {
    $input_date = date('Y-m-d');
}

// گۆڕینی ژمارە عەرەبی/کوردییەکان بۆ ئینگلیزی ئەگەر هەبن
$eastern = array('٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩', '/');
$western = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '-');
$input_date = str_replace($eastern, $western, $input_date);

// ڕێکخستنی بەروار ڕێک بەو شێوازەی لە داتابەیسەکەتدا هەیە (d/m/Y) وەک 02/06/2026
$db_search_date = date('d/m/Y', strtotime($input_date));

// ٥. حسابات: ئەژمارکردنی ژمارەی حجزەکان و کۆی گشتی نرخ بۆ ئەو بەروارە لە خشتەی appointments
$total_appointments = 0;
$total_revenue = 0;
$appointments_list = [];

if ($conn) {
    // دروستکردنی خشتەی نۆتیفیکەیشن ئەگەر نەبێت
    @mysqli_query($conn, "CREATE TABLE IF NOT EXISTS admin_notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        message TEXT NOT NULL,
        status VARCHAR(20) DEFAULT 'unread',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $escaped_date = mysqli_real_escape_string($conn, $db_search_date);

    // کیوری دروست: بەکارهێنانی ستوونەکانی `date` و `time` ڕێک وەک ناو داتابەیسەکەت
    $query_string = "SELECT * FROM appointments WHERE `date` = '$escaped_date' ORDER BY `time` ASC";
    $appoint_query = mysqli_query($conn, $query_string);
    
    if ($appoint_query) {
        while ($row = mysqli_fetch_assoc($appoint_query)) {
            $appointments_list[] = $row;
            $total_appointments++;
            // لادانی نووسینی "د.ع" یان کۆما بۆ ئەوەی کۆبکرێتەوە
            $clean_price = (int)preg_replace('/[^0-9]/', '', $row['service_price']);
            $total_revenue += $clean_price;
        }
    }

    // ژمارەی نەخوێندراوەکانی نۆتیفیکەیشن
    $unread_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM admin_notifications WHERE status='unread'");
    $unread_count = $unread_query ? mysqli_fetch_assoc($unread_query)['total'] : 0;

    // هێنانی گشتی لۆگەکانی داتابەیس
    $db_result = mysqli_query($conn, "SELECT * FROM admin_notifications ORDER BY id DESC");
}

// خوێندنەوەی لیستی فایلی دەقی (JSON)
$notifications = [];
if (file_exists($log_file) && filesize($log_file) > 0) {
    $notifications = json_decode(file_get_contents($log_file), true);
    if (!is_array($notifications)) {
        $notifications = [];
    }
}
?>
<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پانێڵی کۆنترۆڵ و حجزەکان</title>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;700;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #5c061e;
            --primary-hover: #3a0211;
            --accent: #c5a059;
            --bg: #faf6f6;
            --card-bg: #ffffff;
            --text: #221114;
            --text-muted: #7a6669;
            --border: #ede0e2;
        }

        body {
            font-family: 'Vazirmatn', sans-serif;
            background-color: var(--bg);
            color: var(--text);
            margin: 0;
            padding: 15px;
            padding-bottom: 60px;
        }

        .app-bar {
            background: var(--primary);
            padding: 12px;
            border-radius: 12px;
            display: flex;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 20px;
            box-shadow: 0 4px 12px rgba(92, 6, 30, 0.15);
        }

        .app-bar a {
            flex: 1;
            text-align: center;
            color: white;
            text-decoration: none;
            font-weight: bold;
            font-size: 0.9rem;
            padding: 10px 5px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            transition: 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }

        .app-bar a:hover, .app-bar a.active {
            background: var(--accent);
            color: var(--primary);
        }

        .container {
            max-width: 100%;
            margin: 0 auto;
        }

        h1 {
            color: var(--primary);
            font-size: 1.4rem;
            text-align: center;
            margin-bottom: 20px;
            font-weight: 900;
        }

        .section-card {
            background: var(--card-bg);
            padding: 20px;
            border-radius: 12px;
            border: 1px solid var(--border);
            box-shadow: 0 4px 15px rgba(0,0,0,0.03);
            margin-bottom: 20px;
        }

        .section-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--primary);
            margin-top: 0;
            margin-bottom: 15px;
            border-bottom: 2px solid var(--border);
            padding-bottom: 8px;
        }

        .filter-form {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }

        .filter-form input[type="date"] {
            flex: 1;
            padding: 10px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-family: inherit;
            font-size: 1rem;
            text-align: center;
        }

        .btn-submit {
            background: var(--primary);
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            font-family: inherit;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 10px;
        }

        .stat-box {
            background: #fff8f9;
            border: 1px solid #f2d6dc;
            padding: 12px;
            border-radius: 10px;
            text-align: center;
        }

        .stat-box span {
            display: block;
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-bottom: 5px;
        }

        .stat-box strong {
            font-size: 1.1rem;
            color: var(--primary);
            font-weight: 700;
        }

        .booking-item {
            background: white;
            border: 1px solid var(--border);
            border-right: 5px solid var(--accent);
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 10px;
            font-size: 0.9rem;
        }

        .booking-header {
            display: flex;
            justify-content: space-between;
            font-weight: bold;
            margin-bottom: 8px;
            color: var(--primary);
        }

        .booking-body {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 5px;
            color: #444;
        }

        .badge-count {
            background: var(--accent);
            color: white;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .notification-card {
            background: #fff;
            border: 1px solid var(--border);
            border-right: 5px solid #d93838;
            padding: 15px;
            margin-bottom: 12px;
            border-radius: 8px;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            border-bottom: 1px dashed var(--border);
            padding-bottom: 8px;
            margin-bottom: 10px;
            font-weight: bold;
            font-size: 0.85rem;
            color: var(--primary);
        }

        .grid-info {
            display: grid;
            grid-template-columns: 1fr;
            gap: 6px;
            font-size: 0.9rem;
        }

        .info-item span { color: var(--text-muted); }

        .btn-clear {
            background: #b82333;
            color: white;
            border: none;
            padding: 10px;
            cursor: pointer;
            font-weight: bold;
            font-family: inherit;
            width: 100%;
            border-radius: 8px;
            margin-top: 10px;
        }

        .db-row {
            padding: 10px;
            border-bottom: 1px solid var(--border);
            font-size: 0.85rem;
        }
        .db-row:last-child { border: none; }

        .status-badge {
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: bold;
        }
        .unread-badge { background: #ffd2d2; color: #b80000; }
        .read-badge { background: #e2ede0; color: #276916; }

        .no-logs {
            text-align: center;
            color: var(--text-muted);
            padding: 20px;
            font-size: 0.9rem;
        }

        .control-box {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #fff;
            padding: 15px;
            border-radius: 12px;
            border: 1px solid var(--border);
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.03);
        }
        .btn-toggle {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            font-family: inherit;
            transition: 0.3s;
        }
        .btn-on { background: #276916; color: white; }
        .btn-off { background: #b82333; color: white; }
    </style>
</head>
<body>

<div class="container">

    <div class="app-bar">
        <a href="index.php">🏠 ماڵپەڕ</a>
        <a href="admin.php">⚙️ ئەدمین</a>
        <a href="admin_notifications.php" class="active">🔔 نۆتیفیکەیشن</a>
    </div>

    <h1>📋 ئەپڵیکەیشنی بەڕێوەبردنی حجزەکان</h1>

    <div class="control-box">
        <strong style="color: var(--primary); font-size: 1rem;">⚙️ باری وەرگرتنی حجزەکان لە ماڵپەڕ:</strong>
        <form method="POST">
            <?php if ($booking_status === "ON"): ?>
                <button type="submit" name="toggle_booking" class="btn-toggle btn-on">🟢 کراوەیە (ON)</button>
            <?php else: ?>
                <button type="submit" name="toggle_booking" class="btn-toggle btn-off">🔴 داخراوە (OFF)</button>
            <?php endif; ?>
        </form>
    </div>

    <div class="section-card">
        <div class="section-title">📊 فلتەر و حساباتی حجزەکان</div>
        
        <form method="GET" class="filter-form">
            <input type="date" name="filter_date" value="<?php echo htmlspecialchars($input_date); ?>">
            <button type="submit" class="btn-submit">بگەڕێ</button>
        </form>

        <div class="stats-grid">
            <div class="stat-box">
                <span>حجزەکانی ئەم ڕۆژە</span>
                <strong><?php echo $total_appointments; ?> حجز</strong>
            </div>
            <div class="stat-box">
                <span>کۆی گشتی داهات</span>
                <strong style="color: #276916;"><?php echo number_format($total_revenue); ?> د.ع</strong>
            </div>
        </div>

        <h3 style="font-size:0.95rem; color:var(--primary); margin-bottom:10px;">📋 لیستی وردەکاری حجزەکان:</h3>
        <?php if (empty($appointments_list)): ?>
            <div class="no-logs">ℹ️ هیچ حجزێک بۆ ئەم بەروارە تۆمار نەکراوە.</div>
        <?php else: ?>
            <?php foreach ($appointments_list as $app): ?>
                <div class="booking-item">
                    <div class="booking-header">
                        <span>👤 <?php echo htmlspecialchars($app['name']); ?></span>
                        <span>⏰ <?php echo htmlspecialchars($app['time']); ?></span>
                    </div>
                    <div class="booking-body">
                        <div><span>📞 تەلەفۆن:</span> <strong><?php echo htmlspecialchars($app['phone']); ?></strong></div>
                        <div><span>💅 خزمەت:</span> <strong><?php echo htmlspecialchars($app['service']); ?></strong></div>
                        <div style="grid-column: span 2;"><span>💰 نرخ:</span> <strong style="color:var(--accent);"><?php echo htmlspecialchars($app['service_price']); ?> د.ع</strong></div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="section-card">
        <div class="section-title" style="display:flex; justify-content:space-between; align-items:center;">
            <span>🚨 حجزە نوێیە دەستبەجێکان</span>
            <span class="badge-count" id="liveBadge">نەخوێندراوە: <?php echo $unread_count; ?></span>
        </div>

        <div style="margin-bottom: 15px; text-align: left;">
            <a href="?mark_read=1" style="color: var(--primary); text-decoration:none; font-size:0.85rem; font-weight:bold;">✓ مارککردنی هەموو وەک خوێندراوە</a>
        </div>

        <?php if (empty($notifications)): ?>
            <div class="no-logs">📭 چاوەڕێی حجزی نوێ دەکەین...</div>
        <?php else: ?>
            <?php foreach ($notifications as $notif): ?>
                <div class="notification-card">
                    <div class="card-header">
                        <span>🔔 حجزێکی نوێ هات</span>
                        <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: normal;"><?php echo htmlspecialchars($notif['created_at']); ?></span>
                    </div>
                    <div class="grid-info">
                        <div class="info-item"><span>👤 ناو:</span> <strong><?php echo htmlspecialchars($notif['name']); ?></strong></div>
                        <div class="info-item"><span>📞 تەلەفۆن:</span> <strong><?php echo htmlspecialchars($notif['phone']); ?></strong></div>
                        <div class="info-item"><span>💅 خزمەت:</span> <strong><?php echo htmlspecialchars($notif['service']); ?></strong></div>
                        <div class="info-item"><span>💰 نرخ:</span> <strong style="color:var(--accent);"><?php echo htmlspecialchars($notif['price']); ?> د.ع</strong></div>
                        <div class="info-item"><span>📅 ڕێکەوت و کات:</span> <strong><?php echo htmlspecialchars($notif['date']); ?> | <?php echo htmlspecialchars($notif['time']); ?></strong></div>
                    </div>
                </div>
            <?php endforeach; ?>

            <form method="POST">
                <button type="submit" name="clear_logs" class="btn-clear" onclick="return confirm('بسڕێتەوە؟')">🗑️ پاککردنەوەی لۆگی کاتی</button>
            </form>
        <?php endif; ?>
    </div>

    <div class="section-card">
        <div class="section-title">📦 ئەرشیفی گشتی داتابەیس</div>
        <?php if ($db_result && mysqli_num_rows($db_result) > 0): ?>
            <?php while($row = mysqli_fetch_assoc($db_result)){ 
                $status_badge = ($row['status'] == 'unread') ? 'unread-badge' : 'read-badge';
                $status_text = ($row['status'] == 'unread') ? 'نەخوێندراوە' : 'خوێندراوە';
            ?>
                <div class="db-row">
                    <div style="display:flex; justify-content:space-between; margin-bottom:5px;">
                        <strong>ID: <?php echo $row['id']; ?></strong>
                        <span class="status-badge <?php echo $status_badge; ?>"><?php echo $status_text; ?></span>
                    </div>
                    <div style="color:#444; white-space:pre-line; margin-bottom:5px; background:#f9f9f9; padding:8px; border-radius:6px;"><?php echo htmlspecialchars($row['message']); ?></div>
                    <div style="color:var(--text-muted); font-size:0.75rem; text-align:left;"><?php echo $row['created_at']; ?></div>
                </div>
            <?php } ?>
            <form method="POST">
                <button type="submit" name="clear_db_logs" class="btn-clear" style="background:#7a6669;" onclick="return confirm('ئەرشیف بسڕێتەوە؟')">🗑️ سڕینەوەی تەواوی ئەرشیف</button>
            </form>
        <?php else: ?>
            <div class="no-logs">ئەرشیف بەتاڵە.</div>
        <?php endif; ?>
    </div>

</div>

<audio id="alertSound" src="https://actions.google.com/sounds/v1/alarms/beep_short.ogg" preload="auto"></audio>

<script>
    let currentUnread = <?php echo $unread_count; ?>;

    setInterval(function() {
        fetch(window.location.href)
            .then(response => response.text())
            .then(html => {
                let parser = new DOMParser();
                let doc = parser.parseFromString(html, 'text/html');
                let badgeElement = doc.getElementById('liveBadge');
                
                if (badgeElement) {
                    let newCount = parseInt(badgeElement.innerText.replace(/[^0-9]/g, ''));
                    
                    if (newCount > currentUnread) {
                        let audio = document.getElementById('alertSound');
                        audio.play().catch(function(error) {
                            console.log("Audio play blocked by browser interaction rules.");
                        });
                        currentUnread = newCount;
                        
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    }
                }
            }).catch(err => console.error("Error fetching notifications:", err));
    }, 5000);
</script>

</body>
</html>