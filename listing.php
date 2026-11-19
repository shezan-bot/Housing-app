<?php
require_once __DIR__ . '/functions.php';

$all = load_listings();
$id  = $_GET['id'] ?? '';
$listing = null;
foreach ($all as $l) {
    if ($l['id'] === $id) { $listing = $l; break; }
}
if (!$listing) {
    http_response_code(404);
    echo '<p style="font-family:sans-serif;padding:24px">Listing not found. <a href="browse.php">Back to listings</a></p>';
    exit;
}

$v     = rent_verdict($all, $listing);
$avg   = average_rent($all, $listing['area'], (int)$listing['bedrooms']);
$stale = is_stale($listing['last_verified']);

$mapLink = !empty($listing['map_link']) 
    ? $listing['map_link'] 
    : 'https://www.google.com/maps/search/?api=1&query=' . urlencode($listing['area'] . ' Rajshahi');
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($listing['title']) ?> · <?= e(SITE_NAME) ?></title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>

<header class="home-header">
  <a href="index.php"><?= e(SITE_NAME) ?></a>
  <div>
    <a href="browse.php" class="nav-link">Browse</a>
    <a href="admin.php" class="nav-link">Admin</a>
  </div>
</header>

<main class="detail">
  <h1><?= e($listing['title']) ?></h1>
  <p class="sub"><?= e($listing['area']) ?> · <?= (int)$listing['bedrooms'] ?> Bedroom<?= (int)$listing['bedrooms'] > 1 ? 's' : '' ?><?= $listing['size_sqft'] ? ' · ' . e($listing['size_sqft']) . ' sqft' : '' ?></p>

  <div class="rent-big">৳<?= number_format((float)$listing['rent_bdt']) ?><span class="per">/month</span></div>

  <a href="<?= e($mapLink) ?>" target="_blank" class="btn-map">
    Open exact location in Google Maps
  </a>

  <div class="badges">
    <span class="badge <?= $v['class'] ?>"><?= e($v['label']) ?></span>
    <span class="badge <?= $stale ? 'bad' : 'good' ?>">
      <?= $stale ? 'Last verified ' . days_since($listing['last_verified']) . ' days ago' : 'Verified recently' ?>
    </span>
  </div>

  <?php if ($avg !== null): ?>
    <p class="hint">
      Average rent for a <?= (int)$listing['bedrooms'] ?> Bedroom in <?= e($listing['area']) ?> on this site:
      <strong>৳<?= number_format($avg) ?></strong>.
    </p>
  <?php endif; ?>

  <table class="facts">
    <tr><th>Advance</th><td><?= (int)$listing['advance_months'] ?> Month<?= (int)$listing['advance_months'] > 1 ? 's' : '' ?></td></tr>
    <tr><th>Bachelors</th><td><?= e(ucfirst($listing['bachelor_allowed'])) ?></td></tr>
    <tr><th>Gas included</th><td><?= e(ucfirst($listing['gas_included'])) ?></td></tr>
    <tr><th>Water included</th><td><?= e(ucfirst($listing['water_included'])) ?></td></tr>
    <tr><th>Separate entrance</th><td><?= e(ucfirst($listing['separate_entrance'])) ?></td></tr>
    <tr><th>Floor</th><td><?= e($listing['floor']) ?></td></tr>
    <tr><th>Available from</th><td><?= e($listing['available_from']) ?></td></tr>
    <tr><th>Contact</th><td><?= e($listing['contact']) ?> <em class="muted">(demo number)</em></td></tr>
  </table>

  <p class="desc"><?= nl2br(e($listing['description'])) ?></p>

  <?php if (!empty($listing['images'])): ?>
    <div class="gallery-section">
      <?php foreach (['bedroom' => 'Bedroom', 'living' => 'Living Room', 'kitchen' => 'Kitchen', 'bathroom' => 'Bathroom'] as $key => $label): ?>
        <?php if (!empty($listing['images'][$key])): ?>
          <h3><?= $label ?> Photos</h3>
          <div class="image-grid">
            <?php foreach ($listing['images'][$key] as $img): ?>
              <a href="<?= e($img) ?>" target="_blank"><img src="<?= e($img) ?>" alt="<?= $label ?>"></a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <p style="margin-top:40px;"><a href="browse.php">← Back to all listings</a></p>
</main>
</body>
</html>