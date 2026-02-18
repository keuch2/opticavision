<?php
/**
 * My Account page
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/my-account.php.
 *
 * @package OpticaVision_Theme
 */

defined( 'ABSPATH' ) || exit;

?>

<div class="woocommerce-account-wrapper">
    <nav class="woocommerce-MyAccount-navigation">
        <ul>
            <?php foreach ( wc_get_account_menu_items() as $endpoint => $label ) : ?>
                <li class="<?php echo wc_get_account_menu_item_classes( $endpoint ); ?>">
                    <a href="<?php echo esc_url( wc_get_account_endpoint_url( $endpoint ) ); ?>"><?php echo esc_html( $label ); ?></a>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>

    <div class="woocommerce-MyAccount-content">
        <?php
            /**
             * My Account content.
             *
             * @hooked woocommerce_account_content - 10
             */
            do_action( 'woocommerce_account_content' );
        ?>
    </div>
</div>

<style>
.woocommerce-account-wrapper {
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 40px;
    max-width: 1200px;
    margin: 0 auto;
    padding: 40px 20px;
}

.woocommerce-MyAccount-navigation {
    background: #fff;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    height: fit-content;
}

.woocommerce-MyAccount-navigation ul {
    display: block;
    list-style: none;
    padding: 0;
    margin: 0;
}

.woocommerce-MyAccount-navigation li {
    margin: 0;
    border-bottom: 1px solid #eee;
}

.woocommerce-MyAccount-navigation li:last-child {
    border-bottom: none;
}

.woocommerce-MyAccount-navigation a {
    align-items: center;
    gap: 12px;
    padding: 15px 0;
    color: #333;
    text-decoration: none;
    font-weight: 500;
    transition: color 0.3s ease;
}

.woocommerce-MyAccount-navigation a:hover,
.woocommerce-MyAccount-navigation .is-active a {
    color:rgb(255, 0, 0);
}


.woocommerce-MyAccount-content {
    background: #fff;
    border-radius: 8px;
    padding: 40px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.woocommerce-MyAccount-content h2,
.woocommerce-MyAccount-content h3 {
    color: #333;
    margin-bottom: 20px;
}

.woocommerce-MyAccount-content h2 {
    font-size: 2rem;
    border-bottom: 2px solidrgb(255, 0, 0);
    padding-bottom: 10px;
    margin-bottom: 30px;
}

.woocommerce-MyAccount-content .woocommerce-form-row {
    margin-bottom: 20px;
}

.woocommerce-MyAccount-content .woocommerce-form-row label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
    color: #333;
}

.woocommerce-MyAccount-content .woocommerce-Input {
    width: 100%;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 16px;
    transition: border-color 0.3s ease;
}

.woocommerce-MyAccount-content .woocommerce-Input:focus {
    outline: none;
    border-color: #1a2b88;
    box-shadow: 0 0 0 2px rgba(0, 0, 0, 0.1);
}

.woocommerce-MyAccount-content .woocommerce-Button {
    background:rgb(255, 0, 0);
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 500;
    font-size: 16px;
    transition: background 0.3s ease;
    align-items: center;
    width:100%;
    gap: 8px;
}

.woocommerce-MyAccount-content .woocommerce-Button:hover {
    background:rgb(255, 0, 0);
}

.woocommerce-MyAccount-content .woocommerce-Button::before {
    font-family: "Font Awesome 6 Free";
    font-weight: 900;
    content: "\f0c7"; /* fa-save */
}

.woocommerce-MyAccount-content .woocommerce-message,
.woocommerce-MyAccount-content .woocommerce-info,
.woocommerce-MyAccount-content .woocommerce-error {
    padding: 15px 20px;
    margin-bottom: 20px;
    border-radius: 4px;
    border-left: 4px solid;
}

.woocommerce-MyAccount-content .woocommerce-message {
    background: #d4edda;
    border-color: #28a745;
    color: #155724;
}

.woocommerce-MyAccount-content .woocommerce-info {
    background: #d1ecf1;
    border-color: #17a2b8;
    color: #0c5460;
}

.woocommerce-MyAccount-content .woocommerce-error {
    background: #f8d7da;
    border-color: #dc3545;
    color: #721c24;
}

/* Orders table */
.woocommerce-MyAccount-content .woocommerce-orders-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
}

.woocommerce-MyAccount-content .woocommerce-orders-table th,
.woocommerce-MyAccount-content .woocommerce-orders-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.woocommerce-MyAccount-content .woocommerce-orders-table th {
    background: #f8f9fa;
    font-weight: 600;
}

.woocommerce-MyAccount-content .woocommerce-orders-table .woocommerce-button {
    background: #1a2b88;
    color: white;
    padding: 6px 12px;
    border-radius: 4px;
    text-decoration: none;
    font-size: 14px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.woocommerce-MyAccount-content .woocommerce-orders-table .woocommerce-button:hover {
    background: #102064;
}

.woocommerce-MyAccount-content .woocommerce-orders-table .woocommerce-button::before {
    font-family: "Font Awesome 6 Free";
    font-weight: 900;
    content: "\f06e"; /* fa-eye */
    font-size: 12px;
}

@media (max-width: 768px) {
    .woocommerce-account-wrapper {
        grid-template-columns: 1fr;
        gap: 20px;
        padding: 20px;
    }
    
    .woocommerce-MyAccount-navigation ul {
        display: flex;
        overflow-x: auto;
        gap: 10px;
        padding-bottom: 10px;
    }
    
    .woocommerce-MyAccount-navigation li {
        border-bottom: none;
        white-space: nowrap;
    }
    
    .woocommerce-MyAccount-navigation a {
        padding: 10px 15px;
        background: #f8f9fa;
        border-radius: 20px;
        border: 1px solid #ddd;
    }
    
    .woocommerce-MyAccount-navigation .is-active a {
        background: #1a2b88;
        color: white;
        border-color: #1a2b88;
    }
    
    .woocommerce-MyAccount-content {
        padding: 20px;
    }
    
    .woocommerce-MyAccount-content h2 {
        font-size: 1.5rem;
    }
}
</style>
