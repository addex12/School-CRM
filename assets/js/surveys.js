document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('search-surveys');
    const filterStatus = document.getElementById('filter-status');
    const surveySections = document.querySelectorAll('.survey-section');
    const bulkActionSelect = document.getElementById('bulk-action');
    const bulkActionButton = document.getElementById('apply-bulk-action');
    const paginationContainer = document.getElementById('pagination-container');

    let currentPage = 1;
    const rowsPerPage = 10;

    function filterSurveys() {
        const searchTerm = searchInput.value.toLowerCase();
        const selectedStatus = filterStatus.value;

        surveySections.forEach(section => {
            const status = section.getAttribute('data-status');
            const rows = section.querySelectorAll('tbody tr');
            let hasVisibleRows = false;

            rows.forEach(row => {
                const title = row.querySelector('td:first-child').textContent.toLowerCase();
                const matchesSearch = title.includes(searchTerm);
                const matchesStatus = !selectedStatus || status === selectedStatus;

                if (matchesSearch && matchesStatus) {
                    row.style.display = '';
                    hasVisibleRows = true;
                } else {
                    row.style.display = 'none';
                }
            });

            section.style.display = hasVisibleRows ? '' : 'none';
        });

        paginateSurveys();
    }

    function paginateSurveys() {
        surveySections.forEach(section => {
            const rows = section.querySelectorAll('tbody tr');
            const totalRows = rows.length;
            const totalPages = Math.ceil(totalRows / rowsPerPage);

            rows.forEach((row, index) => {
                row.style.display = index >= (currentPage - 1) * rowsPerPage && index < currentPage * rowsPerPage ? '' : 'none';
            });

            renderPagination(totalPages);
        });
    }

    function renderPagination(totalPages) {
        paginationContainer.innerHTML = '';
        for (let i = 1; i <= totalPages; i++) {
            const pageButton = document.createElement('button');
            pageButton.textContent = i;
            pageButton.className = i === currentPage ? 'active' : '';
            pageButton.addEventListener('click', () => {
                currentPage = i;
                paginateSurveys();
            });
            paginationContainer.appendChild(pageButton);
        }
    }

    function applyBulkAction() {
        const selectedAction = bulkActionSelect.value;
        const selectedSurveys = document.querySelectorAll('.survey-checkbox:checked');

        if (!selectedAction || selectedSurveys.length === 0) {
            alert('Please select an action and at least one survey.');
            return;
        }

        const surveyIds = Array.from(selectedSurveys).map(checkbox => checkbox.value);

        if (selectedAction === 'delete') {
            if (confirm('Are you sure you want to delete the selected surveys?')) {
                // Perform delete action
                console.log('Deleting surveys:', surveyIds);
                // Add AJAX request to delete surveys
            }
        } else if (selectedAction === 'activate') {
            // Perform activate action
            console.log('Activating surveys:', surveyIds);
            // Add AJAX request to activate surveys
        } else if (selectedAction === 'deactivate') {
            // Perform deactivate action
            console.log('Deactivating surveys:', surveyIds);
            // Add AJAX request to deactivate surveys
        }
    }

    function sortSurveys(columnIndex, ascending = true) {
        surveySections.forEach(section => {
            const rows = Array.from(section.querySelectorAll('tbody tr'));
            rows.sort((a, b) => {
                const aText = a.children[columnIndex].textContent.trim().toLowerCase();
                const bText = b.children[columnIndex].textContent.trim().toLowerCase();

                if (aText < bText) return ascending ? -1 : 1;
                if (aText > bText) return ascending ? 1 : -1;
                return 0;
            });

            const tbody = section.querySelector('tbody');
            rows.forEach(row => tbody.appendChild(row));
        });
    }

    // Event Listeners
    searchInput.addEventListener('input', filterSurveys);
    filterStatus.addEventListener('change', filterSurveys);
    bulkActionButton.addEventListener('click', applyBulkAction);

    document.querySelectorAll('.sortable').forEach((header, index) => {
        let ascending = true;
        header.addEventListener('click', () => {
            sortSurveys(index, ascending);
            ascending = !ascending;
        });
    });

    // Initialize pagination and filtering
    paginateSurveys();
    filterSurveys();
});

// Function to handle survey deletion
function deleteSurvey(surveyId) {
    if (confirm('Are you sure you want to delete this survey?')) {
        // Perform delete action
        console.log('Deleting survey:', surveyId);
        // Add AJAX request to delete survey
    }
}


// Function to handle survey activation
function activateSurvey(surveyId) {
    // Perform activation action
    console.log('Activating survey:', surveyId);
    // Add AJAX request to activate survey
}

