<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

requireLogin();

// ตรวจสอบ ID พยาบาล
$nurse_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($nurse_id <= 0) {
    header("Location: nurses_action.php");
    exit;
}

// ดึงข้อมูลพยาบาล
$nurse_sql = "SELECT * FROM nurses WHERE id = ?";
$stmt = $conn->prepare($nurse_sql);
$stmt->bind_param("i", $nurse_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: nurses_action.php");
    exit;
}

$nurse = $result->fetch_assoc();

// แปลงรูปแบบวันที่ให้อ่านง่าย
$dob_formatted = '';
if (!empty($nurse['date_of_birth']) && $nurse['date_of_birth'] != '0000-00-00') {
    $dob_obj = date_create($nurse['date_of_birth']);
    if ($dob_obj) {
        $dob_formatted = date_format($dob_obj, 'd/m/Y');
    }
}

$hire_date_formatted = '';
if (!empty($nurse['hire_date']) && $nurse['hire_date'] != '0000-00-00') {
    $hire_date_obj = date_create($nurse['hire_date']);
    if ($hire_date_obj) {
        $hire_date_formatted = date_format($hire_date_obj, 'd/m/Y');
    }
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
            
            <h4 class="fw-bold py-3 mb-4">
                <span class="text-muted fw-light">Nurses /</span> Nurse Details
            </h4>

            <!-- Action Buttons -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="text-end">
                        <button id="printBtn" class="btn btn-secondary me-2">
                            <i class="bx bx-printer me-1"></i> Print
                        </button>
                        <button id="exportPdfBtn" class="btn btn-danger me-2">
                            <i class="bx bx-file-pdf me-1"></i> Export PDF
                        </button>
                        <a href="nurses_action.php" class="btn btn-outline-secondary btn-back">
                            <i class="bx bx-arrow-back me-1"></i> Back to Nurses List
                        </a>
                    </div>
                </div>
            </div>

            <!-- Nurse Details -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card mb-4" id="nurse-details-card">
                        <h5 class="card-header">Nurse Information</h5>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3 mb-4 text-center">
                                    <div class="patient-avatar mx-auto" style="background-color: #28a745;">
                                        <?php echo strtoupper(substr($nurse['first_name'], 0, 1) . substr($nurse['last_name'], 0, 1)); ?>
                                    </div>
                                    <h5><?php echo htmlspecialchars($nurse['first_name'] . ' ' . $nurse['last_name']); ?></h5>
                                    <p class="text-muted">Nurse ID: <?php echo str_pad($nurse['id'], 4, '0', STR_PAD_LEFT); ?></p>
                                    
                                    <!-- Status Badge -->
                                    <?php
                                    $status_colors = [
                                        'Active' => 'success',
                                        'Inactive' => 'danger',
                                        'On Leave' => 'warning'
                                    ];
                                    $color = $status_colors[$nurse['status']] ?? 'secondary';
                                    ?>
                                    <div class="mb-3">
                                        <span class="badge bg-<?php echo $color; ?> p-2"><?php echo htmlspecialchars($nurse['status']); ?></span>
                                    </div>
                                    
                                    <div class="mt-4 btn-actions">
                                        <a href="nurses_action.php?action=edit&id=<?php echo $nurse['id']; ?>" class="btn btn-primary me-2">
                                            <i class="bx bx-edit-alt me-1"></i> Edit
                                        </a>
                                        <a href="nurses_action.php" class="btn btn-outline-secondary">
                                            <i class="bx bx-list-ul me-1"></i> All Nurses
                                        </a>
                                    </div>
                                </div>
                                <div class="col-md-9">
                                    <!-- Personal Information -->
                                    <h6 class="text-primary mb-3"><i class="bx bx-user me-2"></i>Personal Information</h6>
                                    <div class="row mb-4">
                                        <div class="col-md-6">
                                            <div class="info-row">
                                                <div class="info-label">First Name</div>
                                                <div class="info-value"><?php echo htmlspecialchars($nurse['first_name']); ?></div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="info-row">
                                                <div class="info-label">Last Name</div>
                                                <div class="info-value"><?php echo htmlspecialchars($nurse['last_name']); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-4">
                                        <div class="col-md-6">
                                            <div class="info-row">
                                                <div class="info-label">Date of Birth</div>
                                                <div class="info-value"><?php echo htmlspecialchars($dob_formatted); ?></div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="info-row">
                                                <div class="info-label">Gender</div>
                                                <div class="info-value"><?php echo htmlspecialchars($nurse['gender']); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Contact Information -->
                                    <h6 class="text-primary mb-3"><i class="bx bx-phone me-2"></i>Contact Information</h6>
                                    <div class="row mb-4">
                                        <div class="col-md-6">
                                            <div class="info-row">
                                                <div class="info-label">Phone</div>
                                                <div class="info-value"><?php echo htmlspecialchars($nurse['phone']); ?></div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="info-row">
                                                <div class="info-label">Email</div>
                                                <div class="info-value"><?php echo htmlspecialchars($nurse['email']); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-4">
                                        <div class="col-md-6">
                                            <div class="info-row">
                                                <div class="info-label">Emergency Contact</div>
                                                <div class="info-value"><?php echo htmlspecialchars($nurse['emergency_contact']); ?></div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="info-row">
                                                <div class="info-label">Address</div>
                                                <div class="info-value"><?php echo htmlspecialchars($nurse['address']); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Professional Information -->
                                    <h6 class="text-primary mb-3"><i class="bx bx-briefcase me-2"></i>Professional Information</h6>
                                    <div class="row mb-4">
                                        <div class="col-md-6">
                                            <div class="info-row">
                                                <div class="info-label">Department</div>
                                                <div class="info-value">
                                                    <span class="badge bg-info"><?php echo htmlspecialchars($nurse['department']); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="info-row">
                                                <div class="info-label">Position</div>
                                                <div class="info-value"><?php echo htmlspecialchars($nurse['position']); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-4">
                                        <div class="col-md-6">
                                            <div class="info-row">
                                                <div class="info-label">Hire Date</div>
                                                <div class="info-value"><?php echo htmlspecialchars($hire_date_formatted); ?></div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="info-row">
                                                <div class="info-label">Education</div>
                                                <div class="info-value"><?php echo htmlspecialchars($nurse['education']); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <?php if ($nurse['certifications']): ?>
                                    <div class="info-row mb-4">
                                        <div class="info-label">Certifications</div>
                                        <div class="info-value">
                                            <?php 
                                            $certs = explode(',', $nurse['certifications']);
                                            foreach ($certs as $cert): 
                                                $cert = trim($cert);
                                                if (!empty($cert)):
                                            ?>
                                                <span class="badge bg-light text-dark me-1"><?php echo htmlspecialchars($cert); ?></span>
                                            <?php 
                                                endif;
                                            endforeach; 
                                            ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($nurse['bio']): ?>
                                    <div class="info-row mb-4">
                                        <div class="info-label">Biography</div>
                                        <div class="info-value"><?php echo nl2br(htmlspecialchars($nurse['bio'])); ?></div>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <!-- System Information -->
                                    <hr class="my-4">
                                    <h6 class="text-muted mb-3"><i class="bx bx-time me-2"></i>System Information</h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="info-row">
                                                <div class="info-label">Created</div>
                                                <div class="info-value text-muted"><?php echo date('F j, Y \a\t H:i', strtotime($nurse['created_at'])); ?></div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="info-row">
                                                <div class="info-label">Last Updated</div>
                                                <div class="info-value text-muted"><?php echo date('F j, Y \a\t H:i', strtotime($nurse['updated_at'])); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- PDF Export Library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<style>
.patient-avatar {
    width: 100px;
    height: 100px;
    background-color: #28a745;
    color: white;
    font-size: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    margin-bottom: 15px;
}

.info-row {
    margin-bottom: 15px;
}

.info-label {
    font-weight: 600;
    margin-bottom: 5px;
    color: #333;
}

.info-value {
    color: #697a8d;
}

/* สำหรับการพิมพ์ */
@media print {
    .layout-menu, 
    .layout-navbar,
    .content-footer,
    .btn-actions,
    .btn-back {
        display: none !important;
    }
    .layout-wrapper,
    .layout-container,
    .layout-page,
    .content-wrapper {
        width: 100% !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    .card {
        box-shadow: none !important;
        border: 1px solid #ddd !important;
    }
}
</style>

<script>
// Print functionality
document.getElementById('printBtn').addEventListener('click', function() {
    window.print();
});

// Export to PDF functionality
document.getElementById('exportPdfBtn').addEventListener('click', function() {
    // Element to export
    const element = document.getElementById('nurse-details-card');
    
    // HTML2PDF configuration
    const opt = {
        margin: 10,
        filename: 'nurse_<?php echo str_pad($nurse['id'], 4, '0', STR_PAD_LEFT); ?>_details.pdf',
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2 },
        jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
    };
    
    // Generate PDF
    html2pdf().set(opt).from(element).save();
});
</script>

<?php include '../includes/footer.php'; ?>