<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200); exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$body    = json_decode(file_get_contents('php://input'), true);
$name    = isset($body['name'])    ? trim($body['name'])    : '';
$email   = isset($body['email'])   ? trim($body['email'])   : '';
$phone   = isset($body['phone'])   ? trim($body['phone'])   : '';
$insta   = isset($body['insta'])   ? trim($body['insta'])   : '';
$fat     = isset($body['fat'])     ? trim($body['fat'])     : '';
$source  = isset($body['source'])  ? trim($body['source'])  : '';
$medium  = isset($body['medium'])  ? trim($body['medium'])  : '';
$content = isset($body['content']) ? trim($body['content']) : '';
$term    = isset($body['term'])    ? trim($body['term'])    : '';

if (!$name || !$email || !$phone) {
    http_response_code(400);
    echo json_encode(['error' => 'Campos obrigatórios ausentes']);
    exit;
}

$parts     = explode(' ', $name, 2);
$firstName = $parts[0];
$lastName  = isset($parts[1]) ? $parts[1] : '';

$acUrl = 'https://lucasciotti.api-us1.com';
$acKey = '079c71b21b1e5e773d12625feeb76f59a027bef9b68ce48102c3ff1aa0f0dd8a11b83244';

// monta fieldValues com campos customizados
$fieldValues = [];
if ($fat)     $fieldValues[] = ['field' => '16', 'value' => $fat];
if ($insta)   $fieldValues[] = ['field' => '64', 'value' => $insta];
if ($source)  $fieldValues[] = ['field' => '21', 'value' => $source];
if ($medium)  $fieldValues[] = ['field' => '22', 'value' => $medium];
if ($term)    $fieldValues[] = ['field' => '23', 'value' => $term];
if ($content) $fieldValues[] = ['field' => '24', 'value' => $content];

$contactPayload = json_encode([
    'contact' => [
        'email'       => $email,
        'firstName'   => $firstName,
        'lastName'    => $lastName,
        'phone'       => $phone,
        'fieldValues' => $fieldValues,
    ]
]);

// 1. Criar / atualizar contato
$ch = curl_init($acUrl . '/api/3/contacts');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $contactPayload,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Api-Token: ' . $acKey,
    ],
    CURLOPT_TIMEOUT        => 15,
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr  = curl_error($ch);
curl_close($ch);

if ($curlErr) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro de conexão: ' . $curlErr]);
    exit;
}

if ($httpCode !== 201 && $httpCode !== 200) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao criar contato', 'code' => $httpCode, 'detail' => $response]);
    exit;
}

$contactData = json_decode($response, true);
$contactId   = $contactData['contact']['id'] ?? null;

// 2. Aplicar tag 86 ([CONSULT] Cadastrou)
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
        CURLOPT_TIMEOUT => 10,
    ]);
    curl_exec($ch);
    curl_close($ch);
}

echo json_encode(['success' => true, 'contactId' => $contactId]);
