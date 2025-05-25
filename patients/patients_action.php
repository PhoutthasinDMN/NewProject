<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/patient_functions.php';

requireLogin();

// Get user info
$user_id = $_SESSION['user_id'];
$user = getUserInfo($user_id, $conn);
$isAdmin = ($user['role'] == 'admin');

// Get action and patient ID
$action = $_GET['action'] ?? '';
$patient_id = (int)($_GET['id'] ?? 0);

// Messages
$error = '';
$success = '';

// Check permissions for delete action
if ($action == 'delete' && !$isAdmin) {
    redirect('patients.php', 'Access denied - Admin privileges required', 'error');
}

// Initialize patient data
$patient = [];

// Load patient data for edit/delete actions
if (in_array($action, ['edit', 'delete']) && $patient_id > 0) {
    $patient = getPatientById($conn, $patient_id);
    if (!$patient) {
        redirect('patients.php', 'Patient not found', 'error');
    }
}

// Handle delete action
if ($action == 'delete' && $patient_id > 0 && $isAdmin) {
    // Check for medical records
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM medical_records WHERE patient_id = ?");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $records_count = $stmt->get_result()->fetch_assoc()['count'];

    if ($records_count > 0 && !isset($_GET['confirm'])) {
        // Show confirmation page
        $action = 'confirm_delete';
    } else {
        // Delete medical records first if they exist
        if ($records_count > 0) {
            $conn->prepare("DELETE FROM medical_records WHERE patient_id = ?")->execute([$patient_id]);
        }
        
        // Delete patient
        $result = executeQuery($conn, "DELETE FROM patients WHERE id = ?", [$patient_id], 'i');
        
        if ($result['success']) {
            redirect('patients.php', 'Patient deleted successfully', 'success');
        } else {
            redirect('patients.php', 'Error deleting patient', 'error');
        }
    }
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate required fields
    $errors = validatePatientData($_POST);
    
    if (!empty($errors)) {
        $error = alert(implode('<br>', $errors), "danger");
    } else {
        // Prepare patient data
        $isEdit = ($action == 'edit');
        $patientData = preparePatientData($_POST, $user_id, $isEdit);
        
        // Combine address fields into one address field for backward compatibility
        $addressParts = [];
        if (!empty($_POST['province'])) $addressParts[] = trim($_POST['province']);
        if (!empty($_POST['district'])) $addressParts[] = trim($_POST['district']);
        if (!empty($_POST['county'])) $addressParts[] = trim($_POST['county']);
        
        $patientData['address'] = implode(', ', $addressParts);
        
        if ($isEdit && $patient_id > 0) {
            // Update patient
            $columns = array_keys($patientData);
            $setClause = implode(' = ?, ', $columns) . ' = ?';
            $sql = "UPDATE patients SET {$setClause} WHERE id = ?";
            
            $params = array_values($patientData);
            $params[] = $patient_id;
            $types = str_repeat('s', count($patientData)) . 'i';
            
            $result = executeQuery($conn, $sql, $params, $types);
            
            if ($result['success']) {
                $success = alert("Patient information updated successfully", "success");
            } else {
                $error = alert("Error updating patient information", "danger");
            }
        } else {
            // Insert new patient
            $columns = array_keys($patientData);
            $placeholders = str_repeat('?,', count($columns) - 1) . '?';
            $sql = "INSERT INTO patients (" . implode(', ', $columns) . ") VALUES ({$placeholders})";
            
            $params = array_values($patientData);
            $types = str_repeat('s', count($patientData));
            
            $result = executeQuery($conn, $sql, $params, $types);
            
            if ($result['success']) {
                $success = alert("Patient added successfully", "success");
                $patient = []; // Clear form data
            } else {
                $error = alert("Error adding patient", "danger");
            }
        }
    }
}

// Parse existing address for editing
$addressParts = ['province' => '', 'district' => '', 'county' => ''];
if (!empty($patient['address'])) {
    $parts = explode(',', $patient['address']);
    $addressParts['province'] = trim($parts[0] ?? '');
    $addressParts['district'] = trim($parts[1] ?? '');
    $addressParts['county'] = trim($parts[2] ?? '');
}

// Page configuration
$page_title = match($action) {
    'edit' => 'Edit Patient',
    'confirm_delete' => 'Confirm Delete Patient',
    default => 'Add New Patient'
};

