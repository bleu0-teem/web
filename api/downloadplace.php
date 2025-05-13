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

$data = @file_get_contents($url);

if ($data === false) {
    http_response_code(500);
    echo "Error: Failed to download the asset.";
    exit;
}

$filename = "place_" . $placeId;
if (!empty($version)) {
    $filename .= "_v" . $version;
}

file_put_contents($filename, $data);
echo "File downloaded and saved as '$filename'";
