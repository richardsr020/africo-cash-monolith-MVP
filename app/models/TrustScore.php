<?php

declare(strict_types=1);

final class TrustScore
{
    private const SILVER_MIN_TX = 5;
    private const SILVER_MIN_VOLUME_CDF = 20000000;
    private const SILVER_MIN_VOLUME_USD = 50000;
    private const SILVER_MIN_RATING_AVG = 3.50;
    private const SILVER_MIN_RATING_COUNT = 3;

    private const GOLD_MIN_TX = 20;
    private const GOLD_MIN_VOLUME_CDF = 100000000;
    private const GOLD_MIN_VOLUME_USD = 250000;
    private const GOLD_MIN_RATING_AVG = 4.50;
    private const GOLD_MIN_RATING_COUNT = 10;

    public function __construct(private PDO $db)
    {
    }

    public function getForUser(int $userId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM user_trust_scores WHERE user_id = :uid');
        $stmt->execute([':uid' => $userId]);
        $row = $stmt->fetch();

        if (!$row) {
            return $this->recalculate($userId);
        }

        return [
            'badge' => (string) ($row['badge'] ?? 'none'),
            'trust_score' => (int) ($row['trust_score'] ?? 0),
            'volume_6m_cdf' => (int) ($row['volume_6m_cdf'] ?? 0),
            'volume_6m_usd' => (int) ($row['volume_6m_usd'] ?? 0),
            'tx_count_6m' => (int) ($row['tx_count_6m'] ?? 0),
            'rating_avg' => (float) ($row['rating_avg'] ?? 0),
            'rating_count' => (int) ($row['rating_count'] ?? 0),
            'badge_awarded_at' => $row['badge_awarded_at'] ?? null,
            'progression' => $this->computeProgression($row),
        ];
    }

    public function recalculate(int $userId): array
    {
        $sixMonthsAgo = date('Y-m-d H:i:s', strtotime('-6 months'));

        $volumeStmt = $this->db->prepare(
            "SELECT currency, COALESCE(SUM(total_amount), 0) AS volume, COUNT(*) AS tx_count "
            . "FROM transactions "
            . "WHERE user_id = :uid AND status = 'completed' AND created_at >= :since "
            . "GROUP BY currency"
        );
        $volumeStmt->execute([':uid' => $userId, ':since' => $sixMonthsAgo]);
        $volumeRows = $volumeStmt->fetchAll();

        $volumeCdf = 0;
        $volumeUsd = 0;
        $txCount = 0;
        foreach ($volumeRows as $row) {
            $currency = (string) ($row['currency'] ?? '');
            $vol = (int) ($row['volume'] ?? 0);
            $txCount += (int) ($row['tx_count'] ?? 0);
            if ($currency === 'CDF') {
                $volumeCdf = $vol;
            } elseif ($currency === 'USD') {
                $volumeUsd = $vol;
            }
        }

        $ratingStmt = $this->db->prepare(
            "SELECT AVG(rating) AS avg_rating, COUNT(*) AS rating_count "
            . "FROM user_ratings WHERE rated_user_id = :uid"
        );
        $ratingStmt->execute([':uid' => $userId]);
        $ratingRow = $ratingStmt->fetch();
        $ratingAvg = $ratingRow ? (float) ($ratingRow['avg_rating'] ?? 0) : 0.0;
        $ratingCount = $ratingRow ? (int) ($ratingRow['rating_count'] ?? 0) : 0;

        $badge = $this->calculateBadge($txCount, $volumeCdf, $volumeUsd, $ratingAvg, $ratingCount);
        $trustScore = $this->computeTrustScore($txCount, $volumeCdf, $volumeUsd, $ratingAvg, $ratingCount);

        $existingStmt = $this->db->prepare('SELECT badge FROM user_trust_scores WHERE user_id = :uid');
        $existingStmt->execute([':uid' => $userId]);
        $existing = $existingStmt->fetch();

        if ($existing) {
            $updateStmt = $this->db->prepare(
                'UPDATE user_trust_scores SET badge = :badge, trust_score = :score, '
                . 'volume_6m_cdf = :vcdf, volume_6m_usd = :vusd, tx_count_6m = :tx, '
                . 'rating_avg = :ravg, rating_count = :rcount, '
                . 'badge_awarded_at = CASE WHEN :badge != badge THEN CURRENT_TIMESTAMP ELSE badge_awarded_at END, '
                . 'updated_at = CURRENT_TIMESTAMP '
                . 'WHERE user_id = :uid'
            );
        } else {
            $updateStmt = $this->db->prepare(
                'INSERT INTO user_trust_scores '
                . '(user_id, badge, trust_score, volume_6m_cdf, volume_6m_usd, tx_count_6m, '
                . 'rating_avg, rating_count, badge_awarded_at, created_at, updated_at) '
                . 'VALUES (:uid, :badge, :score, :vcdf, :vusd, :tx, :ravg, :rcount, '
                . 'CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
            );
        }

        $updateStmt->execute([
            ':uid' => $userId,
            ':badge' => $badge,
            ':score' => $trustScore,
            ':vcdf' => $volumeCdf,
            ':vusd' => $volumeUsd,
            ':tx' => $txCount,
            ':ravg' => $ratingAvg,
            ':rcount' => $ratingCount,
        ]);

        return $this->getForUser($userId);
    }

