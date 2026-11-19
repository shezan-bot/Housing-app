<?php
require_once __DIR__ . '/config.php';

function load_listings(): array {
    if (!file_exists(DATA_FILE)) return [];
    $data = json_decode(file_get_contents(DATA_FILE), true);
    return is_array($data) ? $data : [];
}

function save_listings(array $listings): void {
    file_put_contents(
        DATA_FILE,
        json_encode(array_values($listings), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        LOCK_EX
    );
}

function e($s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function days_since(string $date): int {
    $t = strtotime($date);
    return $t === false ? 9999 : (int) floor((time() - $t) / 86400);
}

function is_stale(string $lastVerified): bool {
    return days_since($lastVerified) > STALE_DAYS;
}

function areas(array $listings): array {
    $a = array_unique(array_map(fn($l) => $l['area'], $listings));
    sort($a);
    return $a;
}

function average_rent(array $listings, string $area, int $bedrooms): ?float {
    $vals = [];
    foreach ($listings as $l) {
        if ($l['area'] === $area && (int)$l['bedrooms'] === $bedrooms) {
            $vals[] = (float)$l['rent_bdt'];
        }
    }
    return count($vals) < 2 ? null : array_sum($vals) / count($vals);
}

function rent_verdict(array $all, array $listing): array {
    $avg = average_rent($all, $listing['area'], (int)$listing['bedrooms']);
    if ($avg === null) return ['label' => 'Not enough data', 'class' => 'neutral'];
    $diff = ((float)$listing['rent_bdt'] - $avg) / $avg;
    if ($diff <= -0.10) return ['label' => 'Below average', 'class' => 'good'];
    if ($diff >=  0.10) return ['label' => 'Above average', 'class' => 'bad'];
    return ['label' => 'About average', 'class' => 'warn'];
}

function filter_listings(array $listings, array $q): array {
    $out = [];
    foreach ($listings as $l) {
        if ($q['area']      && $l['area'] !== $q['area']) continue;
        if ($q['max_rent']  && (float)$l['rent_bdt'] > (float)$q['max_rent']) continue;
        if ($q['bedrooms']  && (int)$l['bedrooms'] !== (int)$q['bedrooms']) continue;
        if ($q['bachelor'] !== 'any' && $l['bachelor_allowed'] !== $q['bachelor']) continue;
        if ($q['max_advance'] && (int)$l['advance_months'] > (int)$q['max_advance']) continue;
        if ($q['q']) {
            $needle = mb_strtolower($q['q']);
            $hay    = mb_strtolower($l['title'] . ' ' . $l['description'] . ' ' . $l['area']);
            if (mb_strpos($hay, $needle) === false) continue;
        }
        $out[] = $l;
    }
    return $out;
}

// NEW: Function to handle multiple file uploads securely
function upload_images($files, $room_type) {
    $paths = [];
    if (!isset($files['name'][$room_type]) || empty($files['name'][$room_type][0])) return $paths;
    
    $uploadDir = __DIR__ . '/uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    $count = count($files['name'][$room_type]);
    for ($i = 0; $i < $count; $i++) {
        if ($files['error'][$room_type][$i] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($files['name'][$room_type][$i], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg','jpeg','png','webp'])) continue; // Only allow images
            
            $filename = uniqid($room_type . '_') . '.' . $ext;
            if (move_uploaded_file($files['tmp_name'][$room_type][$i], $uploadDir . $filename)) {
                $paths[] = 'uploads/' . $filename;
            }
        }
    }
    return $paths;
}