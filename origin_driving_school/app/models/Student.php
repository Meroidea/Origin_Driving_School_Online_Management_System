<?php
/**
 * Student Model Class
 * 
 * Handles all student-related database operations
 * 
 * file path: app/models/Student.php
 * 
 * @author [sujan darji k231673, and anthony allan regalado k231715]
 * @version 1.0
 */

class Student extends Model {
    
    protected $table = 'students';
    protected $primaryKey = 'student_id';
    
    protected $fillable = [
        'user_id',
        'license_number',
        'license_status',
        'date_of_birth',
        'address',
        'suburb',
        'postcode',
        'emergency_contact_name',
        'emergency_contact_phone',
        'medical_conditions',
        'enrollment_date',
        'branch_id',
        'assigned_instructor_id',
        'total_lessons_completed',
        'test_ready',
        'notes'
    ];
    
    /**
     * Get student by user ID
     */
    public function getByUserId($userId) {
        return $this->getOne(['user_id' => $userId]);
    }
    
    /**
     * Get all students with user and branch details
     */
    public function getAllWithDetails() {
        $sql = "SELECT s.*, u.email, u.first_name, u.last_name, u.phone, u.is_active,
                b.branch_name,
                CONCAT(iu.first_name, ' ', iu.last_name) AS instructor_name
                FROM {$this->table} s
                INNER JOIN users u ON s.user_id = u.user_id
                INNER JOIN branches b ON s.branch_id = b.branch_id
                LEFT JOIN instructors i ON s.assigned_instructor_id = i.instructor_id
                LEFT JOIN users iu ON i.user_id = iu.user_id
                ORDER BY u.first_name ASC";
        
        return $this->db->select($sql);
    }
    
    /**
     * Get student with full details
     */
    public function getStudentWithDetails($studentId) {
        $sql = "SELECT s.*, u.email, u.first_name, u.last_name, u.phone, u.is_active,
                b.branch_name, b.branch_id,
                CONCAT(iu.first_name, ' ', iu.last_name) AS instructor_name,
                i.instructor_id
                FROM {$this->table} s
                INNER JOIN users u ON s.user_id = u.user_id
                INNER JOIN branches b ON s.branch_id = b.branch_id
                LEFT JOIN instructors i ON s.assigned_instructor_id = i.instructor_id
                LEFT JOIN users iu ON i.user_id = iu.user_id
                WHERE s.{$this->primaryKey} = ?";
        
        return $this->db->selectOne($sql, [$studentId]);
    }
    
    /**
     * Get recently enrolled students
     */
    public function getRecentStudents($limit = 5) {
        $sql = "SELECT s.*, u.email, u.first_name, u.last_name, u.phone,
                b.branch_name
                FROM {$this->table} s
                INNER JOIN users u ON s.user_id = u.user_id
                INNER JOIN branches b ON s.branch_id = b.branch_id
                ORDER BY s.enrollment_date DESC
                LIMIT ?";
        
        return $this->db->select($sql, [$limit]);
    }
    
    /**
     * Search students
     */
    public function search($searchTerm) {
        $params = ["%{$searchTerm}%", "%{$searchTerm}%", "%{$searchTerm}%"];
        
        $sql = "SELECT s.*, u.email, u.first_name, u.last_name, u.phone,
                b.branch_name
                FROM {$this->table} s
                INNER JOIN users u ON s.user_id = u.user_id
                INNER JOIN branches b ON s.branch_id = b.branch_id
                WHERE u.first_name LIKE ? 
                OR u.last_name LIKE ? 
                OR u.email LIKE ?
                ORDER BY u.first_name ASC";
        
        return $this->db->select($sql, $params);
    }
    
    /**
     * Get students by branch
     */
    public function getByBranch($branchId) {
        $sql = "SELECT s.*, u.first_name, u.last_name, u.email, u.phone
                FROM {$this->table} s
                INNER JOIN users u ON s.user_id = u.user_id
                WHERE s.branch_id = ?
                ORDER BY u.first_name ASC";
        
        return $this->db->select($sql, [$branchId]);
    }
    
    /**
     * Get students by instructor
     */
    public function getByInstructor($instructorId) {
        $sql = "SELECT s.*, u.first_name, u.last_name, u.email, u.phone
                FROM {$this->table} s
                INNER JOIN users u ON s.user_id = u.user_id
                WHERE s.assigned_instructor_id = ?
                ORDER BY u.first_name ASC";
        
        return $this->db->select($sql, [$instructorId]);
    }
    
    /**
     * Update lesson count
     */
    public function updateLessonCount($studentId) {
        $sql = "UPDATE {$this->table} 
                SET total_lessons_completed = (
                    SELECT COUNT(*) FROM lessons 
                    WHERE student_id = ? AND status = 'completed'
                )
                WHERE {$this->primaryKey} = ?";
        
        return $this->db->update($sql, [$studentId, $studentId]);
    }
    
    /**
     * Mark student as test ready
     */
    public function markTestReady($studentId, $ready = true) {
        return $this->update($studentId, ['test_ready' => $ready ? 1 : 0]);
    }
    
    /**
     * Assign instructor to student
     */
    public function assignInstructor($studentId, $instructorId) {
        return $this->update($studentId, ['assigned_instructor_id' => $instructorId]);
    }
    
    /**
     * Get student statistics
     */
    public function getStatistics() {
        $sql = "SELECT 
                COUNT(*) as total_students,
                SUM(CASE WHEN test_ready = 1 THEN 1 ELSE 0 END) as test_ready_students,
                AVG(total_lessons_completed) as avg_lessons,
                SUM(CASE WHEN license_status = 'learner' THEN 1 ELSE 0 END) as learner_students,
                SUM(CASE WHEN license_status = 'probationary' THEN 1 ELSE 0 END) as probationary_students
                FROM {$this->table}";
        
        return $this->db->selectOne($sql);
    }
}
?>