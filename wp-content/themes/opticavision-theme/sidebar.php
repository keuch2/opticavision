<?php
/**
 * The sidebar containing the main widget area
 *
 * @package OpticaVision_Theme
 */

if (!is_active_sidebar('sidebar-1')) {
    return;
}
?>

<aside id="secondary" class="widget-area">
    <?php dynamic_sidebar('sidebar-1'); ?>
</aside>

<style>
.widget-area {
    background: #fff;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-top: 40px;
}

.widget-area .widget {
    margin-bottom: 40px;
    padding-bottom: 30px;
    border-bottom: 1px solid #eee;
}

.widget-area .widget:last-child {
    margin-bottom: 0;
    padding-bottom: 0;
    border-bottom: none;
}

.widget-area .widget-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: #333;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid #1a2b88;
}

.widget-area ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.widget-area li {
    margin-bottom: 10px;
    padding-left: 20px;
    position: relative;
}

.widget-area li::before {
    content: '→';
    position: absolute;
    left: 0;
    color: #1a2b88;
    font-weight: bold;
}

.widget-area a {
    color: #333;
    text-decoration: none;
    transition: color 0.3s ease;
}

.widget-area a:hover {
    color: #1a2b88;
}

@media (max-width: 768px) {
    .widget-area {
        margin: 20px;
        padding: 20px;
    }
}
</style>
