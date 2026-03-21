<?php
// Clear OpCache
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "OpCache cleared successfully!\n";
} else {
    echo "OpCache not enabled.\n";
}

// Clear file stat cache
clearstatcache(true);
echo "File stat cache cleared!\n";

echo "\nNow try accessing the API again.";
