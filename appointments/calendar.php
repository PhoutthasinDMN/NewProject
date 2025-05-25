<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

requireLogin();

// ตั้งค่าสำหรับ includes
$assets_path = '../assets/';
$page_title = 'Calendar View - Appointments';

// ดึงข้อมูลผู้ใช้
$user_id = $_SESSION['user_id'];
$sql = "SELECT username, email, role FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$isAdmin = ($user['role'] == 'admin');

// Get current month and year from URL parameters
$current_month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
$current_year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// Validate month and year
if ($current_month < 1 || $current_month > 12) {
    $current_month = date('n');
}
if ($current_year < 2020 || $current_year > 2030) {
    $current_year = date('Y');
}

// Get first and last day of the month
$first_day = mktime(0, 0, 0, $current_month, 1, $current_year);
$last_day = mktime(23, 59, 59, $current_month, date('t', $first_day), $current_year);

// แก้ไข SQL query ให้มี placeholder และเพิ่ม DATE() function
$appointments_sql = "
    SELECT 
        mr.id as record_id,
        mr.patient_id,
        mr.next_appointment,
        mr.diagnosis,
        mr.doctor_name,
        mr.visit_date as last_visit,
        p.first_name,
        p.last_name,
        p.phone,
        p.email,
        p.age,
        DATE(mr.next_appointment) as appointment_date,
        TIME(mr.next_appointment) as appointment_time,
        CASE 
            WHEN mr.next_appointment < NOW() THEN 'overdue'
            WHEN DATE(mr.next_appointment) = CURDATE() THEN 'today'
            WHEN mr.next_appointment < DATE_ADD(NOW(), INTERVAL 7 DAY) THEN 'upcoming'
            ELSE 'scheduled'
        END as status
    FROM medical_records mr
    JOIN patients p ON mr.patient_id = p.id
    WHERE mr.next_appointment IS NOT NULL 
    AND DATE(mr.next_appointment) BETWEEN ? AND ?
    ORDER BY mr.next_appointment ASC
";

