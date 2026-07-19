<?php
session_start();
if (!isset($_SESSION['admin_id'])) { die("Access Denied"); }


$api_key = trim("4b9468426dmsh40546ba09a7b0fdp114344jsn240e5596faa5"); 
$api_host = "irctc1.p.rapidapi.com";
$target_url = "https://irctc1.p.rapidapi.com/api/v3/getLiveStation?fromStationCode=CAN&hours=8";

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => $target_url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "GET",
    CURLOPT_HTTPHEADER => [
        "x-rapidapi-host: " . $api_host,
        "x-rapidapi-key: " . $api_key
    ],
]);

$response = curl_exec($curl);
$err = curl_error($curl);
curl_close($curl);

echo "<pre>";
if ($err) {
    echo "cURL Error: " . $err;
} else {
    print_r(json_decode($response, true));
}
echo "</pre>";
?>