<?php
// Simple test to check if export headers work
echo "Testing export headers...\n";

// Test PDF headers
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="test.pdf"');
echo "%PDF-1.4\nTest PDF Content\n";
echo "PDF headers test completed.\n";

// Test Excel headers  
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="test.xls"');
echo "<table><tr><td>Test Excel Content</td></tr></table>";
echo "Excel headers test completed.\n";
?>
