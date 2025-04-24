document.addEventListener('DOMContentLoaded', function() {
    function fetchUsersTable() {
        const search = document.getElementById('searchInput').value;
        const online = document.getElementById('onlineInput').checked ? 1 : '';
        const role = document.getElementById('roleInput').value;
        const status = document.getElementById('statusInput').value;
        const params = new URLSearchParams({
            ajax: 1,
            search: search,
            online: online,
            role: role,
            status: status
        });
        fetch('ajax_active_users.php?' + params.toString())
            .then(res => res.text())
            .then(html => {
                document.getElementById('usersTableBody').innerHTML = html;
                bindRowActions();
            });
    }

    function getSelectedUserIds() {
        return Array.from(document.querySelectorAll('.row-select:checked'))
            .map(cb => cb.closest('tr').getAttribute('data-id'));
    }

    function bindRowActions() {
        document.querySelectorAll('.crud-btn.edit').forEach(function(btn) {
            btn.onclick = function() {
                var tr = btn.closest('tr');
                if (!tr || tr.classList.contains('editing')) return;
                tr.classList.add('editing');
                var usernameTd = tr.querySelector('.username');
                var roleTd = tr.querySelector('.role');
                var statusTd = tr.querySelector('.status');
                var actionsTd = btn.parentElement;
                var currentUsername = usernameTd.textContent.trim();
                var currentRoleId = roleTd.getAttribute('data-role-id');
                var currentStatus = statusTd.getAttribute('data-status');
                var roleOptions = document.getElementById('roleInput').innerHTML;
                usernameTd.innerHTML = '<input type="text" value="' + currentUsername.replace(/"/g, '&quot;') + '" class="edit-username" style="width:120px;">';
                roleTd.innerHTML = '<select class="edit-role">' + roleOptions + '</select>';
                roleTd.querySelector('select').value = currentRoleId;
                statusTd.innerHTML = '<select class="edit-status"><option value="1">Active</option><option value="0">Inactive</option></select>';
                statusTd.querySelector('select').value = currentStatus;
                actionsTd.innerHTML = '<button class="crud-btn save" type="button">Save</button> <button class="crud-btn cancel" type="button">Cancel</button>';

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
                            fetchUsersTable();
                        } else {
                            alert('Failed to update user.');
                        }
                    });
                });
                actionsTd.querySelector('.cancel').addEventListener('click', function() {
                    fetchUsersTable();
                });
            };
        });
        document.querySelectorAll('.crud-btn.delete').forEach(function(btn) {
            btn.onclick = function() {
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
                            fetchUsersTable();
                        } else {
                            alert('Failed to delete user.');
                        }
                    });
                }
            };
        });
    }

    // Real-time search: trigger fetch on input, and prevent form submit from interfering
    const searchInput = document.getElementById('searchInput');
    const userSearchForm = document.getElementById('userSearchForm');

    let searchTimeout;
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(fetchUsersTable, 200);
    });

    userSearchForm.addEventListener('submit', function(e) {
        e.preventDefault();
        fetchUsersTable();
    });

    document.querySelector('.refresh-btn').addEventListener('click', function(e) {
        e.preventDefault();
        fetchUsersTable();
    });

    document.getElementById('bulkDeleteBtn').addEventListener('click', function() {
        const ids = getSelectedUserIds();
        if (!ids.length) return alert('No users selected.');
        if (!confirm('Delete selected users?')) return;
        fetch('ajax_active_users.php', {
            method: 'POST',
            body: new URLSearchParams({ ajax: 'bulk_delete', ids: JSON.stringify(ids) })
        }).then(() => fetchUsersTable());
    });

    document.getElementById('bulkStatusSelect').addEventListener('change', function() {
        const status = this.value;
        if (status === '') return;
        const ids = getSelectedUserIds();
        if (!ids.length) return alert('No users selected.');
        fetch('ajax_active_users.php', {
            method: 'POST',
            body: new URLSearchParams({ ajax: 'bulk_status', ids: JSON.stringify(ids), status: status })
        }).then(() => fetchUsersTable());
        this.value = '';
    });

    document.getElementById('bulkRoleSelect').addEventListener('change', function() {
        const role_id = this.value;
        if (role_id === '') return;
        const ids = getSelectedUserIds();
        if (!ids.length) return alert('No users selected.');
        fetch('ajax_active_users.php', {
            method: 'POST',
            body: new URLSearchParams({ ajax: 'bulk_role', ids: JSON.stringify(ids), role_id: role_id })
        }).then(() => fetchUsersTable());
        this.value = '';
    });

    document.getElementById('bulkExportBtn').addEventListener('click', function() {
        const ids = getSelectedUserIds();
        const params = new URLSearchParams({
            ajax: 'bulk_export',
            ids: JSON.stringify(ids),
            status: document.getElementById('statusInput').value,
            role: document.getElementById('roleInput').value,
            online: document.getElementById('onlineInput').checked ? 1 : '',
            search: document.getElementById('searchInput').value
        });
        window.location = 'ajax_active_users.php?' + params.toString();
    });

    document.getElementById('selectAll').addEventListener('change', function() {
        document.querySelectorAll('.row-select').forEach(cb => cb.checked = this.checked);
    });

    // Initial binding for edit/delete
    bindRowActions();

    // On page load, show all active users (default)
    fetchUsersTable();
});
