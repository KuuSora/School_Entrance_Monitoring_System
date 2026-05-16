<?php
$config = require __DIR__ . '/config.php';

function smtp_send_mail($toEmail, $toName, $subject, $bodyText) {
    $config = require __DIR__ . '/config.php';
    if (empty($config['smtp_enabled'])) {
        return ['ok' => false, 'error' => 'SMTP disabled'];
    }

    $host = $config['smtp_host'];
    $port = (int)$config['smtp_port'];
    $user = $config['smtp_username'];
    $pass = $config['smtp_password'];
    $enc = isset($config['smtp_encryption']) ? strtolower($config['smtp_encryption']) : 'tls';
    $timeout = isset($config['smtp_timeout']) ? (int)$config['smtp_timeout'] : 20;

    if ($host === '' || $port <= 0 || $user === '' || $pass === '') {
        return ['ok' => false, 'error' => 'SMTP settings missing'];
    }

    $scheme = $enc === 'ssl' ? 'ssl://' : 'tcp://';
    $fp = @stream_socket_client($scheme . $host . ':' . $port, $errno, $errstr, $timeout);
    if (!$fp) {
        return ['ok' => false, 'error' => 'SMTP connect failed: ' . $errstr];
    }

    stream_set_timeout($fp, $timeout);

    $read = function () use ($fp) {
        $data = '';
        while (($line = fgets($fp, 515)) !== false) {
            $data .= $line;
            if (preg_match('/^\d{3} /', $line)) {
                break;
            }
        }
        return $data;
    };

    $expect = function ($resp, $codes) {
        foreach ((array)$codes as $code) {
            if (strpos($resp, (string)$code) === 0) {
                return true;
            }
        }
        return false;
    };

    $send = function ($cmd, $codes) use ($fp, $read, $expect) {
        if ($cmd !== null) {
            fwrite($fp, $cmd . "\r\n");
        }
        $resp = $read();
        return [$expect($resp, $codes), $resp];
    };

    list($ok, $resp) = $send(null, 220);
    if (!$ok) {
        fclose($fp);
        return ['ok' => false, 'error' => 'SMTP greeting failed: ' . trim($resp)];
    }

    list($ok, $resp) = $send('EHLO localhost', 250);
    if (!$ok) {
        fclose($fp);
        return ['ok' => false, 'error' => 'SMTP EHLO failed: ' . trim($resp)];
    }

    if ($enc === 'tls') {
        list($ok, $resp) = $send('STARTTLS', 220);
        if (!$ok) {
            fclose($fp);
            return ['ok' => false, 'error' => 'SMTP STARTTLS failed: ' . trim($resp)];
        }
        if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            fclose($fp);
            return ['ok' => false, 'error' => 'SMTP TLS negotiation failed'];
        }
        list($ok, $resp) = $send('EHLO localhost', 250);
        if (!$ok) {
            fclose($fp);
            return ['ok' => false, 'error' => 'SMTP EHLO after TLS failed: ' . trim($resp)];
        }
    }

    list($ok, $resp) = $send('AUTH LOGIN', 334);
    if (!$ok) {
        fclose($fp);
        return ['ok' => false, 'error' => 'SMTP AUTH LOGIN failed: ' . trim($resp)];
    }

    list($ok, $resp) = $send(base64_encode($user), 334);
    if (!$ok) {
        fclose($fp);
        return ['ok' => false, 'error' => 'SMTP username rejected: ' . trim($resp)];
    }

    list($ok, $resp) = $send(base64_encode($pass), 235);
    if (!$ok) {
        fclose($fp);
        return ['ok' => false, 'error' => 'SMTP password rejected: ' . trim($resp)];
    }

    $fromEmail = $config['from_email'];
    $fromName = $config['from_name'];

    list($ok, $resp) = $send('MAIL FROM:<' . $fromEmail . '>', 250);
    if (!$ok) {
        fclose($fp);
        return ['ok' => false, 'error' => 'SMTP MAIL FROM failed: ' . trim($resp)];
    }

    list($ok, $resp) = $send('RCPT TO:<' . $toEmail . '>', [250, 251]);
    if (!$ok) {
        fclose($fp);
        return ['ok' => false, 'error' => 'SMTP RCPT TO failed: ' . trim($resp)];
    }

    list($ok, $resp) = $send('DATA', 354);
    if (!$ok) {
        fclose($fp);
        return ['ok' => false, 'error' => 'SMTP DATA failed: ' . trim($resp)];
    }

    $fromHeader = $fromName !== '' ? $fromName . ' <' . $fromEmail . '>' : $fromEmail;
    $toHeader = $toName !== '' ? $toName . ' <' . $toEmail . '>' : $toEmail;

    $headers = [
        'From: ' . $fromHeader,
        'To: ' . $toHeader,
        'Subject: ' . $subject,
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8'
    ];

    $message = implode("\r\n", $headers) . "\r\n\r\n" . $bodyText . "\r\n";
    fwrite($fp, $message . "\r\n.\r\n");

    $resp = $read();
    if (!$expect($resp, 250)) {
        fclose($fp);
        return ['ok' => false, 'error' => 'SMTP DATA terminate failed: ' . trim($resp)];
    }

    $send('QUIT', 221);
    fclose($fp);
    return ['ok' => true];
}

function send_scan_email($toEmail, $toName, $direction, $uid, $scanTime) {
    $subject = 'RFID Scan Alert';
    $body = "Hello " . ($toName !== '' ? $toName : 'there') . ",\n\n" .
        "Your RFID card was scanned.\n" .
        "Direction: " . $direction . "\n" .
        "UID: " . $uid . "\n" .
        "Time: " . $scanTime . "\n\n" .
        "If this was not you, please contact the admin.";

    return smtp_send_mail($toEmail, $toName, $subject, $body);
}
