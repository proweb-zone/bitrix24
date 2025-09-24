<?php
// Настройки
$webhook_url = 'http://localhost/rest/1/hwca7msorxkj08wl/';
$contacts = [
    [
        'NAME' => 'Иван',
        'LAST_NAME' => 'Иванов',
        'PHONE' => [['VALUE' => '+79161234567', 'VALUE_TYPE' => 'WORK']],
        'EMAIL' => [['VALUE' => 'ivanov@mail.ru', 'VALUE_TYPE' => 'WORK']],
        'POST' => 'Директор',
        'COMPANY_ID' => 123 // ID существующей компании
    ],
    [
        'NAME' => 'Мария',
        'LAST_NAME' => 'Петрова',
        'PHONE' => [['VALUE' => '+79169876543', 'VALUE_TYPE' => 'WORK']],
        'EMAIL' => [['VALUE' => 'petrova@mail.ru', 'VALUE_TYPE' => 'WORK']]
    ]
];

// Функция добавления контакта
function addContact($contact_data) {
    global $webhook_url;

    $query_url = $webhook_url . 'crm.contact.add';
    $query_data = http_build_query(['fields' => $contact_data]);

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_POST => 1,
        CURLOPT_HEADER => 0,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => $query_url,
        CURLOPT_POSTFIELDS => $query_data,
    ]);

    $result = curl_exec($curl);
    curl_close($curl);

    return json_decode($result, true);
}

// Массовая загрузка
foreach ($contacts as $contact) {
    $result = addContact($contact);
    if (isset($result['error'])) {
        echo "Ошибка: " . $result['error_description'] . "\n";
    } else {
        echo "Контакт создан, ID: " . $result['result'] . "\n";
    }
    // Пауза между запросами (ограничение API)
    usleep(500000); // 0.5 секунды
}
?>
