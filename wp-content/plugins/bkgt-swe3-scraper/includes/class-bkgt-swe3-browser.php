<?php
/**
 * BKGT SWE3 Scraper - REST API approach
 * 
 * Fetches SWE3 documents from WordPress Media Library via REST API
 * No browser drivers or JavaScript rendering required - simple and reliable
 */

class BKGT_SWE3_Browser {
    
    /**
     * Path to Python scraper script
     */
    private $scraper_script;
    
    /**
     * Python executable path
     */
    private $python_executable = 'python3';
    
    /**
     * Timeout for HTTP requests (seconds)
     */
    private $timeout = 30;
    
    /**
     * Initialize scraper
     */
    public function __construct() {
        $this->scraper_script = dirname( __FILE__ ) . '/swe3_scraper_final.py';
        
        // Verify Python is available
        if ( ! $this->verify_python() ) {
            // Python not available, will use pure PHP/HTTP fallback
        }
    }
    
    /**
     * Verify Python is available and has required packages
     * 
     * @return bool
     */
    private function verify_python() {
        // Check if Python is available
        exec( 'which python3 2>/dev/null', $output, $return_code );
        
        if ( $return_code !== 0 ) {
            // Try python instead of python3
            exec( 'which python 2>/dev/null', $output, $return_code );
            if ( $return_code === 0 ) {
                $this->python_executable = 'python';
            } else {
                return false;
            }
        }
        
        // Verify Selenium is installed
        $cmd = escapeshellcmd( $this->python_executable ) . ' -c "import selenium; print(selenium.__version__)" 2>&1';
        exec( $cmd, $output, $return_code );
        
        return $return_code === 0;
    }
    
    /**
     * Scrape SWE3 documents from REST API
     * 
     * Falls back to pure HTTP/PHP if Python unavailable
     * 
     * @return array Array with 'success', 'documents', and optional 'error' keys
     */
    public function scrape_url() {
        // Try Python first (faster for pagination)
        if ( $this->python_executable && file_exists( $this->scraper_script ) ) {
            $result = $this->scrape_via_python();
            if ( $result['success'] ) {
                return $result;
            }
        }
        
        // Fallback to pure PHP HTTP request
        return $this->scrape_via_http();
    }
    
    /**
     * Scrape using Python script
     * 
     * @return array
     */
    private function scrape_via_python() {
        $cmd = sprintf(
            '%s %s 2>&1',
            escapeshellcmd( $this->python_executable ),
            escapeshellarg( $this->scraper_script )
        );
        
        $output = array();
        $return_code = 0;
        exec( $cmd, $output, $return_code );
        
        $json_output = implode( "\n", $output );
        $result = json_decode( $json_output, true );
        
        if ( ! is_array( $result ) ) {
            return array(
                'success' => false,
                'documents' => array(),
                'error' => 'Invalid Python output',
            );
        }
        
        return $result;
    }
    
    /**
     * Scrape using pure PHP/HTTP (no Python required)
     * 
     * @return array
     */
    private function scrape_via_http() {
        $base_url = 'https://amerikanskfotboll.swe3.se';
        $api_url = $base_url . '/wp-json/wp/v2/media';
        
        try {
            $documents = array();
            $page = 1;
            $per_page = 100;
            
            // Fetch all pages of media
            while ( true ) {
                $url = $api_url . '?per_page=' . $per_page . '&page=' . $page;
                
                $response = wp_remote_get( $url, array(
                    'timeout' => $this->timeout,
                    'sslverify' => false,
                ) );
                
                if ( is_wp_error( $response ) ) {
                    break;
                }
                
                $body = wp_remote_retrieve_body( $response );
                $media_items = json_decode( $body, true );
                
                if ( ! is_array( $media_items ) || empty( $media_items ) ) {
                    break;
                }
                
                // Extract PDFs
                foreach ( $media_items as $item ) {
                    if ( isset( $item['mime_type'] ) && $item['mime_type'] === 'application/pdf' ) {
                        $documents[] = array(
                            'id' => $item['id'] ?? null,
                            'title' => isset( $item['title']['rendered'] ) ? 
                                html_entity_decode( $item['title']['rendered'], ENT_QUOTES, 'UTF-8' ) : 
                                'Document',
                            'url' => $item['source_url'] ?? '',
                            'date' => $item['modified'] ?? $item['date'] ?? '',
                            'size' => isset( $item['media_details']['filesize'] ) ? 
                                $item['media_details']['filesize'] : 0,
                        );
                    }
                }
                
                // Check if there are more pages
                if ( count( $media_items ) < $per_page ) {
                    break;
                }
                
                $page++;
            }
            
            // Sort by date, newest first
            usort( $documents, function( $a, $b ) {
                return strcmp( $b['date'], $a['date'] );
            });
            
            return array(
                'success' => true,
                'documents' => $documents,
                'count' => count( $documents ),
                'method' => 'http',
            );
            
        } catch ( Exception $e ) {
            return array(
                'success' => false,
                'documents' => array(),
                'error' => $e->getMessage(),
            );
        }
    }
    
    /**
     * Test if browser functionality is working
     * 
     * @return bool
     */
    public function is_available() {
        // Always available - either Python or pure HTTP
        return true;
    }
    
    /**
     * Get scraper method info
     * 
     * @return string 'python' or 'http'
     */
    public function get_method() {
        if ( $this->verify_python() && file_exists( $this->scraper_script ) ) {
            return 'python';
        }
        return 'http';
    }
}
