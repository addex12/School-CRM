//Developer: Adugna Gizaw
//Email: gizawadugna@gmail.com
//Phone: +251925582067 -->

function validateForm() {
    const host = document.getElementById('host').value;
    const dbName = document.getElementById('db_name').value;
    const username = document.getElementById('username').value;
    const password = document.getElementById('password').value;

    if (!host) {
        alert('Host is required.');
        return false;
    }
    if (!dbName) {
        alert('Database name is required.');
        return false;
    }
    if (!username) {
        alert('Username is required.');
        return false;
    }
    if (!password) {
        alert('Password is required.');
        return false;
    }
    alert('All fields are required.');
    return false;
}

return true;
