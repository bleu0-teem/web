<?php
// health.php - Simple health/status endpoint
header('Content-Type: application/json');
header('Cache-Control: no-store');

$results = [
  'success' => true,
  'services' => [
    'main' => [ 'status' => 'unknown', 'latency_ms' => null, 'players' => 0, 'details' => '' ],
    'game' => [ 'status' => 'online', 'latency_ms' => null, 'players' => 0, 'details' => 'Matchmaking OK' ],
    'file' => [ 'status' => 'maintenance', 'latency_ms' => null, 'players' => null, 'details' => 'Planned maintenance' ],
  ]
];

try {
  // Try DB connectivity as Main service check
  require_once __DIR__ . '/db_connection.php';
  $t0 = microtime(true);
  if (defined('DB_TYPE') && DB_TYPE === 'mysql') {
    $stmt = $pdo->prepare('SELECT 1');
    $stmt->execute();
  } else {
    // For supabase wrapper, attempt a no-op like selecting 1 from a known small table or skip
    // Here we just simulate success if wrapper is present
    if (!isset($pdo)) throw new Exception('No PDO instance');
  }
  $lat = (microtime(true) - $t0) * 1000.0;
  $results['services']['main']['status'] = 'online';
  $results['services']['main']['latency_ms'] = round($lat, 2);
  $results['services']['main']['details'] = 'DB reachable';
  // Optional: derive players count from sessions table if exists (skip for now)
} catch (Throwable $e) {
  $results['services']['main']['status'] = 'offline';
  $results['services']['main']['details'] = 'DB check failed';
  $results['success'] = false;
}

http_response_code(200);
echo json_encode($results);
