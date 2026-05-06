<?php
$base = __DIR__ . "/assets/ph-address";
$cityMunPath = $base . "/city-mun.json";
$provPath    = $base . "/provinces.json";
$outPath     = $base . "/zipcodes.json";
$url         = "https://phlpost.gov.ph/zip-code-locator/";

if (!file_exists($cityMunPath)) die("Missing city-mun.json\n");
if (!file_exists($provPath))    die("Missing provinces.json\n");

$cityMuns = json_decode(file_get_contents($cityMunPath), true);
$provsRaw = json_decode(file_get_contents($provPath), true);
if (!is_array($cityMuns) || !is_array($provsRaw)) die("Invalid JSON input\n");

$html = @file_get_contents($url);
if ($html === false) die("Failed to download PHLPost page.\n");

// ---------- Normalization ----------
function norm($s) {
  $s = strtolower(trim($s));
  $s = html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');

  $s = str_replace(['’', '`'], ["'", "'"], $s);
  $s = str_replace(['.', ',', '-', '–', '—', '/', '\\', '(', ')', '[', ']', '{', '}', ':', ';'], ' ', $s);

  // remove common tokens
  $remove = ['city of', 'city', 'municipality of', 'municipality', 'capital', 'district'];
  foreach ($remove as $r) {
    $s = preg_replace('/\b' . preg_quote($r, '/') . '\b/', ' ', $s);
  }

  // common abbreviations
  $s = preg_replace('/\bsto\b/', 'santo', $s);
  $s = preg_replace('/\bsta\b/', 'santa', $s);
  $s = preg_replace('/\bmt\b/', 'mount', $s);

  // compress whitespace
  $s = preg_replace('/\s+/', ' ', $s);
  return trim($s);
}

function chooseDefaultZip(array $zips) {
  sort($zips, SORT_STRING);
  return $zips[0];
}

// ---------- Build prov_code -> province name ----------
$provNameByCode = [];
foreach ($provsRaw as $p) {
  if (!isset($p["prov_code"], $p["name"])) continue;
  $provNameByCode[(string)$p["prov_code"]] = (string)$p["name"];
}

// ---------- Parse PHLPost table ----------
libxml_use_internal_errors(true);
$dom = new DOMDocument();
$dom->loadHTML($html);
$xpath = new DOMXPath($dom);
$rows = $xpath->query("//table//tr");

// Maps:
// 1) city+province key => zips
$zipByCityProv = []; // "$cityKey|$provKey" => [zip=>true]
// 2) city-only fallback => zips
$zipByCityOnly = []; // "$cityKey" => [zip=>true]

foreach ($rows as $tr) {
  $cells = [];
  foreach ($tr->getElementsByTagName("td") as $td) {
    $cells[] = trim($td->textContent);
  }
  if (count($cells) < 4) continue;

  $province = $cells[1];
  $city     = $cells[2];
  $zip      = $cells[3];

  if (!preg_match('/^\d{4}$/', $zip)) continue;

  $cityKey = norm($city);
  $provKey = norm($province);
  if ($cityKey === "" || $provKey === "") continue;

  $k = $cityKey . "|" . $provKey;

  if (!isset($zipByCityProv[$k])) $zipByCityProv[$k] = [];
  $zipByCityProv[$k][$zip] = true;

  if (!isset($zipByCityOnly[$cityKey])) $zipByCityOnly[$cityKey] = [];
  $zipByCityOnly[$cityKey][$zip] = true;
}

// ---------- Map your mun_code -> zip ----------
$out = [];
$assigned = 0;
$missing = 0;
$ambiguous = 0;
$usedFallbackCityOnly = 0;

foreach ($cityMuns as $c) {
  $mun = (string)($c["mun_code"] ?? "");
  $name = (string)($c["name"] ?? "");
  $provCode = (string)($c["prov_code"] ?? "");

  if ($mun === "" || $name === "" || $provCode === "") continue;

  $cityKey = norm($name);

  $provName = $provNameByCode[$provCode] ?? "";
  $provKey  = norm($provName);

  $zips = null;

  // 1) Try city + province (best)
  if ($provKey !== "") {
    $k = $cityKey . "|" . $provKey;
    if (isset($zipByCityProv[$k])) {
      $zips = array_keys($zipByCityProv[$k]);
    }
  }

  // 2) Fallback: city only (if province didn’t match)
  if (!$zips && isset($zipByCityOnly[$cityKey])) {
    $zips = array_keys($zipByCityOnly[$cityKey]);
    $usedFallbackCityOnly++;
  }

  if (!$zips) {
    $missing++;
    continue;
  }

  $zip = (count($zips) === 1) ? $zips[0] : chooseDefaultZip($zips);

  if (count($zips) > 1) $ambiguous++;
  $assigned++;

  $out[] = ["mun_code" => $mun, "zip" => $zip];
}

file_put_contents($outPath, json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo "✅ Generated: $outPath\n";
echo "✅ Assigned ZIPs: $assigned\n";
echo "⚠️ Missing (no match): $missing\n";
echo "⚠️ Ambiguous (multiple ZIPs, default chosen): $ambiguous\n";
echo "ℹ️ Fallback used (city-only matches): $usedFallbackCityOnly\n";
