<?php
class Survey {
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_PENDING = 'pending';
    const STATUS_SUSPENDED = 'suspended';
    const STATUS_ARCHIVED = 'archived';
    const STATUS_PENDING_REVIEW = 'pending_review';
    const STATUS_SUSPENDED_REVIEW = 'suspended_review';

    public static function model() {
        return new self();
    }

    public static function getStatuses() {
        global $pdo;
        $stmt = $pdo->query("SELECT * FROM survey_statuses ORDER BY id");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findByPk($id) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM surveys WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }
}