    public function getAllWithBadge(?string $badge = null): array
    {
        $sql = 'SELECT u.id, u.full_name, u.afric_number, u.email, '
            . 'COALESCE(ts.badge, "none") AS badge, '
            . 'COALESCE(ts.trust_score, 0) AS trust_score, '
            . 'COALESCE(ts.volume_6m_cdf, 0) AS volume_6m_cdf, '
            . 'COALESCE(ts.volume_6m_usd, 0) AS volume_6m_usd, '
            . 'COALESCE(ts.tx_count_6m, 0) AS tx_count_6m, '
            . 'COALESCE(ts.rating_avg, 0) AS rating_avg, '
            . 'COALESCE(ts.rating_count, 0) AS rating_count '
            . 'FROM users u '
            . 'LEFT JOIN user_trust_scores ts ON ts.user_id = u.id '
            . 'WHERE u.is_active = 1';
        $params = [];
        if ($badge !== null && $badge !== '') {
            $sql .= ' AND COALESCE(ts.badge, "none") = :badge';
            $params[':badge'] = $badge;
        }
        $sql .= ' ORDER BY ts.trust_score DESC, u.full_name ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return array_map(static function (array $row): array {
            return [
                'id' => (int) $row['id'],
                'full_name' => $row['full_name'],
                'afric_number' => $row['afric_number'],
                'email' => $row['email'],
                'badge' => $row['badge'],
                'trust_score' => (int) ($row['trust_score'] ?? 0),
                'volume_6m_cdf' => (int) ($row['volume_6m_cdf'] ?? 0),
                'volume_6m_usd' => (int) ($row['volume_6m_usd'] ?? 0),
                'tx_count_6m' => (int) ($row['tx_count_6m'] ?? 0),
                'rating_avg' => (float) ($row['rating_avg'] ?? 0),
                'rating_count' => (int) ($row['rating_count'] ?? 0),
            ];
        }, $stmt->fetchAll());
    }

    private function calculateBadge(int $txCount, int $volumeCdf, int $volumeUsd, float $ratingAvg, int $ratingCount): string
    {
        $silverVolume = $volumeCdf >= self::SILVER_MIN_VOLUME_CDF || $volumeUsd >= self::SILVER_MIN_VOLUME_USD;
        $goldVolume = $volumeCdf >= self::GOLD_MIN_VOLUME_CDF || $volumeUsd >= self::GOLD_MIN_VOLUME_USD;

        $isGold = $txCount >= self::GOLD_MIN_TX
            && $goldVolume
            && $ratingAvg >= self::GOLD_MIN_RATING_AVG
            && $ratingCount >= self::GOLD_MIN_RATING_COUNT;

        if ($isGold) {
            return 'gold';
        }

        $isSilver = $txCount >= self::SILVER_MIN_TX
            && $silverVolume
            && $ratingAvg >= self::SILVER_MIN_RATING_AVG
            && $ratingCount >= self::SILVER_MIN_RATING_COUNT;

        if ($isSilver) {
            return 'silver';
        }

        return 'none';
    }

    private function computeTrustScore(int $txCount, int $volumeCdf, int $volumeUsd, float $ratingAvg, int $ratingCount): int
    {
        $volumeScore = min(
            300,
            (int) bcdiv(bcmul((string) $volumeCdf, '100', 0), '5000000', 0)
            + (int) bcdiv(bcmul((string) $volumeUsd, '100', 0), '10000', 0)
        );
        $txScore = min(250, $txCount * 10);
        $ratingScore = $ratingCount > 0
            ? (int) bcdiv(bcmul(sprintf('%.10f', $ratingAvg), '200', 2), '5', 0)
            : 0;
        $participationScore = min(250, $ratingCount * 15);

        return min(1000, $volumeScore + $txScore + $ratingScore + $participationScore);
    }

    private function computeProgression(array $row): array
    {
        $badge = (string) ($row['badge'] ?? 'none');
        $txCount = (int) ($row['tx_count_6m'] ?? 0);
        $volumeCdf = (int) ($row['volume_6m_cdf'] ?? 0);
        $volumeUsd = (int) ($row['volume_6m_usd'] ?? 0);
        $ratingAvg = (float) ($row['rating_avg'] ?? 0);
        $ratingCount = (int) ($row['rating_count'] ?? 0);

        if ($badge === 'gold') {
            return ['next_badge' => null, 'criteria' => []];
        }

        $target = $badge === 'none' ? 'silver' : 'gold';
        $minTx = $target === 'silver' ? self::SILVER_MIN_TX : self::GOLD_MIN_TX;
        $minVolCdf = $target === 'silver' ? self::SILVER_MIN_VOLUME_CDF : self::GOLD_MIN_VOLUME_CDF;
        $minVolUsd = $target === 'silver' ? self::SILVER_MIN_VOLUME_USD : self::GOLD_MIN_VOLUME_USD;
        $minRatingAvg = $target === 'silver' ? self::SILVER_MIN_RATING_AVG : self::GOLD_MIN_RATING_AVG;
        $minRatingCount = $target === 'silver' ? self::SILVER_MIN_RATING_COUNT : self::GOLD_MIN_RATING_COUNT;

        return [
            'next_badge' => $target,
            'criteria' => [
                'transactions' => [
                    'label' => 'Transactions (6 mois)',
                    'current' => $txCount,
                    'required' => $minTx,
                    'met' => $txCount >= $minTx,
                ],
                'volume' => [
                    'label' => 'Volume (CDF ou USD)',
                    'current_cdf' => $volumeCdf,
                    'current_usd' => $volumeUsd,
                    'required_cdf' => $minVolCdf,
                    'required_usd' => $minVolUsd,
                    'met' => $volumeCdf >= $minVolCdf || $volumeUsd >= $minVolUsd,
                ],
                'rating_avg' => [
                    'label' => 'Moyenne évaluations',
                    'current' => $ratingAvg,
                    'required' => $minRatingAvg,
                    'met' => $ratingAvg >= $minRatingAvg,
                ],
                'rating_count' => [
                    'label' => 'Évaluations reçues',
                    'current' => $ratingCount,
                    'required' => $minRatingCount,
                    'met' => $ratingCount >= $minRatingCount,
                ],
            ],
        ];
    }
}
