<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

requireLogin();

// ตั้งค่าสำหรับ includes
$assets_path = '../assets/';
$page_title = 'Nurses Management';
$extra_css = ['../assets/css/dashboard.css'];

$success_message = '';
$error_message = '';
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$nurse_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $department = trim($_POST['department']);
    $position = trim($_POST['position']);
    $education = trim($_POST['education']);
    $address = trim($_POST['address']);
    $date_of_birth = $_POST['date_of_birth'];
    $gender = $_POST['gender'];
    $emergency_contact = trim($_POST['emergency_contact']);
    $bio = trim($_POST['bio']);
    $certifications = trim($_POST['certifications']);
    $status = $_POST['status'];
    
    // Validation
    if (empty($first_name) || empty($last_name) || empty($email) || empty($phone)) {
        $error_message = "Please fill in all required fields.";
    } else {
        if ($action == 'add') {
            // Check if email already exists
            $check_email = $conn->prepare("SELECT id FROM nurses WHERE email = ?");
            $check_email->bind_param("s", $email);
            $check_email->execute();
            
            if ($check_email->get_result()->num_rows > 0) {
                $error_message = "Email already exists. Please use a different email.";
            } else {
                // Insert new nurse
                $sql = "INSERT INTO nurses (first_name, last_name, email, phone, department, position, education, address, date_of_birth, gender, emergency_contact, bio, certifications, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssssssssssss", $first_name, $last_name, $email, $phone, $department, $position, $education, $address, $date_of_birth, $gender, $emergency_contact, $bio, $certifications, $status);
                
                if ($stmt->execute()) {
                    $success_message = "Nurse added successfully!";
                    $_POST = array();
                } else {
                    $error_message = "Error adding nurse. Please try again.";
                }
            }
        } elseif ($action == 'edit' && $nurse_id > 0) {
            // Check if email already exists for other nurses
            $check_email = $conn->prepare("SELECT id FROM nurses WHERE email = ? AND id != ?");
            $check_email->bind_param("si", $email, $nurse_id);
            $check_email->execute();
            
            if ($check_email->get_result()->num_rows > 0) {
                $error_message = "Email already exists. Please use a different email.";
            } else {
                // Update nurse
                $sql = "UPDATE nurses SET first_name = ?, last_name = ?, email = ?, phone = ?, department = ?, position = ?, education = ?, address = ?, date_of_birth = ?, gender = ?, emergency_contact = ?, bio = ?, certifications = ?, status = ?, updated_at = NOW() WHERE id = ?";
                
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssssssssssssi", $first_name, $last_name, $email, $phone, $department, $position, $education, $address, $date_of_birth, $gender, $emergency_contact, $bio, $certifications, $status, $nurse_id);
                
                if ($stmt->execute()) {
                    $success_message = "Nurse updated successfully!";
                } else {
                    $error_message = "Error updating nurse. Please try again.";
                }
            }
        }
    }
}

// Handle delete action
if ($action == 'delete' && $nurse_id > 0) {
    $stmt = $conn->prepare("DELETE FROM nurses WHERE id = ?");
    $stmt->bind_param("i", $nurse_id);
    
    if ($stmt->execute()) {
        $success_message = "Nurse deleted successfully!";
        $action = 'list'; // Redirect to list after delete
    } else {
        $error_message = "Error deleting nurse. Please try again.";
    }
}

// Get nurse data for editing
$nurse_data = null;
if ($action == 'edit' && $nurse_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM nurses WHERE id = ?");
    $stmt->bind_param("i", $nurse_id);
    $stmt->execute();
    $nurse_data = $stmt->get_result()->fetch_assoc();
    
    if (!$nurse_data) {
        $error_message = "Nurse not found.";
        $action = 'list';
    }
}

// Get all nurses for listing
$nurses = [];
if ($action == 'list') {
    $result = $conn->query("SELECT * FROM nurses ORDER BY created_at DESC");
    $nurses = $result->fetch_all(MYSQLI_ASSOC);
}

