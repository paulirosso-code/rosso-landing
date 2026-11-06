<?php
/**
 * Plugin Name: Rosso Landing
 * Description: Tarjetas de productos, ventanas de detalle y formularios de cotización por WhatsApp de la landing de Rosso Asesor. No guarda datos de cotización.
 * Version: 0.6.0
 * Update URI: https://github.com/paulirosso-code/rosso-landing
 * Text Domain: rosso-landing
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ROSSO_LANDING_VERSION', '0.6.0' );
define( 'ROSSO_LANDING_DIR', plugin_dir_path( __FILE__ ) );
define( 'ROSSO_LANDING_URL', plugin_dir_url( __FILE__ ) );

// Datos de contacto. Los teléfonos van solo con dígitos, con código de país.
define( 'ROSSO_WHATSAPP', '5491150550202' );
define( 'ROSSO_WHATSAPP_URGENCIAS', '5491136813003' );
define( 'ROSSO_TELEFONO', '541148991063' ); // Formato a confirmar: ver "Decisiones pendientes" en CLAUDE.md.

// Fecha de inicio del FAL (Decreto 1228/2026), usada por la cuenta regresiva.
define( 'ROSSO_FAL_FECHA', '2026-12-01' );

// Direcciones de las dos páginas: inicio y fal. Deben coincidir con el slug de cada página.
define( 'ROSSO_PAGINA_LANDING', 'inicio' );
define( 'ROSSO_PAGINA_FAL', 'fal' );

require_once ROSSO_LANDING_DIR . 'includes/productos.php';
require_once ROSSO_LANDING_DIR . 'includes/preguntas.php';
require_once ROSSO_LANDING_DIR . 'includes/vista.php';
require_once ROSSO_LANDING_DIR . 'includes/actualizaciones.php';

register_activation_hook( __FILE__, 'rosso_landing_cargar_contenido_inicial' );

add_action( 'wp_enqueue_scripts', 'rosso_landing_registrar_recursos' );

// En el editor de Elementor los widgets se agregan sin recargar la página: diseño y formularios tienen que estar siempre cargados.
add_action(
	'elementor/preview/enqueue_styles',
	function () {
		wp_enqueue_style( 'rosso-landing' );
	}
);
add_action(
	'elementor/preview/enqueue_scripts',
	function () {
		wp_enqueue_script( 'rosso-landing' );
	}
);

/**
 * Registra CSS y JS, y los carga en el encabezado si la página usa el shortcode,
 * para que no se vea un instante sin estilos.
 */
function rosso_landing_registrar_recursos() {
	wp_register_style(
		'rosso-landing-fuente',
		'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap',
		array(),
		null
	);
	wp_register_style(
		'rosso-landing',
		ROSSO_LANDING_URL . 'assets/css/landing.css',
		array( 'rosso-landing-fuente' ),
		ROSSO_LANDING_VERSION
	);
	wp_register_script(
		'rosso-landing',
		ROSSO_LANDING_URL . 'assets/js/landing.js',
		array(),
		ROSSO_LANDING_VERSION,
		true
	);

	if ( is_singular() ) {
		$id        = get_queried_object_id();
		$elementor = get_post_meta( $id, '_elementor_data', true );
		$contenido = get_post_field( 'post_content', $id ) . ( is_string( $elementor ) ? $elementor : '' );
		if ( false !== strpos( $contenido, 'rosso_landing' ) ) {
			wp_enqueue_style( 'rosso-landing' );
			wp_enqueue_script( 'rosso-landing' );
		}
	}
}

add_shortcode( 'rosso_landing', 'rosso_landing_shortcode' );
