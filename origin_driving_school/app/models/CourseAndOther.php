<?php
/**
 * Course, Branch, and Vehicle Models - Origin Driving School Management System
 * 
 * This file contains three model classes:
 * - Course: Manages driving courses and packages
 * - Branch: Manages school branch locations
 * - Vehicle: Manages the driving school fleet
 * 
 * file path: app/models/CourseAndOther.php
 * 
 * @author [sujan darji k231673, and anthony allan regalado k231715]
 * @version 1.0
 */

// =====================================================
// COURSE MODEL
// =====================================================

/**
 * Course Model Class
 * 
 * Handles all course-related database operations
 */
class Course extends Model {
    
    /**
     * Table name
     * @var string
     */
    protected $table = 'courses';
    
    /**
     * Primary key
     * @var string
     */
    protected $primaryKey = 'course_id';
    
    /**
     * Fillable columns
     * @var array
     */
    protected $fillable = [
        'course_name',
        'course_code',
        'description',
        'number_of_lessons',
        'lesson_duration',
        'price',
        'course_type',
        'is_active'
    ];
    
    /**
     * Get active courses
     * 
     * @return array
     */
    public function getActiveCourses() {
        return $this->getAll(['is_active' => 1], 'course_name ASC');
    }
    
    /**
     * Get courses by type
     * 
     * @param string $type Course type
     * @return array
     */
    public function getByType($type) {
        return $this->getAll(['course_type' => $type, 'is_active' => 1], 'price ASC');
    }
    
    /**
     * Get course by code
     * 
     * @param string $code Course code
     * @return array|null
     */
    public function getByCode($code) {
        return $this->getOne(['course_code' => $code]);
    }
    
    /**
     * Get course enrollment count
     * 
     * @param int $courseId Course ID
     * @return int
     */
    public function getEnrollmentCount($courseId) {
        $sql = "SELECT COUNT(DISTINCT student_id) as count
                FROM invoices
                WHERE course_id = ?";
        
        $result = $this->db->selectOne($sql, [$courseId]);
        return $result ? (int)$result['count'] : 0;
    }
    
    /**
     * Get popular courses
     * 
     * @param int $limit Number of courses to retrieve
     * @return array
     */
    public function getPopularCourses($limit = 5) {
        $sql = "SELECT c.*, 
                COUNT(i.invoice_id) as enrollment_count,
                SUM(i.total_amount) as total_revenue
                FROM {$this->table} c
                LEFT JOIN invoices i ON c.course_id = i.course_id
                WHERE c.is_active = 1
                GROUP BY c.course_id
                ORDER BY enrollment_count DESC
                LIMIT ?";
        
        return $this->db->select($sql, [$limit]);
    }
    
    /**
     * Get course statistics
     * 
     * @param int $courseId Course ID
     * @return array
     */
    public function getCourseStats($courseId) {
        $sql = "SELECT 
                COUNT(DISTINCT i.student_id) as total_students,
                COUNT(l.lesson_id) as total_lessons,
                SUM(CASE WHEN l.status = 'completed' THEN 1 ELSE 0 END) as completed_lessons,
                SUM(i.total_amount) as total_revenue,
                AVG(l.student_performance_rating) as avg_rating
                FROM {$this->table} c
                LEFT JOIN invoices i ON c.course_id = i.course_id
                LEFT JOIN lessons l ON i.student_id = l.student_id
                WHERE c.course_id = ?";
        
        return $this->db->selectOne($sql, [$courseId]);
    }
    
    /**
     * Toggle course active status
     * 
     * @param int $courseId Course ID
     * @return bool
     */
    public function toggleActive($courseId) {
        $course = $this->getById($courseId);
        
        if (!$course) {
            return false;
        }
        
        $newStatus = $course['is_active'] ? 0 : 1;
        return $this->update($courseId, ['is_active' => $newStatus]);
    }
    
    /**
     * Calculate total course hours
     * 
     * @param int $courseId Course ID
     * @return float
     */
    public function getTotalHours($courseId) {
        $course = $this->getById($courseId);
        
        if (!$course) {
            return 0;
        }
        
        return ($course['number_of_lessons'] * $course['lesson_duration']) / 60;
    }
    
