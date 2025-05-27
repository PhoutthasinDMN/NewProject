<?php
// includes/enhanced-db-functions.php - Enhanced Database & Security Functions

class DatabaseManager {
    private $conn;
    private $stmt;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    /**
     * Secure prepared statement execution
     */
    public function executeQuery($sql, $params = [], $types = '') {
        try {
            $this->stmt = $this->conn->prepare($sql);
            
            if (!$this->stmt) {
                throw new Exception("Prepare failed: " . $this->conn->error);
            }
            
            if (!empty($params)) {
                if (empty($types)) {
                    // Auto-detect types
                    $types = str_repeat('s', count($params));
                }
                $this->stmt->bind_param($types, ...$params);
            }
            
            $result = $this->stmt->execute();
            
            if (!$result) {
                throw new Exception("Execute failed: " . $this->stmt->error);
            }
            
            return $this->stmt;
            
        } catch (Exception $e) {
            error_log("Database Error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get single result with error handling
     */
    public function getRow($sql, $params = [], $types = '') {
        $stmt = $this->executeQuery($sql, $params, $types);
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
    
    /**
     * Get multiple results with pagination
     */
    public function getRows($sql, $params = [], $types = '', $page = 1, $limit = 50) {
        // Add pagination if not already in query
        if (stripos($sql, 'LIMIT') === false) {
            $offset = ($page - 1) * $limit;
            $sql .= " LIMIT $limit OFFSET $offset";
        }
        
        $stmt = $this->executeQuery($sql, $params, $types);
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Get total count for pagination
     */
    public function getCount($sql, $params = [], $types = '') {
        // Convert SELECT query to COUNT query
        $countSql = preg_replace('/SELECT.*?FROM/i', 'SELECT COUNT(*) as total FROM', $sql);
        $countSql = preg_replace('/ORDER BY.*$/i', '', $countSql);
        $countSql = preg_replace('/LIMIT.*$/i', '', $countSql);
        
        $result = $this->getRow($countSql, $params, $types);
        return $result ? (int)$result['total'] : 0;
    }
    
    /**
     * Insert with validation
     */
    public function insert($table, $data, $validate = true) {
        if ($validate) {
            $this->validateTableName($table);
            $this->validateData($data);
        }
        
        $columns = array_keys($data);
        $placeholders = str_repeat('?,', count($data) - 1) . '?';
        
        $sql = "INSERT INTO `$table` (`" . implode('`, `', $columns) . "`) VALUES ($placeholders)";
        
        $this->executeQuery($sql, array_values($data));
        
        return $this->conn->insert_id;
    }
    
    /**
     * Update with validation
     */
    public function update($table, $data, $where, $whereParams = [], $validate = true) {
        if ($validate) {
            $this->validateTableName($table);
            $this->validateData($data);
        }
        
        $setParts = [];
        foreach (array_keys($data) as $column) {
            $setParts[] = "`$column` = ?";
        }
        
        $sql = "UPDATE `$table` SET " . implode(', ', $setParts) . " WHERE $where";
        
        $params = array_merge(array_values($data), $whereParams);
        
        $this->executeQuery($sql, $params);
        
        return $this->conn->affected_rows;
    }
    
    /**
     * Safe delete with confirmation
     */
    public function delete($table, $where, $whereParams = [], $validate = true) {
        if ($validate) {
            $this->validateTableName($table);
            
            if (empty($where) || empty($whereParams)) {
                throw new Exception("Delete operations must have WHERE conditions");
            }
        }
        
        $sql = "DELETE FROM `$table` WHERE $where";
        
        $this->executeQuery($sql, $whereParams);
        
        return $this->conn->affected_rows;
    }
    
    /**
     * Validate table name to prevent SQL injection
     */
    private function validateTableName($table) {
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $table)) {
            throw new Exception("Invalid table name: $table");
        }
    }
    
    /**
     * Validate data array
     */
    private function validateData($data) {
        if (empty($data) || !is_array($data)) {
            throw new Exception("Data must be a non-empty array");
        }
        
        foreach (array_keys($data) as $key) {
            if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $key)) {
                throw new Exception("Invalid column name: $key");
            }
        }
    }
    
    /**
     * Transaction support
     */
    public function beginTransaction() {
        return $this->conn->begin_transaction();
    }
    
    public function commit() {
        return $this->conn->commit();
    }
    
    public function rollback() {
        return $this->conn->rollback();
    }
    
    /**
     * Get last error
     */
    public function getError() {
        return $this->conn->error;
    }
    
    /**
     * Close statement
     */
    public function close() {
        if ($this->stmt) {
            $this->stmt->close();
        }
    }
}

class SecurityManager {
    /**
     * Enhanced input sanitization
     */
    public static function sanitizeInput($input, $type = 'string') {
        if (is_array($input)) {
            return array_map(function($item) use ($type) {
                return self::sanitizeInput($item, $type);
            }, $input);
        }
        
        // Basic cleaning
        $input = trim($input);
        $input = stripslashes($input);
        
        switch ($type) {
            case 'email':
                return filter_var($input, FILTER_SANITIZE_EMAIL);
                
            case 'url':
                return filter_var($input, FILTER_SANITIZE_URL);
                
            case 'int':
                return filter_var($input, FILTER_SANITIZE_NUMBER_INT);
                
            case 'float':
                return filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                
            case 'html':
                return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
                
            case 'sql':
                global $conn;
                return $conn->real_escape_string($input);
                
            case 'filename':
                return preg_replace('/[^a-zA-Z0-9._-]/', '', $input);
                
            default: // string
                return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        }
    }
    
