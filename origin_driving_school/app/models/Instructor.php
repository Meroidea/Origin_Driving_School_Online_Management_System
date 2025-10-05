<?php
/**
 * Instructor Model Class
 * 
 * Handles all instructor-related database operations
 * 
 * file path: app/models/Instructor.php
 * 
 * @author [SUJAN DARJI K231673 AND ANTHONY ALLAN REGALADO K231715]
 * @version 2.0
 */

class Instructor extends Model {
    
    protected $table = 'instructors';
    protected $primaryKey = 'instructor_id';
    
    protected $fillable = [
        'user_id',
        'branch_id',
        'certificate_number',
        'adta_membership',
        'wwc_card_number',
        'license_expiry',
        'certification_expiry',
        'police_check_date',
        'medical_check_date',
        'date_joined',
        'hourly_rate',
        'specialization',
        'bio',
        'is_available'
    ];
    
    /**
     * Get instructor by user ID
     */
    public function getByUserId($userId) {
        $sql = "SELECT i.*, 
                u.first_name, u.last_name, u.email, u.phone, u.user_role,
                b.branch_name, b.address as branch_address
                FROM {$this->table} i
                INNER JOIN users u ON i.user_id = u.user_id
                LEFT JOIN branches b ON i.branch_id = b.branch_id
                WHERE i.user_id = ?";
        
        return $this->db->selectOne($sql, [$userId]);
    }
    
    /**
     * Get instructor with user details
     */
    public function getInstructorWithDetails($instructorId) {
        $sql = "SELECT i.*, 
                CONCAT(u.first_name, ' ', u.last_name) AS full_name,
                u.first_name, u.last_name, u.email, u.phone, u.user_role, u.is_active,
                b.branch_name, b.address as branch_address, b.phone as branch_phone
                FROM {$this->table} i
                INNER JOIN users u ON i.user_id = u.user_id
                LEFT JOIN branches b ON i.branch_id = b.branch_id
                WHERE i.{$this->primaryKey} = ?";
        
        return $this->db->selectOne($sql, [$instructorId]);
    }
    
    /**
     * Get all instructors with full details
     */
    public function getAllWithDetails() {
        $sql = "SELECT i.*, 
                CONCAT(u.first_name, ' ', u.last_name) AS full_name,
                u.first_name,
                u.last_name,
                u.email,
                u.phone,
                u.user_role,
                u.is_active,
                b.branch_name,
                b.address as branch_address,
                (SELECT COUNT(*) FROM lessons WHERE instructor_id = i.instructor_id AND status = 'completed') as completed_lessons,
                (SELECT COUNT(*) FROM lessons WHERE instructor_id = i.instructor_id AND status = 'scheduled') as scheduled_lessons,
                (SELECT COUNT(DISTINCT student_id) FROM students WHERE assigned_instructor_id = i.instructor_id) as assigned_students,
                (SELECT AVG(student_performance_rating) FROM lessons WHERE instructor_id = i.instructor_id AND student_performance_rating IS NOT NULL) as avg_rating
                FROM {$this->table} i
                INNER JOIN users u ON i.user_id = u.user_id
                LEFT JOIN branches b ON i.branch_id = b.branch_id
                WHERE u.is_active = 1
                ORDER BY u.first_name ASC, u.last_name ASC";
        
        return $this->db->select($sql);
    }
    
    /**
     * Get all instructors with user information
     */
    public function getAllWithUserInfo() {
        $sql = "SELECT i.*, 
                CONCAT(u.first_name, ' ', u.last_name) AS full_name,
                u.first_name,
                u.last_name,
                u.email,
                u.phone,
                u.is_active,
                b.branch_name,
                b.suburb,
                (SELECT COUNT(DISTINCT student_id) FROM students WHERE assigned_instructor_id = i.instructor_id) as assigned_students,
                (SELECT COUNT(*) FROM lessons WHERE instructor_id = i.instructor_id AND status = 'scheduled' AND lesson_date >= CURDATE()) as upcoming_lessons
                FROM {$this->table} i
                INNER JOIN users u ON i.user_id = u.user_id
                LEFT JOIN branches b ON i.branch_id = b.branch_id
                WHERE i.is_available = 1 AND u.is_active = 1
                ORDER BY u.first_name ASC, u.last_name ASC";
        
        return $this->db->select($sql);
    }
    
