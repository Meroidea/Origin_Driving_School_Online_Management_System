<?php
/**
 * Invoice Model Class - Origin Driving School Management System
 * 
 * Handles all invoice-related database operations including
 * invoice generation, payment tracking, and financial reporting
 * 
 * file path: app/models/Invoice.php
 * 
 * @author [SUJAN DARJI K231673 AND ANTHONY ALLAN REGALADO K231715]
 * @version 1.0
 */

class Invoice extends Model {
    
    /**
     * Table name
     * @var string
     */
    protected $table = 'invoices';
    
    /**
     * Primary key
     * @var string
     */
    protected $primaryKey = 'invoice_id';
    
    /**
     * Fillable columns for mass assignment
     * @var array
     */
    protected $fillable = [
        'invoice_number',
        'student_id',
        'course_id',
        'issue_date',
        'due_date',
        'subtotal',
        'tax_amount',
        'total_amount',
        'amount_paid',
        'balance_due',
        'status',
        'notes'
    ];
    
    /**
     * Get all invoices with student and course details
     * 
     * @return array
     */
    public function getAllWithDetails() {
        $sql = "SELECT i.*, 
                CONCAT(u.first_name, ' ', u.last_name) AS student_name,
                u.email AS student_email,
                c.course_name,
                c.course_code
                FROM {$this->table} i
                INNER JOIN students s ON i.student_id = s.student_id
                INNER JOIN users u ON s.user_id = u.user_id
                INNER JOIN courses c ON i.course_id = c.course_id
                ORDER BY i.created_at DESC";
        
        return $this->db->select($sql);
    }
    
    /**
     * Get invoice with full details
     * 
     * @param int $invoiceId Invoice ID
     * @return array|null
     */
    public function getInvoiceWithDetails($invoiceId) {
        $sql = "SELECT i.*, 
                s.student_id,
                CONCAT(u.first_name, ' ', u.last_name) AS student_name,
                u.email AS student_email,
                u.phone AS student_phone,
                s.address AS student_address,
                s.suburb AS student_suburb,
                s.postcode AS student_postcode,
                c.course_name,
                c.course_code,
                c.description AS course_description,
                c.number_of_lessons,
                b.branch_name,
                b.address AS branch_address,
                b.phone AS branch_phone,
                b.email AS branch_email
                FROM {$this->table} i
                INNER JOIN students s ON i.student_id = s.student_id
                INNER JOIN users u ON s.user_id = u.user_id
                INNER JOIN courses c ON i.course_id = c.course_id
                INNER JOIN branches b ON s.branch_id = b.branch_id
                WHERE i.{$this->primaryKey} = ?";
        
        return $this->db->selectOne($sql, [$invoiceId]);
    }
    
    /**
     * Get student's pending invoices
     * 
     * @param int $studentId Student ID
     * @return array
     */
    public function getStudentPendingInvoices($studentId) {
        $sql = "SELECT i.*, c.course_name, c.course_code
                FROM {$this->table} i
                INNER JOIN courses c ON i.course_id = c.course_id
                WHERE i.student_id = ? 
                AND i.status IN ('unpaid', 'partially_paid', 'overdue')
                ORDER BY i.due_date ASC";
        
        return $this->db->select($sql, [$studentId]);
    }
    
    /**
     * Get all invoices for a specific student
     * 
     * @param int $studentId Student ID
     * @return array
     */
    public function getStudentInvoices($studentId) {
        $sql = "SELECT i.*, c.course_name, c.course_code
                FROM {$this->table} i
                INNER JOIN courses c ON i.course_id = c.course_id
                WHERE i.student_id = ?
                ORDER BY i.issue_date DESC";
        
        return $this->db->select($sql, [$studentId]);
    }
    
    /**
     * Get total pending amount for student
     * 
     * @param int $studentId Student ID
     * @return float
     */
    public function getTotalPendingForStudent($studentId) {
        $sql = "SELECT SUM(balance_due) as total 
                FROM {$this->table}
                WHERE student_id = ? 
                AND status IN ('unpaid', 'partially_paid', 'overdue')";
        
        $result = $this->db->selectOne($sql, [$studentId]);
        return $result ? (float)$result['total'] : 0.00;
    }
    
    /**
     * Get invoices by status
     * 
     * @param string $status Invoice status
     * @return array
     */
    public function getByStatus($status) {
        $sql = "SELECT i.*, 
                CONCAT(u.first_name, ' ', u.last_name) AS student_name,
                c.course_name
                FROM {$this->table} i
                INNER JOIN students s ON i.student_id = s.student_id
                INNER JOIN users u ON s.user_id = u.user_id
                INNER JOIN courses c ON i.course_id = c.course_id
                WHERE i.status = ?
                ORDER BY i.due_date ASC";
        
        return $this->db->select($sql, [$status]);
    }
    
