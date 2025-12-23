<?php

namespace App\Services;

use App\Database;

class StatsService
{
    public static function getSmileCount(): int
    {
        $db = Database::getInstance();
        $result = $db->fetchOne('SELECT smile_count FROM stats WHERE id = 1');
        return $result ? (int) $result['smile_count'] : 0;
    }

    public static function incrementSmileCount(): void
    {
        $db = Database::getInstance();
        $db->query('UPDATE stats SET smile_count = smile_count + 1, last_updated = CURRENT_TIMESTAMP WHERE id = 1');
    }

    public static function getLeaderboard(int $limit = 10): array
    {
        $db = Database::getInstance();
        return $db->fetchAll('
            SELECT email, slug, smile_count
            FROM senders
            WHERE status = "active" AND smile_count > 0
            ORDER BY smile_count DESC, email ASC
            LIMIT ?
        ', [$limit]);
    }
}