<?php
require_once __DIR__ . DIRECTORY_SEPARATOR . 'Database.php';

class AppSession
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? (new Database())->getConnection();
    }

    public function startForUser(int $userId, string $sessionId): bool
    {
        try {
            $stmt = $this->db->prepare(
                'INSERT INTO sessions (user_id, session_token_hash, expires_at)
                 VALUES (:user_id, :session_token_hash, DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 1 DAY))'
            );
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':session_token_hash', hash('sha256', $sessionId));
            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            error_log('Session record creation failed: ' . $e->getMessage());
            return false;
        }
    }

    public function touch(int $userId, string $sessionId): bool
    {
        try {
            $tokenHash = hash('sha256', $sessionId);
            $stmt = $this->db->prepare(
                'UPDATE sessions
                 SET last_activity = CURRENT_TIMESTAMP,
                     expires_at = DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 1 DAY)
                 WHERE user_id = :user_id
                   AND session_token_hash = :session_token_hash
                   AND ended_at IS NULL
                   AND expires_at > CURRENT_TIMESTAMP'
            );
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':session_token_hash', $tokenHash);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                return true;
            }

            $check = $this->db->prepare(
                'SELECT 1 FROM sessions
                 WHERE user_id = :user_id
                   AND session_token_hash = :session_token_hash
                   AND ended_at IS NULL
                   AND expires_at > CURRENT_TIMESTAMP
                 LIMIT 1'
            );
            $check->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $check->bindValue(':session_token_hash', $tokenHash);
            $check->execute();
            return $check->fetchColumn() !== false;
        } catch (PDOException $e) {
            error_log('Session activity update failed: ' . $e->getMessage());
            return false;
        }
    }

    public function endForUser(int $userId, string $sessionId): bool
    {
        try {
            $stmt = $this->db->prepare(
                'UPDATE sessions
                 SET ended_at = CURRENT_TIMESTAMP
                 WHERE user_id = :user_id
                   AND session_token_hash = :session_token_hash
                   AND ended_at IS NULL'
            );
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':session_token_hash', hash('sha256', $sessionId));
            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            error_log('Session end record failed: ' . $e->getMessage());
            return false;
        }
    }

    public function getRecent(int $limit = 10): array
    {
        try {
            $limit = max(1, min($limit, 100));
            $stmt = $this->db->prepare(
                'SELECT s.id, s.created_at, s.last_activity, s.expires_at, s.ended_at,
                        u.username, u.name AS user_name
                 FROM sessions s
                 INNER JOIN users u ON u.id = s.user_id
                 ORDER BY s.created_at DESC, s.id DESC
                 LIMIT :limit'
            );
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('getRecentSessions failed: ' . $e->getMessage());
            return [];
        }
    }
}
