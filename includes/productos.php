<?php
/**
 * Productos: el equipo edita sus textos desde el menú "Productos" de WordPress.
 * La clave de cada producto (post_name) conecta el texto con su formulario en landing.js.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const ROSSO_CPT = 'rosso_producto';

add_action( 'init', 'rosso_landing_registrar_productos' );
add_action( 'add_meta_boxes_' . ROSSO_CPT, 'rosso_landing_agregar_caja' );
add_action( 'save_post_' . ROSSO_CPT, 'rosso_landing_guardar_caja' );

/** Grupos de productos y dónde aparece cada uno en la landing. */
function rosso_landing_grupos() {
	return array(
		'personas'          => 'Personas y familias',
		'seguros-empresa'   => 'Seguros para empresas',
		'servicios-empresa' => 'Servicios para empresas',
		'fal-info'          => 'FAL: tarjeta y formulario de Empresas',
		'entidades-fal'     => 'Página FAL: entidades que lo administran',
	);
}

function rosso_landing_registrar_productos() {
	// Sin show_in_rest: se edita con el editor clásico, más simple para listas y textos.
	register_post_type(
		ROSSO_CPT,
		array(
			'labels'       => array(
				'name'          => 'Productos',
				'singular_name' => 'Producto',
				'add_new_item'  => 'Agregar producto',
				'edit_item'     => 'Editar producto',
				'search_items'  => 'Buscar productos',
				'not_found'     => 'No hay productos',
			),
			'public'       => false,
			'show_ui'      => true,
			'menu_icon'    => 'dashicons-shield',
			'hierarchical' => false,
			'supports'     => array( 'title', 'editor', 'page-attributes' ),
		)
	);
}

function rosso_landing_agregar_caja() {
	add_meta_box( 'rosso-producto-datos', 'Datos de la tarjeta', 'rosso_landing_mostrar_caja', ROSSO_CPT, 'side', 'high' );
}

function rosso_landing_mostrar_caja( $post ) {
	wp_nonce_field( 'rosso_producto_guardar', 'rosso_producto_nonce' );
	$grupo = get_post_meta( $post->ID, '_rosso_grupo', true );
	?>
	<p><label for="rosso-grupo"><strong>Dónde aparece</strong></label><br>
	<select id="rosso-grupo" name="rosso_grupo">
		<?php foreach ( rosso_landing_grupos() as $clave => $nombre ) : ?>
			<option value="<?php echo esc_attr( $clave ); ?>" <?php selected( $grupo, $clave ); ?>><?php echo esc_html( $nombre ); ?></option>
		<?php endforeach; ?>
	</select></p>
	<p><label for="rosso-icono"><strong>Icono (un emoji)</strong></label><br>
	<input id="rosso-icono" name="rosso_icono" type="text" size="4" value="<?php echo esc_attr( get_post_meta( $post->ID, '_rosso_icono', true ) ); ?>"></p>
	<p><label for="rosso-nota"><strong>Nota destacada (opcional)</strong></label><br>
	<textarea id="rosso-nota" name="rosso_nota" rows="5" style="width:100%"><?php echo esc_textarea( get_post_meta( $post->ID, '_rosso_nota', true ) ); ?></textarea></p>
	<?php
}

