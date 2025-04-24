// Real-time AJAX search/filter
const searchInput = document.getElementById('searchInput');
const onlineInput = document.getElementById('onlineInput');
const roleInput = document.getElementById('roleInput');
const statusInput = document.getElementById('statusInput');
const usersTableBody = document.getElementById('usersTableBody');
const form = document.getElementById('userSearchForm');
let searchTimeout = null;

function fetchUsers() {
    const params = new URLSearchParams();
    params.append('ajax', '1');
    params.append('search', searchInput.value);
    if (onlineInput.checked) params.append('online', '1');
    if (roleInput.value) params.append('role', roleInput.value);
    if (statusInput.value !== "") params.append('status', statusInput.value);

    fetch('admin/ajax_active_users.php?' + params.toString())
        .then(res => res.text())
        .then(html => {
            usersTableBody.innerHTML = html;
        });
}

searchInput.addEventListener('input', function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(fetchUsers, 250);
});
onlineInput.addEventListener('change', fetchUsers);
roleInput.addEventListener('change', fetchUsers);
statusInput.addEventListener('change', fetchUsers);

// Also fetch on form submit (search button)
form.addEventListener('submit', function(e) {
    e.preventDefault();
    fetchUsers();
});

// Inline CRUD logic
function saveRow(tr) {
    const id = tr.getAttribute('data-id');
    const username = tr.querySelector('.username').value.trim();
    const role_id = tr.querySelector('.role').value;
    const online = tr.querySelector('.online').checked ? 1 : 0;
    fetch('admin/ajax_active_users.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
            ajax: 'update_user',
            id: id,
            username: username,
            role_id: role_id,
            online: online,
            active: 1
        })
    }).then(res => res.text()).then(resp => {
        if (resp.trim() === 'success') {
            fetchUsers();
        } else {
            alert('Update failed');
        }
    });
}

// Delete handler for Excel-style table
function deleteRow(tr) {
    const id = tr.getAttribute('data-id');
    if (!confirm('Are you sure you want to delete this user?')) return;
    fetch('admin/ajax_active_users.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
            ajax: 'delete_user',
            id: id
        })
    }).then(res => res.text()).then(resp => {
        if (resp.trim() === 'success') {
            fetchUsers();
        } else {
            alert('Delete failed');
        }
    });
}

// Delegate save/delete buttons
function delegateCrud() {
    document.querySelectorAll('#usersTableBody tr').forEach(tr => {
        const saveBtn = tr.querySelector('.save');
        const deleteBtn = tr.querySelector('.delete');
        if (saveBtn) {
            saveBtn.onclick = function(e) {
                e.stopPropagation();
                saveRow(tr);
            };
        }
        if (deleteBtn) {
            deleteBtn.onclick = function(e) {
                e.stopPropagation();
                deleteRow(tr);
            };
        }
    });
}

// Patch fetchUsers to call delegateCrud after update
const origFetchUsers = fetchUsers;
fetchUsers = function() {
    origFetchUsers();
    setTimeout(delegateCrud, 350);
};

// On page load, fetch all users by default (no filter)
document.addEventListener('DOMContentLoaded', function() {
    fetchUsers();
});

// Always re-delegate after table changes
const observer = new MutationObserver(delegateCrud);
observer.observe(usersTableBody, { childList: true, subtree: true });

// Ensure delegateCrud is called after every AJAX update and on page load
delegateCrud();