    /**
     * Get all active instructors
     */
    public function getAllActive() {
        $sql = "SELECT i.*, 
                CONCAT(u.first_name, ' ', u.last_name) AS full_name,
                u.first_name, u.last_name, u.email, u.phone,
                b.branch_name
                FROM {$this->table} i
                INNER JOIN users u ON i.user_id = u.user_id
                LEFT JOIN branches b ON i.branch_id = b.branch_id
                WHERE i.is_available = 1 AND u.is_active = 1
                ORDER BY u.first_name ASC";
        
        return $this->db->select($sql);
    }
    
    /**
     * Get all instructors
     */
    public function getAllInstructors() {
        $sql = "SELECT i.*, 
                CONCAT(u.first_name, ' ', u.last_name) AS full_name,
                u.first_name, u.last_name, u.email, u.phone,
                b.branch_name
                FROM {$this->table} i
                INNER JOIN users u ON i.user_id = u.user_id
                LEFT JOIN branches b ON i.branch_id = b.branch_id
                ORDER BY u.first_name ASC";
        
        return $this->db->select($sql);
    }
    
    /**
     * Get instructors by branch
     */
    public function getByBranch($branchId) {
        $sql = "SELECT i.*, 
                CONCAT(u.first_name, ' ', u.last_name) AS full_name,
                u.first_name, u.last_name, u.email, u.phone
                FROM {$this->table} i
                INNER JOIN users u ON i.user_id = u.user_id
                WHERE i.branch_id = ? AND i.is_available = 1
                ORDER BY u.first_name ASC";
        
        return $this->db->select($sql, [$branchId]);
    }
    
    /**
     * Search instructors
     */
    public function search($searchTerm) {
        $params = ["%{$searchTerm}%", "%{$searchTerm}%", "%{$searchTerm}%", "%{$searchTerm}%"];
        
        $sql = "SELECT i.*, 
                CONCAT(u.first_name, ' ', u.last_name) AS full_name,
                u.first_name, u.last_name, u.email, u.phone,
                b.branch_name
                FROM {$this->table} i
                INNER JOIN users u ON i.user_id = u.user_id
                LEFT JOIN branches b ON i.branch_id = b.branch_id
                WHERE u.first_name LIKE ? 
                OR u.last_name LIKE ? 
                OR u.email LIKE ?
                OR i.certificate_number LIKE ?
                ORDER BY u.first_name ASC";
        
        return $this->db->select($sql, $params);
    }
    
    /**
     * Get instructor statistics
     */
    public function getInstructorStats($instructorId) {
        $sql = "SELECT 
                COUNT(DISTINCT l.lesson_id) as total_lessons,
                COUNT(DISTINCT CASE WHEN l.status = 'completed' THEN l.lesson_id END) as completed_lessons,
                COUNT(DISTINCT CASE WHEN l.status = 'scheduled' THEN l.lesson_id END) as scheduled_lessons,
                COUNT(DISTINCT l.student_id) as total_students,
                AVG(l.student_performance_rating) as avg_rating
                FROM instructors i
                LEFT JOIN lessons l ON i.instructor_id = l.instructor_id
                WHERE i.instructor_id = ?
                GROUP BY i.instructor_id";
        
        return $this->db->selectOne($sql, [$instructorId]);
    }
    
    /**
     * Get available instructors for a time slot
     */
    public function getAvailableInstructors($date, $startTime, $endTime) {
        $sql = "SELECT i.*, 
                CONCAT(u.first_name, ' ', u.last_name) AS full_name,
                u.first_name, u.last_name, u.email, u.phone
                FROM {$this->table} i
                INNER JOIN users u ON i.user_id = u.user_id
                WHERE i.is_available = 1
                AND i.instructor_id NOT IN (
                    SELECT instructor_id 
                    FROM lessons 
                    WHERE lesson_date = ?
                    AND status IN ('scheduled', 'in_progress')
                    AND (
                        (start_time < ? AND end_time > ?)
                        OR (start_time < ? AND end_time > ?)
                        OR (start_time >= ? AND end_time <= ?)
                    )
                )
                ORDER BY u.first_name ASC";
        
        $params = [$date, $endTime, $startTime, $endTime, $startTime, $startTime, $endTime];
        return $this->db->select($sql, $params);
    }
    
