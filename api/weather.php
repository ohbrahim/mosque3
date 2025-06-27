<?php
/**
 * API لجلب حالة الطقس
 */
require_once '../config/config.php';

header('Content-Type: application/json; charset=utf-8');

$response = ['success' => false, 'data' => null, 'message' => ''];

try {
    $city = getSetting($db, 'weather_city', 'Riyadh');
    $apiKey = getSetting($db, 'weather_api_key', '');
    
    if (empty($apiKey)) {
        throw new Exception('مفتاح API للطقس غير مُعرَّف');
    }
    
    $url = "http://api.openweathermap.org/data/2.5/weather?q={$city}&appid={$apiKey}&units=metric&lang=ar";
    
    $context = stream_context_create([
        'http' => [
            'timeout' => 10
        ]
    ]);
    
    $weatherData = @file_get_contents($url, false, $context);
    
    if ($weatherData) {
        $data = json_decode($weatherData, true);
        
        if ($data['cod'] == 200) {
            $response['success'] = true;
            $response['data'] = [
                'city' => $data['name'],
                'temperature' => round($data['main']['temp']),
                'feels_like' => round($data['main']['feels_like']),
                'humidity' => $data['main']['humidity'],
                'description' => $data['weather'][0]['description'],
                'icon' => $data['weather'][0]['icon'],
                'wind_speed' => $data['wind']['speed']
            ];
        } else {
            throw new Exception('خطأ في جلب بيانات الطقس: ' . $data['message']);
        }
    } else {
        throw new Exception('فشل في الاتصال بخدمة الطقس');
    }
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
