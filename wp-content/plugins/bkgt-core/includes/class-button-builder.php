<?php

/**
 * BKGT Button Builder Class
 * 
 * Fluent API for creating BKGT buttons
 * Ensures consistent button markup across plugins
 * 
 * @package BKGT_Core
 * @subpackage Components
 */

class BKGT_Button_Builder {

    /**
     * Button attributes
     * 
     * @var array
     */
    private $attributes = [];

    /**
     * Button content (HTML)
     * 
     * @var string
     */
    private $content = '';

    /**
     * Button classes
     * 
     * @var array
     */
    private $classes = ['bkgt-btn'];

    /**
     * Constructor
     * 
     * @param string $text Initial button text
     */
    public function __construct( $text = 'Button' ) {
        $this->content = esc_html( $text );
    }

    /**
     * Static factory method
     * 
     * @param string $text Button text
     * @return self
     */
    public static function create( $text = 'Button' ) {
        return new self( $text );
    }

    /**
     * Set button variant (primary, secondary, danger, success, warning, info, text, outline)
     * 
     * @param string $variant Variant name
     * @return self
     */
    public function variant( $variant = 'primary' ) {
        // Remove existing variant classes
        $this->classes = array_filter( $this->classes, function( $class ) {
            return $class === 'bkgt-btn' || !preg_match( '/^bkgt-btn-/', $class );
        });

        $variant = sanitize_html_class( $variant );
        $this->classes[] = 'bkgt-btn-' . $variant;

        return $this;
    }

    /**
     * Alias for variant()
     * 
     * @param string $variant Variant name
     * @return self
     */
    public function primary() {
        return $this->variant( 'primary' );
    }

    /**
     * Alias for variant()
     * 
     * @return self
     */
    public function secondary() {
        return $this->variant( 'secondary' );
    }

    /**
     * Alias for variant()
     * 
     * @return self
     */
    public function danger() {
        return $this->variant( 'danger' );
    }

    /**
     * Alias for variant()
     * 
     * @return self
     */
    public function success() {
        return $this->variant( 'success' );
    }

    /**
     * Alias for variant()
     * 
     * @return self
     */
    public function warning() {
        return $this->variant( 'warning' );
    }

    /**
     * Alias for variant()
     * 
     * @return self
     */
    public function info() {
        return $this->variant( 'info' );
    }

    /**
     * Alias for variant()
     * 
     * @return self
     */
    public function outline() {
        return $this->variant( 'outline' );
    }

    /**
     * Alias for variant() - text button style
     * Renamed to avoid conflict with text($text) method
     * 
     * @return self
     */
    public function textVariant() {
        return $this->variant( 'text' );
    }

    /**
     * Set button size (sm, lg)
     * 
     * @param string $size Size name
     * @return self
     */
    public function size( $size = 'md' ) {
        // Remove existing size
        $this->classes = array_filter( $this->classes, function( $class ) {
            return $class !== 'bkgt-btn-sm' && $class !== 'bkgt-btn-lg';
        });

        if ( $size && 'md' !== $size ) {
            $size = sanitize_html_class( $size );
            $this->classes[] = 'bkgt-btn-' . $size;
        }

        return $this;
    }

    /**
     * Alias for size()
     * 
     * @return self
     */
    public function small() {
        return $this->size( 'sm' );
    }

    /**
     * Alias for size()
     * 
     * @return self
     */
    public function large() {
        return $this->size( 'lg' );
    }

    /**
     * Make button block (full width)
     * 
     * @param bool $isBlock Whether button should be block
     * @return self
     */
    public function block( $isBlock = true ) {
        if ( $isBlock ) {
            $this->classes[] = 'bkgt-btn-block';
        } else {
            $key = array_search( 'bkgt-btn-block', $this->classes );
            if ( false !== $key ) {
                unset( $this->classes[ $key ] );
            }
        }

        return $this;
    }

    /**
     * Set button type (submit, reset, button)
     * 
     * @param string $type Button type
     * @return self
     */
    public function type( $type = 'button' ) {
        $allowed_types = [ 'submit', 'reset', 'button' ];
        $type = in_array( $type, $allowed_types ) ? $type : 'button';
        $this->attributes['type'] = $type;

        return $this;
    }

    /**
     * Set button name
     * 
     * @param string $name Button name
     * @return self
     */
    public function name( $name ) {
        $this->attributes['name'] = sanitize_key( $name );
        return $this;
    }

    /**
     * Set button value
     * 
     * @param string $value Button value
     * @return self
     */
    public function value( $value ) {
        $this->attributes['value'] = esc_attr( $value );
        return $this;
    }

    /**
     * Set button ID
     * 
     * @param string $id HTML ID
     * @return self
     */
    public function id( $id ) {
        $this->attributes['id'] = sanitize_html_class( $id );
        return $this;
    }

    /**
     * Add custom attribute
     * 
     * @param string $name Attribute name
     * @param mixed $value Attribute value
     * @return self
     */
    public function attr( $name, $value ) {
        $name = sanitize_key( $name );
        
        // Special handling for data attributes
        if ( strpos( $name, 'data-' ) === 0 ) {
            $this->attributes[ $name ] = esc_attr( $value );
        } else {
            $this->attributes[ $name ] = esc_attr( $value );
        }

        return $this;
    }

