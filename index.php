<?php
require 'vendor/autoload.php'; // Make sure to install Guzzle via Composer

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

function checkIMEI($imei) {
    $client = new Client();
    $headers = [
        'Authorization' => 'Bearer dmRPeiyIT3x5p1W8Yt38dPISBcp3JeSkWW68NqQP1f69e2fd',
        'Accept-Language' => 'en',
        'Content-Type' => 'application/json'
    ];
    $body = json_encode([
        "deviceId" => $imei,
        "serviceId" => 5
    ]);
    $request = new Request('POST', 'https://api.imeicheck.net/v1/checks', $headers, $body);
    $res = $client->sendAsync($request)->wait();
    return json_decode($res->getBody(), true);
}

function getCheckedIMEIs() {
    $checkedIMEIs = [];
    if (($handle = fopen("checked.csv", "r")) !== FALSE) {
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $checkedIMEIs[] = $data[0]; // Assuming IMEI is the first column
        }
        fclose($handle);
    }
    return $checkedIMEIs;
}

function appendToCSV($filename, $data) {
    $fp = fopen($filename, 'a');
    fputcsv($fp, $data);
    fclose($fp);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $imeis = explode("\n", trim($_POST['imeis']));
    $checkedIMEIs = getCheckedIMEIs();
    $newIMEIs = array_diff($imeis, $checkedIMEIs);

    $timestamp = date("Y-m-d_H-i-s");
    $newFileName = "results_{$timestamp}.csv";

    // Write header to new file
    $header = ['imei', 'imei2', 'serial', 'modelName', 'fullName', 'modelNumber', 'warrantyStatus', 'salesBuyerCode', 'salesBuyerName', 'carrier', 'soldByCountry'];
    appendToCSV($newFileName, $header);

    foreach ($newIMEIs as $imei) {
        $result = checkIMEI($imei);
        $properties = $result['properties'];

        $row = [
            $properties['imei'],
            $properties['imei2'],
            $properties['serial'],
            $properties['modelName'],
            $properties['fullName'],
            $properties['modelNumber'],
            $properties['warrantyStatus'],
            $properties['salesBuyerCode'],
            $properties['salesBuyerName'],
            $properties['carrier'],
            $properties['soldByCountry']
        ];

        appendToCSV($newFileName, $row);
        appendToCSV('checked.csv', $row);
    }

    $downloadLink = "<a href='{$newFileName}' download>Download Results</a>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IMEI Checker</title>
</head>
<body>
    <h1>IMEI Checker</h1>
    <form method="post">
        <label for="imeis">Enter IMEI numbers (one per line):</label><br>
        <textarea name="imeis" id="imeis" rows="10" cols="50"></textarea><br>
        <input type="submit" value="Check IMEIs">
    </form>

    <?php if (isset($downloadLink)) echo $downloadLink; ?>
</body>
</html>