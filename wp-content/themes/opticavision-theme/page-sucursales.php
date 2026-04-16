<?php
/**
 * Template Name: Sucursales
 * Template Post Type: page
 *
 * @package OpticaVision_Theme
 */

get_header();

$items    = OpticaVision_Sucursales::get_items(get_the_ID());
$delivery = OpticaVision_Sucursales::get_delivery(get_the_ID());
?>

<div class="sucursales-page">
    <div class="container">
        <div class="sucursales-header">
            <h1 class="sucursales-title"><?php esc_html_e('Sucursales', 'opticavision-theme'); ?></h1>
            <p class="sucursales-subtitle"><?php esc_html_e('Te esperamos en nuestras ubicaciones.', 'opticavision-theme'); ?></p>
        </div>

        <?php if (!empty($items)) : ?>
        <div class="sucursales-list">
            <?php foreach ($items as $item) :
                $nombre    = $item['nombre']    ?? '';
                $direccion = $item['direccion'] ?? '';
                $tel1      = $item['telefono1'] ?? '';
                $tel2      = $item['telefono2'] ?? '';
                $horario   = $item['horario']   ?? '';
                $maps_url  = $item['maps_url']  ?? '';
                $img_id    = intval($item['imagen_id'] ?? 0);
                $img_src   = $img_id ? wp_get_attachment_image_url($img_id, 'medium') : '';
            ?>
            <div class="sucursal-item">
                <div class="sucursal-image">
                    <?php if ($img_src) : ?>
                        <img src="<?php echo esc_url($img_src); ?>" alt="<?php echo esc_attr($nombre); ?>" />
                    <?php endif; ?>
                </div>
                <div class="sucursal-info">
                    <h2 class="sucursal-name"><?php echo esc_html($nombre); ?></h2>
                    <div class="sucursal-details">
                        <?php if ($direccion) : ?>
                            <p class="sucursal-address">
                                <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                                <?php echo esc_html($direccion); ?>
                            </p>
                        <?php endif; ?>
                        <?php if ($tel1 || $tel2) : ?>
                            <p class="sucursal-phone">
                                <i class="fas fa-phone" aria-hidden="true"></i>
                                <?php if ($tel1) : ?>
                                    <a href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/', '', $tel1)); ?>"><?php echo esc_html($tel1); ?></a>
                                <?php endif; ?>
                                <?php if ($tel1 && $tel2) : ?>
                                    <span class="phone-divider">•</span>
                                <?php endif; ?>
                                <?php if ($tel2) : ?>
                                    <a href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/', '', $tel2)); ?>"><?php echo esc_html($tel2); ?></a>
                                <?php endif; ?>
                            </p>
                        <?php endif; ?>
                        <?php if ($horario) : ?>
                            <p class="sucursal-schedule">
                                <i class="fas fa-clock" aria-hidden="true"></i>
                                <?php echo esc_html($horario); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="sucursal-action">
                    <?php if ($maps_url) : ?>
                        <a href="<?php echo esc_url($maps_url); ?>" target="_blank" rel="noopener noreferrer" class="get-directions-btn">
                            <?php esc_html_e('Cómo llegar', 'opticavision-theme'); ?>
                            <i class="fas fa-directions" aria-hidden="true"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else : ?>
        <p style="text-align:center;color:#999;"><?php esc_html_e('No hay sucursales cargadas.', 'opticavision-theme'); ?></p>
        <?php endif; ?>

        <?php
        // --- Tarjeta Servicio de Delivery ---
        if (!empty($delivery) && (!empty($delivery['direccion']) || !empty($delivery['whatsapp']))) :
            $del_img_id  = intval($delivery['imagen_id'] ?? 0);
            $del_img_src = $del_img_id ? wp_get_attachment_image_url($del_img_id, 'medium') : '';
            $del_dir     = $delivery['direccion']  ?? '';
            $del_tel     = $delivery['telefono']   ?? '';
            $del_horario = $delivery['horario']    ?? '';
            $del_wa_num  = preg_replace('/[^0-9]/', '', $delivery['whatsapp'] ?? '');
            $del_wa_msg  = rawurlencode($delivery['wa_mensaje'] ?? '');
            $del_wa_url  = 'https://wa.me/' . $del_wa_num . ($del_wa_msg ? '?text=' . $del_wa_msg : '');
        ?>
        <div class="sucursal-delivery-card">
            <?php if ($del_img_src) : ?>
            <div class="delivery-image">
                <img src="<?php echo esc_url($del_img_src); ?>" alt="<?php esc_attr_e('Servicio de Delivery', 'opticavision-theme'); ?>" />
            </div>
            <?php endif; ?>
            <div class="delivery-info">
                <h2 class="delivery-title"><?php esc_html_e('Servicio de Delivery', 'opticavision-theme'); ?></h2>
                <div class="delivery-details">
                    <?php if ($del_dir) : ?>
                    <p><i class="fas fa-truck" aria-hidden="true"></i> <?php echo esc_html($del_dir); ?></p>
                    <?php endif; ?>
                    <?php if ($del_tel) : ?>
                    <p><i class="fas fa-phone" aria-hidden="true"></i>
                        <a href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/', '', $del_tel)); ?>"><?php echo esc_html($del_tel); ?></a>
                    </p>
                    <?php endif; ?>
                    <?php if ($del_horario) : ?>
                    <p><i class="fas fa-clock" aria-hidden="true"></i> <?php echo esc_html($del_horario); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ($del_wa_num) : ?>
            <div class="delivery-action">
                <a href="<?php echo esc_url($del_wa_url); ?>" target="_blank" rel="noopener noreferrer" class="delivery-whatsapp-btn">
                    <i class="fas fa-truck" aria-hidden="true"></i>
                    <?php esc_html_e('Solicitar Delivery', 'opticavision-theme'); ?>
                </a>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php get_footer(); ?>