    /**
     * Validate input data
     */
    public static function validateInput($input, $rules) {
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            $value = isset($input[$field]) ? $input[$field] : null;
            
            // Required validation
            if (isset($rule['required']) && $rule['required'] && empty($value)) {
                $errors[$field] = ucfirst($field) . ' is required';
                continue;
            }
            
            if (empty($value)) continue; // Skip other validations if empty and not required
            
            // Type validation
            if (isset($rule['type'])) {
                switch ($rule['type']) {
                    case 'email':
                        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $errors[$field] = 'Invalid email format';
                        }
                        break;
                        
                    case 'phone':
                        if (!preg_match('/^[\d\s\-\(\)\+]{10,}$/', $value)) {
                            $errors[$field] = 'Invalid phone format';
                        }
                        break;
                        
                    case 'date':
                        if (!DateTime::createFromFormat('Y-m-d', $value)) {
                            $errors[$field] = 'Invalid date format (YYYY-MM-DD)';
                        }
                        break;
                        
                    case 'int':
                        if (!filter_var($value, FILTER_VALIDATE_INT)) {
                            $errors[$field] = 'Must be a valid integer';
                        }
                        break;
                        
                    case 'float':
                        if (!filter_var($value, FILTER_VALIDATE_FLOAT)) {
                            $errors[$field] = 'Must be a valid number';
                        }
                        break;
                }
            }
            
            // Length validation
            if (isset($rule['min_length']) && strlen($value) < $rule['min_length']) {
                $errors[$field] = ucfirst($field) . ' must be at least ' . $rule['min_length'] . ' characters';
            }
            
            if (isset($rule['max_length']) && strlen($value) > $rule['max_length']) {
                $errors[$field] = ucfirst($field) . ' must not exceed ' . $rule['max_length'] . ' characters';
            }
            
            // Range validation
            if (isset($rule['min']) && $value < $rule['min']) {
                $errors[$field] = ucfirst($field) . ' must be at least ' . $rule['min'];
            }
            
            if (isset($rule['max']) && $value > $rule['max']) {
                $errors[$field] = ucfirst($field) . ' must not exceed ' . $rule['max'];
            }
            
            // Custom pattern validation
            if (isset($rule['pattern']) && !preg_match($rule['pattern'], $value)) {
                $message = isset($rule['pattern_message']) ? $rule['pattern_message'] : 'Invalid format';
                $errors[$field] = $message;
            }
            
            // Custom validation function
            if (isset($rule['custom']) && is_callable($rule['custom'])) {
                $customResult = $rule['custom']($value);
                if ($customResult !== true) {
                    $errors[$field] = $customResult;
                }
            }
        }
        
