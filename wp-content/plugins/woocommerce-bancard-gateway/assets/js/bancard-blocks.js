/**
 * WooCommerce Blocks integration for Bancard Gateway
 */

console.log('🏦 BANCARD BLOCKS: Script cargado');

// Verificar dependencias
if (!window.wc || !window.wc.wcBlocksRegistry) {
    console.error('❌ BANCARD BLOCKS: wcBlocksRegistry no disponible');
} else if (!window.wc.wcSettings) {
    console.error('❌ BANCARD BLOCKS: wcSettings no disponible');
} else {
    console.log('✅ BANCARD BLOCKS: Dependencias disponibles');

    const { registerPaymentMethod } = window.wc.wcBlocksRegistry;
    const { getSetting } = window.wc.wcSettings;
    const { __ } = window.wp.i18n;
    const { decodeEntities } = window.wp.htmlEntities;

    // Get settings passed from PHP
    const settings = getSetting('bancard_data', {});
    const defaultLabel = __('Bancard', 'wc-bancard');
    const label = decodeEntities(settings.title) || defaultLabel;

/**
 * Content component for Bancard payment method
 */
const Content = () => {
    return decodeEntities(settings.description || '');
};

/**
 * Label component for Bancard payment method  
 */
const Label = (props) => {
    const imageUrl = settings.icon || '/opticavision/wp-content/themes/opticavision-theme/pago-seguro.png';
    
    return React.createElement(
        'div',
        { style: { display: 'flex', alignItems: 'center' } },
        React.createElement('img', {
            src: imageUrl,
            alt: 'Bancard - Pago Seguro',
            style: {
                maxHeight: '40px',
                width: 'auto',
                display: 'block'
            }
        })
    );
};

/**
 * Bancard payment method configuration
 */
const BancardPaymentMethod = {
    name: 'bancard',
    label: React.createElement(Label),
    content: React.createElement(Content),
    edit: React.createElement(Content),
    canMakePayment: () => true,
    ariaLabel: label,
    supports: {
        features: settings.supports || [],
    },
};

// Register the payment method
console.log('🏦 BANCARD BLOCKS: Registrando método de pago');
console.log('🏦 BANCARD BLOCKS: Configuración:', settings);
console.log('🏦 BANCARD BLOCKS: Label:', label);

    try {
        registerPaymentMethod(BancardPaymentMethod);
        console.log('✅ BANCARD BLOCKS: Método de pago registrado exitosamente');
    } catch (error) {
        console.error('❌ BANCARD BLOCKS: Error al registrar método de pago:', error);
    }
}
