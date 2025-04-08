document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('ticket-search');
    const tableRows = document.querySelectorAll('.tickets-table tbody tr');

    // Filter table rows based on search input
    searchInput.addEventListener('input', function () {
        const query = searchInput.value.toLowerCase();
        tableRows.forEach(row => {
            const rowText = row.textContent.toLowerCase();
            row.style.display = rowText.includes(query) ? '' : 'none';
        });
    });

    // Handle ticket actions
    document.querySelectorAll('.ticket-action').forEach(button => {
        button.addEventListener('click', function () {
            const ticketId = this.dataset.ticketId;
            const action = this.dataset.action;

            if (action === 'delete' && !confirm('Are you sure you want to delete this ticket?')) {
                return;
            }

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'tickets.php';

            const ticketIdInput = document.createElement('input');
            ticketIdInput.type = 'hidden';
            ticketIdInput.name = 'ticket_id';
            ticketIdInput.value = ticketId;

            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = action;

            form.appendChild(ticketIdInput);
            form.appendChild(actionInput);
            document.body.appendChild(form);
            form.submit();
        });
    });

    // Modal functionality for viewing ticket details
    const modal = document.getElementById('ticketModal');
    const closeModalButton = modal.querySelector('.close');

    closeModalButton.addEventListener('click', function () {
        modal.style.display = 'none';
    });

    document.querySelectorAll('.view-ticket').forEach(button => {
        button.addEventListener('click', function () {
            const ticketId = this.dataset.ticketId;

            fetch(`ticket_details.php?ticket_id=${ticketId}`)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('ticketDetails').innerHTML = data;
                    modal.style.display = 'block';
                });
        });
    });
});