    /**
     * Get course price with tax
     * 
     * @param int $courseId Course ID
     * @return array
     */
    public function getPriceWithTax($courseId) {
        $course = $this->getById($courseId);
        
        if (!$course) {
            return null;
        }
        
        $taxRate = TAX_RATE / 100;
        $taxAmount = $course['price'] * $taxRate;
        $totalPrice = $course['price'] + $taxAmount;
        
        return [
            'subtotal' => $course['price'],
            'tax_rate' => TAX_RATE,
            'tax_amount' => $taxAmount,
            'total' => $totalPrice
        ];
    }
    
    /**
     * Search courses
     * 
     * @param string $searchTerm Search term
     * @return array
     */
    public function search($searchTerm) {
        $params = ["%{$searchTerm}%", "%{$searchTerm}%", "%{$searchTerm}%"];
        
        $sql = "SELECT * FROM {$this->table}
                WHERE course_name LIKE ? 
                OR course_code LIKE ? 
                OR description LIKE ?
                ORDER BY course_name ASC";
        
        return $this->db->select($sql, $params);
    }
}


// =====================================================
// BRANCH MODEL
// =====================================================

/**
 * Branch Model Class
 * 
 * Handles all branch-related database operations
 */
class Branch extends Model {
    
    /**
     * Table name
     * @var string
     */
    protected $table = 'branches';
    
    /**
     * Primary key
     * @var string
     */
    protected $primaryKey = 'branch_id';
    
    /**
     * Fillable columns
     * @var array
     */
    protected $fillable = [
        'branch_name',
        'address',
        'suburb',
        'state',
        'postcode',
        'phone',
        'email',
        'is_active'
    ];
    
    /**
     * Get active branches
     * 
     * @return array
     */
    public function getActiveBranches() {
        return $this->getAll(['is_active' => 1], 'branch_name ASC');
    }
    
    /**
     * Get branch with statistics
     * 
     * @param int $branchId Branch ID
     * @return array|null
     */
    public function getBranchWithStats($branchId) {
        $sql = "SELECT b.*,
                (SELECT COUNT(*) FROM students WHERE branch_id = b.branch_id) as student_count,
                (SELECT COUNT(*) FROM instructors WHERE branch_id = b.branch_id) as instructor_count,
                (SELECT COUNT(*) FROM vehicles WHERE branch_id = b.branch_id) as vehicle_count,
                (SELECT COUNT(*) FROM staff WHERE branch_id = b.branch_id) as staff_count
                FROM {$this->table} b
                WHERE b.branch_id = ?";
        
        return $this->db->selectOne($sql, [$branchId]);
    }
    
    /**
     * Get all branches with statistics
     * 
     * @return array
     */
    public function getAllWithStats() {
        $sql = "SELECT b.*,
                (SELECT COUNT(*) FROM students WHERE branch_id = b.branch_id) as student_count,
                (SELECT COUNT(*) FROM instructors WHERE branch_id = b.branch_id) as instructor_count,
                (SELECT COUNT(*) FROM vehicles WHERE branch_id = b.branch_id) as vehicle_count,
                (SELECT COUNT(*) FROM staff WHERE branch_id = b.branch_id) as staff_count
                FROM {$this->table} b
                ORDER BY b.branch_name ASC";
        
        return $this->db->select($sql);
    }
    
    /**
     * Get branch students
     * 
     * @param int $branchId Branch ID
     * @return array
     */
    public function getStudents($branchId) {
        $sql = "SELECT s.*, 
                u.first_name, 
                u.last_name, 
                u.email, 
                u.phone
                FROM students s
                INNER JOIN users u ON s.user_id = u.user_id
                WHERE s.branch_id = ?
                ORDER BY u.first_name ASC";
        
        return $this->db->select($sql, [$branchId]);
    }
    
    /**
     * Get branch instructors
     * 
     * @param int $branchId Branch ID
     * @return array
     */
    public function getInstructors($branchId) {
        $sql = "SELECT i.*, 
                u.first_name, 
                u.last_name, 
                u.email, 
                u.phone
                FROM instructors i
                INNER JOIN users u ON i.user_id = u.user_id
                WHERE i.branch_id = ?
                ORDER BY u.first_name ASC";
        
        return $this->db->select($sql, [$branchId]);
    }
    
    /**
     * Get branch vehicles
     * 
     * @param int $branchId Branch ID
     * @return array
     */
    public function getVehicles($branchId) {
        $sql = "SELECT * FROM vehicles
                WHERE branch_id = ?
                ORDER BY make ASC, model ASC";
        
        return $this->db->select($sql, [$branchId]);
    }
    
