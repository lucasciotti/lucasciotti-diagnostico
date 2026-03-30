<?php
$name    = isset($_POST['name'])    ? trim($_POST['name'])    : '';
$email   = isset($_POST['email'])   ? trim($_POST['email'])   : '';
$phone   = isset($_POST['phone'])   ? trim($_POST['phone'])   : '';
$insta   = isset($_POST['insta'])   ? trim($_POST['insta'])   : '';
$fat     = isset($_POST['fat'])     ? trim($_POST['fat'])     : '';
$source  = isset($_POST['utm_source'])  ? trim($_POST['utm_source'])  : '';
$medium  = isset($_POST['utm_medium'])  ? trim($_POST['utm_medium'])  : '';
$content = isset($_POST['utm_content']) ? trim($_POST['utm_content']) : '';
$term    = isset($_POST['utm_term'])    ? trim($_POST['utm_term'])    : '';

if (!$name || !$email) {
    header('Location: /diagnostico?erro=campos');
    exit;
}

$parts     = explode(' ', $name, 2);
$firstName = $parts[0];
$lastName  = isset($parts[1]) ? $parts[1] : '';

$acUrl = 'https://lucasciotti.api-us1.com';
$acKey = '079c71b21b1e5e773d12625feeb76f59a027bef9b68ce48102c3ff1aa0f0dd8a11b83244';

$fieldValues = [];
if ($fat)     $fieldValues[] = ['field' => '16', 'value' => $fat];
if ($insta)   $fieldValues[] = ['field' => '64', 'value' => $insta];
if ($source)  $fieldValues[] = ['field' => '21', 'value' => $source];
if ($medium)  $fieldValues[] = ['field' => '22', 'value' => $medium];
if ($term)    $fieldValues[] = ['field' => '23', 'value' => $term];
if ($content) $fieldValues[] = ['field' => '24', 'value' => $content];

$payload = json_encode([
    'contact' => [
        'email'       => $email,
        'firstName'   => $firstName,
        'lastName'    => $lastName,
        'phone'       => $phone,
        'fieldValues' => $fieldValues,
    ]
]);

$ch = curl_init($acUrl . '/api/3/contacts');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Api-Token: ' . $acKey,
    ],
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_SSL_VERIFYPEER => false,
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$contactId = null;
if ($httpCode === 200 || $httpCode === 201) {
    $data = json_decode($response, true);
    $contactId = $data['contact']['id'] ?? null;
}

// Aplica tag 86
if ($contactId) {
    $tagPayload = json_encode([
        'contactTag' => ['contact' => $contactId, 'tag' => 86]
    ]);
    $ch = curl_init($acUrl . '/api/3/contactTags');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $tagPayload,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Api-Token: ' . $acKey,
        ],
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    curl_exec($ch);
    curl_close($ch);
}

header('Location: /diagnostico-confirmacao');
exit;