        return $errors;
    }
    
    /**
     * Generate secure token
     */
    public static function generateToken($length = 32) {
        return bin2hex(random_bytes($length));
    }
    
    /**
     * Verify CSRF token
     */
    public static function verifyCsrfToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Generate CSRF token
     */
    public static function generateCsrfToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = self::generateToken();
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Rate limiting
     */
    public static function checkRateLimit($key, $maxAttempts = 5, $timeWindow = 300) {
        $cacheFile = sys_get_temp_dir() . '/rate_limit_' . md5($key);
        
        $attempts = 0;
        if (file_exists($cacheFile)) {
            $data = json_decode(file_get_contents($cacheFile), true);
            if ($data && $data['time'] > time() - $timeWindow) {
                $attempts = $data['attempts'];
            }
        }
        
        if ($attempts >= $maxAttempts) {
            return false;
        }
        
        // Update attempts
        $attempts++;
        file_put_contents($cacheFile, json_encode([
            'attempts' => $attempts,
            'time' => time()
        ]));
        
        return true;
    }
    
    /**
     * Password strength validation
     */
    public static function validatePassword($password) {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = "Password must be at least 8 characters long";
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = "Password must contain at least one lowercase letter";
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = "Password must contain at least one uppercase letter";
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = "Password must contain at least one number";
        }
        
        if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
            $errors[] = "Password must contain at least one special character";
        }
        
        return $errors;
    }
    
    /**
     * Secure password hashing
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536, // 64 MB
            'time_cost' => 4,       // 4 iterations
            'threads' => 3,         // 3 threads
        ]);
    }
    
    /**
     * Verify password
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * Check if password needs rehashing
     */
    public static function needsRehash($hash) {
        return password_needs_rehash($hash, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3,
        ]);
    }
    
    /**
     * File upload security
     */
    public static function validateFileUpload($file, $allowedTypes = [], $maxSize = 5242880) {
        $errors = [];
        
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            switch ($file['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $errors[] = "File is too large";
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $errors[] = "File upload was interrupted";
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $errors[] = "No file was uploaded";
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    $errors[] = "Missing temporary folder";
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    $errors[] = "Failed to write file to disk";
                    break;
                default:
                    $errors[] = "Unknown upload error";
            }
            return $errors;
        }
        
        // Check file size
        if ($file['size'] > $maxSize) {
            $errors[] = "File size exceeds " . number_format($maxSize / 1024 / 1024, 1) . "MB limit";
        }
        
        // Check file type
        if (!empty($allowedTypes)) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            if (!in_array($mimeType, $allowedTypes) && !in_array($extension, $allowedTypes)) {
                $errors[] = "File type not allowed";
            }
        }
        
        // Check for malicious files
        $content = file_get_contents($file['tmp_name'], false, null, 0, 1024);
        if (preg_match('/<\?php|<script|javascript:/i', $content)) {
            $errors[] = "File contains potentially malicious content";
        }
        
        return $errors;
    }
    
    /**
     * Generate secure filename
     */
    public static function generateSecureFilename($originalName) {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $basename = pathinfo($originalName, PATHINFO_FILENAME);
        
        // Sanitize basename
        $basename = preg_replace('/[^a-zA-Z0-9._-]/', '', $basename);
        $basename = substr($basename, 0, 50); // Limit length
        
        // Add timestamp and random string
        $timestamp = date('YmdHis');
        $random = bin2hex(random_bytes(4));
        
        return $basename . '_' . $timestamp . '_' . $random . '.' . $extension;
    }
    
    /**
     * Log security events
     */
    public static function logSecurityEvent($event, $details = [], $level = 'INFO') {
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'level' => $level,
            'event' => $event,
            'user_id' => $_SESSION['user_id'] ?? 'anonymous',
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'details' => $details
        ];
        
        $logEntry = json_encode($logData) . "\n";
        
        // Log to file
        $logFile = 'logs/security_' . date('Y-m-d') . '.log';
        
        // Create logs directory if it doesn't exist
        if (!is_dir('logs')) {
            mkdir('logs', 0755, true);
        }
        
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
        
        // Also log to database if table exists
        global $conn;
        if ($conn) {
            $checkTable = $conn->query("SHOW TABLES LIKE 'security_logs'");
            if ($checkTable && $checkTable->num_rows > 0) {
                $stmt = $conn->prepare("INSERT INTO security_logs (timestamp, level, event, user_id, ip_address, user_agent, details) VALUES (?, ?, ?, ?, ?, ?, ?)");
                if ($stmt) {
                    $detailsJson = json_encode($details);
                    $stmt->bind_param("sssssss", $logData['timestamp'], $level, $event, $logData['user_id'], $logData['ip_address'], $logData['user_agent'], $detailsJson);
                    $stmt->execute();
                }
            }
        }
    }
}

// Enhanced functions for medical system
class MedicalSystemQueries {
    private $db;
    
    public function __construct($connection) {
        $this->db = new DatabaseManager($connection);
    }
    
