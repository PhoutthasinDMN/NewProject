<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// บังคับให้ล็อกอินก่อนเข้าใช้งาน
requireLogin();

// บังคับให้เป็น admin เท่านั้น
requireAdmin();

// ตัวแปรสำหรับเก็บข้อความแจ้งเตือน
$error = '';
$success = '';

// จำนวนผู้ใช้ต่อหน้า
$users_per_page = 10;

// หน้าปัจจุบัน
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page); // ต้องเป็นหน้า 1 ขึ้นไป

// คำนวณ offset สำหรับ SQL LIMIT
$offset = ($page - 1) * $users_per_page;

// ค้นหาผู้ใช้
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// การลบผู้ใช้
if (isset($_POST['delete_user']) && !empty($_POST['user_id'])) {
    $user_id = (int)$_POST['user_id'];
    
    // ป้องกันการลบตัวเอง
    if ($user_id === (int)$_SESSION['user_id']) {
        $error = alert("คุณไม่สามารถลบบัญชีของตัวเองได้", "danger");
    } else {
        // ลบผู้ใช้จากฐานข้อมูล
        $sql = "DELETE FROM users WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        
        if ($stmt->execute()) {
            $success = alert("ลบผู้ใช้เรียบร้อยแล้ว", "success");
        } else {
            $error = alert("เกิดข้อผิดพลาดในการลบผู้ใช้: " . $stmt->error, "danger");
        }
    }
}

// แก้ไขบทบาทผู้ใช้
if (isset($_POST['update_role']) && !empty($_POST['user_id']) && isset($_POST['role'])) {
    $user_id = (int)$_POST['user_id'];
    $role = $_POST['role'];
    
    // ตรวจสอบค่า role ว่าถูกต้อง
    if ($role !== 'user' && $role !== 'admin') {
        $error = alert("บทบาทไม่ถูกต้อง", "danger");
    } 
    // ป้องกันการลดสิทธิ์ตัวเอง
    elseif ($user_id === (int)$_SESSION['user_id'] && $role !== 'admin') {
        $error = alert("คุณไม่สามารถเปลี่ยนบทบาทของตัวเองได้", "danger");
    } else {
        // อัปเดตบทบาทในฐานข้อมูล
        $sql = "UPDATE users SET role = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $role, $user_id);
        
        if ($stmt->execute()) {
            $success = alert("อัปเดตบทบาทผู้ใช้เรียบร้อยแล้ว", "success");
        } else {
            $error = alert("เกิดข้อผิดพลาดในการอัปเดตบทบาทผู้ใช้: " . $stmt->error, "danger");
        }
    }
}

// ดึงจำนวนผู้ใช้ทั้งหมดเพื่อทำการแบ่งหน้า
$count_sql = "SELECT COUNT(*) as total FROM users";
$search_condition = "";

if (!empty($search)) {
    $search_param = "%$search%";
    $count_sql .= " WHERE username LIKE ? OR email LIKE ?";
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->bind_param("ss", $search_param, $search_param);
    $search_condition = " WHERE username LIKE ? OR email LIKE ?";
} else {
    $count_stmt = $conn->prepare($count_sql);
}

$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_users = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_users / $users_per_page);

// ดึงรายชื่อผู้ใช้
$sql = "SELECT id, username, email, role, created_at FROM users $search_condition ORDER BY id DESC LIMIT ?, ?";
$stmt = $conn->prepare($sql);

if (!empty($search)) {
    $search_param = "%$search%";
    $stmt->bind_param("ssii", $search_param, $search_param, $offset, $users_per_page);
} else {
    $stmt->bind_param("ii", $offset, $users_per_page);
}

$stmt->execute();
$result = $stmt->get_result();
$users = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html
  lang="en"
  class="light-style layout-menu-fixed"
  dir="ltr"
  data-theme="theme-default"
  data-assets-path="../assets/"
  data-template="vertical-menu-template-free"
