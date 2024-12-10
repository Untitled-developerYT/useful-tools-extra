<?php
// Handle settings update via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['updateSettings'])) {
    setcookie('botToken', $_POST['botToken'], time() + (86400 * 30), '/');
    setcookie('channelID', $_POST['channelID'], time() + (86400 * 30), '/');
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Read cookies into variables
$botToken = $_COOKIE['botToken'] ?? '';
$channelId = $_COOKIE['channelID'] ?? '';

// Handle API actions
if (isset($_GET['action'])) {
    header("Content-Type: application/json");

    if ($botToken && $channelId) {
        $curl = curl_init();

        if ($_GET['action'] === 'fetch') {
            // Fetch messages from Discord
            curl_setopt_array($curl, [
                CURLOPT_URL => "https://discord.com/api/v10/channels/$channelId/messages",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    "Authorization: Bot $botToken",
                    "Content-Type: application/json",
                ],
            ]);
        } elseif ($_GET['action'] === 'send') {
            // Send a message to Discord
            $message = json_encode(["content" => $_POST['message']]);
            curl_setopt_array($curl, [
                CURLOPT_URL => "https://discord.com/api/v10/channels/$channelId/messages",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $message,
                CURLOPT_HTTPHEADER => [
                    "Authorization: Bot $botToken",
                    "Content-Type: application/json",
                ],
            ]);
        }

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if (in_array($httpCode, [200, 201])) {
            echo $response;
        } else {
            echo json_encode(["error" => "Failed to process request", "status" => $httpCode]);
        }
    } else {
        echo json_encode(["error" => "Bot token or channel ID not set."]);
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Random number gen</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-image: url('../assets/background.jpg');
            background-repeat: no-repeat;
            background-size: cover;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .container {
            display: flex;
            flex-direction: column;
            height: 100vh;
            max-width: 80%;
            position: relative;
            background-color: #fff;
            overflow-y: auto;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        #messageContainer {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            display: flex;
            flex-direction: column-reverse;
            background-color: #fafafa;
        }
        p {
            margin: 10px 0;
            padding: 10px;
            border-radius: 10px;
            background-color: #e0e0e0;
            word-wrap: break-word;
        }
        p strong {
            color: #007bff;
        }
        form {
            display: flex;
            flex-shrink: 0;
            padding: 10px;
            align-items: stretch;
            gap: 10px; /* Add spacing between items */
            justify-content: space-between;
            background-color: #f1f1f1;
            flex-direction: column;
            box-sizing: border-box;
        }
        form input {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-right: 5px;
        }
        form button {
            padding: 10px 15px;
            background-color: #007bff;
            border: none;
            color: #fff;
            border-radius: 5px;
            cursor: pointer;
            white-space: nowrap;
        }
        form button:hover {
            background-color: #0056b3;
        }
        label {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
        }
        .ad-container {
            width: 100%;
            border: 1px solid #ddd;
            overflow: hidden;
            position: relative;
            background-color: #f4f4f4;
            display: flex;
            flex-direction: column;
            height: 100%;
            max-width: 20%;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .ad-rotator {
            width: 100%;
            border: 1px solid #ddd;
            overflow: hidden;
            position: relative;
            background-color: #f4f4f4;
            display: flex;
            flex-direction: column;
            height: 100%;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .popup {
            display: none; /* Initially hidden */
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 400px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            overflow-y: auto;
            z-index: 1000;
            max-height: 80vh;
        }

        .popup-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 15px;
            background-color: #007bff;
            color: white;
        }

        .popup-header h3 {
            margin: 0;
            font-size: 18px;
        }

        .popup-header button {
            background: none;
            border: none;
            color: white;
            font-size: 18px;
            cursor: pointer;
        }

        .popup-tabs {
            display: flex;
            background-color: #f1f1f1;
            border-bottom: 1px solid #ddd;
        }

        .popup-tab {
            flex: 1;
            padding: 10px;
            text-align: center;
            cursor: pointer;
            background-color: #f1f1f1;
            border-right: 1px solid #ddd;
            transition: background-color 0.3s ease;
        }

        .popup-tab:last-child {
            border-right: none;
        }

        .popup-tab.active {
            background-color: #fff;
            font-weight: bold;
        }

        .popup-content {
            padding: 15px;
            overflow-y: auto;
            display: none;
        }

        .popup-content.active {
            display: block;
            overflow-y: auto;
        }

        .overlay {
            display: none; /* Initially hidden */
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }
        .footer {
            background-color: #f0f0f0;
            text-align: center;
            padding: 10px;
        }

        .ad-container iframe,
        .ad-container img,
        .ad-container video {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        @media (max-width: 600px) {
            form {
                flex-direction: column; /* Switch to vertical layout */
                align-items: stretch; /* Make inputs take full width */
            }

            form input,
            form button {
                width: 100%; /* Full width for both input and button */
            }
        
        }
    </style>
</head>
<body>
<div class="container">
<p style="font-size: 40px;">Random numbers are essential in various fields and applications because they provide unpredictability and fairness. In computer science, they are crucial for algorithms in cryptography, ensuring secure data encryption by making it nearly impossible to predict keys. In gaming, random numbers enhance user experience by creating unpredictability in outcomes, such as rolling dice or spawning items. Simulations and statistical modeling rely on random numbers to mimic real-world phenomena, enabling accurate testing and forecasting. Additionally, in decision-making processes, they eliminate bias by randomly selecting options or participants. This versatility makes random numbers indispensable across technology, science, and everyday problem-solving. You should use our generator!</p>
<form id="randIntForm">
            <input type="number" id="randIntInput" placeholder="Type a number..." required>
            <button type="submit">Send</button>
</form>
<p id="int"></p>
<div class="footer">
<button id="openPopup" style="background: url('../assets/icon.png') no-repeat center center; background-size: cover; width: 100px; height: 50px; border: none; cursor: pointer;" aria-label="Click me"></button>
<p>Icon by O.moonstd</p>
</div>
<!-- Overlay -->
<div class="overlay" id="popupOverlay"></div>
</div>
<div class="popup" id="popup">
    <div class="popup-header">
        <h3>Popup Tabs</h3>
        <button id="closePopup">&times;</button>
    </div>
    <div class="popup-tabs">
        <div class="popup-tab active" data-tab="tab1">Tab 1</div>
        <div class="popup-tab" data-tab="tab2">Tab 2</div>
        <div class="popup-tab" data-tab="tab3">Tab 3</div>
    </div>
    <div class="popup-content active" id="tab1">
           

        <div id="messageContainer">

            <!-- Messages will be dynamically added here -->
        </div>
        <form id="sendMessageForm">
            <input type="text" id="messageInput" placeholder="Type a message..." required>
            <button type="submit">Send</button>
        </form>
    </div>
    <div class="popup-content" id="tab2">
        <form method="POST">
            <label for="botToken">Bot Token:</label><br>
            <input type="password" id="botToken" name="botToken" value="<?= htmlspecialchars($botToken) ?>" required><br><br>
            <label for="channelID">Channel ID:</label><br>
            <input type="text" id="channelID" name="channelID" value="<?= htmlspecialchars($channelId) ?>" required><br><br>
            <button type="submit" name="updateSettings">Save Settings</button>
        </form>
    </div>
    <div class="popup-content" id="tab3">
        <div class="ad-container">
            <div id="adRotator" class="ad-rotator"></div>
        </div>
    </div>
</div>






    <script>
        const messageContainer = document.getElementById("messageContainer");
        const messageForm = document.getElementById("sendMessageForm");
        const messageInput = document.getElementById("messageInput");
        const popup = document.getElementById('popup');
        const overlay = document.getElementById('popupOverlay');
        const openButton = document.getElementById('openPopup');
        const closeButton = document.getElementById('closePopup');
        const tabs = document.querySelectorAll('.popup-tab');
        const contents = document.querySelectorAll('.popup-content');

        // Show Popup
        openButton.addEventListener('click', () => {
            popup.style.display = 'block';
            overlay.style.display = 'block';
        });

    // Close Popup
        closeButton.addEventListener('click', () => {
            popup.style.display = 'none';
            overlay.style.display = 'none';
        });

        overlay.addEventListener('click', () => {
            popup.style.display = 'none';
            overlay.style.display = 'none';
        });

    // Tab Switching
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
            // Remove active class from all tabs and contents
                tabs.forEach(t => t.classList.remove('active'));
                contents.forEach(c => c.classList.remove('active'));

            // Add active class to the clicked tab and corresponding content
                tab.classList.add('active');
                document.getElementById(tab.dataset.tab).classList.add('active');
            });
        });
        // Function to fetch messages
        function fetchMessages() {
            fetch("?action=fetch")
                .then(response => response.json())
                .then(data => {
                    messageContainer.innerHTML = ""; // Clear existing messages
                    data.forEach(message => {
                        const p = document.createElement("p");
                        p.innerHTML = `<strong>${message.author.username}:</strong> ${message.content}`;
                        messageContainer.appendChild(p);
                    });
                })
                .catch(error => console.error("Error fetching messages:", error));
        }

        // Function to send a message
        function sendMessage(content) {
            const formData = new FormData();
            formData.append("message", content);

            fetch("?action=send", {
                method: "POST",
                body: formData,
            })
                .then(response => response.json())
                .then(data => {
                    console.log("Message sent:", data);
                    fetchMessages(); // Refresh messages after sending
                })
                .catch(error => console.error("Error sending message:", error));
        }

        // Event listener for form submission
        messageForm.addEventListener("submit", event => {
            event.preventDefault(); // Prevent form from reloading the page
            const content = messageInput.value.trim();
            if (content) {
                sendMessage(content); // Send the message
                messageInput.value = ""; // Clear the input
            }
        });

        // Fetch messages every 5 seconds
        setInterval(fetchMessages, 5000);
        fetchMessages(); // Initial fetch

        function getRandomInt(max) {
  return Math.floor(Math.random() * max) + 1;
}

