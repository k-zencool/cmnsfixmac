<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

// if (!isAdmin()) { exit('Access Denied'); }

function e($string) {
    return htmlspecialchars((string) $string, ENT_QUOTES, 'UTF-8');
}

$username = $_SESSION['admin_username'] ?? 'Admin';

// --- Dashboard Stats (All in one place) ---

// 1. Summary Cards Data
$totalRepairs = (int) $pdo->query("SELECT COUNT(*) FROM repairs")->fetchColumn();
$totalProducts = (int) $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$totalArticles = (int) $pdo->query("SELECT COUNT(*) FROM articles")->fetchColumn();
$totalAdmins = (int) $pdo->query("SELECT COUNT(*) FROM admin_users")->fetchColumn();

// 2. Chart Data (Optimized Query - 1 query instead of 12!)
$currentYear = date('Y');
$repairsByMonth = array_fill(1, 12, 0); // Initialize all 12 months with 0

// Fetch data for the whole year and group by month
$stmtChart = $pdo->prepare("SELECT MONTH(created_at) AS month, COUNT(*) AS count 
                            FROM repairs 
                            WHERE YEAR(created_at) = ? 
                            GROUP BY MONTH(created_at)");
$stmtChart->execute([$currentYear]);
$monthlyResults = $stmtChart->fetchAll(PDO::FETCH_KEY_PAIR); // Fetches as [month => count]

// Fill the array with data from the database
foreach ($monthlyResults as $month => $count) {
    $repairsByMonth[(int)$month] = (int)$count;
}

$chartMonths = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
$chartRepairsData = array_values($repairsByMonth);

// For the "This Month" card
$currentMonthIndex = (int)date('n');
$monthlyRepairs = $repairsByMonth[$currentMonthIndex];

// 3. Latest Items Data
$latestRepairs = $pdo->query("SELECT id, title, model, created_at FROM repairs ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
$latestProducts = $pdo->query("SELECT id, name, price, created_at FROM products ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
$latestArticles = $pdo->query("SELECT id, title, slug, created_at FROM articles ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

// Include header AFTER all PHP logic is done
include '../templates/header_admin.php';
include '../templates/sidebar_admin.php';
?>

<main class="main" id="main-content">
  <div class="topbar" id="topbar">
    <span>ยินดีต้อนรับ, <?= e($username) ?></span>
    <a href="/" class="view-site" target="_blank">ดูเว็บไซต์</a>
  </div>

  <section class="dashboard-cards">
    <div class="card card-hover">
      <span class="material-symbols-rounded">construction</span>
      <h2><?= $totalRepairs ?> รายการ</h2>
      <p>ผลงานซ่อมทั้งหมด</p>
    </div>
    <div class="card card-hover">
      <span class="material-symbols-rounded">inventory_2</span>
      <h2><?= $totalProducts ?> ชิ้น</h2>
      <p>สินค้าทั้งหมด</p>
    </div>
    <div class="card card-hover">
      <span class="material-symbols-rounded">description</span>
      <h2><?= $totalArticles ?> บทความ</h2>
      <p>บทความทั้งหมด</p>
    </div>
    <div class="card card-hover">
      <span class="material-symbols-rounded">trending_up</span>
      <h2><?= $monthlyRepairs ?> รายการ</h2>
      <p>ผลงานซ่อมเดือนนี้</p>
    </div>
  </section>

  <section class="dashboard-section">
    <h2>สรุปผลงานซ่อมรายเดือน (ปี <?= $currentYear ?>)</h2>
    <div class="chart-container">
      <canvas id="repairsChart"></canvas>
    </div>
  </section>

  <section class="dashboard-section">
    <h2>รายการล่าสุด</h2>
    <div class="latest-items-grid">
      
      <div class="latest-item-list">
        <h3>ผลงานซ่อมล่าสุด</h3>
        <ul>
          <?php foreach($latestRepairs as $item): ?>
            <li>
              <a href="repairs/edit.php?id=<?= e($item['id']) ?>">
                <span>
                  <strong><?= e($item['title'] ?: 'N/A') ?></strong> (<?= e($item['model'] ?: 'N/A') ?>)
                </span>
                <small><?= date('d M Y', strtotime($item['created_at'])) ?></small>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>

      <div class="latest-item-list">
        <h3>สินค้าล่าสุด</h3>
        <ul>
          <?php foreach($latestProducts as $item): ?>
            <li>
              <a href="products/edit.php?id=<?= e($item['id']) ?>">
                <strong><?= e($item['name'] ?: 'N/A') ?></strong>
                <small><?= date('d M Y', strtotime($item['created_at'])) ?></small>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>

      <div class="latest-item-list">
        <h3>บทความล่าสุด</h3>
        <ul>
          <?php foreach($latestArticles as $item): ?>
            <li>
              <a href="articles/edit.php?id=<?= e($item['id']) ?>"> <strong><?= e($item['title'] ?: 'N/A') ?></strong>
                <small><?= date('d M Y', strtotime($item['created_at'])) ?></small>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>

    </div>
  </section>
</main>

<?php 
// Include footer only ONCE
include '../templates/footer_admin.php'; 
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const ctx = document.getElementById('repairsChart');
  if (ctx) { 
    new Chart(ctx, {
      type: 'bar',
      data: {
        labels: <?= json_encode($chartMonths) ?>,
        datasets: [{
          label: 'จำนวนผลงานซ่อม',
          data: <?= json_encode($chartRepairsData) ?>,
          backgroundColor: 'rgba(59, 130, 246, 0.5)',
          borderColor: 'rgba(59, 130, 246, 1)',
          borderWidth: 1,
          borderRadius: 5
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              precision: 0 // Ensure y-axis shows whole numbers
            }
          }
        },
        plugins: {
            legend: {
                display: false // Hide legend if there is only one dataset
            }
        }
      }
    });
  }
});

// Assuming your dashboard-script.js is for sidebar toggling and other global admin functions
// And it's correctly included in your footer_admin.php with correct paths
// The chart logic is now self-contained in dashboard.php for simplicity and to ensure correct load order.
</script>

</body>
</html>