<?php
/**
 * Created by PhpStorm.
 * User: x
 * Date: 7.2.25.
 * Time: 12.47
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

$servername = "localhost";
$username = "root";
$password = "ilija";
$dbname = "panmotortest";

// Konektovanje na bazu
$conn = mysqli_connect($servername, $username, $password, $dbname);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
//$kurs = "srednji"; // Možete promeniti vrednost
$kurs = "prodajni";
if ($kurs === "prodajni") {
//    $url = 'https://www.nbs.rs/kursnaListaModul/zaDevize.faces?lang=lat';
    $url = 'https://webappcenter.nbs.rs/WebApp/ExchangeRate/ExchangeRate/ForeignExchange?';
} else {
    $url = 'https://webappcenter.nbs.rs/WebApp/ExchangeRate/ExchangeRate/MiddleExchangeRate?';
}

// Preuzimanje HTML-a pomoću cURL-a
/*$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, $url);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_HEADER, false);
curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
curl_setopt($curl, CURLOPT_TIMEOUT, 30);
curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0');
*/

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HEADER => false,
    CURLOPT_CONNECTTIMEOUT => 30,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36',
    CURLOPT_HTTPHEADER => [
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
        'Accept-Language: sr-RS,sr;q=0.9,en-US;q=0.8,en;q=0.7',
        'Connection: keep-alive'
    ],
    CURLOPT_SSL_VERIFYPEER => false, // ako je problem sa SSL
    CURLOPT_SSL_VERIFYHOST => false
]);

$response = curl_exec($curl);
$error = curl_error($curl);
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

if ($error) {
    echo "Greška prilikom cURL zahteva: $error";
} elseif ($httpCode !== 200) {
    echo "Zahtev nije uspeo. HTTP kod: $httpCode";
} elseif (empty($response)) {
    echo "Odgovor je prazan.";
}

if ($response === false) {
    die('cURL error: ' . $error);
}

// Upisivanje fajla
$filename = 'tmp/temp_file.txt';
if (!is_dir('tmp')) {
    mkdir('tmp', 0777, true);
}
file_put_contents($filename, $response);

// Provera da li je fajl upisan
$fileContents = file_get_contents($filename);
if (!$fileContents) {
    die('Fajl je prazan!');
}
unlink($filename);

// Parsiranje HTML-a
libxml_use_internal_errors(true);
$dom = new DOMDocument();
$dom->loadHTML($fileContents);
libxml_clear_errors();

// Pronalaženje tabele
$table = $dom->getElementsByTagName('table')->item(0);
if (!$table) {
    die('Tabela nije pronađena u HTML-u!');
}

foreach ($table->getElementsByTagName('tr') as $row) {
    $cells = $row->getElementsByTagName('td');
    // Proverite da li red ima dovoljno ćelija pre nego što im pristupite
    if ($cells->length < 5) {
        continue; // Preskoči redove koji nemaju dovoljno podataka
    }

    $currency = trim($cells->item(0)->textContent);
    $unit = trim($cells->item(3)->textContent);
    if ($kurs === "prodajni") {
        $value = trim($cells->item(5)->textContent);
    } else {
        $value = trim($cells->item(4)->textContent);
    }

    // Konverzija vrednosti
    $value = floatval(str_replace(',', '.', $value));
//    $unit = floatval(str_replace(',', '.', $unit));
    $value = round(((1 / $value) * $unit), 8);

    if ((float)$value) {
        $sql = "UPDATE oc_currency SET value = '" . (float)$value . "', date_modified = '" . date('Y-m-d H:i:s') . "' WHERE code = '" . $currency . "'";
        $result = mysqli_query($conn, $sql);
    }
}

if ($result)
    echo 'sve OK ' . date('Y-m-d H:i:s');
else
    echo 'NOK ' . date('Y-m-d H:i:s');
$sql = "UPDATE oc_currency SET value = '1.00000', date_modified = '" .  date('Y-m-d H:i:s') . "' WHERE code = 'RSD'";
$result = mysqli_query($conn, $sql);

// Provera rezultata

$sql = "SELECT * FROM oc_currency";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) > 0) {
    echo ' *** ' . $kurs .' *** ' . "<br>";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "id: " . $row["code"] . " - value: " . $row["value"] . " - date_modified: " . $row["date_modified"] . PHP_EOL;
    }
}
// Zatvaranje konekcije
mysqli_close($conn);
