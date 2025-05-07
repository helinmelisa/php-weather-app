<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load API key from .env
$env = parse_ini_file('.env');
$apiKey = $env['API_KEY'] ?? null;

if (!$apiKey) {
    die("API key not found. Please check your .env file.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PHP Weather App</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>PHP Weather App</h1>

    <form method="GET" action="">
        <input type="text" name="city" placeholder="Enter city name" required>
        <button type="submit">Get Weather</button>
    </form>

    <?php
    if (isset($_GET['city'])) {
        $city = htmlspecialchars($_GET['city']);
        $url = "https://api.openweathermap.org/data/2.5/weather?q={$city}&appid={$apiKey}&units=metric&lang=tr";

        // Initialize cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // in case of SSL error
        $response = curl_exec($ch);

        // Check for cURL errors
        if (curl_errno($ch)) {
            echo "<p>cURL error: " . curl_error($ch) . "</p>";
        } else {
            $data = json_decode($response, true);

            if (isset($data['cod']) && $data['cod'] != 200) {
                echo "<p>Error: " . $data['message'] . "</p>";
            } else {
                echo "<h2>Weather in " . $data['name'] . "</h2>";
                echo "<p>Temperature: " . $data['main']['temp'] . " °C</p>";
                echo "<p>Condition: " . $data['weather'][0]['description'] . "</p>";
                echo "<p>Humidity: " . $data['main']['humidity'] . "%</p>";

                $iconCode = $data['weather'][0]['icon'];
                $iconUrl = "https://openweathermap.org/img/wn/{$iconCode}@2x.png";
                echo "<img src='$iconUrl' alt='Weather icon'>";
            }
        }

        curl_close($ch);
    }
    ?>
</body>
</html>
