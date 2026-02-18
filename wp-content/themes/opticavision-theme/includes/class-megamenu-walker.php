<?php
/**
 * OpticaVision Megamenu Walker - OPTIMIZED VERSION
 * Extiende el sistema nativo de menús de WordPress para crear megamenús
 * 
 * FIXES:
 * - Evita bucles infinitos
 * - Optimiza queries de metadata
 * - Manejo seguro de estados
 *
 * @package OpticaVision_Theme
 */

class OpticaVision_Megamenu_Walker extends Walker_Nav_Menu {

    private $megamenu_cache = array();
    private $current_parent_has_megamenu = false;
    private $execution_count = 0;
    private $max_executions = 1000; // Safety limit

    /**
     * Constructor - Initialize cache
     */
    public function __construct() {
        $this->megamenu_cache = array();
        $this->execution_count = 0;
    }

    /**
     * Get megamenu data with caching
     */
    private function get_megamenu_data($item_id) {
        // Safety check
        if (++$this->execution_count > $this->max_executions) {
            error_log('OpticaVision Megamenu Walker: Max executions reached, preventing infinite loop');
            return array('enabled' => false, 'columns' => 4);
        }

        if (!isset($this->megamenu_cache[$item_id])) {
            $this->megamenu_cache[$item_id] = array(
                'enabled' => get_post_meta($item_id, '_menu_item_megamenu', true) === '1',
                'columns' => absint(get_post_meta($item_id, '_menu_item_megamenu_columns', true)) ?: 4
            );
        }
        
        return $this->megamenu_cache[$item_id];
    }

    /**
     * Starts the list before the elements are added.
     */
    public function start_lvl(&$output, $depth = 0, $args = null) {
        if ($depth > 5) return; // Prevent deep nesting
        
        $indent = str_repeat("\t", $depth);
        
        if ($depth === 0 && $this->current_parent_has_megamenu) {
            $output .= "\n$indent<div class=\"megamenu-dropdown\">\n";
            $output .= "$indent\t<div class=\"megamenu-container\">\n";
        } else {
            $output .= "\n$indent<ul class=\"sub-menu\">\n";
        }
    }

    /**
     * Ends the list after the elements are added.
     */
    public function end_lvl(&$output, $depth = 0, $args = null) {
        if ($depth > 5) return; // Prevent deep nesting
        
        $indent = str_repeat("\t", $depth);
        
        if ($depth === 0 && $this->current_parent_has_megamenu) {
            $output .= "$indent\t</div>\n";
            $output .= "$indent</div>\n";
            $this->current_parent_has_megamenu = false; // Reset immediately
        } else {
            $output .= "$indent</ul>\n";
        }
    }

    /**
     * Starts the element output.
     */
    public function start_el(&$output, $item, $depth = 0, $args = null, $id = 0) {
        if ($depth > 5) return; // Prevent deep nesting
        
        $indent = ($depth) ? str_repeat("\t", $depth) : '';
        
        $classes = empty($item->classes) ? array() : (array) $item->classes;
        $classes[] = 'menu-item-' . $item->ID;
        
        // Check if has children (WordPress sets this automatically)
        $has_children = in_array('menu-item-has-children', $classes);
        
        // Get megamenu data with caching
        $megamenu_data = $this->get_megamenu_data($item->ID);
        $is_megamenu = $megamenu_data['enabled'];
        $megamenu_columns = $megamenu_data['columns'];
        
        // Only set megamenu flag for top-level items
        if ($is_megamenu && $depth === 0 && $has_children) {
            $this->current_parent_has_megamenu = true;
            $classes[] = 'has-megamenu';
            $classes[] = 'megamenu-columns-' . $megamenu_columns;
        } elseif ($has_children && $depth === 0) {
            $classes[] = 'has-dropdown';
        }
        
        // Build class string
        $class_names = join(' ', array_filter($classes));
        $class_names = $class_names ? ' class="' . esc_attr($class_names) . '"' : '';
        
        // Build ID
        $id = 'menu-item-' . $item->ID;
        $id = $id ? ' id="' . esc_attr($id) . '"' : '';
        
        // Build link attributes
        $attributes = ! empty($item->attr_title) ? ' title="'  . esc_attr($item->attr_title) .'"' : '';
        $attributes .= ! empty($item->target)     ? ' target="' . esc_attr($item->target     ) .'"' : '';
        $attributes .= ! empty($item->xfn)        ? ' rel="'    . esc_attr($item->xfn        ) .'"' : '';
        $attributes .= ! empty($item->url)        ? ' href="'   . esc_attr($item->url        ) .'"' : '';
        
        // Build item output
        $item_output = '<a' . $attributes . '>';
        $item_output .= esc_html($item->title);
        
        // Add dropdown icon for parent items
        if ($has_children && $depth === 0) {
            $item_output .= ' <span class="dropdown-icon"><i class="fas fa-chevron-down" aria-hidden="true"></i></span>';
        }
        
        $item_output .= '</a>';
        
        // Output structure based on depth and type
        if ($depth === 0) {
            // Top level items
            $output .= $indent . '<li' . $id . $class_names . '>' . $item_output;
        } elseif ($depth === 1 && $this->current_parent_has_megamenu) {
            // Megamenu columns
            $output .= $indent . '<div class="megamenu-column">';
            $output .= '<div class="megamenu-column-header">' . $item_output . '</div>';
        } else {
            // Regular submenu items
            $output .= $indent . '<li' . $id . $class_names . '>' . $item_output;
        }
    }

    /**
     * Ends the element output.
     */
    public function end_el(&$output, $item, $depth = 0, $args = null) {
        if ($depth > 5) return; // Prevent deep nesting
        
        if ($depth === 1 && $this->current_parent_has_megamenu) {
            $output .= "</div>\n"; // Close megamenu-column
        } else {
            $output .= "</li>\n";
        }
    }
}
