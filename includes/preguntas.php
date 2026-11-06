<?php
/**
 * Preguntas frecuentes: el equipo las edita desde el menú "Preguntas frecuentes" de WordPress.
 * El título es la pregunta y el texto es la respuesta. El campo "Orden" define en qué lugar aparece.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const ROSSO_CPT_PREGUNTA = 'rosso_pregunta';

add_action( 'init', 'rosso_landing_registrar_preguntas' );
add_action( 'init', 'rosso_landing_cargar_preguntas_iniciales', 20 );

function rosso_landing_registrar_preguntas() {
	register_post_type(
		ROSSO_CPT_PREGUNTA,
		array(
			'labels'       => array(
				'name'          => 'Preguntas frecuentes',
				'singular_name' => 'Pregunta',
				'add_new'       => 'Agregar pregunta',
				'add_new_item'  => 'Agregar pregunta',
				'edit_item'     => 'Editar pregunta',
				'search_items'  => 'Buscar preguntas',
				'not_found'     => 'No hay preguntas',
				'enter_title_here' => 'Escribí la pregunta',
			),
			'public'       => false,
			'show_ui'      => true,
			'menu_icon'    => 'dashicons-editor-help',
			'hierarchical' => false,
			'supports'     => array( 'title', 'editor', 'page-attributes' ),
		)
	);
}

/**
 * Carga las preguntas del prototipo una sola vez. Corre en "init" y no al activar el plugin,
 * porque los sitios que ya lo tenían instalado lo reciben como actualización, sin activarlo de nuevo.
 */
function rosso_landing_cargar_preguntas_iniciales() {
	if ( get_option( 'rosso_landing_preguntas_cargadas' ) ) {
		return;
	}
	// Se marca antes de cargar para que dos visitas simultáneas no las dupliquen.
	update_option( 'rosso_landing_preguntas_cargadas', 1 );
	$preguntas = json_decode( file_get_contents( ROSSO_LANDING_DIR . 'includes/preguntas-iniciales.json' ), true );
	foreach ( (array) $preguntas as $p ) {
		wp_insert_post(
			wp_slash(
				array(
					'post_type'    => ROSSO_CPT_PREGUNTA,
					'post_status'  => $p['estado'],
					'post_title'   => $p['pregunta'],
					'post_content' => $p['respuesta'],
					'menu_order'   => $p['orden'],
				)
			)
		);
	}
}

/** Preguntas publicadas, en el orden indicado por el equipo. */
function rosso_landing_preguntas() {
	return get_posts(
		array(
			'post_type'   => ROSSO_CPT_PREGUNTA,
			'post_status' => 'publish',
			'numberposts' => -1,
			'orderby'     => array(
				'menu_order' => 'ASC',
				'title'      => 'ASC',
			),
		)
	);
}
