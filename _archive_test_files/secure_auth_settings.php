    /**
     * Add authentication settings to admin
     */
    public function add_authentication_settings($settings) {
        $settings['authentication'] = array(
            'title' => __('Authentication', 'bkgt-data-scraping'),
            'fields' => array(
                'bkgt_scraping_username' => array(
                    'label' => __('Svenskalag.se Username', 'bkgt-data-scraping'),
                    'type' => 'text',
                    'description' => __('Username for svenskalag.se login', 'bkgt-data-scraping')
                ),
                'bkgt_scraping_password' => array(
                    'label' => __('Svenskalag.se Password', 'bkgt-data-scraping'),
                    'type' => 'password',
                    'description' => __('Password for svenskalag.se login (stored securely)', 'bkgt-data-scraping')
                )
            )
        );

        return $settings;
    }

    /**
     * Securely store password using WordPress salts
     */
    private function store_password($password) {
        if (empty($password)) {
            return '';
        }

        // Use WordPress salts for encryption
        $salt = wp_salt('auth');
        return base64_encode(openssl_encrypt($password, 'aes-256-cbc', $salt, 0, substr($salt, 0, 16)));
    }

    /**
     * Decrypt stored password
     */
    private function get_password() {
        $encrypted = get_option('bkgt_scraping_password');
        if (empty($encrypted)) {
            return '';
        }

        $salt = wp_salt('auth');
        return openssl_decrypt(base64_decode($encrypted), 'aes-256-cbc', $salt, 0, substr($salt, 0, 16));
    }