// Include Header และ Sidebar
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<!-- Layout Page -->
<div class="layout-page">
    <?php include '../includes/navbar.php'; ?>

    <!-- Content wrapper -->
    <div class="content-wrapper">
        <div class="container-xxl flex-grow-1 container-p-y">
            
            <!-- Header -->
            <div class="row">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h4 class="mb-1">
                                <?php if ($action == 'add'): ?>
                                    Add New Nurse
                                <?php elseif ($action == 'edit'): ?>
                                    Edit Nurse
                                <?php else: ?>
                                    Nurses Management
                                <?php endif; ?>
                            </h4>
                            <p class="text-muted">
                                <?php if ($action == 'add'): ?>
                                    Register a new nurse in the system
                                <?php elseif ($action == 'edit'): ?>
                                    Update nurse information
                                <?php else: ?>
                                    Manage nurses in the system
                                <?php endif; ?>
                            </p>
                        </div>
                        <div>
                            <?php if ($action == 'list'): ?>
                                <a href="?action=add" class="btn btn-primary">
                                    <i class="bx bx-plus me-2"></i>Add New Nurse
                                </a>
                            <?php else: ?>
                                <a href="nurses_action.php" class="btn btn-outline-secondary">
                                    <i class="bx bx-arrow-back me-2"></i>Back to List
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alert Messages -->
            <?php if (!empty($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bx bx-check-circle me-2"></i>
                <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bx bx-error-circle me-2"></i>
                <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <?php if ($action == 'list'): ?>
                <!-- Nurses Table -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="bx bx-group me-2 text-primary"></i>All Nurses (<?php echo count($nurses); ?>)</h5>
                                <div class="d-flex gap-2">
                                    <input type="text" id="searchInput" class="form-control" placeholder="Search nurses..." style="width: 250px;">
                                    <select id="departmentFilter" class="form-select" style="width: 200px;">
                                        <option value="">All Departments</option>
                                        <option value="Emergency Department">Emergency Department</option>
                                        <option value="Intensive Care Unit">Intensive Care Unit</option>
                                        <option value="Pediatrics">Pediatrics</option>
                                        <option value="Surgery">Surgery</option>
                                        <option value="Maternity">Maternity</option>
                                        <option value="Internal Medicine">Internal Medicine</option>
                                        <option value="Orthopedics">Orthopedics</option>
                                        <option value="Cardiology">Cardiology</option>
                                    </select>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <?php if (empty($nurses)): ?>
                                    <div class="text-center py-5">
                                        <i class="bx bx-user-check display-1 text-muted"></i>
                                        <h5 class="mt-3 text-muted">No nurses found</h5>
                                        <p class="text-muted">Start by adding your first nurse</p>
                                        <a href="?action=add" class="btn btn-primary">
                                            <i class="bx bx-plus me-2"></i>Add First Nurse
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0" id="nursesTable">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Nurse</th>
                                                    <th>Department</th>
                                                    <th>Position</th>
                                                    <th>Contact</th>
                                                    <th>Education</th>
                                                    <th>Status</th>
                                                    <th width="120">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($nurses as $nurse): ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="avatar avatar-md me-3">
                                                                <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                                                                    <span class="fw-bold"><?php echo strtoupper(substr($nurse['first_name'], 0, 1) . substr($nurse['last_name'], 0, 1)); ?></span>
                                                                </div>
                                                            </div>
                                                            <div>
                                                                <h6 class="mb-1"><?php echo htmlspecialchars($nurse['first_name'] . ' ' . $nurse['last_name']); ?></h6>
                                                                <small class="text-muted"><?php echo htmlspecialchars($nurse['email']); ?></small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-info"><?php echo htmlspecialchars($nurse['department']); ?></span>
                                                    </td>
                                                    <td>
                                                        <small class="d-block"><?php echo htmlspecialchars($nurse['position']); ?></small>
                                                        <?php if ($nurse['education']): ?>
                                                            <small class="text-muted"><?php echo htmlspecialchars(substr($nurse['education'], 0, 30) . (strlen($nurse['education']) > 30 ? '...' : '')); ?></small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <div>
                                                            <small class="d-block"><i class="bx bx-phone me-1"></i><?php echo htmlspecialchars($nurse['phone']); ?></small>
                                                            <?php if ($nurse['emergency_contact']): ?>
                                                                <small class="text-muted"><i class="bx bx-phone-call me-1"></i><?php echo htmlspecialchars($nurse['emergency_contact']); ?></small>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <small><?php echo $nurse['education'] ? htmlspecialchars($nurse['education']) : '-'; ?></small>
                                                        <br><small class="text-muted">Added <?php echo date('M Y', strtotime($nurse['created_at'])); ?></small>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $status_colors = [
                                                            'Active' => 'success',
                                                            'Inactive' => 'danger',
                                                            'On Leave' => 'warning'
                                                        ];
                                                        $color = $status_colors[$nurse['status']] ?? 'secondary';
                                                        ?>
                                                        <span class="badge bg-<?php echo $color; ?>"><?php echo htmlspecialchars($nurse['status']); ?></span>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <a href="?action=edit&id=<?php echo $nurse['id']; ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                                                                <i class="bx bx-edit"></i>
                                                            </a>
                                                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmDelete(<?php echo $nurse['id']; ?>, '<?php echo htmlspecialchars($nurse['first_name'] . ' ' . $nurse['last_name']); ?>')" title="Delete">
                                                                <i class="bx bx-trash"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

            <?php else: ?>
                <!-- Nurse Form -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="bx bx-user-check me-2 text-primary"></i>
                                    <?php echo $action == 'add' ? 'Add Nurse' : 'Edit Nurse'; ?>
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="" class="row g-3">
                                    
                                    <!-- Personal Information -->
                                    <div class="col-12">
                                        <h6 class="text-primary mb-3"><i class="bx bx-user me-2"></i>Personal Information</h6>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo $nurse_data ? htmlspecialchars($nurse_data['first_name']) : (isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''); ?>" required>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo $nurse_data ? htmlspecialchars($nurse_data['last_name']) : (isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''); ?>" required>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="date_of_birth" class="form-label">Date of Birth</label>
                                        <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" value="<?php echo $nurse_data ? $nurse_data['date_of_birth'] : (isset($_POST['date_of_birth']) ? $_POST['date_of_birth'] : ''); ?>">
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="gender" class="form-label">Gender</label>
                                        <select class="form-select" id="gender" name="gender">
                                            <option value="">Select Gender</option>
                                            <option value="Male" <?php echo ($nurse_data && $nurse_data['gender'] == 'Male') || (isset($_POST['gender']) && $_POST['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                                            <option value="Female" <?php echo ($nurse_data && $nurse_data['gender'] == 'Female') || (isset($_POST['gender']) && $_POST['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                                            <option value="Other" <?php echo ($nurse_data && $nurse_data['gender'] == 'Other') || (isset($_POST['gender']) && $_POST['gender'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                                        </select>
                                    </div>

                                    <!-- Contact Information -->
                                    <div class="col-12 mt-4">
                                        <h6 class="text-primary mb-3"><i class="bx bx-phone me-2"></i>Contact Information</h6>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo $nurse_data ? htmlspecialchars($nurse_data['email']) : (isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''); ?>" required>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="phone" class="form-label">Phone <span class="text-danger">*</span></label>
                                        <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo $nurse_data ? htmlspecialchars($nurse_data['phone']) : (isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''); ?>" required>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="emergency_contact" class="form-label">Emergency Contact</label>
                                        <input type="tel" class="form-control" id="emergency_contact" name="emergency_contact" value="<?php echo $nurse_data ? htmlspecialchars($nurse_data['emergency_contact']) : (isset($_POST['emergency_contact']) ? htmlspecialchars($_POST['emergency_contact']) : ''); ?>">
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="address" class="form-label">Address</label>
                                        <input type="text" class="form-control" id="address" name="address" value="<?php echo $nurse_data ? htmlspecialchars($nurse_data['address']) : (isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''); ?>">
                                    </div>

                                    <!-- Professional Information -->
                                    <div class="col-12 mt-4">
                                        <h6 class="text-primary mb-3"><i class="bx bx-briefcase me-2"></i>Professional Information</h6>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="department" class="form-label">Department</label>
                                        <select class="form-select" id="department" name="department">
                                            <option value="">Select Department</option>
                                            <?php 
                                            $departments = ['Emergency Department', 'Intensive Care Unit', 'Pediatrics', 'Surgery', 'Maternity', 'Internal Medicine', 'Orthopedics', 'Cardiology', 'Neurology', 'Oncology'];
                                            foreach ($departments as $dept): 
                                                $selected = ($nurse_data && $nurse_data['department'] == $dept) || (isset($_POST['department']) && $_POST['department'] == $dept) ? 'selected' : '';
                                            ?>
                                                <option value="<?php echo $dept; ?>" <?php echo $selected; ?>><?php echo $dept; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="position" class="form-label">Position</label>
                                        <select class="form-select" id="position" name="position">
                                            <option value="">Select Position</option>
                                            <?php 
                                            $positions = ['Staff Nurse', 'Head Nurse', 'Charge Nurse', 'ICU Nurse', 'OR Nurse', 'ER Nurse', 'Pediatric Nurse', 'Midwife', 'Nurse Supervisor', 'Clinical Nurse Specialist'];
                                            foreach ($positions as $pos): 
                                                $selected = ($nurse_data && $nurse_data['position'] == $pos) || (isset($_POST['position']) && $_POST['position'] == $pos) ? 'selected' : '';
                                            ?>
                                                <option value="<?php echo $pos; ?>" <?php echo $selected; ?>><?php echo $pos; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="status" class="form-label">Status</label>
                                        <select class="form-select" id="status" name="status">
                                            <option value="Active" <?php echo ($nurse_data && $nurse_data['status'] == 'Active') || (isset($_POST['status']) && $_POST['status'] == 'Active') || (!$nurse_data && !isset($_POST['status'])) ? 'selected' : ''; ?>>Active</option>
                                            <option value="Inactive" <?php echo ($nurse_data && $nurse_data['status'] == 'Inactive') || (isset($_POST['status']) && $_POST['status'] == 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                                            <option value="On Leave" <?php echo ($nurse_data && $nurse_data['status'] == 'On Leave') || (isset($_POST['status']) && $_POST['status'] == 'On Leave') ? 'selected' : ''; ?>>On Leave</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-12">
                                        <label for="education" class="form-label">Education</label>
                                        <input type="text" class="form-control" id="education" name="education" value="<?php echo $nurse_data ? htmlspecialchars($nurse_data['education']) : (isset($_POST['education']) ? htmlspecialchars($_POST['education']) : ''); ?>" placeholder="e.g., Bachelor of Nursing Science">
                                    </div>
                                    
                                    <div class="col-12">
                                        <label for="certifications" class="form-label">Certifications</label>
                                        <textarea class="form-control" id="certifications" name="certifications" rows="3" placeholder="List relevant certifications (e.g., BLS, ACLS, PALS)"><?php echo $nurse_data ? htmlspecialchars($nurse_data['certifications']) : (isset($_POST['certifications']) ? htmlspecialchars($_POST['certifications']) : ''); ?></textarea>
                                    </div>
                                    
                                    <div class="col-12">
                                        <label for="bio" class="form-label">Biography</label>
                                        <textarea class="form-control" id="bio" name="bio" rows="4" placeholder="Brief description about the nurse..."><?php echo $nurse_data ? htmlspecialchars($nurse_data['bio']) : (isset($_POST['bio']) ? htmlspecialchars($_POST['bio']) : ''); ?></textarea>
                                    </div>

                                    <!-- Form Actions -->
                                    <div class="col-12 mt-4">
                                        <hr>
                                        <div class="d-flex justify-content-between">
                                            <a href="nurses_action.php" class="btn btn-outline-secondary">
                                                <i class="bx bx-arrow-back me-2"></i>Back to List
                                            </a>
                                            <div>
                                                <button type="reset" class="btn btn-outline-warning me-2">
                                                    <i class="bx bx-refresh me-2"></i>Reset Form
                                                </button>
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="bx bx-save me-2"></i><?php echo $action == 'add' ? 'Add Nurse' : 'Update Nurse'; ?>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <strong id="nurseName"></strong>?</p>
                <p class="text-muted">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="deleteLink" class="btn btn-danger">Delete Nurse</a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Search and filter functionality
    const searchInput = document.getElementById('searchInput');
    const departmentFilter = document.getElementById('departmentFilter');
    const table = document.getElementById('nursesTable');
    
    if (searchInput && table) {
        function filterTable() {
            const searchTerm = searchInput.value.toLowerCase();
            const selectedDepartment = departmentFilter ? departmentFilter.value : '';
            const rows = table.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const nurseName = row.cells[0].textContent.toLowerCase();
                const email = row.cells[0].textContent.toLowerCase();
                const department = row.cells[1].textContent;
                const phone = row.cells[3].textContent.toLowerCase();
                
                const matchesSearch = nurseName.includes(searchTerm) || 
                                    email.includes(searchTerm) || 
                                    phone.includes(searchTerm);
                const matchesDepartment = !selectedDepartment || 
                                       department.includes(selectedDepartment);
                
                row.style.display = matchesSearch && matchesDepartment ? '' : 'none';
            });
        }
        
        searchInput.addEventListener('input', filterTable);
        if (departmentFilter) {
            departmentFilter.addEventListener('change', filterTable);
        }
    }

    // Form validation
    const form = document.querySelector('form');
    if (form) {
        const requiredFields = form.querySelectorAll('[required]');
        
        form.addEventListener('submit', function(e) {
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add('is-invalid');
                    isValid = false;
                } else {
                    field.classList.remove('is-invalid');
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields.');
            }
        });

        // Email validation
        const emailField = document.getElementById('email');
        if (emailField) {
            emailField.addEventListener('blur', function() {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (this.value && !emailRegex.test(this.value)) {
                    this.classList.add('is-invalid');
                } else {
                    this.classList.remove('is-invalid');
                }
            });
        }

        // Phone validation
        const phoneField = document.getElementById('phone');
        if (phoneField) {
            phoneField.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9\s\-\(\)\+]/g, '');
            });
        }

        // License number formatting
        const licenseField = document.getElementById('license_number');
        if (licenseField) {
            licenseField.addEventListener('input', function() {
                let value = this.value.toUpperCase();
                // Remove any characters that aren't letters, numbers, or hyphens
                value = value.replace(/[^A-Z0-9\-]/g, '');
                this.value = value;
            });
        }
    }

    // Auto-dismiss alerts
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            if (alert && alert.parentNode) {
                alert.style.opacity = '0';
                setTimeout(() => {
                    if (alert.parentNode) {
                        alert.parentNode.removeChild(alert);
                    }
                }, 300);
            }
        }, 5000);
    });
});

// Delete confirmation
function confirmDelete(id, name) {
    document.getElementById('nurseName').textContent = name;
    document.getElementById('deleteLink').href = '?action=delete&id=' + id;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>

<?php include '../includes/footer.php'; ?>