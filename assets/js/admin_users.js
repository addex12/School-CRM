// admin_users.js
// Fetch and display active users from JSON

document.addEventListener('DOMContentLoaded', function () {
    fetch('/assets/js/admin_users.json')
        .then(response => response.json())
        .then(data => {
            const tableBody = document.getElementById('active-users-table-body');
            if (!tableBody) return;
            tableBody.innerHTML = '';
            data.forEach((user, idx) => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${idx + 1}</td>
                    <td>${user.username}</td>
                    <td>${user.email}</td>
                    <td>${user.role}</td>
                    <td>${user.last_active}</td>
                `;
                tableBody.appendChild(row);
            });
        })
        .catch(err => {
            console.error('Failed to load active users:', err);
        });
});