// Inline edit logic
function makeEditableRow(tr) {
    const id = tr.getAttribute('data-id');
    const usernameTd = tr.querySelector('.username');
    const roleTd = tr.querySelector('.role');
    const statusTd = tr.querySelector('.status');
    const onlineTd = tr.querySelector('.online');
    const actionsTd = tr.querySelector('td:last-child');

    // Save original values
    const orig = {
        username: usernameTd.textContent.trim(),
        role_id: roleTd.getAttribute('data-role-id'),
        status: statusTd ? statusTd.getAttribute('data-status') : '0',
        role_name: roleTd.textContent.trim()
    };

    // Replace with inputs for username, role, and status
    usernameTd.innerHTML = `<input class="crud-editable" name="username" value="${orig.username}">`;
    // Role select
    let roleOptions = `<option value="">Select</option>`;
    // The following block should be generated server-side and replaced here
    // Example:
    // roleOptions += `<option value="1" selected>Admin</option>`;
    // roleOptions += `<option value="2">User</option>`;
    // etc.
    roleTd.innerHTML = `<select class="crud-editable" name="role">${roleOptions}</select>`;
    // Status select (0/1)
    statusTd.innerHTML = `<select class="crud-editable" name="status">
        <option value="1" ${orig.status == "1" ? "selected" : ""}>Active</option>
        <option value="0" ${orig.status == "0" ? "selected" : ""}>Inactive</option>
    </select>`;
    // Online status remains as plain text (not editable)

    // Actions: Save/Cancel
    actionsTd.innerHTML =
        `<button class="crud-btn save">Save</button>
         <button class="crud-btn cancel">Cancel</button>`;

    // Save handler
    actionsTd.querySelector('.save').onclick = function() {
        const username = usernameTd.querySelector('input').value.trim();
        const role_id = roleTd.querySelector('select').value;
        const status = statusTd.querySelector('select').value;
        // Always set online to false (0) when saving
        fetch('admin/ajax_active_users.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({
                ajax: 'update_user',
                id: id,
                username: username,
                role_id: role_id,
                status: status,
                active: status, // send as both for compatibility
            })
        }).then(res => res.text()).then(resp => {
            if (resp.trim() === 'success') {
                fetchUsers();
            } else {
                alert('Update failed');
            }
        });
    };
    // Cancel handler
    actionsTd.querySelector('.cancel').onclick = function() {
        fetchUsers();
    };
}

// Bulk selection logic
const selectAll = document.getElementById('selectAll');
const bulkActionsBar = document.getElementById('bulkActionsBar');
const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
const bulkStatusSelect = document.getElementById('bulkStatusSelect');
const bulkRoleSelect = document.getElementById('bulkRoleSelect');
const bulkExportBtn = document.getElementById('bulkExportBtn');
const selectedCount = document.getElementById('selectedCount');

function getSelectedRows() {
    return Array.from(document.querySelectorAll('.row-select:checked')).map(cb => cb.closest('tr'));
}

function updateBulkBar() {
    const selected = getSelectedRows();
    selectedCount.textContent = selected.length + " selected";
    bulkActionsBar.style.display = selected.length > 0 ? "flex" : "none";
}

function clearBulkSelection() {
    document.querySelectorAll('.row-select').forEach(cb => cb.checked = false);
    updateBulkBar();
}

// Handle select all
selectAll.addEventListener('change', function() {
    document.querySelectorAll('.row-select').forEach(cb => cb.checked = selectAll.checked);
    updateBulkBar();
});

// Handle row select
usersTableBody.addEventListener('change', function(e) {
    if (e.target.classList.contains('row-select')) {
        updateBulkBar();
        // Uncheck selectAll if any unchecked
        if (!e.target.checked) selectAll.checked = false;
    }
});

// Bulk Delete
bulkDeleteBtn.addEventListener('click', function() {
    const rows = getSelectedRows();
    if (!rows.length) return;
    if (!confirm('Delete selected users?')) return;
    const ids = rows.map(tr => tr.getAttribute('data-id'));
    fetch('admin/ajax_active_users.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
            ajax: 'bulk_delete',
            ids: JSON.stringify(ids)
        })
    }).then(res => res.text()).then(resp => {
        fetchUsers();
        clearBulkSelection();
    });
});

// Bulk Status Change
bulkStatusSelect.addEventListener('change', function() {
    const rows = getSelectedRows();
    const status = bulkStatusSelect.value;
    if (!rows.length || status === "") return;
    const ids = rows.map(tr => tr.getAttribute('data-id'));
    fetch('admin/ajax_active_users.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
            ajax: 'bulk_status',
            ids: JSON.stringify(ids),
            status: status
        })
    }).then(res => res.text()).then(resp => {
        fetchUsers();
        clearBulkSelection();
        bulkStatusSelect.value = "";
    });
});

// Bulk Role Change
bulkRoleSelect.addEventListener('change', function() {
    const rows = getSelectedRows();
    const role_id = bulkRoleSelect.value;
    if (!rows.length || role_id === "") return;
    const ids = rows.map(tr => tr.getAttribute('data-id'));
    fetch('admin/ajax_active_users.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
            ajax: 'bulk_role',
            ids: JSON.stringify(ids),
            role_id: role_id
        })
    }).then(res => res.text()).then(resp => {
        fetchUsers();
        clearBulkSelection();
        bulkRoleSelect.value = "";
    });
});

