<?php
// ١. دیاریکردنی جۆری وەڵام وەک JSON
header('Content-Type: application/json; charset=utf-8');

// ٢. پەیوەندی بە داتابەیس
$conn = @mysqli_connect("localhost", "root", "", "beauty_salon");

if (!$conn) {
    echo json_encode([
        "status" => "error",
        "message" => "ناتوانرێت پەیوەندی بە داتابەیسەوە بکرێت: " . mysqli_connect_error()
    ]);
    exit;
}

// ٣. وەرگرتنی زانیارییەکان لە فۆرمەکەوە
$name          = isset($_POST['name']) ? mysqli_real_escape_string($conn, trim($_POST['name'])) : '';
$phone         = isset($_POST['phone']) ? mysqli_real_escape_string($conn, trim($_POST['phone'])) : '';
$date          = isset($_POST['date']) ? mysqli_real_escape_string($conn, trim($_POST['date'])) : '';
$time          = isset($_POST['time']) ? mysqli_real_escape_string($conn, trim($_POST['time'])) : '';
$service_name  = isset($_POST['service_name']) ? mysqli_real_escape_string($conn, trim($_POST['service_name'])) : '';
$service_price = isset($_POST['service_price']) ? mysqli_real_escape_string($conn, trim($_POST['service_price'])) : '';

// ٤. پشکینینی خانە بەتاڵەکان
if (empty($name) || empty($phone) || empty($date) || empty($time) || empty($service_name)) {
    echo json_encode([
        "status" => "error",
        "message" => "تکایە هەموو خانەکان بە دروستی پڕبکەرەوە!"
    ]);
    exit;
}

// ٥. داخڵکردنی زانیارییەکان بۆ ناو خشتەی سەرەکی حجزەکان
$query = "INSERT INTO appointments_new (client_name, client_phone, appointment_date, appointment_time, service_name, service_price) 
          VALUES ('$name', '$phone', '$date', '$time', '$service_name', '$service_price')";

if (mysqli_query($conn, $query)) {
    
    $current_time = date("Y-m-d H:i:s");

    // ٦. [زیادکراو] - پاشەکەوتکردن لە فایلی دەقی (admin_notifications.txt) بۆ پانێڵی زیندوو
    $log_file = "admin_notifications.txt";
    $notifications = [];
    
    if (file_exists($log_file) && filesize($log_file) > 0) {
        $notifications = json_decode(file_get_contents($log_file), true);
        if (!is_array($notifications)) {
            $notifications = [];
        }
    }
    
    // دروستکردنی ئۆبجێکتی نۆتیفیکەیشن بەو زمان و شێوازەی پانێڵەکەت دەیخوێنێتەوە
    $new_notif = [
        "name" => $name,
        "phone" => $phone,
        "service" => $service_name,
        "price" => $service_price,
        "date" => $date,
        "time" => $time,
        "created_at" => $current_time
    ];
    
    // زیادکردنی بۆ سەرەتای لیستەکە
    array_unshift($notifications, $new_notif);
    file_put_contents($log_file, json_encode($notifications, JSON_UNESCAPED_UNICODE));


    // ٧. [زیادکراو] - پاشەکەوتکردن لە خشتەی داتابەیسی ئەرشیف (admin_notifications)
    // بەپێی پێکهاتەی وێنەی image_052258.png، نامەکە بە دەق ڕێکدەخەین
    $db_message = "حجزەکا نوو هاتە تۆمارکرن:\n👤 کڕیار: $name\n📞 تەلەفۆن: $phone\n💅 خزمەتگوزاری: $service_name\n⏰ کات: $date ($time)";
    $db_message = mysqli_real_escape_string($conn, $db_message);
    
    $notif_query = "INSERT INTO admin_notifications (message, status, created_at) VALUES ('$db_message', 'unread', '$current_time')";
    mysqli_query($conn, $notif_query);


    // ناردنی وەڵامی سەرکەوتووبوون بۆ فۆڕمەکە
    echo json_encode([
        "status" => "success",
        "message" => "حجزەکە بە سەرکەوتوویی تۆمارکرا."
    ]);

} else {
    echo json_encode([
        "status" => "error",
        "message" => "هەڵەیەک ڕوویدا لە کاتی پاشەکەوتکردن: " . mysqli_error($conn)
    ]);
}

mysqli_close($conn);
?>