try {
    $stmt = $conn->prepare($appointments_sql);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $start_date = date('Y-m-d', $first_day);
    $end_date = date('Y-m-d', $last_day);
    
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $appointments_result = $stmt->get_result();

    // Group appointments by date
    $appointments_by_date = [];
    while ($row = $appointments_result->fetch_assoc()) {
        $date = $row['appointment_date'];
        if (!isset($appointments_by_date[$date])) {
            $appointments_by_date[$date] = [];
        }
        $appointments_by_date[$date][] = $row;
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    error_log("Database error in calendar.php: " . $e->getMessage());
    $appointments_by_date = [];
    $_SESSION['error_message'] = "เกิดข้อผิดพลาดในการดึงข้อมูลปฏิทินนัดหมาย";
}

// Calendar helper functions
function getMonthName($month) {
    $months = [
        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
        5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
        9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
    ];
    return $months[$month];
}

function getDayName($day) {
    $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    return $days[$day];
}

// Navigation URLs
$prev_month = $current_month - 1;
$prev_year = $current_year;
if ($prev_month < 1) {
    $prev_month = 12;
    $prev_year--;
}

$next_month = $current_month + 1;
$next_year = $current_year;
if ($next_month > 12) {
    $next_month = 1;
    $next_year++;
}

$today = date('Y-m-d');

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<!-- Layout Page -->
<div class="layout-page">
    <!-- Content wrapper -->
    <div class="content-wrapper">
        <div class="container-xxl flex-grow-1 container-p-y">
            
            <!-- แสดงข้อความ error หากมี -->
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bx bx-error me-2"></i>
                    <?php echo htmlspecialchars($_SESSION['error_message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>
            
            <!-- Header -->
            <div class="row">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h4 class="mb-1"><i class="bx bx-calendar-alt me-2 text-primary"></i>Appointments Calendar</h4>
                            <p class="text-muted">View and manage appointments in calendar format</p>
                        </div>
                        <div>
                            <a href="appointments_action.php?action=add" class="btn btn-primary me-2">
                                <i class="bx bx-plus me-2"></i>Schedule New
                            </a>
                            <a href="appointments.php" class="btn btn-outline-secondary">
                                <i class="bx bx-list-ul me-2"></i>List View
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Calendar Navigation -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
                                    <a href="?month=<?php echo $prev_month; ?>&year=<?php echo $prev_year; ?>" class="btn btn-outline-primary me-3">
                                        <i class="bx bx-chevron-left"></i> Previous
                                    </a>
                                    <h5 class="mb-0 me-3"><?php echo getMonthName($current_month) . ' ' . $current_year; ?></h5>
                                    <a href="?month=<?php echo $next_month; ?>&year=<?php echo $next_year; ?>" class="btn btn-outline-primary">
                                        Next <i class="bx bx-chevron-right"></i>
                                    </a>
                                </div>
                                <div class="d-flex align-items-center">
                                    <a href="calendar.php" class="btn btn-outline-info me-2">
                                        <i class="bx bx-calendar-check me-1"></i>Today
                                    </a>
                                    <div class="dropdown">
                                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                            <i class="bx bx-calendar me-1"></i>Jump to Month
                                        </button>
                                        <ul class="dropdown-menu">
                                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                                <li><a class="dropdown-item <?php echo $m == $current_month ? 'active' : ''; ?>" 
                                                      href="?month=<?php echo $m; ?>&year=<?php echo $current_year; ?>">
                                                    <?php echo getMonthName($m); ?>
                                                </a></li>
                                            <?php endfor; ?>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Calendar Legend -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body py-2">
                            <div class="d-flex justify-content-center align-items-center gap-4">
                                <small class="d-flex align-items-center">
                                    <span class="badge bg-danger me-2">●</span>Overdue
                                </small>
                                <small class="d-flex align-items-center">
                                    <span class="badge bg-success me-2">●</span>Today
                                </small>
                                <small class="d-flex align-items-center">
                                    <span class="badge bg-warning me-2">●</span>Upcoming
                                </small>
                                <small class="d-flex align-items-center">
                                    <span class="badge bg-info me-2">●</span>Scheduled
                                </small>
                                <small class="text-muted">
                                    <i class="bx bx-info-circle me-1"></i>Click on appointments for details
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Calendar Grid -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body p-0">
                            <div class="calendar-container">
                                
                                <!-- Calendar Header (Days of Week) -->
                                <div class="calendar-header">
                                    <div class="calendar-day-header">Sunday</div>
                                    <div class="calendar-day-header">Monday</div>
                                    <div class="calendar-day-header">Tuesday</div>
                                    <div class="calendar-day-header">Wednesday</div>
                                    <div class="calendar-day-header">Thursday</div>
                                    <div class="calendar-day-header">Friday</div>
                                    <div class="calendar-day-header">Saturday</div>
                                </div>

                                <!-- Calendar Body -->
                                <div class="calendar-body">
                                    <?php
                                    // Get first day of month and number of days
                                    $first_day_of_week = date('w', $first_day); // 0 = Sunday
                                    $days_in_month = date('t', $first_day);
                                    
                                    // Calculate previous month's last days to fill the grid
                                    $prev_month_days = date('t', mktime(0, 0, 0, $current_month - 1, 1, $current_year));
                                    
                                    $day_count = 1;
                                    $week_count = 0;
                                    
                                    // Generate calendar grid (6 weeks max)
                                    for ($week = 0; $week < 6; $week++):
                                        echo '<div class="calendar-week">';
                                        
                                        for ($day_of_week = 0; $day_of_week < 7; $day_of_week++):
                                            $current_day = '';
                                            $is_current_month = false;
                                            $is_today = false;
                                            
                                            if ($week == 0 && $day_of_week < $first_day_of_week) {
                                                // Previous month days
                                                $current_day = $prev_month_days - ($first_day_of_week - $day_of_week - 1);
                                                $cell_class = 'calendar-day other-month';
                                            } elseif ($day_count <= $days_in_month) {
                                                // Current month days
                                                $current_day = $day_count;
                                                $is_current_month = true;
                                                $day_count++;
                                                
                                                $current_date = sprintf('%04d-%02d-%02d', $current_year, $current_month, $current_day);
                                                $is_today = ($current_date == $today);
                                                
                                                $cell_class = 'calendar-day current-month';
                                                if ($is_today) {
                                                    $cell_class .= ' today';
                                                }
                                            } else {
                                                // Next month days
                                                $current_day = $day_count - $days_in_month;
                                                $day_count++;
                                                $cell_class = 'calendar-day other-month';
                                            }
                                    ?>
                                            <div class="<?php echo $cell_class; ?>" data-date="<?php echo isset($current_date) ? $current_date : ''; ?>">
                                                <div class="day-number"><?php echo $current_day; ?></div>
                                                
                                                <?php if ($is_current_month): ?>
                                                    <?php
                                                    $current_date = sprintf('%04d-%02d-%02d', $current_year, $current_month, $current_day);
                                                    if (isset($appointments_by_date[$current_date])):
                                                    ?>
                                                        <div class="appointments-list">
                                                            <?php foreach ($appointments_by_date[$current_date] as $appointment): ?>
                                                                <div class="appointment-item <?php echo $appointment['status']; ?>" 
                                                                     data-bs-toggle="modal" 
                                                                     data-bs-target="#appointmentModal"
                                                                     onclick="showAppointmentDetails(<?php echo htmlspecialchars(json_encode($appointment)); ?>)">
                                                                    <div class="appointment-time"><?php echo date('H:i', strtotime($appointment['appointment_time'])); ?></div>
                                                                    <div class="appointment-patient"><?php echo htmlspecialchars(substr($appointment['first_name'] . ' ' . $appointment['last_name'], 0, 15)); ?></div>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </div>
                                    <?php
                                        endfor;
                                        echo '</div>';
                                        
                                        // Break if we've filled all days of the month and the week is complete
                                        if ($day_count > $days_in_month && $day_of_week == 6) {
                                            break;
                                        }
                                    endfor;
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Appointment Details Modal -->
<div class="modal fade" id="appointmentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bx bx-calendar-event me-2"></i>Appointment Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="appointmentDetails">
                <!-- Content will be populated by JavaScript -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <a href="#" id="editAppointmentBtn" class="btn btn-primary">
                    <i class="bx bx-edit-alt me-1"></i>Edit Appointment
                </a>
                <a href="#" id="viewRecordBtn" class="btn btn-outline-info">
                    <i class="bx bx-file-blank me-1"></i>View Medical Record
                </a>
            </div>
        </div>
    </div>
</div>

<style>
/* Calendar Container */
.calendar-container {
    background: white;
    border-radius: 10px;
    overflow: hidden;
}

/* Calendar Header */
.calendar-header {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    background: linear-gradient(135deg, #696cff, #5a67d8);
    color: white;
}

.calendar-day-header {
    padding: 15px 8px;
    text-align: center;
    font-weight: 600;
    font-size: 0.875rem;
}

/* Calendar Body */
.calendar-body {
    display: grid;
    grid-template-rows: repeat(6, 1fr);
    min-height: 600px;
}

.calendar-week {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    border-bottom: 1px solid #e9ecef;
}

.calendar-week:last-child {
    border-bottom: none;
}

/* Calendar Days */
.calendar-day {
    border-right: 1px solid #e9ecef;
    padding: 8px;
    min-height: 100px;
    position: relative;
    background: white;
    transition: all 0.2s ease;
    cursor: pointer;
}

.calendar-day:last-child {
    border-right: none;
}

.calendar-day:hover {
    background-color: #f8f9ff;
}

.calendar-day.other-month {
    background-color: #f8f9fa;
    color: #6c757d;
}

.calendar-day.today {
    background: linear-gradient(135deg, #e3f2fd, #bbdefb);
    border: 2px solid #2196f3;
}

.calendar-day.today .day-number {
    color: #1976d2;
    font-weight: bold;
}

/* Day Numbers */
.day-number {
    font-weight: 600;
    font-size: 0.875rem;
    margin-bottom: 5px;
    color: #333;
}

/* Appointments */
.appointments-list {
    position: absolute;
    top: 25px;
    left: 4px;
    right: 4px;
    bottom: 4px;
    overflow-y: auto;
}

.appointment-item {
    background: white;
    border-radius: 4px;
    padding: 4px 6px;
    margin-bottom: 2px;
    font-size: 0.75rem;
    cursor: pointer;
    transition: all 0.2s ease;
    border-left: 3px solid #ccc;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.appointment-item:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.appointment-item.overdue {
    border-left-color: #dc3545;
    background: linear-gradient(135deg, #fff5f5, #fed7d7);
}

.appointment-item.today {
    border-left-color: #28a745;
    background: linear-gradient(135deg, #f0fff4, #c6f6d5);
}

.appointment-item.upcoming {
    border-left-color: #ffc107;
    background: linear-gradient(135deg, #fffbf0, #feebcb);
}

.appointment-item.scheduled {
    border-left-color: #17a2b8;
    background: linear-gradient(135deg, #f0fdff, #bee3f8);
}

.appointment-time {
    font-weight: 600;
    color: #333;
}

.appointment-patient {
    color: #666;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.past-appointment {
    opacity: 0.6;
    background: #f8f9fa !important;
}

/* Modal Enhancements */
.modal-content {
    border-radius: 15px;
    border: none;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
}

.modal-header {
    background: linear-gradient(135deg, #696cff, #5a67d8);
    color: white;
    border-bottom: none;
}

.modal-header .btn-close {
    filter: brightness(0) invert(1);
}

/* Responsive Design */
@media (max-width: 768px) {
    .calendar-day-header {
        padding: 10px 4px;
        font-size: 0.75rem;
    }
    
    .calendar-day {
        min-height: 80px;
        padding: 4px;
    }
    
    .day-number {
        font-size: 0.75rem;
    }
    
    .appointment-item {
        font-size: 0.65rem;
        padding: 2px 4px;
    }
    
    .appointment-patient {
        display: none;
    }
    
    .appointment-time {
        font-size: 0.7rem;
    }
}

@media (max-width: 576px) {
    .calendar-day {
        min-height: 60px;
    }
    
    .calendar-day-header {
        padding: 8px 2px;
        font-size: 0.7rem;
    }
}

/* Loading Animation */
.loading-spinner {
    display: inline-block;
    width: 20px;
    height: 20px;
    border: 3px solid #f3f3f3;
    border-top: 3px solid #696cff;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Print Styles */
@media print {
    .calendar-container {
        break-inside: avoid;
    }
    
    .appointment-item {
        background: white !important;
        border: 1px solid #000 !important;
        color: #000 !important;
    }
    
    .modal {
        display: none !important;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
    const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Keyboard navigation
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey || e.metaKey) {
            switch(e.key) {
                case 'ArrowLeft':
                    e.preventDefault();
                    window.location.href = '?month=<?php echo $prev_month; ?>&year=<?php echo $prev_year; ?>';
                    break;
                case 'ArrowRight':
                    e.preventDefault();
                    window.location.href = '?month=<?php echo $next_month; ?>&year=<?php echo $next_year; ?>';
                    break;
                case 'Home':
                    e.preventDefault();
                    window.location.href = 'calendar.php';
                    break;
                case 'n':
                    e.preventDefault();
                    window.location.href = 'appointments_action.php?action=add';
                    break;
            }
        }
    });
    
    // Auto-refresh every 5 minutes
    setInterval(() => {
        location.reload();
    }, 300000);
    
    // Highlight current time
    updateCurrentTime();
    setInterval(updateCurrentTime, 60000);
    
    // Add click handler for empty calendar days (to schedule new appointments)
    const calendarDays = document.querySelectorAll('.calendar-day.current-month');
    
    calendarDays.forEach(day => {
        day.addEventListener('dblclick', function(e) {
            if (e.target === this || e.target.classList.contains('day-number')) {
                const dayNumber = this.querySelector('.day-number').textContent;
                const selectedDate = `<?php echo $current_year; ?>-${String(<?php echo $current_month; ?>).padStart(2, '0')}-${String(dayNumber).padStart(2, '0')}`;
                quickScheduleFromCalendar(selectedDate);
            }
        });
        
        // Add visual feedback for double-click
        day.setAttribute('title', 'Double-click to schedule new appointment');
    });
});

function updateCurrentTime() {
    const now = new Date();
    const today = now.toISOString().split('T')[0];
    const currentTime = now.toTimeString().split(' ')[0].slice(0, 5);
    
    // Highlight current time slot if today is visible
    const todayCell = document.querySelector(`[data-date="${today}"]`);
    if (todayCell) {
        const appointments = todayCell.querySelectorAll('.appointment-item');
        appointments.forEach(appointment => {
            const appointmentTime = appointment.querySelector('.appointment-time').textContent;
            if (appointmentTime <= currentTime) {
                appointment.classList.add('past-appointment');
            }
        });
    }
}

function showAppointmentDetails(appointment) {
    const modalBody = document.getElementById('appointmentDetails');
    const editBtn = document.getElementById('editAppointmentBtn');
    const viewBtn = document.getElementById('viewRecordBtn');
    
    // Status badge styling
    const statusClasses = {
        'overdue': 'bg-danger',
        'today': 'bg-success',
        'upcoming': 'bg-warning',
        'scheduled': 'bg-info'
    };
    
    const statusLabels = {
        'overdue': 'Overdue',
        'today': 'Today',
        'upcoming': 'Upcoming',
        'scheduled': 'Scheduled'
    };
    
    const appointmentDate = new Date(appointment.next_appointment);
    const formattedDate = appointmentDate.toLocaleDateString('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
    const formattedTime = appointmentDate.toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit'
    });
    
    modalBody.innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <h6 class="text-primary mb-3"><i class="bx bx-user me-2"></i>Patient Information</h6>
                <div class="d-flex align-items-center mb-3">
                    <div class="avatar avatar-lg me-3">
                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px; font-size: 24px;">
                            <span class="fw-bold">${appointment.first_name.charAt(0)}${appointment.last_name.charAt(0)}</span>
                        </div>
                    </div>
                    <div>
                        <h5 class="mb-1">${appointment.first_name} ${appointment.last_name}</h5>
                        <p class="text-muted mb-0"><i class="bx bx-phone me-1"></i>${appointment.phone || 'No phone'}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <h6 class="text-primary mb-3"><i class="bx bx-calendar me-2"></i>Appointment Details</h6>
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted">Status:</span>
                        <span class="badge ${statusClasses[appointment.status]}">${statusLabels[appointment.status]}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Date:</span>
                        <span class="fw-bold">${formattedDate}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Time:</span>
                        <span class="fw-bold">${formattedTime}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Doctor:</span>
                        <span>${appointment.doctor_name}</span>
                    </div>
                </div>
            </div>
        </div>
        
        <hr class="my-4">
        
        <div class="row">
            <div class="col-12">
                <h6 class="text-primary mb-3"><i class="bx bx-file-blank me-2"></i>Follow-up Information</h6>
                <div class="alert alert-light">
                    <p class="mb-0"><strong>Follow-up for:</strong> ${appointment.diagnosis}</p>
                </div>
            </div>
        </div>
    `;
    
    // Update button links
    editBtn.href = `appointments_action.php?action=edit&record_id=${appointment.record_id}`;
    viewBtn.href = `../medical_records/medical_record_view.php?id=${appointment.record_id}`;
}

// Quick actions
function quickScheduleFromCalendar(date) {
    const baseUrl = 'appointments_action.php?action=add';
    const dateParam = `&date=${date}`;
    window.location.href = baseUrl + dateParam;
}

// Export calendar functionality
function exportCalendar() {
    const month = <?php echo $current_month; ?>;
    const year = <?php echo $current_year; ?>;
    const monthName = '<?php echo getMonthName($current_month); ?>';
    
    // Create a simple text export
    let exportData = `Appointments Calendar - ${monthName} ${year}\n`;
    exportData += "=".repeat(50) + "\n\n";
    
    <?php foreach ($appointments_by_date as $date => $appointments): ?>
        exportData += "<?php echo date('l, F j, Y', strtotime($date)); ?>\n";
        exportData += "-".repeat(30) + "\n";
        <?php foreach ($appointments as $appointment): ?>
            exportData += "• <?php echo date('H:i', strtotime($appointment['appointment_time'])); ?> - <?php echo htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']); ?> (<?php echo htmlspecialchars($appointment['doctor_name']); ?>)\n";
        <?php endforeach; ?>
        exportData += "\n";
    <?php endforeach; ?>
    
    // Download as text file
    const blob = new Blob([exportData], { type: 'text/plain' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `appointments_${month}_${year}.txt`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
}

// Print calendar
function printCalendar() {
    window.print();
}

// Helper function for month names
function getMonthName(month) {
    const months = [
        '', 'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'
    ];
    return months[month];
}

// Status color helper
function getStatusColor(status) {
    const colors = {
        'overdue': 'danger',
        'today': 'success', 
        'upcoming': 'warning',
        'scheduled': 'info'
    };
    return colors[status] || 'secondary';
}

// Add keyboard shortcuts info
function showKeyboardShortcuts() {
    const shortcuts = `
        📅 Calendar Keyboard Shortcuts:
        
        Ctrl + ← (Left Arrow)  - Previous Month
        Ctrl + → (Right Arrow) - Next Month  
        Ctrl + Home           - Go to Today
        Ctrl + N              - New Appointment
        
        Double-click empty day - Schedule new appointment
        Click appointment     - View details
    `;
    
    alert(shortcuts);
}

// Add right-click context menu for calendar days
document.addEventListener('DOMContentLoaded', function() {
    const calendarDays = document.querySelectorAll('.calendar-day.current-month');
    
    calendarDays.forEach(day => {
        day.addEventListener('contextmenu', function(e) {
            e.preventDefault();
            
            const dayNumber = this.querySelector('.day-number').textContent;
            const selectedDate = `<?php echo $current_year; ?>-${String(<?php echo $current_month; ?>).padStart(2, '0')}-${String(dayNumber).padStart(2, '0')}`;
            
            // Create context menu
            const contextMenu = document.createElement('div');
            contextMenu.className = 'context-menu';
            contextMenu.style.cssText = `
                position: fixed;
                top: ${e.clientY}px;
                left: ${e.clientX}px;
                background: white;
                border: 1px solid #ccc;
                border-radius: 5px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.2);
                z-index: 1000;
                padding: 5px 0;
                min-width: 150px;
            `;
            
            contextMenu.innerHTML = `
                <div style="padding: 8px 12px; cursor: pointer; hover:background: #f5f5f5;" onclick="quickScheduleFromCalendar('${selectedDate}'); document.body.removeChild(this.parentElement);">
                    <i class="bx bx-plus me-2"></i>Schedule Appointment
                </div>
                <div style="padding: 8px 12px; cursor: pointer;" onclick="exportCalendar(); document.body.removeChild(this.parentElement);">
                    <i class="bx bx-download me-2"></i>Export Calendar
                </div>
                <div style="padding: 8px 12px; cursor: pointer;" onclick="printCalendar(); document.body.removeChild(this.parentElement);">
                    <i class="bx bx-printer me-2"></i>Print Calendar
                </div>
            `;
            
            document.body.appendChild(contextMenu);
            
            // Remove context menu when clicking elsewhere
            setTimeout(() => {
                document.addEventListener('click', function removeMenu() {
                    if (document.body.contains(contextMenu)) {
                        document.body.removeChild(contextMenu);
                    }
                    document.removeEventListener('click', removeMenu);
                });
            }, 100);
        });
    });
});

// Add hover effects for better UX
document.addEventListener('DOMContentLoaded', function() {
    const contextMenuItems = document.querySelectorAll('.context-menu div');
    contextMenuItems.forEach(item => {
        item.addEventListener('mouseenter', function() {
            this.style.backgroundColor = '#f5f5f5';
        });
        item.addEventListener('mouseleave', function() {
            this.style.backgroundColor = 'transparent';
        });
    });
});

// Console logging for debugging and info
console.log('📅 Calendar View Loaded Successfully');
console.log('⌨️ Shortcuts: Ctrl+← (Previous), Ctrl+→ (Next), Ctrl+Home (Today), Ctrl+N (New)');
console.log('🖱️ Double-click empty days to schedule appointments');
console.log('📱 Right-click for context menu');
console.log('🔄 Auto-refresh every 5 minutes');

// Add smooth transitions for navigation
function smoothNavigate(url) {
    document.body.style.opacity = '0.7';
    setTimeout(() => {
        window.location.href = url;
    }, 200);
}

// Enhanced error handling
window.addEventListener('error', function(e) {
    console.error('Calendar Error:', e.error);
    
    // Show user-friendly error message
    const errorDiv = document.createElement('div');
    errorDiv.className = 'alert alert-warning alert-dismissible fade show';
    errorDiv.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; max-width: 300px;';
    errorDiv.innerHTML = `
        <i class="bx bx-error me-2"></i>
        <strong>Oops!</strong> Something went wrong. Please refresh the page.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(errorDiv);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (document.body.contains(errorDiv)) {
            errorDiv.remove();
        }
    }, 5000);
});

// Performance monitoring
const perfObserver = new PerformanceObserver((list) => {
    for (const entry of list.getEntries()) {
        if (entry.loadEventEnd - entry.loadEventStart > 3000) {
            console.warn('⚠️ Calendar loaded slowly:', entry.loadEventEnd - entry.loadEventStart + 'ms');
        }
    }
});

try {
    perfObserver.observe({entryTypes: ['navigation']});
} catch (e) {
    // Performance API not supported in older browsers
    console.log('Performance monitoring not available');
}
</script>

<?php include '../includes/footer.php'; ?>