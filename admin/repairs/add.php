<?php
session_start();
require_once __DIR__ . '/../../includes/db.php'; // Assuming db.php is correctly located
require_once __DIR__ . '/../../includes/auth.php'; // Assuming auth.php handles admin authentication

// Check if user is authenticated (example, adjust to your auth logic)
// if (!isAdmin()) {
//    header("Location: /admin/login.php"); // Or your admin login page
//    exit;
// }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Thai fields
  $title = $_POST['title'] ?? '';
  $model = $_POST['model'] ?? '';
  $issue = $_POST['issue'] ?? '';
  $fix_detail = $_POST['fix_detail'] ?? '';
  $category = $_POST['category'] ?? '';

  // NEW: English fields
  $title_en = $_POST['title_en'] ?? '';
  $issue_en = $_POST['issue_en'] ?? '';
  $fix_detail_en = $_POST['fix_detail_en'] ?? '';

  $image = ''; // Default empty image
  if (!empty($_FILES['image']['name'])) {
    $filename = time() . '_' . preg_replace("/[^a-zA-Z0-9.\-\_]/", "_", basename($_FILES['image']['name'])); // Sanitize filename
    $path = '../../uploads/' . $filename; // Path relative to this admin script
    
    // Create uploads directory if it doesn't exist
    $uploadDir = dirname($path);
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0775, true);
    }

    if (move_uploaded_file($_FILES['image']['tmp_name'], $path)) {
      $image = $filename;
    } else {
      // Optional: Add error handling for upload failure
      // For now, $image remains empty or previous value if editing
      // echo "Error uploading image.";
    }
  }

  // UPDATED: SQL INSERT statement to include English fields
  $stmt = $pdo->prepare("INSERT INTO repairs (title, model, issue, fix_detail, image, category, created_at, title_en, issue_en, fix_detail_en) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?)");
  
  // UPDATED: Execute array to include English variables
  if ($stmt->execute([$title, $model, $issue, $fix_detail, $image, $category, $title_en, $issue_en, $fix_detail_en])) {
    $_SESSION['success_message'] = "ผลงานซ่อมถูกเพิ่มเรียบร้อยแล้ว"; // Optional: Success message
    header("Location: index.php"); // Redirect to the list of repairs in admin
    exit;
  } else {
    // Optional: Add error handling for DB insert failure
    // $_SESSION['error_message'] = "เกิดข้อผิดพลาดในการเพิ่มผลงานซ่อม";
    // echo "Error: " . $stmt->errorInfo()[2]; // For debugging
  }
}
?>

<?php include '../../templates/header_admin.php'; // Admin header ?>
<?php include '../../templates/sidebar_admin.php'; // Admin sidebar ?>

<main class="main" id="main-content">
  <div class="topbar">
    <button class="menu-btn" onclick="toggleSidebar()">☰</button>
    <span>เพิ่มผลงานใหม่</span>
    <a href="index.php" class="view-site">← กลับหน้ารายการ</a>
  </div>

  <h2 style="margin: 20px 0;">เพิ่มข้อมูลผลงาน (Add New Repair Work)</h2>
  <form action="" method="POST" enctype="multipart/form-data" class="form-section">
    
    <fieldset>
        <legend>ข้อมูลภาษาไทย (Thai Content)</legend>
        <label for="title">ชื่อผลงาน (Title TH):</label>
        <input type="text" name="title" id="title" required>

        <label for="issue">ปัญหา (Issue TH):</label>
        <textarea name="issue" id="issue" rows="3" required></textarea>

        <label for="fix_detail">รายละเอียดการซ่อม (Repair Details TH):</label>
        <textarea name="fix_detail" id="fix_detail" rows="5" required></textarea>
    </fieldset>

    <hr style="margin: 20px 0;">

    <fieldset>
        <legend>ข้อมูลภาษาอังกฤษ (English Content)</legend>
        <label for="title_en">ชื่อผลงาน (Title EN):</label>
        <input type="text" name="title_en" id="title_en"> <label for="issue_en">ปัญหา (Issue EN):</label>
        <textarea name="issue_en" id="issue_en" rows="3"></textarea>

        <label for="fix_detail_en">รายละเอียดการซ่อม (Repair Details EN):</label>
        <textarea name="fix_detail_en" id="fix_detail_en" rows="5"></textarea>
    </fieldset>
    
    <hr style="margin: 20px 0;">

    <fieldset>
        <legend>ข้อมูลทั่วไป (General Information)</legend>
        <label for="model">รุ่น (Model):</label>
        <input type="text" name="model" id="model" required>

        <label for="category">หมวดหมู่ (Category):</label>
        <input type="text" name="category" id="category"> 
        <label for="image">อัปโหลดรูปภาพหลัก (Main Image):</label>
        <input type="file" name="image" id="image" accept="image/*" onchange="previewImage(event)">
        <div id="imagePreview" style="margin-top:10px;"></div>
    </fieldset>

    <div style="margin-top: 20px;">
      <button type="submit">บันทึกข้อมูล (Save Data)</button>
      <a href="index.php" class="back-link" style="margin-left: 10px;">← ย้อนกลับ (Back)</a>
    </div>
  </form>
</main>

<?php include '../../templates/footer_admin.php'; // Admin footer ?>

<script>
function previewImage(event) {
  const input = event.target;
  const previewContainer = document.getElementById('imagePreview');
  previewContainer.innerHTML = ''; // Clear previous preview

  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = function(e) {
      const img = document.createElement('img');
      img.src = e.target.result;
      img.style.maxWidth = '200px'; // Control preview size
      img.style.maxHeight = '200px';
      img.style.borderRadius = '8px';
      img.style.marginTop = '10px';
      previewContainer.appendChild(img);
    }
    reader.readAsDataURL(input.files[0]);
  }
}

// Basic sidebar toggle function (if not already in your admin scripts)
// function toggleSidebar() {
//   const sidebar = document.getElementById('admin-sidebar'); // Adjust ID if needed
//   const mainContent = document.getElementById('main-content');
//   if (sidebar && mainContent) {
//     sidebar.classList.toggle('collapsed');
//     mainContent.classList.toggle('expanded');
//   }
// }
</script>