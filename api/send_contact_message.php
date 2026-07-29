<?php
// api/send_contact_message.php

error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

require_once __DIR__ . '/../backend/includes/mailer.php';

try {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    $firstName = trim($data['first_name'] ?? '');
    $lastName = trim($data['last_name'] ?? '');
    $email = trim($data['email'] ?? '');
    $message = trim($data['message'] ?? '');

    $fullName = trim($firstName . ' ' . $lastName);

    if (empty($firstName) || empty($lastName) || empty($message)) {
        throw new Exception('Please fill in all required fields.');
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Please enter a valid email address.');
    }

    $emailSent = sendContactFormEmail($fullName, $email, $message);

    if (!$emailSent) {
        throw new Exception('Failed to send your message. Please try again later.');
    }

    echo json_encode(['success' => true, 'message' => 'Thanks for reaching out! We\'ll get back to you within 24 hours.']);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}