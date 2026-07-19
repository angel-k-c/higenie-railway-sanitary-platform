<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    die("Access Denied");
}

require_once __DIR__ . '/../../db.php';

//API Configuration (CAN)
$api_key = "4b9468426dmsh40546ba09a7b0fdp114344jsn240e5596faa5"; 
$api_url = "https://irctc1.p.rapidapi.com/api/v3/getLiveStation?fromStationCode=CAN&hours=12";

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => $api_url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "GET",
    CURLOPT_HTTPHEADER => [
        "x-rapidapi-host: irctc1.p.rapidapi.com",
		"x-rapidapi-key: 4b9468426dmsh40546ba09a7b0fdp114344jsn240e5596faa5"
    ],
]);

$response = curl_exec($curl);
$err = curl_error($curl);
curl_close($curl);

if ($err) {
    $_SESSION['api_msg'] = "cURL Error: " . $err;
    header("Location: live_trains.php");
    exit;
}

$data = json_decode($response, true);

if (isset($data['status']) && $data['status'] == 1 && isset($data['data'])) {
    $count = 0;
    
    $stmt = $pdo->prepare("
        INSERT IGNORE INTO trains 
        (train_number, train_name, source_name, source_code, destination_name, destination_code, scheduled_arrival, scheduled_departure, platform, avg_delay_minutes)
        VALUES (:tn, :tname, :src_name, :src_code, :dest_name, :dest_code, :arr, :dep, :plat, :delay)
    ");

    foreach ($data['data'] as $train) {
        $arrivalStr = substr($train['arrivalTime'], 0, 5);
        $departureStr = substr($train['departureTime'], 0, 5);
        $arrParts = explode(':', $arrivalStr);
        if ((int)$arrParts[0] >= 24) {
             $arrivalStr = str_pad(((int)$arrParts[0] % 24), 2, '0', STR_PAD_LEFT) . ':' . $arrParts[1];
        }
        $depParts = explode(':', $departureStr);
        if ((int)$depParts[0] >= 24) {
             $departureStr = str_pad(((int)$depParts[0] % 24), 2, '0', STR_PAD_LEFT) . ':' . $depParts[1];
        }

        $src_code  = $train['source_station_code'] ?? $train['source'] ?? 'UNK';
        $src_name  = $train['source_station_name'] ?? $src_code; 
        $dest_code = $train['destination_station_code'] ?? $train['destination'] ?? 'UNK';
        $dest_name = $train['destination_station_name'] ?? $dest_code;
        $platform  = $train['platform_number'] ?? $train['platform'] ?? 'TBD'; 

        $stmt->execute([
            ':tn'        => $train['trainNumber'],
            ':tname'     => $train['trainName'],
            ':src_name'  => $src_name,
            ':src_code'  => $src_code,
            ':dest_name' => $dest_name,
            ':dest_code' => $dest_code,
            ':arr'       => $arrivalStr,
            ':dep'       => $departureStr,
            ':plat'      => $platform,
            ':delay'     => $train['delay_in_minutes'] ?? 0 
        ]);
        
        if ($stmt->rowCount() > 0) {
            $count++;
        }
    }

    $_SESSION['api_msg'] = "Success! Fetched $count new trains for Kannur.";
} else {
    $_SESSION['api_msg'] = "API fetch failed. Check your quota or try again later.";
}

header("Location: live_trains.php");
exit;
?>