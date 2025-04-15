<?php
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="bulk_email_template.csv"');

echo "email,role\n";
echo "parent1@example.com,parent\n";
echo "teacher1@example.com,teacher\n";
echo "student1@example.com,student\n";
// Add more sample rows if needed
exit;
