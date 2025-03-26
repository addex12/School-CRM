<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAQ - Survey System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header>
        <?php include 'user/includes/header.php'; ?>
    </header>
    <main>
        <h1>Frequently Asked Questions (FAQ)</h1>
        <div id="faq-section" class="faq-section">
            <!-- FAQ content will be loaded dynamically -->
        </div>
    </main>
    <footer>
        <?php include 'user/includes/footer.php'; ?>
    </footer>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const faqContent = [
                { question: "What is the Survey System?", answer: "The Survey System is a platform that allows users to create, distribute, and analyze surveys efficiently." },
                { question: "How do I create a survey?", answer: "You can create a survey by logging into your account and navigating to the survey builder section." },
                { question: "Is my data secure?", answer: "Yes, we take data security seriously and implement various measures to protect your information." }
            ];

            const faqSection = document.getElementById('faq-section');
            faqContent.forEach(faq => {
                const faqDiv = document.createElement('div');
                faqDiv.classList.add('faq-item');
                faqDiv.innerHTML = `<h3>${faq.question}</h3><p>${faq.answer}</p>`;
                faqSection.appendChild(faqDiv);
            });
        });
    </script>
</body>
</html>