    /**
     * Get patients with advanced filtering and pagination
     */
    public function getPatients($filters = [], $page = 1, $limit = 20) {
        $whereConditions = [];
        $params = [];
        $types = '';
        
        // Build WHERE clause based on filters
        if (!empty($filters['search'])) {
            $searchTerm = '%' . $filters['search'] . '%';
            $whereConditions[] = "(first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
            $types .= 'ssss';
        }
        
        if (!empty($filters['gender'])) {
            $whereConditions[] = "gender = ?";
            $params[] = $filters['gender'];
            $types .= 's';
        }
        
        if (!empty($filters['age_min'])) {
            $whereConditions[] = "age >= ?";
            $params[] = (int)$filters['age_min'];
            $types .= 'i';
        }
        
        if (!empty($filters['age_max'])) {
            $whereConditions[] = "age <= ?";
            $params[] = (int)$filters['age_max'];
            $types .= 'i';
        }
        
        if (!empty($filters['date_from'])) {
            $whereConditions[] = "created_at >= ?";
            $params[] = $filters['date_from'];
            $types .= 's';
        }
        
        if (!empty($filters['date_to'])) {
            $whereConditions[] = "created_at <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
            $types .= 's';
        }
        
        // Build SQL
        $sql = "SELECT * FROM patients";
        if (!empty($whereConditions)) {
            $sql .= " WHERE " . implode(" AND ", $whereConditions);
        }
        
        // Add ordering
        $orderBy = isset($filters['sort']) ? $filters['sort'] : 'created_at';
        $orderDir = isset($filters['direction']) && strtoupper($filters['direction']) === 'ASC' ? 'ASC' : 'DESC';
        $sql .= " ORDER BY $orderBy $orderDir";
        
        // Get total count
        $totalCount = $this->db->getCount($sql, $params, $types);
        
        // Get paginated results
        $patients = $this->db->getRows($sql, $params, $types, $page, $limit);
        
        return [
            'data' => $patients,
            'total' => $totalCount,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($totalCount / $limit)
        ];
    }
    
    /**
     * Create patient with validation
     */
    public function createPatient($data) {
        // Validation rules
        $rules = [
            'first_name' => ['required' => true, 'max_length' => 100],
            'last_name' => ['required' => true, 'max_length' => 100],
            'email' => ['type' => 'email', 'max_length' => 255],
            'phone' => ['type' => 'phone', 'max_length' => 20],
            'age' => ['type' => 'int', 'min' => 0, 'max' => 150],
            'dob' => ['type' => 'date'],
            'gender' => ['pattern' => '/^[MFO]$/', 'pattern_message' => 'Gender must be M, F, or O']
        ];
        
        // Validate input
        $errors = SecurityManager::validateInput($data, $rules);
        if (!empty($errors)) {
            throw new Exception("Validation errors: " . implode(", ", $errors));
        }
        
        // Check for duplicate email
        if (!empty($data['email'])) {
            $existing = $this->db->getRow("SELECT id FROM patients WHERE email = ?", [$data['email']], 's');
            if ($existing) {
                throw new Exception("Email already exists");
            }
        }
        
        // Sanitize data
        $cleanData = [];
        foreach ($data as $key => $value) {
            $cleanData[$key] = SecurityManager::sanitizeInput($value);
        }
        
        // Add timestamps
        $cleanData['created_at'] = date('Y-m-d H:i:s');
        $cleanData['updated_at'] = date('Y-m-d H:i:s');
        
        // Insert patient
        $patientId = $this->db->insert('patients', $cleanData);
        
        // Log the action
        SecurityManager::logSecurityEvent('patient_created', ['patient_id' => $patientId]);
        
        return $patientId;
    }
    