$button_text = $action == 'edit' ? 'Update Patient' : 'Add Patient';

// Laos Administrative Divisions: 17 Provinces + 1 Capital
$laos_provinces = [
    'Vientiane Capital', // นครหลวงเวียงจันทน์
    'Attapeu', 'Bokeo', 'Bolikhamxai', 'Champasak', 'Houaphanh',
    'Khammouan', 'Luang Namtha', 'Luang Prabang', 'Oudomxai', 'Phongsali',
    'Sainyabuli', 'Salavan', 'Savannakhet', 'Sekong', 'Vientiane Province', 
    'Xaisomboun', 'Xieng Khouang'
];

// Districts by Province
$districts_by_province = [
    'Vientiane Capital' => [
        'Chanthabouly', 'Hadxaifong', 'Mayparkngum', 'Naxaithong', 'Pakngum', 
        'Sangthong', 'Sikhottabong', 'Sisattanak', 'Xaysetha'
    ],
    'Attapeu' => [
        'Sanamxai', 'Sanxai', 'Phouvong', 'Xaysetha', 'Samakkhixai'
    ],
    'Bokeo' => [
        'Houayxai', 'Tonpheung', 'Meung', 'Paktha', 'Pha Oudom'
    ],
    'Bolikhamxai' => [
        'Pakxan', 'Thaphabat', 'Pakkading', 'Borikhan', 'Khamkeut', 'Viengthong'
    ],
    'Champasak' => [
        'Pakse', 'Champasak', 'Bachiangchaleunsouk', 'Khong', 'Mounlapamok', 
        'Pakxong', 'Pathoumphone', 'Phonthong', 'Soukhouma', 'Sanasomboun'
    ],
    'Houaphanh' => [
        'Xam Neua', 'Viengxai', 'Viengthong', 'Houamuang', 'Aed', 'Kouan', 
        'Xamtai', 'Sopbao', 'Et', 'Xiangkho'
    ],
    'Khammouan' => [
        'Thakhek', 'Mahaxai', 'Hinboun', 'Nongbok', 'Xe Bang Fai', 
        'Yommalath', 'Boualapha', 'Nakai', 'Gnommalat'
    ],
    'Luang Namtha' => [
        'Luang Namtha', 'Sing', 'Long', 'Viengphoukha', 'Nalae'
    ],
    'Luang Prabang' => [
        'Luang Prabang', 'Xieng Ngeun', 'Nan', 'Pak Ou', 'Nambak', 
        'Ngoi', 'Pak Xeng', 'Phonxai', 'Chomphet', 'Viengkham', 'Phoukhoune'
    ],
    'Oudomxai' => [
        'Xai', 'Nga', 'Beng', 'Houn', 'La', 'Namor', 'Pakbeng'
    ],
    'Phongsali' => [
        'Phongsali', 'Mai', 'Khua', 'Samphanh', 'Boun Neua', 'Bountai', 'Nhot Ou'
    ],
    'Sainyabuli' => [
        'Sainyabuli', 'Hongsa', 'Ngeun', 'Xienghon', 'Phiang', 'Parklai', 
        'Kenethao', 'Khop', 'Botene', 'Thongmyxai', 'Beng'
    ],
    'Salavan' => [
        'Salavan', 'Khongxedon', 'Laongam', 'Toumlane', 'Taoih', 
        'Samouay', 'Va Pi', 'Lakhonpheng'
    ],
    'Savannakhet' => [
        'Kaysone Phomvihane', 'Outhoumphone', 'Atsaphangthong', 'Phin', 
        'Songkhone', 'Champhone', 'Nong', 'Atsaphone', 'Xaybouly', 
        'Xepon', 'Vilabuly', 'Thapangthong', 'Kaisone Phomvihan', 
        'Sepon', 'Phine'
    ],
    'Sekong' => [
        'Lamam', 'Kaleum', 'Dakcheung', 'Thateng'
    ],
    'Vientiane Province' => [
        'Phonhong', 'Thoulakhom', 'Keo Oudom', 'Mad', 'Feuang', 'Xanakharm', 
        'Hinheup', 'Vangvieng', 'Kasi', 'Meun', 'Kham'
    ],
    'Xaisomboun' => [
        'Anouvong', 'Thathom', 'Longxan', 'Hom'
    ],
    'Xieng Khouang' => [
        'Phonsavan', 'Pek', 'Kham', 'Nonghet', 'Khoun', 'Morkmay', 'Paek'
    ]
];
?>
<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title><?php echo $page_title; ?> | Healthcare System</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../assets/img/favicon/favicon.ico" />
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet" />
    
    <!-- Core CSS -->
    <link rel="stylesheet" href="../assets/vendor/fonts/boxicons.css" />
    <link rel="stylesheet" href="../assets/vendor/css/core.css" class="template-customizer-core-css" />
    <link rel="stylesheet" href="../assets/vendor/css/theme-default.css" class="template-customizer-theme-css" />
    <link rel="stylesheet" href="../assets/css/demo.css" />
    <link rel="stylesheet" href="../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />
    
    <style>
        .user-role-indicator {
            background: linear-gradient(135deg, <?php echo $isAdmin ? '#007bff, #0056b3' : '#28a745, #20c997'; ?>);
            color: white; padding: 8px 16px; border-radius: 25px;
            font-size: 12px; font-weight: 600; display: inline-block; margin-left: 10px;
        }
        .confirm-delete-card { border: 2px solid #dc3545; background: #fff5f5; }
        .patient-info-card { background: #f8f9fa; border-radius: 8px; padding: 15px; margin-bottom: 20px; }
        .address-section { background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .address-section h6 { color: #495057; margin-bottom: 15px; }
    </style>
    
    <script src="../assets/vendor/js/helpers.js"></script>
    <script src="../assets/js/config.js"></script>
</head>

<body>
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            <?php include '../includes/sidebar.php'; ?>
            
            <div class="layout-page">
                <div class="content-wrapper">
                    <div class="container-xxl flex-grow-1 container-p-y">
                        <h4 class="fw-bold py-3 mb-4">
                            <span class="text-muted fw-light">Patients /</span> <?php echo $page_title; ?>
                            <span class="user-role-indicator">
                                <?php echo $isAdmin ? 'Admin Access' : 'User Access'; ?>
                            </span>
                        </h4>

                        <?php if ($action == 'confirm_delete'): ?>
                            <!-- Confirm Delete Section -->
                            <div class="card mb-4 confirm-delete-card">
                                <h5 class="card-header text-danger">
                                    <i class="bx bx-warning me-2"></i>Confirm Patient Deletion
                                </h5>
                                <div class="card-body">
                                    <div class="patient-info-card">
                                        <h6 class="mb-3">Patient Information:</h6>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <p><strong>Name:</strong> <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></p>
                                                <p><strong>Age:</strong> <?php echo htmlspecialchars($patient['age']); ?></p>
                                                <p><strong>Phone:</strong> <?php echo htmlspecialchars($patient['phone']); ?></p>
                                            </div>
                                            <div class="col-md-6">
                                                <p><strong>Email:</strong> <?php echo htmlspecialchars($patient['email']); ?></p>
                                                <p><strong>Patient ID:</strong> <?php echo str_pad($patient['id'], 4, '0', STR_PAD_LEFT); ?></p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="alert alert-danger">
                                        <h6 class="mb-2"><i class="bx bx-error-circle me-1"></i>Warning: This action cannot be undone!</h6>
                                        <p class="mb-2">This patient has <?php echo $records_count; ?> medical record(s) associated with them.</p>
                                        <p class="mb-0">Deleting this patient will also permanently delete all their medical records.</p>
                                    </div>

                                    <div class="text-center">
                                        <a href="patients_action.php?action=delete&id=<?php echo $patient_id; ?>&confirm=yes" 
                                           class="btn btn-danger me-3">
                                            <i class="bx bx-trash me-1"></i>Yes, Delete Patient and All Records
                                        </a>
                                        <a href="patients.php" class="btn btn-secondary">
                                            <i class="bx bx-x me-1"></i>Cancel
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- Add/Edit Form Section -->
                            <div class="card mb-4">
                                <h5 class="card-header"><?php echo $page_title; ?></h5>

                                <!-- Alerts -->
                                <?php if (!empty($error)) echo $error; ?>
                                <?php if (!empty($success)) echo $success; ?>

                                <div class="card-body">
                                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . '?action=' . $action . ($patient_id > 0 ? '&id=' . $patient_id : '')); ?>">
                                        <!-- Personal Information -->
                                        <div class="row">
                                            <div class="mb-3 col-md-6">
                                                <label for="first_name" class="form-label">First Name *</label>
                                                <input class="form-control" type="text" id="first_name" name="first_name"
                                                       value="<?php echo htmlspecialchars($patient['first_name'] ?? ''); ?>" required />
                                            </div>
                                            <div class="mb-3 col-md-6">
                                                <label for="last_name" class="form-label">Last Name *</label>
                                                <input class="form-control" type="text" id="last_name" name="last_name"
                                                       value="<?php echo htmlspecialchars($patient['last_name'] ?? ''); ?>" required />
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="mb-3 col-md-3">
                                                <label for="age" class="form-label">Age</label>
                                                <input class="form-control" type="number" id="age" name="age" min="1" max="150"
                                                       value="<?php echo htmlspecialchars($patient['age'] ?? ''); ?>" />
                                            </div>
                                            <div class="mb-3 col-md-6">
                                                <label for="dob" class="form-label">Date of Birth</label>
                                                <input class="form-control" type="date" id="dob" name="dob"
                                                       value="<?php echo !empty($patient['dob']) && $patient['dob'] != '0000-00-00' ? date('Y-m-d', strtotime($patient['dob'])) : ''; ?>" />
                                            </div>
                                            <div class="mb-3 col-md-3">
                                                <label for="gender" class="form-label">Gender</label>
                                                <select id="gender" name="gender" class="form-select">
                                                    <option value="">Select Gender</option>
                                                    <option value="M" <?php echo ($patient['gender'] ?? '') == 'M' ? 'selected' : ''; ?>>Male</option>
                                                    <option value="F" <?php echo ($patient['gender'] ?? '') == 'F' ? 'selected' : ''; ?>>Female</option>
                                                    <option value="O" <?php echo ($patient['gender'] ?? '') == 'O' ? 'selected' : ''; ?>>Other</option>
                                                </select>
                                            </div>
                                        </div>

                                        <!-- Address Information -->
                                     
                                            <div class="row">
                                                <div class="mb-3 col-md-4">
                                                    <label for="province" class="form-label">Province/Capital</label>
                                                    <select class="form-select" id="province" name="province" required>
                                                        <option value="">Select Province/Capital</option>
                                                        <optgroup label="Capital">
                                                            <option value="Vientiane Capital" 
                                                                <?php echo $addressParts['province'] == 'Vientiane Capital' ? 'selected' : ''; ?>>
                                                                🏛️ Vientiane Capital
                                                            </option>
                                                        </optgroup>
                                                        <optgroup label="Provinces (17 แขวง)">
                                                            <?php 
                                                            $provinces_only = array_slice($laos_provinces, 1); // Skip Vientiane Capital
                                                            foreach ($provinces_only as $province): 
                                                            ?>
                                                                <option value="<?php echo $province; ?>" 
                                                                    <?php echo $addressParts['province'] == $province ? 'selected' : ''; ?>>
                                                                    <?php echo $province; ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </optgroup>
                                                    </select>
                                                </div>
                                                <div class="mb-3 col-md-4">
                                                    <label for="district" class="form-label">District</label>
                                                    <select class="form-select" id="district" name="district" required disabled>
                                                        <option value="">Select province first</option>
                                                    </select>
                                                </div>
                                                <div class="mb-3 col-md-4">
                                                    <label for="county" class="form-label">Village</label>
                                                    <input class="form-control" type="text" id="county" name="county" 
                                                           placeholder="Enter village name"
                                                           value="<?php echo htmlspecialchars($addressParts['county']); ?>" />
                                                </div>
                                            </div>
                                      

                                        <!-- Contact Information -->
                                        <div class="row">
                                            <div class="mb-3 col-md-6">
                                                <label for="phone" class="form-label">Phone</label>
                                                <input class="form-control" type="text" id="phone" name="phone"
                                                       value="<?php echo htmlspecialchars($patient['phone'] ?? ''); ?>" />
                                            </div>
                                            <div class="mb-3 col-md-6">
                                                <label for="email" class="form-label">Email</label>
                                                <input class="form-control" type="email" id="email" name="email"
                                                       value="<?php echo htmlspecialchars($patient['email'] ?? ''); ?>" />
                                            </div>
                                        </div>

                                        <!-- Additional Information -->
                                        <div class="row">
                                            <div class="mb-3 col-md-6">
                                                <label for="nationality" class="form-label">Nationality</label>
                                                <select class="form-select" id="nationality" name="nationality">
                                                    <option value="">Select Nationality</option>
                                                    <option value="Lao" <?php echo ($patient['nationality'] ?? '') == 'Lao' ? 'selected' : ''; ?>>Lao</option>
                                                    <option value="Thai" <?php echo ($patient['nationality'] ?? '') == 'Thai' ? 'selected' : ''; ?>>Thai</option>
                                                    <option value="Vietnamese" <?php echo ($patient['nationality'] ?? '') == 'Vietnamese' ? 'selected' : ''; ?>>Vietnamese</option>
                                                    <option value="Chinese" <?php echo ($patient['nationality'] ?? '') == 'Chinese' ? 'selected' : ''; ?>>Chinese</option>
                                                    <option value="Other" <?php echo ($patient['nationality'] ?? '') == 'Other' ? 'selected' : ''; ?>>Other</option>
                                                </select>
                                            </div>
                                            <div class="mb-3 col-md-6">
                                                <label for="religion" class="form-label">Religion</label>
                                                <select class="form-select" id="religion" name="religion">
                                                    <option value="">Select Religion</option>
                                                    <option value="Buddhism" <?php echo ($patient['religion'] ?? '') == 'Buddhism' ? 'selected' : ''; ?>>Buddhism</option>
                                                    <option value="Christianity" <?php echo ($patient['religion'] ?? '') == 'Christianity' ? 'selected' : ''; ?>>Christianity</option>
                                                    <option value="Islam" <?php echo ($patient['religion'] ?? '') == 'Islam' ? 'selected' : ''; ?>>Islam</option>
                                                    <option value="Animism" <?php echo ($patient['religion'] ?? '') == 'Animism' ? 'selected' : ''; ?>>Animism</option>
                                                    <option value="Other" <?php echo ($patient['religion'] ?? '') == 'Other' ? 'selected' : ''; ?>>Other</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="mb-3 col-md-6">
                                                <label for="marital_status" class="form-label">Marital Status</label>
                                                <select id="marital_status" name="marital_status" class="form-select">
                                                    <option value="">Select Marital Status</option>
                                                    <?php
                                                    $marital_options = ['Single', 'Married', 'Divorced', 'Widowed'];
                                                    foreach ($marital_options as $option) {
                                                        $selected = ($patient['marital_status'] ?? '') == $option ? 'selected' : '';
                                                        echo "<option value=\"$option\" $selected>$option</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            <div class="mb-3 col-md-6">
                                                <label for="occupation" class="form-label">Occupation</label>
                                                <input class="form-control" type="text" id="occupation" name="occupation"
                                                       value="<?php echo htmlspecialchars($patient['occupation'] ?? ''); ?>" />
                                            </div>
                                        </div>

                                        <!-- Emergency Contact -->
                                        <hr class="my-4">
                                        <h6 class="mb-3"><i class="bx bx-phone me-2"></i>Emergency Contact Information</h6>

                                        <div class="row">
                                            <div class="mb-3 col-md-6">
                                                <label for="emergency_contact_name" class="form-label">Emergency Contact Name</label>
                                                <input class="form-control" type="text" id="emergency_contact_name" name="emergency_contact_name"
                                                       value="<?php echo htmlspecialchars($patient['emergency_contact_name'] ?? ''); ?>" />
                                            </div>
                                            <div class="mb-3 col-md-6">
                                                <label for="emergency_contact_relationship" class="form-label">Relationship</label>
                                                <select class="form-select" id="emergency_contact_relationship" name="emergency_contact_relationship">
                                                    <option value="">Select Relationship</option>
                                                    <?php
                                                    $relationships = ['Spouse', 'Parent', 'Child', 'Sibling', 'Friend', 'Other'];
                                                    foreach ($relationships as $rel) {
                                                        $selected = ($patient['emergency_contact_relationship'] ?? '') == $rel ? 'selected' : '';
                                                        echo "<option value=\"$rel\" $selected>$rel</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="emergency_contact_phone" class="form-label">Emergency Contact Phone</label>
                                            <input class="form-control" type="text" id="emergency_contact_phone" name="emergency_contact_phone"
                                                   value="<?php echo htmlspecialchars($patient['emergency_contact_phone'] ?? ''); ?>" />
                                        </div>

                                        <div class="mt-4">
                                            <button type="submit" class="btn btn-primary me-2">
                                                <i class="bx bx-check me-1"></i><?php echo $button_text; ?>
                                            </button>
                                            <a href="patients.php" class="btn btn-outline-secondary">
                                                <i class="bx bx-x me-1"></i>Cancel
                                            </a>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php include '../includes/footer.php'; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Core JS -->
    <script src="../assets/vendor/libs/jquery/jquery.js"></script>
    <script src="../assets/vendor/libs/popper/popper.js"></script>
    <script src="../assets/vendor/js/bootstrap.js"></script>
    <script src="../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="../assets/vendor/js/menu.js"></script>
    <script src="../assets/js/main.js"></script>

    <script>
        // Districts data for dynamic loading
        const districtsByProvince = <?php echo json_encode($districts_by_province); ?>;
        const currentDistrict = "<?php echo htmlspecialchars($addressParts['district']); ?>";

        // Auto focus on first name field when adding new patient
        document.addEventListener('DOMContentLoaded', function() {
            const firstNameInput = document.getElementById('first_name');
            if (firstNameInput && '<?php echo $action; ?>' !== 'edit') {
                firstNameInput.focus();
            }

            // Initialize district dropdown if editing
            const provinceSelect = document.getElementById('province');
            if (provinceSelect.value && currentDistrict) {
                loadDistricts(provinceSelect.value, currentDistrict);
            }
        });

        // Load districts when province changes
        document.getElementById('province').addEventListener('change', function() {
            const selectedProvince = this.value;
            loadDistricts(selectedProvince);
        });

        function loadDistricts(province, selectedDistrict = '') {
            const districtSelect = document.getElementById('district');
            
            // Clear existing options
            districtSelect.innerHTML = '<option value="">Select district</option>';
            
            if (province && districtsByProvince[province]) {
                // Enable the select
                districtSelect.disabled = false;
                
                // Add districts for selected province
                districtsByProvince[province].forEach(function(district) {
                    const option = document.createElement('option');
                    option.value = district;
                    option.textContent = district;
                    
                    // Select if this is the current district (for editing)
                    if (district === selectedDistrict) {
                        option.selected = true;
                    }
                    
                    districtSelect.appendChild(option);
                });
            } else {
                // Disable if no province selected
                districtSelect.disabled = true;
                districtSelect.innerHTML = '<option value="">Select province first</option>';
            }
        }

        // Calculate age from date of birth
        document.getElementById('dob').addEventListener('change', function() {
            const dobValue = this.value;
            if (dobValue) {
                const dob = new Date(dobValue);
                const today = new Date();
                let age = today.getFullYear() - dob.getFullYear();
                const monthDiff = today.getMonth() - dob.getMonth();
                
                if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dob.getDate())) {
                    age--;
                }
                
                if (age >= 0 && age <= 150) {
                    document.getElementById('age').value = age;
                }
            }
        });

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const firstName = document.getElementById('first_name').value.trim();
            const lastName = document.getElementById('last_name').value.trim();
            const province = document.getElementById('province').value;
            const district = document.getElementById('district').value;
            
            if (!firstName || !lastName) {
                e.preventDefault();
                alert('Please fill in both first name and last name.');
                return false;
            }
            
            if (!province) {
                e.preventDefault();
                alert('Please select a province.');
                return false;
            }
            
            if (!district) {
                e.preventDefault();
                alert('Please select a district.');
                return false;
            }
        });

        // Real-time address preview
        function updateAddressPreview() {
            const province = document.getElementById('province').value;
            const district = document.getElementById('district').value;
            const county = document.getElementById('county').value;
            
            const parts = [province, district, county].filter(part => part.trim() !== '');
            const preview = parts.join(', ') || 'Address will appear here...';
            
            console.log('Address preview:', preview);
        }

        // Add event listeners for address preview
        document.getElementById('province').addEventListener('change', updateAddressPreview);
        document.getElementById('district').addEventListener('change', updateAddressPreview);
        document.getElementById('county').addEventListener('input', updateAddressPreview);
    </script>
</body>
</html>