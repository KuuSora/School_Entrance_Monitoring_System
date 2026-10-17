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
    'from_name' => 'RFID Monitor',

    // SMS provider: 'textbee' uses the web gateway; 'sim900a' lets the ESP32
    // send the SMS through its connected SIM900A modem.
    'sms_provider' => 'sim900a',
    // TextBee SMS Gateway (https://textbee.dev)
    'textbee_enabled' => false,
    'textbee_api_key' => 'c832da0c-e199-4da5-b069-a32dc99da5be',
    'textbee_device_id' => '69fd95879b9db0a6fe283b7c',
    'textbee_api_url' => 'https://api.textbee.dev/api/v1'
];
