<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load API key from .env
$env = parse_ini_file('.env');
$apiKey = $env['API_KEY'] ?? null;

if (!$apiKey) {
    die("API key not found. Please check your .env file.");
}

$weatherMain = 'default';
$bgClass = 'default';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Weather App</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="<?= $bgClass ?>">
    <div class="container">
        <h1>Weather App</h1>

        <!-- Search Form -->
        <form method="GET" action="">
            <input type="text" name="city" placeholder="Enter city name" required>
            <button type="submit">Get Weather</button>
        </form>

        <?php
        if (isset($_GET['city'])) {
            $city = htmlspecialchars($_GET['city']);
            $url = "https://api.openweathermap.org/data/2.5/weather?q={$city}&appid={$apiKey}&units=metric&lang=en";

            // Initialize cURL
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // in case of SSL error
            $response = curl_exec($ch);

            // Check for cURL errors
            if (curl_errno($ch)) {
                echo "<div class='weather-info error'>cURL Error: " . curl_error($ch) . "</div>";
            } else {
                $data = json_decode($response, true);

                if (isset($data['cod']) && $data['cod'] != 200) {
                    echo "<div class='weather-info error'>Error: " . ucfirst($data['message']) . "</div>";
                } else {
                    // Determine weather type and background class
                    $weatherMain = strtolower($data['weather'][0]['main']);
                    $bgClass = $weatherMain;

                    // Save to session history
                    if (!isset($_SESSION['history'])) {
                        $_SESSION['history'] = [];
                    }
                    if (!in_array($city, $_SESSION['history'])) {
                        $_SESSION['history'][] = $city;
                    }

                    // Display weather info
                    $description = ucfirst($data['weather'][0]['description']);
                    echo "<script>
                        window.onload = function () {
                            document.body.className = '$bgClass';
                            const animDiv = document.querySelector('.weather-animation');
                            if (animDiv) {
                                animDiv.classList.add('$bgClass');
                            }
                        };
                    </script>";

                    echo "<div class='weather-info'>";
                    echo "<h2>Weather in " . $data['name'] . "</h2>";
                    echo "<p>Temperature: " . $data['main']['temp'] . " °C</p>";
                    echo "<p>Condition: $description</p>";
                    echo "<p>Humidity: " . $data['main']['humidity'] . "%</p>";

                    $iconCode = $data['weather'][0]['icon'];
                    $iconUrl = "https://openweathermap.org/img/wn/{$iconCode}@2x.png";
                    echo "<img src='$iconUrl' alt='Weather icon'>";
                    echo "</div>";
                }
            }

            curl_close($ch);
        }
        ?>

        <!-- Search history -->
        <?php if (!empty($_SESSION['history'])): ?>
            <div class="weather-info history">
                <h3>Recent Searches:</h3>
                <ul>
                    <?php foreach (array_reverse($_SESSION['history']) as $searched): ?>
                        <li><?= htmlspecialchars($searched) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Clear history button -->
        <form method="POST" style="margin-top: 10px;">
            <button type="submit" name="clear_history">Clear Search History</button>
        </form>

        <?php
        if (isset($_POST['clear_history'])) {
            unset($_SESSION['history']);
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }
        ?>
    </div>

    <!-- Background animation layers -->
    <div class="weather-animation <?= $bgClass ?>">
        <?php if ($bgClass === 'clear'): ?>
            <div class="sun"></div>
        <?php elseif ($bgClass === 'rain'): ?>
            <?php for ($i = 0; $i < 30; $i++): ?>
                <div class="raindrop"></div>
            <?php endfor; ?>
        <?php elseif ($bgClass === 'clouds'): ?>
            <div class="cloud cloud-1"></div>
            <div class="cloud cloud-2"></div>
        <?php endif; ?>
    </div>

    <!-- Loading Indicator Script -->
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const form = document.querySelector("form");
            const container = document.querySelector(".container");

            form.addEventListener("submit", function () {
                const loadingDiv = document.createElement("p");
                loadingDiv.textContent = "Loading weather data...";
                loadingDiv.id = "loading-message";
                loadingDiv.style.marginTop = "15px";
                loadingDiv.style.color = "#555";
                container.appendChild(loadingDiv);
            });
        });

        window.addEventListener("load", () => {
            const loading = document.getElementById("loading-message");
            if (loading) {
                loading.remove();
            }
        });
    </script>
</body>
</html>
