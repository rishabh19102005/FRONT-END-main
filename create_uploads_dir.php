<?php
// Display header
echo "Creating upload directories...<br>";

// Define directories to create
$directories = [
    'uploads',
    'uploads/profiles',
    'uploads/group_materials',
    'uploads/resources',
    'uploads/discussions'
];

// Create each directory if it doesn't exist
foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        if (mkdir($dir, 0755, true)) {
            echo "Created directory: $dir<br>";
        } else {
            echo "Failed to create directory: $dir<br>";
        }
    } else {
        echo "Directory already exists: $dir<br>";
    }
}

echo "<br>Setup complete!<br>";
echo "<a href='index.php'>Return to homepage</a>";
?> 