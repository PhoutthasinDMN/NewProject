<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

requireLogin();

// ตรวจสอบ ID ของ medical record
$record_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($record_id <= 0) {
    header("Location: medical_records_action.php");
    exit;
}

// ดึงข้อมูล medical record พร้อมข้อมูลผู้ป่วย
$record_sql = "SELECT mr.*, p.first_name, p.last_name, p.age, p.gender, p.phone, p.email, p.address, p.nationality, p.religion, p.marital_status, p.occupation, p.emergency_contact_name, p.emergency_contact_relationship, p.emergency_contact_phone 
               FROM medical_records mr 
               JOIN patients p ON mr.patient_id = p.id 
               WHERE mr.id = ?";
$stmt = $conn->prepare($record_sql);
$stmt->bind_param("i", $record_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: medical_records_action.php");
    exit;
}

$record = $result->fetch_assoc();

// ดึงข้อมูล medical records อื่นๆ ของผู้ป่วยคนเดียวกัน
$other_records_sql = "SELECT id, visit_date, diagnosis, doctor_name FROM medical_records WHERE patient_id = ? AND id != ? ORDER BY visit_date DESC LIMIT 5";
$stmt = $conn->prepare($other_records_sql);
$stmt->bind_param("ii", $record['patient_id'], $record_id);
$stmt->execute();
$other_records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// แยกข้อมูลพยาบาลจาก notes
$nurse_name = '';
if (preg_match('/Assisted by Nurse: (.+?)(?:\n|$)/', $record['notes'], $matches)) {
    $nurse_name = trim($matches[1]);
    // ลบข้อมูลพยาบาลออกจาก notes เพื่อแสดงผล
    $clean_notes = preg_replace('/\n\nAssisted by Nurse: .+?(?:\n|$)/', '', $record['notes']);
    $record['notes'] = $clean_notes;
}

// คำนวณ BMI ถ้ามีข้อมูล
$bmi_info = '';
if ($record['bmi']) {
    $bmi = $record['bmi'];
    if ($bmi < 18.5) {
        $bmi_category = 'Underweight';
        $bmi_class = 'warning';
    } elseif ($bmi < 25) {
        $bmi_category = 'Normal';
        $bmi_class = 'success';
    } elseif ($bmi < 30) {
        $bmi_category = 'Overweight';
        $bmi_class = 'warning';
    } else {
        $bmi_category = 'Obese';
        $bmi_class = 'danger';
    }
    $bmi_info = "<span class='badge bg-{$bmi_class}'>{$bmi} ({$bmi_category})</span>";
}

