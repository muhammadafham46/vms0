<?php
// Feature Access Control for Hospital Panel
// This file contains functions to check feature access based on hospital subscription plan

/**
 * Check if a specific feature is accessible for the current hospital
 * @param string $feature_name The name of the feature to check
 * @param array $hospital The hospital data array
 * @return bool True if feature is accessible, false otherwise
 */
function check_feature_access($feature_name, $hospital) {
    // Default features available to all hospitals
    $basic_features = [
        'dashboard',
        'appointments',
        'vaccine_inventory_basic',
        'reports_basic',
        'profile',
        'settings_basic'
    ];
    
    // Features available only with premium plans
    $premium_features = [
        'staff_management',
        'backup_records',
        'upgrade_request',
        'reports_advanced',
        'vaccine_inventory_advanced',
        'notification_settings',
        'working_hours',
        'vaccination_schedule'
    ];
    
    // Check if feature is in basic features
    if (in_array($feature_name, $basic_features)) {
        return true;
    }
    
    // Check if feature is in premium features and hospital has premium plan
    if (in_array($feature_name, $premium_features)) {
        // Check if hospital has a premium plan (plan_id > 1 indicates premium)
        if (isset($hospital['current_plan_id']) && $hospital['current_plan_id'] > 1) {
            return true;
        }
        
        // Check trial period
        if (isset($hospital['trial_end_date']) && $hospital['trial_end_date'] > date('Y-m-d')) {
            return true;
        }
    }
    
    return false;
}

/**
 * Get feature access message for restricted features
 * @param string $feature_name The name of the feature
 * @return string Message explaining feature access requirements
 */
function get_feature_access_message($feature_name) {
    $messages = [
        'staff_management' => 'Staff Management feature requires a premium subscription plan.',
        'backup_records' => 'Backup Records feature requires a premium subscription plan.',
        'upgrade_request' => 'Upgrade Request feature is available for premium plans.',
        'reports_advanced' => 'Advanced Reports require a premium subscription plan.',
        'vaccine_inventory_advanced' => 'Advanced Vaccine Inventory features require premium access.',
        'notification_settings' => 'Notification Settings require a premium subscription.',
        'working_hours' => 'Working Hours management requires premium access.',
        'vaccination_schedule' => 'Vaccination Schedule management requires premium plan.'
    ];
    
    return $messages[$feature_name] ?? 'This feature requires a premium subscription plan.';
}

// /**
//  * Redirect to upgrade page if feature is not accessible
//  * @param string $feature_name The feature to check
//  * @param array $hospital The hospital data
//  */
// function redirect_if_feature_not_accessible($feature_name, $hospital) {
//     if (!check_feature_access($feature_name, $hospital)) {
//         $_SESSION['error'] = get_feature_access_message($feature_name);
//         header('Location: upgrade_request.php');
//         exit();
//     }
// }

/**
 * Check if hospital is in trial period
 * @param array $hospital The hospital data
 * @return bool True if in trial period, false otherwise
 */
function is_in_trial_period($hospital) {
    if (isset($hospital['trial_end_date']) && $hospital['trial_end_date'] > date('Y-m-d')) {
        return true;
    }
    return false;
}

/**
 * Get days remaining in trial period
 * @param array $hospital The hospital data
 * @return int Days remaining in trial, 0 if trial expired or not applicable
 */
function get_trial_days_remaining($hospital) {
    if (isset($hospital['trial_end_date']) && $hospital['trial_end_date'] > date('Y-m-d')) {
        $trial_end = new DateTime($hospital['trial_end_date']);
        $today = new DateTime();
        $interval = $today->diff($trial_end);
        return $interval->days;
    }
    return 0;
}
?>
