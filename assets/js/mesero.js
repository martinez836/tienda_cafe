// Función para cargar la lista de mesas con token activo

// Evento para cerrar sesión
document.addEventListener('DOMContentLoaded', function () {
  const btnCerrarSesion = document.getElementById('btnCerrarSesion');
  if (btnCerrarSesion) {
    btnCerrarSesion.addEventListener('click', function () {
      window.location.href = '../../controllers/logout.php';
    });
  }
});

async function cargarMesasTokenActivas() {
  try {
    const response = await fetch('../../controllers/mesero/generar_token.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'activos=1'
    });
    const data = await response.json();
    const lista = document.getElementById('mesasTokenLista');
    if (data.success && Array.isArray(data.tokens)) {
      lista.innerHTML = '';
      if (data.tokens.length === 0) {
        lista.innerHTML = '<li class="list-group-item">No hay mesas con token activo</li>';
      } else {
        data.tokens.forEach(token => {
          lista.innerHTML += `<li class="list-group-item d-flex justify-content-between align-items-center">
            <span><b>${token.mesa_nombre}</b> <span class="badge bg-warning text-dark ms-2">${token.token}</span></span>
            <span class="text-muted small">Expira: ${token.fecha_hora_expiracion}</span>
            <button class="btn btn-sm btn-danger ms-3 btn-cancelar-token" data-token-id="${token.idtoken_mesa}" title="Cancelar token">
              <i class="fas fa-ban"></i>
            </button>
          </li>`;
        });
        // Agregar eventos a los botones de cancelar token
        document.querySelectorAll('.btn-cancelar-token').forEach(btn => {
          btn.addEventListener('click', async function() {
            const tokenId = this.getAttribute('data-token-id');
            if (!tokenId) return;
            // Debug: log tokenId before sending
            console.log('Cancelando token con idtoken_mesa:', tokenId);
            // SweetAlert2 confirmación
            const result = await Swal.fire({
              title: '¿Cancelar token?',
              text: '¿Seguro que deseas cancelar este token? Esta acción no se puede deshacer.',
              icon: 'warning',
              showCancelButton: true,
              confirmButtonColor: '#d33',
              cancelButtonColor: '#3085d6',
              confirmButtonText: 'Sí, cancelar',
              cancelButtonText: 'No'
            });
            if (!result.isConfirmed) return;
            try {
              const response = await fetch('../../controllers/mesero/cancelar_token.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ idtoken_mesa: tokenId })
              });
              const data = await response.json();
              if (data.success) {
                await Swal.fire('Cancelado', 'El token fue cancelado correctamente.', 'success');
                cargarMesasTokenActivas(); // Recargar lista
              } else {
                Swal.fire('Error', data.message || 'No se pudo cancelar el token', 'error');
              }
            } catch (error) {
              Swal.fire('Error', 'Error de conexión al cancelar el token.', 'error');
            }
          });
        });
      }
    } else {
      lista.innerHTML = '<li class="list-group-item text-danger">No se pudo cargar la lista de tokens</li>';
    }
  } catch (error) {
    document.getElementById('mesasTokenLista').innerHTML = '<li class="list-group-item text-danger">Error al cargar tokens</li>';
  }
}

// Evento para el botón de generar token
function configurarBotonGenerarToken() {
  const btnGenerarToken = document.getElementById('btnGenerarToken');
  btnGenerarToken.addEventListener('click', async function () {
    const mesaId = mesaSelect.value;
    if (!mesaId) {
      mostrarAdvertencia('Seleccione una mesa', 'Debe seleccionar una mesa para generar el token.');
      return;
    }
    try {
      btnGenerarToken.disabled = true;
      btnGenerarToken.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Generando...';
      const response = await fetch('../../controllers/mesero/generar_token.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `mesa_id=${encodeURIComponent(mesaId)}`
      });
      const data = await response.json();
      if (data.success) {
        mostrarExito('Token generado', `Token: <b>${data.token}</b><br>Expira: ${data.expira}`);
        await cargarMesasTokenActivas();
      } else {
        mostrarError('Error', data.message || 'No se pudo generar el token');
      }
    } catch (error) {
      mostrarError('Error', 'No se pudo generar el token.');
    } finally {
      btnGenerarToken.disabled = false;
      btnGenerarToken.innerHTML = '<i class="fas fa-key me-2"></i>Generar Token para la Mesa';
    }
  });
}
const mesaSelect = document.querySelector('#mesaSelect');
const categoriaSelect = document.querySelector('#categoriaSelect');
const buscadorProductos = document.querySelector('#buscadorProductos');
const productosContainer = document.querySelector('#productosContainer');
const modalElement = document.getElementById("observacionModal");
const modal = new bootstrap.Modal(modalElement);

