<?php
/**
 * BKGT Button System - Examples
 * 
 * This file demonstrates various ways to use the BKGT Button System
 * Copy and adapt these examples for your plugins and templates
 * 
 * @package BKGT
 * @subpackage Examples
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Example 1: Basic Buttons with Different Variants
 */
function bkgt_example_basic_buttons() {
    ?>
    <div class="bkgt-example">
        <h3><?php esc_html_e( 'Basic Button Variants', 'bkgt' ); ?></h3>
        
        <?php echo bkgt_button( 'Primary Button' )->primary(); ?>
        <?php echo bkgt_button( 'Secondary Button' )->secondary(); ?>
        <?php echo bkgt_button( 'Danger Button' )->danger(); ?>
        <?php echo bkgt_button( 'Success Button' )->success(); ?>
        <?php echo bkgt_button( 'Warning Button' )->warning(); ?>
        <?php echo bkgt_button( 'Info Button' )->info(); ?>
        <?php echo bkgt_button( 'Text Button' )->text(); ?>
        <?php echo bkgt_button( 'Outline Button' )->outline(); ?>
    </div>
    <?php
}

/**
 * Example 2: Button Sizes
 */
function bkgt_example_button_sizes() {
    ?>
    <div class="bkgt-example">
        <h3><?php esc_html_e( 'Button Sizes', 'bkgt' ); ?></h3>
        
        <p>
            <?php echo bkgt_button( 'Small Button' )->small()->primary(); ?>
            <?php echo bkgt_button( 'Normal Button' )->primary(); ?>
            <?php echo bkgt_button( 'Large Button' )->large()->primary(); ?>
        </p>
        
        <p>
            <?php echo bkgt_button( 'Full Width Block Button' )->block()->primary(); ?>
        </p>
    </div>
    <?php
}

/**
 * Example 3: Form with Buttons
 */
function bkgt_example_form_with_buttons() {
    ?>
    <div class="bkgt-example">
        <h3><?php esc_html_e( 'Form with Buttons', 'bkgt' ); ?></h3>
        
        <form method="POST" class="bkgt-example-form">
            <div class="bkgt-form-group">
                <label for="name"><?php esc_html_e( 'Your Name', 'bkgt' ); ?></label>
                <input type="text" id="name" name="name" class="bkgt-form-control" required>
            </div>
            
            <div class="bkgt-form-group">
                <label for="email"><?php esc_html_e( 'Email Address', 'bkgt' ); ?></label>
                <input type="email" id="email" name="email" class="bkgt-form-control" required>
            </div>
            
            <div class="bkgt-form-footer">
                <?php
                echo bkgt_button( __( 'Send Message', 'bkgt' ) )
                    ->primary()
                    ->large()
                    ->type( 'submit' )
                    ->id( 'submit-form' );
                
                echo bkgt_button( __( 'Reset', 'bkgt' ) )
                    ->secondary()
                    ->type( 'reset' );
                
                echo bkgt_button( __( 'Cancel', 'bkgt' ) )
                    ->text()
                    ->onclick( "document.getElementById('example-form').reset();" );
                ?>
            </div>
        </form>
    </div>
    <?php
}

/**
 * Example 4: Semantic Action Buttons
 */
function bkgt_example_semantic_buttons() {
    ?>
    <div class="bkgt-example">
        <h3><?php esc_html_e( 'Semantic Action Buttons', 'bkgt' ); ?></h3>
        
        <p>
            <?php
            // Primary action button
            echo bkgt_button( __( 'Save Document', 'bkgt' ) )
                ->primary_action()
                ->large();
            ?>
        </p>
        
        <p>
            <?php
            // Secondary action button
            echo bkgt_button( __( 'Skip This Step', 'bkgt' ) )
                ->secondary_action();
            ?>
        </p>
        
        <p>
            <?php
            // Delete action button
            echo bkgt_button( __( 'Delete Forever', 'bkgt' ) )
                ->delete_action();
            ?>
        </p>
        
        <p>
            <?php
            // Cancel action button
            echo bkgt_button( __( 'Abandon Changes', 'bkgt' ) )
                ->cancel_action();
            ?>
        </p>
    </div>
    <?php
}

/**
 * Example 5: Button Group (Checkbox - Multiple Selection)
 */