// Bulk Export
bulkExportBtn.addEventListener('click', function() {
    const rows = getSelectedRows();
    const ids = rows.map(tr => tr.getAttribute('data-id'));
    const params = new URLSearchParams();
    params.append('ajax', 'bulk_export');
    params.append('ids', JSON.stringify(ids));
    // Add current filters
    if (searchInput.value) params.append('search', searchInput.value);
    if (onlineInput.checked) params.append('online', '1');
    if (roleInput.value) params.append('role', roleInput.value);
    if (statusInput.value !== "") params.append('status', statusInput.value);

    window.open('admin/ajax_active_users.php?' + params.toString(), '_blank');
});

document.addEventListener('DOMContentLoaded', function() {
    // Edit button functionality
    document.querySelectorAll('.crud-btn.edit').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var tr = btn.closest('tr');
            if (!tr) return;
            if (tr.classList.contains('editing')) return;
            tr.classList.add('editing');

            var usernameTd = tr.querySelector('.username');
            var roleTd = tr.querySelector('.role');
            var statusTd = tr.querySelector('.status');
            var actionsTd = btn.parentElement;

            var currentUsername = usernameTd.textContent.trim();
            var currentRoleId = roleTd.getAttribute('data-role-id');
            var currentStatus = statusTd.getAttribute('data-status');

            // Build role select
            var roleOptions = '';
            window.activeUserRoles.forEach(function(role) {
                roleOptions += '<option value="' + role.id + '">' + role.name + '</option>';
            });

            usernameTd.innerHTML = '<input type="text" value="' + currentUsername.replace(/"/g, '&quot;') + '" class="edit-username" style="width:120px;">';
            roleTd.innerHTML = '<select class="edit-role">' + roleOptions + '</select>';
            roleTd.querySelector('select').value = currentRoleId;
            statusTd.innerHTML = '<select class="edit-status"><option value="1">Active</option><option value="0">Inactive</option></select>';
            statusTd.querySelector('select').value = currentStatus;

            actionsTd.innerHTML = '<button class="crud-btn save">Save</button> <button class="crud-btn cancel">Cancel</button>';

            actionsTd.querySelector('.save').addEventListener('click', function() {
                var newUsername = usernameTd.querySelector('input').value.trim();
                var newRoleId = roleTd.querySelector('select').value;
                var newStatus = statusTd.querySelector('select').value;
                var userId = tr.getAttribute('data-id');
                var formData = new FormData();
                formData.append('ajax', 'update_user');
                formData.append('id', userId);
                formData.append('username', newUsername);
                formData.append('role_id', newRoleId);
                formData.append('status', newStatus);
                fetch('ajax_active_users.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.text())
                .then(function(response) {
                    if (response.trim() === 'success') {
                        usernameTd.textContent = newUsername;
                        roleTd.textContent = roleTd.querySelector('select').selectedOptions[0].textContent;
                        roleTd.setAttribute('data-role-id', newRoleId);
                        statusTd.textContent = newStatus === '1' ? 'Active' : 'Inactive';
                        statusTd.setAttribute('data-status', newStatus);
                        actionsTd.innerHTML = '<button class="crud-btn edit">Edit</button> <button class="crud-btn delete">Delete</button>';
                        tr.classList.remove('editing');
                        // Re-bind edit/delete
                        setTimeout(function() {
                            tr.querySelector('.crud-btn.edit').addEventListener('click', arguments.callee.caller);
                            tr.querySelector('.crud-btn.delete').addEventListener('click', deleteHandler);
                        }, 0);
                    } else {
                        alert('Failed to update user.');
                    }
                });
            });

            actionsTd.querySelector('.cancel').addEventListener('click', function() {
                window.location.reload();
            });
        });
    });

    // Delete button functionality
    function deleteHandler(e) {
        var btn = e.target.closest('.crud-btn.delete');
        var tr = btn.closest('tr');
        var userId = tr.getAttribute('data-id');
        if (confirm('Are you sure you want to delete this user?')) {
            var formData = new FormData();
            formData.append('ajax', 'delete_user');
            formData.append('id', userId);
            fetch('ajax_active_users.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.text())
            .then(function(response) {
                if (response.trim() === 'success') {
                    tr.remove();
                } else {
                    alert('Failed to delete user.');
                }
            });
        }
    }
    document.querySelectorAll('.crud-btn.delete').forEach(function(btn) {
        btn.addEventListener('click', deleteHandler);
    });

    // All other AJAX (search/filter, bulk actions, export) should also use 'admin/ajax_active_users.php'
    // ...existing code...
});

// Helper: Set roles for JS (add this in a <script> tag in your PHP file)
window.activeUserRoles = [
    // ...populate from PHP...
    // Example: {id: 1, name: "Admin"}, {id: 2, name: "Teacher"}, ...
];