    /**
     * Get overdue invoices
     * 
     * @return array
     */
    public function getOverdueInvoices() {
        $sql = "SELECT i.*, 
                CONCAT(u.first_name, ' ', u.last_name) AS student_name,
                u.email AS student_email,
                u.phone AS student_phone,
                c.course_name
                FROM {$this->table} i
                INNER JOIN students s ON i.student_id = s.student_id
                INNER JOIN users u ON s.user_id = u.user_id
                INNER JOIN courses c ON i.course_id = c.course_id
                WHERE i.due_date < CURDATE() 
                AND i.status IN ('unpaid', 'partially_paid')
                ORDER BY i.due_date ASC";
        
        return $this->db->select($sql);
    }
    
    /**
     * Search invoices by invoice number, student name, or email
     * 
     * @param string $searchTerm Search keyword
     * @return array
     */
    public function search($searchTerm) {
        $params = ["%{$searchTerm}%", "%{$searchTerm}%", "%{$searchTerm}%"];
        
        $sql = "SELECT i.*, 
                CONCAT(u.first_name, ' ', u.last_name) AS student_name,
                u.email AS student_email,
                c.course_name,
                c.course_code
                FROM {$this->table} i
                INNER JOIN students s ON i.student_id = s.student_id
                INNER JOIN users u ON s.user_id = u.user_id
                INNER JOIN courses c ON i.course_id = c.course_id
                WHERE i.invoice_number LIKE ? 
                OR u.first_name LIKE ? 
                OR u.email LIKE ?
                ORDER BY i.created_at DESC";
        
        return $this->db->select($sql, $params);
    }
    
    /**
     * Create new invoice
     * 
     * @param array $data Invoice data
     * @return int|bool Invoice ID or false
     */
    public function createInvoice($data) {
        // Validate required fields
        $errors = $this->validate($data, [
            'student_id' => 'required|numeric',
            'course_id' => 'required|numeric',
            'subtotal' => 'required|numeric',
            'issue_date' => 'required',
            'due_date' => 'required'
        ]);
        
        if (!empty($errors)) {
            return false;
        }
        
        // Generate unique invoice number
        $data['invoice_number'] = $this->generateInvoiceNumber();
        
        // Calculate tax and total
        $taxRate = 10 / 100; // 10% GST
        $data['tax_amount'] = $data['subtotal'] * $taxRate;
        $data['total_amount'] = $data['subtotal'] + $data['tax_amount'];
        $data['amount_paid'] = 0.00;
        $data['balance_due'] = $data['total_amount'];
        $data['status'] = 'unpaid';
        
        return $this->create($data);
    }
    
