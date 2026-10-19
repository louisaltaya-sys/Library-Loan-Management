<?php
require_once __DIR__ . DIRECTORY_SEPARATOR . 'Database.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'Transaction.php';

class BookLoan
{
    private PDO $db;
    private Transaction $transactions;
    private string $table = 'book_loans';

    public ?int $loan_id = null;
    public ?string $reference_no = null;
    public ?string $book_title = null;
    public ?string $book_isbn = null;
    public ?string $borrower_name = null;
    public ?string $borrower_id = null;
    public ?string $loan_date = null;
    public ?string $due_date = null;
    public ?string $return_date = null;
    public ?string $status = null;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
        $this->transactions = new Transaction($this->db);
    }

    public function addBookLoan(int $userId): int|false
    {
        try {
            $this->db->beginTransaction();
            $sql = "INSERT INTO {$this->table}
                        (reference_no, book_title, book_isbn, borrower_name, borrower_id,
                         loan_date, due_date, return_date, status)
                    VALUES
                        (:reference_no, :book_title, :book_isbn, :borrower_name, :borrower_id,
                         :loan_date, :due_date, :return_date, :status)";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':reference_no', $this->reference_no);
            $stmt->bindValue(':book_title', $this->book_title);
            $stmt->bindValue(':book_isbn', $this->book_isbn);
            $stmt->bindValue(':borrower_name', $this->borrower_name);
            $stmt->bindValue(':borrower_id', $this->borrower_id);
            $stmt->bindValue(':loan_date', $this->loan_date);
            $stmt->bindValue(':due_date', $this->due_date);
            $stmt->bindValue(':return_date', $this->return_date);
            $stmt->bindValue(':status', $this->status ?? 'Borrowed');
            $stmt->execute();
            $id = (int) $this->db->lastInsertId();
            $this->transactions->recordLoanAction($userId, $id, 'created', (string) $this->reference_no);
            $this->db->commit();
            return $id;
        } catch (PDOException $e) {
            $this->rollBackTransaction('addBookLoan');
            error_log('addBookLoan failed: ' . $e->getMessage());
            return false;
        }
    }

    public function getBookLoans(): array
    {
        try {
            $stmt = $this->db->query("SELECT * FROM {$this->table} ORDER BY loan_date DESC, loan_id DESC");
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('getBookLoans failed: ' . $e->getMessage());
            return [];
        }
    }

    public function getBookLoanById(int $id): array|false
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE loan_id = :id LIMIT 1");
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log('getBookLoanById failed: ' . $e->getMessage());
            return false;
        }
    }

    public function updateBookLoan(int $id, int $userId): bool
    {
        try {
            $this->db->beginTransaction();
            $existing = $this->db->prepare("SELECT reference_no FROM {$this->table} WHERE loan_id = :id FOR UPDATE");
            $existing->bindValue(':id', $id, PDO::PARAM_INT);
            $existing->execute();
            $oldRecord = $existing->fetch();
            if (!$oldRecord) {
                $this->db->rollBack();
                return false;
            }

            $sql = "UPDATE {$this->table}
                    SET reference_no = :reference_no,
                        book_title = :book_title,
                        book_isbn = :book_isbn,
                        borrower_name = :borrower_name,
                        borrower_id = :borrower_id,
                        loan_date = :loan_date,
                        due_date = :due_date,
                        return_date = :return_date,
                        status = :status
                    WHERE loan_id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':reference_no', $this->reference_no);
            $stmt->bindValue(':book_title', $this->book_title);
            $stmt->bindValue(':book_isbn', $this->book_isbn);
            $stmt->bindValue(':borrower_name', $this->borrower_name);
            $stmt->bindValue(':borrower_id', $this->borrower_id);
            $stmt->bindValue(':loan_date', $this->loan_date);
            $stmt->bindValue(':due_date', $this->due_date);
            $stmt->bindValue(':return_date', $this->return_date);
            $stmt->bindValue(':status', $this->status);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $this->transactions->recordLoanAction($userId, $id, 'updated', (string) $this->reference_no);
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->rollBackTransaction('updateBookLoan');
            error_log('updateBookLoan failed: ' . $e->getMessage());
            return false;
        }
    }

    public function deleteBookLoan(int $id, int $userId): bool
    {
        try {
            $this->db->beginTransaction();
            $existing = $this->db->prepare("SELECT reference_no FROM {$this->table} WHERE loan_id = :id FOR UPDATE");
            $existing->bindValue(':id', $id, PDO::PARAM_INT);
            $existing->execute();
            $record = $existing->fetch();
            if (!$record) {
                $this->db->rollBack();
                return false;
            }

            $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE loan_id = :id");
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $this->transactions->recordLoanAction($userId, $id, 'deleted', (string) $record['reference_no']);
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->rollBackTransaction('deleteBookLoan');
            error_log('deleteBookLoan failed: ' . $e->getMessage());
            return false;
        }
    }

    private function rollBackTransaction(string $operation): void
    {
        if (!$this->db->inTransaction()) {
            return;
        }

        try {
            $this->db->rollBack();
        } catch (PDOException $e) {
            error_log("{$operation} rollback failed: " . $e->getMessage());
        }
    }

    public function searchBookLoans(string $keyword): array
    {
        try {
            $sql = "SELECT * FROM {$this->table}
                    WHERE reference_no LIKE :kw
                       OR book_title LIKE :kw
                       OR book_isbn LIKE :kw
                       OR borrower_name LIKE :kw
                       OR borrower_id LIKE :kw
                       OR status LIKE :kw
                    ORDER BY loan_date DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':kw', "%{$keyword}%", PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('searchBookLoans failed: ' . $e->getMessage());
            return [];
        }
    }

    public function getCounts(): array
    {
        try {
            $sql = "SELECT COUNT(*) AS total,
                           SUM(CASE WHEN status = 'Borrowed' THEN 1 ELSE 0 END) AS borrowed,
                           SUM(CASE WHEN status = 'Overdue' THEN 1 ELSE 0 END) AS overdue,
                           SUM(CASE WHEN status = 'Returned' THEN 1 ELSE 0 END) AS returned
                    FROM {$this->table}";
            $counts = $this->db->query($sql)->fetch();
            return [
                'total' => (int) ($counts['total'] ?? 0),
                'borrowed' => (int) ($counts['borrowed'] ?? 0),
                'overdue' => (int) ($counts['overdue'] ?? 0),
                'returned' => (int) ($counts['returned'] ?? 0),
            ];
        } catch (PDOException $e) {
            error_log('getCounts failed: ' . $e->getMessage());
            return ['total' => 0, 'borrowed' => 0, 'overdue' => 0, 'returned' => 0];
        }
    }

    public function getRecentTransactions(int $limit = 10): array
    {
        return $this->transactions->getRecent($limit);
    }
}