// Variable global para almacenar y acumular los productos del pedido actual
let pedidoActual = {
  mesa: null,
  productos: [],
  total: 0
};

// Función para validar observaciones y prevenir inyección SQL
function validarObservaciones(observaciones) {

  if(observaciones === "") return true; // Permitir vacío

  const patronSeguro = /^[a-zA-ZáéíóúÁÉÍÓÚñÑ0-9\s.,;:()¿?¡!\-_]*$/;
  const caracteresProhibidos = /['"<>{}[\]\\|`~@#$%^&*+=]/;

  if(observaciones.length > 255) return false;
  if(caracteresProhibidos.test(observaciones)) return false;
  if(!patronSeguro.test(observaciones)) return false;
  return true;
}

// Función para sanitizar observaciones antes de enviar
function sanitizarObservaciones(texto) {
  if (!texto || typeof texto !== 'string') return '';

  // Eliminar espacios extra y caracteres de control
  return texto
    .trim()
    .replace(/\s+/g, ' ') // Reemplazar múltiples espacios por uno solo
    .replace(/[\x00-\x1F\x7F]/g, '') // Eliminar caracteres de control
    .substring(0, 255); // Asegurar límite de caracteres
}

// Funciones para Toast Notifications (reemplazo de SweetAlert2)
function mostrarToast(tipo, titulo, mensaje, duracion = 5000) {
  const toastContainer = document.querySelector('.toast-container');
  const toastId = 'toast-' + Date.now();

  const iconos = {
    success: 'fas fa-check-circle text-success',
    error: 'fas fa-exclamation-circle text-danger',
    warning: 'fas fa-exclamation-triangle text-warning',
    info: 'fas fa-info-circle text-info'
  };

  const colores = {
    success: 'border-success',
    error: 'border-danger',
    warning: 'border-warning',
    info: 'border-info'
  };

  const toastHTML = `
    <div id="${toastId}" class="toast ${colores[tipo]}" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="${duracion}">
      <div class="toast-header">
        <i class="${iconos[tipo]} me-2"></i>
        <strong class="me-auto">${titulo}</strong>
        <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
      <div class="toast-body">
        ${mensaje}
      </div>
    </div>
  `;
 
  toastContainer.insertAdjacentHTML('beforeend', toastHTML);

  const toastElement = document.getElementById(toastId);
  const toast = new bootstrap.Toast(toastElement);
  toast.show();

  // Remover el elemento del DOM después de que se oculte
  toastElement.addEventListener('hidden.bs.toast', function () {
    toastElement.remove();
  });
}

// Funciones específicas para cada tipo de mensaje
function mostrarExito(titulo, mensaje, duracion = 4000) {
  mostrarToast('success', titulo, mensaje, duracion);
}

function mostrarError(titulo, mensaje, duracion = 6000) {
  mostrarToast('error', titulo, mensaje, duracion);
}

function mostrarAdvertencia(titulo, mensaje, duracion = 5000) {
  mostrarToast('warning', titulo, mensaje, duracion);
}

function mostrarInfo(titulo, mensaje, duracion = 4000) {
  mostrarToast('info', titulo, mensaje, duracion);
}

// Función para confirmaciones (reemplaza SweetAlert2 confirm)
function mostrarConfirmacion(titulo, mensaje, callback) {
  const toastContainer = document.querySelector('.toast-container');
  const toastId = 'toast-confirm-' + Date.now();

  const toastHTML = `
    <div id="${toastId}" class="toast border-warning" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="false">
      <div class="toast-header">
        <i class="fas fa-question-circle text-warning me-2"></i>
        <strong class="me-auto">${titulo}</strong>
        <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
      <div class="toast-body">
        <p class="mb-3">${mensaje}</p>
        <div class="d-flex gap-2">
          <button class="btn btn-danger btn-sm confirm-yes">Sí, confirmar</button>
          <button class="btn btn-secondary btn-sm confirm-no">Cancelar</button>
        </div>
      </div>
    </div>
  `;

  toastContainer.insertAdjacentHTML('beforeend', toastHTML);

  const toastElement = document.getElementById(toastId);
  const toast = new bootstrap.Toast(toastElement);
  toast.show();

  // Eventos para los botones
  toastElement.querySelector('.confirm-yes').addEventListener('click', function () {
    callback(true);
    toast.hide();
  });

  toastElement.querySelector('.confirm-no').addEventListener('click', function () {
    callback(false);
    toast.hide();
  });

  toastElement.querySelector('.btn-close').addEventListener('click', function () {
    callback(false);
  });

  // Remover el elemento del DOM después de que se oculte
  toastElement.addEventListener('hidden.bs.toast', function () {
    toastElement.remove();
  });
}

// Función para validar selección de mesa y mostrar productos activos
function configurarValidacionMesas() {
  mesaSelect.addEventListener('change', function () {
    const idMesaSeleccionada = this.value;
    if (idMesaSeleccionada) {      // verifica si se selecciono una mesa en el select
      // Cargar productos del pedido activo para visualización
      cargarProductosPedidoActivo(idMesaSeleccionada);
    } else {
      // Limpiar si no hay mesa seleccionada
      const pedidosContainer = document.getElementById('pedidosActivosMesa');
      if (pedidosContainer) {
        pedidosContainer.innerHTML = '';
      }
    }
  });
}

//funcion que trae el html generado con los productos
async function obtenerHtmlProductosActivos(mesaId) {
  const formData = new FormData();
  formData.append('mesa_id', mesaId);

  const response = await fetch('../../controllers/mesero/cargar_productos_pedido_activo.php', {
    method: 'POST',
    body: formData
  });

  if (!response.ok) {
    throw new Error(`HTTP error! status: ${response.status}`);
  }

  const data = await response.json();
  if (data.success) {
    return data.html;
  } else {
    throw new Error('No se pudieron cargar los productos activos.');
  }
}

// Función para cargar productos del pedido activo (solo visualización)
async function cargarProductosPedidoActivo(mesaId) {
  idPedidoActivo = ""; // Resetear ID del pedido activo
  const contenedor = document.getElementById('pedidosActivosMesa');

  try {
    const html = await obtenerHtmlProductosActivos(mesaId);
    contenedor.innerHTML = html;
    
    // Extraer el ID del pedido activo después de insertar el HTML para poder adicionar productos al pedido existente
    setTimeout(() => {
      const h6Element = contenedor.querySelector('.card-header h6');
      if (h6Element) {
        const textoCompleto = h6Element.textContent;
        idPedidoActivo = textoCompleto.match(/\d+/);
        console.log('🎯 ID del pedido activo capturado:', idPedidoActivo[0]);
        console.log('📋 Mesa:', mesaId, '- Pedido ID:', idPedidoActivo[0]);

      } else {
        console.log('ℹ️ No hay elementos h6 con ID de pedido en el contenedor');
      }
    }, 100);
    
  } catch (error) {
    console.error('❌ Error al cargar productos activos:', error);
    contenedor.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Error al cargar los productos en proceso. Detalles: ' + error.message + '</div>';
  }
}

// Función para cargar las mesas disponibles
async function cargarMesas() {
  try {
    const response = await fetch('../../controllers/mesero/cargarSelectMesas.php');
    const data = await response.text();
    mesaSelect.innerHTML = data;
  } catch (error) {
    console.error('Error al cargar las mesas:', error);
  }
}

async function cargarCategorias() {
  try {
    const response = await fetch('../../controllers/mesero/cargarCategorias.php');
    const data = await response.text();
    document.querySelector('#categoriaSelect').innerHTML = data;
  } catch (error) {
    console.error('Error al cargar las categorías:', error);
  }
}

// funcion para buscar productos desde el input buscador
function inicializarBuscadorProductos() {
  buscadorProductos.addEventListener("input", function () {
    const filtro = buscadorProductos.value.toLowerCase();
    const productos = productosContainer.querySelectorAll(".card");

    productos.forEach(card => {
      const nombre = card.querySelector("h5").textContent.toLowerCase();
      card.parentElement.style.display = nombre.includes(filtro) ? "" : "none";
    });
  });
}

// funcion para cargar productos al seleccionar una categoria de productos
function cargarProductos() {
  // Validar que se haya seleccionado una mesa antes de cargar productos
  categoriaSelect.addEventListener("change", async function () {
    if (!mesaSelect.value) {
      // Validación de mesa
      mostrarAdvertencia('Seleccione una mesa', 'Por favor, seleccione una mesa antes de ver los productos.');
      categoriaSelect.value = '';
      return;
    }

    const idcategorias = categoriaSelect.value;
    if (!idcategorias) {
      productosContainer.innerHTML = "";
      return;
    }

    // Mostrar loading
    productosContainer.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Cargando...</span></div></div>';


    try {
    
      const response = await fetch("../../controllers/mesero/cargar_productos.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ idcategorias: idcategorias }),
      });

      if (!response.ok) {
        throw new Error('Error en la respuesta del servidor');
      }

      const data = await response.json();

      if (data.success) {
        //AQUÍ SE INSERTAN LAS CARDS EN EL DOM:
        productosContainer.innerHTML = data.html;

        // Configurar validaciones de stock y event listeners para los productos
        await eventosProductos();
      } else {
        productosContainer.innerHTML = '<div class="alert alert-info">No hay productos disponibles en esta categoría.</div>';
      }
    } catch (error) {
      console.error('Error al cargar productos:', error);
      productosContainer.innerHTML = '<div class="alert alert-danger">Error al cargar los productos. Intente nuevamente.</div>';
    }
  });
}

