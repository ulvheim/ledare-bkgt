# 📅 EVENTS MANAGEMENT SYSTEM - IMPLEMENTATION PLAN

**Status:** READY FOR IMPLEMENTATION
**Date:** November 2, 2025
**Priority:** HIGH (User-facing feature)
**Scope:** Implement functional events system replacing "Coming Soon" placeholder

---

## 🎯 CURRENT STATE

### ❌ What's Broken
- Events tab shows "Eventhantering Kommer Snart" (Coming Soon)
- No events storage system
- No event creation/editing interface
- No calendar display
- events_shortcode returns placeholder text
- Admin tab is disabled (button marked `disabled`)

### ✅ What Exists
- Shortcode `[bkgt_events]` registered
- Admin tab interface with placeholder
- CSS styling for events (placeholder)
- `events_shortcode()` function structure
- Supporting functions: `get_events_list()`, `get_events_calendar()`
- Frontend styling in place

### 📍 Key Files
- **Main:** `wp-content/plugins/bkgt-team-player/bkgt-team-player.php` (2423 lines)
- **CSS Admin:** `wp-content/plugins/bkgt-team-player/assets/css/admin-dashboard.css`
- **CSS Frontend:** `wp-content/plugins/bkgt-team-player/assets/css/frontend.css`

---

## 🏗️ PROPOSED ARCHITECTURE

### Custom Post Type: `bkgt_event`

```php
register_post_type('bkgt_event', array(
    'label' => 'Events',
    'public' => true,
    'show_ui' => true,
    'show_in_rest' => false,
    'supports' => array('title', 'editor', 'author'),
));
```

### Post Meta Fields

```php
_bkgt_event_date         // Event date (YYYY-MM-DD)
_bkgt_event_time         // Event time (HH:MM)
_bkgt_event_location     // Event location (text)
_bkgt_event_type         // Event type (match/training/meeting)
_bkgt_event_team         // Team ID
_bkgt_event_status       // Status (scheduled/cancelled/completed)
_bkgt_event_opponent     // Opponent name (for matches)
_bkgt_event_notes        // Internal notes
_bkgt_event_color        // Calendar color (for display)
```

### Taxonomies

```php
// Event type taxonomy
bkgt_event_type
  ├─ Match (Matcher)
  ├─ Training (Träning)
  └─ Meeting (Möte)

// Event category taxonomy
bkgt_event_category
  ├─ Senior
  ├─ Junior
  └─ Other
```

---

## 📋 IMPLEMENTATION PHASES

### PHASE 1: Core Data Structure (30 mins)

1. Register `bkgt_event` custom post type
2. Register `bkgt_event_type` taxonomy
3. Register `bkgt_event_category` taxonomy
4. Add post meta fields
5. Create database schema

**Status:** ⏳ TO DO

### PHASE 2: Admin Interface (45 mins)

1. Replace placeholder in `render_events_tab()`
2. Add "Schemalägg Event" button (enable it)
3. Create event listing table
4. Add inline editing
5. Add quick actions (edit, delete, toggle status)
6. Add event creation form

**Status:** ⏳ TO DO

### PHASE 3: Frontend Display (30 mins)

1. Implement `get_events_list()` with real data
2. Implement `get_events_calendar()` with basic calendar
3. Add event filters (team, type, date range)
4. Add event details popup/page
5. Add responsive design

**Status:** ⏳ TO DO

### PHASE 4: User Interactions (15 mins)

1. Add AJAX handlers for quick edit
2. Add AJAX handlers for delete
3. Add inline create functionality
4. Add event notifications

**Status:** ⏳ TO DO

### PHASE 5: Testing & Docs (15 mins)

1. Test all functionality
2. Test permissions
3. Create test data
4. Document implementation

**Status:** ⏳ TO DO

---

## 🔧 DETAILED IMPLEMENTATION

### Step 1: Register Post Type & Taxonomies

