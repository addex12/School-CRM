// Linted, modular JS for active users AJAX
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('searchInput');
    const onlineInput = document.getElementById('onlineInput');
    const roleInput = document.getElementById('roleInput');
    const statusInput = document.getElementById('statusInput');
    const usersTableBody = document.getElementById('usersTableBody');
    const form = document.getElementById('userSearchForm');
    let searchTimeout = null;

    function fetchUsers() {
        const params = new URLSearchParams();
        params.append('ajax', 'search');
        params.append('search', searchInput.value);
        if (onlineInput.checked) params.append('online', '1');
        if (roleInput.value) params.append('role', roleInput.value);
        if (statusInput.value !== "") params.append('status', statusInput.value);

        fetch('../api/active_users.php?' + params.toString())
            .then(res => res.json())
            .then(data => {
                usersTableBody.innerHTML = '';
                if (data.users && data.users.length) {
                    data.users.forEach(user => {
                        usersTableBody.innerHTML += `
<tr data-id="${user.id}">
    <td class="select-col"><input type="checkbox" class="row-select"></td>
    <td>${user.id}</td>
    <td class="username">${user.username}</td>
    <td>${user.last_active || ''}</td>
    <td class="online">${user.online ? '<span class="online-dot"></span> <span style="color:#27ae60;font-weight:500;">Online</span>' : '<span style="color:#aaa;">Offline</span>'}</td>
    <td class="role" data-role-id="${user.role_id}">${user.role_name}</td>
    <td class="status" data-status="${user.active}">${user.active == 1 ? 'Active' : 'Inactive'}</td>
    <td>
        <button class="crud-btn edit">Edit</button>
        <button class="crud-btn delete">Delete</button>
    </td>
</tr>`;
                    });
                } else {
                    usersTableBody.innerHTML = '<tr><td colspan="8" class="text-center">No active users found</td></tr>';
                }
            });
    }

    searchInput.addEventListener('input', function () {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(fetchUsers, 250);
    });
    onlineInput.addEventListener('change', fetchUsers);
    roleInput.addEventListener('change', fetchUsers);
    statusInput.addEventListener('change', fetchUsers);

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        fetchUsers();
    });

    // ...existing JS for CRUD, bulk, etc. should be refactored to use the API endpoints...
    // ...existing code...
});