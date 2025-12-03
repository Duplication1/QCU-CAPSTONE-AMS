<?php
/**
 * Asset History Helper
 * Use this to automatically log asset history across the system
 */

require_once __DIR__ . '/../model/AssetHistory.php';

class AssetHistoryHelper {
    private static $instance = null;
    private $assetHistory;
    
    private function __construct() {
        $this->assetHistory = new AssetHistory();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new AssetHistoryHelper();
        }
        return self::$instance;
    }
    
    /**
     * Log asset creation
     */
    public function logAssetCreated($asset_id, $asset_tag, $asset_name, $created_by = null) {
        $description = "Asset created: $asset_tag - $asset_name";
        return $this->assetHistory->logHistory(
            $asset_id,
            'Created',
            null,
            null,
            null,
            $description,
            $created_by
        );
    }
    
    /**
     * Log asset status change
     */
    public function logStatusChange($asset_id, $old_status, $new_status, $performed_by = null) {
        $description = "Status changed from $old_status to $new_status";
        return $this->assetHistory->logHistory(
            $asset_id,
            'Status Changed',
            'status',
            $old_status,
            $new_status,
            $description,
            $performed_by
        );
    }
    
    /**
     * Log asset condition change
     */
    public function logConditionChange($asset_id, $old_condition, $new_condition, $performed_by = null) {
        $description = "Condition changed from $old_condition to $new_condition";
        return $this->assetHistory->logHistory(
            $asset_id,
            'Condition Changed',
            'condition',
            $old_condition,
            $new_condition,
            $description,
            $performed_by
        );
    }
    
    /**
     * Log asset location change
     */
    public function logLocationChange($asset_id, $old_location, $new_location, $performed_by = null) {
        $description = "Location changed from $old_location to $new_location";
        return $this->assetHistory->logHistory(
            $asset_id,
            'Location Changed',
            'location',
            $old_location,
            $new_location,
            $description,
            $performed_by
        );
    }
    
    /**
     * Log asset assignment
     */
    public function logAssignment($asset_id, $assigned_to_name, $performed_by = null) {
        $description = "Asset assigned to $assigned_to_name";
        return $this->assetHistory->logHistory(
            $asset_id,
            'Assigned',
            'assigned_to',
            null,
            $assigned_to_name,
            $description,
            $performed_by
        );
    }
    
    /**
     * Log asset unassignment
     */
    public function logUnassignment($asset_id, $previous_assignee, $performed_by = null) {
        $description = "Asset unassigned from $previous_assignee";
        return $this->assetHistory->logHistory(
            $asset_id,
            'Unassigned',
            'assigned_to',
            $previous_assignee,
            null,
            $description,
            $performed_by
        );
    }
    
    /**
     * Log asset borrowing
     */
    public function logBorrowing($asset_id, $borrower_name, $performed_by = null) {
        $description = "Asset borrowed by $borrower_name";
        return $this->assetHistory->logHistory(
            $asset_id,
            'Borrowed',
            null,
            null,
            $borrower_name,
            $description,
            $performed_by
        );
    }
    
    /**
     * Log asset return
     */
    public function logReturn($asset_id, $borrower_name, $performed_by = null) {
        $description = "Asset returned by $borrower_name";
        return $this->assetHistory->logHistory(
            $asset_id,
            'Returned',
            null,
            $borrower_name,
            'Available',
            $description,
            $performed_by
        );
    }
    
    /**
     * Log asset maintenance
     */
    public function logMaintenance($asset_id, $maintenance_details, $performed_by = null) {
        $description = "Maintenance performed: $maintenance_details";
        return $this->assetHistory->logHistory(
            $asset_id,
            'Maintenance',
            null,
            null,
            null,
            $description,
            $performed_by
        );
    }
    
    /**
     * Log asset disposal
     */
    public function logDisposal($asset_id, $reason, $performed_by = null) {
        $description = "Asset disposed: $reason";
        return $this->assetHistory->logHistory(
            $asset_id,
            'Disposed',
            'status',
            'Available',
            'Disposed',
            $description,
            $performed_by
        );
    }
    
    /**
     * Log asset restoration
     */
    public function logRestoration($asset_id, $performed_by = null) {
        $description = "Asset restored from disposal/archive";
        return $this->assetHistory->logHistory(
            $asset_id,
            'Restored',
            'status',
            'Disposed',
            'Available',
            $description,
            $performed_by
        );
    }
    
    /**
     * Log asset archiving
     */
    public function logArchiving($asset_id, $performed_by = null) {
        $description = "Asset archived";
        return $this->assetHistory->logHistory(
            $asset_id,
            'Archived',
            'status',
            'Available',
            'Archive',
            $description,
            $performed_by
        );
    }
    
    /**
     * Log QR code generation
     */
    public function logQRGeneration($asset_id, $performed_by = null) {
        $description = "QR code generated for asset";
        return $this->assetHistory->logHistory(
            $asset_id,
            'QR Generated',
            null,
            null,
            null,
            $description,
            $performed_by
        );
    }
    
    /**
     * Log general asset update
     */
    public function logUpdate($asset_id, $field_changed, $old_value, $new_value, $performed_by = null) {
        $description = "Asset updated: $field_changed changed";
        return $this->assetHistory->logHistory(
            $asset_id,
            'Updated',
            $field_changed,
            $old_value,
            $new_value,
            $description,
            $performed_by
        );
    }
}
