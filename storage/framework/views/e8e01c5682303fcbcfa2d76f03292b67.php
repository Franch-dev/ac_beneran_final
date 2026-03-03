<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <meta name="theme-color" content="#1a73e8">
    <meta name="description" content="Sistem Manajemen Servis AC untuk Masjid dan Musholla">
    <title><?php echo $__env->yieldContent('title', 'AC Servis Masjid'); ?></title>
    <link rel="stylesheet" href="<?php echo e(asset('css/style.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('css/style-responsive-improvements.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('css/visual-enhancements.css')); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700&display=swap">
</head>
<?php if(auth()->guard()->check()): ?>
<body class="has-sidebar">
    <?php echo $__env->make('layouts.header', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <div class="sidebar-layout">
        <main id="main-content" class="sidebar-main">
            <?php if(session('success')): ?>
                <div class="alert alert-success"><?php echo e(session('success')); ?></div>
            <?php endif; ?>
            <?php if(session('error')): ?>
                <div class="alert alert-danger"><?php echo e(session('error')); ?></div>
            <?php endif; ?>
            <?php echo $__env->yieldContent('content'); ?>
        </main>
    </div>
    <?php echo $__env->make('layouts.footer', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php else: ?>
<body>
    <?php echo $__env->make('layouts.header', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <main id="main-content" class="main-content">
        <?php if(session('success')): ?>
            <div class="alert alert-success"><?php echo e(session('success')); ?></div>
        <?php endif; ?>
        <?php if(session('error')): ?>
            <div class="alert alert-danger"><?php echo e(session('error')); ?></div>
        <?php endif; ?>
        <?php echo $__env->yieldContent('content'); ?>
    </main>
    <?php echo $__env->make('layouts.footer', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php endif; ?>

    <!-- Global Popup Overlay -->
    <div class="overlay" id="overlay" onclick="closeAllPopups()"></div>

    <!-- Logout Confirm Popup -->
    <div class="popup" id="logoutPopup">
        <div class="popup-header">
            <h3><i class="fas fa-sign-out-alt"></i> Konfirmasi Logout</h3>
            <button class="popup-close" onclick="closePopup('logoutPopup')">&times;</button>
        </div>
        <div class="popup-body">
            <p>Apakah Anda yakin ingin logout?</p>
            <div class="popup-actions">
                <form action="<?php echo e(route('logout')); ?>" method="POST" style="display:inline">
                    <?php echo csrf_field(); ?>
                    <button type="submit" class="btn btn-danger">Ya, Logout</button>
                </form>
                <button class="btn btn-secondary" onclick="closePopup('logoutPopup')">Tidak</button>
            </div>
        </div>
    </div>

    <script src="<?php echo e(asset('js/app.js')); ?>"></script>
    <?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html>
<?php /**PATH C:\laragon\www\ac_beneran_final\resources\views\layouts\app.blade.php ENDPATH**/ ?>