randIntForm.addEventListener("submit", event => {
            event.preventDefault(); // Prevent form from reloading the page
            const content = randIntInput.value.trim();
            if (content) {
                int.innerHTML = getRandomInt(content); // Send the message
                randIntInput.value = ""; // Clear the input
            }
        });



// ADS
                // Define the ads: an array of objects with type and source
                const ads = [
           /* { type: 'image', src: '../assets/background.jpg' }, */
           /* { type: 'video', src: 'https://www.w3schools.com/html/mov_bbb.mp4' }, */
            { type: 'iframe', src: 'https://discord.com/widget?id=1277599930621366312&theme=dark' },
        ];

        let currentAdIndex = 0; // Start with the first ad
        const adRotator = document.getElementById("adRotator");

        // Function to display the current ad
        function displayAd() {
            const ad = ads[currentAdIndex];
            adRotator.innerHTML = ""; // Clear the previous ad

            if (ad.type === "image") {
                const img = document.createElement("img");
                img.src = ad.src;
                img.alt = "Advertisement";
                adRotator.appendChild(img);
            } else if (ad.type === "video") {
                const video = document.createElement("video");
                video.src = ad.src;
                video.autoplay = true;
                video.muted = true;
                video.loop = true;
                adRotator.appendChild(video);
            } else if (ad.type === "iframe") {
                const iframe = document.createElement("iframe");
                iframe.src = ad.src;
                iframe.allowFullscreen = true;
                iframe.height = "100%"
                iframe.sandbox = "allow-popups allow-popups-to-escape-sandbox allow-same-origin allow-scripts"
                adRotator.appendChild(iframe);
            }
            

            // Move to the next ad, looping back to the start if needed
            currentAdIndex = (currentAdIndex + 1) % ads.length;
        }

        // Start rotating ads every 5 seconds
        displayAd(); // Display the first ad immediately
        setInterval(displayAd, 6000); // Change ads every 5 seconds
    </script>
</body>
</html>