    /**
     * Add data attribute
     * 
     * @param string $key Data key
     * @param mixed $value Data value
     * @return self
     */
    public function data( $key, $value ) {
        $key = sanitize_key( $key );
        $data_key = 'data-' . str_replace( '_', '-', $key );
        
        if ( is_array( $value ) || is_object( $value ) ) {
            $value = wp_json_encode( $value );
        }

        $this->attributes[ $data_key ] = esc_attr( $value );

        return $this;
    }

    /**
     * Set button text/content
     * 
     * @param string $text Button text
     * @return self
     */
    public function text( $text ) {
        $this->content = esc_html( $text );
        return $this;
    }

    /**
     * Add HTML content to button
     * 
     * @param string $html HTML content
     * @return self
     */
    public function html( $html ) {
        $this->content = wp_kses_post( $html );
        return $this;
    }

    /**
     * Add icon before text
     * 
     * @param string $icon Icon HTML or class
     * @return self
     */
    public function icon( $icon ) {
        // If it looks like a class, wrap it
        if ( strpos( $icon, 'fa-' ) === 0 || strpos( $icon, 'icon-' ) === 0 ) {
            $icon = '<i class="' . esc_attr( $icon ) . '"></i>';
        }

        $icon = wp_kses_post( $icon );
        $this->content = $icon . ' ' . $this->content;

        return $this;
    }

    /**
     * Set button to disabled
     * 
     * @param bool $disabled Whether button should be disabled
     * @return self
     */
    public function disabled( $disabled = true ) {
        if ( $disabled ) {
            $this->attributes['disabled'] = 'disabled';
        } else {
            unset( $this->attributes['disabled'] );
        }

        return $this;
    }

    /**
     * Add onClick handler
     * 
     * @param string $handler JavaScript code
     * @return self
     */
    public function onClick( $handler ) {
        $this->attributes['onclick'] = esc_attr( $handler );
        return $this;
    }

    /**
     * Add custom class
     * 
     * @param string $class CSS class name
     * @return self
     */
    public function addClass( $class ) {
        $class = sanitize_html_class( $class );
        if ( !in_array( $class, $this->classes ) ) {
            $this->classes[] = $class;
        }

        return $this;
    }

    /**
     * Remove custom class
     * 
     * @param string $class CSS class name
     * @return self
     */
    public function removeClass( $class ) {
        $class = sanitize_html_class( $class );
        $key = array_search( $class, $this->classes );
        if ( false !== $key ) {
            unset( $this->classes[ $key ] );
        }

        return $this;
    }

    /**
     * Add ARIA label
     * 
     * @param string $label ARIA label
     * @return self
     */
    public function ariaLabel( $label ) {
        $this->attributes['aria-label'] = esc_attr( $label );
        return $this;
    }

    /**
     * Set as primary action
     * 
     * @return self
     */
    public function primary_action() {
        return $this->primary()
            ->addClass( 'bkgt-primary-action' )
            ->ariaLabel( __( 'Primary action', 'bkgt' ) );
    }

    /**
     * Set as secondary action
     * 
     * @return self
     */
    public function secondary_action() {
        return $this->secondary()
            ->addClass( 'bkgt-secondary-action' )
            ->ariaLabel( __( 'Secondary action', 'bkgt' ) );
    }

    /**
     * Set as delete action
     * 
     * @return self
     */
    public function delete_action() {
        return $this->danger()
            ->addClass( 'bkgt-delete-action' )
            ->ariaLabel( __( 'Delete', 'bkgt' ) );
    }

    /**
     * Set as cancel action
     * 
     * @return self
     */
    public function cancel_action() {
        return $this->secondary()
            ->addClass( 'bkgt-cancel-action' )
            ->ariaLabel( __( 'Cancel', 'bkgt' ) );
    }

    /**
     * Get HTML attributes string
     * 
     * @return string
     */
    private function get_attributes_string() {
        $html = '';

        foreach ( $this->attributes as $name => $value ) {
            if ( 'disabled' === $name || 'onclick' === $name ) {
                $html .= ' ' . esc_attr( $name ) . '="' . esc_attr( $value ) . '"';
            } else {
                $html .= ' ' . esc_attr( $name ) . '="' . esc_attr( $value ) . '"';
            }
        }

        return $html;
    }

    /**
     * Get CSS classes string
     * 
     * @return string
     */
    private function get_classes_string() {
        return implode( ' ', array_unique( $this->classes ) );
    }

    /**
     * Build the button HTML
     * 
     * @return string
     */
    public function build() {
        $type = isset( $this->attributes['type'] ) ? $this->attributes['type'] : 'button';
        $classes = $this->get_classes_string();
        $attributes = $this->get_attributes_string();

        $html = '<button type="' . esc_attr( $type ) . '" class="' . esc_attr( $classes ) . '"' . $attributes . '>';
        $html .= $this->content;
        $html .= '</button>';

        return $html;
    }

    /**
     * Output the button HTML
     * 
     * @return void
     */
    public function render() {
        echo $this->build(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    /**
     * Magic method: Convert to string
     * 
     * @return string
     */
    public function __toString() {
        return $this->build();
    }
}
