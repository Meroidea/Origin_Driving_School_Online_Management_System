<?php
/**
 * Enhanced Lesson Model Class
 * 
 * Professional lesson management system with advanced scheduling,
 * analytics, and business intelligence features
 * 
 * file path: app/models/Lesson.php
 * 
 * @author [SUJAN DARJI K231673 AND ANTHONY ALLAN REGALADO K231715]
 * @version 2.0
 */

class Lesson extends Model {
    
    protected $table = 'lessons';
    protected $primaryKey = 'lesson_id';
    
    protected $fillable = [
        'student_id',
        'instructor_id',
        'vehicle_id',
        'course_id',
        'lesson_date',
        'start_time',
        'end_time',
        'pickup_location',
        'dropoff_location',
        'lesson_type',
        'status',
        'instructor_notes',
        'student_performance_rating',
        'skills_practiced',
        'weather_conditions',
        'traffic_conditions',
        'lesson_objectives',
        'objectives_achieved'
    ];
    
    // ==================== CORE LESSON QUERIES ====================
    
    /**
     * Get upcoming lessons with enhanced details
     */
    public function getUpcomingLessons($limit = 10) {
        $sql = "SELECT l.*, 
                CONCAT(su.first_name, ' ', su.last_name) AS student_name,
                su.email AS student_email,
                su.phone AS student_phone,
                CONCAT(iu.first_name, ' ', iu.last_name) AS instructor_name,
                iu.email AS instructor_email,
                iu.phone AS instructor_phone,
                v.registration_number,
                v.make,
                v.model,
                v.color,
                c.course_name,
                c.course_code,
                b.branch_name,
                TIMESTAMPDIFF(MINUTE, l.start_time, l.end_time) AS duration_minutes,
                CASE 
                    WHEN l.lesson_date = CURDATE() THEN 'Today'
                    WHEN l.lesson_date = DATE_ADD(CURDATE(), INTERVAL 1 DAY) THEN 'Tomorrow'
                    ELSE DATE_FORMAT(l.lesson_date, '%W, %M %d')
                END AS lesson_day_display
                FROM {$this->table} l
                INNER JOIN students s ON l.student_id = s.student_id
                INNER JOIN users su ON s.user_id = su.user_id
                INNER JOIN instructors i ON l.instructor_id = i.instructor_id
                INNER JOIN users iu ON i.user_id = iu.user_id
                LEFT JOIN vehicles v ON l.vehicle_id = v.vehicle_id
                LEFT JOIN courses c ON l.course_id = c.course_id
                INNER JOIN branches b ON s.branch_id = b.branch_id
                WHERE l.lesson_date >= CURDATE() AND l.status = 'scheduled'
                ORDER BY l.lesson_date ASC, l.start_time ASC
                LIMIT ?";
        
        return $this->db->select($sql, [$limit]);
    }
    
    /**
     * Get upcoming lessons count
     */
    public function getUpcomingLessonsCount() {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}
                WHERE lesson_date >= CURDATE() AND status = 'scheduled'";
        
