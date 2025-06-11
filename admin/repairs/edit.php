<?php
session_start();
require_once __DIR__ . '/../../includes/db.php'; 
require_once __DIR__ . '/../../includes/auth.php'; 

// VV ฟังก์ชัน e() ที่เราเพิ่มเข้ามา VV
function e($string) { 
  return htmlspecialchars((string) $string, ENT_QUOTES, 'UTF-8');
}
// ^^ จบส่วนฟังก์ชัน e() ^^

// if (!isAdmin()) { // Example auth check
//   header("Location: /admin/login.php"); // Or your admin login page
//   exit;
// }

if (!isset($_GET['id'])) {
  // Consider adding a session flash message here for better UX
  // $_SESSION['error_message'] = "ไม่พบรหัสผลงานที่ต้องการแก้ไข";
  header("Location: index.php"); // repairs list in admin
  exit;
}

$id = intval($_GET['id']);

// Fetch existing data including English fields
$stmt = $pdo->prepare("SELECT * FROM repairs WHERE id = ?");
$stmt->execute([$id]);
$repair = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$repair) {
  // Consider adding a session flash message here
  // $_SESSION['error_message'] = "ไม่พบข้อมูลผลงานนี้ (#{$id})";
  // For now, simple echo, but ideally redirect or show within admin layout
  http_response_code(404);
  echo "Could not find the repair work item with ID: " . e($id); // Basic error
  // You might want to include admin header/footer here for consistent layout on error
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Thai fields from POST, with fallback to existing data (though form fields are usually 'required')
  $title = $_POST['title'] ?? ($repair['title'] ?? ''); 
  $model = $_POST['model'] ?? ($repair['model'] ?? '');
  $issue = $_POST['issue'] ?? ($repair['issue'] ?? '');
  $fix_detail = $_POST['fix_detail'] ?? ($repair['fix_detail'] ?? '');
  $category = $_POST['category'] ?? ($repair['category'] ?? '');
  
  // English fields from POST, with fallback to existing English data or empty string
  $title_en = $_POST['title_en'] ?? ($repair['title_en'] ?? '');
  $issue_en = $_POST['issue_en'] ?? ($repair['issue_en'] ?? '');
  $fix_detail_en = $_POST['fix_detail_en'] ?? ($repair['fix_detail_en'] ?? '');

  $current_image = $repair['image']; // Keep old image by default

  // Handle new image upload
  if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK && !empty($_FILES['image']['name'])) {
    $newImageName = time() . '_' . preg_replace("/[^a-zA-Z0-9.\-\_]/", "_", basename($_FILES['image']['name']));
    // Ensure the path is correct relative to THIS script's location
    $targetPath = __DIR__ . '/../../uploads/' . $newImageName; 
    $uploadDir = dirname($targetPath);

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0775, true); // Create directory if it doesn't exist
    }

    if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
      // Delete old image if new one is uploaded successfully and old image exists and is not a placeholder
      if ($current_image && $current_image !== 'placeholder.png' && file_exists(__DIR__ . "/../../uploads/" . $current_image)) {
        unlink(__DIR__ . "/../../uploads/" . $current_image);
      }
      $current_image = $newImageName; // Update to new image name for the database
    } else {
      // Optional: Handle image upload error, maybe set a session message
      // $_SESSION['error_message'] = "เกิดข้อผิดพลาดในการอัปโหลดรูปภาพใหม่";
    }
  }

  // SQL UPDATE statement to include English fields
  $sqlUpdate = "UPDATE repairs SET 
                    title = ?, model = ?, issue = ?, fix_detail = ?, category = ?, image = ?,
                    title_en = ?, issue_en = ?, fix_detail_en = ? 
                WHERE id = ?";
  
  $stmtUpdate = $pdo->prepare($sqlUpdate);
  
  // Execute array to include English variables
  if ($stmtUpdate->execute([
    $title, $model, $issue, $fix_detail, $category, $current_image, 
    $title_en, $issue_en, $fix_detail_en, 
    $id
  ])) {
    // $_SESSION['success_message'] = "แก้ไขข้อมูลผลงานเรียบร้อยแล้ว"; // Optional success message
    header("Location: index.php"); // Redirect to the list of repairs
    exit;
  } else {
    // Optional: Handle DB update error
    // $_SESSION['error_message'] = "เกิดข้อผิดพลาดในการแก้ไขข้อมูล";
    // For debugging: echo "Error: " . implode(" | ", $stmtUpdate->errorInfo()); 
  }
}
?>

