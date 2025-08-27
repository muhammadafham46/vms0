<?php
require_once 'config/database.php';

class SubscriptionExpirationChecker {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function checkExpiredSubscriptions() {
        try {
            // Get all active subscriptions that have expired
            $query = "SELECT hs.*, h.name as hospital_name, hp.name as plan_name
                      FROM hospital_subscriptions hs
                      JOIN hospitals h ON hs.hospital_id = h.id
                      JOIN hospital_plans hp ON hs.plan_id = hp.id
                      WHERE hs.status = 'active' 
                      AND hs.end_date < CURDATE()";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $expired_subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($expired_subscriptions) > 0) {
                error_log("Found " . count($expired_subscriptions) . " expired subscriptions to process");
                
                foreach ($expired_subscriptions as $subscription) {
                    $this->downgradeToBasicPlan($subscription);
                }
                
                return count($expired_subscriptions) . " subscriptions processed";
            } else {
                error_log("No expired subscriptions found");
                return "No expired subscriptions found";
            }
            
        } catch (Exception $e) {
            error_log("Error checking expired subscriptions: " . $e->getMessage());
            return "Error: " . $e->getMessage();
        }
    }
    
    private function downgradeToBasicPlan($subscription) {
        try {
            $this->db->beginTransaction();
            
            // Get the basic plan ID
            $query = "SELECT id FROM hospital_plans WHERE name LIKE '%Basic%' LIMIT 1";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $basic_plan = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$basic_plan) {
                throw new Exception("Basic plan not found");
            }
            
            $basic_plan_id = $basic_plan['id'];
            
            // Update the subscription status to expired
            $query = "UPDATE hospital_subscriptions 
                      SET status = 'expired', updated_at = NOW() 
                      WHERE id = :subscription_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':subscription_id', $subscription['id']);
            $stmt->execute();
            
            // Update hospital to basic plan
            $query = "UPDATE hospitals 
                      SET current_plan_id = :plan_id, 
                          subscription_status = 'expired',
                          max_staff_accounts = 1,
                          max_branches = 1,
                          monthly_sms_limit = 0,
                          updated_at = NOW()
                      WHERE id = :hospital_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':plan_id', $basic_plan_id);
            $stmt->bindParam(':hospital_id', $subscription['hospital_id']);
            $stmt->execute();
            
            // Log the downgrade
            $this->logDowngrade($subscription, $basic_plan_id);
            
            $this->db->commit();
            
            error_log("Successfully downgraded hospital '{$subscription['hospital_name']}' from '{$subscription['plan_name']}' to Basic Plan");
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error downgrading hospital {$subscription['hospital_id']}: " . $e->getMessage());
            throw $e;
        }
    }
    
    private function logDowngrade($subscription, $basic_plan_id) {
        $query = "INSERT INTO subscription_downgrade_logs 
                  (hospital_id, from_plan_id, to_plan_id, downgrade_date, reason)
                  VALUES (:hospital_id, :from_plan_id, :to_plan_id, NOW(), 'Subscription expired')";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':hospital_id', $subscription['hospital_id']);
        $stmt->bindParam(':from_plan_id', $subscription['plan_id']);
        $stmt->bindParam(':to_plan_id', $basic_plan_id);
        $stmt->execute();
    }
    
    public function createDowngradeLogsTable() {
        $query = "CREATE TABLE IF NOT EXISTS subscription_downgrade_logs (
            id INT PRIMARY KEY AUTO_INCREMENT,
            hospital_id INT NOT NULL,
            from_plan_id INT NOT NULL,
            to_plan_id INT NOT NULL,
            downgrade_date DATETIME NOT NULL,
            reason TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (hospital_id) REFERENCES hospitals(id) ON DELETE CASCADE,
            FOREIGN KEY (from_plan_id) REFERENCES hospital_plans(id) ON DELETE CASCADE,
            FOREIGN KEY (to_plan_id) REFERENCES hospital_plans(id) ON DELETE CASCADE
        )";
        
        $stmt = $this->db->prepare($query);
        return $stmt->execute();
    }
}

// Run the expiration checker
if (php_sapi_name() === 'cli') {
    $checker = new SubscriptionExpirationChecker();
    
    // Create the downgrade logs table if it doesn't exist
    $checker->createDowngradeLogsTable();
    
    $result = $checker->checkExpiredSubscriptions();
    echo $result . "\n";
} else {
    echo "This script should be run from the command line.\n";
}
?>
