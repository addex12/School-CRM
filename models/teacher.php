// Author: Adugna Gizaw
// Email: gizawadugna@gmail.com
// Phone: +251925582067

<?php
class Teacher {
    private $conn;
    private $table_name = "teachers";

    public $id;
    public $name;
    public $email;
    public $subject;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Create teacher
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " SET name=:name, email=:email, subject=:subject";
        $stmt = $this->conn->prepare($query);

        // sanitize
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->subject = htmlspecialchars(strip_tags($this->subject));

        // bind values
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":subject", $this->subject);

        if ($stmt->execute()) {
            return true;
        }

        return false;
    }

    // Read teachers
    public function read() {
        $query = "SELECT * FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Update teacher
    public function update() {
        $query = "UPDATE " . $this->table_name . " SET name = :name, email = :email, subject = :subject WHERE id = :id";
        $stmt = $this->conn->prepare($query);

        // sanitize
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->subject = htmlspecialchars(strip_tags($this->subject));
        $this->id = htmlspecialchars(strip_tags($this->id));

        // bind values
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":subject", $this->subject);
        $stmt->bindParam(":id", $this->id);

        if ($stmt->execute()) {
            return true;
        }

        return false;
    }

    // Delete teacher
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