        $result = $this->db->selectOne($sql);
        return $result ? (int)$result['count'] : 0;
    }
    
    /**
     * Get comprehensive lesson details
     */
    public function getLessonWithFullDetails($lessonId) {
        $sql = "SELECT l.*, 
                s.license_number AS student_license,
                s.enrollment_date,
                CONCAT(su.first_name, ' ', su.last_name) AS student_name,
                su.email AS student_email,
                su.phone AS student_phone,
                CONCAT(iu.first_name, ' ', iu.last_name) AS instructor_name,
                iu.email AS instructor_email,
                iu.phone AS instructor_phone,
                i.license_number AS instructor_license,
                i.specialization,
                v.registration_number,
                v.make,
                v.model,
                v.year,
                v.color,
                v.transmission_type,
                c.course_name,
                c.course_code,
                c.duration_weeks,
                c.total_hours,
                b.branch_name,
                b.address AS branch_address,
                b.phone AS branch_phone,
                TIMESTAMPDIFF(MINUTE, l.start_time, l.end_time) AS duration_minutes,
                (SELECT COUNT(*) FROM lessons WHERE student_id = l.student_id AND status = 'completed') AS student_completed_lessons,
                (SELECT COUNT(*) FROM lessons WHERE student_id = l.student_id AND status = 'scheduled') AS student_pending_lessons
                FROM {$this->table} l
                INNER JOIN students s ON l.student_id = s.student_id
                INNER JOIN users su ON s.user_id = su.user_id
                INNER JOIN instructors i ON l.instructor_id = i.instructor_id
                INNER JOIN users iu ON i.user_id = iu.user_id
                LEFT JOIN vehicles v ON l.vehicle_id = v.vehicle_id
                LEFT JOIN courses c ON l.course_id = c.course_id
                INNER JOIN branches b ON s.branch_id = b.branch_id
                WHERE l.{$this->primaryKey} = ?";
        
        return $this->db->selectOne($sql, [$lessonId]);
    }
    
    /**
     * Get lesson with details
     */
    public function getLessonWithDetails($lessonId) {
        $sql = "SELECT l.*, 
                CONCAT(su.first_name, ' ', su.last_name) AS student_name,
                su.email AS student_email,
                su.phone AS student_phone,
                CONCAT(iu.first_name, ' ', iu.last_name) AS instructor_name,
                iu.email AS instructor_email,
                iu.phone AS instructor_phone,
                v.registration_number, v.make, v.model,
                c.course_name,
                b.branch_name
                FROM {$this->table} l
                INNER JOIN students s ON l.student_id = s.student_id
                INNER JOIN users su ON s.user_id = su.user_id
                INNER JOIN instructors i ON l.instructor_id = i.instructor_id
                INNER JOIN users iu ON i.user_id = iu.user_id
                LEFT JOIN vehicles v ON l.vehicle_id = v.vehicle_id
                LEFT JOIN courses c ON l.course_id = c.course_id
                INNER JOIN branches b ON s.branch_id = b.branch_id
                WHERE l.{$this->primaryKey} = ?";
        
        return $this->db->selectOne($sql, [$lessonId]);
    }
    
    /**
     * Get all lessons with full details
     */
    public function getAllWithDetails() {
        $sql = "SELECT l.*, 
                CONCAT(su.first_name, ' ', su.last_name) AS student_name,
                su.phone AS student_phone,
                CONCAT(iu.first_name, ' ', iu.last_name) AS instructor_name,
                v.registration_number,
                v.make,
                v.model,
                c.course_name,
                TIMESTAMPDIFF(MINUTE, l.start_time, l.end_time) AS duration_minutes
                FROM {$this->table} l
                INNER JOIN students s ON l.student_id = s.student_id
                INNER JOIN users su ON s.user_id = su.user_id
                INNER JOIN instructors i ON l.instructor_id = i.instructor_id
                INNER JOIN users iu ON i.user_id = iu.user_id
                LEFT JOIN vehicles v ON l.vehicle_id = v.vehicle_id
                LEFT JOIN courses c ON l.course_id = c.course_id
                ORDER BY l.lesson_date DESC, l.start_time DESC";
        
        return $this->db->select($sql);
    }
    
    /**
     * Get lessons by status
     */
    public function getByStatus($status) {
        $sql = "SELECT l.*, 
                CONCAT(su.first_name, ' ', su.last_name) AS student_name,
                su.phone AS student_phone,
                CONCAT(iu.first_name, ' ', iu.last_name) AS instructor_name,
                v.registration_number,
                v.make,
                v.model,
                TIMESTAMPDIFF(MINUTE, l.start_time, l.end_time) AS duration_minutes
                FROM {$this->table} l
                INNER JOIN students s ON l.student_id = s.student_id
                INNER JOIN users su ON s.user_id = su.user_id
                INNER JOIN instructors i ON l.instructor_id = i.instructor_id
                INNER JOIN users iu ON i.user_id = iu.user_id
                LEFT JOIN vehicles v ON l.vehicle_id = v.vehicle_id
                WHERE l.status = ?
                ORDER BY l.lesson_date DESC, l.start_time DESC";
        
        return $this->db->select($sql, [$status]);
    }
    
    /**
     * Get lessons by date
     */
    public function getByDate($date) {
        $sql = "SELECT l.*, 
                CONCAT(su.first_name, ' ', su.last_name) AS student_name,
                su.phone AS student_phone,
                CONCAT(iu.first_name, ' ', iu.last_name) AS instructor_name,
                v.registration_number,
                v.make,
                v.model,
                TIMESTAMPDIFF(MINUTE, l.start_time, l.end_time) AS duration_minutes
                FROM {$this->table} l
                INNER JOIN students s ON l.student_id = s.student_id
                INNER JOIN users su ON s.user_id = su.user_id
                INNER JOIN instructors i ON l.instructor_id = i.instructor_id
                INNER JOIN users iu ON i.user_id = iu.user_id
                LEFT JOIN vehicles v ON l.vehicle_id = v.vehicle_id
                WHERE l.lesson_date = ?
                ORDER BY l.start_time ASC";
        
        return $this->db->select($sql, [$date]);
    }
    
    /**
     * Advanced search with multiple criteria
     */
    public function advancedSearch($criteria) {
        $conditions = [];
        $params = [];
        
        $sql = "SELECT l.*, 
                CONCAT(su.first_name, ' ', su.last_name) AS student_name,
                su.phone AS student_phone,
                CONCAT(iu.first_name, ' ', iu.last_name) AS instructor_name,
                v.registration_number,
                v.make,
                v.model,
                c.course_name,
                TIMESTAMPDIFF(MINUTE, l.start_time, l.end_time) AS duration_minutes
                FROM {$this->table} l
                INNER JOIN students s ON l.student_id = s.student_id
                INNER JOIN users su ON s.user_id = su.user_id
                INNER JOIN instructors i ON l.instructor_id = i.instructor_id
                INNER JOIN users iu ON i.user_id = iu.user_id
                LEFT JOIN vehicles v ON l.vehicle_id = v.vehicle_id
                LEFT JOIN courses c ON l.course_id = c.course_id
                WHERE 1=1";
        
        if (!empty($criteria['search_term'])) {
            $conditions[] = "(su.first_name LIKE ? OR su.last_name LIKE ? OR iu.first_name LIKE ? OR iu.last_name LIKE ?)";
            $searchTerm = "%{$criteria['search_term']}%";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        }
        
        if (!empty($criteria['status'])) {
            $conditions[] = "l.status = ?";
            $params[] = $criteria['status'];
        }
        
        if (!empty($criteria['lesson_type'])) {
            $conditions[] = "l.lesson_type = ?";
            $params[] = $criteria['lesson_type'];
        }
        
        if (!empty($criteria['date_from'])) {
            $conditions[] = "l.lesson_date >= ?";
            $params[] = $criteria['date_from'];
        }
        
        if (!empty($criteria['date_to'])) {
            $conditions[] = "l.lesson_date <= ?";
            $params[] = $criteria['date_to'];
        }
        
        if (!empty($criteria['instructor_id'])) {
            $conditions[] = "l.instructor_id = ?";
            $params[] = $criteria['instructor_id'];
        }
        
        if (!empty($criteria['student_id'])) {
            $conditions[] = "l.student_id = ?";
            $params[] = $criteria['student_id'];
        }
        
        if (!empty($conditions)) {
            $sql .= " AND " . implode(" AND ", $conditions);
        }
        
        $sql .= " ORDER BY l.lesson_date DESC, l.start_time DESC";
        
        return $this->db->select($sql, $params);
    }
    
    /**
     * Search lessons
     */
    public function search($searchTerm) {
        $params = ["%{$searchTerm}%", "%{$searchTerm}%", "%{$searchTerm}%"];
        
        $sql = "SELECT l.*, 
                CONCAT(su.first_name, ' ', su.last_name) AS student_name,
                su.phone AS student_phone,
                CONCAT(iu.first_name, ' ', iu.last_name) AS instructor_name,
                v.registration_number,
                v.make,
                v.model,
                TIMESTAMPDIFF(MINUTE, l.start_time, l.end_time) AS duration_minutes
                FROM {$this->table} l
                INNER JOIN students s ON l.student_id = s.student_id
                INNER JOIN users su ON s.user_id = su.user_id
                INNER JOIN instructors i ON l.instructor_id = i.instructor_id
                INNER JOIN users iu ON i.user_id = iu.user_id
                LEFT JOIN vehicles v ON l.vehicle_id = v.vehicle_id
                WHERE su.first_name LIKE ? 
                OR su.last_name LIKE ? 
                OR iu.first_name LIKE ?
                ORDER BY l.lesson_date DESC, l.start_time DESC";
        
        return $this->db->select($sql, $params);
    }
    
    // ==================== INSTRUCTOR LESSONS ====================
    
    /**
     * Get instructor upcoming lessons
     */
    public function getInstructorUpcomingLessons($instructorId, $limit = 10) {
        $sql = "SELECT l.*, 
                CONCAT(su.first_name, ' ', su.last_name) AS student_name,
                su.phone AS student_phone,
                v.registration_number, v.make, v.model,
                TIMESTAMPDIFF(MINUTE, l.start_time, l.end_time) AS duration_minutes
                FROM {$this->table} l
                INNER JOIN students s ON l.student_id = s.student_id
                INNER JOIN users su ON s.user_id = su.user_id
                LEFT JOIN vehicles v ON l.vehicle_id = v.vehicle_id
                WHERE l.instructor_id = ? 
                AND l.lesson_date >= CURDATE() 
                AND l.status = 'scheduled'
                ORDER BY l.lesson_date ASC, l.start_time ASC
                LIMIT ?";
        
        return $this->db->select($sql, [$instructorId, $limit]);
    }
    
    /**
     * Get instructor lessons
     */
    public function getInstructorLessons($instructorId) {
        $sql = "SELECT l.*, 
                CONCAT(su.first_name, ' ', su.last_name) AS student_name,
                su.phone AS student_phone,
                v.registration_number,
                v.make,
                v.model,
                TIMESTAMPDIFF(MINUTE, l.start_time, l.end_time) AS duration_minutes
                FROM {$this->table} l
                INNER JOIN students s ON l.student_id = s.student_id
                INNER JOIN users su ON s.user_id = su.user_id
                LEFT JOIN vehicles v ON l.vehicle_id = v.vehicle_id
                WHERE l.instructor_id = ?
                ORDER BY l.lesson_date DESC, l.start_time DESC";
        
        return $this->db->select($sql, [$instructorId]);
    }
    
    /**
     * Get instructor lessons by status
     */
    public function getInstructorLessonsByStatus($instructorId, $status) {
        $sql = "SELECT l.*, 
                CONCAT(su.first_name, ' ', su.last_name) AS student_name,
                su.phone AS student_phone,
                v.registration_number,
                v.make,
                v.model,
                TIMESTAMPDIFF(MINUTE, l.start_time, l.end_time) AS duration_minutes
                FROM {$this->table} l
                INNER JOIN students s ON l.student_id = s.student_id
                INNER JOIN users su ON s.user_id = su.user_id
                LEFT JOIN vehicles v ON l.vehicle_id = v.vehicle_id
                WHERE l.instructor_id = ? AND l.status = ?
                ORDER BY l.lesson_date DESC, l.start_time DESC";
        
        return $this->db->select($sql, [$instructorId, $status]);
    }
    
    /**
     * Get instructor's daily schedule
     */
    public function getInstructorDailySchedule($instructorId, $date) {
        $sql = "SELECT l.*,
                CONCAT(su.first_name, ' ', su.last_name) AS student_name,
                su.phone AS student_phone,
                v.registration_number,
                v.make,
                v.model,
                TIMESTAMPDIFF(MINUTE, l.start_time, l.end_time) AS duration_minutes
                FROM {$this->table} l
                INNER JOIN students s ON l.student_id = s.student_id
                INNER JOIN users su ON s.user_id = su.user_id
                LEFT JOIN vehicles v ON l.vehicle_id = v.vehicle_id
                WHERE l.instructor_id = ?
                AND l.lesson_date = ?
                AND l.status != 'cancelled'
                ORDER BY l.start_time ASC";
        
        return $this->db->select($sql, [$instructorId, $date]);
    }
    
    /**
     * Get today's lessons count for instructor
     */
    public function getTodaysLessonsCountForInstructor($instructorId) {
        $sql = "SELECT COUNT(*) as count 
                FROM {$this->table}
                WHERE instructor_id = ? 
                AND lesson_date = CURDATE() 
                AND status = 'scheduled'";
        
        $result = $this->db->selectOne($sql, [$instructorId]);
        return $result ? (int)$result['count'] : 0;
    }
    
    /**
     * Get upcoming lessons count for instructor
     */
    public function getUpcomingLessonsCountForInstructor($instructorId) {
        $sql = "SELECT COUNT(*) as count 
                FROM {$this->table}
                WHERE instructor_id = ? 
                AND lesson_date >= CURDATE() 
                AND status = 'scheduled'";
        
        $result = $this->db->selectOne($sql, [$instructorId]);
        return $result ? (int)$result['count'] : 0;
    }
    
    /**
     * Get total students for instructor
     */
    public function getTotalStudentsForInstructor($instructorId) {
        $sql = "SELECT COUNT(DISTINCT student_id) as count 
                FROM {$this->table}
                WHERE instructor_id = ?";
        
        $result = $this->db->selectOne($sql, [$instructorId]);
        return $result ? (int)$result['count'] : 0;
    }
    
    /**
     * Get completed lessons this month for instructor
     */
    public function getCompletedLessonsThisMonthForInstructor($instructorId) {
        $sql = "SELECT COUNT(*) as count 
                FROM {$this->table}
                WHERE instructor_id = ? 
                AND status = 'completed'
                AND MONTH(lesson_date) = MONTH(CURDATE())
                AND YEAR(lesson_date) = YEAR(CURDATE())";
        
        $result = $this->db->selectOne($sql, [$instructorId]);
        return $result ? (int)$result['count'] : 0;
    }
    
    // ==================== STUDENT LESSONS ====================
    
    /**
     * Get student upcoming lessons
     */
    public function getStudentUpcomingLessons($studentId, $limit = 10) {
        $sql = "SELECT l.*, 
                CONCAT(iu.first_name, ' ', iu.last_name) AS instructor_name,
                iu.phone AS instructor_phone,
                v.registration_number, v.make, v.model,
                TIMESTAMPDIFF(MINUTE, l.start_time, l.end_time) AS duration_minutes
                FROM {$this->table} l
                INNER JOIN instructors i ON l.instructor_id = i.instructor_id
                INNER JOIN users iu ON i.user_id = iu.user_id
                LEFT JOIN vehicles v ON l.vehicle_id = v.vehicle_id
                WHERE l.student_id = ? 
                AND l.lesson_date >= CURDATE() 
                AND l.status = 'scheduled'
                ORDER BY l.lesson_date ASC, l.start_time ASC
                LIMIT ?";
        
        return $this->db->select($sql, [$studentId, $limit]);
    }
    
    /**
     * Get student lessons
     */
    public function getStudentLessons($studentId) {
        $sql = "SELECT l.*, 
                CONCAT(iu.first_name, ' ', iu.last_name) AS instructor_name,
                iu.phone AS instructor_phone,
                v.registration_number,
                v.make,
                v.model,
                TIMESTAMPDIFF(MINUTE, l.start_time, l.end_time) AS duration_minutes
                FROM {$this->table} l
                INNER JOIN instructors i ON l.instructor_id = i.instructor_id
                INNER JOIN users iu ON i.user_id = iu.user_id
                LEFT JOIN vehicles v ON l.vehicle_id = v.vehicle_id
                WHERE l.student_id = ?
                ORDER BY l.lesson_date DESC, l.start_time DESC";
        
        return $this->db->select($sql, [$studentId]);
    }
    
    /**
     * Get student lessons by status
     */
    public function getStudentLessonsByStatus($studentId, $status) {
        $sql = "SELECT l.*, 
                CONCAT(iu.first_name, ' ', iu.last_name) AS instructor_name,
                iu.phone AS instructor_phone,
                v.registration_number,
                v.make,
                v.model,
                TIMESTAMPDIFF(MINUTE, l.start_time, l.end_time) AS duration_minutes
                FROM {$this->table} l
                INNER JOIN instructors i ON l.instructor_id = i.instructor_id
                INNER JOIN users iu ON i.user_id = iu.user_id
                LEFT JOIN vehicles v ON l.vehicle_id = v.vehicle_id
                WHERE l.student_id = ? AND l.status = ?
                ORDER BY l.lesson_date DESC, l.start_time DESC";
        
        return $this->db->select($sql, [$studentId, $status]);
    }
    
    /**
     * Get upcoming lessons count for student
     */
    public function getUpcomingLessonsCountForStudent($studentId) {
        $sql = "SELECT COUNT(*) as count 
                FROM {$this->table}
                WHERE student_id = ? 
                AND lesson_date >= CURDATE() 
                AND status = 'scheduled'";
        
        $result = $this->db->selectOne($sql, [$studentId]);
        return $result ? (int)$result['count'] : 0;
    }
    
    /**
     * Get student progress tracking
     */
    public function getStudentProgress($studentId) {
        $sql = "SELECT 
                COUNT(*) as total_lessons,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_lessons,
                SUM(CASE WHEN status = 'scheduled' THEN 1 ELSE 0 END) as scheduled_lessons,
                AVG(CASE WHEN student_performance_rating IS NOT NULL THEN student_performance_rating ELSE NULL END) as avg_rating,
                SUM(TIMESTAMPDIFF(MINUTE, start_time, end_time)) as total_hours_learned,
                MAX(lesson_date) as last_lesson_date,
                GROUP_CONCAT(DISTINCT lesson_type) as lesson_types_taken
                FROM {$this->table}
                WHERE student_id = ?";
        
        return $this->db->selectOne($sql, [$studentId]);
    }
    
    // ==================== SCHEDULING & AVAILABILITY ====================
    
    /**
     * Check instructor availability for a time slot
     */
    public function checkInstructorAvailability($instructorId, $date, $startTime, $endTime, $excludeLessonId = null) {
        $sql = "SELECT l.*,
                CONCAT(s.first_name, ' ', s.last_name) AS student_name
                FROM {$this->table} l
                INNER JOIN students st ON l.student_id = st.student_id
                INNER JOIN users s ON st.user_id = s.user_id
                WHERE l.instructor_id = ?
                AND l.lesson_date = ?
                AND l.status IN ('scheduled', 'in_progress')
                AND (
                    (l.start_time < ? AND l.end_time > ?)
                    OR (l.start_time < ? AND l.end_time > ?)
                    OR (l.start_time >= ? AND l.end_time <= ?)
                )";
        
        $params = [$instructorId, $date, $endTime, $startTime, $endTime, $startTime, $startTime, $endTime];
        
        if ($excludeLessonId) {
            $sql .= " AND l.lesson_id != ?";
            $params[] = $excludeLessonId;
        }
        
        return $this->db->select($sql, $params);
    }
    
    /**
     * Check for scheduling conflicts (legacy method)
     */
    public function hasSchedulingConflict($instructorId, $date, $startTime, $endTime, $excludeLessonId = null) {
        $conflicts = $this->checkInstructorAvailability($instructorId, $date, $startTime, $endTime, $excludeLessonId);
        return !empty($conflicts);
    }
    
    /**
     * Check vehicle availability
     */
    public function checkVehicleAvailability($vehicleId, $date, $startTime, $endTime, $excludeLessonId = null) {
        $sql = "SELECT COUNT(*) as count 
                FROM {$this->table}
                WHERE vehicle_id = ?
                AND lesson_date = ?
                AND status IN ('scheduled', 'in_progress')
                AND (
                    (start_time < ? AND end_time > ?)
                    OR (start_time < ? AND end_time > ?)
                    OR (start_time >= ? AND end_time <= ?)
                )";
        
        $params = [$vehicleId, $date, $endTime, $startTime, $endTime, $startTime, $startTime, $endTime];
        
        if ($excludeLessonId) {
            $sql .= " AND lesson_id != ?";
            $params[] = $excludeLessonId;
        }
        
        $result = $this->db->selectOne($sql, $params);
        return $result && $result['count'] > 0;
    }
    
    // ==================== STATISTICS & ANALYTICS ====================
    
    /**
     * Get today's lessons count
     */
    public function getTodayLessonsCount() {
        $sql = "SELECT COUNT(*) as count 
                FROM {$this->table}
                WHERE lesson_date = CURDATE() 
                AND status = 'scheduled'";
        
        $result = $this->db->selectOne($sql);
        return $result ? (int)$result['count'] : 0;
    }
    
    /**
     * Get lesson statistics for dashboard
     */
    public function getLessonStatistics($dateFrom = null, $dateTo = null) {
        $params = [];
        $dateCondition = "";
        
        if ($dateFrom && $dateTo) {
            $dateCondition = "AND lesson_date BETWEEN ? AND ?";
            $params = [$dateFrom, $dateTo];
        }
        
        $sql = "SELECT 
                COUNT(*) as total_lessons,
                SUM(CASE WHEN status = 'scheduled' THEN 1 ELSE 0 END) as scheduled,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
                SUM(CASE WHEN status = 'rescheduled' THEN 1 ELSE 0 END) as rescheduled,
                SUM(CASE WHEN lesson_date = CURDATE() AND status = 'scheduled' THEN 1 ELSE 0 END) as today_lessons,
                SUM(CASE WHEN lesson_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND status = 'scheduled' THEN 1 ELSE 0 END) as week_lessons,
                AVG(CASE WHEN student_performance_rating IS NOT NULL THEN student_performance_rating ELSE NULL END) as avg_performance_rating,
                SUM(TIMESTAMPDIFF(MINUTE, start_time, end_time)) as total_minutes
                FROM {$this->table}
                WHERE 1=1 {$dateCondition}";
        
        return $this->db->selectOne($sql, $params);
    }
    
    /**
     * Get instructor performance metrics
     */
    public function getInstructorMetrics($instructorId, $dateFrom = null, $dateTo = null) {
        $params = [$instructorId];
        $dateCondition = "";
        
        if ($dateFrom && $dateTo) {
            $dateCondition = "AND l.lesson_date BETWEEN ? AND ?";
            $params[] = $dateFrom;
            $params[] = $dateTo;
        }
        
        $sql = "SELECT 
                COUNT(*) as total_lessons,
                SUM(CASE WHEN l.status = 'completed' THEN 1 ELSE 0 END) as completed_lessons,
                SUM(CASE WHEN l.status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_lessons,
                AVG(CASE WHEN l.student_performance_rating IS NOT NULL THEN l.student_performance_rating ELSE NULL END) as avg_student_rating,
                SUM(TIMESTAMPDIFF(MINUTE, l.start_time, l.end_time)) as total_teaching_minutes,
                COUNT(DISTINCT l.student_id) as unique_students
                FROM {$this->table} l
                WHERE l.instructor_id = ? {$dateCondition}";
        
        return $this->db->selectOne($sql, $params);
    }
    
    /**
     * Get lessons by date range for calendar
     */
    public function getLessonsByDateRange($dateFrom, $dateTo, $filters = []) {
        $params = [$dateFrom, $dateTo];
        $conditions = "";
        
        if (!empty($filters['instructor_id'])) {
            $conditions .= " AND l.instructor_id = ?";
            $params[] = $filters['instructor_id'];
        }
        
        if (!empty($filters['student_id'])) {
            $conditions .= " AND l.student_id = ?";
            $params[] = $filters['student_id'];
        }
        
        if (!empty($filters['status'])) {
            $conditions .= " AND l.status = ?";
            $params[] = $filters['status'];
        }
        
        $sql = "SELECT l.*,
                CONCAT(su.first_name, ' ', su.last_name) AS student_name,
                CONCAT(iu.first_name, ' ', iu.last_name) AS instructor_name,
                v.registration_number,
                c.course_name,
                TIMESTAMPDIFF(MINUTE, l.start_time, l.end_time) AS duration_minutes
                FROM {$this->table} l
                INNER JOIN students s ON l.student_id = s.student_id
                INNER JOIN users su ON s.user_id = su.user_id
                INNER JOIN instructors i ON l.instructor_id = i.instructor_id
                INNER JOIN users iu ON i.user_id = iu.user_id
                LEFT JOIN vehicles v ON l.vehicle_id = v.vehicle_id
                LEFT JOIN courses c ON l.course_id = c.course_id
                WHERE l.lesson_date BETWEEN ? AND ? {$conditions}
                ORDER BY l.lesson_date ASC, l.start_time ASC";
        
        return $this->db->select($sql, $params);
    }
    
    // ==================== UPDATE OPERATIONS ====================
    
    /**
     * Update lesson status
     */
    public function updateStatus($lessonId, $status, $notes = null) {
        $data = ['status' => $status];
        if ($notes !== null) {
            $data['instructor_notes'] = $notes;
        }
        
        return $this->update($lessonId, $data);
    }
    
    /**
     * Bulk update lesson status
     */
    public function bulkUpdateStatus($lessonIds, $status, $notes = null) {
        if (empty($lessonIds)) {
            return false;
        }
        
        $placeholders = str_repeat('?,', count($lessonIds) - 1) . '?';
        $params = array_merge([$status], $lessonIds);
        
        $sql = "UPDATE {$this->table} 
                SET status = ?, updated_at = NOW()";
        
        if ($notes) {
            $sql .= ", instructor_notes = ?";
            array_splice($params, 1, 0, [$notes]);
        }
        
        $sql .= " WHERE lesson_id IN ({$placeholders})";
        
        return $this->db->query($sql, $params);
    }
}
?>