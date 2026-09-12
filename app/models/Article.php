<?php
namespace App\models;

use Core\Database;
use PDO;

class Article
{
    public static function getAll($status = 'published', $category = null, $limit = null)
    {
        $db = Database::getInstance();
        $sql = "SELECT * FROM articles WHERE status = ?";
        $params = [$status];

        if (!empty($category) && $category !== 'all') {
            $sql .= " AND category = ?";
            $params[] = $category;
        }

        $sql .= " ORDER BY created_at DESC";

        if ($limit !== null) {
            $sql .= " LIMIT " . (int) $limit;
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function findById($id)
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM articles WHERE id = ? AND status = 'published' LIMIT 1");
        $stmt->execute([(int) $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function findBySlug($slug)
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM articles WHERE slug = ? AND status = 'published' LIMIT 1");
        $stmt->execute([urldecode($slug)]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function getRelated($categoryId, $excludeId, $limit = 2)
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT id, title, slug, summary, reading_time, icon, cover_image, category_label 
            FROM articles 
            WHERE category = ? AND id != ? AND status = 'published' 
            ORDER BY id DESC 
            LIMIT " . (int) $limit
        );
        $stmt->execute([$categoryId, (int) $excludeId]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // در صورت نبودن مقالات هم‌دسته، جدیدترین‌ها را بازمی‌گرداند
        if (count($results) < $limit) {
            $remaining = $limit - count($results);
            $stmt = $db->prepare("
                SELECT id, title, slug, summary, reading_time, icon, cover_image, category_label 
                FROM articles 
                WHERE id != ? AND status = 'published' 
                ORDER BY id DESC 
                LIMIT " . (int) $remaining
            );
            $stmt->execute([(int) $excludeId]);
            $results = array_merge($results, $stmt->fetchAll(PDO::FETCH_ASSOC));
        }

        return $results;
    }

    public static function incrementViews($id)
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("UPDATE articles SET views = views + 1 WHERE id = ?");
        return $stmt->execute([(int) $id]);
    }

    public static function getPopular($limit = 3)
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT id, title, slug, views FROM articles WHERE status = 'published' ORDER BY views DESC, id DESC LIMIT " . (int) $limit);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}