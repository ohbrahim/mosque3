<?php
/**
 * API لجلب أوقات الصلاة
 */
require_once '../config/config.php';

header('Content-Type: application/json; charset=utf-8');

$response = ['success' => false, 'data' => null, 'message' => ''];

try {
    $city = getSetting($db, 'prayer_city', 'Riyadh');
    $country = getSetting($db, 'prayer_country', 'SA');
    
    // محاولة جلب أوقات الصلاة من قاعدة البيانات أولاً
    $today = date('Y-m-d');
    $cachedTimes = $db->fetchOne("SELECT * FROM prayer_times WHERE city = ? AND prayer_date = ?", [$city, $today]);
    
    if ($cachedTimes) {
        $response['success'] = true;
        $response['data'] = [
            'city' => $cachedTimes['city'],
            'date' => $cachedTimes['prayer_date'],
            'fajr' => $cachedTimes['fajr'],
            'sunrise' => $cachedTimes['sunrise'],
            'dhuhr' => $cachedTimes['dhuhr'],
            'asr' => $cachedTimes['asr'],
            'maghrib' => $cachedTimes['maghrib'],
            'isha' => $cachedTimes['isha']
        ];
    } else {
        // جلب أوقات الصلاة من API خارجي
        $url = "http://api.aladhan.com/v1/timingsByCity?city={$city}&country={$country}&method=4";
        
        $context = stream_context_create([
            'http' => [
                'timeout' => 10
            ]
        ]);
        
        $apiResponse = @file_get_contents($url, false, $context);
        
        if ($apiResponse) {
            $data = json_decode($apiResponse, true);
            
            if ($data['code'] == 200) {
                $timings = $data['data']['timings'];
                
                // حفظ أوقات الصلاة في قاعدة البيانات
                $prayerData = [
                    'city' => $city,
                    'prayer_date' => $today,
                    'fajr' => $timings['Fajr'],
                    'sunrise' => $timings['Sunrise'],
                    'dhuhr' => $timings['Dhuhr'],
                    'asr' => $timings['Asr'],
                    'maghrib' => $timings['Maghrib'],
                    'isha' => $timings['Isha']
                ];
                
                $db->insert('prayer_times', $prayerData);
                
                $response['success'] = true;
                $response['data'] = $prayerData;
            } else {
                throw new Exception('خطأ في جلب أوقات الصلاة من الخدمة');
            }
        } else {
            throw new Exception('فشل في الاتصال بخدمة أوقات الصلاة');
        }
    }
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    
    // في حالة الخطأ، استخدم أوقات افتراضية
    $response['data'] = [
        'city' => $city,
        'date' => date('Y-m-d'),
        'fajr' => '05:30',
        'sunrise' => '06:45',
        'dhuhr' => '12:15',
        'asr' => '15:30',
        'maghrib' => '18:00',
        'isha' => '19:30'
    ];
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
