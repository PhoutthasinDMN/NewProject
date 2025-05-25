<?php
// includes/patient_functions.php - Helper Functions for Patient Management

/**
 * Format gender display text
 */
function formatGender($gender) {
    $genders = [
        'M' => 'Male',
        'F' => 'Female', 
        'O' => 'Other'
    ];
    return $genders[$gender] ?? $gender;
}

/**
 * Format date for display (check if function exists first)
 */
if (!function_exists('formatDate')) {
    function formatDate($date, $format = 'd/m/Y') {
        if (empty($date) || $date == '0000-00-00') return '';
        $dateObj = date_create($date);
        return $dateObj ? date_format($dateObj, $format) : '';
    }
}

/**
 * Get user info (check if function exists first)
 */
if (!function_exists('getUserInfo')) {
    function getUserInfo($userId, $conn) {
        $stmt = $conn->prepare("SELECT username, email, role FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
}
function getPatientById($conn, $id) {
    $stmt = $conn->prepare("SELECT * FROM patients WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0 ? $result->fetch_assoc() : null;
}

/**
 * Execute prepared statement with parameters
 */
function executeQuery($conn, $sql, $params = [], $types = '') {
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $result = $stmt->execute();
    return ['success' => $result, 'stmt' => $stmt];
}

/**
 * Redirect with message
 */
function redirect($url, $message = '', $type = 'success') {
    $query = $message ? "?{$type}=" . urlencode($message) : '';
    header("Location: {$url}{$query}");
    exit;
}

/**
 * Validate required patient fields
 */
function validatePatientData($data) {
    $errors = [];
    
    if (empty(trim($data['first_name'] ?? ''))) {
        $errors[] = "First name is required";
    }
    
    if (empty(trim($data['last_name'] ?? ''))) {
        $errors[] = "Last name is required";
    }
    
    return $errors;
}

/**
 * Prepare patient data for database
 */
function preparePatientData($formData, $userId, $isEdit = false) {
    $data = [];
    $allowedFields = [
        'first_name', 'last_name', 'age', 'gender', 'dob', 'phone', 'email',
        'address', 'nationality', 'religion', 'marital_status', 'occupation',
        'emergency_contact_name', 'emergency_contact_relationship', 'emergency_contact_phone'
    ];
    
    foreach ($allowedFields as $field) {
        if (isset($formData[$field])) {
            $data[$field] = sanitize($formData[$field]);
        }
    }
    
    // Handle date format conversion
    if (!empty($data['dob'])) {
        $data['dob'] = date('Y-m-d', strtotime($data['dob']));
    }
    
    // Add timestamps - check if columns exist first
    if ($isEdit) {
        $data['updated_at'] = date('Y-m-d H:i:s');
        // Note: updated_by column doesn't exist in current table structure
    } else {
        $data['created_at'] = date('Y-m-d H:i:s');
        // Note: created_by column doesn't exist in current table structure
    }
    
    return $data;
}

/**
 * Get gender badge class
 */
function getGenderBadgeClass($gender) {
    $classes = [
        'M' => 'badge bg-primary',
        'F' => 'badge bg-info',
        'O' => 'badge bg-secondary'
    ];
    return $classes[$gender] ?? 'badge bg-light text-dark';
}

/**
 * Highlight search terms in text
 */
function highlightSearch($text, $search) {
    if (empty($search)) return htmlspecialchars($text);
    
    $highlighted = preg_replace(
        '/(' . preg_quote($search, '/') . ')/i',
        '<span class="highlight">$1</span>',
        htmlspecialchars($text)
    );
    return $highlighted;
}
?>