// Author: Adugna Gizaw
// Email: gizawadugna@gmail.com
// Phone: +251925582067

<?php
class Attendance {
    private $conn;
    private $table_name = "attendance";

    public $id;
    public $student_id;
    public $date;
    public $status;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Create attendance
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " SET student_id=:student_id, date=:date, status=:status";
        $stmt = $this->conn->prepare($query);

        // sanitize
        $this->student_id = htmlspecialchars(strip_tags($this->student_id));
        $this->date = htmlspecialchars(strip_tags($this->date));
        $this->status = htmlspecialchars(strip_tags($this->status));

        // bind values
        $stmt->bindParam(":student_id", $this->student_id);
        $stmt->bindParam(":date", $this->date);
        $stmt->bindParam(":status", $this->status);

        if ($stmt->execute()) {
            return true;
        }

        return false;
    }

    // Read attendance
    public function read() {
        $query = "SELECT * FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Update attendance
    public function update() {
        $query = "UPDATE " . $this->table_name . " SET student_id = :student_id, date = :date, status = :status WHERE id = :id";
        $stmt = $this->conn->prepare($query);

        // sanitize
        $this->student_id = htmlspecialchars(strip_tags($this->student_id));
        $this->date = htmlspecialchars(strip_tags($this->date));
        $this->status = htmlspecialchars(strip_tags($this->status));
        $this->id = htmlspecialchars(strip_tags($this->id));

        // bind values
        $stmt->bindParam(":student_id", $this->student_id);
        $stmt->bindParam(":date", $this->date);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":id", $this->id);

        if ($stmt->execute()) {
            return true;
        }

        return false;
    }

    // Delete attendance
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);

        // sanitize
        $this->id = htmlspecialchars(strip_tags($this->id));

        // bind id of record to delete
        $stmt->bindParam(":id", $this->id);

        if ($stmt->execute()) {
            return true;
        }

        return false;
    }
}
?>
