<?php
session_start();
require_once __DIR__ . '/functions.php';

$listings = load_listings();
$msg = '';

if (isset($_POST['login'])) {
    if ($_POST['password'] === ADMIN_PASSWORD) {
        $_SESSION['admin'] = true;
    } else {
        $msg = 'Wrong password.';
    }
}
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}
$loggedIn = !empty($_SESSION['admin']);

if ($loggedIn && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'delete') {
        $listings = array_values(array_filter($listings, fn($l) => $l['id'] !== $_POST['id']));
        save_listings($listings);
        $msg = 'Listing deleted.';
    }

    if ($action === 'verify') {
        foreach ($listings as $i => $l) {
            if ($l['id'] === $_POST['id']) {
                $listings[$i]['last_verified'] = date('Y-m-d');
                break;
            }
        }
        save_listings($listings);
        $msg = 'Marked as verified today.';
    }

    if ($action === 'save') {
        $fields = ['title','area','rent_bdt','advance_months','bedrooms','size_sqft',
                   'bachelor_allowed','gas_included','water_included','separate_entrance',
                   'floor','available_from','contact','description','map_link'];
        $item = [];
        foreach ($fields as $f) {
            $item[$f] = trim((string)($_POST[$f] ?? ''));
        }
        $item['rent_bdt']       = (float)$item['rent_bdt'];
        $item['advance_months'] = (int)$item['advance_months'];
        $item['bedrooms']       = (int)$item['bedrooms'];
        $item['last_verified']  = date('Y-m-d');

        $editId = trim((string)($_POST['id'] ?? ''));
        
        // Handle image uploads
        $newImages = [];
        foreach (['bedroom', 'living', 'kitchen', 'bathroom'] as $room) {
            $uploaded = upload_images($_FILES, $room);
            if (!empty($uploaded)) {
                $newImages[$room] = $uploaded;
            }
        }

        if ($editId === '') {
            $item['id']         = bin2hex(random_bytes(4));
            $item['date_added'] = date('Y-m-d');
            $item['images']     = $newImages; // Add images
            $listings[] = $item;
            $msg = 'Listing added.';
        } else {
            foreach ($listings as $i => $l) {
                if ($l['id'] === $editId) {
                    $item['id']         = $editId;
                    $item['date_added'] = $l['date_added'] ?? date('Y-m-d');
                    // If new images uploaded, replace old. Otherwise keep old.
                    $item['images']     = empty($newImages) ? ($l['images'] ?? []) : $newImages;
                    $listings[$i] = $item;
                    break;
                }
            }
            $msg = 'Listing updated.';
        }
        save_listings($listings);
    }
}

$editing = null;
if ($loggedIn && isset($_GET['edit'])) {
    foreach ($listings as $l) {
        if ($l['id'] === $_GET['edit']) { $editing = $l; break; }
    }
}

$sel = fn($field, $val) => ($editing[$field] ?? '') === $val ? 'selected' : '';
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin · <?= e(SITE_NAME) ?></title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header><a href="index.php">← <?= e(SITE_NAME) ?></a> · Admin</header>

<main class="admin">
<?php if (!$loggedIn): ?>
  <form method="post" class="box">
    <h2>Admin login</h2>
    <p class="hint">Password is in <code>config.php</code>.</p>
    <?php if ($msg): ?><p class="error"><?= e($msg) ?></p><?php endif; ?>
    <input type="password" name="password" placeholder="Password" required>
    <button type="submit" name="login" value="1">Log in</button>
  </form>

