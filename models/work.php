<!-- Developer: Adugna Gizaw
Email: gizawadugna@gmail.com
Phone: +251925582067 -->

<?php
class Work {
    private $conn;
    private $table_name = "work";

    public $id;
    public $staff_id;
    public $task;
    public $status;
    public $date;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Create work
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " SET staff_id=:staff_id, task=:task, status=:status, date=:date";
        $stmt = $this->conn->prepare($query);

        // sanitize
        $this->staff_id = htmlspecialchars(strip_tags($this->staff_id));
        $this->task = htmlspecialchars(strip_tags($this->task));
        $this->status = htmlspecialchars(strip_tags($this->status));
        $this->date = htmlspecialchars(strip_tags($this->date));

        // bind values
        $stmt->bindParam(":staff_id", $this->staff_id);
        $stmt->bindParam(":task", $this->task);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":date", $this->date);

        if ($stmt->execute()) {
            return true;
        }

        return false;
    }

    // Read work
    public function read() {
        $query = "SELECT * FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Update work
    public function update() {
        $query = "UPDATE " . $this->table_name . " SET staff_id = :staff_id, task = :task, status = :status, date = :date WHERE id = :id";
        $stmt = $this->conn->prepare($query);

        // sanitize
        $this->staff_id = htmlspecialchars(strip_tags($this->staff_id));
        $this->task = htmlspecialchars(strip_tags($this->task));
        $this->status = htmlspecialchars(strip_tags($this->status));
        $this->date = htmlspecialchars(strip_tags($this->date));
        $this->id = htmlspecialchars(strip_tags($this->id));

        // bind values
        $stmt->bindParam(":staff_id", $this->staff_id);
        $stmt->bindParam(":task", $this->task);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":date", $this->date);
        $stmt->bindParam(":id", $this->id);

        if ($stmt->execute()) {
            return true;
        }

        return false;
    }

    // Delete work
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
