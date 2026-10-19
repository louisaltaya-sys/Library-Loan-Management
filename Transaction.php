<?php
require_once __DIR__ . DIRECTORY_SEPARATOR . 'Database.php';

class Transaction
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? (new Database())->getConnection();
    }

    public function recordLoanAction(int $userId, int $loanId, string $action, string $referenceNo): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO `transactions` (user_id, loan_id, action, reference_no, summary)
             VALUES (:user_id, :loan_id, :action, :reference_no, :summary)"
        );
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':loan_id', $loanId, PDO::PARAM_INT);
        $stmt->bindValue(':action', $action);
        $stmt->bindValue(':reference_no', $referenceNo);
        $stmt->bindValue(':summary', ucfirst($action) . ' loan ' . $referenceNo);
        $stmt->execute();
    }

    public function getRecent(int $limit = 10): array
    {
        try {
            $limit = max(1, min($limit, 100));
            $sql = "SELECT t.id, t.action, t.reference_no, t.summary, t.created_at,
                           u.username, u.name AS user_name
                    FROM `transactions` t
                    INNER JOIN users u ON u.id = t.user_id
                    ORDER BY t.created_at DESC, t.id DESC
                    LIMIT :limit";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('getRecentTransactions failed: ' . $e->getMessage());
            return [];
        }
    }
}
