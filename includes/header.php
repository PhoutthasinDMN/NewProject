<?php
// includes/header.php

// กำหนด path เริ่มต้นถ้าไม่ได้กำหนด
if (!isset($assets_path)) {
    $assets_path = '../assets/';
}

// กำหนด title เริ่มต้น
if (!isset($page_title)) {
    $page_title = 'Medical Dashboard';
}
?>
<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="<?php echo $assets_path; ?>">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title><?php echo htmlspecialchars($page_title); ?> | Medical System</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo $assets_path; ?>img/favicon/favicon.ico" />
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    
    <!-- Icons -->
    <link rel="stylesheet" href="<?php echo $assets_path; ?>vendor/fonts/boxicons.css" />
    
    <!-- Core CSS -->
    <link rel="stylesheet" href="<?php echo $assets_path; ?>vendor/css/core.css" class="template-customizer-core-css" />
    <link rel="stylesheet" href="<?php echo $assets_path; ?>vendor/css/theme-default.css" class="template-customizer-theme-css" />
    <link rel="stylesheet" href="<?php echo $assets_path; ?>css/demo.css" />
    
    <!-- Vendors CSS -->
    <link rel="stylesheet" href="<?php echo $assets_path; ?>vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />
    
    <!-- Extra CSS -->
    <?php if (isset($extra_css) && is_array($extra_css)): ?>
        <?php foreach ($extra_css as $css): ?>
            <link rel="stylesheet" href="<?php echo htmlspecialchars($css); ?>" />
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Extra Scripts (head) -->
    <?php if (isset($extra_scripts_head) && is_array($extra_scripts_head)): ?>
        <?php foreach ($extra_scripts_head as $script): ?>
            <script src="<?php echo htmlspecialchars($script); ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Helpers -->
    <script src="<?php echo $assets_path; ?>vendor/js/helpers.js"></script>
    <script src="<?php echo $assets_path; ?>js/config.js"></script>
    
    <style>
        /* Medical System Custom Styles */
        :root {
            --primary-color: #696cff;
            --primary-dark: #5a67d8;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
        }
        
        .stats-card { 
            border-radius: 15px; 
            transition: all 0.3s ease; 
            border: none; 
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        
        .stats-card:hover { 
            transform: translateY(-5px); 
            box-shadow: 0 12px 40px rgba(0,0,0,0.15); 
        }
        
        .stats-icon { 
            width: 60px; 
            height: 60px; 
            border-radius: 50%; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            color: white; 
            font-size: 24px;
            margin: 0 auto;
        }
        
        .welcome-card { 
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark)); 
            color: white; 
            border-radius: 20px;
            border: none;
            box-shadow: 0 8px 30px rgba(105, 108, 255, 0.2);
        }
        
        .activity-item { 
            padding: 15px 0; 
            border-bottom: 1px solid #f1f1f1;
            transition: background-color 0.2s ease;
        }
        
        .activity-item:hover {
            background-color: #f8f9ff;
            border-radius: 8px;
            padding-left: 10px;
            padding-right: 10px;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .patient-avatar { 
            width: 40px; 
            height: 40px; 
            background: var(--primary-color); 
            color: white; 
            border-radius: 50%; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-size: 14px; 
            font-weight: 600;
            margin-right: 12px;
            flex-shrink: 0;
        }
        
        .table th { 
            background-color: var(--primary-color); 
            color: white; 
            border: none;
            font-weight: 600;
            font-size: 0.875rem;
            padding: 15px 12px;
        }
        
        .table td {
            padding: 12px;
            vertical-align: middle;
            border-color: #f1f1f1;
        }
        
        .table tbody tr:hover {
            background-color: #f8f9ff;
            transform: translateY(-1px);
            transition: all 0.2s ease;
        }
        
        .card { 
            border-radius: 15px; 
            border: none; 
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            transition: box-shadow 0.3s ease;
        }
        
        .card:hover {
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
        }
        
        /* Form Styles */
        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid #d1d5db;
            transition: all 0.2s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(105, 108, 255, 0.15);
        }
        
        /* Button Styles */
        .btn {
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            border: none;
        }
        
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(105, 108, 255, 0.3);
        }
        
        /* Medical Records Specific */
        .record-card {
            border-left: 4px solid var(--primary-color);
            margin-bottom: 20px;
        }
        
        .diagnosis-badge {
            background: var(--primary-color);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .vital-signs-mini {
            font-size: 0.75rem;
        }
        
        .vital-signs-mini .vital-item {
            display: inline-block;
            margin-right: 8px;
            padding: 4px 8px;
            background: #f8f9fa;
            border-radius: 6px;
            border: 1px solid #e9ecef;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .stats-card {
                margin-bottom: 1rem;
            }
            
            .patient-avatar {
                width: 35px;
                height: 35px;
                font-size: 12px;
            }
            
            .activity-item {
                padding: 10px 0;
            }
        }
        
        /* Loading Animation */
        .loading {
            position: relative;
            pointer-events: none;
        }
        
        .loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 20px;
            height: 20px;
            margin: -10px 0 0 -10px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Custom CSS from page */
        <?php if (isset($custom_css)): ?>
            <?php echo $custom_css; ?>
        <?php endif; ?>
    </style>
</head>
<body>
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">