    /**
     * Update patient with validation
     */
    public function updatePatient($id, $data) {
        // Validate ID
        if (!is_numeric($id) || $id <= 0) {
            throw new Exception("Invalid patient ID");
        }
        
        // Check if patient exists
        $existing = $this->db->getRow("SELECT id FROM patients WHERE id = ?", [$id], 'i');
        if (!$existing) {
            throw new Exception("Patient not found");
        }
        
        // Validation rules (same as create but not required)
        $rules = [
            'first_name' => ['max_length' => 100],
            'last_name' => ['max_length' => 100],
            'email' => ['type' => 'email', 'max_length' => 255],
            'phone' => ['type' => 'phone', 'max_length' => 20],
            'age' => ['type' => 'int', 'min' => 0, 'max' => 150],
            'dob' => ['type' => 'date'],
            'gender' => ['pattern' => '/^[MFO]$/', 'pattern_message' => 'Gender must be M, F, or O']
        ];
        
        // Validate input
        $errors = SecurityManager::validateInput($data, $rules);
        if (!empty($errors)) {
            throw new Exception("Validation errors: " . implode(", ", $errors));
        }
        
        // Check for duplicate email (excluding current patient)
        if (!empty($data['email'])) {
            $existing = $this->db->getRow("SELECT id FROM patients WHERE email = ? AND id != ?", [$data['email'], $id], 'si');
            if ($existing) {
                throw new Exception("Email already exists");
            }
        }
        
        // Sanitize data
        $cleanData = [];
        foreach ($data as $key => $value) {
            if ($value !== null && $value !== '') {
                $cleanData[$key] = SecurityManager::sanitizeInput($value);
            }
        }
        
        // Add updated timestamp
        $cleanData['updated_at'] = date('Y-m-d H:i:s');
        
        // Update patient
        $affected = $this->db->update('patients', $cleanData, 'id = ?', [$id]);
        
        // Log the action
        SecurityManager::logSecurityEvent('patient_updated', ['patient_id' => $id]);
        
        return $affected > 0;
    }
    
