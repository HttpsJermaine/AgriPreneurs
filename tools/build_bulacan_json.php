<?php
// tools/build_bulacan_json.php
// Generates: assets/ph-address/bulacan.json
// Output format:
// { "Bulacan": { "Calumpit": ["Brgy1",...], "Malolos": [...] ... } }

header("Content-Type: text/plain; charset=utf-8");

function readJson($path) {
  if (!file_exists($path)) {
    throw new Exception("Missing file: " . $path);
  }
  $raw = file_get_contents($path);
  $data = json_decode($raw, true);
  if (!is_array($data)) {
    throw new Exception("Invalid JSON: " . $path);
  }
  return $data;
}

function pick($row, $keys, $default = "") {
  foreach ($keys as $k) {
    if (isset($row[$k]) && $row[$k] !== "") return $row[$k];
  }
  return $default;
}

try {
  $base = __DIR__ . "/../assets/ph-address/";

  $provinces = readJson($base . "provinces.json");
  $citymun   = readJson($base . "city-mun.json");
  $barangays = readJson($base . "barangays.json");

  // 1) Find Bulacan province code by name
  $bulacanProvCode = null;
  foreach ($provinces as $p) {
    $name = pick($p, ["name", "prov_name", "province_name"]);
    if (mb_strtolower(trim($name)) === "bulacan") {
      $bulacanProvCode = pick($p, ["prov_code", "province_code"]);
      break;
    }
  }
  if (!$bulacanProvCode) {
    throw new Exception("Could not find Bulacan in provinces.json");
  }

  // 2) Build municipality list for Bulacan: mun_code => mun_name
  $muns = [];
  foreach ($citymun as $c) {
    $prov_code = pick($c, ["prov_code", "province_code"]);
    if ((string)$prov_code !== (string)$bulacanProvCode) continue;

    $mun_code = pick($c, ["mun_code", "citymun_code", "city_code", "psgc_code"]);
    $mun_name = pick($c, ["name", "citymun_name", "mun_name", "city_name"]);
    if ($mun_code && $mun_name) {
      $muns[(string)$mun_code] = $mun_name;
    }
  }

  if (!$muns) {
    throw new Exception("No cities/municipalities found for Bulacan. Check city-mun.json structure.");
  }

  // 3) For each barangay, attach to its municipality (by mun_code)
  $out = ["Bulacan" => []];

  // Prepare output keys (municipality names)
  foreach ($muns as $mun_name) {
    $out["Bulacan"][$mun_name] = [];
  }

  foreach ($barangays as $b) {
    $b_mun_code = pick($b, ["mun_code", "citymun_code", "city_code", "municipality_code"]);
    $b_name     = pick($b, ["name", "brgy_name", "barangay_name"]);

    if (!$b_mun_code || !$b_name) continue;
    if (!isset($muns[(string)$b_mun_code])) continue; // not Bulacan

    $mun_name = $muns[(string)$b_mun_code];
    $out["Bulacan"][$mun_name][] = $b_name;
  }

  // 4) Sort barangays A–Z per municipality
  foreach ($out["Bulacan"] as $mun => $brgys) {
    sort($brgys, SORT_NATURAL | SORT_FLAG_CASE);
    $out["Bulacan"][$mun] = $brgys;
  }

  // Sort municipality keys A–Z
  ksort($out["Bulacan"], SORT_NATURAL | SORT_FLAG_CASE);

  // 5) Save to assets/ph-address/bulacan.json
  $savePath = $base . "bulacan.json";
  $json = json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
  file_put_contents($savePath, $json);

  echo "✅ Generated: assets/ph-address/bulacan.json\n";
  echo "Province code used: " . $bulacanProvCode . "\n";
  echo "Municipalities found: " . count($out["Bulacan"]) . "\n";

} catch (Exception $e) {
  http_response_code(500);
  echo "❌ Error: " . $e->getMessage();
}
