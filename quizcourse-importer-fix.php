<?php
/**
 * Plugin Name: QuizCourse Importer Fix
 * Description: Fix for QuizCourse One-Click Importer
 * Version: 1.0.0
 */

// ปิดการใช้งานปลั๊กอินตัวเดิมก่อน
add_action('admin_init', function() {
    deactivate_plugins('quizcourse-importer/quizcourse-importer.php');
    wp_redirect(admin_url('plugins.php?deactivate=true'));
    exit;
});