// Función para configurar eventos de los productos cargados
async function eventosProductos() {
  const productCards = document.querySelectorAll('#productosContainer .card');

  productCards.forEach(card => {
    const btnAgregar = card.querySelector('.btn-agregar-producto');
    if (btnAgregar) {
      btnAgregar.addEventListener('click', async function () {
        const productoId = this.dataset.productId;
        const productoNombre = this.dataset.productName;
        const productoPrecio = this.dataset.productPrice;
        const productoStock = this.dataset.productStock;
        const productoType = this.dataset.productType;

        // Llenar el modal con la información del producto
        document.querySelector('#productoNombreSeleccionado').textContent = productoNombre;
        document.querySelector('#productoSeleccionado').value = productoId;
        document.querySelector('#precioUnitario').value = productoPrecio;
        document.querySelector('#stockDisponible').textContent = productoStock === 'ilimitado' ? '∞' : productoStock;

        // Resetear cantidad y observaciones
        document.querySelector('#cantidadInput').value = 1;
        const comentarioTextarea = document.querySelector('#comentarioInput');
        comentarioTextarea.value = '';

        // Resetear validación de observaciones
        comentarioTextarea.classList.remove('is-valid', 'is-invalid');
        document.getElementById('contadorCaracteres').textContent = '0';
        document.getElementById('errorObservaciones').classList.add('d-none');

        // Calcular precio inicial
        actualizarPrecioTotal();

        // Configurar límite máximo si hay stock limitado
        const cantidadInput = document.querySelector('#cantidadInput');
        if (productoStock !== 'ilimitado') {
          cantidadInput.max = productoStock;
        } else {
          cantidadInput.max = 99;
        }

        // Mostrar modal y manejar accesibilidad
        modalElement.removeAttribute('aria-hidden');
        modal.show();
      });
    }
  });
}

