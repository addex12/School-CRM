<?php
class Survey {
    public static function model() {
        return new self();
    }

    public static function getStatuses() {
        global $pdo;
        $stmt = $pdo->query("SELECT status, label, icon FROM survey_statuses ORDER BY id");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findByPk($id) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM surveys WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_OBJ) ?: false; // Return false if no survey is found
    }
}