    /**
     * Update instructor availability
     */
    public function updateAvailability($instructorId, $isAvailable) {
        return $this->update($instructorId, ['is_available' => $isAvailable]);
    }
    
    /**
     * Check if license is expired
     */
    public function isLicenseExpired($instructorId) {
        $instructor = $this->find($instructorId);
        if (!$instructor || !$instructor['license_expiry']) {
            return false;
        }
        
        return strtotime($instructor['license_expiry']) < time();
    }
    
    /**
     * Get instructors with expiring licenses
     */
    public function getExpiringLicenses($days = 30) {
        $sql = "SELECT i.*, 
                CONCAT(u.first_name, ' ', u.last_name) AS full_name,
                u.first_name, u.last_name, u.email, u.phone,
                DATEDIFF(i.license_expiry, CURDATE()) as days_until_expiry
                FROM {$this->table} i
                INNER JOIN users u ON i.user_id = u.user_id
                WHERE i.license_expiry IS NOT NULL
                AND i.license_expiry BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
                AND i.is_available = 1
                ORDER BY i.license_expiry ASC";
        
        return $this->db->select($sql, [$days]);
    }
    
    /**
     * Get top performing instructors
     */
    public function getTopPerformers($limit = 5) {
        $sql = "SELECT i.*, 
                CONCAT(u.first_name, ' ', u.last_name) AS full_name,
                u.first_name, u.last_name,
                COUNT(l.lesson_id) as total_lessons,
                AVG(l.student_performance_rating) as avg_rating
                FROM {$this->table} i
                INNER JOIN users u ON i.user_id = u.user_id
                LEFT JOIN lessons l ON i.instructor_id = l.instructor_id AND l.status = 'completed'
                WHERE i.is_available = 1
                GROUP BY i.instructor_id
                HAVING avg_rating IS NOT NULL
                ORDER BY avg_rating DESC, total_lessons DESC
                LIMIT ?";
        
        return $this->db->select($sql, [$limit]);
    }
    
    /**
     * Get instructor count
     */
    public function getInstructorCount() {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE is_available = 1";
        $result = $this->db->selectOne($sql);
        return $result ? (int)$result['count'] : 0;
    }
    
    /**
     * Get total instructor count (all statuses)
     */
    public function getTotalInstructorCount() {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";
        $result = $this->db->selectOne($sql);
        return $result ? (int)$result['count'] : 0;
    }
    
    /**
     * Get instructors by availability
     */
    public function getByAvailability($isAvailable) {
        $sql = "SELECT i.*, 
                CONCAT(u.first_name, ' ', u.last_name) AS full_name,
                u.first_name, u.last_name, u.email, u.phone,
                b.branch_name
                FROM {$this->table} i
                INNER JOIN users u ON i.user_id = u.user_id
                LEFT JOIN branches b ON i.branch_id = b.branch_id
                WHERE i.is_available = ?
                ORDER BY u.first_name ASC";
        
        return $this->db->select($sql, [$isAvailable]);
    }
    
    /**
     * Get instructor performance metrics
     */
    public function getPerformanceMetrics($instructorId) {
        $sql = "SELECT 
                i.instructor_id,
                CONCAT(u.first_name, ' ', u.last_name) as instructor_name,
                COUNT(DISTINCT l.lesson_id) as total_lessons,
                SUM(CASE WHEN l.status = 'completed' THEN 1 ELSE 0 END) as completed_lessons,
                SUM(CASE WHEN l.status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_lessons,
                AVG(l.student_performance_rating) as avg_rating,
                COUNT(DISTINCT s.student_id) as total_students
                FROM {$this->table} i
                INNER JOIN users u ON i.user_id = u.user_id
                LEFT JOIN lessons l ON i.instructor_id = l.instructor_id
                LEFT JOIN students s ON i.instructor_id = s.assigned_instructor_id
                WHERE i.instructor_id = ?
                GROUP BY i.instructor_id, u.first_name, u.last_name";
        
        return $this->db->selectOne($sql, [$instructorId]);
    }
}
?>