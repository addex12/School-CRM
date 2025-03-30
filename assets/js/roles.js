document.addEventListener('DOMContentLoaded', function () {
    const targetRolesElement = document.getElementById('target-roles');
    if (!targetRolesElement) return;

    const targetRoles = JSON.parse(targetRolesElement.dataset.roles || '[]');

    fetch('../api/roles.php')
        .then(response => response.json())
        .then(data => {
            const roles = targetRoles.map(roleId => data[roleId] || `Unknown Role (${roleId})`);
            targetRolesElement.textContent = roles.join(', ');
        })
        .catch(error => {
            console.error('Error fetching roles:', error);
            targetRolesElement.textContent = 'Error loading roles.';
        });
});
