<?php
// الاتصال بقاعدة البيانات
$pdo = new PDO("mysql:host=localhost;dbname=mosque_management;charset=utf8", "root", "");

// العدد الذي تريد أن يبدأ منه العداد
$base_visits = 84678; // (84831 - 153)

// جلب عدد السجلات من جدول الزوار
$stmt = $pdo->query("SELECT COUNT(*) FROM visitors");
$real_visits = $stmt->fetchColumn();

// مجموع الزيارات المعروضة
$total_visits = $base_visits + $real_visits;

// عرض العدد
echo "عدد الزوار: $total_visits";
?>
