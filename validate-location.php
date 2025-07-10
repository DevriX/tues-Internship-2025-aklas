<?php
require_once __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

function isValidLocation($locationInput) {
    $apiKey = $_ENV['GEOCODING_API'];
    $encodedLocation = urlencode($locationInput);

    $url = "https://maps.googleapis.com/maps/api/geocode/json?address=$encodedLocation&key=$apiKey";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    $responseData = json_decode($response, true);

    if ($responseData['status'] === 'OK' && count($responseData['results']) > 0) {
        return 1;
    } else {
        return 0;
    }
}
?>
