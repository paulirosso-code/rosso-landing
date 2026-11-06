<?php
/**
 * HTML de la landing. El detalle de cada producto se imprime en la página
 * (no lo genera JavaScript) para que Google pueda leerlo.
 *
 * Uso: [rosso_landing] muestra la landing completa, o [rosso_landing seccion="personas"]
 * una parte, para intercalar secciones de Elementor. La página del FAL usa las secciones
 * encabezado, fal y pie.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function rosso_landing_shortcode( $atributos ) {
	wp_enqueue_style( 'rosso-landing' );
	wp_enqueue_script( 'rosso-landing' );

	$secciones = array(
		'encabezado' => 'rosso_landing_encabezado',
		'aviso'      => 'rosso_landing_aviso',
		'portada'    => 'rosso_landing_portada',
		'personas'   => 'rosso_landing_personas',
		'cotizar'    => 'rosso_landing_cotizar',
		'empresas'   => 'rosso_landing_empresas',
		'fal'        => 'rosso_landing_pagina_fal',
		'pie'        => 'rosso_landing_pie',
	);

	$atributos = shortcode_atts( array( 'seccion' => '' ), $atributos, 'rosso_landing' );
	$pedida    = $atributos['seccion'];
	if ( '' !== $pedida && ! isset( $secciones[ $pedida ] ) ) {
		return '';
	}

	// El FAL tiene su propia página: solo aparece si se lo pide por nombre.
	$a_mostrar = '' === $pedida ? array_diff_key( $secciones, array( 'fal' => true ) ) : array( $pedida => $secciones[ $pedida ] );

	$html = '';
	foreach ( $a_mostrar as $funcion ) {
		$html .= $funcion();
	}
	return '<div class="rosso-landing">' . $html . '</div>';
}

function rosso_landing_url_whatsapp( $mensaje = '', $numero = ROSSO_WHATSAPP ) {
	return 'https://wa.me/' . $numero . ( $mensaje ? '?text=' . rawurlencode( $mensaje ) : '' );
}

/** URL de una página por su slug. Si la landing es la portada del sitio, WordPress devuelve la raíz. */
function rosso_landing_url_pagina( $slug ) {
	$pagina = get_page_by_path( $slug );
	return $pagina ? get_permalink( $pagina ) : home_url( '/' . $slug . '/' );
}

function rosso_landing_logo( $clase = '' ) {
	return '<img class="' . esc_attr( $clase ) . '" src="' . esc_url( ROSSO_LANDING_URL . 'assets/img/logo-rosso.png' ) . '" alt="Rosso, asesor de seguros" width="432" height="152">';
}

/** Tarjetas de un grupo, cada una con su ventana de detalle. */
function rosso_landing_tarjetas( $grupo, $empresa = false ) {
	$productos = rosso_landing_productos( $grupo );
	if ( ! $productos ) {
		return '';
	}
	$html = '<div class="rl-grid">';
	foreach ( $productos as $clave => $post ) {
		$html .= sprintf(
			'<button class="rl-card" type="button" data-rosso-abrir="%s" aria-haspopup="dialog"><span class="rl-ico" aria-hidden="true">%s</span><span><strong>%s</strong><small>+ info</small></span></button>',
			esc_attr( $clave ),
			esc_html( get_post_meta( $post->ID, '_rosso_icono', true ) ),
			esc_html( $post->post_title )
		);
	}
	$html .= '</div>';
	foreach ( $productos as $clave => $post ) {
		$html .= rosso_landing_detalle( $clave, $post, $empresa );
	}
	return $html;
}

