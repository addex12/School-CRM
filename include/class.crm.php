<?php

class CRM {
    private $id;
    private $customer_name;
    private $email;
    private $phone;
    private $notes;
    private $created;

    public function __construct($id = null) {
        if ($id) {
            $this->load($id);
        }
    }

    public function load($id) {
        $sql = 'SELECT * FROM ' . TABLE_PREFIX . 'crm WHERE id=' . db_input($id);
        if (($res = db_query($sql)) && db_num_rows($res)) {
            $row = db_fetch_array($res);
            $this->id = $row['id'];
            $this->customer_name = $row['customer_name'];
            $this->email = $row['email'];
            $this->phone = $row['phone'];
            $this->notes = $row['notes'];
            $this->created = $row['created'];
        }
    }

    public function save() {
        if ($this->id) {
            $sql = 'UPDATE ' . TABLE_PREFIX . 'crm SET '
                . 'customer_name=' . db_input($this->customer_name) . ', '
                . 'email=' . db_input($this->email) . ', '
                . 'phone=' . db_input($this->phone) . ', '
                . 'notes=' . db_input($this->notes)
                . ' WHERE id=' . db_input($this->id);
        } else {
            $sql = 'INSERT INTO ' . TABLE_PREFIX . 'crm (customer_name, email, phone, notes) VALUES ('
                . db_input($this->customer_name) . ', '
                . db_input($this->email) . ', '
                . db_input($this->phone) . ', '
                . db_input($this->notes) . ')';
        }
        return db_query($sql);
    }

    // Getters and setters for the properties
    // ...existing code...

    public function addContact($name, $email, $phone) {
        // Code to add contact to CRM
        $sql = "INSERT INTO contacts (name, email, phone) VALUES (?, ?, ?)";
        $stmt = db_prepare($sql);
        db_bind_param($stmt, 'sss', $name, $email, $phone);
        db_execute($stmt);
    }

    public function getContacts() {
        // Code to get all contacts
        $sql = "SELECT * FROM contacts";
        $result = db_query($sql);
        $contacts = array();
        while ($row = db_fetch_assoc($result)) {
            $contacts[] = $row;
        }
        return $contacts;
    }

    public function updateContact($id, $name, $email, $phone) {
        // Code to update a contact
        $sql = "UPDATE contacts SET name = ?, email = ?, phone = ? WHERE id = ?";
        $stmt = db_prepare($sql);
        db_bind_param($stmt, 'sssi', $name, $email, $phone, $id);
        db_execute($stmt);
    }

    public function deleteContact($id) {
        // Code to delete a contact
        $sql = "DELETE FROM contacts WHERE id = ?";
        $stmt = db_prepare($sql);
        db_bind_param($stmt, 'i', $id);
        db_execute($stmt);
    }
}

require_once INCLUDE_DIR . 'class.crm.php';

class CRMController {
    public function create($data) {
        $crm = new CRM();
        $crm->customer_name = $data['customer_name'];
        $crm->email = $data['email'];
        $crm->phone = $data['phone'];
        $crm->notes = $data['notes'];
        return $crm->save();
    }

    public function update($id, $data) {
        $crm = new CRM($id);
        $crm->customer_name = $data['customer_name'];
        $crm->email = $data['email'];
        $crm->phone = $data['phone'];
        $crm->notes = $data['notes'];
        return $crm->save();
    }

    public function get($id) {
        return new CRM($id);
    }

    public function getAll() {
        $sql = 'SELECT * FROM ' . TABLE_PREFIX . 'crm';
        $res = db_query($sql);
        $crms = [];
        while ($row = db_fetch_array($res)) {
            $crms[] = new CRM($row['id']);
        }
        return $crms;
    }
}
?>