// Función para actualizar el precio total en el modal
function actualizarPrecioTotal() {
  const cantidad = parseInt(document.querySelector('#cantidadInput').value) || 1;
  const precioUnitario = parseInt(document.querySelector('#precioUnitario').value) || 0;
  const precioTotal = cantidad * precioUnitario;

  document.querySelector('#precioTotal').textContent = '$' + precioTotal.toLocaleString();
}

// Función para configurar eventos del modal y desde donde se agregan los productos y el detalle para enviar al 
//controller agregar_productos_pedido
function configurarEventosModal() {
  const cantidadInput = document.querySelector('#cantidadInput');
  const btnMenos = document.querySelector('#btnMenosCantidad');
  const btnMas = document.querySelector('#btnMasCantidad');
  const btnAgregarAlPedido = document.querySelector('#btnAgregarAlPedido');

  // Manejar eventos de accesibilidad del modal
  modalElement.addEventListener('shown.bs.modal', function () {
    // Cuando el modal se muestra, asegurarse de que no tenga aria-hidden
    modalElement.removeAttribute('aria-hidden');
  });

  modalElement.addEventListener('hidden.bs.modal', function () {
    // Cuando el modal se oculta, agregar aria-hidden
    modalElement.setAttribute('aria-hidden', 'true');
  });

  // Evento para agregar producto al pedido
  btnAgregarAlPedido.addEventListener('click', function () {
    // Obtener datos del producto desde el modal
    const productoId = document.querySelector('#productoSeleccionado').value;
    const productoNombre = document.querySelector('#productoNombreSeleccionado').textContent;
    const precioUnitario = parseInt(document.querySelector('#precioUnitario').value);
    const cantidad = parseInt(document.querySelector('#cantidadInput').value);
    const observacionesModal = document.querySelector('#comentarioInput').value;

    // Validar y sanitizar observaciones
    if (!validarObservaciones(observacionesModal)) {
      mostrarError('Observaciones inválidas', 'Por favor corrija las observaciones antes de continuar');
      return;
    }
    

    // Crear objeto del producto individual (para detalle_pedidos)
    const producto = {
      id: productoId,
      nombre: productoNombre,
      precio: precioUnitario,
      cantidad: cantidad,
      subtotal: precioUnitario * cantidad,
      observaciones: sanitizarObservaciones(observacionesModal)
    };
    console.log('Observaciones sanitizadas:', producto);

    // Agregar producto al pedido temporal
    agregarProductoAlPedidoTemporal(producto);

    // Cerrar modal
    modal.hide();
  });

  // Evento para el botón de disminuir cantidad
  btnMenos.addEventListener('click', function () {
    const valor = parseInt(cantidadInput.value);
    if (valor > 1) {
      cantidadInput.value = valor - 1;
      actualizarPrecioTotal();
    }
  });

  // Evento para el botón de aumentar cantidad
  btnMas.addEventListener('click', function () {
    const valor = parseInt(cantidadInput.value);
    const maximo = parseInt(cantidadInput.max);
    if (valor < maximo) {
      cantidadInput.value = valor + 1;
      actualizarPrecioTotal();
    }
  });

  // Evento para cambio directo en el input
  cantidadInput.addEventListener('input', function () {
    const valor = parseInt(this.value);
    const minimo = parseInt(this.min);
    const maximo = parseInt(this.max);

    if (valor < minimo) {
      this.value = minimo;
    } else if (valor > maximo) {
      this.value = maximo;
    }

    actualizarPrecioTotal();
  });
}

