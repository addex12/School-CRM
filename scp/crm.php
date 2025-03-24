<?php
require('staff.inc.php');
$nav->setTabActive('crm');
require(STAFFINC_DIR.'header.inc.php');
require_once(INCLUDE_DIR.'class.crm.php');

$crmController = new CRMController();

if ($_POST) {
    if ($_POST['action'] == 'create') {
        $crmController->create($_POST);
    } elseif ($_POST['action'] == 'update') {
        $crmController->update($_POST['id'], $_POST);
    } elseif ($_POST['action'] == 'delete') {
        $crmController->delete($_POST['id']);
    }
}

$contacts = $crmController->getAll();
?>

<div class="crm-container">
    <h2 class="crm-header">CRM</h2>
    <table class="crm-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($contacts as $contact) { ?>
                <tr>
                    <td><?php echo $contact->customer_name; ?></td>
                    <td><?php echo $contact->email; ?></td>
                    <td><?php echo $contact->phone; ?></td>
                    <td>
                        <form action="crm.php" method="post" style="display:inline;">
                            <input type="hidden" name="id" value="<?php echo $contact->id; ?>">
                            <input type="hidden" name="action" value="delete">
                            <button type="submit">Delete</button>
                        </form>
                        <button onclick="editContact(<?php echo $contact->id; ?>)">Edit</button>
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>

    <h3>Add New Contact</h3>
    <form action="crm.php" method="post" class="crm-form">
        <input type="hidden" name="action" value="create">
        <label for="customer_name">Name</label>
        <input type="text" name="customer_name" id="customer_name" required>
        <label for="email">Email</label>
        <input type="email" name="email" id="email" required>
        <label for="phone">Phone</label>
        <input type="tel" name="phone" id="phone" required>
        <label for="notes">Notes</label>
        <input type="text" name="notes" id="notes">
        <button type="submit">Add Contact</button>
    </form>
</div>

<script>
function editContact(id) {
    // Code to handle editing a contact
}
</script>

<?php
require(STAFFINC_DIR.'footer.inc.php');
?>
