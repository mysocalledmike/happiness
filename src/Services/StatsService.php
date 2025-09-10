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
}