<?php include '../../templates/header_admin.php'; // Admin header ?>
<?php include '../../templates/sidebar_admin.php'; // Admin sidebar ?>

<main class="main" id="main-content">
  <div class="topbar">
    <button class="menu-btn" onclick="toggleSidebar()">☰</button>
    <span>แก้ไขผลงาน (Edit Repair Work #<?= e($id) ?>)</span>
    <a href="index.php" class="view-site">← กลับหน้ารายการ</a>
  </div>

  <h2 style="margin: 20px 0;">แก้ไขข้อมูลผลงาน (Edit Repair Work)</h2>

  <form action="edit.php?id=<?= e($id) ?>" method="POST" enctype="multipart/form-data" class="form-section">
    <fieldset>
        <legend>ข้อมูลภาษาไทย (Thai Content)</legend>
        <label for="title">ชื่อผลงาน (Title TH):</label>
        <input type="text" name="title" id="title" value="<?= e($repair['title'] ?? '') ?>" required>

        <label for="issue">ปัญหา (Issue TH):</label>
        <textarea name="issue" id="issue" rows="3" required><?= e($repair['issue'] ?? '') ?></textarea>

        <label for="fix_detail">รายละเอียดการซ่อม (Repair Details TH):</label>
        <textarea name="fix_detail" id="fix_detail" rows="5" required><?= e($repair['fix_detail'] ?? '') ?></textarea>
    </fieldset>

    <hr style="margin: 20px 0;">

    <fieldset>
        <legend>ข้อมูลภาษาอังกฤษ (English Content)</legend>
        <label for="title_en">ชื่อผลงาน (Title EN):</label>
        <input type="text" name="title_en" id="title_en" value="<?= e($repair['title_en'] ?? '') ?>">

        <label for="issue_en">ปัญหา (Issue EN):</label>
        <textarea name="issue_en" id="issue_en" rows="3"><?= e($repair['issue_en'] ?? '') ?></textarea>

        <label for="fix_detail_en">รายละเอียดการซ่อม (Repair Details EN):</label>
        <textarea name="fix_detail_en" id="fix_detail_en" rows="5"><?= e($repair['fix_detail_en'] ?? '') ?></textarea>
    </fieldset>
    
    <hr style="margin: 20px 0;">

    <fieldset>
        <legend>ข้อมูลทั่วไป (General Information)</legend>
        <label for="model">รุ่น (Model):</label>
        <input type="text" name="model" id="model" value="<?= e($repair['model'] ?? '') ?>" required>

        <label for="category">หมวดหมู่ (Category):</label>
        <input type="text" name="category" id="category" value="<?= e($repair['category'] ?? '') ?>"> 

        <label for="image">อัปโหลดรูปใหม่ (ถ้าต้องการเปลี่ยน) (New Image - Optional):</label>
        <input type="file" name="image" id="image" accept="image/*" onchange="previewImage(event)">
        
        <div id="imagePreview" style="margin-top:10px;">
          <?php if (!empty($repair['image'])): ?>
            <p style="margin-bottom: 5px;">รูปปัจจุบัน (Current Image):</p>
            <img src="../../uploads/<?= e($repair['image']) ?>" class="preview-thumb" alt="Current repair image" style="max-width:200px; max-height:200px; border-radius:8px;">
          <?php endif; ?>
        </div>
        <div id="newImagePreviewContainer" style="margin-top:10px;"></div> 
    </fieldset>

    <div style="margin-top: 20px;">
      <button type="submit">บันทึกการแก้ไข (Save Changes)</button>
      <a href="index.php" class="back-link" style="margin-left: 10px;">← ย้อนกลับ (Back)</a>
    </div>
  </form>
</main>

<?php include '../../templates/footer_admin.php'; // Admin footer ?>

<script>
function previewImage(event) {
  const input = event.target;
  // Target a new div for the *new* image preview so it doesn't remove the "Current Image" display
  const previewContainer = document.getElementById('newImagePreviewContainer'); 
  previewContainer.innerHTML = ''; // Clear previous *new* image preview

  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = function(e) {
      const img = document.createElement('img');
      img.src = e.target.result;
      img.style.maxWidth = '200px'; 
      img.style.maxHeight = '200px';
      img.style.borderRadius = '8px';
      img.style.marginTop = '10px';
      // img.className = 'preview-thumb'; // Can use the same class if styling is appropriate
      previewContainer.appendChild(img);
    }
    reader.readAsDataURL(input.files[0]);
  }
}

// Assuming toggleSidebar() is defined in one of your admin template includes or a global JS file
// function toggleSidebar() { ... } 
</script>