// Función para agregar producto al pedido temporal al arreglo productos del objeto pedidoActual
function agregarProductoAlPedidoTemporal(producto) {
  // Asignar mesa si no está asignada
  if (!pedidoActual.mesa) {
    pedidoActual.mesa = mesaSelect.value;
  }

  // Busca si el producto ya existe en el pedido para sumar la cantidad y calcular valores
  const productoExistente = pedidoActual.productos.find(item => item.id === producto.id);

  if (productoExistente) {
    // Si existe, incrementar cantidad y actualizar subtotal
    productoExistente.cantidad += producto.cantidad;
    productoExistente.subtotal = productoExistente.precio * productoExistente.cantidad;
    // Combinar observaciones si existen
    if (producto.observaciones && !productoExistente.observaciones.includes(producto.observaciones)) {
      productoExistente.observaciones += '; ' + producto.observaciones;
    }
  } else {
    // Si no existe, agregarlo al array
    pedidoActual.productos.push(producto);
  }
  console.log('Producto agregado al pedido:', producto);

  // Recalcular total
  pedidoActual.total = pedidoActual.productos.reduce((sum, p) => sum + p.subtotal, 0);

  // Actualizar UI del pedido actual
  actualizarUIPedidoActual();
}


// Función para actualizar la UI del pedido actual
function actualizarUIPedidoActual() {
  const pedidoLista = document.querySelector('#pedidoLista');
  const totalPedido = document.querySelector('#totalPedido');

  if (pedidoActual.productos.length === 0) {
    pedidoLista.innerHTML = '<li class="list-group-item text-muted">No hay productos en el pedido</li>';
    // Actualizar solo el contenido del total
    if (totalPedido) {
      totalPedido.textContent = '$0';
    }
    return;
  }

  let html = '';

  pedidoActual.productos.forEach((producto, index) => {
    html += `
      <li class="list-group-item d-flex justify-content-between align-items-center">
        <div>
          <h6>${producto.nombre}</h6>
          <small>Cantidad: ${producto.cantidad} x $${producto.precio.toLocaleString()}</small>
          ${producto.observaciones ? `<br><small class="text-muted">Obs: ${producto.observaciones}</small>` : ''}
        </div>
        <div class="d-flex align-items-center">
          <span class="badge bg-primary me-2">$${producto.subtotal.toLocaleString()}</span>
          <button class="btn btn-sm btn-danger" onclick="eliminarProductoDelPedido(${index})">
            <i class="fas fa-trash"></i>
          </button>
        </div>
      </li>
    `;
  });

  pedidoLista.innerHTML = html;

  // Actualizar solo el contenido del total en el elemento HTML existente
  if (totalPedido) {
    totalPedido.textContent = '$' + pedidoActual.total.toLocaleString();
  }
}

