<?php

declare(strict_types=1);

final class UserRating
{
    public function __construct(private PDO $db)
    {
    }

    public function rate(int $raterId, int $ratedUserId, string $transactionReference, int $rating, ?string $comment = null): array
    {
        $stmt = $this->db->prepare(
            'INSERT INTO user_ratings (rater_id, rated_user_id, transaction_reference, rating, comment, created_at) '
            . 'VALUES (:rater, :rated, :ref, :rating, :comment, CURRENT_TIMESTAMP)'
        );
        $stmt->execute([
            ':rater' => $raterId,
            ':rated' => $ratedUserId,
            ':ref' => $transactionReference,
            ':rating' => $rating,
            ':comment' => $comment,
        ]);

        $id = (int) $this->db->lastInsertId();

        return [
            'id' => $id,
            'rater_id' => $raterId,
            'rated_user_id' => $ratedUserId,
            'transaction_reference' => $transactionReference,
            'rating' => $rating,
            'comment' => $comment,
        ];
    }

    public function getForUser(int $userId, int $limit = 20): array
    {
        $stmt = $this->db->prepare(
            'SELECT ur.id, ur.rating, ur.comment, ur.created_at, '
            . 'ur.transaction_reference, '
            . 'rater.full_name AS rater_name, rater.afric_number AS rater_number '
            . 'FROM user_ratings ur '
            . 'JOIN users rater ON rater.id = ur.rater_id '
            . 'WHERE ur.rated_user_id = :uid '
            . 'ORDER BY ur.created_at DESC LIMIT :lim'
        );
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return array_map(static function (array $row): array {
            return [
                'id' => (int) $row['id'],
                'rating' => (int) $row['rating'],
                'comment' => $row['comment'],
                'created_at' => $row['created_at'],
                'transaction_reference' => $row['transaction_reference'],
                'rater_name' => $row['rater_name'],
                'rater_number' => $row['rater_number'],
            ];
        }, $stmt->fetchAll());
    }

    public function getAverageForUser(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT AVG(rating) AS avg_rating, COUNT(*) AS rating_count '
            . 'FROM user_ratings WHERE rated_user_id = :uid'
        );
        $stmt->execute([':uid' => $userId]);
        $row = $stmt->fetch();

        return [
            'avg_rating' => $row ? (float) ($row['avg_rating'] ?? 0) : 0.0,
            'rating_count' => $row ? (int) ($row['rating_count'] ?? 0) : 0,
        ];
    }

    public function hasRated(int $raterId, string $transactionReference): bool
    {
        $stmt = $this->db->prepare(
            'SELECT 1 FROM user_ratings WHERE rater_id = :rater AND transaction_reference = :ref LIMIT 1'
        );
        $stmt->execute([':rater' => $raterId, ':ref' => $transactionReference]);
        return (bool) $stmt->fetchColumn();
    }
}