    /**
     * Get branch revenue
     * 
     * @param int $branchId Branch ID
     * @param string $startDate Start date (Y-m-d)
     * @param string $endDate End date (Y-m-d)
     * @return float
     */
    public function getRevenue($branchId, $startDate = null, $endDate = null) {
        $sql = "SELECT SUM(i.total_amount) as total_revenue
                FROM invoices i
                INNER JOIN students s ON i.student_id = s.student_id
                WHERE s.branch_id = ?";
        
        $params = [$branchId];
        
        if ($startDate) {
            $sql .= " AND i.issue_date >= ?";
            $params[] = $startDate;
        }
        
        if ($endDate) {
            $sql .= " AND i.issue_date <= ?";
            $params[] = $endDate;
        }
        
        $result = $this->db->selectOne($sql, $params);
        return $result ? (float)$result['total_revenue'] : 0.00;
    }
    
    /**
     * Toggle branch active status
     * 
     * @param int $branchId Branch ID
     * @return bool
     */
    public function toggleActive($branchId) {
        $branch = $this->getById($branchId);
        
        if (!$branch) {
            return false;
        }
        
        $newStatus = $branch['is_active'] ? 0 : 1;
        return $this->update($branchId, ['is_active' => $newStatus]);
    }
    
    /**
     * Get branch performance comparison
     * 
     * @return array
     */
    public function getPerformanceComparison() {
        $sql = "SELECT b.branch_name,
                COUNT(DISTINCT s.student_id) as student_count,
                COUNT(DISTINCT i.instructor_id) as instructor_count,
                COUNT(DISTINCT l.lesson_id) as total_lessons,
                SUM(CASE WHEN l.status = 'completed' THEN 1 ELSE 0 END) as completed_lessons,
                SUM(inv.total_amount) as total_revenue
                FROM {$this->table} b
                LEFT JOIN students s ON b.branch_id = s.branch_id
                LEFT JOIN instructors i ON b.branch_id = i.branch_id
                LEFT JOIN lessons l ON i.instructor_id = l.instructor_id
                LEFT JOIN invoices inv ON s.student_id = inv.student_id
                WHERE b.is_active = 1
                GROUP BY b.branch_id, b.branch_name
                ORDER BY total_revenue DESC";
        
        return $this->db->select($sql);
    }
    
    /**
     * Search branches
     * 
     * @param string $searchTerm Search term
     * @return array
     */
    public function search($searchTerm) {
        $params = ["%{$searchTerm}%", "%{$searchTerm}%", "%{$searchTerm}%"];
        
        $sql = "SELECT * FROM {$this->table}
                WHERE branch_name LIKE ? 
                OR suburb LIKE ? 
                OR address LIKE ?
                ORDER BY branch_name ASC";
        
        return $this->db->select($sql, $params);
    }
}


// =====================================================
// VEHICLE MODEL
// =====================================================

/**
 * Vehicle Model Class
 * 
 * Handles all vehicle/fleet-related database operations
 */
class Vehicle extends Model {
    
    /**
     * Table name
     * @var string
     */
    protected $table = 'vehicles';
    
    /**
     * Primary key
     * @var string
     */
    protected $primaryKey = 'vehicle_id';
    
    /**
     * Fillable columns
     * @var array
     */
    protected $fillable = [
        'branch_id',
        'registration_number',
        'make',
        'model',
        'year',
        'transmission',
        'color',
        'registration_expiry',
        'last_service_date',
        'next_service_due',
        'insurance_expiry',
        'is_available',
        'notes'
    ];
    
    /**
     * Get available vehicles
     * 
     * @return array
     */
    public function getAvailableVehicles() {
        $sql = "SELECT v.*, b.branch_name
                FROM {$this->table} v
                INNER JOIN branches b ON v.branch_id = b.branch_id
                WHERE v.is_available = 1
                ORDER BY v.make ASC, v.model ASC";
        
        return $this->db->select($sql);
    }
    
    /**
     * Get vehicles by branch
     * 
     * @param int $branchId Branch ID
     * @return array
     */
    public function getByBranch($branchId) {
        return $this->getAll(['branch_id' => $branchId], 'make ASC, model ASC');
    }
    
