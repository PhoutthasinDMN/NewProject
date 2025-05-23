<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

requireLogin();

// ตั้งค่าสำหรับ includes
$assets_path = '../assets/';
$page_title = 'Doctors Management';
$extra_css = ['../assets/css/dashboard.css'];

$success_message = '';
$error_message = '';
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$doctor_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $specialization = trim($_POST['specialization']);
    $education = trim($_POST['education']);
    $address = trim($_POST['address']);
    $date_of_birth = $_POST['date_of_birth'];
    $gender = $_POST['gender'];
    $emergency_contact = trim($_POST['emergency_contact']);
    $bio = trim($_POST['bio']);
    
    // Validation
    if (empty($first_name) || empty($last_name) || empty($email) || empty($phone) || empty($specialization)) {
        $error_message = "Please fill in all required fields.";
    } else {
        if ($action == 'add') {
            // Check if email already exists
            $check_email = $conn->prepare("SELECT id FROM doctors WHERE email = ?");
            $check_email->bind_param("s", $email);
            $check_email->execute();
            
            if ($check_email->get_result()->num_rows > 0) {
                $error_message = "Email already exists. Please use a different email.";
            } else {
                // Insert new doctor
                $sql = "INSERT INTO doctors (first_name, last_name, email, phone, specialization, education, address, date_of_birth, gender, emergency_contact, bio, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssssssssss", $first_name, $last_name, $email, $phone, $specialization, $education, $address, $date_of_birth, $gender, $emergency_contact, $bio);
                
                if ($stmt->execute()) {
                    $success_message = "Doctor added successfully!";
                    $_POST = array();
                } else {
                    $error_message = "Error adding doctor. Please try again.";
                }
            }
        } elseif ($action == 'edit' && $doctor_id > 0) {
            // Check if email already exists for other doctors
            $check_email = $conn->prepare("SELECT id FROM doctors WHERE email = ? AND id != ?");
            $check_email->bind_param("si", $email, $doctor_id);
            $check_email->execute();
            
            if ($check_email->get_result()->num_rows > 0) {
                $error_message = "Email already exists. Please use a different email.";
            } else {
                // Update doctor
                $sql = "UPDATE doctors SET first_name = ?, last_name = ?, email = ?, phone = ?, specialization = ?, education = ?, address = ?, date_of_birth = ?, gender = ?, emergency_contact = ?, bio = ?, updated_at = NOW() WHERE id = ?";
                
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssssssssssi", $first_name, $last_name, $email, $phone, $specialization, $education, $address, $date_of_birth, $gender, $emergency_contact, $bio, $doctor_id);
                
                if ($stmt->execute()) {
                    $success_message = "Doctor updated successfully!";
                } else {
                    $error_message = "Error updating doctor. Please try again.";
                }
            }
        }
    }
}

// Handle delete action
if ($action == 'delete' && $doctor_id > 0) {
    $stmt = $conn->prepare("DELETE FROM doctors WHERE id = ?");
    $stmt->bind_param("i", $doctor_id);
    
    if ($stmt->execute()) {
        $success_message = "Doctor deleted successfully!";
        $action = 'list'; // Redirect to list after delete
    } else {
        $error_message = "Error deleting doctor. Please try again.";
    }
}

// Get doctor data for editing
$doctor_data = null;
if ($action == 'edit' && $doctor_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM doctors WHERE id = ?");
    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();
    $doctor_data = $stmt->get_result()->fetch_assoc();
    
    if (!$doctor_data) {
        $error_message = "Doctor not found.";
        $action = 'list';
    }
}

