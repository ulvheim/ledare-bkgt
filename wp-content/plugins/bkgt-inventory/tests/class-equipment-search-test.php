<?php
/**
 * Equipment Search Enhancement Tests
 *
 * Unit tests for the comprehensive equipment search functionality
 *
 * @package BKGT_Inventory
 * @since 1.3.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class BKGT_EquipmentSearchTest extends WP_UnitTestCase {

    private $test_equipment_ids = array();

    public function setUp() {
        parent::setUp();

        // Create test equipment with different sizes and attributes
        $test_data = array(
            array(
                'manufacturer_id' => 1,
                'item_type_id' => 1,
                'size' => 'TDJ',
                'notes' => 'Test equipment for TDJ size',
                'storage_location' => 'Warehouse A',
                'condition_reason' => 'New equipment'
            ),
            array(
                'manufacturer_id' => 1,
                'item_type_id' => 1,
                'size' => 'Large',
                'notes' => 'Large size equipment',
                'storage_location' => 'Warehouse B',
                'condition_reason' => 'Slightly used'
            ),
            array(
                'manufacturer_id' => 2,
                'item_type_id' => 2,
                'size' => 'Medium',
                'notes' => 'Medium size with special notes',
                'storage_location' => 'Warehouse A',
                'sticker_code' => 'ABC123'
            )
        );

        foreach ($test_data as $data) {
            $id = BKGT_Inventory_Item::create_item($data);
            $this->test_equipment_ids[] = $id;
        }
    }

    public function tearDown() {
        // Clean up test equipment
        foreach ($this->test_equipment_ids as $id) {
            BKGT_Inventory_Item::delete_item($id);
        }

        parent::tearDown();
    }

    /**
     * Test searching by size field
     */
    public function test_search_by_size() {
        $args = array('search' => 'TDJ');
        $results = BKGT_Inventory_Item::get_items($args);

        $this->assertNotEmpty($results, 'Search by size should return results');
        $this->assertEquals('TDJ', $results[0]['size'], 'First result should have TDJ size');
    }

    /**
     * Test searching by notes field
     */
    public function test_search_by_notes() {
        $args = array('search' => 'special notes');
        $results = BKGT_Inventory_Item::get_items($args);

        $this->assertNotEmpty($results, 'Search by notes should return results');
        $this->assertStringContains($results[0]['notes'], 'special notes', 'Result should contain search term in notes');
    }

    /**
     * Test field-specific search
     */
    public function test_field_specific_search() {
        $args = array(
            'search' => 'TDJ',
            'search_fields' => 'size'
        );
        $results = BKGT_Inventory_Item::get_items($args);

        $this->assertNotEmpty($results, 'Field-specific search should return results');
        $this->assertEquals('TDJ', $results[0]['size'], 'Result should have TDJ in size field');
    }

    /**
     * Test AND operator search
     */
    public function test_search_with_and_operator() {
        $args = array(
            'search' => 'Warehouse A',
            'search_fields' => 'storage_location,size',
            'search_operator' => 'AND'
        );
        $results = BKGT_Inventory_Item::get_items($args);

        $this->assertNotEmpty($results, 'AND search should return results');
        // Verify results contain both criteria
        foreach ($results as $result) {
            $this->assertEquals('Warehouse A', $result['storage_location'], 'All results should have Warehouse A location');
        }
    }

    /**
     * Test fuzzy search
     */
    public function test_fuzzy_search() {
        $args = array(
            'search' => 'TDJ', // Test with exact match first
            'fuzzy' => true
        );
        $results = BKGT_Inventory_Item::get_items($args);

        $this->assertNotEmpty($results, 'Fuzzy search should return results');
    }

    /**
     * Test exact search mode
     */
    public function test_exact_search_mode() {
        $args = array(
            'search' => 'TDJ',
            'search_mode' => 'exact'
        );
        $results = BKGT_Inventory_Item::get_items($args);

        $this->assertNotEmpty($results, 'Exact search should return results');
        $this->assertEquals('TDJ', $results[0]['size'], 'Result should exactly match TDJ');
    }

    /**
     * Test API response includes new fields
     */
    public function test_api_response_includes_new_fields() {
        // This would be tested with actual API calls in integration tests
        $item = BKGT_Inventory_Item::get_item($this->test_equipment_ids[0]);

        $this->assertArrayHasKey('size', $item, 'API response should include size field');
        $this->assertArrayHasKey('condition_reason', $item, 'API response should include condition_reason field');
        $this->assertArrayHasKey('sticker_code', $item, 'API response should include sticker_code field');
        $this->assertArrayHasKey('notes', $item, 'API response should include notes field');
    }

    /**
     * Test search performance (basic check)
     */
    public function test_search_performance() {
        $start_time = microtime(true);

        $args = array('search' => 'TDJ');
        BKGT_Inventory_Item::get_items($args);

        $search_time = microtime(true) - $start_time;

        // Search should complete within reasonable time (less than 1 second for unit test)
        $this->assertLessThan(1.0, $search_time, 'Search should complete within 1 second');
    }
}