<?php
/**
 * Header Template
 * Contains the initial HTML structure, meta tags, CSS includes
 */

// Get current page
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?php echo isset($page_title) ? $page_title . ' - ' . APP_NAME : APP_NAME; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome for Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Google Fonts - Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Main theme CSS -->
    <link href="assets/css/modern-theme.css" rel="stylesheet">
    
    <!-- Page-specific CSS -->
    <?php if(isset($extra_css)): ?>
        <?php echo $extra_css; ?>
    <?php endif; ?>
    
    <!-- Alert container for dynamic alerts -->
    <div class="alert-container position-fixed top-0 end-0 p-3" style="z-index: 1050;"></div>
</head>
<body class="<?php echo $current_page === 'login.php' ? 'login-page' : ''; ?>">
    <?php if($current_page !== 'login.php'): ?>
    <div class="d-flex">
        <!-- Include the sidebar/navbar -->
        <?php include 'includes/partials/navbar.php'; ?>
        
        <!-- Main content container -->
        <div class="content">
            <div class="container-fluid p-4">
    <?php endif; ?>