// Get all doctors for listing
$doctors = [];
if ($action == 'list') {
    $result = $conn->query("SELECT * FROM doctors ORDER BY created_at DESC");
    $doctors = $result->fetch_all(MYSQLI_ASSOC);
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
                                    Add New Doctor
                                <?php elseif ($action == 'edit'): ?>
                                    Edit Doctor
                                <?php else: ?>
                                    Doctors Management
                                <?php endif; ?>
                            </h4>
                            <p class="text-muted">
                                <?php if ($action == 'add'): ?>
                                    Register a new doctor in the system
                                <?php elseif ($action == 'edit'): ?>
                                    Update doctor information
                                <?php else: ?>
                                    Manage doctors in the system
                                <?php endif; ?>
                            </p>
                        </div>
                        <div>
                            <?php if ($action == 'list'): ?>
                                <a href="?action=add" class="btn btn-primary">
                                    <i class="bx bx-plus me-2"></i>Add New Doctor
                                </a>
                            <?php else: ?>
                                <a href="doctors_action.php" class="btn btn-outline-secondary">
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
                <!-- Doctors Table -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="bx bx-group me-2 text-primary"></i>All Doctors (<?php echo count($doctors); ?>)</h5>
                                <div class="d-flex gap-2">
                                    <input type="text" id="searchInput" class="form-control" placeholder="Search doctors..." style="width: 250px;">
                                    <select id="specializationFilter" class="form-select" style="width: 200px;">
                                        <option value="">All Specializations</option>
                                        <option value="General Medicine">General Medicine</option>
                                        <option value="Cardiology">Cardiology</option>
                                        <option value="Dermatology">Dermatology</option>
                                        <option value="Neurology">Neurology</option>
                                        <option value="Orthopedics">Orthopedics</option>
                                        <option value="Pediatrics">Pediatrics</option>
                                        <option value="Psychiatry">Psychiatry</option>
                                        <option value="Radiology">Radiology</option>
                                        <option value="Surgery">Surgery</option>
                                        <option value="Gynecology">Gynecology</option>
                                        <option value="Ophthalmology">Ophthalmology</option>
                                        <option value="ENT">ENT</option>
                                    </select>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <?php if (empty($doctors)): ?>
                                    <div class="text-center py-5">
                                        <i class="bx bx-user-check display-1 text-muted"></i>
                                        <h5 class="mt-3 text-muted">No doctors found</h5>
                                        <p class="text-muted">Start by adding your first doctor</p>
                                        <a href="?action=add" class="btn btn-primary">
                                            <i class="bx bx-plus me-2"></i>Add First Doctor
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0" id="doctorsTable">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Doctor</th>
                                                    <th>Specialization</th>
                                                    <th>Contact</th>
                                                    <th>Education</th>
                                                    <th>Registered</th>
                                                    <th width="120">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($doctors as $doctor): ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="avatar avatar-md me-3">
                                                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                                                                    <span class="fw-bold"><?php echo strtoupper(substr($doctor['first_name'], 0, 1) . substr($doctor['last_name'], 0, 1)); ?></span>
                                                                </div>
                                                            </div>
                                                            <div>
                                                                <h6 class="mb-1">Dr. <?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']); ?></h6>
                                                                <small class="text-muted"><?php echo htmlspecialchars($doctor['email']); ?></small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-primary"><?php echo htmlspecialchars($doctor['specialization']); ?></span>
                                                    </td>
                                                    <td>
                                                        <div>
                                                            <small class="d-block"><i class="bx bx-phone me-1"></i><?php echo htmlspecialchars($doctor['phone']); ?></small>
                                                            <?php if ($doctor['emergency_contact']): ?>
                                                                <small class="text-muted"><i class="bx bx-phone-call me-1"></i><?php echo htmlspecialchars($doctor['emergency_contact']); ?></small>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <small class="text-muted"><?php echo $doctor['education'] ? htmlspecialchars($doctor['education']) : '-'; ?></small>
                                                    </td>
                                                    <td>
                                                        <small class="text-muted"><?php echo date('M j, Y', strtotime($doctor['created_at'])); ?></small>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <a href="?action=edit&id=<?php echo $doctor['id']; ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                                                                <i class="bx bx-edit"></i>
                                                            </a>
                                                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmDelete(<?php echo $doctor['id']; ?>, '<?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']); ?>')" title="Delete">
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
                <!-- Doctor Form -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="bx bx-user-check me-2 text-primary"></i>
                                    <?php echo $action == 'add' ? 'Add Doctor' : 'Edit Doctor'; ?>
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
                                        <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo $doctor_data ? htmlspecialchars($doctor_data['first_name']) : (isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''); ?>" required>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo $doctor_data ? htmlspecialchars($doctor_data['last_name']) : (isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''); ?>" required>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="date_of_birth" class="form-label">Date of Birth</label>
                                        <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" value="<?php echo $doctor_data ? $doctor_data['date_of_birth'] : (isset($_POST['date_of_birth']) ? $_POST['date_of_birth'] : ''); ?>">
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="gender" class="form-label">Gender</label>
                                        <select class="form-select" id="gender" name="gender">
                                            <option value="">Select Gender</option>
                                            <option value="Male" <?php echo ($doctor_data && $doctor_data['gender'] == 'Male') || (isset($_POST['gender']) && $_POST['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                                            <option value="Female" <?php echo ($doctor_data && $doctor_data['gender'] == 'Female') || (isset($_POST['gender']) && $_POST['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                                            <option value="Other" <?php echo ($doctor_data && $doctor_data['gender'] == 'Other') || (isset($_POST['gender']) && $_POST['gender'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                                        </select>
                                    </div>

                                    <!-- Contact Information -->
                                    <div class="col-12 mt-4">
                                        <h6 class="text-primary mb-3"><i class="bx bx-phone me-2"></i>Contact Information</h6>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo $doctor_data ? htmlspecialchars($doctor_data['email']) : (isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''); ?>" required>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="phone" class="form-label">Phone <span class="text-danger">*</span></label>
                                        <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo $doctor_data ? htmlspecialchars($doctor_data['phone']) : (isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''); ?>" required>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="emergency_contact" class="form-label">Emergency Contact</label>
                                        <input type="tel" class="form-control" id="emergency_contact" name="emergency_contact" value="<?php echo $doctor_data ? htmlspecialchars($doctor_data['emergency_contact']) : (isset($_POST['emergency_contact']) ? htmlspecialchars($_POST['emergency_contact']) : ''); ?>">
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="address" class="form-label">Address</label>
                                        <input type="text" class="form-control" id="address" name="address" value="<?php echo $doctor_data ? htmlspecialchars($doctor_data['address']) : (isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''); ?>">
                                    </div>

                                    <!-- Professional Information -->
                                    <div class="col-12 mt-4">
                                        <h6 class="text-primary mb-3"><i class="bx bx-briefcase me-2"></i>Professional Information</h6>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="specialization" class="form-label">Specialization <span class="text-danger">*</span></label>
                                        <select class="form-select" id="specialization" name="specialization" required>
                                            <option value="">Select Specialization</option>
                                            <?php 
                                            $specializations = ['General Medicine', 'Cardiology', 'Dermatology', 'Neurology', 'Orthopedics', 'Pediatrics', 'Psychiatry', 'Radiology', 'Surgery', 'Gynecology', 'Ophthalmology', 'ENT'];
                                            foreach ($specializations as $spec): 
                                                $selected = ($doctor_data && $doctor_data['specialization'] == $spec) || (isset($_POST['specialization']) && $_POST['specialization'] == $spec) ? 'selected' : '';
                                            ?>
                                                <option value="<?php echo $spec; ?>" <?php echo $selected; ?>><?php echo $spec; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="education" class="form-label">Education</label>
                                        <input type="text" class="form-control" id="education" name="education" value="<?php echo $doctor_data ? htmlspecialchars($doctor_data['education']) : (isset($_POST['education']) ? htmlspecialchars($_POST['education']) : ''); ?>" placeholder="e.g., MD, PhD">
                                    </div>
                                    
                                    <div class="col-12">
                                        <label for="bio" class="form-label">Biography</label>
                                        <textarea class="form-control" id="bio" name="bio" rows="4" placeholder="Brief description about the doctor..."><?php echo $doctor_data ? htmlspecialchars($doctor_data['bio']) : (isset($_POST['bio']) ? htmlspecialchars($_POST['bio']) : ''); ?></textarea>
                                    </div>

                                    <!-- Form Actions -->
                                    <div class="col-12 mt-4">
                                        <hr>
                                        <div class="d-flex justify-content-between">
                                            <a href="doctors_action.php" class="btn btn-outline-secondary">
                                                <i class="bx bx-arrow-back me-2"></i>Back to List
                                            </a>
                                            <div>
                                                <button type="reset" class="btn btn-outline-warning me-2">
                                                    <i class="bx bx-refresh me-2"></i>Reset Form
                                                </button>
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="bx bx-save me-2"></i><?php echo $action == 'add' ? 'Add Doctor' : 'Update Doctor'; ?>
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
                <p>Are you sure you want to delete <strong id="doctorName"></strong>?</p>
                <p class="text-muted">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="deleteLink" class="btn btn-danger">Delete Doctor</a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Search and filter functionality
    const searchInput = document.getElementById('searchInput');
    const specializationFilter = document.getElementById('specializationFilter');
    const table = document.getElementById('doctorsTable');
    
    if (searchInput && table) {
        function filterTable() {
            const searchTerm = searchInput.value.toLowerCase();
            const selectedSpecialization = specializationFilter ? specializationFilter.value : '';
            const rows = table.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const doctorName = row.cells[0].textContent.toLowerCase();
                const email = row.cells[0].textContent.toLowerCase();
                const specialization = row.cells[1].textContent;
                const phone = row.cells[2].textContent.toLowerCase();
                
                const matchesSearch = doctorName.includes(searchTerm) || 
                                    email.includes(searchTerm) || 
                                    phone.includes(searchTerm);
                const matchesSpecialization = !selectedSpecialization || 
                                            specialization.includes(selectedSpecialization);
                
                row.style.display = matchesSearch && matchesSpecialization ? '' : 'none';
            });
        }
        
        searchInput.addEventListener('input', filterTable);
        if (specializationFilter) {
            specializationFilter.addEventListener('change', filterTable);
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
    document.getElementById('doctorName').textContent = 'Dr. ' + name;
    document.getElementById('deleteLink').href = '?action=delete&id=' + id;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>

<?php include '../includes/footer.php'; ?>