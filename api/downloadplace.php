<?php
$placeId = isset($_GET['placeid']) ? $_GET['placeid'] : null;
$version = isset($_GET['version']) ? $_GET['version'] : null;

if (empty($placeId)) {
    http_response_code(400);
    echo "Error: 'placeid' parameter is required.";
    exit;
}

$url = "https://assetdelivery.roblox.com/v1/asset?id=" . urlencode($placeId);
if (!empty($version)) {
    $url .= "&version=" . urlencode($version);
}

$filename = "place_" . $placeId;
if (!empty($version)) {
    $filename .= "_v" . $version;
}
$filename .= ".rbxl";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
$data = curl_exec($ch);

if (curl_errno($ch)) {
    http_response_code(500);
    echo "cURL Error: " . curl_error($ch);
    curl_close($ch);
    exit;
}

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    http_response_code($httpCode);
    echo "Failed to download file. HTTP status code: $httpCode";
    exit;
}

file_put_contents($filename, $data);

echo "File downloaded and saved as '$filename'";
