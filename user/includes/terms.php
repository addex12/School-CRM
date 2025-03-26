<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms of Service - Survey System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header>
        <?php include 'user/includes/header.php'; ?>
    </header>
    <main>
        <h1>Terms of Service</h1>
        <div id="terms-section" class="terms-section">
            <!-- Terms content will be loaded dynamically -->
        </div>
    </main>
    <footer>
        <?php include 'user/includes/footer.php'; ?>
    </footer>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const termsContent = [
                { title: "1. Acceptance of Terms", text: "By using our services, you agree to comply with these terms and conditions." },
                { title: "2. User Responsibilities", text: "Users are responsible for maintaining the confidentiality of their account information." },
                { title: "3. Data Usage", text: "We may collect and use your data in accordance with our privacy policy." },
                { title: "4. Limitation of Liability", text: "We are not liable for any damages resulting from the use of our services." },
                { title: "5. Changes to Terms", text: "We reserve the right to modify these terms at any time. Users will be notified of any changes." }
            ];

            const termsSection = document.getElementById('terms-section');
            termsContent.forEach(term => {
                const termDiv = document.createElement('div');
                termDiv.innerHTML = `<h2>${term.title}</h2><p>${term.text}</p>`;
                termsSection.appendChild(termDiv);
            });
        });
    </script>
</body>
</html>