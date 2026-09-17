<?php
namespace App\models;

use Core\Database;
use PDO;

class Notice
{
    public static function getForPage(string $page = 'checkout'): array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT * FROM site_notices 
            WHERE (page = ? OR page = 'global') AND is_active = 1 
            ORDER BY priority DESC, id DESC
        ");
        $stmt->execute([$page]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}