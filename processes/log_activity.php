<?php
/**
 * log_activity.php
 * Call log_action($mysqli, $role, $user_id, $action, $detail) 
 * anywhere in the system to record an activity.
 */
function log_action($mysqli, $role, $user_id, $action, $detail = '') {
    $role    = mysqli_real_escape_string($mysqli, $role ?? 'System');
    $user_id = (int)$user_id;
    $action  = mysqli_real_escape_string($mysqli, $action);
    $detail  = mysqli_real_escape_string($mysqli, $detail);
    $ip      = mysqli_real_escape_string($mysqli, $_SERVER['REMOTE_ADDR'] ?? '');
    mysqli_query($mysqli,
        "INSERT INTO activity_log (role, user_id, action, detail, ip_address, created_at)
         VALUES ('$role', $user_id, '$action', '$detail', '$ip', NOW())"
    );
}
?>
