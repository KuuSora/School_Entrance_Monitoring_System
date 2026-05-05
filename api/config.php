<?php
return [
    // Set to true once SMTP credentials are filled in.
    'smtp_enabled' => false,
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587,
    'smtp_username' => 'your-email@gmail.com',
    'smtp_password' => 'app-password',
    // Use 'tls' for STARTTLS on port 587 or 'ssl' for implicit TLS (465).
    'smtp_encryption' => 'tls',
    'smtp_timeout' => 20,
    'from_email' => 'your-email@gmail.com',
    'from_name' => 'RFID Monitor'
];
