/* Ventanas de detalle, formularios a WhatsApp y cuenta regresiva del FAL.
   El mensaje se arma en el navegador del cliente: nada se envía a WordPress. */
(function () {
	'use strict';

	var OPCION = 'Elegí una opción';
	var EN_EDITOR = /[?&]elementor-preview=/.test(window.location.search);

	/* ---------- Campos de cada formulario ----------
	   Para cambiar un formulario, se edita esta lista.
	   Cada campo es [etiqueta, tipo]. Tipos: "t" texto, "n" número, "d" fecha,
	   "x" texto largo, o una lista de opciones. La clave (auto, moto...) es la del
	   producto. Un producto sin lista acá pide solo "Detalle de lo que necesitás". */
	var NEGOCIO = ['Nombre del negocio o empresa', 't'];
	var RUBRO = ['Rubro o actividad', 't'];
	var LOCALIDAD = ['Localidad', 't'];
	var EMPLEADOS = ['Cantidad de empleados', 'n'];
	var SUPERFICIE = ['Superficie (m²)', 'n'];
	var CP = ['Localidad o código postal', 't'];

	var CAMPOS = {
		auto: [['Marca', 't'], ['Modelo', 't'], ['Versión', 't'], ['Año', 'n'], ['Patente (o 0 km)', 't'], ['Uso', ['Particular', 'Comercial o trabajo', 'Aplicaciones (Uber, Cabify, etc.)']], CP, ['¿Tiene GNC?', ['No', 'Sí']], ['Cobertura que buscás', ['Responsabilidad civil', 'Terceros completo', 'Todo riesgo', 'No sé, que me asesoren']], ['Seguro actual, si tenés (compañía y vencimiento)', 't']],
		moto: [['Marca', 't'], ['Modelo', 't'], ['Año', 'n'], ['Cilindrada (cc)', 'n'], ['Patente (o 0 km)', 't'], CP, ['Uso', ['Particular', 'Reparto o trabajo']], ['Cobertura que buscás', ['Responsabilidad civil', 'Robo e incendio', 'Todo riesgo', 'No sé, que me asesoren']]],
		mov: [['Tipo', ['Bicicleta', 'Bicicleta eléctrica', 'Monopatín eléctrico', 'Otro']], ['Marca y modelo', 't'], ['Valor aproximado ($)', 'n'], LOCALIDAD],
		hogar: [['Tipo de vivienda', ['Casa', 'Departamento', 'PH']], ['Sos', ['Propietario', 'Inquilino']], CP, SUPERFICIE, ['Valor aproximado del contenido ($)', 'n']],
		equi: [['Tipo de equipo', ['Cámara fotográfica o de video', 'Notebook o computadora', 'Tablet o celular', 'Dron', 'Equipo de audio o sonido', 'Otro']], ['Marca y modelo', 't'], ['Valor aproximado ($)', 'n'], ['Año de compra', 'n'], ['Uso', ['Personal', 'Profesional o de trabajo']], ['Cobertura que buscás', ['Robo', 'Daño accidental', 'Robo y daño accidental', 'No sé, que me asesoren']], LOCALIDAD],
		acc: [['Edad', 'n'], ['Ocupación o actividad', 't'], ['Suma asegurada que buscás ($)', 'n'], LOCALIDAD],
		ret: [['Edad', 'n'], ['Aporte mensual aproximado ($)', 'n'], ['Edad estimada de retiro', 'n']],
		via: [['Destino', 't'], ['Fecha de salida', 'd'], ['Fecha de regreso', 'd'], ['Cantidad de pasajeros', 'n'], ['Edades de los pasajeros', 't']],
		com: [NEGOCIO, RUBRO, LOCALIDAD, SUPERFICIE, ['Valor aproximado del contenido ($)', 'n'], ['El local es', ['Propio', 'Alquilado']]],
		inc: [NEGOCIO, ['Tipo de inmueble', 't'], LOCALIDAD, SUPERFICIE],
		rc: [NEGOCIO, RUBRO, EMPLEADOS, LOCALIDAD],
		cau: [NEGOCIO, ['Tipo de caución', ['Alquiler', 'Contrato u obra', 'Aduana', 'Otro']], ['Monto a garantizar ($)', 'n']],
		flota: [NEGOCIO, ['Cantidad de vehículos', 'n'], ['Tipo de vehículos', 't'], LOCALIDAD],
		transp: [NEGOCIO, ['Tipo de mercadería', 't'], ['Origen y destino', 't'], ['Valor aproximado por viaje ($)', 'n']],
		art: [NEGOCIO, RUBRO, EMPLEADOS, ['ART actual, si tenés (cuál)', 't']],
		vida: [NEGOCIO, RUBRO, EMPLEADOS],
		fal: [NEGOCIO, RUBRO, EMPLEADOS, ['¿Tenés certificado MiPyME?', ['Sí', 'No', 'No sé']]],
		syh: [NEGOCIO, RUBRO, EMPLEADOS, LOCALIDAD],
		aud: [NEGOCIO, ['Seguros que ya tenés', 't']],
		sin: [NEGOCIO, ['¿Qué pasó? (breve)', 't']],
		prog: [NEGOCIO, RUBRO, EMPLEADOS]
	};
	CAMPOS.artc = CAMPOS.art;

	/* ---------- Ventanas de detalle ---------- */
	document.addEventListener('click', function (e) {
		var objetivo = e.target;
		// En el editor, tocar una tarjeta selecciona el widget: no abre la ventana.
		if (EN_EDITOR || !objetivo.closest) {
			return;
		}
		var abrir = objetivo.closest('[data-rosso-abrir]');
		if (abrir) {
			var ventana = document.getElementById('rosso-detalle-' + abrir.getAttribute('data-rosso-abrir'));
			if (ventana) {
				ventana.showModal();
			}
			return;
		}
		var cerrar = objetivo.closest('[data-rosso-cerrar]');
		if (cerrar) {
			cerrar.closest('dialog').close();
			return;
		}
		// Un clic en el fondo oscuro cae sobre el propio <dialog>.
		if (objetivo.matches('dialog.rl-dialog')) {
			objetivo.close();
		}
	});

	/* ---------- Formularios ---------- */
	function crearCampo(campo, id) {
		var etiqueta = campo[0];
		var tipo = campo[1];
		var caja = document.createElement('div');
		var label = document.createElement('label');
		var control;

		label.htmlFor = id;
		label.textContent = etiqueta;
		caja.appendChild(label);

		if (Array.isArray(tipo)) {
			control = document.createElement('select');
			[OPCION].concat(tipo).forEach(function (texto, i) {
				var opcion = document.createElement('option');
				opcion.textContent = texto;
				opcion.value = i === 0 ? '' : texto;
				control.appendChild(opcion);
			});
		} else if (tipo === 'x') {
			caja.className = 'rl-full';
			control = document.createElement('textarea');
			control.rows = 2;
		} else {
			control = document.createElement('input');
			control.type = tipo === 'n' ? 'number' : tipo === 'd' ? 'date' : 'text';
			if (tipo === 'n') {
				control.inputMode = 'numeric';
				control.min = '0';
			}
		}
		control.id = id;
		caja.appendChild(control);
		return caja;
	}

	function iniciarFormulario(form) {
		var producto = form.querySelector('[data-rosso-producto]');
		var nombre = form.querySelector('[data-rosso-nombre]');
		var contenedor = form.querySelector('[data-rosso-campos]');
		var sufijo = form.getAttribute('data-sufijo');
		var actuales = [];

		function idCampo(i) {
			return 'rl-campo' + sufijo + '-' + i;
		}

		function pintar() {
			actuales = (CAMPOS[producto.value] || [['Detalle de lo que necesitás', 't']]).concat([['Comentarios (opcional)', 'x']]);
			contenedor.textContent = '';
			actuales.forEach(function (campo, i) {
				contenedor.appendChild(crearCampo(campo, idCampo(i)));
			});
		}

		producto.addEventListener('change', pintar);
		pintar();

		form.addEventListener('submit', function (e) {
			e.preventDefault();
			var elegido = producto.options[producto.selectedIndex];
			var mensaje = 'Hola, soy ' + (nombre.value.trim() || '(sin nombre)') + '. Quiero cotizar: ' + (elegido ? elegido.text : '') + '.';
			actuales.forEach(function (campo, i) {
				var valor = document.getElementById(idCampo(i)).value.trim();
				if (valor) {
					mensaje += '\n' + campo[0] + ': ' + valor;
				}
			});
			window.open('https://wa.me/' + form.getAttribute('data-whatsapp') + '?text=' + encodeURIComponent(mensaje), '_blank', 'noopener');
		});
	}

	/* ---------- Cuenta regresiva del FAL ---------- */
	function iniciarCuenta(bloque) {
		var partes = bloque.getAttribute('data-rosso-fal').split('-');
		var inicio = new Date(+partes[0], +partes[1] - 1, +partes[2]);
		var dias = Math.ceil((inicio - new Date()) / 864e5);
		var plural = dias === 1 ? ' día' : ' días';
		var numero = bloque.querySelector('[data-rosso-dias]');
		var texto = bloque.querySelector('[data-rosso-dias-texto]');
		var frase = bloque.querySelector('[data-rosso-dias-frase]');

		if (numero) {
			numero.textContent = dias > 0 ? dias + plural : 'Vigente';
		}
		if (texto) {
			texto.textContent = dias > 0 ? 'para la entrada en vigencia del FAL, el 1 de diciembre de 2026.' : 'El FAL rige desde el 1 de diciembre de 2026.';
		}
		if (frase) {
			frase.textContent = dias > 0 ? 'Faltan ' + dias + plural + '.' : 'Ya está en vigencia.';
		}
	}

	/* ---------- Arranque ---------- */
	// La marca evita iniciar dos veces un bloque: en el editor esto corre con cada cambio.
	function iniciarPendientes(selector, iniciar) {
		document.querySelectorAll(selector + ':not([data-rosso-listo])').forEach(function (bloque) {
			bloque.setAttribute('data-rosso-listo', '');
			iniciar(bloque);
		});
	}

	function iniciarTodo() {
		iniciarPendientes('[data-rosso-cotizador]', iniciarFormulario);
		iniciarPendientes('[data-rosso-fal]', iniciarCuenta);
	}

	iniciarTodo();

	// El editor de Elementor agrega los widgets después de cargar la página.
	if (EN_EDITOR) {
		new MutationObserver(iniciarTodo).observe(document.documentElement, { childList: true, subtree: true });
	}
})();
