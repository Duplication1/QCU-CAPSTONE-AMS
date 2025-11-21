<?php
/**
 * Session Messages Component
 * Displays session-based success and error messages as dismissible chips
 * Uses the JavaScript notification system for modern UI
 */

// Check if we have any session messages to display
$has_error = isset($_SESSION['error_message']);
$has_success = isset($_SESSION['success_message']);

if ($has_error || $has_success):
?>
<div id="session-messages-container"></div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($has_error): ?>
    showChip(<?php echo json_encode(htmlspecialchars($_SESSION['error_message'])); ?>, 'error', 'session-messages-container', 7000);
    <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>
    
    <?php if ($has_success): ?>
    showChip(<?php echo json_encode(htmlspecialchars($_SESSION['success_message'])); ?>, 'success', 'session-messages-container', 7000);
    <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>
});
</script>
<?php
endif;
?>
