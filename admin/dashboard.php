<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

function e($string) {
    return htmlspecialchars((string) $string, ENT_QUOTES, 'UTF-8');
}

$username = $_SESSION['admin_username'] ?? 'Admin';

try {
    // --- Dashboard Stats (All in one place) ---

    // 1. Summary Cards Data (From your code)
    $totalRepairs = (int) $pdo->query("SELECT COUNT(*) FROM repairs")->fetchColumn();
    $totalProducts = (int) $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    $totalArticles = (int) $pdo->query("SELECT COUNT(*) FROM articles")->fetchColumn();

    // 2. Chart Data (From your code)
    $currentYear = date('Y');
    $repairsByMonth = array_fill(1, 12, 0); 
    $stmtChart = $pdo->prepare("SELECT MONTH(created_at) AS month, COUNT(*) AS count FROM repairs WHERE YEAR(created_at) = ? GROUP BY MONTH(created_at)");
    $stmtChart->execute([$currentYear]);
    $monthlyResults = $stmtChart->fetchAll(PDO::FETCH_KEY_PAIR);
    foreach ($monthlyResults as $month => $count) {
        $repairsByMonth[(int)$month] = (int)$count;
    }
    $chartMonths = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    $chartRepairsData = array_values($repairsByMonth);
    $currentMonthIndex = (int)date('n');
    $monthlyRepairs = $repairsByMonth[$currentMonthIndex];

    // 3. Latest Items Data (From your code)
    $latestRepairs = $pdo->query("SELECT id, title, model, created_at FROM repairs ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    $latestProducts = $pdo->query("SELECT id, name, price, created_at FROM products ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    $latestArticles = $pdo->query("SELECT id, title, slug, created_at FROM articles ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

    // ==================================================================
    //  ส่วนที่กูเพิ่มให้: สถิติแยกตามแอดมิน
    // ==================================================================
    $article_counts_stmt = $pdo->query("
        SELECT admin_users.username, COUNT(articles.id) AS item_count FROM articles
        LEFT JOIN admin_users ON articles.admin_id = admin_users.id WHERE articles.admin_id IS NOT NULL
        GROUP BY articles.admin_id ORDER BY item_count DESC
    ");
    $article_stats = $article_counts_stmt->fetchAll(PDO::FETCH_ASSOC);

    $repair_counts_stmt = $pdo->query("
        SELECT admin_users.username, COUNT(repairs.id) AS item_count FROM repairs
        LEFT JOIN admin_users ON repairs.admin_id = admin_users.id WHERE repairs.admin_id IS NOT NULL
        GROUP BY repairs.admin_id ORDER BY item_count DESC
    ");
    $repair_stats = $repair_counts_stmt->fetchAll(PDO::FETCH_ASSOC);

    $product_counts_stmt = $pdo->query("
        SELECT admin_users.username, COUNT(products.id) AS item_count FROM products
        LEFT JOIN admin_users ON products.admin_id = admin_users.id WHERE products.admin_id IS NOT NULL
        GROUP BY products.admin_id ORDER BY item_count DESC
    ");
    $product_stats = $product_counts_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
}

// Include header AFTER all PHP logic is done
include '../templates/header_admin.php';
include '../templates/sidebar_admin.php';
?>

<style>
    .stats-container { display: flex; gap: 20px; flex-wrap: wrap; }
    .stat-card { background-color: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; flex: 1; min-width: 250px; box-shadow: 0 1px 3px 0 rgba(0,0,0,0.1); }
    .stat-card h3 { margin-top: 0; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px; font-size: 1.1rem; }
    .stat-card ul { list-style-type: none; padding: 0; margin: 0; }
    .stat-card li { padding: 8px 0; display: flex; justify-content: space-between; border-bottom: 1px solid #f1f5f9; }
    .stat-card li:last-child { border-bottom: none; }
    .stat-card li span:first-child { font-weight: 600; }
</style>

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
  
  <hr style="margin: 30px 0;">
  <section class="dashboard-section">
    <h2>สรุปผลงานตามผู้ดูแล</h2>
    <?php if (isset($error_message)): ?>
        <p style="color: red;"><?= htmlspecialchars($error_message) ?></p>
    <?php else: ?>
        <div class="stats-container">
            <div class="stat-card">
                <h3>ยอดรวมบทความ</h3>
                <?php if (empty($article_stats)): ?><p>ยังไม่มีข้อมูล</p><?php else: ?>
                    <ul><?php foreach ($article_stats as $stat): ?><li><span><?= e($stat['username']) ?></span><span><?= $stat['item_count'] ?> รายการ</span></li><?php endforeach; ?></ul>
                <?php endif; ?>
            </div>
            <div class="stat-card">
                <h3>ยอดรวมผลงานซ่อม</h3>
                <?php if (empty($repair_stats)): ?><p>ยังไม่มีข้อมูล</p><?php else: ?>
                    <ul><?php foreach ($repair_stats as $stat): ?><li><span><?= e($stat['username']) ?></span><span><?= $stat['item_count'] ?> รายการ</span></li><?php endforeach; ?></ul>
                <?php endif; ?>
            </div>
            <div class="stat-card">
                <h3>ยอดรวมสินค้า</h3>
                <?php if (empty($product_stats)): ?><p>ยังไม่มีข้อมูล</p><?php else: ?>
                    <ul><?php foreach ($product_stats as $stat): ?><li><span><?= e($stat['username']) ?></span><span><?= $stat['item_count'] ?> รายการ</span></li><?php endforeach; ?></ul>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
  </section>
  
  <hr style="margin: 30px 0;">
  <section class="dashboard-section">
    <h2>รายการล่าสุด</h2>
    <div class="latest-items-grid">
      <div class="latest-item-list">
        <h3>ผลงานซ่อมล่าสุด</h3>
        <ul><?php foreach($latestRepairs as $item): ?><li><a href="repairs/edit.php?id=<?= e($item['id']) ?>"><span><strong><?= e($item['title'] ?: 'N/A') ?></strong> (<?= e($item['model'] ?: 'N/A') ?>)</span><small><?= date('d M Y', strtotime($item['created_at'])) ?></small></a></li><?php endforeach; ?></ul>
      </div>
      <div class="latest-item-list">
        <h3>สินค้าล่าสุด</h3>
        <ul><?php foreach($latestProducts as $item): ?><li><a href="products/edit.php?id=<?= e($item['id']) ?>"><strong><?= e($item['name'] ?: 'N/A') ?></strong><small><?= date('d M Y', strtotime($item['created_at'])) ?></small></a></li><?php endforeach; ?></ul>
      </div>
      <div class="latest-item-list">
        <h3>บทความล่าสุด</h3>
        <ul><?php foreach($latestArticles as $item): ?><li><a href="articles/edit.php?id=<?= e($item['id']) ?>"> <strong><?= e($item['title'] ?: 'N/A') ?></strong><small><?= date('d M Y', strtotime($item['created_at'])) ?></small></a></li><?php endforeach; ?></ul>
      </div>
    </div>
  </section>
</main>

<?php include '../templates/footer_admin.php'; ?>
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
        scales: { y: { beginAtZero: true, ticks: { precision: 0 }}},
        plugins: { legend: { display: false }}
      }
    });
  }
});
</script>
</body>
</html>