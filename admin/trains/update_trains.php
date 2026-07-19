<?php

set_time_limit(300); // 5 mins


require_once __DIR__ . '/../../db.php';


$apiKey = '4b9468426dmsh40546ba09a7b0fdp114344jsn240e5596faa5';
$fromStationCode = 'CAN'; 
$hours = '4'; 
$apiUrl = "https://irctc1.p.rapidapi.com/api/v1/liveStation?fromStationCode={$fromStationCode}&hours={$hours}";


function fetchTrainData($url, $apiKey) {
    $ch = curl_init();
 
    $headers = [
        'X-RapidAPI-Host: irctc1.p.rapidapi.com',
        'X-RapidAPI-Key: ' . $apiKey
    ];
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30); 
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        curl_close($ch);
        return ['error' => 'Failed to connect to the API server: ' . curl_error($ch)];
    }
    
    if ($httpCode != 200) {
        $apiError = json_decode($response, true);
        $errorMessage = $apiError['message'] ?? 'The API returned an error.';
        curl_close($ch);
        return ['error' => "API Error (Status Code: {$httpCode}): " . $errorMessage];
    }

    curl_close($ch);
    $data = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['error' => 'Failed to parse API response.'];
    }
    return $data;
}

function parseDelay($delayStr) {
    $delayStr = strtolower(trim($delayStr));
    if ($delayStr == 'on time' || $delayStr == 'na') {
        return 0;
    }
    preg_match('/(\d+)/', $delayStr, $matches);
    if (isset($matches[1])) {
        
        return (int)$matches[1];
    }
    return 0;
}

echo "Starting train data synchronization at " . date('Y-m-d H:i:s') . "\n";
$apiData = fetchTrainData($apiUrl, $apiKey);

if (isset($apiData['error'])) {
    echo "API Error: " . $apiData['error'] . "\n";
    exit;
}

if (isset($apiData['data']['train_between_stations']) && !empty($apiData['data']['train_between_stations'])) {
    
    $trainsFromApi = $apiData['data']['train_between_stations'];
    echo "Fetched " . count($trainsFromApi) . " trains from API.\n";

    $sql = "INSERT INTO trains (
                train_number, train_name, source_name, source_code,
                destination_name, destination_code, scheduled_arrival, scheduled_departure,
                platform, avg_delay_minutes, last_checked
            )
            VALUES (
                :tn, :t_name, :s_name, :s_code,
                :d_name, :d_code, :s_arr, :s_dep,
                :platform, :delay, CURDATE()
            )
            ON DUPLICATE KEY UPDATE
                train_name = VALUES(train_name),
                source_name = VALUES(source_name),
                source_code = VALUES(source_code),
                destination_name = VALUES(destination_name),
                destination_code = VALUES(destination_code),
                scheduled_arrival = VALUES(scheduled_arrival),
                scheduled_departure = VALUES(scheduled_departure),
                platform = VALUES(platform),
                avg_delay_minutes = VALUES(avg_delay_minutes),
                last_checked = CURDATE()";
                
    $stmt = $pdo->prepare($sql);
    $updateCount = 0;

    foreach ($trainsFromApi as $apiTrain) {
        $delay_minutes = parseDelay($apiTrain['delay'] ?? 'On Time');

        $stmt->execute([
            'tn' => $apiTrain['train_number'],
            't_name' => $apiTrain['train_name'],
            's_name' => $apiTrain['from_station_name'],
            's_code' => $apiTrain['from_station_code'],
            'd_name' => $apiTrain['to_station_name'],
            'd_code' => $apiTrain['to_station_code'],
            's_arr' => $apiTrain['platform_arrival_time'],
            's_dep' => $apiTrain['platform_departure_time'],
            'platform' => $apiTrain['platform_number'] ?? null,
            'delay' => $delay_minutes
        ]);
        $updateCount++;
    }
    
    echo "Synchronization complete. " . $updateCount . " records inserted or updated.\n";

} else {
    echo "No train data returned from API.\n";
}

echo "Finished at " . date('Y-m-d H:i:s') . "\n";
?>