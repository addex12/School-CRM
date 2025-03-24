<?php

require_once INCLUDE_DIR . 'class.crm.php';
require_once INCLUDE_DIR . 'class.crmcontroller.php';

$controller = new CRMController();

if ($_POST) {
    if ($_POST['id']) {
        $controller->update($_POST['id'], $_POST);
    } else {
        $controller->create($_POST);
    }
}

$crms = $controller->getAll();

?>

<h2>CRM</h2>

<form method="post" action="crm.php">
    <input type="hidden" name="id" value="">
    <label for="customer_name">Customer Name:</label>
    <input type="text" name="customer_name" id="customer_name" required>
    <label for="email">Email:</label>
    <input type="email" name="email" id="email" required>
    <label for="phone">Phone:</label>
    <input type="text" name="phone" id="phone">
    <label for="notes">Notes:</label>
    <textarea name="notes" id="notes"></textarea>
    <button type="submit">Save</button>
</form>

<h3>Customer List</h3>
<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Customer Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Notes</th>
            <th>Created</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($crms as $crm) { ?>
            <tr>
                <td><?php echo $crm->id; ?></td>
                <td><?php echo $crm->customer_name; ?></td>
                <td><?php echo $crm->email; ?></td>
                <td><?php echo $crm->phone; ?></td>
                <td><?php echo $crm->notes; ?></td>
                <td><?php echo $crm->created; ?></td>
            </tr>
        <?php } ?>
    </tbody>
</table>
?>