>
  <head>
    <meta charset="utf-8" />
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0"
    />

    <title>Settings | User Management</title>

    <meta name="description" content="" />

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../assets/img/favicon/favicon.ico" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap"
      rel="stylesheet"
    />

    <!-- Icons. Uncomment required icon fonts -->
    <link rel="stylesheet" href="../assets/vendor/fonts/boxicons.css" />

    <!-- Core CSS -->
    <link rel="stylesheet" href="../assets/vendor/css/core.css" class="template-customizer-core-css" />
    <link rel="stylesheet" href="../assets/vendor/css/theme-default.css" class="template-customizer-theme-css" />
    <link rel="stylesheet" href="../assets/css/demo.css" />

    <!-- Vendors CSS -->
    <link rel="stylesheet" href="../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />

    <!-- Page CSS -->

    <!-- Helpers -->
    <script src="../assets/vendor/js/helpers.js"></script>

    <!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->
    <!--? Config:  Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file.  -->
    <script src="../assets/js/config.js"></script>
  </head>

  <body>
    <!-- Layout wrapper -->
    <div class="layout-wrapper layout-content-navbar">
      <div class="layout-container">
        <!-- Menu -->
        <aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
          <div class="app-brand demo">
            <a href="index.php" class="app-brand-link">
              <span class="app-brand-logo demo">
                <svg
                  width="25"
                  viewBox="0 0 25 42"
                  version="1.1"
                  xmlns="http://www.w3.org/2000/svg"
                  xmlns:xlink="http://www.w3.org/1999/xlink"
                >
                  <defs>
                    <path
                      d="M13.7918663,0.358365126 L3.39788168,7.44174259 C0.566865006,9.69408886 -0.379795268,12.4788597 0.557900856,15.7960551 C0.68998853,16.2305145 1.09562888,17.7872135 3.12357076,19.2293357 C3.8146334,19.7207684 5.32369333,20.3834223 7.65075054,21.2172976 L7.59773219,21.2525164 L2.63468769,24.5493413 C0.445452254,26.3002124 0.0884951797,28.5083815 1.56381646,31.1738486 C2.83770406,32.8170431 5.20850219,33.2640127 7.09180128,32.5391577 C8.347334,32.0559211 11.4559176,30.0011079 16.4175519,26.3747182 C18.0338572,24.4997857 18.6973423,22.4544883 18.4080071,20.2388261 C17.963753,17.5346866 16.1776345,15.5799961 13.0496516,14.3747546 L10.9194936,13.4715819 L18.6192054,7.984237 L13.7918663,0.358365126 Z"
                      id="path-1"
                    ></path>
                    <path
                      d="M5.47320593,6.00457225 C4.05321814,8.216144 4.36334763,10.0722806 6.40359441,11.5729822 C8.61520715,12.571656 10.0999176,13.2171421 10.8577257,13.5094407 L15.5088241,14.433041 L18.6192054,7.984237 C15.5364148,3.11535317 13.9273018,0.573395879 13.7918663,0.358365126 C13.5790555,0.511491653 10.8061687,2.3935607 5.47320593,6.00457225 Z"
                      id="path-3"
                    ></path>
                    <path
                      d="M7.50063644,21.2294429 L12.3234468,23.3159332 C14.1688022,24.7579751 14.397098,26.4880487 13.008334,28.506154 C11.6195701,30.5242593 10.3099883,31.790241 9.07958868,32.3040991 C5.78142938,33.4346997 4.13234973,34 4.13234973,34 C4.13234973,34 2.75489982,33.0538207 2.37032616e-14,31.1614621 C-0.55822714,27.8186216 -0.55822714,26.0572515 -4.05231404e-15,25.8773518 C0.83734071,25.6075023 2.77988457,22.8248993 3.3049379,22.52991 C3.65497346,22.3332504 5.05353963,21.8997614 7.50063644,21.2294429 Z"
                      id="path-4"
                    ></path>
                    <path
                      d="M20.6,7.13333333 L25.6,13.8 C26.2627417,14.6836556 26.0836556,15.9372583 25.2,16.6 C24.8538077,16.8596443 24.4327404,17 24,17 L14,17 C12.8954305,17 12,16.1045695 12,15 C12,14.5672596 12.1403557,14.1461923 12.4,13.8 L17.4,7.13333333 C18.0627417,6.24967773 19.3163444,6.07059163 20.2,6.73333333 C20.3516113,6.84704183 20.4862915,6.981722 20.6,7.13333333 Z"
                      id="path-5"
                    ></path>
                  </defs>
                  <g id="g-app-brand" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                    <g id="Brand-Logo" transform="translate(-27.000000, -15.000000)">
                      <g id="Icon" transform="translate(27.000000, 15.000000)">
                        <g id="Mask" transform="translate(0.000000, 8.000000)">
                          <mask id="mask-2" fill="white">
                            <use xlink:href="#path-1"></use>
                          </mask>
                          <use fill="#696cff" xlink:href="#path-1"></use>
                          <g id="Path-3" mask="url(#mask-2)">
                            <use fill="#696cff" xlink:href="#path-3"></use>
                            <use fill-opacity="0.2" fill="#FFFFFF" xlink:href="#path-3"></use>
                          </g>
                          <g id="Path-4" mask="url(#mask-2)">
                            <use fill="#696cff" xlink:href="#path-4"></use>
                            <use fill-opacity="0.2" fill="#FFFFFF" xlink:href="#path-4"></use>
                          </g>
                        </g>
                        <g
                          id="Triangle"
                          transform="translate(19.000000, 11.000000) rotate(-300.000000) translate(-19.000000, -11.000000) "
                        >
                          <use fill="#696cff" xlink:href="#path-5"></use>
                          <use fill-opacity="0.2" fill="#FFFFFF" xlink:href="#path-5"></use>
                        </g>
                      </g>
                    </g>
                  </g>
                </svg>
              </span>
              <span class="app-brand-text demo menu-text fw-bolder ms-2">Sneat</span>
            </a>

            <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-block d-xl-none">
              <i class="bx bx-chevron-left bx-sm align-middle"></i>
            </a>
          </div>

          <div class="menu-inner-shadow"></div>

          <ul class="menu-inner py-1">
            <!-- Dashboard -->
            <li class="menu-item">
              <a href="index.php" class="menu-link">
                <i class="menu-icon tf-icons bx bx-home-circle"></i>
                <div data-i18n="Analytics">Dashboard</div>
              </a>
            </li>

            <!-- Profile -->
            <li class="menu-item">
              <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-user"></i>
                <div data-i18n="Profile">Profile</div>
              </a>
              <ul class="menu-sub">
                <li class="menu-item">
                  <a href="profile.php" class="menu-link">
                    <div data-i18n="View Profile">View Profile</div>
                  </a>
                </li>
                <li class="menu-item">
                  <a href="profile-edit.php" class="menu-link">
                    <div data-i18n="Edit Profile">Edit Profile</div>
                  </a>
                </li>
              </ul>
            </li>

            <!-- Settings (Admin Only) -->
            <li class="menu-item active">
              <a href="settings.php" class="menu-link">
                <i class="menu-icon tf-icons bx bx-cog"></i>
                <div data-i18n="Settings">Settings</div>
              </a>
            </li>

            <!-- Logout -->
            <li class="menu-item">
              <a href="../auth/logout.php" class="menu-link">
                <i class="menu-icon tf-icons bx bx-log-out"></i>
                <div data-i18n="Logout">Logout</div>
              </a>
            </li>
          </ul>
        </aside>
        <!-- / Menu -->

        <!-- Layout container -->
        <div class="layout-page">
          
          <!-- Navbar -->
         <?php include '../includes/sidebar.php'; ?>
        <!-- / Navbar -->

          <!-- Content wrapper -->
          <div class="content-wrapper">
            <!-- Content -->
            <div class="container-xxl flex-grow-1 container-p-y">
              <h4 class="fw-bold py-3 mb-4">
                <span class="text-muted fw-light">Settings /</span> User Management
              </h4>

              <div class="row">
                <div class="col-md-12">
                  <!-- User Management -->
                  <div class="card mb-4">
                    <h5 class="card-header">User Management</h5>
                    
                    <!-- Alerts -->
                    <?php if (!empty($error)): ?>
                      <?php echo $error; ?>
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                      <?php echo $success; ?>
                    <?php endif; ?>
                    
                    <!-- เพิ่มผู้ใช้ใหม่ -->
                    <div class="card-body">
                      <div class="mb-3">
                        <a href="user-add.php" class="btn btn-primary">
                          <i class="bx bx-user-plus me-1"></i> Add New User
                        </a>
                      </div>
                      
                      <!-- ตารางผู้ใช้ -->
                      <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                          <thead>
                            <tr>
                              <th>ID</th>
                              <th>Username</th>
                              <th>Email</th>
                              <th>Role</th>
                              <th>Created At</th>
                              <th>Actions</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php if (empty($users)): ?>
                              <tr>
                                <td colspan="6" class="text-center">No users found</td>
                              </tr>
                            <?php else: ?>
                              <?php foreach ($users as $user): ?>
                                <tr>
                                  <td><?php echo $user['id']; ?></td>
                                  <td><?php echo htmlspecialchars($user['username']); ?></td>
                                  <td><?php echo htmlspecialchars($user['email']); ?></td>
                                  <td>
                                    <form action="" method="post" class="d-inline">
                                      <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                      <select name="role" class="form-select form-select-sm" onchange="this.form.submit()" <?php echo $user['id'] == $_SESSION['user_id'] ? 'disabled' : ''; ?>>
                                        <option value="user" <?php echo $user['role'] == 'user' ? 'selected' : ''; ?>>User</option>
                                        <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                                      </select>
                                      <input type="hidden" name="update_role" value="1">
                                    </form>
                                  </td>
                                  <td><?php echo date('F j, Y', strtotime($user['created_at'])); ?></td>
                                  <td>
                                    <a href="user-edit.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-primary">
                                      <i class="bx bx-edit-alt"></i>
                                    </a>
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                      <form action="" method="post" class="d-inline" onsubmit="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบผู้ใช้นี้?');">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" name="delete_user" class="btn btn-sm btn-danger">
                                          <i class="bx bx-trash"></i>
                                        </button>
                                      </form>
                                    <?php endif; ?>
                                  </td>
                                </tr>
                              <?php endforeach; ?>
                            <?php endif; ?>
                          </tbody>
                        </table>
                      </div>
                      
                      <!-- Pagination -->
                      <?php if ($total_pages > 1): ?>
                        <div class="mt-3">
                          <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-center">
                              <?php if ($page > 1): ?>
                                <li class="page-item">
                                  <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                                    <i class="bx bx-chevron-left"></i>
                                  </a>
                                </li>
                              <?php endif; ?>
                              
                              <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                  <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                                    <?php echo $i; ?>
                                  </a>
                                </li>
                              <?php endfor; ?>
                              
                              <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                  <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                                    <i class="bx bx-chevron-right"></i>
                                  </a>
                                </li>
                              <?php endif; ?>
                            </ul>
                          </nav>
                        </div>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <!-- / Content -->

            <!-- Footer -->
           <?php include '../includes/footer.php'; ?>
            <!-- / Footer -->

            <div class="content-backdrop fade"></div>
          </div>
          <!-- Content wrapper -->
        </div>
        <!-- / Layout page -->
      </div>

      <!-- Overlay -->
      <div class="layout-overlay layout-menu-toggle"></div>
    </div>
    <!-- / Layout wrapper -->

    <!-- Core JS -->
    <!-- build:js assets/vendor/js/core.js -->
    <script src="../assets/vendor/libs/jquery/jquery.js"></script>
    <script src="../assets/vendor/libs/popper/popper.js"></script>
    <script src="../assets/vendor/js/bootstrap.js"></script>
    <script src="../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>

    <script src="../assets/vendor/js/menu.js"></script>
    <!-- endbuild -->

    <!-- Vendors JS -->

    <!-- Main JS -->
    <script src="../assets/js/main.js"></script>
  </body>
</html>