function rosso_landing_detalle( $clave, $post, $empresa ) {
	$nota = get_post_meta( $post->ID, '_rosso_nota', true );
	$id   = 'rosso-detalle-' . $clave;
	ob_start();
	?>
	<dialog class="rl-dialog" id="<?php echo esc_attr( $id ); ?>" aria-labelledby="<?php echo esc_attr( $id ); ?>-titulo">
		<div class="rl-dl<?php echo $empresa ? ' rl-dl-emp' : ''; ?>">
			<button class="rl-x" type="button" aria-label="Cerrar" data-rosso-cerrar>&times;</button>
			<h3 id="<?php echo esc_attr( $id ); ?>-titulo"><?php echo esc_html( $post->post_title ); ?></h3>
			<?php echo wpautop( wp_kses_post( $post->post_content ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php if ( $nota ) : ?>
				<p class="rl-extra"><?php echo esc_html( $nota ); ?></p>
			<?php endif; ?>
			<div class="rl-row">
				<a class="rl-btn rl-btn-main" href="<?php echo esc_url( rosso_landing_url_whatsapp( 'Hola, quiero info de: ' . $post->post_title ) ); ?>" target="_blank" rel="noopener">Consultar por WhatsApp</a>
				<button class="rl-btn" type="button" data-rosso-cerrar>Cerrar</button>
			</div>
		</div>
	</dialog>
	<?php
	return ob_get_clean();
}

/**
 * Formulario de cotización. Los campos los arma landing.js según el producto elegido.
 * Los controles no llevan "name" a propósito: así nunca viajan a WordPress.
 */
function rosso_landing_formulario( $sufijo, $pregunta, $grupos, $clase_boton ) {
	ob_start();
	?>
	<form class="rl-cot" data-rosso-cotizador data-whatsapp="<?php echo esc_attr( ROSSO_WHATSAPP ); ?>" data-sufijo="<?php echo esc_attr( $sufijo ); ?>">
		<label for="rl-que<?php echo esc_attr( $sufijo ); ?>"><?php echo esc_html( $pregunta ); ?></label>
		<select id="rl-que<?php echo esc_attr( $sufijo ); ?>" data-rosso-producto>
			<?php foreach ( $grupos as $etiqueta => $productos ) : ?>
				<optgroup label="<?php echo esc_attr( $etiqueta ); ?>">
					<?php foreach ( $productos as $clave => $post ) : ?>
						<option value="<?php echo esc_attr( $clave ); ?>"><?php echo esc_html( $post->post_title ); ?></option>
					<?php endforeach; ?>
				</optgroup>
			<?php endforeach; ?>
		</select>
		<label for="rl-nombre<?php echo esc_attr( $sufijo ); ?>">Tu nombre</label>
		<input id="rl-nombre<?php echo esc_attr( $sufijo ); ?>" type="text" autocomplete="name" data-rosso-nombre>
		<div class="rl-fg" data-rosso-campos></div>
		<button class="rl-btn <?php echo esc_attr( $clase_boton ); ?>" type="submit">Cotizá online</button>
		<p class="rl-note">Se abre WhatsApp con tu consulta armada. No guardamos tus datos en esta página.</p>
	</form>
	<?php
	return ob_get_clean();
}

function rosso_landing_encabezado() {
	$landing = rosso_landing_url_pagina( ROSSO_PAGINA_LANDING );
	ob_start();
	?>
	<div class="rl-header-lugar">
		<header class="rl-header">
			<div class="rl-nav">
				<a href="<?php echo esc_url( $landing ); ?>" aria-label="Rosso, asesor de seguros"><?php echo rosso_landing_logo(); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
				<nav class="rl-links" aria-label="Principal">
					<a href="<?php echo esc_url( $landing . '#personas' ); ?>">Personas</a>
					<a href="<?php echo esc_url( $landing . '#empresas' ); ?>">Empresas</a>
					<a href="<?php echo esc_url( $landing . '#siniestros' ); ?>">Siniestros</a>
					<a class="rl-btn rl-btn-main" href="<?php echo esc_url( rosso_landing_url_whatsapp( 'Hola, quiero hablar con un asesor' ) ); ?>" target="_blank" rel="noopener">Asesor virtual</a>
				</nav>
			</div>
		</header>
	</div>
	<?php
	return ob_get_clean();
}

function rosso_landing_aviso() {
	ob_start();
	?>
	<div class="rl-alert" data-rosso-fal="<?php echo esc_attr( ROSSO_FAL_FECHA ); ?>">
		<div class="rl-wrap">
			<span>🔔 <b>El FAL es obligatorio desde el 1 de diciembre de 2026.</b> <span data-rosso-dias-frase></span></span>
			<a href="<?php echo esc_url( rosso_landing_url_pagina( ROSSO_PAGINA_FAL ) ); ?>">Ver más</a>
		</div>
	</div>
	<?php
	return ob_get_clean();
}

function rosso_landing_portada() {
	ob_start();
	?>
	<div class="rl-hero">
		<div class="rl-wrap rl-hero-titulo">
			<div>
				<h1>Las mejores compañías, en un solo lugar</h1>
				<p class="rl-lead">Seguros para vos, tu familia y tu empresa. Buscamos la mejor opción entre las principales compañías del mercado.</p>
			</div>
			<div class="rl-orbs" aria-hidden="true"><i></i><i></i><i></i><i></i></div>
		</div>
		<div class="rl-wrap rl-hero-puertas">
			<div class="rl-doors">
				<a class="rl-door rl-door-p" href="#personas">
					<span class="rl-big"></span>
					<span class="rl-em" aria-hidden="true">🏡</span>
					<h2>Personas y familias</h2>
					<p>Protegé lo que más querés.</p>
					<ul><li>Automotor</li><li>Hogar</li><li>Motos</li><li>Retiro</li></ul>
					<span class="rl-go">Ver seguros</span>
				</a>
				<a class="rl-door rl-door-e" href="#empresas">
					<span class="rl-big"></span>
					<span class="rl-em" aria-hidden="true">🏢</span>
					<h2>Empresas</h2>
					<p>Seguros y servicios para tu negocio.</p>
					<ul><li>Integral de comercio</li><li>ART</li><li>Seguridad e higiene</li><li>FAL</li></ul>
					<span class="rl-go">Ver para empresas</span>
				</a>
			</div>
			<p class="rl-trust">Matrícula SSN N.º 1093 · Siniestros 24 hs · Atención personalizada</p>
		</div>
	</div>
	<?php
	return ob_get_clean();
}

function rosso_landing_personas() {
	ob_start();
	?>
	<section class="rl-section" id="personas">
		<div class="rl-wrap">
			<h2>Para personas y familias</h2>
			<p>Tocá cada seguro para ver qué comprende.</p>
			<?php echo rosso_landing_tarjetas( 'personas' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</div>
	</section>
	<?php
	return ob_get_clean();
}

function rosso_landing_cotizar() {
	ob_start();
	?>
	<section class="rl-section rl-pasos" id="cotiza">
		<div class="rl-wrap">
			<h2>¡Cotizá en tres simples pasos!</h2>
			<ol class="rl-steps">
				<li><span>1</span>Elegí tu seguro</li>
				<li><span>2</span>Nuestros asesores evaluarán la mejor opción</li>
				<li><span>3</span>¡Listo! Ya tenés tu cotización 100% online</li>
			</ol>
			<?php
			echo rosso_landing_formulario( // phpcs:ignore WordPress.Security.EscapeOutput
				'-per',
				'¿Qué querés asegurar?',
				array( 'Seguros para personas' => rosso_landing_productos( 'personas' ) ),
				'rl-btn-main'
			);
			?>
		</div>
	</section>
	<?php
	return ob_get_clean();
}

function rosso_landing_empresas() {
	$info      = rosso_landing_productos( 'fal-info' );
	$servicios = rosso_landing_productos( 'servicios-empresa' );
	if ( isset( $info['fal'] ) ) {
		$servicios = array_merge( array( 'fal' => $info['fal'] ), $servicios );
	}
	ob_start();
	?>
	<section class="rl-section rl-emp" id="empresas">
		<div class="rl-wrap">
			<h2>Para empresas</h2>
			<p>Cobertura para tu negocio y servicios para que cumplas con lo que la ley te pide.</p>

			<div class="rl-fal" id="fal">
				<div>
					<span class="rl-pill">Propuesta destacada</span>
					<h3>FAL: llegá a diciembre con todo resuelto</h3>
					<p>El Fondo de Asistencia Laboral es obligatorio para los empleadores privados. Te ayudamos a elegir quién lo administra y a hacer el alta en ARCA.</p>
					<a class="rl-btn rl-btn-blue" href="<?php echo esc_url( rosso_landing_url_pagina( ROSSO_PAGINA_FAL ) ); ?>">Conocé todo sobre el FAL</a>
				</div>
				<?php echo rosso_landing_cuenta_fal(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</div>

			<h3 class="rl-sub">Seguros para empresas</h3>
			<?php echo rosso_landing_tarjetas( 'seguros-empresa', true ); // phpcs:ignore WordPress.Security.EscapeOutput ?>

			<h3 class="rl-sub">Servicios para empresas</h3>
			<?php echo rosso_landing_tarjetas( 'servicios-empresa', true ); // phpcs:ignore WordPress.Security.EscapeOutput ?>

			<h3 class="rl-sub" id="cotiza-emp">Cotizá para tu empresa</h3>
			<p>Elegí el seguro o servicio que necesitás y completá los datos. Te respondemos por WhatsApp.</p>
			<?php
			echo rosso_landing_formulario( // phpcs:ignore WordPress.Security.EscapeOutput
				'-emp',
				'¿Qué necesitás?',
				array(
					'Seguros para empresas'   => rosso_landing_productos( 'seguros-empresa' ),
					'Servicios para empresas' => $servicios,
				),
				'rl-btn-blue'
			);
			?>
		</div>
	</section>
	<?php
	return ob_get_clean();
}

function rosso_landing_cuenta_fal() {
	ob_start();
	?>
	<aside data-rosso-fal="<?php echo esc_attr( ROSSO_FAL_FECHA ); ?>">
		<strong data-rosso-dias>1 dic</strong>
		<span data-rosso-dias-texto>Fecha de entrada en vigencia del FAL: 1 de diciembre de 2026.</span>
	</aside>
	<?php
	return ob_get_clean();
}

/** Página propia del FAL. "Qué es" y "Cómo dar el alta" se editan desde el menú Productos. */
function rosso_landing_pagina_fal() {
	$info      = rosso_landing_productos( 'fal-info' );
	$entidades = rosso_landing_tarjetas( 'entidades-fal', true );
	ob_start();
	?>
	<section class="rl-section rl-emp" id="fal">
		<div class="rl-wrap">
			<h1>Fondo de Asistencia Laboral (FAL)</h1>
			<p>El FAL es obligatorio para los empleadores privados. Te ayudamos a elegir quién lo administra y a hacer el alta en ARCA.</p>

			<div class="rl-fal">
				<div>
					<span class="rl-pill">Propuesta destacada</span>
					<h2>Llegá a diciembre con todo resuelto</h2>
					<p>Contanos sobre tu empresa y te ayudamos a comparar opciones.</p>
					<a class="rl-btn rl-btn-blue" href="<?php echo esc_url( rosso_landing_url_whatsapp( 'Hola, quiero una propuesta para el Fondo de Asistencia Laboral (FAL) de mi empresa' ) ); ?>" target="_blank" rel="noopener">Quiero mi propuesta FAL</a>
				</div>
				<?php echo rosso_landing_cuenta_fal(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</div>

			<div class="rl-stats">
				<div><b>1/12</b>Entra en vigencia en diciembre de 2026</div>
				<div><b>1% · 2,5%</b>Resto del sector privado · MiPyMEs. Sale de las contribuciones patronales</div>
				<div><b>12 meses</b>De aportes antes de poder usar el fondo</div>
				<div class="rl-warn"><b>De oficio</b>Si tu empresa no elige, la CNV le asigna una entidad</div>
			</div>

			<div class="rl-fal-textos">
				<?php foreach ( $info as $post ) : ?>
					<?php $nota = get_post_meta( $post->ID, '_rosso_nota', true ); ?>
					<article class="rl-dl rl-dl-emp">
						<h2><?php echo esc_html( $post->post_title ); ?></h2>
						<?php echo wpautop( wp_kses_post( $post->post_content ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<?php if ( $nota ) : ?>
							<p class="rl-extra"><?php echo esc_html( $nota ); ?></p>
						<?php endif; ?>
					</article>
				<?php endforeach; ?>
			</div>

			<?php if ( $entidades ) : ?>
				<h3 class="rl-sub">Opciones para administrar tu FAL</h3>
				<?php echo $entidades; // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<p class="rl-note2">Información basada en las presentaciones de cada entidad (septiembre de 2026). No constituye asesoramiento legal, contable ni financiero. La elección no es definitiva: el fondo se puede trasladar a otra entidad.</p>
			<?php endif; ?>

			<?php if ( isset( $info['fal'] ) ) : ?>
				<h3 class="rl-sub">Pedí tu propuesta</h3>
				<p>Completá los datos y te respondemos por WhatsApp.</p>
				<?php
				echo rosso_landing_formulario( // phpcs:ignore WordPress.Security.EscapeOutput
					'-fal',
					'¿Qué necesitás?',
					array( 'Servicios para empresas' => array( 'fal' => $info['fal'] ) ),
					'rl-btn-blue'
				);
				?>
			<?php endif; ?>
		</div>
	</section>
	<?php
	return ob_get_clean();
}

function rosso_landing_pie() {
	ob_start();
	?>
	<footer class="rl-footer">
		<div class="rl-wrap">
			<div class="rl-fgrid">
				<div><?php echo rosso_landing_logo(); // phpcs:ignore WordPress.Security.EscapeOutput ?><p>Creemos que el trabajo en equipo es la herramienta más valiosa que tenemos. Por eso nuestros colaboradores son el activo más importante de Rosso Asesor de Seguros.</p></div>
				<div><p><strong>Siniestros 24 hs</strong></p><p>WhatsApp: <a href="<?php echo esc_url( rosso_landing_url_whatsapp( '', ROSSO_WHATSAPP_URGENCIAS ) ); ?>" target="_blank" rel="noopener">+54 9 11 3681 3003</a></p><p><a href="mailto:mjrecalde@rossoasesor.com.ar">mjrecalde@rossoasesor.com.ar</a></p></div>
				<div><p><strong>Atención al cliente</strong></p><p>Llamadas: <a href="tel:+<?php echo esc_attr( ROSSO_TELEFONO ); ?>">+54 11 4899-1063</a></p><p>WhatsApp: <a href="<?php echo esc_url( rosso_landing_url_whatsapp() ); ?>" target="_blank" rel="noopener">+54 9 11 5055 0202</a></p></div>
				<div><p><strong>N.º de Matrícula: 1093</strong></p><p>Superintendencia de Seguros de la Nación<br><a href="https://www.argentina.gob.ar/ssn" target="_blank" rel="noopener">argentina.gob.ar/ssn</a></p></div>
			</div>
		</div>
		<div class="rl-legal"><div class="rl-wrap">Rosso Asesor © <?php echo esc_html( gmdate( 'Y' ) ); ?></div></div>
	</footer>
	<a class="rl-wa" href="<?php echo esc_url( rosso_landing_url_whatsapp() ); ?>" target="_blank" rel="noopener" aria-label="Escribir por WhatsApp">WhatsApp</a>
	<?php
	return ob_get_clean();
}
