<?php
// ١. پەیوەندی بە داتابەیس
$conn = @mysqli_connect("localhost", "root", "", "beauty_salon");

if (!$conn) {
    die("<p style='color:red; text-align:center; font-weight:bold; margin-top: 50px;'>ناتوانرێت پەیوەندی بە داتابەیسەوە بکرێت.</p>");
}

// دروستکردنی فۆڵدەری uploads ئەگەر بوونی نەبێت
if (!file_exists('uploads')) {
    mkdir('uploads', 0777, true);
}

// دروستکردنی خشتەکان ئەگەر بوونیان نەبێت
@mysqli_query($conn, "CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(255) NOT NULL UNIQUE
)");

@mysqli_query($conn, "CREATE TABLE IF NOT EXISTS services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    category VARCHAR(255) DEFAULT 'گشتی',
    price VARCHAR(50) NOT NULL,
    old_price VARCHAR(50) DEFAULT NULL,
    discount_expiry DATETIME DEFAULT NULL,
    media_type VARCHAR(50) DEFAULT 'image',
    media_url TEXT DEFAULT NULL,
    is_vip INT DEFAULT 0
)");

// دروستکردنی خشتەی ڕیکلامەکان (بۆ وێنە و ڤیدیۆ)
@mysqli_query($conn, "CREATE TABLE IF NOT EXISTS ads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) DEFAULT NULL,
    media_type ENUM('image', 'video') NOT NULL,
    media_url TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// زیادکردنی بەشی نوێ (Category)
if (isset($_POST['add_category'])) {
    $cat_name = mysqli_real_escape_string($conn, $_POST['category_name']);
    if (!empty($cat_name)) {
        mysqli_query($conn, "INSERT IGNORE INTO categories (category_name) VALUES ('$cat_name')");
    }
    header("Location: admin.php");
    exit();
}

// سڕینەوەی بەش (Category)
if (isset($_GET['delete_cat'])) {
    $cat_id = (int)$_GET['delete_cat'];
    mysqli_query($conn, "DELETE FROM categories WHERE id=$cat_id");
    header("Location: admin.php");
    exit();
}

// زیادکردنی خزمەتگوزاری نوێ
if (isset($_POST['add_service'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $price = mysqli_real_escape_string($conn, $_POST['price']);
    $old_price = !empty($_POST['old_price']) ? mysqli_real_escape_string($conn, $_POST['old_price']) : null;
    $discount_expiry = !empty($_POST['discount_expiry']) ? mysqli_real_escape_string($conn, $_POST['discount_expiry']) : null;
    $is_vip = isset($_POST['is_vip']) ? 1 : 0;
    
    $media_url = "";
    if (isset($_FILES['media_file']) && $_FILES['media_file']['error'] == 0) {
        $target_dir = "uploads/";
        $file_name = time() . "_" . basename($_FILES["media_file"]["name"]);
        $target_file = $target_dir . $file_name;
        if (move_uploaded_file($_FILES["media_file"]["tmp_name"], $target_file)) {
            $media_url = $target_file;
        }
    }

    $sql = "INSERT INTO services (name, category, price, old_price, discount_expiry, media_type, media_url, is_vip) 
            VALUES ('$name', '$category', '$price', " . ($old_price ? "'$old_price'" : "NULL") . ", " . ($discount_expiry ? "'$discount_expiry'" : "NULL") . ", 'image', '$media_url', $is_vip)";
    mysqli_query($conn, $sql);
    header("Location: admin.php");
    exit();
}

// بڵاوکردنەوەی ڕیکلامی نوێ (وێنە یان ڤیدیۆ)
if (isset($_POST['add_ad'])) {
    $title = mysqli_real_escape_string($conn, $_POST['ad_title']);
    $media_type = mysqli_real_escape_string($conn, $_POST['ad_media_type']);
    $media_url = "";

    if (isset($_FILES['ad_file']) && $_FILES['ad_file']['error'] == 0) {
        $target_dir = "uploads/";
        $file_name = "ad_" . time() . "_" . basename($_FILES["ad_file"]["name"]);
        $target_file = $target_dir . $file_name;
        if (move_uploaded_file($_FILES["ad_file"]["tmp_name"], $target_file)) {
            $media_url = $target_file;
        }
    }

    if (!empty($media_url)) {
        mysqli_query($conn, "INSERT INTO ads (title, media_type, media_url) VALUES ('$title', '$media_type', '$media_url')");
    }
    header("Location: admin.php");
    exit();
}

// سڕینەوەی ڕیکلام
if (isset($_GET['delete_ad'])) {
    $ad_id = (int)$_GET['delete_ad'];
    mysqli_query($conn, "DELETE FROM ads WHERE id=$ad_id");
    header("Location: admin.php");
    exit();
}

// سڕینەوەی خزمەتگوزاری
if (isset($_GET['delete_service'])) {
    $id = (int)$_GET['delete_service'];
    mysqli_query($conn, "DELETE FROM services WHERE id=$id");
    header("Location: admin.php");
    exit();
}

// هێنانی لیستەکان بۆ پیشاندان
$categories_query = mysqli_query($conn, "SELECT * FROM categories ORDER BY id DESC");
$services_query = mysqli_query($conn, "SELECT * FROM services ORDER BY id DESC");
$ads_query = mysqli_query($conn, "SELECT * FROM ads ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پانێڵی بەڕێوەبردن</title>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Vazirmatn', sans-serif; background: #faf6f6; margin: 0; padding: 15px; }
        .nav-bar { background: #5c061e; padding: 12px; border-radius: 8px; display: flex; gap: 10px; margin-bottom: 20px; }
        .nav-bar a { color: white; text-decoration: none; padding: 8px 12px; background: rgba(255,255,255,0.1); border-radius: 6px; font-weight: bold; font-size: 0.9rem; }
        .card { background: white; padding: 20px; border-radius: 12px; border: 1px solid #ede0e2; margin-bottom: 20px; box-shadow: 0 4px 10px rgba(0,0,0,0.02); }
        h2 { color: #5c061e; font-size: 1.2rem; margin-top: 0; border-bottom: 2px solid #ede0e2; padding-bottom: 8px; }
        input, select, button { width: 100%; padding: 10px; margin-bottom: 12px; border: 1px solid #ede0e2; border-radius: 6px; box-sizing: border-box; font-family: inherit; }
        button { background: #5c061e; color: white; border: none; font-weight: bold; cursor: pointer; }
        .list-item { display: flex; justify-content: space-between; align-items: center; padding: 10px; border-bottom: 1px solid #ede0e2; font-size: 0.9rem; }
        .delete-btn { color: #b82333; text-decoration: none; font-weight: bold; }
        .thumb { width: 60px; height: 40px; object-fit: cover; border-radius: 4px; }
    </style>
</head>
<body>

    <div class="nav-bar">
        <a href="index.php">🏠 ماڵپەڕ</a>
        <a href="admin_notifications.php">🔔 نۆتیفیکەیشن و حسابات</a>
    </div>

    <div class="card">
        <h2>📺 بڵاوکردنەوەی ڕیکلامی نوێ (وێنە / ڤیدیۆ)</h2>
        <form method="POST" enctype="multipart/form-data">
            <input type="text" name="ad_title" placeholder="ناونیشانی ڕیکلامەکە (ئارەزوومەندانە)">
            
            <label>جۆری مێدیا:</label>
            <select name="ad_media_type" required>
                <option value="image">🖼️ وێنە (Image)</option>
                <option value="video">🎥 ڤیدیۆ (Video)</option>
            </select>

            <label>فایلی ڕیکلام (وێنە یان ڤیدیۆ هەڵبژێره):</label>
            <input type="file" name="ad_file" accept="image/*,video/*" required>

            <button type="submit" name="add_ad">📢 بڵاوکردنەوەی ڕیکلام</button>
        </form>

        <h3>لیستی ڕیکلامە چالاکەکان:</h3>
        <?php while($ad = mysqli_fetch_assoc($ads_query)) { ?>
            <div class="list-item">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <?php if($ad['media_type'] == 'image'): ?>
                        <img src="<?php echo $ad['media_url']; ?>" class="thumb">
                    <?php else: ?>
                        <span style="font-size: 1.5rem;">🎥</span>
                    <?php endif; ?>
                    <span><?php echo htmlspecialchars($ad['title'] ? $ad['title'] : 'ڕیکلامی بێ ناونیشان'); ?> (<?php echo $ad['media_type']; ?>)</span>
                </div>
                <a href="?delete_ad=<?php echo $ad['id']; ?>" class="delete-btn" onclick="return confirm('ڕیکلامەکە بسڕێتەوە؟')">🗑️ سڕینەوە</a>
            </div>
        <?php } ?>
    </div>

    <div class="card">
        <h2>✨ زیادکردنی بەشی نوێ (Category)</h2>
        <form method="POST">
            <input type="text" name="category_name" placeholder="بۆ نموونە: ماکیاژ، قژ، نۆک..." required>
            <button type="submit" name="add_category">➕ زیادکردنی بەش</button>
        </form>
        
        <h3>لیستی بەشە ئێستاییەکان:</h3>
        <?php 
        mysqli_data_seek($categories_query, 0);
        while($cat = mysqli_fetch_assoc($categories_query)) { ?>
            <div class="list-item">
                <span>📂 <?php echo htmlspecialchars($cat['category_name']); ?></span>
                <a href="?delete_cat=<?php echo $cat['id']; ?>" class="delete-btn" onclick="return confirm('بسڕێتەوە؟')">🗑️ سڕینەوە</a>
            </div>
        <?php } ?>
    </div>

    <div class="card">
        <h2>💅 زیادکردنی خزمەتگوزاری نوێ</h2>
        <form method="POST" enctype="multipart/form-data">
            <input type="text" name="name" placeholder="ناوی خزمەتگوزاری" required>
            
            <label>هەڵبژاردنی بەش:</label>
            <select name="category" required>
                <option value="گشتی">گشتی</option>
                <?php 
                mysqli_data_seek($categories_query, 0);
                while($cat = mysqli_fetch_assoc($categories_query)) {
                    echo "<option value='".htmlspecialchars($cat['category_name'])."'>".htmlspecialchars($cat['category_name'])."</option>";
                }
                ?>
            </select>

            <input type="text" name="price" placeholder="نرخ (بۆ نموونە: 25,000)" required>
            <input type="text" name="old_price" placeholder="نرخی کۆن پێش داشکاندن (ئارەزوومەندانە)">
            <input type="datetime-local" name="discount_expiry">
            <input type="file" name="media_file" accept="image/*" required>
            
            <label style="display:flex; align-items:center; gap:5px; margin-bottom:12px;">
                <input type="checkbox" name="is_vip" style="width:auto; margin:0;"> 👑 دیاریکردن وەک VIP
            </label>

            <button type="submit" name="add_service">🚀 بڵاوکردنەوەی خزمەتگوزاری</button>
        </form>
    </div>

</body>
</html>