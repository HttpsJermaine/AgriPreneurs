<?php
header("Content-Type: text/plain; charset=utf-8");

@set_time_limit(300);
@ini_set("memory_limit", "512M");

$baseDir = __DIR__ . "/../assets/ph-address/";
if (!is_dir($baseDir)) mkdir($baseDir, 0777, true);

$files = [
  "provinces.json" => "https://unpkg.com/philippine-location-json-for-geer@1.1.11/src/json/provinces.json",
  "city-mun.json"  => "https://unpkg.com/philippine-location-json-for-geer@1.1.11/src/json/city-mun.json",
  "barangays.json" => "https://unpkg.com/philippine-location-json-for-geer@1.1.11/src/json/barangays.json",
];

function fetchUrl($url) {
  $data = @file_get_contents($url);
  if ($data !== false) return $data;

  if (!function_exists("curl_init")) {
    throw new Exception("Enable allow_url_fopen OR enable PHP cURL.");
  }

  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 120,
  ]);
  $data = curl_exec($ch);
  $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  $err  = curl_error($ch);
  curl_close($ch);

  if ($data === false || $code >= 400) {
    throw new Exception("Download failed ($code): " . ($err ?: "HTTP error"));
  }
  return $data;
}

foreach ($files as $name => $url) {
  echo "Downloading $name...\n";
  $raw = fetchUrl($url);

  // Validate JSON before saving
  $decoded = json_decode($raw, true);
  if (!is_array($decoded)) {
    throw new Exception("$name downloaded but not valid JSON (maybe blocked or partial download).");
  }

  file_put_contents($baseDir . $name, $raw);
  echo "✅ Saved to assets/ph-address/$name\n";
}

echo "\nDONE ✅\n";
