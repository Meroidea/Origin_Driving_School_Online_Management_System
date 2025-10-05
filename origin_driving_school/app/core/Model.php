<?php
/**
 * Base Model Class
 * 
 * This abstract class provides common database operations for all models
 * All model classes should extend this base class
 * file path: app/core/Model.php
 * 
 * @author [Your Group Members Names and IDs]
 * @version 1.0
 */

abstract class Model {
    
    /**
     * Database instance
     * @var Database
     */
    protected $db;
    
    /**
     * Table name (must be set by child class)
     * @var string
     */
    protected $table;
    
    /**
     * Primary key column name
     * @var string
     */
    protected $primaryKey = 'id';
    
    /**
     * Fillable columns (allowed for mass assignment)
     * @var array
     */
    protected $fillable = [];
    
    /**
     * Constructor - initialize database connection
     */
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get all records from table
     * 
     * @param array $conditions Optional WHERE conditions
     * @param string $orderBy Optional ORDER BY clause
     * @param int $limit Optional LIMIT
     * @return array
     */
    public function getAll($conditions = [], $orderBy = null, $limit = null) {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];
        
        if (!empty($conditions)) {
            $sql .= " WHERE " . $this->buildWhereClause($conditions, $params);
        }
        
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }
        
        if ($limit) {
            $sql .= " LIMIT {$limit}";
        }
        
        return $this->db->select($sql, $params);
    }
    
    /**
     * Get single record by ID
     * 
     * @param int $id Record ID
     * @return array|null
     */
    public function getById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ? LIMIT 1";
        return $this->db->selectOne($sql, [$id]);
    }
    
    /**
     * Get single record by conditions
     * 
     * @param array $conditions WHERE conditions
     * @return array|null
     */
    public function getOne($conditions) {
        $params = [];
        $sql = "SELECT * FROM {$this->table} WHERE " . $this->buildWhereClause($conditions, $params) . " LIMIT 1";
        return $this->db->selectOne($sql, $params);
    }
    
    /**
     * Get records with pagination
     * 
     * @param int $page Current page number
     * @param int $perPage Records per page
     * @param array $conditions Optional WHERE conditions
     * @param string $orderBy Optional ORDER BY clause
     * @return array
     */
    public function paginate($page = 1, $perPage = RECORDS_PER_PAGE, $conditions = [], $orderBy = null) {
        $offset = ($page - 1) * $perPage;
        $params = [];
        
        $sql = "SELECT * FROM {$this->table}";
        
        if (!empty($conditions)) {
            $sql .= " WHERE " . $this->buildWhereClause($conditions, $params);
        }
        
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }
        
        $sql .= " LIMIT {$perPage} OFFSET {$offset}";
        
        $records = $this->db->select($sql, $params);
        $total = $this->count($conditions);
        
        return [
            'data' => $records,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage)
        ];
    }
    
    /**
     * Insert new record
     * 
     * @param array $data Data to insert
     * @return int|bool Last insert ID or false
     */
    public function create($data) {
        // Filter only fillable columns
        $data = $this->filterFillable($data);
        
        if (empty($data)) {
            return false;
        }
        
        $columns = array_keys($data);
        $values = array_values($data);
        
        $columnList = implode(', ', $columns);
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        
        $sql = "INSERT INTO {$this->table} ({$columnList}) VALUES ({$placeholders})";
        
        return $this->db->insert($sql, $values);
    }
    
    /**
     * Update record by ID
     * 
     * @param int $id Record ID
     * @param array $data Data to update
     * @return bool
     */
    public function update($id, $data) {
        // Filter only fillable columns
        $data = $this->filterFillable($data);
        
        if (empty($data)) {
            return false;
        }
        
        $setClause = [];
        $values = [];
        
        foreach ($data as $column => $value) {
            $setClause[] = "{$column} = ?";
            $values[] = $value;
        }
        
        $values[] = $id;
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $setClause) . " WHERE {$this->primaryKey} = ?";
        
        return $this->db->update($sql, $values);
    }
    
    /**
     * Delete record by ID
     * 
     * @param int $id Record ID
     * @return bool
     */
    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?";
        return $this->db->delete($sql, [$id]);
    }
    
    /**
     * Count records
     * 
     * @param array $conditions Optional WHERE conditions
     * @return int
     */
    public function count($conditions = []) {
        $params = [];
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";
        
        if (!empty($conditions)) {
            $sql .= " WHERE " . $this->buildWhereClause($conditions, $params);
        }
        
        $result = $this->db->selectOne($sql, $params);
        return $result ? (int)$result['count'] : 0;
    }
    
    /**
     * Check if record exists
     * 
     * @param array $conditions WHERE conditions
     * @return bool
     */
    public function exists($conditions) {
        return $this->count($conditions) > 0;
    }
    
    /**
     * Execute custom query
     * 
     * @param string $sql SQL query
     * @param array $params Query parameters
     * @return array
     */
    public function customQuery($sql, $params = []) {
        return $this->db->select($sql, $params);
    }
    
    /**
     * Execute custom query and return single row
     * 
     * @param string $sql SQL query
     * @param array $params Query parameters
     * @return array|null
     */
    public function customQueryOne($sql, $params = []) {
        return $this->db->selectOne($sql, $params);
    }
    
    /**
     * Build WHERE clause from conditions array
     * 
     * @param array $conditions Conditions array
     * @param array &$params Parameters array (passed by reference)
     * @return string WHERE clause
     */
    protected function buildWhereClause($conditions, &$params) {
        $whereParts = [];
        
        foreach ($conditions as $column => $value) {
            if (is_array($value)) {
                // Handle IN clause
                $placeholders = implode(',', array_fill(0, count($value), '?'));
                $whereParts[] = "{$column} IN ({$placeholders})";
                $params = array_merge($params, $value);
            } else {
                $whereParts[] = "{$column} = ?";
                $params[] = $value;
            }
        }
        
        return implode(' AND ', $whereParts);
    }
    
    /**
     * Filter data to only include fillable columns
     * 
     * @param array $data Data to filter
     * @return array Filtered data
     */
    protected function filterFillable($data) {
        if (empty($this->fillable)) {
            return $data;
        }
        
        return array_intersect_key($data, array_flip($this->fillable));
    }
    
    /**
     * Validate data against rules
     * 
     * @param array $data Data to validate
     * @param array $rules Validation rules
     * @return array Array of errors (empty if valid)
     */
    protected function validate($data, $rules) {
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            $ruleList = explode('|', $rule);
            $value = $data[$field] ?? null;
            
            foreach ($ruleList as $singleRule) {
                // Required rule
                if ($singleRule === 'required' && empty($value)) {
                    $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . " is required";
                    break;
                }
                
                // Email rule
                if ($singleRule === 'email' && !empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[$field] = "Invalid email format";
                    break;
                }
                
                // Numeric rule
                if ($singleRule === 'numeric' && !empty($value) && !is_numeric($value)) {
                    $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . " must be numeric";
                    break;
                }
                
                // Min length rule
                if (strpos($singleRule, 'min:') === 0) {
                    $minLength = (int)substr($singleRule, 4);
                    if (!empty($value) && strlen($value) < $minLength) {
                        $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . " must be at least {$minLength} characters";
                        break;
                    }
                }
                
                // Max length rule
                if (strpos($singleRule, 'max:') === 0) {
                    $maxLength = (int)substr($singleRule, 4);
                    if (!empty($value) && strlen($value) > $maxLength) {
                        $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . " must not exceed {$maxLength} characters";
                        break;
                    }
                }
            }
        }
        
        return $errors;
    }
    
    /**
     * Begin database transaction
     * 
     * @return bool
     */
    protected function beginTransaction() {
        return $this->db->beginTransaction();
    }
    
    /**
     * Commit database transaction
     * 
     * @return bool
     */
    protected function commit() {
        return $this->db->commit();
    }
    
    /**
     * Rollback database transaction
     * 
     * @return bool
     */
    protected function rollback() {
        return $this->db->rollback();
    }
}

?>