// Include Header และ Sidebar
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<!-- Layout Page -->
<div class="layout-page">
   

    <!-- Content wrapper -->
    <div class="content-wrapper">
        <div class="container-xxl flex-grow-1 container-p-y">
            
            <!-- Header -->
            <div class="row">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h4 class="mb-1">Medical Record Details</h4>
                            <p class="text-muted">Complete medical record information</p>
                        </div>
                        <div>
                            <button id="printBtn" class="btn btn-secondary me-2">
                                <i class="bx bx-printer me-1"></i> Print
                            </button>
                            <button id="exportPdfBtn" class="btn btn-danger me-2">
                                <i class="bx bx-file-pdf me-1"></i> Export PDF
                            </button>
                            <a href="medical_records_action.php?action=edit&id=<?php echo $record['id']; ?>" class="btn btn-primary me-2">
                                <i class="bx bx-edit me-1"></i> Edit Record
                            </a>
                            <a href="medical_records_action.php" class="btn btn-outline-secondary">
                                <i class="bx bx-arrow-back me-1"></i> Back to List
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Patient Information -->
                <div class="col-lg-4 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bx bx-user me-2 text-primary"></i>Patient Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="text-center mb-4">
                                <div class="patient-avatar mx-auto mb-3" style="width: 80px; height: 80px; font-size: 32px;">
                                    <?php echo strtoupper(substr($record['first_name'], 0, 1) . substr($record['last_name'], 0, 1)); ?>
                                </div>
                                <h5 class="mb-1"><?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?></h5>
                                <p class="text-muted mb-0">Patient ID: <?php echo str_pad($record['patient_id'], 4, '0', STR_PAD_LEFT); ?></p>
                            </div>
                            
                            <div class="info-row">
                                <div class="info-label">Age</div>
                                <div class="info-value"><?php echo $record['age']; ?> years old</div>
                            </div>
                            
                            <div class="info-row">
                                <div class="info-label">Gender</div>
                                <div class="info-value">
                                    <?php 
                                    $gender_full = ['M' => 'Male', 'F' => 'Female', 'O' => 'Other'];
                                    echo $gender_full[$record['gender']] ?? $record['gender']; 
                                    ?>
                                </div>
                            </div>
                            
                            <div class="info-row">
                                <div class="info-label">Phone</div>
                                <div class="info-value"><?php echo htmlspecialchars($record['phone']); ?></div>
                            </div>
                            
                            <?php if ($record['email']): ?>
                            <div class="info-row">
                                <div class="info-label">Email</div>
                                <div class="info-value"><?php echo htmlspecialchars($record['email']); ?></div>
                            </div>
                            <?php endif; ?>
                            
                            <div class="info-row">
                                <div class="info-label">Address</div>
                                <div class="info-value"><?php echo htmlspecialchars($record['address']); ?></div>
                            </div>
                            
                            <?php if ($record['emergency_contact_name']): ?>
                            <hr class="my-3">
                            <h6 class="text-danger mb-3">Emergency Contact</h6>
                            <div class="info-row">
                                <div class="info-label">Name</div>
                                <div class="info-value"><?php echo htmlspecialchars($record['emergency_contact_name']); ?></div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Relationship</div>
                                <div class="info-value"><?php echo htmlspecialchars($record['emergency_contact_relationship']); ?></div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Phone</div>
                                <div class="info-value"><?php echo htmlspecialchars($record['emergency_contact_phone']); ?></div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Medical Record Details -->
                <div class="col-lg-8 mb-4">
                    <div class="card h-100" id="medical-record-card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bx bx-health me-2 text-success"></i>Medical Record</h5>
                            <small class="text-muted">Record ID: #<?php echo str_pad($record['id'], 6, '0', STR_PAD_LEFT); ?></small>
                        </div>
                        <div class="card-body">
                            <!-- Visit Information -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <div class="info-row">
                                        <div class="info-label">Visit Date & Time</div>
                                        <div class="info-value">
                                            <span class="fw-bold"><?php echo date('F j, Y', strtotime($record['visit_date'])); ?></span>
                                            <br><small class="text-muted"><?php echo date('H:i A', strtotime($record['visit_date'])); ?></small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-row">
                                        <div class="info-label">Attending Doctor</div>
                                        <div class="info-value">
                                            <span class="badge bg-primary"><?php echo htmlspecialchars($record['doctor_name']); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <?php if ($nurse_name): ?>
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <div class="info-row">
                                        <div class="info-label">Assisting Nurse</div>
                                        <div class="info-value">
                                            <span class="badge bg-success"><?php echo htmlspecialchars($nurse_name); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Medical Information -->
                            <h6 class="text-primary mb-3"><i class="bx bx-clipboard me-2"></i>Medical Information</h6>
                            
                            <div class="info-row mb-3">
                                <div class="info-label">Primary Diagnosis</div>
                                <div class="info-value">
                                    <div class="diagnosis-highlight p-3 bg-light border-start border-primary border-4">
                                        <?php echo nl2br(htmlspecialchars($record['diagnosis'])); ?>
                                    </div>
                                </div>
                            </div>

                            <?php if ($record['symptoms']): ?>
                            <div class="info-row mb-3">
                                <div class="info-label">Symptoms</div>
                                <div class="info-value"><?php echo nl2br(htmlspecialchars($record['symptoms'])); ?></div>
                            </div>
                            <?php endif; ?>

                            <?php if ($record['treatment']): ?>
                            <div class="info-row mb-3">
                                <div class="info-label">Treatment</div>
                                <div class="info-value"><?php echo nl2br(htmlspecialchars($record['treatment'])); ?></div>
                            </div>
                            <?php endif; ?>

                            <?php if ($record['prescription']): ?>
                            <div class="info-row mb-3">
                                <div class="info-label">Prescription</div>
                                <div class="info-value">
                                    <div class="prescription-box p-3 bg-warning bg-opacity-10 border border-warning rounded">
                                        <i class="bx bx-capsule me-2 text-warning"></i>
                                        <?php echo nl2br(htmlspecialchars($record['prescription'])); ?>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Vital Signs -->
                            <?php if ($record['weight'] || $record['height'] || $record['temperature'] || $record['pulse_rate'] || $record['blood_pressure_systolic']): ?>
                            <h6 class="text-primary mb-3"><i class="bx bx-heart me-2"></i>Vital Signs</h6>
                            <div class="row mb-4">
                                <?php if ($record['weight'] && $record['height']): ?>
                                <div class="col-md-3">
                                    <div class="vital-sign-card text-center">
                                        <div class="vital-icon bg-info text-white">
                                            <i class="bx bx-body"></i>
                                        </div>
                                        <div class="vital-value"><?php echo $record['weight']; ?> kg</div>
                                        <div class="vital-label">Weight</div>
                                        <small class="text-muted"><?php echo $record['height']; ?> cm</small>
                                        <?php if ($bmi_info): ?>
                                            <div class="mt-1"><?php echo $bmi_info; ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <?php if ($record['temperature']): ?>
                                <div class="col-md-3">
                                    <div class="vital-sign-card text-center">
                                        <div class="vital-icon bg-danger text-white">
                                            <i class="bx bx-thermometer"></i>
                                        </div>
                                        <div class="vital-value"><?php echo $record['temperature']; ?>°C</div>
                                        <div class="vital-label">Temperature</div>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <?php if ($record['pulse_rate']): ?>
                                <div class="col-md-3">
                                    <div class="vital-sign-card text-center">
                                        <div class="vital-icon bg-success text-white">
                                            <i class="bx bx-heart"></i>
                                        </div>
                                        <div class="vital-value"><?php echo $record['pulse_rate']; ?></div>
                                        <div class="vital-label">Pulse (bpm)</div>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <?php if ($record['blood_pressure_systolic'] && $record['blood_pressure_diastolic']): ?>
                                <div class="col-md-3">
                                    <div class="vital-sign-card text-center">
                                        <div class="vital-icon bg-warning text-white">
                                            <i class="bx bx-pulse"></i>
                                        </div>
                                        <div class="vital-value"><?php echo $record['blood_pressure_systolic']; ?>/<?php echo $record['blood_pressure_diastolic']; ?></div>
                                        <div class="vital-label">Blood Pressure</div>
                                        <small class="text-muted">mmHg</small>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>

                            <!-- Next Appointment -->
                            <?php if ($record['next_appointment']): ?>
                            <div class="info-row mb-3">
                                <div class="info-label">Next Appointment</div>
                                <div class="info-value">
                                    <div class="appointment-box p-3 bg-info bg-opacity-10 border border-info rounded">
                                        <i class="bx bx-calendar-check me-2 text-info"></i>
                                        <span class="fw-bold"><?php echo date('F j, Y \a\t H:i A', strtotime($record['next_appointment'])); ?></span>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Additional Notes -->
                            <?php if ($record['notes']): ?>
                            <div class="info-row mb-3">
                                <div class="info-label">Additional Notes</div>
                                <div class="info-value">
                                    <div class="notes-box p-3 bg-secondary bg-opacity-10 border border-secondary rounded">
                                        <?php echo nl2br(htmlspecialchars($record['notes'])); ?>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Record Metadata -->
                            <hr class="my-4">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="info-row">
                                        <div class="info-label">Created</div>
                                        <div class="info-value text-muted"><?php echo date('F j, Y \a\t H:i', strtotime($record['created_at'])); ?></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-row">
                                        <div class="info-label">Last Updated</div>
                                        <div class="info-value text-muted"><?php echo date('F j, Y \a\t H:i', strtotime($record['updated_at'])); ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Other Records -->
            <?php if (!empty($other_records)): ?>
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bx bx-history me-2 text-info"></i>Other Records for This Patient</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Visit Date</th>
                                            <th>Diagnosis</th>
                                            <th>Doctor</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($other_records as $other_record): ?>
                                        <tr>
                                            <td><?php echo date('M j, Y', strtotime($other_record['visit_date'])); ?></td>
                                            <td><?php echo htmlspecialchars(substr($other_record['diagnosis'], 0, 50) . (strlen($other_record['diagnosis']) > 50 ? '...' : '')); ?></td>
                                            <td><?php echo htmlspecialchars($other_record['doctor_name']); ?></td>
                                            <td>
                                                <a href="?action=view&id=<?php echo $other_record['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="bx bx-show"></i> View
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<!-- PDF Export Library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<style>
.patient-avatar {
    background: linear-gradient(135deg, #007bff, #0056b3);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
}

.info-row {
    margin-bottom: 15px;
}

.info-label {
    font-weight: 600;
    margin-bottom: 5px;
    color: #333;
    font-size: 0.875rem;
}

.info-value {
    color: #697a8d;
    line-height: 1.5;
}

.diagnosis-highlight {
    font-weight: 500;
    color: #333;
}

.prescription-box {
    font-family: 'Courier New', monospace;
}

.vital-sign-card {
    padding: 1rem;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    margin-bottom: 1rem;
    background: #fff;
    transition: box-shadow 0.2s ease;
}

.vital-sign-card:hover {
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.vital-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 10px;
    font-size: 18px;
}

.vital-value {
    font-size: 1.25rem;
    font-weight: bold;
    color: #333;
    margin-bottom: 5px;
}

.vital-label {
    font-size: 0.875rem;
    color: #697a8d;
    font-weight: 500;
}

.appointment-box, .notes-box {
    border-radius: 8px;
}

/* Print Styles */
@media print {
    .layout-menu, 
    .layout-navbar,
    .content-footer,
    .btn,
    .card-header h5 .text-muted {
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
        page-break-inside: avoid;
    }
    
    .card-body {
        padding: 15px !important;
    }
    
    .vital-sign-card {
        border: 1px solid #ccc !important;
        box-shadow: none !important;
    }
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .vital-sign-card {
        margin-bottom: 0.5rem;
    }
    
    .patient-avatar {
        width: 60px !important;
        height: 60px !important;
        font-size: 24px !important;
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
    const element = document.getElementById('medical-record-card');
    
    // HTML2PDF configuration
    const opt = {
        margin: 10,
        filename: 'medical_record_<?php echo str_pad($record['id'], 6, '0', STR_PAD_LEFT); ?>.pdf',
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2 },
        jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
    };
    
    // Generate PDF
    html2pdf().set(opt).from(element).save();
});