// Function to handle survey preview
function previewSurvey(surveyId) {
    // Perform preview action
    console.log('Previewing survey:', surveyId);
    // Add AJAX request to fetch and display survey preview
}
// Function to handle survey editing
function editSurvey(surveyId) {
    // Perform edit action
    console.log('Editing survey:', surveyId);
    // Add AJAX request to fetch and display survey edit form
}
// Function to handle survey sharing
function shareSurvey(surveyId) {
    // Perform share action
    console.log('Sharing survey:', surveyId);
    // Add AJAX request to fetch and display survey sharing options
}
// Function to handle survey exporting
function exportSurvey(surveyId) {
    // Perform export action
    console.log('Exporting survey:', surveyId);
    // Add AJAX request to fetch and download survey data
}
// Function to handle survey importing
function importSurvey() {
    const fileInput = document.getElementById('import-file');
    const file = fileInput.files[0];

    if (!file) {
        alert('Please select a file to import.');
        return;
    }

    const formData = new FormData();
    formData.append('file', file);

    // Perform import action
    console.log('Importing survey:', file.name);
    // Add AJAX request to upload and process the file
}
// Function to handle survey analytics
function viewSurveyAnalytics(surveyId) {
    // Perform analytics action
    console.log('Viewing analytics for survey:', surveyId);
    // Add AJAX request to fetch and display survey analytics
}
// Function to handle survey settings
function editSurveySettings(surveyId) {
    // Perform settings action
    console.log('Editing settings for survey:', surveyId);
    // Add AJAX request to fetch and display survey settings form
}
// Function to handle survey responses
function viewSurveyResponses(surveyId) {
    // Perform responses action
    console.log('Viewing responses for survey:', surveyId);
    // Add AJAX request to fetch and display survey responses
}
// Function to handle survey sharing
function shareSurvey(surveyId) {
    // Perform sharing action
    console.log('Sharing survey:', surveyId);
    // Add AJAX request to generate and display sharing link
}
// Function to handle survey deletion
function deleteSurvey(surveyId) {
    // Perform deletion action
    console.log('Deleting survey:', surveyId);
    // Add AJAX request to delete the survey
}

// Function to handle survey editing
function editSurvey(surveyId) {
    // Perform editing action
    console.log('Editing survey:', surveyId);
    // Add AJAX request to fetch and display survey editing form
}

// Function to handle survey deactivation
function deactivateSurvey(surveyId) {
    // Perform deactivation action
    console.log('Deactivating survey:', surveyId);
    // Add AJAX request to deactivate the survey
}
// Function to handle survey preview
function previewSurvey(surveyId) {
    // Perform preview action
    console.log('Previewing survey:', surveyId);
    // Add AJAX request to fetch and display survey preview
}
// Function to handle survey analytics
function viewSurveyAnalytics(surveyId) {
    // Perform analytics action
    console.log('Viewing analytics for survey:', surveyId);
    // Add AJAX request to fetch and display survey analytics
}
// Function to handle survey deletion
function deleteSurvey(surveyId) {
    // Perform deletion action
    console.log('Deleting survey:', surveyId);
    // Add AJAX request to delete the survey
}
// Function to handle survey editing
function editSurvey(surveyId) {
    // Perform editing action
    console.log('Editing survey:', surveyId);
    // Add AJAX request to fetch and display survey editing form
}


// Function to handle survey sharing
function shareSurvey(surveyId) {
    // Perform sharing action
    console.log('Sharing survey:', surveyId);
    // Add AJAX request to share the survey
}
// Function to handle survey deletion
function deleteSurvey(surveyId) {
    // Perform deletion action
    console.log('Deleting survey:', surveyId);
    // Add AJAX request to delete the survey
}
// Function to handle survey duplication
function duplicateSurvey(surveyId) {
    // Perform duplication action
    console.log('Duplicating survey:', surveyId);
    // Add AJAX request to duplicate the survey
}
// Function to handle survey editing
function editSurvey(surveyId) {
    // Perform editing action
    console.log('Editing survey:', surveyId);
    // Add AJAX request to fetch and display survey editing form
}
   // Add event listeners for survey actions
    document.querySelectorAll('.deactivate-survey').forEach(button => {
        button.addEventListener('click', () => {
            const surveyId = button.getAttribute('data-survey-id');
            deactivateSurvey(surveyId);
        });
    });
    document.querySelectorAll('.share-survey').forEach(button => {
        button.addEventListener('click', () => {
            const surveyId = button.getAttribute('data-survey-id');
            shareSurvey(surveyId);
        });
    });
    document.querySelectorAll('.delete-survey').forEach(button => {
        button.addEventListener('click', () => {
            const surveyId = button.getAttribute('data-survey-id');
            deleteSurvey(surveyId);
        });
    });
    document.querySelectorAll('.duplicate-survey').forEach(button => {
        button.addEventListener('click', () => {
            const surveyId = button.getAttribute('data-survey-id');
            duplicateSurvey(surveyId);
        });
    });
    document.querySelectorAll('.edit-survey').forEach(button => {
        button.addEventListener('click', () => {
            const surveyId = button.getAttribute('data-survey-id');
            editSurvey(surveyId);
        });
    });
    document.querySelectorAll('.activate-survey').forEach(button => {
        button.addEventListener('click', () => {
            const surveyId = button.getAttribute('data-survey-id');
            activateSurvey(surveyId);
        });
    });
    document.querySelectorAll('.deactivate-survey').forEach(button => {
        button.addEventListener('click', () => {
            const surveyId = button.getAttribute('data-survey-id');
            deactivateSurvey(surveyId);
        });
    });
    document.querySelectorAll('.preview-survey').forEach(button => {
        button.addEventListener('click', () => {
            const surveyId = button.getAttribute('data-survey-id');
            previewSurvey(surveyId);
        });
    });
    document.querySelectorAll('.view-analytics').forEach(button => {
        button.addEventListener('click', () => {
            const surveyId = button.getAttribute('data-survey-id');
            viewSurveyAnalytics(surveyId);
        });
    });