    /**
     * Delete patient with safety checks
     */
    public function deletePatient($id) {
        // Validate ID
        if (!is_numeric($id) || $id <= 0) {
            throw new Exception("Invalid patient ID");
        }
        
        // Check if patient exists
        $patient = $this->db->getRow("SELECT id, first_name, last_name FROM patients WHERE id = ?", [$id], 'i');
        if (!$patient) {
            throw new Exception("Patient not found");
        }
        
        // Check for related records
        $relatedRecords = $this->db->getRow("SELECT COUNT(*) as count FROM medical_records WHERE patient_id = ?", [$id], 'i');
        if ($relatedRecords && $relatedRecords['count'] > 0) {
            throw new Exception("Cannot delete patient with existing medical records");
        }
        
        // Begin transaction
        $this->db->beginTransaction();
        
        try {
            // Delete patient
            $affected = $this->db->delete('patients', 'id = ?', [$id]);
            
            if ($affected === 0) {
                throw new Exception("Failed to delete patient");
            }
            
            // Log the action
            SecurityManager::logSecurityEvent('patient_deleted', [
                'patient_id' => $id,
                'patient_name' => $patient['first_name'] . ' ' . $patient['last_name']
            ]);
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * Get dashboard statistics with caching
     */
    public function getDashboardStats() {
        $cacheKey = 'dashboard_stats_' . date('Y-m-d-H');
        $cacheFile = sys_get_temp_dir() . '/' . md5($cacheKey);
        
        // Check cache
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 3600) {
            return json_decode(file_get_contents($cacheFile), true);
        }
        
        // Calculate stats
        $stats = [];
        
        // Patient statistics
        $stats['patients'] = [
            'total' => $this->db->getRow("SELECT COUNT(*) as count FROM patients")['count'],
            'new_this_month' => $this->db->getRow("SELECT COUNT(*) as count FROM patients WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)")['count'],
            'by_gender' => $this->db->getRows("SELECT gender, COUNT(*) as count FROM patients GROUP BY gender")
        ];
        
        // Medical records statistics
        $stats['records'] = [
            'total' => $this->db->getRow("SELECT COUNT(*) as count FROM medical_records")['count'],
            'this_month' => $this->db->getRow("SELECT COUNT(*) as count FROM medical_records WHERE visit_date >= DATE_SUB(NOW(), INTERVAL 1 MONTH)")['count']
        ];
        
        // Doctor statistics
        $stats['doctors'] = [
            'total' => $this->db->getRow("SELECT COUNT(*) as count FROM doctors")['count'],
            'by_specialization' => $this->db->getRows("SELECT specialization, COUNT(*) as count FROM doctors GROUP BY specialization ORDER BY count DESC")
        ];
        
        // Upcoming appointments
        $stats['appointments'] = [
            'upcoming' => $this->db->getRow("SELECT COUNT(*) as count FROM medical_records WHERE next_appointment > NOW()")['count'],
            'today' => $this->db->getRow("SELECT COUNT(*) as count FROM medical_records WHERE DATE(next_appointment) = CURDATE()")['count']
        ];
        
        // Cache the results
        file_put_contents($cacheFile, json_encode($stats));
        
        return $stats;
    }
    
    /**
     * Search across multiple tables
     */
    public function globalSearch($query, $limit = 50) {
        $searchTerm = '%' . SecurityManager::sanitizeInput($query) . '%';
        $results = [];
        
        // Search patients
        $patients = $this->db->getRows(
            "SELECT 'patient' as type, id, CONCAT(first_name, ' ', last_name) as title, email as subtitle, created_at 
             FROM patients 
             WHERE first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR phone LIKE ?
             LIMIT ?",
            [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $limit],
            'ssssi'
        );
        
        // Search doctors
        $doctors = $this->db->getRows(
            "SELECT 'doctor' as type, id, CONCAT('Dr. ', first_name, ' ', last_name) as title, specialization as subtitle, created_at 
             FROM doctors 
             WHERE first_name LIKE ? OR last_name LIKE ? OR specialization LIKE ?
             LIMIT ?",
            [$searchTerm, $searchTerm, $searchTerm, $limit],
            'sssi'
        );
        
        // Search medical records
        $records = $this->db->getRows(
            "SELECT 'record' as type, mr.id, CONCAT(p.first_name, ' ', p.last_name, ' - ', mr.diagnosis) as title, DATE_FORMAT(mr.visit_date, '%Y-%m-%d') as subtitle, mr.visit_date as created_at
             FROM medical_records mr
             JOIN patients p ON mr.patient_id = p.id
             WHERE mr.diagnosis LIKE ? OR mr.treatment LIKE ? OR mr.notes LIKE ?
             LIMIT ?",
            [$searchTerm, $searchTerm, $searchTerm, $limit],
            'sssi'
        );
        
        // Combine and sort results
        $results = array_merge($patients, $doctors, $records);
        
        // Sort by relevance and date
        usort($results, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        
        return array_slice($results, 0, $limit);
    }
}

// Initialize enhanced database manager
function getEnhancedDB() {
    global $conn;
    static $enhancedDB = null;
    
    if ($enhancedDB === null) {
        $enhancedDB = new MedicalSystemQueries($conn);
    }
    
    return $enhancedDB;
}

// Enhanced error handler
function handleDatabaseError($error, $query = '') {
    // Log the error
    error_log("Database Error: " . $error . " | Query: " . $query);
    
    // Log security event
    SecurityManager::logSecurityEvent('database_error', [
        'error' => $error,
        'query' => $query
    ], 'ERROR');
    
    // Return user-friendly message
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        return "Database Error: " . $error;
    } else {
        return "An error occurred while processing your request. Please try again.";
    }
}

// Enhanced session security
function enhanceSessionSecurity() {
    // Regenerate session ID periodically
    if (!isset($_SESSION['last_regeneration'])) {
        $_SESSION['last_regeneration'] = time();
    } elseif (time() - $_SESSION['last_regeneration'] > 300) { // 5 minutes
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
    
    // Check for session hijacking
    if (isset($_SESSION['user_ip']) && $_SESSION['user_ip'] !== $_SERVER['REMOTE_ADDR']) {
        SecurityManager::logSecurityEvent('session_hijack_attempt', [
            'original_ip' => $_SESSION['user_ip'],
            'current_ip' => $_SERVER['REMOTE_ADDR']
        ], 'WARNING');
        
        session_destroy();
        header('Location: ../auth/login.php?error=session_expired');
        exit;
    }
    
    // Set user IP on first login
    if (!isset($_SESSION['user_ip'])) {
        $_SESSION['user_ip'] = $_SERVER['REMOTE_ADDR'];
    }
    
    // Check session timeout
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > 3600) { // 1 hour
        session_destroy();
        header('Location: ../auth/login.php?error=session_timeout');
        exit;
    }
    
    $_SESSION['last_activity'] = time();
}

// CSRF protection helper
function csrf_token() {
    return SecurityManager::generateCsrfToken();
}

function csrf_field() {
    $token = csrf_token();
    return '<input type="hidden" name="_token" value="' . htmlspecialchars($token) . '">';
}

function verify_csrf() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['_token'] ?? '';
        if (!SecurityManager::verifyCsrfToken($token)) {
            SecurityManager::logSecurityEvent('csrf_token_mismatch', [], 'WARNING');
            http_response_code(403);
            die('CSRF token mismatch');
        }
    }
}

// Auto-call session security enhancement
if (session_status() === PHP_SESSION_ACTIVE) {
    enhanceSessionSecurity();
}

?>