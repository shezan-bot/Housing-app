<?php
require_once __DIR__ . '/functions.php';

$all = load_listings();
$q = [
    'area'        => $_GET['area']        ?? '',
    'max_rent'    => $_GET['max_rent']    ?? '',
    'bedrooms'    => $_GET['bedrooms']    ?? '',
    'bachelor'    => $_GET['bachelor']    ?? 'any',
    'max_advance' => $_GET['max_advance'] ?? '',
    'q'           => $_GET['q']           ?? '',
];
$results = filter_listings($all, $q);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Browse Homes · <?= e(SITE_NAME) ?></title>
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

<form class="filters" method="get" action="browse.php">
  <input type="search" name="q" placeholder="Search title or description" value="<?= e($q['q']) ?>">

  <select name="area">
    <option value="">Any area</option>
    <?php foreach (areas($all) as $a): ?>
      <option value="<?= e($a) ?>" <?= $q['area'] === $a ? 'selected' : '' ?>><?= e($a) ?></option>
    <?php endforeach; ?>
  </select>

  <input type="number" name="max_rent" placeholder="Max rent (BDT)" min="0" step="500" value="<?= e($q['max_rent']) ?>">

  <select name="bedrooms">
    <option value="">Any bedrooms</option>
    <?php foreach ([1,2,3,4] as $b): ?>
      <option value="<?= $b ?>" <?= (string)$q['bedrooms'] === (string)$b ? 'selected' : '' ?>><?= $b ?> bedroom<?= $b > 1 ? 's' : '' ?></option>
    <?php endforeach; ?>
  </select>

  <select name="bachelor">
    <option value="any"        <?= $q['bachelor'] === 'any'        ? 'selected' : '' ?>>Bachelors: any</option>
    <option value="yes"        <?= $q['bachelor'] === 'yes'        ? 'selected' : '' ?>>Bachelors allowed</option>
    <option value="negotiable" <?= $q['bachelor'] === 'negotiable' ? 'selected' : '' ?>>Bachelors negotiable</option>
  </select>

  <select name="max_advance">
    <option value="">Any advance</option>
    <?php foreach ([1,2,3] as $m): ?>
      <option value="<?= $m ?>" <?= (string)$q['max_advance'] === (string)$m ? 'selected' : '' ?>>Max <?= $m ?> month<?= $m > 1 ? 's' : '' ?> advance</option>
    <?php endforeach; ?>
  </select>

  <button type="submit">Apply Filters</button>
  <a class="reset" href="browse.php">Reset</a>
</form>

<p class="count"><?= count($results) ?> listing<?= count($results) === 1 ? '' : 's' ?> found</p>

<main class="grid" style="padding: 24px;">
<?php if (!$results): ?>
  <p class="empty">No listings match those filters. Try widening your search.</p>
<?php endif; ?>

<?php foreach ($results as $l): ?>
  <?php
    $v = rent_verdict($all, $l);
    $stale = is_stale($l['last_verified']);
    $thumb = $l['images']['bedroom'][0] ?? ($l['images']['living'][0] ?? null);
  ?>
  <article class="card">
    <div class="photo">
      <?php if ($thumb): ?>
        <img src="<?= e($thumb) ?>" alt="">
      <?php else: ?>
        No Image
      <?php endif; ?>
    </div>
    <div class="body">
      <div class="rent">৳<?= number_format((float)$l['rent_bdt']) ?><span class="per">/mo</span></div>
      <a class="title" href="listing.php?id=<?= e($l['id']) ?>"><?= e($l['title']) ?></a>
      <div class="sub"><?= e($l['area']) ?> · <?= (int)$l['bedrooms'] ?> Bedroom<?= (int)$l['bedrooms'] > 1 ? 's' : '' ?><?= $l['size_sqft'] ? ' · ' . e($l['size_sqft']) . ' sqft' : '' ?></div>

      <div class="tags">
        <span class="tag <?= $l['bachelor_allowed'] === 'yes' ? 'yes' : ($l['bachelor_allowed'] === 'no' ? 'no' : '') ?>">
          <?= $l['bachelor_allowed'] === 'yes' ? 'Bachelors Allowed' : ($l['bachelor_allowed'] === 'no' ? 'No Bachelors' : 'Bachelors Negotiable') ?>
        </span>
        <span class="tag"><?= (int)$l['advance_months'] ?> Month<?= (int)$l['advance_months'] > 1 ? 's' : '' ?> Advance</span>
      </div>

      <div class="badges">
        <span class="badge <?= $v['class'] ?>"><?= e($v['label']) ?></span>
        <span class="badge <?= $stale ? 'bad' : 'good' ?>">
          <?= $stale ? 'Unverified (' . days_since($l['last_verified']) . ' days)' : 'Verified' ?>
        </span>
      </div>
    </div>
  </article>
<?php endforeach; ?>
</main>

<footer>
  <p>Built for Rajshahi. <a href="admin.php">Admin Panel</a></p>
</footer>
</body>
</html>