    /**
     * Get vehicles by transmission type
     * 
     * @param string $transmission Transmission type (manual/automatic)
     * @return array
     */
    public function getByTransmission($transmission) {
        $sql = "SELECT v.*, b.branch_name
                FROM {$this->table} v
                INNER JOIN branches b ON v.branch_id = b.branch_id
                WHERE v.transmission = ?
                AND v.is_available = 1
                ORDER BY v.make ASC, v.model ASC";
        
        return $this->db->select($sql, [$transmission]);
    }
    
    /**
     * Get vehicle with full details
     * 
     * @param int $vehicleId Vehicle ID
     * @return array|null
     */
    public function getVehicleWithDetails($vehicleId) {
        $sql = "SELECT v.*, 
                b.branch_name,
                b.address AS branch_address,
                b.phone AS branch_phone
                FROM {$this->table} v
                INNER JOIN branches b ON v.branch_id = b.branch_id
                WHERE v.vehicle_id = ?";
        
        return $this->db->selectOne($sql, [$vehicleId]);
    }
    
    /**
     * Get all vehicles with details
     * 
     * @return array
     */
    public function getAllWithDetails() {
        $sql = "SELECT v.*, b.branch_name
                FROM {$this->table} v
                INNER JOIN branches b ON v.branch_id = b.branch_id
                ORDER BY b.branch_name ASC, v.make ASC, v.model ASC";
        
        return $this->db->select($sql);
    }
    
    /**
     * Check if vehicle is available for time slot
     * 
     * @param int $vehicleId Vehicle ID
     * @param string $date Date (Y-m-d)
     * @param string $startTime Start time (H:i:s)
     * @param string $endTime End time (H:i:s)
     * @param int $excludeLessonId Optional lesson ID to exclude
     * @return bool
     */
    public function isAvailableForSlot($vehicleId, $date, $startTime, $endTime, $excludeLessonId = null) {
        $sql = "SELECT COUNT(*) as count
                FROM lessons
                WHERE vehicle_id = ?
                AND lesson_date = ?
                AND status = 'scheduled'
                AND (
                    (start_time <= ? AND end_time > ?) OR
                    (start_time < ? AND end_time >= ?) OR
                    (start_time >= ? AND end_time <= ?)
                )";
        
        $params = [$vehicleId, $date, $startTime, $startTime, $endTime, $endTime, $startTime, $endTime];
        
        if ($excludeLessonId) {
            $sql .= " AND lesson_id != ?";
            $params[] = $excludeLessonId;
        }
        
        $result = $this->db->selectOne($sql, $params);
        return $result && $result['count'] == 0;
    }
    
    /**
     * Get vehicle schedule for a date
     * 
     * @param int $vehicleId Vehicle ID
     * @param string $date Date (Y-m-d)
     * @return array
     */
    public function getSchedule($vehicleId, $date) {
        $sql = "SELECT l.*,
                CONCAT(su.first_name, ' ', su.last_name) AS student_name,
                CONCAT(iu.first_name, ' ', iu.last_name) AS instructor_name
                FROM lessons l
                INNER JOIN students s ON l.student_id = s.student_id
                INNER JOIN users su ON s.user_id = su.user_id
                INNER JOIN instructors i ON l.instructor_id = i.instructor_id
                INNER JOIN users iu ON i.user_id = iu.user_id
                WHERE l.vehicle_id = ?
                AND l.lesson_date = ?
                ORDER BY l.start_time ASC";
        
        return $this->db->select($sql, [$vehicleId, $date]);
    }
    
    /**
     * Get vehicles needing service
     * 
     * @param int $days Number of days to check ahead
     * @return array
     */
    public function getNeedingService($days = 30) {
        $checkDate = date('Y-m-d', strtotime("+{$days} days"));
        
        $sql = "SELECT v.*, b.branch_name
                FROM {$this->table} v
                INNER JOIN branches b ON v.branch_id = b.branch_id
                WHERE v.next_service_due <= ?
                ORDER BY v.next_service_due ASC";
        
        return $this->db->select($sql, [$checkDate]);
    }
    