function rosso_landing_guardar_caja( $post_id ) {
	if ( ! isset( $_POST['rosso_producto_nonce'] )
		|| ! wp_verify_nonce( sanitize_key( $_POST['rosso_producto_nonce'] ), 'rosso_producto_guardar' )
		|| ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
		|| ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$grupo = sanitize_key( wp_unslash( $_POST['rosso_grupo'] ?? '' ) );
	if ( array_key_exists( $grupo, rosso_landing_grupos() ) ) {
		update_post_meta( $post_id, '_rosso_grupo', $grupo );
	}
	update_post_meta( $post_id, '_rosso_icono', sanitize_text_field( wp_unslash( $_POST['rosso_icono'] ?? '' ) ) );
	update_post_meta( $post_id, '_rosso_nota', sanitize_textarea_field( wp_unslash( $_POST['rosso_nota'] ?? '' ) ) );
}

/**
 * Crea los productos la primera vez que se activa el plugin. Si después el equipo
 * edita o borra uno, volver a activar el plugin no lo pisa ni lo recrea.
 */
function rosso_landing_cargar_contenido_inicial() {
	rosso_landing_registrar_productos();
	if ( get_option( 'rosso_landing_contenido_cargado' ) ) {
		return;
	}
	$productos = json_decode( file_get_contents( ROSSO_LANDING_DIR . 'includes/contenido-inicial.json' ), true );
	foreach ( $productos as $p ) {
		wp_insert_post( wp_slash( rosso_landing_datos_producto( $p ) ) );
	}
	update_option( 'rosso_landing_contenido_cargado', 1 );
}

/** Convierte un producto de los archivos JSON en los datos de una entrada de WordPress. */
function rosso_landing_datos_producto( $p ) {
	$items = array_map(
		function ( $item ) {
			return '<li>' . esc_html( $item ) . '</li>';
		},
		$p['lista']
	);
	return array(
		'post_type'    => ROSSO_CPT,
		'post_status'  => $p['estado'],
		'post_title'   => $p['nombre'],
		'post_name'    => $p['clave'],
		'post_content' => '<p>' . esc_html( $p['descripcion'] ) . "</p>\n<ul>\n" . implode( "\n", $items ) . "\n</ul>",
		'menu_order'   => $p['orden'],
		'meta_input'   => array(
			'_rosso_grupo' => $p['grupo'],
			'_rosso_icono' => $p['icono'],
			'_rosso_nota'  => $p['nota'],
		),
	);
}

add_action( 'init', 'rosso_landing_actualizar_entidades_fal', 20 );

/**
 * Carga una sola vez las seis entidades FAL del material final de noviembre de 2026
 * (Allaria, Balanz, BIND, GST, Pellegrini y Sancor). Pisa las entradas anteriores con la misma clave
 * y pasa a borrador "Cómo dar de alta", que la página FAL ya no muestra.
 * Para una carga futura, cambiar el número de versión. La 2 era el material anterior, con cinco entidades;
 * la 4 corrige el texto de BIND según el PDF final.
 */
function rosso_landing_actualizar_entidades_fal() {
	$version = 4;
	if ( (int) get_option( 'rosso_landing_entidades_fal' ) >= $version ) {
		return;
	}
	// Se marca antes de cargar para que dos visitas simultáneas no dupliquen entradas.
	update_option( 'rosso_landing_entidades_fal', $version );

	$entidades = json_decode( file_get_contents( ROSSO_LANDING_DIR . 'includes/entidades-fal.json' ), true );
	foreach ( (array) $entidades as $p ) {
		$datos  = rosso_landing_datos_producto( $p );
		$actual = get_posts(
			array(
				'post_type'   => ROSSO_CPT,
				'name'        => $p['clave'],
				// "any" no encuentra borradores de este tipo de entrada: los estados van uno por uno.
				'post_status' => array( 'publish', 'draft', 'pending', 'private', 'future' ),
				'numberposts' => 1,
			)
		);
		if ( $actual ) {
			$datos['ID'] = $actual[0]->ID;
			wp_update_post( wp_slash( $datos ) );
		} else {
			wp_insert_post( wp_slash( $datos ) );
		}
	}

	$alta = get_posts(
		array(
			'post_type'   => ROSSO_CPT,
			'name'        => 'alta',
			'post_status' => 'publish',
			'numberposts' => 1,
		)
	);
	if ( $alta ) {
		wp_update_post(
			array(
				'ID'          => $alta[0]->ID,
				'post_status' => 'draft',
			)
		);
	}
}

/** Devuelve los productos publicados de un grupo, en el orden indicado por el equipo. */
function rosso_landing_productos( $grupo ) {
	static $por_grupo = null;
	if ( null === $por_grupo ) {
		$por_grupo = array();
		$posts     = get_posts(
			array(
				'post_type'   => ROSSO_CPT,
				'post_status' => 'publish',
				'numberposts' => -1,
				'orderby'     => array(
					'menu_order' => 'ASC',
					'title'      => 'ASC',
				),
			)
		);
		foreach ( $posts as $post ) {
			$por_grupo[ get_post_meta( $post->ID, '_rosso_grupo', true ) ][ $post->post_name ] = $post;
		}
	}
	return $por_grupo[ $grupo ] ?? array();
}