```php
public function register_events_post_type() {
    // Register post type
    register_post_type('bkgt_event', array(
        'labels' => array(
            'name' => __('Events', 'bkgt-team-player'),
            'singular_name' => __('Event', 'bkgt-team-player'),
            'add_new' => __('Add Event', 'bkgt-team-player'),
            'add_new_item' => __('Add New Event', 'bkgt-team-player'),
            'edit_item' => __('Edit Event', 'bkgt-team-player'),
            'view_item' => __('View Event', 'bkgt-team-player'),
            'search_items' => __('Search Events', 'bkgt-team-player'),
        ),
        'public' => true,
        'show_ui' => true,
        'show_in_menu' => false, // Hidden, managed through our tab
        'capability_type' => 'post',
        'hierarchical' => false,
        'supports' => array('title', 'editor', 'author'),
        'show_in_rest' => false,
        'rewrite' => false, // No front-end single view
    ));

    // Register taxonomy: Event Type
    register_taxonomy('bkgt_event_type', 'bkgt_event', array(
        'labels' => array(
            'name' => __('Event Types', 'bkgt-team-player'),
            'singular_name' => __('Event Type', 'bkgt-team-player'),
        ),
        'hierarchical' => false,
        'public' => false,
        'show_ui' => false,
        'query_var' => false,
    ));

    // Register taxonomy: Event Category
    register_taxonomy('bkgt_event_category', 'bkgt_event', array(
        'labels' => array(
            'name' => __('Event Categories', 'bkgt-team-player'),
            'singular_name' => __('Event Category', 'bkgt-team-player'),
        ),
        'hierarchical' => true,
        'public' => false,
        'show_ui' => true,
        'show_admin_column' => true,
        'query_var' => false,
    ));
}
```

### Step 2: Update Admin Tab UI

```php
private function render_events_tab() {
    ?>
    <div id="events-tab" class="bkgt-events-tab">
        <div class="bkgt-tab-header">
            <h2><?php _e('Matcher & Träningar', 'bkgt-team-player'); ?></h2>
            <button class="button button-primary" id="bkgt-add-event-btn">
                <span class="dashicons dashicons-plus"></span>
                <?php _e('Schemalägg Event', 'bkgt-team-player'); ?>
            </button>
        </div>

        <div class="bkgt-events-list-container">
            <!-- Event creation form (hidden by default) -->
            <div id="bkgt-event-form-container" style="display: none;">
                <?php $this->render_event_form(); ?>
            </div>

            <!-- Events table -->
            <table class="bkgt-events-table wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th class="bkgt-col-type"><?php _e('Type', 'bkgt-team-player'); ?></th>
                        <th class="bkgt-col-title"><?php _e('Event', 'bkgt-team-player'); ?></th>
                        <th class="bkgt-col-date"><?php _e('Date & Time', 'bkgt-team-player'); ?></th>
                        <th class="bkgt-col-location"><?php _e('Location', 'bkgt-team-player'); ?></th>
                        <th class="bkgt-col-status"><?php _e('Status', 'bkgt-team-player'); ?></th>
                        <th class="bkgt-col-actions"><?php _e('Actions', 'bkgt-team-player'); ?></th>
                    </tr>
                </thead>
                <tbody id="bkgt-events-tbody">
                    <?php $this->render_events_list(); ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}
```

### Step 3: Implement Event List Rendering

```php
private function render_events_list() {
    $args = array(
        'post_type' => 'bkgt_event',
        'posts_per_page' => 20,
        'orderby' => 'meta_value',
        'meta_key' => '_bkgt_event_date',
        'order' => 'ASC',
    );

    $events = get_posts($args);

    if (empty($events)) {
        echo '<tr><td colspan="6" class="bkgt-no-events">' . 
             __('No events yet. Click "Schemalägg Event" to create one.', 'bkgt-team-player') . 
             '</td></tr>';
        return;
    }

    foreach ($events as $event) {
        $event_date = get_post_meta($event->ID, '_bkgt_event_date', true);
        $event_time = get_post_meta($event->ID, '_bkgt_event_time', true);
        $event_type = get_post_meta($event->ID, '_bkgt_event_type', true);
        $event_location = get_post_meta($event->ID, '_bkgt_event_location', true);
        $event_status = get_post_meta($event->ID, '_bkgt_event_status', true);
        
        $date_display = $event_date && $event_time ? 
            sprintf('%s %s', $event_date, $event_time) : 
            __('Unscheduled', 'bkgt-team-player');
        
        $status_class = 'bkgt-status-' . ($event_status ?: 'scheduled');
        
        ?>
        <tr class="bkgt-event-row" data-event-id="<?php echo $event->ID; ?>">
            <td class="bkgt-col-type">
                <span class="bkgt-event-type-badge bkgt-type-<?php echo esc_attr($event_type); ?>">
                    <?php echo esc_html($this->get_event_type_label($event_type)); ?>
                </span>
            </td>
            <td class="bkgt-col-title">
                <strong><?php echo esc_html($event->post_title); ?></strong>
            </td>
            <td class="bkgt-col-date">
                <?php echo esc_html($date_display); ?>
            </td>
            <td class="bkgt-col-location">
                <?php echo esc_html($event_location ?: '—'); ?>
            </td>
            <td class="bkgt-col-status">
                <span class="bkgt-event-status <?php echo esc_attr($status_class); ?>">
                    <?php echo esc_html($this->get_status_label($event_status)); ?>
                </span>
            </td>
            <td class="bkgt-col-actions">
                <a href="#" class="bkgt-event-edit" data-event-id="<?php echo $event->ID; ?>">
                    <?php _e('Edit', 'bkgt-team-player'); ?>
                </a> |
                <a href="#" class="bkgt-event-delete" data-event-id="<?php echo $event->ID; ?>">
                    <?php _e('Delete', 'bkgt-team-player'); ?>
                </a> |
                <a href="#" class="bkgt-event-toggle-status" data-event-id="<?php echo $event->ID; ?>">
                    <?php _e('Toggle Status', 'bkgt-team-player'); ?>
                </a>
            </td>
        </tr>
        <?php
    }
}
```

