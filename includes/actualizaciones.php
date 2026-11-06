<?php
/**
 * Actualizaciones desde GitHub: hace aparecer el botón "Actualizar" en Plugins.
 * Cada versión publicada incluye rosso-landing.json, con el número de versión y la dirección del zip.
 * Se publica con scripts/publicar-plugin.sh del repositorio del proyecto.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ROSSO_LANDING_DATOS_VERSION', 'https://github.com/paulirosso-code/rosso-landing/releases/latest/download/rosso-landing.json' );

/** Datos de la última versión publicada, o null si GitHub no responde bien. */
function rosso_landing_ultima_version() {
	$respuesta = wp_remote_get( ROSSO_LANDING_DATOS_VERSION, array( 'timeout' => 10 ) );
	if ( is_wp_error( $respuesta ) || 200 !== wp_remote_retrieve_response_code( $respuesta ) ) {
		return null;
	}
	$datos = json_decode( wp_remote_retrieve_body( $respuesta ), true );
	return isset( $datos['version'], $datos['package'] ) ? $datos : null;
}

// WordPress llama a este filtro por el encabezado "Update URI" de rosso-landing.php.
add_filter(
	'update_plugins_github.com',
	function ( $actualizacion, $datos_plugin, $archivo ) {
		if ( plugin_basename( ROSSO_LANDING_DIR . 'rosso-landing.php' ) !== $archivo ) {
			return $actualizacion;
		}
		$ultima = rosso_landing_ultima_version();
		if ( ! $ultima ) {
			return $actualizacion;
		}
		return array(
			'slug'    => 'rosso-landing',
			'version' => $ultima['version'],
			'url'     => 'https://github.com/paulirosso-code/rosso-landing',
			'package' => $ultima['package'],
		);
	},
	10,
	3
);

// El enlace "Ver detalles" busca el plugin en wordpress.org, donde no está: sin esto muestra un error.
add_filter(
	'plugins_api',
	function ( $resultado, $accion, $argumentos ) {
		if ( 'plugin_information' !== $accion || ! isset( $argumentos->slug ) || 'rosso-landing' !== $argumentos->slug ) {
			return $resultado;
		}
		$ultima = rosso_landing_ultima_version();
		return (object) array(
			'name'     => 'Rosso Landing',
			'slug'     => 'rosso-landing',
			'version'  => $ultima ? $ultima['version'] : ROSSO_LANDING_VERSION,
			'sections' => array( 'description' => 'Plugin de la web de Rosso Asesor.' ),
		);
	},
	10,
	3
);
