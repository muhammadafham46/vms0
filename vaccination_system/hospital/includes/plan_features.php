<?php
/**
 * Plan Features Utility Functions
 * Provides functions to check feature access based on hospital subscription plan
 */

require_once '../config/database.php';

class PlanFeatures {
    private $db;
    private $hospital_id;
    private $current_plan_id;
    private $plan_name;
    private $plan_features;
    
    public function __construct($hospital_id) {
        $this->db = (new Database())->getConnection();
        $this->hospital_id = $hospital_id;
        $this->loadPlanDetails();
    }
    
    private function loadPlanDetails() {
        $query = "SELECT h.current_plan_id, hp.name as plan_name, hp.features as plan_features
                  FROM hospitals h
                  LEFT JOIN hospital_plans hp ON h.current_plan_id = hp.id
                  WHERE h.id = :hospital_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':hospital_id', $this->hospital_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $this->current_plan_id = $result['current_plan_id'];
        $this->plan_name = $result['plan_name'] ?: 'Basic (Trial)';
        $this->plan_features = $result['plan_features'] ?: 'Basic appointment count reports, Limited features for trial period';
    }
    
    /**
     * Check if a specific feature is available for the current plan
     */
    public function hasFeature($feature_name) {
        // Basic plan (trial) has limited features
        if (!$this->current_plan_id) {
            return $this->hasBasicFeature($feature_name);
        }
        
        // Check based on plan ID
        switch ($this->current_plan_id) {
            case 1: // Basic Plan
                return $this->hasBasicFeature($feature_name);
                
            case 2: // Standard Plan
                return $this->hasStandardFeature($feature_name);
                
            case 3: // Advanced Plan
                return $this->hasAdvancedFeature($feature_name);
                
            case 4: // Premium Plan
                return $this->hasPremiumFeature($feature_name);
                
            default:
                return $this->hasBasicFeature($feature_name);
        }
    }
    
    /**
     * Basic Plan (Trial) Features
     */
    private function hasBasicFeature($feature_name) {
        $basic_features = [
            'dashboard' => true,
            'appointments' => true,
            'vaccine_inventory' => true,
            'reports_basic' => true,
            'profile' => true,
            'working_hours' => false,
            'vaccination_schedule' => false,
            'staff_management' => false,
            'notification_settings' => false,
            'backup_records' => false,
            'analytics_dashboard' => false,
            'revenue_insights' => false,
            'custom_branding' => false,
            'promotions' => false,
            'advanced_analytics' => false
        ];
        
        return $basic_features[$feature_name] ?? false;
    }
    
    /**
     * Standard Plan Features
     */
    private function hasStandardFeature($feature_name) {
        $standard_features = [
            'dashboard' => true,
            'appointments' => true,
            'vaccine_inventory' => true,
            'reports_basic' => true,
            'profile' => true,
            'working_hours' => true,
            'vaccination_schedule' => true,
            'staff_management' => true,
            'notification_settings' => true,
            'backup_records' => true,
            'analytics_dashboard' => false,
            'revenue_insights' => false,
            'custom_branding' => false,
            'promotions' => false,
            'advanced_analytics' => false
        ];
        
        return $standard_features[$feature_name] ?? false;
    }
    
    /**
     * Advanced Plan Features
     */
    private function hasAdvancedFeature($feature_name) {
        $advanced_features = [
            'dashboard' => true,
            'appointments' => true,
            'vaccine_inventory' => true,
            'reports_basic' => true,
            'profile' => true,
            'working_hours' => true,
            'vaccination_schedule' => true,
            'staff_management' => true,
            'notification_settings' => true,
            'backup_records' => true,
            'analytics_dashboard' => true,
            'revenue_insights' => true,
            'custom_branding' => false,
            'promotions' => false,
            'advanced_analytics' => true
        ];
        
        return $advanced_features[$feature_name] ?? false;
    }
    
    /**
     * Premium Plan Features
     */
    private function hasPremiumFeature($feature_name) {
        // Premium plan has access to all features
        return true;
    }
    
    /**
     * Get current plan name
     */
    public function getPlanName() {
        return $this->plan_name;
    }
    
    /**
     * Get current plan features description
     */
    public function getPlanFeatures() {
        return $this->plan_features;
    }
    
    /**
     * Check if current plan is trial/basic
     */
    public function isTrialPlan() {
        return !$this->current_plan_id;
    }
    
    /**
     * Get upgrade URL with feature context
     */
    public function getUpgradeUrl($feature_name = '') {
        $url = 'upgrade_request.php';
        if ($feature_name) {
            $url .= '?feature=' . urlencode($feature_name);
        }
        return $url;
    }
}

/**
 * Helper function to check feature access
 */
function has_feature_access($hospital_id, $feature_name) {
    $plan_features = new PlanFeatures($hospital_id);
    return $plan_features->hasFeature($feature_name);
}

/**
 * Helper function to get plan name
 */
function get_plan_name($hospital_id) {
    $plan_features = new PlanFeatures($hospital_id);
    return $plan_features->getPlanName();
}

/**
 * Helper function to check if trial plan
 */
function is_trial_plan($hospital_id) {
    $plan_features = new PlanFeatures($hospital_id);
    return $plan_features->isTrialPlan();
}
?>
