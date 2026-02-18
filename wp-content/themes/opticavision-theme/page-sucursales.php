<?php
/**
 * Template for Sucursales (Stores) Page
 *
 * @package OpticaVision_Theme
 */

get_header(); 

// Get sucursales from Custom Post Type
$sucursales = OpticaVision_Sucursales::get_sucursales();
?>

<div class="sucursales-page">
    <div class="container">
        <div class="sucursales-header">
            <h1 class="sucursales-title"><?php esc_html_e('Sucursales', 'opticavision-theme'); ?></h1>
            <p class="sucursales-subtitle"><?php esc_html_e('Te esperamos en nuestras ubicaciones.', 'opticavision-theme'); ?></p>
            
        </div>

        <div class="sucursales-list">
            <?php if (!empty($sucursales)) : ?>
                <?php foreach ($sucursales as $sucursal) : 
                    $city = get_post_meta($sucursal->ID, '_sucursal_city', true);
                    $address = get_post_meta($sucursal->ID, '_sucursal_address', true);
                    $phone = get_post_meta($sucursal->ID, '_sucursal_phone', true);
                    $schedule = get_post_meta($sucursal->ID, '_sucursal_schedule', true);
                    $maps_url = get_post_meta($sucursal->ID, '_sucursal_maps_url', true);
                    $thumbnail = get_the_post_thumbnail_url($sucursal->ID, 'medium');
                ?>
                    <div class="sucursal-item">
                        <div class="sucursal-image">
                            <?php if ($thumbnail) : ?>
                                <img src="<?php echo esc_url($thumbnail); ?>" alt="<?php echo esc_attr($city ? $city : $sucursal->post_title); ?>" />
                            <?php endif; ?>
                        </div>
                        <div class="sucursal-info">
                            <h2 class="sucursal-name"><?php echo esc_html($city ? $city : $sucursal->post_title); ?></h2>
                            <div class="sucursal-details">
                                <?php if ($address) : ?>
                                    <p class="sucursal-address">
                                        <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                                        <?php esc_html_e('Address:', 'opticavision-theme'); ?> <?php echo esc_html($address); ?>
                                    </p>
                                <?php endif; ?>
                                <?php if ($phone) : ?>
                                    <p class="sucursal-phone">
                                        <i class="fas fa-phone" aria-hidden="true"></i>
                                        <a href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/', '', $phone)); ?>"><?php echo esc_html($phone); ?></a>
                                    </p>
                                <?php endif; ?>
                                <?php if ($schedule) : ?>
                                    <p class="sucursal-schedule">
                                        <i class="fas fa-clock" aria-hidden="true"></i>
                                        <?php echo esc_html($schedule); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="sucursal-action">
                            <?php if ($maps_url) : ?>
                                <a href="<?php echo esc_url($maps_url); ?>" target="_blank" rel="noopener" class="get-directions-btn">
                                    <?php esc_html_e('Get Directions', 'opticavision-theme'); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else : ?>
                <!-- OpticaVision Sucursales -->
                <div class="sucursal-item">
                    <div class="sucursal-image">
                        <img src="<?php echo esc_url(get_template_directory_uri() . '/sucursales/casacentral.png'); ?>" alt="Casa Central" />
                    </div>
                    <div class="sucursal-info">
                        <h2 class="sucursal-name">Casa Central</h2>
                        <div class="sucursal-details">
                            <p class="sucursal-address">
                                <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                                Palma 764 c/ Ayolas
                            </p>
                            <p class="sucursal-phone">
                                <i class="fas fa-phone" aria-hidden="true"></i>
                                <a href="tel:021441660">021-441-660 (RA)</a>
                                <span class="phone-divider">•</span>
                                <a href="tel:0974829865">0974-829 865</a>
                            </p>
                            <p class="sucursal-schedule">
                                <i class="fas fa-clock" aria-hidden="true"></i>
                                Lun-Vie: 8:00-18:30 | Sáb: 8:30-12:30
                            </p>
                        </div>
                    </div>
                    <div class="sucursal-action">
                        <a href="https://maps.google.com/?q=Palma+764+c/+Ayolas,+Asunción,+Paraguay" target="_blank" rel="noopener" class="get-directions-btn">Cómo llegar</a>
                    </div>
                </div>

                <div class="sucursal-item">
                    <div class="sucursal-image">
                        <img src="<?php echo esc_url(get_template_directory_uri() . '/sucursales/delsol.png'); ?>" alt="Shopping del Sol" />
                    </div>
                    <div class="sucursal-info">
                        <h2 class="sucursal-name">Shopping del Sol</h2>
                        <div class="sucursal-details">
                            <p class="sucursal-address">
                                <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                                Local 246 – 2do Nivel
                            </p>
                            <p class="sucursal-phone">
                                <i class="fas fa-phone" aria-hidden="true"></i>
                                <a href="tel:021441660115">021-441-660 Int. 115</a>
                                <span class="phone-divider">•</span>
                                <a href="tel:0982506318">0982-506 318</a>
                            </p>
                            <p class="sucursal-schedule">
                                <i class="fas fa-clock" aria-hidden="true"></i>
                                Lun-Sáb: 9:00-21:00 | Dom: 10:00-21:00
                            </p>
                        </div>
                    </div>
                    <div class="sucursal-action">
                        <a href="https://maps.google.com/?q=Shopping+del+Sol,+Asunción,+Paraguay" target="_blank" rel="noopener" class="get-directions-btn">Cómo llegar</a>
                    </div>
                </div>

                <div class="sucursal-item">
                    <div class="sucursal-image">
                        <img src="<?php echo esc_url(get_template_directory_uri() . '/sucursales/shoppingmariscal.png'); ?>" alt="Shopping Mariscal López" />
                    </div>
                    <div class="sucursal-info">
                        <h2 class="sucursal-name">Shopping Mariscal</h2>
                        <div class="sucursal-details">
                            <p class="sucursal-address">
                                <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                                Local 228 – 2do Nivel
                            </p>
                            <p class="sucursal-phone">
                                <i class="fas fa-phone" aria-hidden="true"></i>
                                <a href="tel:021441660114">021-441-660 Int. 114</a>
                                <span class="phone-divider">•</span>
                                <a href="tel:0982506319">0982-506 319</a>
                            </p>
                            <p class="sucursal-schedule">
                                <i class="fas fa-clock" aria-hidden="true"></i>
                                Lun-Sáb: 9:00-21:00 | Dom: 10:00-21:00
                            </p>
                        </div>
                    </div>
                    <div class="sucursal-action">
                        <a href="https://maps.google.com/?q=Shopping+Mariscal+López,+Asunción,+Paraguay" target="_blank" rel="noopener" class="get-directions-btn">Cómo llegar</a>
                    </div>
                </div>

                <div class="sucursal-item">
                    <div class="sucursal-image">
                        <img src="<?php echo esc_url(get_template_directory_uri() . '/sucursales/pinedo.png'); ?>" alt="Shopping Pinedo" />
                    </div>
                    <div class="sucursal-info">
                        <h2 class="sucursal-name">Shopping Pinedo</h2>
                        <div class="sucursal-details">
                            <p class="sucursal-address">
                                <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                                Local 21
                            </p>
                            <p class="sucursal-phone">
                                <i class="fas fa-phone" aria-hidden="true"></i>
                                <a href="tel:021441660119">021-441-660 Int. 119</a>
                                <span class="phone-divider">•</span>
                                <a href="tel:0986345562">0986-345 562</a>
                            </p>
                            <p class="sucursal-schedule">
                                <i class="fas fa-clock" aria-hidden="true"></i>
                                Lun-Sáb: 9:00-21:00 | Dom: 10:00-21:00
                            </p>
                        </div>
                    </div>
                    <div class="sucursal-action">
                        <a href="https://maps.google.com/?q=Shopping+Pinedo,+Asunción,+Paraguay" target="_blank" rel="noopener" class="get-directions-btn">Cómo llegar</a>
                    </div>
                </div>

                <div class="sucursal-item">
                    <div class="sucursal-image">
                        <img src="<?php echo esc_url(get_template_directory_uri() . '/sucursales/sucursalpanteon.png'); ?>" alt="Sucursal Panteón" />
                    </div>
                    <div class="sucursal-info">
                        <h2 class="sucursal-name">Sucursal Panteón</h2>
                        <div class="sucursal-details">
                            <p class="sucursal-address">
                                <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                                Palma 254 frente al Panteón
                            </p>
                            <p class="sucursal-phone">
                                <i class="fas fa-phone" aria-hidden="true"></i>
                                <a href="tel:021441660113">021-441-660 Int. 113</a>
                                <span class="phone-divider">•</span>
                                <a href="tel:0972224520">0972-224 520</a>
                            </p>
                            <p class="sucursal-schedule">
                                <i class="fas fa-clock" aria-hidden="true"></i>
                                Lun-Vie: 7:30-18:30 | Sáb: 8:30-12:30
                            </p>
                        </div>
                    </div>
                    <div class="sucursal-action">
                        <a href="https://maps.google.com/?q=Palma+254,+Asunción,+Paraguay" target="_blank" rel="noopener" class="get-directions-btn">Cómo llegar</a>
                    </div>
                </div>

                <div class="sucursal-item">
                    <div class="sucursal-image">
                        <img src="<?php echo esc_url(get_template_directory_uri() . '/sucursales/paseolagaleria.png'); ?>" alt="Paseo La Galería" />
                    </div>
                    <div class="sucursal-info">
                        <h2 class="sucursal-name">Paseo La Galería</h2>
                        <div class="sucursal-details">
                            <p class="sucursal-address">
                                <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                                Local 136
                            </p>
                            <p class="sucursal-phone">
                                <i class="fas fa-phone" aria-hidden="true"></i>
                                <a href="tel:021441660117">021-441-660 Int. 117</a>
                                <span class="phone-divider">•</span>
                                <a href="tel:0982506317">0982-506 317</a>
                            </p>
                            <p class="sucursal-schedule">
                                <i class="fas fa-clock" aria-hidden="true"></i>
                                Lun-Jue: 10:00-21:00 | Vie-Sáb: 10:00-22:00 | Dom: 10:00-21:00
                            </p>
                        </div>
                    </div>
                    <div class="sucursal-action">
                        <a href="https://maps.google.com/?q=Paseo+La+Galería,+Asunción,+Paraguay" target="_blank" rel="noopener" class="get-directions-btn">Cómo llegar</a>
                    </div>
                </div>

                <div class="sucursal-item upcoming">
                    <div class="sucursal-image">
                        <img src="<?php echo esc_url(get_template_directory_uri() . '/sucursales/distritoperseverancia.png'); ?>" alt="Distrito Perseverancia" />
                    </div>
                    <div class="sucursal-info">
                        <h2 class="sucursal-name">Distrito Perseverancia</h2>
                        <div class="sucursal-details">
                            <p class="sucursal-address">
                                <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                                Próximamente
                            </p>
                            <p class="sucursal-phone">
                                <i class="fas fa-info-circle" aria-hidden="true"></i>
                                Información disponible pronto
                            </p>
                            <p class="sucursal-schedule">
                                <i class="fas fa-clock" aria-hidden="true"></i>
                                Lun-Sáb: 9:00-21:00 | Dom: 10:00-21:00
                            </p>
                        </div>
                    </div>
                    <div class="sucursal-action">
                        <span class="coming-soon-btn">Próximamente</span>
                    </div>
                </div>

                <div class="sucursal-item delivery">
                    <div class="sucursal-image">
                        <img src="<?php echo esc_url(get_template_directory_uri() . '/sucursales/casacentral.png'); ?>" alt="Servicio de Delivery" />
                    </div>
                    <div class="sucursal-info">
                        <h2 class="sucursal-name">Servicio de Delivery</h2>
                        <div class="sucursal-details">
                            <p class="sucursal-address">
                                <i class="fas fa-truck" aria-hidden="true"></i>
                                Palma 764 c/ Ayolas
                            </p>
                            <p class="sucursal-phone">
                                <i class="fas fa-phone" aria-hidden="true"></i>
                                <a href="tel:0982506314">0982-506 314</a>
                            </p>
                            <p class="sucursal-schedule">
                                <i class="fas fa-clock" aria-hidden="true"></i>
                                Lun-Vie: 8:00-18:00, Sáb: 8:00-15:00
                            </p>
                        </div>
                    </div>
                    <div class="sucursal-action">
                        <a href="tel:0982506314" class="delivery-btn">Solicitar Delivery</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php get_footer(); ?>