    /**
     * Get vehicles with expiring documents
     * 
     * @param int $days Number of days to check ahead
     * @return array
     */
    public function getExpiringDocuments($days = 30) {
        $checkDate = date('Y-m-d', strtotime("+{$days} days"));
        
        $sql = "SELECT v.*, 
                b.branch_name,
                CASE 
                    WHEN v.registration_expiry <= ? THEN 'registration'
                    WHEN v.insurance_expiry <= ? THEN 'insurance'
                    ELSE 'other'
                END as expiry_type
                FROM {$this->table} v
                INNER JOIN branches b ON v.branch_id = b.branch_id
                WHERE v.registration_expiry <= ? 
                OR v.insurance_expiry <= ?
                ORDER BY v.registration_expiry ASC, v.insurance_expiry ASC";
        
        return $this->db->select($sql, [$checkDate, $checkDate, $checkDate, $checkDate]);
    }
    
    /**
     * Get vehicle usage statistics
     * 
     * @param int $vehicleId Vehicle ID
     * @return array
     */
    public function getUsageStats($vehicleId) {
        $sql = "SELECT 
                COUNT(*) as total_lessons,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_lessons,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_lessons,
                MIN(lesson_date) as first_used,
                MAX(lesson_date) as last_used
                FROM lessons
                WHERE vehicle_id = ?";
        
        return $this->db->selectOne($sql, [$vehicleId]);
    }
    
    /**
     * Toggle vehicle availability
     * 
     * @param int $vehicleId Vehicle ID
     * @return bool
     */
    public function toggleAvailability($vehicleId) {
        $vehicle = $this->getById($vehicleId);
        
        if (!$vehicle) {
            return false;
        }
        
        $newStatus = $vehicle['is_available'] ? 0 : 1;
        return $this->update($vehicleId, ['is_available' => $newStatus]);
    }
    
    /**
     * Record service
     * 
     * @param int $vehicleId Vehicle ID
     * @param string $serviceDate Service date
     * @param int $nextServiceDays Days until next service
     * @return bool
     */
    public function recordService($vehicleId, $serviceDate, $nextServiceDays = 180) {
        $nextServiceDue = date('Y-m-d', strtotime($serviceDate . " +{$nextServiceDays} days"));
        
        return $this->update($vehicleId, [
            'last_service_date' => $serviceDate,
            'next_service_due' => $nextServiceDue
        ]);
    }
    
    /**
     * Get vehicle statistics
     * 
     * @return array
     */
    public function getStatistics() {
        $sql = "SELECT 
                COUNT(*) as total_vehicles,
                SUM(CASE WHEN is_available = 1 THEN 1 ELSE 0 END) as available_vehicles,
                SUM(CASE WHEN transmission = 'manual' THEN 1 ELSE 0 END) as manual_vehicles,
                SUM(CASE WHEN transmission = 'automatic' THEN 1 ELSE 0 END) as automatic_vehicles,
                AVG(YEAR(CURDATE()) - year) as avg_age
                FROM {$this->table}";
        
        return $this->db->selectOne($sql);
    }
    
    /**
     * Get fleet overview
     * 
     * @return array
     */
    public function getFleetOverview() {
        $sql = "SELECT v.*,
                b.branch_name,
                (SELECT COUNT(*) FROM lessons WHERE vehicle_id = v.vehicle_id) as total_lessons,
                DATEDIFF(v.registration_expiry, CURDATE()) as reg_days_remaining,
                DATEDIFF(v.insurance_expiry, CURDATE()) as insurance_days_remaining,
                DATEDIFF(v.next_service_due, CURDATE()) as service_days_remaining
                FROM {$this->table} v
                INNER JOIN branches b ON v.branch_id = b.branch_id
                ORDER BY b.branch_name ASC, v.make ASC, v.model ASC";
        
        return $this->db->select($sql);
    }
    
    /**
     * Search vehicles
     * 
     * @param string $searchTerm Search term
     * @return array
     */
    public function search($searchTerm) {
        $params = ["%{$searchTerm}%", "%{$searchTerm}%", "%{$searchTerm}%", "%{$searchTerm}%"];
        
        $sql = "SELECT v.*, b.branch_name
                FROM {$this->table} v
                INNER JOIN branches b ON v.branch_id = b.branch_id
                WHERE v.registration_number LIKE ? 
                OR v.make LIKE ? 
                OR v.model LIKE ?
                OR b.branch_name LIKE ?
                ORDER BY v.make ASC, v.model ASC";
        
        return $this->db->select($sql, $params);
    }
}
?>