<?php else: ?>
  <p class="row"><strong><?= count($listings) ?> listings</strong>
    <a href="?logout=1">Log out</a></p>
  <?php if ($msg): ?><p class="ok"><?= e($msg) ?></p><?php endif; ?>

  <div class="box">
    <h2><?= $editing ? 'Edit listing' : 'Add a listing' ?></h2>
    <!-- ADDED enctype for file uploads -->
    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="id" value="<?= e($editing['id'] ?? '') ?>">

      <label>Title <input name="title" required value="<?= e($editing['title'] ?? '') ?>"></label>

      <label>Area <input name="area" required list="area-list" value="<?= e($editing['area'] ?? '') ?>"></label>
      <datalist id="area-list">
        <?php foreach (areas($listings) as $a): ?><option value="<?= e($a) ?>"><?php endforeach; ?>
      </datalist>

      <label>Google Maps Link <input name="map_link" placeholder="https://maps.app.goo.gl/..." value="<?= e($editing['map_link'] ?? '') ?>"></label>

      <div class="two">
        <label>Rent (BDT) <input type="number" name="rent_bdt" min="0" required value="<?= e($editing['rent_bdt'] ?? '') ?>"></label>
        <label>Advance months <input type="number" name="advance_months" min="0" required value="<?= e($editing['advance_months'] ?? '1') ?>"></label>
      </div>
      <div class="two">
        <label>Bedrooms <input type="number" name="bedrooms" min="1" required value="<?= e($editing['bedrooms'] ?? '1') ?>"></label>
        <label>Size (sqft) <input name="size_sqft" value="<?= e($editing['size_sqft'] ?? '') ?>"></label>
      </div>

      <div class="three">
        <label>Bachelors
          <select name="bachelor_allowed">
            <option value="yes"        <?= $sel('bachelor_allowed','yes') ?>>yes</option>
            <option value="no"         <?= $sel('bachelor_allowed','no') ?>>no</option>
            <option value="negotiable" <?= $sel('bachelor_allowed','negotiable') ?>>negotiable</option>
          </select>
        </label>
        <label>Gas included
          <select name="gas_included">
            <option value="yes" <?= $sel('gas_included','yes') ?>>yes</option>
            <option value="no"  <?= $sel('gas_included','no') ?>>no</option>
          </select>
        </label>
        <label>Water included
          <select name="water_included">
            <option value="yes" <?= $sel('water_included','yes') ?>>yes</option>
            <option value="no"  <?= $sel('water_included','no') ?>>no</option>
          </select>
        </label>
      </div>

      <div class="three">
        <label>Separate entrance
          <select name="separate_entrance">
            <option value="yes" <?= $sel('separate_entrance','yes') ?>>yes</option>
            <option value="no"  <?= $sel('separate_entrance','no') ?>>no</option>
          </select>
        </label>
        <label>Floor <input name="floor" value="<?= e($editing['floor'] ?? '') ?>"></label>
        <label>Available from <input type="date" name="available_from" value="<?= e($editing['available_from'] ?? '') ?>"></label>
      </div>

      <label>Contact <input name="contact" required value="<?= e($editing['contact'] ?? '') ?>"></label>
      
      <!-- NEW: Image uploads for each room -->
      <div class="uploads-box">
        <p style="margin:0 0 10px; font-weight:bold; color:var(--ink);">Upload Photos (JPG, PNG)</p>
        <label>Bedroom Photos (Multiple allowed) <input type="file" name="bedroom[]" accept="image/*" multiple></label>
        <label>Living Room Photos <input type="file" name="living[]" accept="image/*" multiple></label>
        <label>Kitchen Photos <input type="file" name="kitchen[]" accept="image/*" multiple></label>
        <label>Bathroom Photos <input type="file" name="bathroom[]" accept="image/*" multiple></label>
      </div>

      <label>Description <textarea name="description" rows="4"><?= e($editing['description'] ?? '') ?></textarea></label>

      <button type="submit"><?= $editing ? 'Save changes' : 'Add listing' ?></button>
      <?php if ($editing): ?><a href="admin.php">Cancel</a><?php endif; ?>
    </form>
  </div>

  <div class="box">
    <h2>All listings</h2>
    <table class="admin-table">
      <tr><th>Title</th><th>Area</th><th>Rent</th><th>Bach.</th><th>Verified</th><th></th></tr>
      <?php foreach ($listings as $l): ?>
      <tr>
        <td><a href="listing.php?id=<?= e($l['id']) ?>"><?= e($l['title']) ?></a></td>
        <td><?= e($l['area']) ?></td>
        <td>৳<?= number_format((float)$l['rent_bdt']) ?></td>
        <td><?= e($l['bachelor_allowed']) ?></td>
        <td><?= e($l['last_verified']) ?></td>
        <td class="actions">
          <a href="?edit=<?= e($l['id']) ?>">Edit</a>
          <form method="post" onsubmit="return confirm('Delete this listing?')">
            <input type="hidden" name="action" value="verify">
            <input type="hidden" name="id" value="<?= e($l['id']) ?>">
            <button type="submit" class="link">Verified today</button>
          </form>
          <form method="post" onsubmit="return confirm('Delete this listing?')">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= e($l['id']) ?>">
            <button type="submit" class="link danger">Delete</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </table>
  </div>
<?php endif; ?>
</main>
</body>
</html>