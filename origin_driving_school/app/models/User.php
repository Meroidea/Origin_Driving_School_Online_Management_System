<?php
/**
 * User Model Class
 * 
 * Handles all user-related database operations including authentication
 * 
 * file path: app/models/User.php
 * 
 * @author [sujan darji k231673, and anthony allan regalado k231715]
 * @version 1.0
 */

class User extends Model {
    
    /**
     * Table name
     * @var string
     */
    protected $table = 'users';
    
    /**
     * Primary key
     * @var string
     */
    protected $primaryKey = 'user_id';
    
    /**
     * Fillable columns
     * @var array
     */
    protected $fillable = [
        'email',
        'password_hash',
        'user_role',
        'first_name',
        'last_name',
        'phone',
        'is_active'
    ];
    
    /**
     * Authenticate user login
     * 
     * @param string $email User email
     * @param string $password User password
     * @return array|bool User data or false
     */
    public function authenticate($email, $password) {
        // Get user by email
        $user = $this->getOne(['email' => $email]);
        
        if (!$user) {
            return false;
        }
        
        // Check if user is active
        if ($user['is_active'] != 1) {
            return false;
        }
        
        // Verify password
        if (!password_verify($password, $user['password_hash'])) {
            return false;
        }
        
        // Update last login
        $this->updateLastLogin($user['user_id']);
        
        return $user;
    }
    
    /**
     * Register new user
     * 
     * @param array $userData User data
     * @return int|bool User ID or false
     */
    public function register($userData) {
        // Validate required fields
        $errors = $this->validate($userData, [
            'email' => 'required|email',
            'password' => 'required|min:8',
            'first_name' => 'required',
            'last_name' => 'required',
            'phone' => 'required',
            'user_role' => 'required'
        ]);
        
        if (!empty($errors)) {
            return false;
        }
        
        // Check if email already exists
        if ($this->exists(['email' => $userData['email']])) {
            return false;
        }
        
        // Hash password
        $userData['password_hash'] = password_hash($userData['password'], PASSWORD_DEFAULT);
        unset($userData['password']);
        
        // Create user
        return $this->create($userData);
    }
    
    /**
     * Update user password
     * 
     * @param int $userId User ID
     * @param string $newPassword New password
     * @return bool
     */
    public function updatePassword($userId, $newPassword) {
        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        
        $sql = "UPDATE {$this->table} SET password_hash = ? WHERE {$this->primaryKey} = ?";
        return $this->db->update($sql, [$passwordHash, $userId]);
    }
    
    /**
     * Update last login timestamp
     * 
     * @param int $userId User ID
     * @return bool
     */
    public function updateLastLogin($userId) {
        $sql = "UPDATE {$this->table} SET last_login = NOW() WHERE {$this->primaryKey} = ?";
        return $this->db->update($sql, [$userId]);
    }
    
    /**
     * Get user by email
     * 
     * @param string $email User email
     * @return array|null
     */
    public function getByEmail($email) {
        return $this->getOne(['email' => $email]);
    }
    
    /**
     * Get users by role
     * 
     * @param string $role User role
     * @return array
     */
    public function getByRole($role) {
        return $this->getAll(['user_role' => $role], 'first_name ASC');
    }
    
    /**
     * Get active users
     * 
     * @return array
     */
    public function getActiveUsers() {
        return $this->getAll(['is_active' => 1], 'first_name ASC');
    }
    
    /**
     * Deactivate user
     * 
     * @param int $userId User ID
     * @return bool
     */
    public function deactivate($userId) {
        return $this->update($userId, ['is_active' => 0]);
    }
    
    /**
     * Activate user
     * 
     * @param int $userId User ID
     * @return bool
     */
    public function activate($userId) {
        return $this->update($userId, ['is_active' => 1]);
    }
    
    /**
     * Get full user details with role-specific information
     * 
     * @param int $userId User ID
     * @return array|null
     */
    public function getFullDetails($userId) {
        $user = $this->getById($userId);
        
        if (!$user) {
            return null;
        }
        
        // Get role-specific details
        switch ($user['user_role']) {
            case 'student':
                $sql = "SELECT u.*, s.*, b.branch_name 
                        FROM users u
                        INNER JOIN students s ON u.user_id = s.user_id
                        INNER JOIN branches b ON s.branch_id = b.branch_id
                        WHERE u.user_id = ?";
                break;
                
            case 'instructor':
                $sql = "SELECT u.*, i.*, b.branch_name 
                        FROM users u
                        INNER JOIN instructors i ON u.user_id = i.user_id
                        INNER JOIN branches b ON i.branch_id = b.branch_id
                        WHERE u.user_id = ?";
                break;
                
            case 'staff':
                $sql = "SELECT u.*, st.*, b.branch_name 
                        FROM users u
                        INNER JOIN staff st ON u.user_id = st.user_id
                        INNER JOIN branches b ON st.branch_id = b.branch_id
                        WHERE u.user_id = ?";
                break;
                
            default:
                return $user;
        }
        
        return $this->db->selectOne($sql, [$userId]);
    }
    
    /**
     * Search users
     * 
     * @param string $searchTerm Search term
     * @param string $role Optional role filter
     * @return array
     */
    public function search($searchTerm, $role = null) {
        $params = ["%{$searchTerm}%", "%{$searchTerm}%", "%{$searchTerm}%"];
        
        $sql = "SELECT * FROM {$this->table} 
                WHERE (first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)";
        
        if ($role) {
            $sql .= " AND user_role = ?";
            $params[] = $role;
        }
        
        $sql .= " ORDER BY first_name ASC";
        
        return $this->db->select($sql, $params);
    }
    
    /**
     * Get user statistics
     * 
     * @return array
     */
    public function getStatistics() {
        $sql = "SELECT 
                    COUNT(*) as total_users,
                    SUM(CASE WHEN user_role = 'student' THEN 1 ELSE 0 END) as total_students,
                    SUM(CASE WHEN user_role = 'instructor' THEN 1 ELSE 0 END) as total_instructors,
                    SUM(CASE WHEN user_role = 'staff' THEN 1 ELSE 0 END) as total_staff,
                    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_users
                FROM {$this->table}";
        
        return $this->db->selectOne($sql);
    }
    
    /**
     * Get recently registered users
     * 
     * @param int $limit Number of users to retrieve
     * @return array
     */
    public function getRecentlyRegistered($limit = 5) {
        $sql = "SELECT * FROM {$this->table} 
                ORDER BY created_at DESC 
                LIMIT ?";
        
        return $this->db->select($sql, [$limit]);
    }
}

?>