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
		'preguntas'  => 'rosso_landing_seccion_preguntas',
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

/**
 * Página propia del FAL, con la estructura del material "FAL Rosso Empresas" (noviembre de 2026).
 * Las entidades se editan desde el menú Productos; el resto de los textos, acá.
 */
function rosso_landing_pagina_fal() {
	$info      = rosso_landing_productos( 'fal-info' );
	$entidades = rosso_landing_productos( 'entidades-fal' );
	$propuesta = rosso_landing_url_whatsapp( 'Hola, quiero una propuesta para el Fondo de Asistencia Laboral (FAL) de mi empresa' );
	ob_start();
	?>
	<div class="rl-falp" id="fal">
		<section class="rl-section rl-emp rl-falp-portada">
			<div class="rl-wrap">
				<p class="rl-kicker">Empresas · Fondo de Asistencia Laboral</p>
				<h1>Desde el<br><span class="rl-resalte">1° de diciembre</span><br>el FAL es obligatorio</h1>
				<p class="rl-lead">Si tu empresa no elige quién lo administra, <strong>la CNV elige por vos.</strong></p>
				<div class="rl-fal">
					<div>
						<span class="rl-pill">Te hacemos el trámite</span>
						<h2>Llegá a diciembre con todo resuelto</h2>
						<p>Elegimos juntos la entidad y coordinamos el alta. Vos solo validás en ARCA.</p>
						<a class="rl-btn rl-btn-blue" href="<?php echo esc_url( $propuesta ); ?>" target="_blank" rel="noopener">Quiero mi propuesta FAL</a>
					</div>
					<?php echo rosso_landing_cuenta_fal(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</div>
			</div>
		</section>

		<section class="rl-section">
			<div class="rl-wrap">
				<p class="rl-kicker">Qué es el FAL</p>
				<h2>Un fondo a nombre de tu empresa para ayudar a pagar indemnizaciones</h2>
				<div class="rl-cifras">
					<div class="rl-cifra-oscura"><b>1/12</b><p>Entra en vigencia. El primer aporte va en el F.931 de diciembre.</p></div>
					<div>
						<b>2,5%</b><p><strong>MiPyMEs</strong> con certificado vigente y <strong>entidades sin fines de lucro</strong>.</p>
						<b class="rl-cifra-segunda">1%</b><p><strong>Grandes empresas</strong> del sector privado.</p>
					</div>
					<div><b>12 <small>meses</small></b><p>De aportes antes de poder usar el fondo.</p></div>
					<div class="rl-cifra-azul"><b>De oficio</b><p>Si no elegís entidad, la <strong>CNV</strong> te asigna una.</p></div>
				</div>
				<p class="rl-ejemplo"><strong>No pagás más que hoy:</strong> el aporte al FAL se descuenta de las contribuciones patronales. Mirá el ejemplo más abajo.</p>
				<p class="rl-fuente">La indemnización sigue a cargo del empleador. Ley 27.802, Decretos 408/2026 y 1228/2026, RG ARCA 5907 y 5915/2026.</p>
			</div>
		</section>

		<section class="rl-section rl-celeste">
			<div class="rl-wrap">
				<p class="rl-kicker">Un ejemplo con números</p>
				<h2>Cuánto aportás y cuánto más pagás: nada</h2>
				<div class="rl-cuentas">
					<div class="rl-cuenta">
						<div class="rl-cuenta-quien"><h3>MiPyME</h3><p>Con certificado vigente, o entidad sin fines de lucro</p></div>
						<div class="rl-monto"><small>Sueldos brutos del mes</small><b>$1.000.000</b></div>
						<span class="rl-por">× 2,5%</span>
						<div class="rl-monto rl-monto-oscuro"><small>Aporte al FAL por mes</small><b>$25.000</b></div>
						<span class="rl-por" aria-hidden="true">→</span>
						<div class="rl-monto rl-monto-borde"><small>Aportado en 12 meses</small><b>$300.000</b></div>
					</div>
					<div class="rl-cuenta">
						<div class="rl-cuenta-quien"><h3>Gran empresa</h3><p>Del sector privado</p></div>
						<div class="rl-monto"><small>Sueldos brutos del mes</small><b>$1.000.000</b></div>
						<span class="rl-por">× 1%</span>
						<div class="rl-monto rl-monto-azul"><small>Aporte al FAL por mes</small><b>$10.000</b></div>
						<span class="rl-por" aria-hidden="true">→</span>
						<div class="rl-monto rl-monto-borde"><small>Aportado en 12 meses</small><b>$120.000</b></div>
					</div>
				</div>
				<div class="rl-barras-hoy">
					<p class="rl-barra-quien"><strong>Hoy</strong></p>
					<p class="rl-barra"><span>Contribuciones patronales que ya pagás · todo va a la seguridad social</span></p>
					<p class="rl-barra-quien"><strong>Desde diciembre</strong><small>ejemplo MiPyME</small></p>
					<p class="rl-barra"><span>El mismo total · una parte a la seguridad social</span><span class="rl-barra-fal">$25.000 a tu FAL</span></p>
				</div>
				<div class="rl-definiciones">
					<p><b>2,5%</b><span><strong>MiPyMEs con certificado vigente</strong> y <strong>entidades sin fines de lucro</strong>: cooperativas, asociaciones, mutuales, fundaciones e instituciones religiosas.</span></p>
					<p><b>1%</b><span><strong>Grandes empresas del sector privado</strong>: las que no tienen certificado MiPyME vigente.</span></p>
				</div>
				<p class="rl-fuente">Ejemplo ilustrativo. A lo aportado se suma un rendimiento: lo explicamos a continuación. Excepción: por trabajadores del RIFL el aporte no se descuenta.</p>
			</div>
		</section>

		<section class="rl-section">
			<div class="rl-wrap">
				<p class="rl-kicker">Además, el fondo se invierte</p>
				<h2>Tus aportes no quedan quietos: suman un rendimiento</h2>
				<div class="rl-rinde">
					<ol class="rl-rinde-pasos">
						<li><span>1</span><p>Cada mes, tu aporte entra a la cuenta de tu empresa en el fondo que elegiste.</p></li>
						<li><span>2</span><p>La entidad lo invierte, por ejemplo en bonos o en instrumentos que ajustan por inflación.</p></li>
						<li><span>3</span><p>A lo que aportaste se le suma un <strong>rendimiento</strong>, que depende de la entidad y del fondo que elijas.</p></li>
						<li><span>4</span><p>Y el rendimiento también rinde: <strong>cuanto más tiempo pasa, más se nota.</strong></p></li>
					</ol>
					<figure class="rl-grafico">
						<span class="rl-pill">Ejemplo ilustrativo</span>
						<div class="rl-columnas" aria-hidden="true">
							<?php
							// Alturas tomadas del gráfico del PDF, en unidades de un año de aportes.
							$rendimientos = array( 0.1, 0.45, 1.13, 2.3, 3.94 );
							foreach ( $rendimientos as $i => $rendimiento ) :
								$anio = $i + 1;
								?>
								<div>
									<i class="rl-col-rinde" style="height:<?php echo esc_attr( round( $rendimiento / 9 * 100, 1 ) ); ?>%"></i>
									<i class="rl-col-aporte" style="height:<?php echo esc_attr( round( $anio / 9 * 100, 1 ) ); ?>%"></i>
									<small>Año <?php echo (int) $anio; ?></small>
								</div>
							<?php endforeach; ?>
						</div>
						<figcaption><span class="rl-ref-aporte"></span>Lo que aportaste <span class="rl-ref-rinde"></span>El rendimiento</figcaption>
					</figure>
				</div>
				<p class="rl-ejemplo rl-ejemplo-relleno"><strong>Es un respaldo para indemnizaciones, no una inversión para ganar plata.</strong> El rendimiento no está garantizado y varía según la opción que elijas.</p>
				<p class="rl-fuente">Gráfico ilustrativo: aportes mensuales iguales durante 5 años. No representa un rendimiento garantizado ni incluye la comisión de administración (tope legal 1%).</p>
			</div>
		</section>

		<section class="rl-section rl-emp">
			<div class="rl-wrap">
				<p class="rl-kicker">Qué pasa si no elegís</p>
				<h2>Si no elegís, te toca la entidad que sigue en la lista</h2>
				<ol class="rl-pasos4">
					<li><span>1</span><h3>ARCA retiene</h3><p>Sin ID FAL validado, tus aportes quedan retenidos, sin ir a ninguna cuenta.</p></li>
					<li><span>2</span><h3>Avisa a la CNV</h3><p>Al mes del vencimiento sin ID válido, ARCA informa tu CUIT a la CNV.</p></li>
					<li><span>3</span><h3>La CNV asigna</h3><p>Por orden cronológico entre las entidades anotadas. No por lo que le conviene a tu empresa.</p></li>
					<li><span>4</span><h3>Te abren la cuenta</h3><p>La entidad asignada la abre en 5 días hábiles, sin tu consentimiento.</p></li>
				</ol>
				<div class="rl-cajas3">
					<div><h3>Demora</h3><p>Los aportes tardan en acreditarse en tu cuenta.</p></div>
					<div><h3>Menos rendimiento</h3><p>Mientras están retenidos, no rinden.</p></div>
					<div><h3>Posibles sanciones</h3><p>Además de una administradora que no elegiste.</p></div>
				</div>
				<p class="rl-fuente">RG CNV 1167/2026 (Título V, Cap. X) y RG ARCA 5907/2026. Después se puede pedir el traslado a otra entidad.</p>
			</div>
		</section>

		<?php if ( $entidades ) : ?>
			<section class="rl-section">
				<div class="rl-wrap">
					<p class="rl-kicker">Quién lo administra</p>
					<h2><?php echo esc_html( rosso_landing_cantidad_opciones( count( $entidades ) ) ); ?> Vos elegís.</h2>
					<p>Todas están abiertas a cualquier empresa. Te ayudamos a compararlas según lo que buscás.</p>
					<div class="rl-entidades">
						<?php foreach ( $entidades as $post ) : ?>
							<?php echo rosso_landing_entidad( $post ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<?php endforeach; ?>
					</div>
					<p class="rl-fuente">*Hasta el 31/12/2027, adhiriendo antes del 31/12/2026 (en cuenta corriente BNA). En orden alfabético. Información al 06/11/2026. Tope de comisión: 1%. El fondo se puede trasladar.</p>
				</div>
			</section>
		<?php endif; ?>

		<section class="rl-section rl-celeste">
			<div class="rl-wrap">
				<p class="rl-kicker">Cómo lo hacemos</p>
				<h2>Te hacemos el trámite de punta a punta</h2>
				<ol class="rl-pasos4">
					<li><span>1</span><h3>Elegimos juntos</h3><p>En una llamada de 15 minutos vemos qué entidad encaja con tu empresa.</p></li>
					<li><span>2</span><h3>Abrís la cuenta</h3><p>Armamos la carpeta y coordinamos el alta con la entidad.</p></li>
					<li><span>3</span><h3>Validás en ARCA</h3><p>Vos o tu contador confirman el ID FAL, con nuestra guía paso a paso.</p></li>
					<li><span>4</span><h3>Primer aporte</h3><p>En el F.931 de diciembre, que se paga en enero 2027 con VEP 266-019-019.</p></li>
				</ol>
				<div class="rl-avisos">
					<p class="rl-aviso-oscuro"><span class="rl-pill">Plazo</span> <span>Los pasos 1 a 3, <strong>antes del F.931 de diciembre</strong>.</span></p>
					<p class="rl-aviso-claro"><span aria-hidden="true">🔒</span> <strong>Nunca te pedimos tu Clave Fiscal.</strong></p>
				</div>
			</div>
		</section>

		<section class="rl-section">
			<div class="rl-wrap">
				<p class="rl-kicker">Para empezar</p>
				<h2>Qué necesitamos de tu empresa</h2>
				<div class="rl-requisitos">
					<div class="rl-checklist">
						<h3>Documentación, en PDF</h3>
						<ul>
							<li>Constancia de inscripción en ARCA</li>
							<li>Estatuto o contrato social</li>
							<li>Acta de designación de autoridades</li>
							<li>Poder del firmante, si corresponde</li>
							<li>DNI de autoridades y apoderados</li>
							<li>Certificado MiPyME, si lo tenés</li>
							<li>Constancia de CBU de la empresa</li>
							<li>Contacto de tu contador</li>
						</ul>
						<p class="rl-fuente">Cada entidad puede pedir algún dato adicional.</p>
					</div>
					<div class="rl-notas">
						<div class="rl-nota-oscura"><h3><span aria-hidden="true">🔒</span> Nunca te pedimos tu Clave Fiscal</h3><p>Los pasos en ARCA los hacés vos o tu contador, con Clave Fiscal nivel 3 del representante. Si alguien te la pide en nuestro nombre, no la des.</p></div>
						<div><h3>Revisá tu certificado MiPyME</h3><p>Si no figura vigente en ARCA, aportás 1%, como gran empresa.</p></div>
						<div><h3>Podés cambiar después</h3><p>El fondo se puede trasladar a otra entidad habilitada.</p></div>
					</div>
				</div>
			</div>
		</section>

		<section class="rl-section rl-emp">
			<div class="rl-wrap">
				<p class="rl-kicker">Y mientras el fondo crece</p>
				<h2>El FAL no se puede usar el primer año. ¿Quién cubre a tu empresa mientras tanto?</h2>
				<div class="rl-cajas4">
					<div><h3>Vida LCT</h3><p>Indemnización por fallecimiento del trabajador.</p></div>
					<div><h3>Vida y ART</h3><p>Toda la nómina cubierta y al día.</p></div>
					<div><h3>Seguridad e higiene</h3><p>Con Servhiseg, menos accidentes y reclamos.</p></div>
					<div><h3>Seguros de la empresa</h3><p>Integral, responsabilidad civil y flota.</p></div>
				</div>
				<div class="rl-cierre">
					<a class="rl-btn rl-btn-blanco" href="<?php echo esc_url( rosso_landing_url_whatsapp( 'Hola, quiero el chequeo laboral sin costo para mi empresa' ) ); ?>" target="_blank" rel="noopener">Chequeo laboral sin costo</a>
					<p><strong>WhatsApp</strong> <a href="<?php echo esc_url( rosso_landing_url_whatsapp() ); ?>" target="_blank" rel="noopener">+54 9 11 5055-0202</a><br><a href="mailto:info@rossoasesor.com.ar"><strong>info@rossoasesor.com.ar</strong></a></p>
				</div>
			</div>
		</section>

		<?php if ( isset( $info['fal'] ) ) : ?>
			<section class="rl-section rl-emp rl-falp-form">
				<div class="rl-wrap">
					<h2>Pedí tu propuesta</h2>
					<p>Completá los datos y te respondemos por WhatsApp.</p>
					<?php
					echo rosso_landing_formulario( // phpcs:ignore WordPress.Security.EscapeOutput
						'-fal',
						'¿Qué necesitás?',
						array( 'Servicios para empresas' => array( 'fal' => $info['fal'] ) ),
						'rl-btn-blue'
					);
					?>
					<p class="rl-fuente">Material informativo: no constituye asesoramiento legal, contable ni de inversión. Rosso puede recibir una retribución de la entidad que elijas. Fechas vigentes al 06/11/2026.</p>
				</div>
			</section>
		<?php endif; ?>
	</div>
	<?php
	return ob_get_clean();
}

/** "Cinco opciones." según cuántas entidades estén publicadas. */
function rosso_landing_cantidad_opciones( $cantidad ) {
	$palabras = array( 1 => 'Una opción.', 2 => 'Dos opciones.', 3 => 'Tres opciones.', 4 => 'Cuatro opciones.', 5 => 'Cinco opciones.', 6 => 'Seis opciones.', 7 => 'Siete opciones.', 8 => 'Ocho opciones.' );
	return $palabras[ $cantidad ] ?? $cantidad . ' opciones.';
}

/**
 * Tarjeta de una entidad FAL. Del texto del producto, el primer párrafo va como subtítulo
 * y el resto abajo. La "Nota destacada" es el recuadro "Se destaca por".
 */
function rosso_landing_entidad( $post ) {
	$contenido = wpautop( wp_kses_post( $post->post_content ) );
	$subtitulo = '';
	if ( preg_match( '#^\s*<p>(.*?)</p>#s', $contenido, $partes ) ) {
		$subtitulo = $partes[1];
		$contenido = substr( $contenido, strlen( $partes[0] ) );
	}
	$nota = get_post_meta( $post->ID, '_rosso_nota', true );
	ob_start();
	?>
	<article class="rl-entidad">
		<header>
			<h3><?php echo esc_html( $post->post_title ); ?></h3>
			<?php if ( $subtitulo ) : ?>
				<p><?php echo $subtitulo; // phpcs:ignore WordPress.Security.EscapeOutput -- ya pasó por wp_kses_post ?></p>
			<?php endif; ?>
		</header>
		<div class="rl-entidad-texto"><?php echo $contenido; // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
		<?php if ( $nota ) : ?>
			<p class="rl-ideal"><small>Se destaca por</small><strong><?php echo esc_html( $nota ); ?></strong></p>
		<?php endif; ?>
	</article>
	<?php
	return ob_get_clean();
}

/** Preguntas frecuentes (se editan desde el menú "Preguntas frecuentes") y recuadro de siniestros. */
function rosso_landing_seccion_preguntas() {
	$preguntas = rosso_landing_preguntas();
	ob_start();
	?>
	<section class="rl-section rl-faq" id="siniestros">
		<div class="rl-wrap rl-two">
			<?php if ( $preguntas ) : ?>
				<div>
					<h2>Preguntas frecuentes</h2>
					<?php foreach ( $preguntas as $pregunta ) : ?>
						<details>
							<summary><?php echo esc_html( $pregunta->post_title ); ?></summary>
							<div class="rl-respuesta"><?php echo wpautop( wp_kses_post( $pregunta->post_content ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
						</details>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
			<div class="rl-sin">
				<h2>¿Tuviste un siniestro?</h2>
				<p>Podés enviar un mail a las siguientes casillas:</p>
				<p>Automotores: <a href="mailto:mscatolon@rossoasesor.com.ar">mscatolon@rossoasesor.com.ar</a></p>
				<p>Riesgos varios: <a href="mailto:abadi@rossoasesor.com.ar">abadi@rossoasesor.com.ar</a></p>
				<p>Emergencias 24 hs: <a href="mailto:mjrecalde@rossoasesor.com.ar">mjrecalde@rossoasesor.com.ar</a> o llamar al <a href="tel:+<?php echo esc_attr( ROSSO_WHATSAPP_URGENCIAS ); ?>">+54 9 11 3681-3003</a></p>
				<a class="rl-btn rl-btn-main" href="<?php echo esc_url( rosso_landing_url_whatsapp( 'Hola! Quiero denunciar un siniestro. ¿Qué datos se necesitan?' ) ); ?>" target="_blank" rel="noopener">Denunciar</a>
			</div>
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
