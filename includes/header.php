<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$userName = $isLoggedIn ? $_SESSION['user_name'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle : 'Study Groups - Online Discussion Platform'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script defer>
        document.addEventListener("DOMContentLoaded", function () {
            const toggleButton = document.getElementById("theme-toggle");
            const body = document.body;
            
            if (localStorage.getItem("theme") === "dark") {
                body.classList.add("dark");
            }

            toggleButton.addEventListener("click", function () {
                body.classList.toggle("dark");
                if (body.classList.contains("dark")) {
                    localStorage.setItem("theme", "dark");
                } else {
                    localStorage.setItem("theme", "light");
                }
            });
        });
    </script>
    <style>
        .dark {
            background-color: rgb(123, 163, 244);
            color: hwb(0 100% 0%);
        }
        .dark nav {
            background-color: hwb(226 21% 9%);
        }
        .dark .bg-white {
            background-color: #2d3748;
            color: hwb(197 75% 22%);
        }
        .dark .bg-gray-200 {
            background-color: #a5c1f2;
        }
    </style>
    <?php if (isset($extraStyles)) echo $extraStyles; ?>
</head>
<body class="bg-gray-100">

    <!-- Navigation Bar -->
    <nav class="bg-blue-600 p-4">
        <div class="container mx-auto flex justify-between items-center">
            <a href="index.php" class="text-white text-xl font-bold">Study Groups</a>
            <div class="space-x-4">
                <a href="index.php" class="text-white">Home</a>
                <a href="about.php" class="text-white">About</a>
                <a href="groups.php" class="text-white">Groups</a>
                <a href="discussions.php" class="text-white">Discussions</a>
                <a href="resources.php" class="text-white">Resources</a>
                <a href="contact.php" class="text-white">Contact</a>
                <?php if ($isLoggedIn): ?>
                    <span class="text-white">Hello, <?php echo htmlspecialchars($userName); ?></span>
                    <a href="logout.php" class="text-white">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="text-white">Login</a>
                    <a href="register.php" class="text-white">Register</a>
                <?php endif; ?>
                <button id="theme-toggle" class="text-white bg-blue-800 px-4 py-2 rounded">Toggle Theme</button>
            </div>
        </div>
    </nav> 