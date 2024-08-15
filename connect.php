<?php
require 'config.php';

function connectWithDb() {
    global $servername, $username, $password, $dbname;
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        echo "ERROR MySQL: Connect to Server\n";
    } else {
        echo "Success!\n";
        getLink($conn);
    }
}

function getLink($conn) {
    $url = readline("Enter your URL to shorten: ");

    $shortCode = generateShortLink();

    while (true) {
        $sql = "SELECT * FROM links WHERE new = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $shortCode);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            break; 
        }

        $shortCode = generateShortLink();
    }

    sendShortLinkToDb($url, $shortCode, $conn);
}

function generateShortLink() {
    global $length;
    return substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, $length);
}

function sendShortLinkToDb($url, $shortCode, $conn) {
    $sql = "INSERT INTO links (orginal, new) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $url, $shortCode);
    if ($stmt->execute()) {
        echo "Shortened URL: http://localhost/$shortCode\n";
        echo "URL before inserting: $url\n";
    } else {
        echo "Error: " . $stmt->error . "\n";
    }   

    getOrginalLinkFromDb($conn, $shortCode);
}

function getOrginalLinkFromDb($conn, $shortCode = null) {
    if (!$shortCode) {
        echo("Do you want to retrieve an original URL?\n");
        $check = readline("Enter Y or N\n");

        if ($check != "Y") {
            echo "Goodbye...\n";
            return;
        }

        $shortCode = readline("Enter the short code: ");
    }

    $sql = "SELECT orginal FROM links WHERE new = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $shortCode);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        header("Location: " . $row['orginal']);
        exit();
    } else {
        echo "Short link not found!\n";
    }
}

connectWithDb();
?>