    /**
     * Generate unique invoice number
     * 
     * @return string
     */
    private function generateInvoiceNumber() {
        $year = date('Y');
        $month = date('m');
        
        // Get last invoice number for this month
        $sql = "SELECT invoice_number 
                FROM {$this->table} 
                WHERE invoice_number LIKE ? 
                ORDER BY invoice_id DESC 
                LIMIT 1";
        
        $prefix = "INV-{$year}{$month}-";
        $result = $this->db->selectOne($sql, [$prefix . '%']);
        
        if ($result) {
            // Extract number and increment
            $lastNumber = (int)substr($result['invoice_number'], -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }
    
    /**
     * Add payment to invoice
     * 
     * @param int $invoiceId Invoice ID
     * @param float $amount Payment amount
     * @param string $paymentMethod Payment method
     * @param string $transactionRef Transaction reference
     * @param int $processedBy User ID who processed the payment
     * @return bool
     */
    public function addPayment($invoiceId, $amount, $paymentMethod, $transactionRef, $processedBy) {
        // Get current invoice
        $invoice = $this->getById($invoiceId);
        if (!$invoice) {
            return false;
        }
        
        // Calculate new amounts
        $newAmountPaid = $invoice['amount_paid'] + $amount;
        $newBalanceDue = $invoice['total_amount'] - $newAmountPaid;
        
        // Determine new status
        $newStatus = 'unpaid';
        if ($newBalanceDue <= 0) {
            $newStatus = 'paid';
            $newBalanceDue = 0;
        } elseif ($newAmountPaid > 0 && $newBalanceDue > 0) {
            $newStatus = 'partially_paid';
        }
        
        // Check if overdue
        if ($newStatus !== 'paid' && strtotime($invoice['due_date']) < time()) {
            $newStatus = 'overdue';
        }
        
        // Start transaction
        $this->db->getConnection()->beginTransaction();
        
        try {
            // Update invoice
            $updateSql = "UPDATE {$this->table} 
                         SET amount_paid = ?, 
                             balance_due = ?, 
                             status = ?
                         WHERE {$this->primaryKey} = ?";
            
            $this->db->update($updateSql, [
                $newAmountPaid,
                $newBalanceDue,
                $newStatus,
                $invoiceId
            ]);
            
            // Insert payment record
            $paymentSql = "INSERT INTO payments 
                          (invoice_id, payment_date, amount, payment_method, transaction_reference, processed_by)
                          VALUES (?, CURDATE(), ?, ?, ?, ?)";
            
            $this->db->insert($paymentSql, [
                $invoiceId,
                $amount,
                $paymentMethod,
                $transactionRef,
                $processedBy
            ]);
            
            $this->db->getConnection()->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->getConnection()->rollBack();
            error_log("Payment Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update invoice status based on due date
     * 
     * @return int Number of invoices updated
     */
    public function updateOverdueInvoices() {
        $sql = "UPDATE {$this->table} 
                SET status = 'overdue'
                WHERE due_date < CURDATE() 
                AND status IN ('unpaid', 'partially_paid')";
        
        return $this->db->update($sql);
    }
    
    /**
     * Get invoice statistics
     * 
     * @return array
     */
    public function getStatistics() {
        $sql = "SELECT 
                COUNT(*) as total_invoices,
                SUM(total_amount) as total_revenue,
                SUM(amount_paid) as total_paid,
                SUM(balance_due) as total_outstanding,
                COUNT(CASE WHEN status = 'paid' THEN 1 END) as paid_count,
                COUNT(CASE WHEN status = 'unpaid' THEN 1 END) as unpaid_count,
                COUNT(CASE WHEN status = 'partially_paid' THEN 1 END) as partially_paid_count,
                COUNT(CASE WHEN status = 'overdue' THEN 1 END) as overdue_count
                FROM {$this->table}";
        
        return $this->db->selectOne($sql);
    }
    
    /**
     * Get recent invoices
     * 
     * @param int $limit Number of invoices to retrieve
     * @return array
     */
    public function getRecentInvoices($limit = 5) {
        $sql = "SELECT i.*, 
                CONCAT(u.first_name, ' ', u.last_name) AS student_name,
                c.course_name
                FROM {$this->table} i
                INNER JOIN students s ON i.student_id = s.student_id
                INNER JOIN users u ON s.user_id = u.user_id
                INNER JOIN courses c ON i.course_id = c.course_id
                ORDER BY i.created_at DESC
                LIMIT ?";
        
        return $this->db->select($sql, [$limit]);
    }
    
    /**
     * Get monthly revenue
     * 
     * @param int $months Number of months to retrieve
     * @return array
     */
    public function getMonthlyRevenue($months = 6) {
        $sql = "SELECT 
                DATE_FORMAT(issue_date, '%Y-%m') as month,
                DATE_FORMAT(issue_date, '%M %Y') as month_name,
                SUM(total_amount) as total_revenue,
                SUM(amount_paid) as total_collected,
                COUNT(*) as invoice_count
                FROM {$this->table}
                WHERE issue_date >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
                GROUP BY DATE_FORMAT(issue_date, '%Y-%m')
                ORDER BY month ASC";
        
        return $this->db->select($sql, [$months]);
    }
    
    /**
     * Get invoices due soon
     * 
     * @param int $days Number of days ahead to check
     * @return array
     */
    public function getInvoicesDueSoon($days = 7) {
        $sql = "SELECT i.*, 
                CONCAT(u.first_name, ' ', u.last_name) AS student_name,
                u.email AS student_email,
                u.phone AS student_phone,
                c.course_name
                FROM {$this->table} i
                INNER JOIN students s ON i.student_id = s.student_id
                INNER JOIN users u ON s.user_id = u.user_id
                INNER JOIN courses c ON i.course_id = c.course_id
                WHERE i.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
                AND i.status IN ('unpaid', 'partially_paid')
                ORDER BY i.due_date ASC";
        
        return $this->db->select($sql, [$days]);
    }
    
    /**
     * Delete invoice
     * 
     * @param int $invoiceId Invoice ID
     * @return bool
     */
    public function deleteInvoice($invoiceId) {
        // Check if invoice has payments
        $paymentsSql = "SELECT COUNT(*) as count FROM payments WHERE invoice_id = ?";
        $result = $this->db->selectOne($paymentsSql, [$invoiceId]);
        
        if ($result && $result['count'] > 0) {
            // Cannot delete invoice with payments
            return false;
        }
        
        // Delete the invoice
        return $this->delete($invoiceId);
    }
}
?>