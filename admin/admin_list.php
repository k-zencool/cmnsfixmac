<?php
session_start();
include_once __DIR__ . '/../includes/db.php';
include_once __DIR__ . '/../includes/auth.php';

// --- SQL เวอร์ชันอัปเกรด: ดึงข้อมูลแอดมินพร้อมนับจำนวนโพสต์ ---
$stmt = $pdo->query("
    SELECT
        a.id,
        a.username,
        a.created_at,
        (SELECT COUNT(*) FROM articles WHERE admin_id = a.id) AS article_count,
        (SELECT COUNT(*) FROM repairs WHERE admin_id = a.id) AS repair_count,
        (SELECT COUNT(*) FROM products WHERE admin_id = a.id) AS product_count
    FROM
        admin_users a
    ORDER BY
        a.id ASC
");
$admins = $stmt->fetchAll();

// หากมีการลบ
if (isset($_GET['delete']) && isset($_SESSION['admin_id']) && $_SESSION['admin_id'] == 1) {
    $deleteId = (int)$_GET['delete'];
    if ($deleteId !== 1) {
        $stmt = $pdo->prepare("DELETE FROM admin_users WHERE id = ?");
        $stmt->execute([$deleteId]);
    }
    header('Location: admin_list.php');
    exit;
}

include '../templates/header_admin.php';
include '../templates/sidebar_admin.php';
?>

<main class="main" id="main-content">
  <div class="topbar">
    <span>รายชื่อแอดมินและสถิติ</span>
    <a href="../" class="view-site" target="_blank">ดูเว็บไซต์</a>
  </div>

  <div class="form-section" style="max-width: 100%; padding: 40px;">
    <h2 style="font-size: 24px; margin-bottom: 20px;">👥 รายชื่อแอดมินและสถิติ</h2>

    <table class="data-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Username</th>
          <th>บทความ</th>
          <th>ผลงานซ่อม</th>
          <th>สินค้า</th>
          <th>วันที่สร้าง</th>
          <th>การจัดการ</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($admins as $admin): ?>
        <tr>
          <td><?= htmlspecialchars($admin['id']) ?></td>
          <td><?= htmlspecialchars($admin['username']) ?></td>
          <td><?= htmlspecialchars($admin['article_count']) ?></td>
          <td><?= htmlspecialchars($admin['repair_count']) ?></td>
          <td><?= htmlspecialchars($admin['product_count']) ?></td>
          <td><?= date('d/m/Y', strtotime($admin['created_at'])) ?></td>
          <td>
            <?php if (isset($_SESSION['admin_id']) && $_SESSION['admin_id'] == 1): ?>
              <?php if ($admin['id'] != 1): ?>
                <a href="admin_edit.php?id=<?= $admin['id'] ?>" class="btn-edit">แก้ไข</a>
                <a href="admin_list.php?delete=<?= $admin['id'] ?>" class="btn-delete" onclick="return confirm('ยืนยันการลบแอดมินคนนี้?');">ลบ</a>
              <?php else: ?>
                <span style="color: #6b7280;">Super Admin</span>
              <?php endif; ?>
            <?php else: ?>
              <span>-</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <p style="font-size: 13px; color: #6b7280; margin-top: 15px;">* หมายเหตุ: ไม่สามารถลบ Super Admin (ID 1) ได้เพื่อความปลอดภัย</p>
  </div>
</main>

<?php include '../templates/footer_admin.php'; ?>