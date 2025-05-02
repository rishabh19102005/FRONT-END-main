<?php
// Display header
echo "<h2>Updating Event Links</h2>";

// Get the content of index.php
$indexFile = file_get_contents('index.php');

if ($indexFile) {
    // Check if we need to replace links
    if (strpos($indexFile, 'href="web_bootcamp.php"') === false) {
        // Replace event links
        $indexFile = preg_replace(
            '/<a href="[^"]*" class="[^"]*">Join Now<\/a>/',
            '<a href="web_bootcamp.php" class="bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700">Join Now</a>',
            $indexFile,
            1
        );
        
        $indexFile = preg_replace(
            '/<a href="[^"]*" class="[^"]*">Register<\/a>/',
            '<a href="web_bootcamp.php#register" class="bg-gray-200 text-gray-800 px-3 py-1 rounded hover:bg-gray-300">Register</a>',
            $indexFile,
            1
        );
        
        // Update the second event (AI & ML)
        $pattern = '/<div class="[^"]*">[\s\S]*?<h3[^>]*>AI & ML Workshop<\/h3>[\s\S]*?<a href="[^"]*"[^>]*>Join Now<\/a>/';
        $replacement = preg_replace(
            '/<a href="[^"]*"([^>]*)>Join Now<\/a>/',
            '<a href="ai_ml_workshop.php"$1>Join Now</a>',
            preg_match($pattern, $indexFile, $matches) ? $matches[0] : ''
        );
        
        if ($replacement) {
            $indexFile = str_replace($matches[0], $replacement, $indexFile);
        }
        
        // Update the Register link for AI & ML
        $pattern = '/<div class="[^"]*">[\s\S]*?<h3[^>]*>AI & ML Workshop<\/h3>[\s\S]*?<a href="[^"]*"[^>]*>Register<\/a>/';
        $replacement = preg_replace(
            '/<a href="[^"]*"([^>]*)>Register<\/a>/',
            '<a href="ai_ml_workshop.php#register"$1>Register</a>',
            preg_match($pattern, $indexFile, $matches) ? $matches[0] : ''
        );
        
        if ($replacement) {
            $indexFile = str_replace($matches[0], $replacement, $indexFile);
        }
        
        // Write the updated content back to the file
        if (file_put_contents('index.php', $indexFile)) {
            echo "Successfully updated event links in index.php<br>";
        } else {
            echo "Failed to write to index.php. Check file permissions.<br>";
        }
    } else {
        echo "Links already updated in index.php<br>";
    }
} else {
    echo "Could not read index.php. Make sure the file exists and is readable.<br>";
}

echo "<p>Link update process complete. You may now visit the homepage to see the updated event links.</p>";
echo "<p><a href='index.php'>Go to homepage</a></p>";
?> 