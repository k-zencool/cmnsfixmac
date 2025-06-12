<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

// --- SECURITY CHECK: ONLY SUPER ADMIN (ID=1) CAN ACCESS ---
if (!isset($_SESSION['admin_id']) || $_SESSION['admin_id'] != 1) {
    // ถ้าคนดูไม่ใช่ Super Admin ให้ออกไปเลย
    die('Access Denied. You do not have permission to access this page.');
}

// ดึง ID ของ user ที่จะแก้จาก URL
$edit_user_id = $_GET['id'] ?? 0;
// ห้าม Super Admin แก้รหัสตัวเองในหน้านี้ และต้องมี ID ส่งมา
if (!$edit_user_id || $edit_user_id == 1) { 
    header('Location: admin_list.php');
    exit;
}

// ดึงข้อมูล user ที่จะแก้ไขมาโชว์ชื่อ
$stmt = $pdo->prepare("SELECT * FROM admin_users WHERE id = ?");
$stmt->execute([$edit_user_id]);
$user_to_edit = $stmt->fetch();

// ถ้าหา user ไม่เจอ ให้เด้งกลับ
if (!$user_to_edit) {
    header('Location: admin_list.php');
    exit;
}

$error = '';
$success = '';

// ตรวจสอบเมื่อมีการกดปุ่ม Submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($new_password) || mb_strlen($new_password) < 8) {
        $error = "รหัสผ่านใหม่ต้องมีอย่างน้อย 8 ตัวอักษร";
    } elseif ($new_password !== $confirm_password) {
        $error = "รหัสผ่านใหม่ทั้งสองช่องไม่ตรงกัน";
    } else {
        // ทุกอย่างถูกต้อง... ทำการ Hash รหัสผ่านใหม่
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        // อัปเดตลงฐานข้อมูล
        $updateStmt = $pdo->prepare("UPDATE admin_users SET password = ? WHERE id = ?");
        if ($updateStmt->execute([$hashed_password, $edit_user_id])) {
            $success = "เปลี่ยนรหัสผ่านสำหรับ '" . htmlspecialchars($user_to_edit['username']) . "' เรียบร้อยแล้ว!";
        } else {
            $error = "เกิดข้อผิดพลาดในการอัปเดตฐานข้อมูล";
        }
    }
}

include '../templates/header_admin.php';
include '../templates/sidebar_admin.php';
?>

<main class="main" id="main-content">
  <div class="topbar">
    <span>แก้ไขแอดมิน</span>
    <a href="admin_list.php" class="view-site">← กลับหน้ารายชื่อ</a>
  </div>

  <div class="form-section" style="max-width: 600px; padding: 40px;">
    <h2 style="font-size: 24px; margin-bottom: 20px;">
      📝 แก้ไขรหัสผ่านสำหรับ: <?= htmlspecialchars($user_to_edit['username']) ?>
    </h2>

    <?php if ($error): ?><p class="error" style="color:red; background-color: #ffebee; padding: 10px; border-radius: 5px;"><?= $error ?></p><?php endif; ?>
    <?php if ($success): ?><p class="success" style="color:green; background-color: #e8f5e9; padding: 10px; border-radius: 5px;"><?= $success ?></p><?php endif; ?>

    <form method="POST">
      <label for="new_password">รหัสผ่านใหม่ (New Password):</label>
      <input type="password" name="new_password" id="new_password" required>

      <label for="confirm_password">ยืนยันรหัสผ่านใหม่ (Confirm New Password):</label>
      <input type="password" name="confirm_password" id="confirm_password" required>
      
      <div style="margin-top: 20px;">
        <button type="submit">ตั้งรหัสผ่านใหม่ (Reset Password)</button>
      </div>
    </form>
  </div>
</main>

<?php include '../templates/footer_admin.php'; ?>