<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAQ - Survey System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .faq-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .faq-section {
            margin-top: 20px;
        }
        .faq-item {
            margin-bottom: 20px;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        .faq-item h3 {
            margin-bottom: 10px;
            font-size: 1.2em;
            color: #333;
        }
        .faq-item p {
            font-size: 1em;
            color: #555;
        }
    </style>
</head>
<body>
    <header>
        <?php include __DIR__ . '/header.php'; ?>
    </header>
    <main>
        <div class="faq-container">
            <h1>Frequently Asked Questions (FAQ)</h1>
            <div id="faq-section" class="faq-section">
                <!-- FAQ content will be loaded dynamically -->
            </div>
        </div>
    </main>
    <footer>
        <?php include __DIR__ . '/footer.php'; ?>
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