### Step 4: Event Form

```php
private function render_event_form() {
    ?>
    <div class="bkgt-event-form">
        <form id="bkgt-event-form" method="post">
            <?php wp_nonce_field('bkgt_save_event', 'bkgt_event_nonce'); ?>
            <input type="hidden" name="event_id" value="">
            
            <div class="bkgt-form-row">
                <label for="bkgt_event_title"><?php _e('Event Title', 'bkgt-team-player'); ?> *</label>
                <input type="text" id="bkgt_event_title" name="event_title" required>
            </div>

            <div class="bkgt-form-row">
                <label for="bkgt_event_type"><?php _e('Event Type', 'bkgt-team-player'); ?> *</label>
                <select id="bkgt_event_type" name="event_type" required>
                    <option value="match"><?php _e('Match', 'bkgt-team-player'); ?></option>
                    <option value="training"><?php _e('Training', 'bkgt-team-player'); ?></option>
                    <option value="meeting"><?php _e('Meeting', 'bkgt-team-player'); ?></option>
                </select>
            </div>

            <div class="bkgt-form-row">
                <label for="bkgt_event_date"><?php _e('Date', 'bkgt-team-player'); ?> *</label>
                <input type="date" id="bkgt_event_date" name="event_date" required>
            </div>

            <div class="bkgt-form-row">
                <label for="bkgt_event_time"><?php _e('Time', 'bkgt-team-player'); ?> *</label>
                <input type="time" id="bkgt_event_time" name="event_time" required>
            </div>

            <div class="bkgt-form-row">
                <label for="bkgt_event_location"><?php _e('Location', 'bkgt-team-player'); ?></label>
                <input type="text" id="bkgt_event_location" name="event_location" placeholder="e.g., Söderstadion">
            </div>

            <div class="bkgt-form-row">
                <label for="bkgt_event_opponent"><?php _e('Opponent', 'bkgt-team-player'); ?></label>
                <input type="text" id="bkgt_event_opponent" name="event_opponent" placeholder="e.g., Stockholm United">
            </div>

            <div class="bkgt-form-row">
                <label for="bkgt_event_notes"><?php _e('Notes', 'bkgt-team-player'); ?></label>
                <textarea id="bkgt_event_notes" name="event_notes" rows="4"></textarea>
            </div>

            <div class="bkgt-form-actions">
                <button type="submit" class="button button-primary"><?php _e('Save Event', 'bkgt-team-player'); ?></button>
                <button type="button" class="button" id="bkgt-cancel-event"><?php _e('Cancel', 'bkgt-team-player'); ?></button>
            </div>
        </form>
    </div>
    <?php
}
```

---

## ⏱️ TIME ESTIMATE

| Phase | Task | Time |
|-------|------|------|
| 1 | Register post type & taxonomies | 30 mins |
| 2 | Admin interface | 45 mins |
| 3 | Frontend display | 30 mins |
| 4 | User interactions | 15 mins |
| 5 | Testing & docs | 15 mins |
| **TOTAL** | **Complete Events System** | **2.5 hours** |

---

## 🎯 SUCCESS CRITERIA

✅ Events can be created in admin
✅ Events appear in table with full details
✅ Events can be edited inline or via form
✅ Events can be deleted
✅ Events display on frontend
✅ Calendar view works
✅ List view works
✅ Filters work (team, type, date range)
✅ No console errors
✅ Responsive design works
✅ Permissions enforced (only admins/coaches can edit)

---

## 🚀 NEXT STEPS

1. Register custom post type and taxonomies
2. Update `render_events_tab()` with functional UI
3. Implement `render_events_list()` with real data
4. Implement `render_event_form()` for creation/editing
5. Implement `get_events_list()` for frontend shortcode
6. Add AJAX handlers for quick actions
7. Add CSS styling for new UI
8. Test all functionality

**Total Estimated Time:** 2-3 hours
**Status:** Ready to begin implementation ✅