function bkgt_example_button_group_checkbox() {
    ?>
    <div class="bkgt-example">
        <h3><?php esc_html_e( 'Button Group - Checkbox (Multiple Select)', 'bkgt' ); ?></h3>
        
        <div class="bkgt-btn-group" data-bkgt-button-group="checkbox">
            <?php
            echo bkgt_button( __( 'HTML', 'bkgt' ) )
                ->outline()
                ->attr( 'value', 'html' );
            
            echo bkgt_button( __( 'CSS', 'bkgt' ) )
                ->outline()
                ->attr( 'value', 'css' );
            
            echo bkgt_button( __( 'JavaScript', 'bkgt' ) )
                ->outline()
                ->attr( 'value', 'javascript' );
            
            echo bkgt_button( __( 'PHP', 'bkgt' ) )
                ->outline()
                ->attr( 'value', 'php' );
            ?>
        </div>
        
        <p>
            <small id="checkbox-selected"><?php esc_html_e( 'Selected: None', 'bkgt' ); ?></small>
        </p>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const group = new BKGTButtonGroup('[data-bkgt-button-group="checkbox"]', {
            onSelect: function() {
                updateCheckboxSelected();
            },
            onDeselect: function() {
                updateCheckboxSelected();
            }
        });
        
        function updateCheckboxSelected() {
            const values = group.getSelectedValues();
            const text = values.length ? values.join(', ') : '<?php esc_html_e( 'None', 'bkgt' ); ?>';
            document.getElementById('checkbox-selected').textContent = '<?php esc_html_e( 'Selected:', 'bkgt' ); ?> ' + text;
        }
    });
    </script>
    <?php
}

/**
 * Example 6: Button Group (Radio - Single Selection)
 */
function bkgt_example_button_group_radio() {
    ?>
    <div class="bkgt-example">
        <h3><?php esc_html_e( 'Button Group - Radio (Single Select)', 'bkgt' ); ?></h3>
        
        <div class="bkgt-btn-group" data-bkgt-button-group="radio">
            <?php
            echo bkgt_button( __( 'Small', 'bkgt' ) )
                ->outline()
                ->attr( 'value', 'small' );
            
            echo bkgt_button( __( 'Medium', 'bkgt' ) )
                ->outline()
                ->attr( 'value', 'medium' );
            
            echo bkgt_button( __( 'Large', 'bkgt' ) )
                ->outline()
                ->attr( 'value', 'large' );
            ?>
        </div>
        
        <p>
            <small id="radio-selected"><?php esc_html_e( 'Selected: None', 'bkgt' ); ?></small>
        </p>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const group = new BKGTButtonGroup('[data-bkgt-button-group="radio"]', {
            onSelect: function() {
                const values = group.getSelectedValues();
                const text = values.length ? values[0] : '<?php esc_html_e( 'None', 'bkgt' ); ?>';
                document.getElementById('radio-selected').textContent = '<?php esc_html_e( 'Selected:', 'bkgt' ); ?> ' + text;
            },
            onDeselect: function() {
                document.getElementById('radio-selected').textContent = '<?php esc_html_e( 'Selected: None', 'bkgt' ); ?>';
            }
        });
    });
    </script>
    <?php
}

/**
 * Example 7: Buttons with Icons (Font Awesome)
 */
function bkgt_example_buttons_with_icons() {
    ?>
    <div class="bkgt-example">
        <h3><?php esc_html_e( 'Buttons with Icons', 'bkgt' ); ?></h3>
        
        <p>
            <?php
            echo bkgt_button( __( 'Download', 'bkgt' ) )
                ->icon( 'fa-download' )
                ->primary();
            ?>
        </p>
        
        <p>
            <?php
            echo bkgt_button( __( 'Delete', 'bkgt' ) )
                ->icon( 'fa-trash' )
                ->danger();
            ?>
        </p>
        
        <p>
            <?php
            echo bkgt_button( __( 'Save', 'bkgt' ) )
                ->icon( 'fa-save' )
                ->success();
            ?>
        </p>
        
        <p>
            <?php
            echo bkgt_button()
                ->ariaLabel( __( 'Close', 'bkgt' ) )
                ->icon( 'fa-times' )
                ->text();
            ?>
        </p>
    </div>
    <?php
}

/**
 * Example 8: Loading States (JavaScript)
 */
function bkgt_example_loading_states() {
    ?>
    <div class="bkgt-example">
        <h3><?php esc_html_e( 'Loading States', 'bkgt' ); ?></h3>
        
        <p>
            <?php
            echo bkgt_button( __( 'Click to Load', 'bkgt' ) )
                ->primary()
                ->id( 'loading-example-btn' );
            ?>
        </p>
        
        <p id="loading-result"></p>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const btn = new BKGTButton('#loading-example-btn');
        
        btn.onClick(function(button) {
            btn.perform(async function() {
                // Simulate API call
                await new Promise(resolve => setTimeout(resolve, 2000));
                
                // Random success/error
                if (Math.random() > 0.3) {
                    btn.showSuccess();
                    document.getElementById('loading-result').textContent = '✓ <?php esc_html_e( 'Success!', 'bkgt' ); ?>';
                } else {
                    throw new Error('Demo error');
                }
            }).catch(error => {
                btn.showError('<?php esc_html_e( 'Something went wrong', 'bkgt' ); ?>', 2000);
                document.getElementById('loading-result').textContent = '✕ ' + error.message;
            });
        });
    });
    </script>
    <?php
}

/**
 * Example 9: Modal with Buttons
 */