// Función para eliminar producto del pedido
function eliminarProductoDelPedido(index) {
  pedidoActual.productos.splice(index, 1);
  pedidoActual.total = pedidoActual.productos.reduce((sum, p) => sum + p.subtotal, 0);
  actualizarUIPedidoActual();
}

// Función para verificar si hay pedido activo consultando el DOM
function hayPedidoActivoEnMesa() {
  const contenedor = document.querySelector('#pedidosActivosMesa');

  // Verificar si el contenedor existe y tiene contenido
  if (!contenedor || !contenedor.innerHTML.trim()) {
    return false;
  }
  
  // Verificar si hay productos activos (no solo mensajes de error o vacío)
  const tieneProductos = contenedor.querySelector('.productos-activos-mesa');
  const tieneHeader = contenedor.querySelector('.card-header h6');
  
  // Si encuentra la estructura de productos activos, hay pedido
  if (tieneProductos && tieneHeader) {
    console.log('Pedido activo detectado en DOM', tieneHeader);
    return true;
  }
  
  console.log('No hay pedido activo en DOM');
  return false;
}

// Función para obtener ID del pedido activo desde el DOM
function obtenerIdPedidoActivoDOM() {
  const contenedor = document.getElementById('pedidosActivosMesa');
  const h6Element = contenedor?.querySelector('.card-header h6');
  
  if (h6Element) {
    const textoCompleto = h6Element.textContent;
    // captura el numero del elemento con expresion regular que busca digitos 
    const idPedidoActivo = textoCompleto.match(/\d+/);
    
    if (idPedidoActivo) {
      const pedidoId = idPedidoActivo[0];
      console.log('ID del pedido extraído del DOM:', pedidoId);
      return pedidoId;
    }
  }
  
  console.log('Error, al obtener ID del pedido del DOM');
  return null;
}

// Función para confirmar pedido completo
async function confirmarPedidoCompleto() {
  if (pedidoActual.productos.length === 0) {
    mostrarAdvertencia('Pedido vacío', 'Agregue al menos un producto al pedido.');
    return;
  }

  // Verificar si existe un pedido activo consultando el DOM
  if (hayPedidoActivoEnMesa()) {
    // Mesa tiene pedido activo - ACTUALIZAR pedido existente
    const pedidoActivoId = obtenerIdPedidoActivoDOM();
    
    if (pedidoActivoId) {
      console.log('🔄 Mesa con pedido activo detectada. ID del pedido:', pedidoActivoId);
      await actualizarPedidoExistente(pedidoActivoId);
    } else {
      mostrarError('Error', 'No se pudo obtener el ID del pedido activo');
    }
  } else {
    // Mesa sin pedido activo - CREAR pedido nuevo
    console.log('🆕 Mesa sin pedido activo. Creando pedido nuevo.');
    await crearPedidoNuevo();
  }
}

/**
 * Actualizar un pedido existente agregando nuevos productos
 */
