<?php
if (http_response_code() !== 404 || !preg_match('/(overflow|empty-page)$/', $GLOBALS['case'])) {
    throw new RuntimeException('Unexpected 404 for ' . ($GLOBALS['case'] ?? 'unknown'));
}
echo "PASS: {$GLOBALS['case']} returned HTTP 404\n";