// Enhanced interactions
document.addEventListener('DOMContentLoaded', function() {
    // Add hover effects to vital signs cards
    const vitalCards = document.querySelectorAll('.vital-sign-card');
    vitalCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });

    // Add smooth scrolling to navigation
    const links = document.querySelectorAll('a[href^="#"]');
    links.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({ behavior: 'smooth' });
            }
        });
    });

    // Highlight urgent values
    const temperature = <?php echo $record['temperature'] ? $record['temperature'] : 'null'; ?>;
    const systolic = <?php echo $record['blood_pressure_systolic'] ? $record['blood_pressure_systolic'] : 'null'; ?>;
    
    if (temperature && (temperature > 38.0 || temperature < 36.0)) {
        const tempCard = document.querySelector('.vital-sign-card .bx-thermometer').closest('.vital-sign-card');
        if (tempCard) {
            tempCard.classList.add('border-danger');
            tempCard.style.backgroundColor = '#ffebee';
        }
    }
    
    if (systolic && (systolic > 140 || systolic < 90)) {
        const bpCard = document.querySelector('.vital-sign-card .bx-pulse').closest('.vital-sign-card');
        if (bpCard) {
            bpCard.classList.add('border-warning');
            bpCard.style.backgroundColor = '#fff8e1';
        }
    }
});
</script>

<?php include '../includes/footer.php'; ?>