async function actualizarPedidoExistente(pedidoId) {
  // Crear objeto para actualizar pedido existente
  const datosActualizacion = {
    pedido_id: parseInt(pedidoId),
    mesa: pedidoActual.mesa,
    // Solo enviar los nuevos productos a agregar
    detalles: pedidoActual.productos.map(producto => ({
      producto_id: producto.id,
      cantidad: producto.cantidad,
      precio: producto.precio,
      subtotal: producto.subtotal,
      observaciones: producto.observaciones || ''
    })),
    // Total de los nuevos productos (se sumará al total existente)
    total_nuevos_productos: pedidoActual.total
  };

  console.log('🔄 Datos para actualizar pedido:', datosActualizacion);

  try {
    const response = await fetch('../../controllers/mesero/agregar_productos_pedido.php', {
      method: 'PUT', // Usar PUT para actualización
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(datosActualizacion)
    });

    const data = await response.json();

    if (data.success) {
      mostrarExito('Pedido actualizado', `Pedido #${pedidoId} actualizado correctamente. Se agregaron ${data.detalles_insertados} productos.`);
      limpiarPedidoActual();
      setTimeout(() => {
        location.reload();
      }, 2000);
    } else {
      mostrarError('Error al actualizar pedido', data.message || 'No se pudo actualizar el pedido');
    }
  } catch (error) {
    console.error('Error al actualizar pedido:', error);
    mostrarError('Error de conexión', 'No se pudo conectar con el servidor');
  }
}

/**
 * Crear un pedido completamente nuevo
 */
async function crearPedidoNuevo() {
  // Crear objeto completo para enviar al backend
  // Obtener el ID del usuario logueado desde el atributo data del body
  const usuarioId = document.body.getAttribute('data-usuario-id') || 1;
  const pedidoCompleto = {
    // Datos para tabla pedidos
    pedido: {
      mesa: pedidoActual.mesa,
      usuario: usuarioId, // ID del mesero desde data attribute
      fecha: new Date().toISOString().slice(0, 19).replace('T', ' '),
      estado: 3, // 3 = confirmado
      total: pedidoActual.total,
      observaciones: '' // Observaciones generales del pedido
    },
    // Datos para tabla detalle_pedidos (array de productos)
    detalles: pedidoActual.productos.map(producto => ({
      producto_id: producto.id,
      cantidad: producto.cantidad,
      precio: producto.precio,
      subtotal: producto.subtotal,
      observaciones: producto.observaciones || ''
    }))
  };

  console.log('🆕 Pedido nuevo a enviar:', pedidoCompleto);

  try {
    const response = await fetch('../../controllers/mesero/agregar_productos_pedido.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(pedidoCompleto)
    });

    const data = await response.json();

    if (data.success) {
      mostrarExito('Pedido creado', `Pedido #${data.pedido_id} creado correctamente con ${data.detalles_insertados} productos.`);
      limpiarPedidoActual();
      setTimeout(() => {
        location.reload();
      }, 2000);
    } else {
      mostrarError('Error al crear pedido', data.message || 'No se pudo crear el pedido');
    }
  } catch (error) {
    console.error('Error al crear pedido:', error);
    mostrarError('Error de conexión', 'No se pudo conectar con el servidor');
  }
}

// Función para limpiar pedido actual
function limpiarPedidoActual() {
  pedidoActual = {
    mesa: null,
    productos: [],
    total: 0
  };
  actualizarUIPedidoActual();
}

document.addEventListener('DOMContentLoaded', async () => {
  try {
    // Cargar datos iniciales de forma paralela
    await Promise.all([
      cargarMesas(),
      cargarCategorias()
    ]);

    // Después de cargar el DOM con los select
    inicializarBuscadorProductos();
    cargarProductos();
    configurarEventosModal();
    configurarEventosPedido();
    configurarValidacionMesas();
    configurarBotonGenerarToken();
    await cargarMesasTokenActivas();
  } catch (error) {
    console.error('Error en la inicialización:', error);
    mostrarError('Error de inicialización', 'Hubo un problema al cargar la aplicación. Recargue la página.');
  }
});

// Función para configurar eventos del pedido
function configurarEventosPedido() {
  const btnConfirmarPedido = document.querySelector('#btnConfirmarPedido');
  const btnCancelarPedido = document.querySelector('#btnCancelarPedido');

  btnConfirmarPedido.addEventListener('click', confirmarPedidoCompleto);

  btnCancelarPedido.addEventListener('click', function () {
    mostrarConfirmacion(
      '¿Cancelar pedido?',
      'Se perderán todos los productos agregados.',
      function (confirmado) {
        if (confirmado) {
          limpiarPedidoActual();
          mostrarExito('Pedido cancelado', 'El pedido ha sido cancelado correctamente.');
        }
      }
    );
  });
}