function bkgt_example_modal_with_buttons() {
    ?>
    <div class="bkgt-example">
        <h3><?php esc_html_e( 'Modal with Buttons', 'bkgt' ); ?></h3>
        
        <p>
            <?php
            echo bkgt_button( __( 'Open Modal', 'bkgt' ) )
                ->primary()
                ->id( 'open-modal-btn' );
            ?>
        </p>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const btn = new BKGTButton('#open-modal-btn');
        const modal = new BKGTModal({
            id: 'example-modal',
            title: '<?php esc_html_e( 'Example Modal', 'bkgt' ); ?>',
            size: 'medium'
        });
        
        btn.onClick(function() {
            let content = '<p><?php esc_html_e( 'This is a modal with BKGT buttons.', 'bkgt' ); ?></p>';
            content += '<div class="bkgt-modal-footer">';
            content += '<button class="bkgt-btn bkgt-btn-primary modal-confirm-btn"><?php esc_html_e( 'Confirm', 'bkgt' ); ?></button>';
            content += '<button class="bkgt-btn bkgt-btn-secondary modal-cancel-btn"><?php esc_html_e( 'Cancel', 'bkgt' ); ?></button>';
            content += '</div>';
            
            modal.open(content);
            
            // Add button handlers
            document.querySelector('.modal-confirm-btn').addEventListener('click', function() {
                console.log('Confirmed!');
                modal.close();
            });
            
            document.querySelector('.modal-cancel-btn').addEventListener('click', function() {
                modal.close();
            });
        });
    });
    </script>
    <?php
}

/**
 * Example 10: Button State Management (JavaScript)
 */
function bkgt_example_button_state_management() {
    ?>
    <div class="bkgt-example">
        <h3><?php esc_html_e( 'Button State Management', 'bkgt' ); ?></h3>
        
        <p>
            <?php
            echo bkgt_button( __( 'Disable/Enable', 'bkgt' ) )
                ->primary()
                ->id( 'state-demo-btn' );
            ?>
        </p>
        
        <p>
            <button id="toggle-disabled-btn" class="bkgt-btn bkgt-btn-secondary">
                <?php esc_html_e( 'Toggle Disabled State', 'bkgt' ); ?>
            </button>
        </p>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const demoBtn = new BKGTButton('#state-demo-btn');
        const toggleBtn = document.getElementById('toggle-disabled-btn');
        
        toggleBtn.addEventListener('click', function() {
            if (demoBtn.isDisabled()) {
                demoBtn.enable();
                toggleBtn.textContent = '<?php esc_html_e( 'Disable Button', 'bkgt' ); ?>';
            } else {
                demoBtn.disable();
                toggleBtn.textContent = '<?php esc_html_e( 'Enable Button', 'bkgt' ); ?>';
            }
        });
    });
    </script>
    <?php
}

/**
 * Example 11: Batch Button Operations (JavaScript)
 */
function bkgt_example_batch_operations() {
    ?>
    <div class="bkgt-example">
        <h3><?php esc_html_e( 'Batch Button Operations', 'bkgt' ); ?></h3>
        
        <div style="margin-bottom: 15px;">
            <button class="bkgt-btn bkgt-btn-primary batch-action-btn">
                <?php esc_html_e( 'Action 1', 'bkgt' ); ?>
            </button>
            <button class="bkgt-btn bkgt-btn-primary batch-action-btn">
                <?php esc_html_e( 'Action 2', 'bkgt' ); ?>
            </button>
            <button class="bkgt-btn bkgt-btn-primary batch-action-btn">
                <?php esc_html_e( 'Action 3', 'bkgt' ); ?>
            </button>
        </div>
        
        <p>
            <button id="disable-all-btn" class="bkgt-btn bkgt-btn-danger">
                <?php esc_html_e( 'Disable All', 'bkgt' ); ?>
            </button>
            <button id="enable-all-btn" class="bkgt-btn bkgt-btn-success">
                <?php esc_html_e( 'Enable All', 'bkgt' ); ?>
            </button>
        </p>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('disable-all-btn').addEventListener('click', function() {
            BKGTButton.disableAll('.batch-action-btn');
        });
        
        document.getElementById('enable-all-btn').addEventListener('click', function() {
            BKGTButton.enableAll('.batch-action-btn');
        });
    });
    </script>
    <?php
}

/**
 * Example 12: Custom Styled Buttons
 */
function bkgt_example_custom_styled_buttons() {
    ?>
    <div class="bkgt-example">
        <h3><?php esc_html_e( 'Custom Styled Buttons', 'bkgt' ); ?></h3>
        
        <style>
            .bkgt-button-custom-gradient {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                border-color: #667eea;
            }
            
            .bkgt-button-custom-gradient:hover {
                background: linear-gradient(135deg, #5568d3 0%, #6a3f8f 100%);
            }
        </style>
        
        <p>
            <?php
            echo bkgt_button( __( 'Custom Gradient', 'bkgt' ) )
                ->addClass( 'bkgt-button-custom-gradient' )
                ->primary();
            ?>
        </p>
    </div>
    <?php
}
