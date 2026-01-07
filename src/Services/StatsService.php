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

    public static function getCompanyFromEmail(string $email): ?string
    {
        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return null;
        }

        $domain = strtolower($parts[1]);

        // Filter out common free email providers
        $freeProviders = ['gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com', 'aol.com', 'icloud.com'];
        if (in_array($domain, $freeProviders)) {
            return null;
        }

        return $domain;
    }

    public static function getTotalCompanies(): int
    {
        $db = Database::getInstance();

        $result = $db->fetchOne('
            SELECT COUNT(DISTINCT LOWER(SUBSTR(email, INSTR(email, "@") + 1))) as count
            FROM senders
            WHERE INSTR(email, "@") > 0
            AND LOWER(SUBSTR(email, INSTR(email, "@") + 1)) NOT IN ("gmail.com", "yahoo.com", "hotmail.com", "outlook.com", "aol.com", "icloud.com")
        ');

        return $result ? (int) $result['count'] : 0;
    }

    public static function getCompanyStats(string $company): array
    {
        $db = Database::getInstance();

        // Get total smiles from this company
        $result = $db->fetchOne('
            SELECT COUNT(m.id) as smile_count
            FROM messages m
            JOIN senders s ON m.sender_id = s.id
            WHERE m.read_at IS NOT NULL
            AND LOWER(SUBSTR(s.email, INSTR(s.email, "@") + 1)) = LOWER(?)
        ', [$company]);

        $smileCount = $result ? (int) $result['smile_count'] : 0;

        // Get top senders from this company
        $topSenders = self::getTopSendersByCompany($company, 10);

        return [
            'company' => $company,
            'smile_count' => $smileCount,
            'top_senders' => $topSenders
        ];
    }

    public static function getTopSendersByCompany(string $company, int $limit = 10): array
    {
        $db = Database::getInstance();

        return $db->fetchAll('
            SELECT
                s.id,
                s.name,
                s.email,
                s.avatar,
                COUNT(m.id) as smile_count
            FROM senders s
            LEFT JOIN messages m ON s.id = m.sender_id AND m.read_at IS NOT NULL
            WHERE LOWER(SUBSTR(s.email, INSTR(s.email, "@") + 1)) = LOWER(?)
            GROUP BY s.id
            HAVING smile_count > 0
            ORDER BY smile_count DESC, s.name ASC
            LIMIT ?
        ', [$company, $limit]);
    }

    public static function getTopCompanies(int $limit = 10): array
    {
        $db = Database::getInstance();

        $companies = $db->fetchAll('
            SELECT
                LOWER(SUBSTR(s.email, INSTR(s.email, "@") + 1)) as company,
                COUNT(m.id) as smile_count
            FROM senders s
            JOIN messages m ON s.id = m.sender_id
            WHERE m.read_at IS NOT NULL
            AND LOWER(SUBSTR(s.email, INSTR(s.email, "@") + 1)) NOT IN ("gmail.com", "yahoo.com", "hotmail.com", "outlook.com", "aol.com", "icloud.com")
            GROUP BY company
            ORDER BY smile_count DESC
            LIMIT ?
        ', [$limit]);

        return $companies;
    }

    public static function getTopSendersGlobal(int $limit = 10): array
    {
        $db = Database::getInstance();

        return $db->fetchAll('
            SELECT
                s.id,
                s.name,
                s.email,
                s.avatar,
                COUNT(m.id) as smile_count
            FROM senders s
            LEFT JOIN messages m ON s.id = m.sender_id AND m.read_at IS NOT NULL
            GROUP BY s.id
            HAVING smile_count > 0
            ORDER BY smile_count DESC, s.name ASC
            LIMIT ?
        ', [$limit]);
    }

    public static function getSenderSmileCount(int $senderId): int
    {
        $db = Database::getInstance();

        $result = $db->fetchOne('
            SELECT COUNT(*) as count
            FROM messages
            WHERE sender_id = ? AND read_at IS NOT NULL
        ', [$senderId]);

        return $result ? (int) $result['count'] : 0;
    }
}
