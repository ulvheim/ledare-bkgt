<?php
require_once('wp-load.php');

echo "=== CRUD Operations Testing ===\n\n";

global $wpdb;
$tests_passed = 0;
$total_tests = 0;

// Test 1: Team CRUD
echo "Testing Team CRUD operations:\n";
$total_tests += 4;

// Create
$team_id = wp_insert_post(array(
    'post_title' => 'Test Team CRUD',
    'post_type' => 'bkgt_team',
    'post_status' => 'publish'
));
if ($team_id) {
    echo "  ✅ Create: Team created (ID: $team_id)\n";
    $tests_passed++;
} else {
    echo "  ❌ Create: Failed to create team\n";
}

// Read
$team = get_post($team_id);
if ($team && $team->post_title == 'Test Team CRUD') {
    echo "  ✅ Read: Team retrieved successfully\n";
    $tests_passed++;
} else {
    echo "  ❌ Read: Failed to retrieve team\n";
}

// Update
$update_result = wp_update_post(array(
    'ID' => $team_id,
    'post_title' => 'Test Team CRUD Updated'
));
if ($update_result) {
    echo "  ✅ Update: Team updated successfully\n";
    $tests_passed++;
} else {
    echo "  ❌ Update: Failed to update team\n";
}

// Delete
$delete_result = wp_delete_post($team_id, true);
if ($delete_result) {
    echo "  ✅ Delete: Team deleted successfully\n";
    $tests_passed++;
} else {
    echo "  ❌ Delete: Failed to delete team\n";
}

// Test 2: Document CRUD
echo "\nTesting Document CRUD operations:\n";
$total_tests += 4;

// Create
$doc_id = wp_insert_post(array(
    'post_title' => 'Test Document CRUD',
    'post_type' => 'bkgt_document',
    'post_status' => 'publish'
));
if ($doc_id) {
    echo "  ✅ Create: Document created (ID: $doc_id)\n";
    $tests_passed++;
} else {
    echo "  ❌ Create: Failed to create document\n";
}

// Read
$doc = get_post($doc_id);
if ($doc && $doc->post_title == 'Test Document CRUD') {
    echo "  ✅ Read: Document retrieved successfully\n";
    $tests_passed++;
} else {
    echo "  ❌ Read: Failed to retrieve document\n";
}

// Update
$update_result = wp_update_post(array(
    'ID' => $doc_id,
    'post_title' => 'Test Document CRUD Updated'
));
if ($update_result) {
    echo "  ✅ Update: Document updated successfully\n";
    $tests_passed++;
} else {
    echo "  ❌ Update: Failed to update document\n";
}

// Delete
$delete_result = wp_delete_post($doc_id, true);
if ($delete_result) {
    echo "  ✅ Delete: Document deleted successfully\n";
    $tests_passed++;
} else {
    echo "  ❌ Delete: Failed to delete document\n";
}

// Test 3: Offboarding CRUD
echo "\nTesting Offboarding CRUD operations:\n";
$total_tests += 4;

// Create
$offboard_id = wp_insert_post(array(
    'post_title' => 'Test Offboarding CRUD',
    'post_type' => 'bkgt_offboarding',
    'post_status' => 'publish'
));
if ($offboard_id) {
    echo "  ✅ Create: Offboarding process created (ID: $offboard_id)\n";
    $tests_passed++;
} else {
    echo "  ❌ Create: Failed to create offboarding process\n";
}

// Read
$offboard = get_post($offboard_id);
if ($offboard && $offboard->post_title == 'Test Offboarding CRUD') {
    echo "  ✅ Read: Offboarding process retrieved successfully\n";
    $tests_passed++;
} else {
    echo "  ❌ Read: Failed to retrieve offboarding process\n";
}

// Update
$update_result = wp_update_post(array(
    'ID' => $offboard_id,
    'post_title' => 'Test Offboarding CRUD Updated'
));
if ($update_result) {
    echo "  ✅ Update: Offboarding process updated successfully\n";
    $tests_passed++;
} else {
    echo "  ❌ Update: Failed to update offboarding process\n";
}

// Delete
$delete_result = wp_delete_post($offboard_id, true);
if ($delete_result) {
    echo "  ✅ Delete: Offboarding process deleted successfully\n";
    $tests_passed++;
} else {
    echo "  ❌ Delete: Failed to delete offboarding process\n";
}

echo "\n=== CRUD Test Results ===\n";
echo "Tests passed: $tests_passed/$total_tests\n";

if ($tests_passed == $total_tests) {
    echo "🎉 ALL CRUD TESTS PASSED - Data operations working!\n";
} elseif ($tests_passed >= $total_tests * 0.75) {
    echo "⚠️ MOST CRUD TESTS PASSED - Minor issues detected\n";
} else {
    echo "❌ CRITICAL CRUD ISSUES - Data operations failing\n";
}
?>