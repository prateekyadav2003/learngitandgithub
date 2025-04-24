<?php
// listing.php
// Include your database configuration file
require_once 'database_connect.php';

// Helper: renders star ratings out of 5
function generateStars($rating) {
    $full = floor($rating);
    $half = ($rating - $full) >= 0.5 ? 1 : 0;
    $empty = 5 - $full - $half;
    return str_repeat('★', $full) . ($half ? '½' : '') . str_repeat('☆', $empty);
}

// Fetch all properties along with city name
$sql = "
    SELECT p.*, c.name AS city_name
    FROM properties p
    LEFT JOIN cities c ON p.city_id = c.id
    ORDER BY p.id DESC
";
$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();

// Correct uploads paths relative to this script inside 'includes/'
$uploadDir = realpath(__DIR__ . '/../uploads/') . '/';
$uploadUrl = '../uploads/';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>PG Listings</title>
  <style>
    /* Container */
    .pg-wrapper { display: flex; align-items: center; justify-content: center; padding: 20px; background: #f5f5f5; }
    .scroll-btn { background: #333; color: #fff; border: none; padding: 10px; cursor: pointer; font-size: 1.5rem; }
    /* Scrollable container */
    #pgScroll { display: flex; overflow-x: auto; scroll-behavior: smooth; }
    /* Card */
    .pg-card { background: #fff; border-radius: 8px; margin: 0 10px; min-width: 280px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
    .pg-card img { width: 100%; height: 180px; object-fit: cover; border-top-left-radius: 8px; border-top-right-radius: 8px; }
    .pg-info { padding: 12px; color: #333; }
    .pg-title { font-size: 1.2rem; margin: 0 0 6px; }
    .pg-city, .pg-type, .pg-rent, .pg-rating { margin: 4px 0; }
    .pg-rent { font-weight: bold; }
  </style>
</head>
<body>
  <div class="pg-wrapper">
    <button class="scroll-btn left" onclick="scrollToStart()">←</button>
    <div id="pgScroll">
      <?php while ($row = $result->fetch_assoc()):
        // Determine image path with existence check
        $filename = $row['file'] ?? '';
        $filePath = $uploadDir . $filename;
        if (!empty($filename) && file_exists($filePath)) {
            $imageSrc = $uploadUrl . rawurlencode($filename);
        } else {
            $imageSrc = $uploadUrl . 'placeholder.jpg';
        }

        // Compute average rating from available rating columns
        $avgRating = isset($row['rating_clean'], $row['rating_food'], $row['rating_safety'])
            ? ($row['rating_clean'] + $row['rating_food'] + $row['rating_safety']) / 3
            : 0;
      ?>
      <div class="pg-card">
        <img src="<?php echo $imageSrc; ?>" alt="<?php echo htmlspecialchars($row['name']); ?>">
        <div class="pg-info">
          <h3 class="pg-title"><?php echo htmlspecialchars($row['name']); ?></h3>
          <p class="pg-city"><?php echo htmlspecialchars($row['city_name'] ?? ''); ?></p>
          <p class="pg-type"><?php echo htmlspecialchars($row['type'] ?? $row['pg_type'] ?? ''); ?></p>
          <p class="pg-rent">₹<?php echo number_format($row['rent']); ?>/month</p>
          <p class="pg-rating"><?php echo generateStars($avgRating); ?></p>
        </div>
      </div>
      <?php endwhile; ?>
    </div>
    <button class="scroll-btn right" onclick="scrollToEnd()">→</button>
  </div>

  <script>
    function scrollToEnd() {
      const box = document.getElementById('pgScroll');
      box.scrollLeft += box.clientWidth;
    }
    function scrollToStart() {
      const box = document.getElementById('pgScroll');
      box.scrollLeft -= box.clientWidth;
    }
  </script>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
