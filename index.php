<?php
require_once __DIR__ . '/functions.php';

$all = load_listings();
$featured = $all;
shuffle($featured);
$featured = array_slice($featured, 0, 3);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e(SITE_NAME) ?> · Home</title>
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

<section class="hero">
  <h1>Find a bachelor-friendly home in Rajshahi.</h1>
  <p>No more calling numbers that say "family only". Filter by advance pay, compare fair rents, and find your next room.</p>
  
  <form class="hero-search" method="get" action="browse.php">
    <select name="area">
      <option value="">Any area</option>
      <?php foreach (areas($all) as $a): ?>
        <option value="<?= e($a) ?>"><?= e($a) ?></option>
      <?php endforeach; ?>
    </select>
    <input type="number" name="max_rent" placeholder="Max rent (BDT)" min="0" step="1000">
    <button type="submit">Search Homes</button>
  </form>
</section>

<section class="features">
  <div class="feature-box">
    <h3>Bachelor Focused</h3>
    <p>Filter specifically for places that allow bachelors or are negotiable. No time wasted on family-only flats.</p>
  </div>
  <div class="feature-box">
    <h3>Know the Advance</h3>
    <p>Don't waste time. See exactly how many months of advance the owner wants before you even call.</p>
  </div>
  <div class="feature-box">
    <h3>Fair Rent Check</h3>
    <p>We compare the rent against the average for that area so you don't get overcharged.</p>
  </div>
</section>

<section class="featured-section">
  <div class="section-header">
    <h2>Featured Listings</h2>
    <a href="browse.php">View all listings</a>
  </div>
  
  <div class="grid">
    <?php foreach ($featured as $l): ?>
      <?php
        $v = rent_verdict($all, $l);
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
          <div class="sub"><?= e($l['area']) ?> · <?= (int)$l['bedrooms'] ?> Bedrooms</div>
          <div class="tags">
            <span class="tag <?= $l['bachelor_allowed'] === 'yes' ? 'yes' : '' ?>">
              <?= $l['bachelor_allowed'] === 'yes' ? 'Bachelors Allowed' : 'Bachelors ' . e(ucfirst($l['bachelor_allowed'])) ?>
            </span>
            <span class="badge <?= $v['class'] ?>"><?= e($v['label']) ?></span>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>

<footer>
  <p>Built for Rajshahi. Demo project with sample data.</p>
</footer>

</body>
</html>