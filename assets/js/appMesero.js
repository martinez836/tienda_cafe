let pedido = [];
let pedidoIdModificar = null;
let reseteandoPorConfirmacion = false; // <-- Añadido para controlar el reseteo intencional

// funcion para cargar productos al seleccionar una categoria de productos
document.addEventListener("DOMContentLoaded", function () {
  const select = document.querySelector("#categoriaSelect");
  const contenedor = document.querySelector("#productosContainer");
  const mesaSelect = document.querySelector("#mesaSelect");

  // Validar que se haya seleccionado una mesa antes de cargar productos
  select.addEventListener("change", function () {
    if (!mesaSelect.value) {
      if (!reseteandoPorConfirmacion) {
        Swal.fire({
          icon: 'warning',
          title: 'Seleccione una mesa',
          text: 'Por favor, seleccione una mesa antes de ver los productos.',
        });
      }
      select.value = '';
      return;
    }

    const idcategorias = select.value;
    if (!idcategorias) {
      contenedor.innerHTML = "";
      return;
    }

    // Mostrar loading
    contenedor.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Cargando...</span></div></div>';

    fetch("../controllers/cargar_productos.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: "idcategorias=" + encodeURIComponent(idcategorias),
    })
      .then((response) => {
        if (!response.ok) {
          throw new Error('Error en la respuesta del servidor');
        }
        return response.json();
      })
      .then((data) => {
        if (data.success) {
          contenedor.innerHTML = data.html;
          // Limitar el input de cantidad según el stock
          document.querySelectorAll('#productosContainer .card').forEach(card => {
            const stockBadge = card.querySelector('.badge.bg-secondary');
            let stock = null;
            if (stockBadge) {
              const match = stockBadge.textContent.match(/Stock:\s*(\d+)/);
              if (match) stock = parseInt(match[1]);
            }
            const input = card.querySelector('input[type=number]');
            if (input && stock !== null) {
              input.max = stock;
            }
          });
          // Agregar event listeners a los botones de agregar
          document.querySelectorAll('#productosContainer .btn-primary').forEach(btn => {
            btn.addEventListener('click', function() {
              const card = this.closest('.card');
              const id = card.getAttribute('data-id');
              const nombre = card.querySelector('h5').textContent.trim();
              const precio = parseFloat(this.getAttribute('data-precio'));
              const input = card.querySelector('input[type=number]');
              const cantidad = parseInt(input.value);
              // Validar stock
              const stockBadge = card.querySelector('.badge.bg-secondary');
              let stock = null;
              if (stockBadge) {
                const match = stockBadge.textContent.match(/Stock:\s*(\d+)/);
                if (match) stock = parseInt(match[1]);
              }
              if (stock !== null && cantidad > stock) {
                Swal.fire({
                  icon: 'warning',
                  title: 'Cantidad inválida',
                  text: 'No puedes agregar más que el stock disponible.',
                  confirmButtonText: 'Entendido'
                });
                return;
              }
              if (!cantidad || cantidad <= 0) {
                Swal.fire({
                  icon: 'warning',
                  title: 'Cantidad inválida',
                  text: 'Por favor, ingrese una cantidad válida.',
                  confirmButtonText: 'Entendido'
                });
                return;
              }

              // Actualizar el modal con los datos del producto
              document.getElementById("productoSeleccionado").value = id;
              document.getElementById("productoNombreSeleccionado").textContent = nombre;
              document.getElementById("productoCantidadSeleccionada").textContent = cantidad;
              document.getElementById("productoPrecioSeleccionado").textContent = `$${precio.toFixed(2)}`;
              document.getElementById("comentarioInput").value = "";
              document.getElementById("productoSeleccionado").setAttribute("data-precio", precio);
              document.getElementById("productoSeleccionado").setAttribute("data-nombre", nombre);
              document.getElementById("productoSeleccionado").setAttribute("data-cantidad", cantidad);
              document.getElementById("productoSeleccionado").setAttribute("data-stock", stock);

              // Mostrar el modal
              const modalElement = document.getElementById("observacionModal");
              const modal = new bootstrap.Modal(modalElement);
              modal.show();
            });
          });
        } else {
          contenedor.innerHTML = data.html;
          console.error('Error:', data.message);
        }
      })
      .catch((error) => {
        console.error('Error:', error);
        contenedor.innerHTML = `
          <div class="alert alert-danger" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            Error al cargar productos. Por favor, intente nuevamente.
          </div>`;
      });
  });

  // Validar selección de mesa
  mesaSelect.addEventListener("change", function() {
    if (select.value) {
      select.dispatchEvent(new Event('change'));
    }
    // Cargar pedido activo de la mesa
    const mesaId = mesaSelect.value;
    if (mesaId) {
      fetch("../controllers/pedidos_activos_mesa.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ mesa_id: mesaId, para_edicion: false })
      })
      .then(res => res.json())
      .then(data => {
        if (data.success && data.pedidos && data.pedidos.length > 0) {
          // Tomar el primer pedido activo
          const productos = data.pedidos[0].productos;
          pedido = productos.map(prod => ({
            id: prod.id,
            nombre: prod.nombre,
            cantidad: parseInt(prod.cantidad),
            comentario: prod.comentario,
            precio: parseFloat(prod.precio)
          }));
          actualizarLista();
          
          // Mostrar pedidos activos en el contenedor específico
          mostrarPedidosActivosMesa(data.pedidos);
        } else {
          pedido = [];
          actualizarLista();
          
          // Limpiar contenedor de pedidos activos
          mostrarPedidosActivosMesa([]);
        }
      });
    } else {
      pedido = [];
      actualizarLista();
      // Limpiar contenedor de pedidos activos
      mostrarPedidosActivosMesa([]);
    }
  });
});

// funcion para buscar productos desde el input buscador
document.addEventListener("DOMContentLoaded", function () {
  const buscador = document.querySelector("#buscadorProductos");

  buscador.addEventListener("input", function () {
    const filtro = buscador.value.toLowerCase();
    const productos = document.querySelectorAll("#productosContainer .card");

    productos.forEach(card => {
      const nombre = card.querySelector("h5").textContent.toLowerCase();
      card.parentElement.style.display = nombre.includes(filtro) ? "" : "none";
    });
  });
});

function abrirModal(button, id_producto, nombre_producto) {
  const card = button.closest(".card");
  const inputCantidad = card.querySelector("input[type='number']");
  const cantidad = parseInt(inputCantidad.value);
  const precio = parseFloat(button.getAttribute("data-precio"));

  if (!cantidad || cantidad <= 0) {
    Swal.fire({
      icon: 'warning',
      title: 'Cantidad inválida',
      text: 'Por favor, ingrese una cantidad válida.',
      confirmButtonText: 'Entendido'
    });
    return;
  }

  // Actualizar el modal con los datos del producto
  document.getElementById("productoSeleccionado").value = id_producto;
  document.getElementById("productoNombreSeleccionado").textContent = nombre_producto;
  document.getElementById("productoCantidadSeleccionada").textContent = cantidad;
  document.getElementById("productoPrecioSeleccionado").textContent = `$${precio.toFixed(2)}`;
  document.getElementById("comentarioInput").value = "";
  document.getElementById("productoSeleccionado").setAttribute("data-precio", precio);
  document.getElementById("productoSeleccionado").setAttribute("data-nombre", nombre_producto);
  document.getElementById("productoSeleccionado").setAttribute("data-cantidad", cantidad);
  document.getElementById("productoSeleccionado").setAttribute("data-stock", stock);

  // Mostrar el modal
  new bootstrap.Modal(document.getElementById("observacionModal")).show();
}

function agregarAlPedido() {
  const id = document.getElementById("productoSeleccionado").value;
  const comentario = document.getElementById("comentarioInput").value.trim();
  const precio = parseFloat(document.getElementById("productoSeleccionado").getAttribute("data-precio"));
  const nombre = document.getElementById("productoSeleccionado").getAttribute("data-nombre");
  const cantidad = parseInt(document.getElementById("productoSeleccionado").getAttribute("data-cantidad"));
  const stock = parseInt(document.getElementById("productoSeleccionado").getAttribute("data-stock"));

  if (!cantidad || cantidad <= 0) {
    Swal.fire({
      icon: 'warning',
      title: 'Cantidad inválida',
      text: 'Por favor, ingrese una cantidad válida.',
    });
    return;
  }

  // Si el pedido está en estado entregado (4), siempre agregar como nuevo line item
  // sin combinar con productos existentes
  if (window.estadoPedidoActual === 4) {
    if (cantidad > stock) {
      Swal.fire({
        icon: 'warning',
        title: 'Stock insuficiente',
        text: 'No puedes agregar más que el stock disponible.',
        confirmButtonText: 'Entendido'
      });
      return;
    }
    pedido.push({
      id: id,
      nombre: nombre,
      cantidad: cantidad,
      comentario: comentario,
      precio: precio,
      stock: stock
    });
  } else {
    // Para otros estados, combinar productos con el mismo ID y comentario
    const existente = pedido.find(p => p.id === id && p.comentario === comentario);

    if (existente) {
      if (existente.cantidad + cantidad > stock) {
        Swal.fire({
          icon: 'warning',
          title: 'Stock insuficiente',
          text: 'No puedes agregar más que el stock disponible.',
          confirmButtonText: 'Entendido'
        });
        return;
      }
      existente.cantidad += cantidad;
    } else {
      if (cantidad > stock) {
        Swal.fire({
          icon: 'warning',
          title: 'Stock insuficiente',
          text: 'No puedes agregar más que el stock disponible.',
          confirmButtonText: 'Entendido'
        });
        return;
      }
      pedido.push({
        id: id,
        nombre: nombre,
        cantidad: cantidad,
        comentario: comentario,
        precio: precio,
        stock: stock
      });
    }
  }

  actualizarLista();
  // Antes de mostrar cualquier SweetAlert de error, cerrar el modal si está abierto
  const modalElement = document.getElementById('observacionModal');
  const modal = bootstrap.Modal.getInstance(modalElement);
  if (modal) modal.hide();
}

function actualizarLista() {
  const lista = document.getElementById("pedidoLista");
  lista.innerHTML = "";
  let total = 0;

  pedido.forEach((item, index) => {
    const subtotal = item.precio * item.cantidad;
    total += subtotal;

    lista.innerHTML += `
      <li class="list-group-item d-flex justify-content-between align-items-center">
        <div>
          <strong>${item.nombre}</strong>
          <br>
          <small class="text-muted">${item.comentario || "sin obs."}</small>
          <br>
          <small>$${item.precio.toFixed(2)} x ${item.cantidad}</small>
        </div>
        <div class="text-end">
          <div class="mb-2">$${subtotal.toFixed(2)}</div>
          <div>
            <button class="btn btn-sm btn-secondary" onclick="cambiarCantidad(${index}, -1)">-</button>
            <button class="btn btn-sm btn-secondary" onclick="cambiarCantidad(${index}, 1)">+</button>
            <button class="btn btn-sm btn-danger" onclick="eliminarProducto(${index})">x</button>
          </div>
        </div>
      </li>`;
  });

  // Agregar el total al final de la lista
  lista.innerHTML += `
    <li class="list-group-item d-flex justify-content-between align-items-center bg-light">
      <strong>Total</strong>
      <strong>$${total.toFixed(2)}</strong>
    </li>`;
}

// Función para mostrar pedidos activos de la mesa en el contenedor específico
function mostrarPedidosActivosMesa(pedidos) {
  const contenedor = document.getElementById("pedidosActivosMesa");
  if (!contenedor) return;
  
  if (!pedidos || pedidos.length === 0) {
    contenedor.innerHTML = '<div class="text-muted">No hay pedidos activos.</div>';
    return;
  }
  
  let html = '<div class="accordion" id="accordionPedidosActivosMesa">';
  
  pedidos.forEach((pedido, index) => {
    const estadoNombre = getEstadoNombre(pedido.estados_idestados || pedido.estado);
    const estadoClass = getEstadoClass(pedido.estados_idestados || pedido.estado);
    
    html += `
      <div class="accordion-item">
        <h2 class="accordion-header" id="heading${pedido.pedido_id}">
          <button class="accordion-button ${index === 0 ? '' : 'collapsed'}" type="button" 
                  data-bs-toggle="collapse" data-bs-target="#collapse${pedido.pedido_id}" 
                  aria-expanded="${index === 0 ? 'true' : 'false'}" aria-controls="collapse${pedido.pedido_id}">
            <div class="d-flex justify-content-between w-100 align-items-center">
              <span><strong>Pedido #${pedido.pedido_id}</strong></span>
              <span class="badge ${estadoClass} me-2">${estadoNombre}</span>
            </div>
          </button>
        </h2>
        <div id="collapse${pedido.pedido_id}" class="accordion-collapse collapse ${index === 0 ? 'show' : ''}" 
             aria-labelledby="heading${pedido.pedido_id}" data-bs-parent="#accordionPedidosActivosMesa">
          <div class="accordion-body">
            <div class="mb-2">
              <strong>Fecha:</strong> ${pedido.fecha_hora}
            </div>
            ${pedido.token_utilizado ? `<div class="mb-2"><strong>Token:</strong> ${pedido.token_utilizado}</div>` : ''}
            <div class="mb-3">
              <strong>Productos:</strong>
              <ul class="list-unstyled ms-2">`;
    
    let totalPedido = 0;
    pedido.productos.forEach(producto => {
      const subtotal = parseFloat(producto.precio) * parseInt(producto.cantidad);
      totalPedido += subtotal;
      const esNuevo = producto.es_producto_nuevo == 1;
      
      html += `
                <li class="d-flex justify-content-between align-items-center border-bottom py-1">
                  <div>
                    ${producto.nombre} x${producto.cantidad}
                    ${esNuevo ? '<span class="badge bg-success ms-1">NUEVO</span>' : ''}
                    ${producto.comentario ? `<br><small class="text-muted">${producto.comentario}</small>` : ''}
                  </div>
                  <span>$${subtotal.toFixed(2)}</span>
                </li>`;
    });
    
    html += `
              </ul>
            </div>
            <div class="d-flex justify-content-between align-items-center">
              <strong>Total: $${totalPedido.toFixed(2)}</strong>
              <div>
                ${pedido.estados_idestados == 3 ? 
                  `<button class="btn btn-warning btn-sm me-2" onclick="modificarPedidoActivo(${pedido.pedido_id}, ${pedido.mesa_id || 'null'})">
                    <i class="fas fa-edit"></i> Modificar
                  </button>
                  <button class="btn btn-danger btn-sm" onclick="cancelarPedidoActivoDesdeCard(${pedido.pedido_id}, ${pedido.mesa_id || 'null'})">
                    <i class="fas fa-times"></i> Cancelar
                  </button>` : 
                  pedido.estados_idestados == 4 ? 
                  `<button class="btn btn-warning btn-sm" onclick="modificarPedidoActivo(${pedido.pedido_id}, ${pedido.mesa_id || 'null'})">
                    <i class="fas fa-plus"></i> Agregar productos
                  </button>` : ''
                }
              </div>
            </div>
          </div>
        </div>
      </div>`;
  });
  
  html += '</div>';
  contenedor.innerHTML = html;
}

// Función auxiliar para obtener el nombre del estado
function getEstadoNombre(estadoId) {
  const estados = {
    1: 'Pendiente',
    3: 'Confirmado',
    4: 'Entregado',
    5: 'Procesado'
  };
  return estados[estadoId] || 'Desconocido';
}

// Función auxiliar para obtener la clase CSS del estado
function getEstadoClass(estadoId) {
  const clases = {
    1: 'bg-warning',
    3: 'bg-info',
    4: 'bg-success',
    5: 'bg-primary'
  };
  return clases[estadoId] || 'bg-secondary';
}

function actualizarCantidadEnBD(index, nuevaCantidad) {
  // Solo si hay pedido activo
  const mesaId = document.getElementById('mesaSelect').value;
  if (!mesaId || !window.pedidosActivosGlobal || !window.pedidosActivosGlobal[mesaId]) return;
  const pedidoActivo = window.pedidosActivosGlobal[mesaId];
  const prod = pedido[index];
  fetch('../controllers/actualizar_detalle_pedido.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      pedido_id: pedidoActivo.pedido_id,
      producto_id: prod.id,
      comentario: prod.comentario,
      cantidad: nuevaCantidad
    })
  })
  .then(res => res.json())
  .then(data => {
    if (!data.success) {
      Swal.fire('Error', data.message || 'No se pudo actualizar el pedido', 'error');
    } else {
      // Actualizar el stock en la card si está visible
      const card = document.querySelector(`#productosContainer .card[data-id='${prod.id}']`);
      if (card) {
        const stockBadge = card.querySelector('.badge.bg-secondary');
        if (stockBadge) {
          // Restar la cantidad agregada/eliminada
          let stock = parseInt(stockBadge.textContent.replace(/\D/g, ''));
          let diff = nuevaCantidad - (prod.cantidad || 0);
          stockBadge.textContent = 'Stock: ' + (stock - diff);
        }
      }
    }
  });
}

function cambiarCantidad(index, delta) {
  // Solo permitir incrementar si no supera el stock
  if (delta > 0) {
    const item = pedido[index];
    if (item.stock !== undefined && item.cantidad + 1 > item.stock) {
      Swal.fire({
        icon: 'warning',
        title: 'Stock insuficiente',
        text: 'No puedes agregar más que el stock disponible.',
        confirmButtonText: 'Entendido'
      });
      return;
    }
  }
  pedido[index].cantidad += delta;
  if (pedido[index].cantidad <= 0) {
    eliminarProducto(index);
    return;
  }
  actualizarCantidadEnBD(index, pedido[index].cantidad);
  actualizarLista();
}

function eliminarProducto(index) {
  actualizarCantidadEnBD(index, 0);
  pedido.splice(index, 1);
  actualizarLista();
}

function confirmarPedido() {
  const mesa = document.getElementById("mesaSelect").value;
  const mesaSelect = document.getElementById("mesaSelect");
  const mesaNombre = mesaSelect.options[mesaSelect.selectedIndex].text;

  if (!mesa) {
    Swal.fire({
      icon: 'warning',
      title: 'Mesa no seleccionada',
      text: 'Por favor, seleccione una mesa antes de confirmar el pedido.',
    });
    return;
  }

  if (pedido.length === 0) {
    Swal.fire({
      icon: 'info',
      title: 'Sin productos',
      text: 'Agrega al menos un producto al pedido.',
    });
    return;
  }

  // Calcular el total del pedido
  const total = pedido.reduce((sum, item) => sum + (item.precio * item.cantidad), 0);

  fetch("../controllers/confirmar_pedido.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      mesa_id: parseInt(mesa),
      productos: pedido,
      total: total,
      pedido_id: pedidoIdModificar,
      nuevo_estado: (pedidoIdModificar && window.pedidoCanceladoParaModificar) ? 2 : undefined
    }),
  })
    .then((res) => res.json())
    .then((data) => {
      if (data.success) {
        // Verificar si el pedido fue reactivado (estaba entregado)
        const mensaje = pedidoIdModificar ? 
          (data.message.includes('actualizado') ? 
            'Pedido actualizado correctamente. Si estaba entregado, ahora aparecerá en la cocina.' : 
            data.message) : 
          data.message;
        
        Swal.fire({
          icon: 'success',
          title: 'Pedido registrado',
          text: mensaje,
        }).then(() => {
          if (usuarioInteractuando()) {
            actualizarPendiente = true;
          } else {
            actualizarSelectMesas();
            cargarPedidosActivosGlobal();
          }
          pedido = [];
          pedidoIdModificar = null;
          actualizarLista();
          // Limpiar los inputs seleccion de mesa y categoria
          reseteandoPorConfirmacion = true;
          document.getElementById("categoriaSelect").value = "";
          document.getElementById("mesaSelect").value = "";
          document.getElementById("productosContainer").innerHTML = "";
          setTimeout(() => { reseteandoPorConfirmacion = false; }, 500);
        });
      } else {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: data.message,
        });
      }
    })
    .catch((error) => {
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: 'No se pudo guardar el pedido.',
      });
      console.error(error);
    });
}

function generarTokenMesa() {
  const mesaId = document.getElementById('mesaSelect').value;
  if (!mesaId) {
    if (typeof Swal === 'undefined') {
      const script = document.createElement('script');
      script.src = 'https://cdn.jsdelivr.net/npm/sweetalert2@11';
      script.onload = function() {
        Swal.fire('Seleccione una mesa', 'Debe seleccionar una mesa para generar el token', 'warning');
      };
      document.body.appendChild(script);
    } else {
      Swal.fire('Seleccione una mesa', 'Debe seleccionar una mesa para generar el token', 'warning');
    }
    return;
  }
  fetch('../controllers/generar_token.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'mesa_id=' + mesaId
  })
  .then(res => res.json())
  .then(data => {
    function showTokenSwal() {
      if (data.success) {
        Swal.fire({
          title: 'Token generado',
          html: 'El token para la mesa es: <b>' + data.token + '</b><br>Expira a las: <b>' + (new Date(data.expira).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})) + '</b>',
          icon: 'success'
        }).then(() => {
          location.reload();
        });
      } else {
        Swal.fire('Error', data.message, 'error');
      }
    }
    if (typeof Swal === 'undefined') {
      const script = document.createElement('script');
      script.src = 'https://cdn.jsdelivr.net/npm/sweetalert2@11';
      script.onload = showTokenSwal;
      document.body.appendChild(script);
    } else {
      showTokenSwal();
    }
  })
  .catch(error => {
    console.error('Error al generar token:', error);
    function showErrorSwal() {
      Swal.fire('Error', 'No se pudo generar el token.', 'error');
    }
    if (typeof Swal === 'undefined') {
      const script = document.createElement('script');
      script.src = 'https://cdn.jsdelivr.net/npm/sweetalert2@11';
      script.onload = showErrorSwal;
      document.body.appendChild(script);
    } else {
      showErrorSwal();
    }
  });
}

// Función para reactivar un pedido entregado
function reactivarPedidoEntregado(pedidoId) {
  fetch("../controllers/reactivar_pedido_entregado.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      pedido_id: pedidoId
    }),
  })
  .then((res) => res.json())
  .then((data) => {
    if (data.success) {
      Swal.fire({
        icon: 'success',
        title: 'Pedido reactivado',
        text: data.message,
      }).then(() => {
        // Recargar los pedidos activos
        cargarPedidosActivosGlobal();
      });
    } else {
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: data.message,
      });
    }
  })
  .catch((error) => {
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'No se pudo reactivar el pedido.',
    });
    console.error(error);
  });
}

// Exportar funciones globales para el HTML
document.addEventListener("DOMContentLoaded", function () {
  window.confirmarPedido = confirmarPedido;
  window.generarTokenMesa = generarTokenMesa;
  window.reactivarPedidoEntregado = reactivarPedidoEntregado;
  
  // Cargar todos los pedidos activos de todas las mesas al iniciar
  cargarPedidosActivosGlobal();
});

window.modificarPedidoActivo = function(pedidoId, mesaId) {
  // Buscar el pedido en la variable global
  const pedidoObj = window.pedidosActivosGlobal[mesaId];
  if (!pedidoObj || pedidoObj.pedido_id != pedidoId) return;

  // Si el pedido está entregado, cargar solo productos nuevos para edición
  if (pedidoObj.estados_idestados === 4) {
    // Obtener solo productos nuevos usando para_edicion = true
    fetch("../controllers/pedidos_activos_mesa.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ mesa_id: mesaId, para_edicion: true })
    })
    .then(res => res.json())
    .then(data => {
      if (data.success && data.pedidos && data.pedidos.length > 0) {
        // Solo cargar productos nuevos (es_producto_nuevo = 1)
        const productos = data.pedidos[0].productos;
        pedido = productos.map(prod => ({
          id: prod.id,
          nombre: prod.nombre,
          cantidad: parseInt(prod.cantidad),
          comentario: prod.comentario,
          precio: parseFloat(prod.precio),
          es_producto_nuevo: prod.es_producto_nuevo || 0
        }));
      } else {
        pedido = []; // Carrito vacío si no hay productos nuevos
      }
      actualizarLista(pedidoObj.estados_idestados);
    });
  } else {
    // Para otros estados, cargar todos los productos normalmente
    pedido = pedidoObj.productos.map(prod => ({
      id: prod.id,
      nombre: prod.nombre,
      cantidad: parseInt(prod.cantidad),
      comentario: prod.comentario,
      precio: parseFloat(prod.precio),
      estado: pedidoObj.estado_nombre, // Guardar el estado textual
      es_producto_nuevo: prod.es_producto_nuevo || 0
    }));
    actualizarLista(pedidoObj.estados_idestados);
  }
  
  // Resaltar el pedido que se está editando
  document.querySelectorAll('#pedidosActivosMesa .border').forEach(div => div.classList.remove('border-primary'));
  const divPedido = document.getElementById('pedidoActivo_' + pedidoId);
  if (divPedido) divPedido.classList.add('border-primary');
  // Seleccionar la mesa en el select
  const mesaSelect = document.getElementById('mesaSelect');
  if (mesaSelect) {
    mesaSelect.value = mesaId;
    mesaSelect.dispatchEvent(new Event('change'));
  }
  pedidoIdModificar = pedidoId;
  window.estadoPedidoActual = pedidoObj.estados_idestados; // Guardar estado numérico global
  actualizarBotonCancelarPedido(window.estadoPedidoActual);
}

// Modifico actualizarLista para deshabilitar edición si estadoPedidoActual === 4
function actualizarLista(estadoPedido) {
  const lista = document.getElementById("pedidoLista");
  lista.innerHTML = "";
  let total = 0;
  const esEntregado = (typeof estadoPedido !== 'undefined' ? estadoPedido : window.estadoPedidoActual) === 4;

  pedido.forEach((item, index) => {
    const subtotal = item.precio * item.cantidad;
    total += subtotal;
    lista.innerHTML += `
      <li class="list-group-item d-flex justify-content-between align-items-center">
        <div>
          <strong>${item.nombre}</strong>
          ${item.es_producto_nuevo === 1 ? '<span class="badge bg-success ms-1">NUEVO</span>' : ''}
          <br>
          <small class="text-muted">${item.comentario || "sin obs."}</small>
          <br>
          <small>$${item.precio.toFixed(2)} x ${item.cantidad}</small>
        </div>
        <div class="text-end">
          <div class="mb-2">$${subtotal.toFixed(2)}</div>
          <div>
            <button class="btn btn-sm btn-secondary" onclick="cambiarCantidad(${index}, -1)" ${esEntregado ? 'disabled' : ''}>-</button>
            <button class="btn btn-sm btn-secondary" onclick="cambiarCantidad(${index}, 1)" ${esEntregado ? 'disabled' : ''}>+</button>
            <button class="btn btn-sm btn-danger" onclick="eliminarProducto(${index})" ${esEntregado ? 'disabled' : ''}>x</button>
          </div>
        </div>
      </li>`;
  });
  // Agregar el total al final de la lista
  lista.innerHTML += `
    <li class="list-group-item d-flex justify-content-between align-items-center bg-light">
      <strong>Total</strong>
      <strong>$${total.toFixed(2)}</strong>
    </li>`;
  if (typeof estadoPedido !== 'undefined' || typeof window.estadoPedidoActual !== 'undefined') {
    actualizarBotonCancelarPedido(typeof estadoPedido !== 'undefined' ? estadoPedido : window.estadoPedidoActual);
  }
}

// Modifico cambiarCantidad y eliminarProducto para advertir si es entregado
function cambiarCantidad(index, delta) {
  if ((window.estadoPedidoActual === 4)) {
    Swal.fire({
      icon: 'info',
      title: 'No permitido',
      text: 'No puedes modificar productos en un pedido entregado. Solo puedes agregar nuevos productos.'
    });
    return;
  }
  // Solo permitir incrementar si no supera el stock
  if (delta > 0) {
    const item = pedido[index];
    if (item.stock !== undefined && item.cantidad + 1 > item.stock) {
      Swal.fire({
        icon: 'warning',
        title: 'Stock insuficiente',
        text: 'No puedes agregar más que el stock disponible.',
        confirmButtonText: 'Entendido'
      });
      return;
    }
  }
  pedido[index].cantidad += delta;
  if (pedido[index].cantidad <= 0) {
    eliminarProducto(index);
    return;
  }
  actualizarCantidadEnBD(index, pedido[index].cantidad);
  actualizarLista();
}
function eliminarProducto(index) {
  if ((window.estadoPedidoActual === 4)) {
    Swal.fire({
      icon: 'info',
      title: 'No permitido',
      text: 'No puedes eliminar productos en un pedido entregado. Solo puedes agregar nuevos productos.'
    });
    return;
  }
  actualizarCantidadEnBD(index, 0);
  pedido.splice(index, 1);
  actualizarLista();
}

function confirmarPedido() {
  const mesa = document.getElementById("mesaSelect").value;
  const mesaSelect = document.getElementById("mesaSelect");
  const mesaNombre = mesaSelect.options[mesaSelect.selectedIndex].text;

  if (!mesa) {
    Swal.fire({
      icon: 'warning',
      title: 'Mesa no seleccionada',
      text: 'Por favor, seleccione una mesa antes de confirmar el pedido.',
    });
    return;
  }

  if (pedido.length === 0) {
    Swal.fire({
      icon: 'info',
      title: 'Sin productos',
      text: 'Agrega al menos un producto al pedido.',
    });
    return;
  }

  // Calcular el total del pedido
  const total = pedido.reduce((sum, item) => sum + (item.precio * item.cantidad), 0);

  // Crear resumen del pedido para mostrar
  let resumenHtml = `
    <div class="text-start">
      <h5 class="mb-3"><i class="fas fa-chair me-2"></i>Mesa: ${mesaNombre}</h5>
      <div class="mb-3">
        <strong><i class="fas fa-shopping-cart me-2"></i>Productos:</strong>
        <div class="mt-2">
  `;

  pedido.forEach(item => {
    const subtotal = item.precio * item.cantidad;
    resumenHtml += `
      <div class="d-flex justify-content-between align-items-center border-bottom py-2">
        <div>
          <strong>${item.nombre}</strong>
          ${item.es_producto_nuevo === 1 ? '<span class="badge bg-success ms-1">NUEVO</span>' : ''}
          <br>
          <small class="text-muted">${item.comentario || 'Sin observaciones'}</small>
          <br>
          <small class="text-info">$${item.precio.toFixed(2)} × ${item.cantidad}</small>
        </div>
        <div class="text-end">
          <strong>$${subtotal.toFixed(2)}</strong>
        </div>
      </div>
    `;
  });

  resumenHtml += `
        </div>
      </div>
      <div class="d-flex justify-content-between align-items-center bg-light p-3 rounded">
        <strong class="fs-5"><i class="fas fa-calculator me-2"></i>Total del pedido:</strong>
        <strong class="fs-4 text-success">$${total.toFixed(2)}</strong>
      </div>
    </div>
  `;

  // Mostrar resumen y confirmar
  Swal.fire({
    title: pedidoIdModificar ? 
      '<i class="fas fa-edit"></i> Confirmar Modificación' : 
      '<i class="fas fa-check"></i> Confirmar Pedido',
    html: resumenHtml,
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#28a745',
    cancelButtonColor: '#6c757d',
    confirmButtonText: pedidoIdModificar ? 
      '<i class="fas fa-save me-2"></i>Confirmar Modificación' : 
      '<i class="fas fa-check me-2"></i>Confirmar Pedido',
    cancelButtonText: '<i class="fas fa-times me-2"></i>Cancelar',
    width: '600px',
    customClass: {
      popup: 'swal-wide'
    }
  }).then((result) => {
    if (result.isConfirmed) {
      // Proceder con la confirmación del pedido
      enviarPedido(mesa, total);
    }
  });
}

// Función separada para enviar el pedido al servidor
function enviarPedido(mesa, total) {
  // Mostrar loading
  Swal.fire({
    title: 'Procesando pedido...',
    html: 'Por favor espere mientras se procesa su pedido.',
    allowOutsideClick: false,
    allowEscapeKey: false,
    showConfirmButton: false,
    didOpen: () => {
      Swal.showLoading();
    }
  });

  fetch("../controllers/confirmar_pedido.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      mesa_id: parseInt(mesa),
      productos: pedido,
      total: total,
      pedido_id: pedidoIdModificar,
      nuevo_estado: (pedidoIdModificar && window.pedidoCanceladoParaModificar) ? 2 : undefined
    }),
  })
    .then((res) => res.json())
    .then((data) => {
      if (data.success) {
        // Verificar si el pedido fue reactivado (estaba entregado)
        const mensaje = pedidoIdModificar ? 
          (data.message.includes('actualizado') ? 
            'Pedido actualizado correctamente. Si estaba entregado, ahora aparecerá en la cocina.' : 
            data.message) : 
          data.message;
        
        Swal.fire({
          icon: 'success',
          title: 'Pedido registrado',
          text: mensaje,
        }).then(() => {
          if (usuarioInteractuando()) {
            actualizarPendiente = true;
          } else {
            actualizarSelectMesas();
            cargarPedidosActivosGlobal();
          }
          pedido = [];
          pedidoIdModificar = null;
          window.estadoPedidoActual = null;
          actualizarLista();
          // Limpiar los inputs seleccion de mesa y categoria
          document.getElementById("mesaSelect").value = "";
          document.getElementById("categoriaSelect").value = "";
          document.getElementById("productosContainer").innerHTML = "";
          
          // Recargar pedidos activos de la mesa si hay una seleccionada
          const mesaSelect = document.getElementById("mesaSelect");
          if (mesaSelect.value) {
            mostrarPedidosActivosMesa([]);
          }
        });
      } else {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: data.message,
        });
      }
    })
    .catch((error) => {
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: 'No se pudo guardar el pedido.',
      });
      console.error(error);
    });
}

window.cancelarPedidoActual = function() {
  if (!pedidoIdModificar) {
    Swal.fire('No hay pedido seleccionado', 'Seleccione un pedido para cancelar.', 'info');
    return;
  }
  // Solo permitir si el estado es confirmado
  if (window.estadoPedidoActual !== 3) {
    Swal.fire('No permitido', 'Solo se puede cancelar un pedido en estado confirmado.', 'info');
    return;
  }
  Swal.fire({
    title: '¿Cancelar pedido?',
    text: 'Esta acción no se puede deshacer. ¿Desea cancelar el pedido actual?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Sí, cancelar',
    cancelButtonText: 'No'
  }).then((result) => {
    if (result.isConfirmed) {
      fetch('../controllers/cancelar_pedido.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ pedido_id: pedidoIdModificar })
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          Swal.fire('Pedido cancelado', data.message || 'El pedido ha sido cancelado.', 'success').then(() => {
            pedido = [];
            pedidoIdModificar = null;
            window.estadoPedidoActual = null;
            actualizarLista();
            actualizarBotonCancelarPedido(null);
            actualizarSelectMesas();
            cargarPedidosActivosGlobal();
          });
        } else {
          Swal.fire('Error', data.message || 'No se pudo cancelar el pedido.', 'error');
        }
      })
      .catch(() => {
        Swal.fire('Error', 'No se pudo cancelar el pedido.', 'error');
      });
    }
  });
}

window.cancelarPedidoActivoDesdeCard = function(pedidoId, mesaId) {
  // Buscar el pedido en la variable global
  const pedidoObj = window.pedidosActivosGlobal[mesaId];
  if (!pedidoObj || pedidoObj.pedido_id != pedidoId) return;
  if (pedidoObj.estados_idestados !== 3) {
    Swal.fire('No permitido', 'Solo se puede cancelar un pedido en estado confirmado.', 'info');
    return;
  }
  Swal.fire({
    title: '¿Cancelar pedido?',
    text: 'Esta acción no se puede deshacer. ¿Desea cancelar el pedido actual?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Sí, cancelar',
    cancelButtonText: 'No'
  }).then((result) => {
    if (result.isConfirmed) {
      fetch('../controllers/cancelar_pedido.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ pedido_id: pedidoId })
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          Swal.fire('Pedido cancelado', data.message || 'El pedido ha sido cancelado.', 'success').then(() => {
            actualizarSelectMesas();
            cargarPedidosActivosGlobal();
          });
        } else {
          Swal.fire('Error', data.message || 'No se pudo cancelar el pedido.', 'error');
        }
      })
      .catch(() => {
        Swal.fire('Error', 'No se pudo cancelar el pedido.', 'error');
      });
    }
  });
}

document.addEventListener('DOMContentLoaded', function() {
  const loginForm = document.getElementById('loginForm');
  if (loginForm) {
    loginForm.addEventListener('submit', async function(e) {
      e.preventDefault();
      const correo = document.getElementById('correo').value;
      const contrasena = document.getElementById('contrasena').value;
      try {
        const response = await fetch('../controllers/login.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ correo, contrasena })
        });
        const data = await response.json();
        if (data.success) {
          window.location.href = 'mesero.php';
        } else {
          Swal.fire('Error', data.message || 'Credenciales incorrectas', 'error');
        }
      } catch (err) {
        Swal.fire('Error', 'Error de conexión', 'error');
      }
    });
  }

  // Cierre de sesión con SweetAlert2
  const btnCerrarSesion = document.getElementById('btnCerrarSesion');
  if (btnCerrarSesion) {
    btnCerrarSesion.addEventListener('click', function(e) {
      e.preventDefault();
      if (typeof Swal === 'undefined') {
        // Cargar SweetAlert2 dinámicamente si no está
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/sweetalert2@11';
        script.onload = showLogoutSwal;
        document.body.appendChild(script);
      } else {
        showLogoutSwal();
      }
      function showLogoutSwal() {
        Swal.fire({
          title: '¿Deseas cerrar tu sesión?',
          text: 'Se cerrará tu sesión y volverás al inicio de sesión.',
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#d33',
          cancelButtonColor: '#3085d6',
          confirmButtonText: 'Sí, cerrar sesión',
          cancelButtonText: 'Cancelar'
        }).then((result) => {
          if (result.isConfirmed) {
            window.location.href = '../controllers/logout.php';
          }
        });
      }
    });
  }
});

// === ACTUALIZACIÓN DINÁMICA DEL SELECT DE MESAS ===
function actualizarSelectMesas() {
  const mesaSelect = document.getElementById('mesaSelect');
  if (!mesaSelect) return;
  // Detectar si el select está abierto o el usuario está interactuando
  if (mesaSelect.matches(':focus') || mesaSelect.open) return;
  const seleccionActual = mesaSelect.value;
  fetch('../controllers/cargar_mesas.php')
    .then(res => res.json())
    .then(data => {
      if (data.success && Array.isArray(data.mesas)) {
        const opciones = ['<option value="">Seleccione una mesa</option>'];
        data.mesas.forEach(mesa => {
          const deshabilitar = (mesa.tiene_pedido_confirmado > 0 || mesa.tiene_pedido_entregado > 0 || mesa.tiene_token_activo > 0);
          const disabled = deshabilitar ? 'disabled' : '';
          const msg = mesa.tiene_pedido_confirmado > 0 ? ' (Confirmado)' : (mesa.tiene_pedido_entregado > 0 ? ' (Entregado)' : '');
          const token = mesa.token_activo ? ' | Token #' + mesa.token_activo : '';
          const tokenActivo = mesa.token_activo ? '1' : '0';
          opciones.push(`<option value="${mesa.idmesas}" data-token-activo="${tokenActivo}" ${disabled}>${mesa.nombre}${token}${msg}</option>`);
        });
        // Guardar la selección previa
        const prevValue = mesaSelect.value;
        mesaSelect.innerHTML = opciones.join('');
        // Restaurar selección si sigue disponible y habilitada
        if (prevValue && mesaSelect.querySelector(`option[value="${prevValue}"]`) && !mesaSelect.querySelector(`option[value="${prevValue}"]`).disabled) {
          mesaSelect.value = prevValue;
        } else {
          mesaSelect.value = '';
        }
        // Solo disparar el evento change si la selección realmente cambió
        if (mesaSelect.value !== seleccionActual) {
          mesaSelect.dispatchEvent(new Event('change'));
        }
      }
    });
}
// Llamar periódicamente, pero solo si el select no está abierto
// === FLAGS Y FUNCIONES DE INTERACCIÓN (AJUSTADO) ===
let actualizarPendiente = false;

function usuarioInteractuando() {
  // Modal abierto
  if (document.querySelector('.modal.show')) return true;
  // Input de cantidad en foco
  if (document.querySelector('#productosContainer input[type="number"]:focus')) return true;
  // Select de mesa o categoría en foco (desplegado)
  const mesaSelect = document.getElementById('mesaSelect');
  const categoriaSelect = document.getElementById('categoriaSelect');
  if ((mesaSelect && mesaSelect === document.activeElement) || (categoriaSelect && categoriaSelect === document.activeElement)) return true;
  // Card de pedidos activos desplegada (acordeón abierto)
  if (document.querySelector('#pedidosActivosMesa .accordion-collapse.show')) return true;
  return false;
}
// Detectar cierre de acordeón (card de pedidos activos)
document.addEventListener('hidden.bs.collapse', function(e) {
  if (e.target.classList.contains('accordion-collapse')) {
    setTimeout(() => {
      if (actualizarPendiente && !usuarioInteractuando()) {
        actualizarPendiente = false;
        actualizarSelectMesas();
        cargarPedidosActivosGlobal();
      }
    }, 100);
  }
});
// Detectar blur en inputs de cantidad
function setupInputBlurListener() {
  document.querySelectorAll('#productosContainer input[type="number"]').forEach(input => {
    input.addEventListener('blur', function() {
      setTimeout(() => {
        if (actualizarPendiente && !usuarioInteractuando()) {
          actualizarPendiente = false;
          actualizarSelectMesas();
          cargarPedidosActivosGlobal();
        }
      }, 100); // Esperar a que termine el blur
    });
  });
}
document.addEventListener('DOMContentLoaded', setupInputBlurListener);
// Llamar también tras cargar productos
const origProductosChange = document.querySelector('#categoriaSelect')?.onchange;
document.querySelector('#categoriaSelect')?.addEventListener('change', function(e) {
  if (typeof origProductosChange === 'function') origProductosChange.call(this, e);
  setupInputBlurListener();
});
// Detectar blur en selects
['mesaSelect', 'categoriaSelect'].forEach(id => {
  const sel = document.getElementById(id);
  if (sel) {
    sel.addEventListener('blur', function() {
      setTimeout(() => {
        if (actualizarPendiente && !usuarioInteractuando()) {
          actualizarPendiente = false;
          actualizarSelectMesas();
          cargarPedidosActivosGlobal();
        }
      }, 100);
    });
  }
});
// === FIN FLAGS ===

// Cambiar intervalos automáticos para que respeten la interacción
function intervaloActualizacion() {
  if (usuarioInteractuando()) {
    actualizarPendiente = true;
    return;
  }
  actualizarSelectMesas();
  cargarPedidosActivosGlobal();
}
setInterval(intervaloActualizacion, 7000); // 7 segundos es un buen balance para apps de restaurante

// === FUNCIONES GLOBALES ===
function cargarPedidosActivosGlobal() {
  const mesaSelect = document.getElementById('mesaSelect');
  if (mesaSelect && (mesaSelect.matches(':focus') || mesaSelect.open)) return;
  fetch("../controllers/pedidos_activos.php")
    .then(res => res.json())
    .then(data => {
      const cont = document.getElementById("pedidosActivosMesa");
      if (!cont) return;
      window.pedidosActivosGlobal = {};
      // Obtener el id del usuario en sesión
      let usuarioIdSesion = window.usuarioIdSesion;
      if (!usuarioIdSesion) {
        // Intenta obtenerlo de un atributo data-usuario-id en el body o en un div principal
        const body = document.body;
        usuarioIdSesion = body.getAttribute('data-usuario-id') || null;
        if (usuarioIdSesion) window.usuarioIdSesion = usuarioIdSesion;
      }
      if (data.success && data.pedidos && data.pedidos.length > 0) {
        // Filtrar solo los pedidos hechos por el usuario en sesión
        const pedidosFiltrados = usuarioIdSesion ? data.pedidos.filter(p => String(p.usuario_id) === String(usuarioIdSesion)) : data.pedidos;
        if (pedidosFiltrados.length > 0) {
          let html = '<div class="accordion" id="accordionPedidosActivos">';
          pedidosFiltrados.forEach((pedido, idx) => {
            window.pedidosActivosGlobal[pedido.mesa_id] = pedido;
            html += `
              <div class="accordion-item">
                <h2 class="accordion-header" id="heading${pedido.pedido_id}">
                  <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse${pedido.pedido_id}" aria-expanded="false" aria-controls="collapse${pedido.pedido_id}">
                    <strong>${pedido.mesa_nombre || pedido.mesa_id}</strong> 
                  </button>
                </h2>
                <div id="collapse${pedido.pedido_id}" class="accordion-collapse collapse" aria-labelledby="heading${pedido.pedido_id}" data-bs-parent="#accordionPedidosActivos">
                  <div class="accordion-body">
                    <div><strong>Pedido #:</strong> ${pedido.pedido_id}</div>
                    <div><strong>Productos:</strong><ul class='mb-1'>`;
            pedido.productos.forEach(prod => {
              const esNuevo = prod.es_producto_nuevo === 1;
              const badgeNuevo = esNuevo ? '<span class="badge bg-success ms-1">NUEVO</span>' : '';
              html += `<li>${prod.nombre} x${prod.cantidad} ($${parseFloat(prod.precio).toFixed(2)})${badgeNuevo}</li>`;
            });
            html += `</ul></div>
                    <div><strong>Total:</strong> $${pedido.productos.reduce((sum, p) => sum + (parseFloat(p.precio) * parseInt(p.cantidad)), 0).toFixed(2)}</div>
                    <div class="d-flex justify-content-between align-items-center mt-2">
                      <button class='btn btn-warning btn-sm' onclick='modificarPedidoActivo(${pedido.pedido_id}, ${pedido.mesa_id})'>
                        ${pedido.estados_idestados === 4 ? 'Adicionar productos' : 'Modificar pedido'}
                      </button>
                      <span class="badge bg-info text-dark ms-2">${pedido.estado_nombre || 'Desconocido'}</span>
                      ${pedido.estados_idestados === 3 ? `<button class='btn btn-danger btn-sm ms-2' onclick='cancelarPedidoActivoDesdeCard(${pedido.pedido_id}, ${pedido.mesa_id})'><i class=\"fas fa-times me-1\"></i>Cancelar</button>` : ''}
                    </div>
                  </div>
                </div>
              </div>
            `;
          });
          html += '</div>';
          cont.innerHTML = html;
        } else {
          cont.innerHTML = '<div class="text-muted">No hay pedidos activos.</div>';
        }
      } else {
        cont.innerHTML = '<div class="text-muted">No hay pedidos activos.</div>';
      }
    });
}

// Función global para cancelar el token de una mesa
function cancelarTokenMesa(mesaId, mesaNombre) {
  Swal.fire({
    title: '¿Cancelar token?',
    text: `¿Seguro que deseas cancelar el token de la mesa "${mesaNombre}"?`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Sí, cancelar',
    cancelButtonText: 'No'
  }).then((result) => {
    if (result.isConfirmed) {
      fetch('../controllers/cancelar_token.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ mesa_id: mesaId })
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          Swal.fire('Token cancelado', 'El token ha sido cancelado correctamente.', 'success')
            .then(() => location.reload());
        } else {
          Swal.fire('Error', data.message || 'No se pudo cancelar el token', 'error');
        }
      })
      .catch(() => {
        Swal.fire('Error', 'No se pudo cancelar el token', 'error');
      });
    }
  });
}
window.cancelarTokenMesa = cancelarTokenMesa;

// Mostrar/ocultar el botón de cancelar token según la mesa seleccionada
window.addEventListener('DOMContentLoaded', function() {
  const mesaSelect = document.getElementById('mesaSelect');
  const btnCancelarToken = document.getElementById('btnCancelarToken');
  if (!mesaSelect || !btnCancelarToken) return;
  function updateBtnCancelarToken() {
    const selected = mesaSelect.options[mesaSelect.selectedIndex];
    if (selected && selected.getAttribute('data-token-activo') === '1') {
      btnCancelarToken.style.display = '';
    } else {
      btnCancelarToken.style.display = 'none';
    }
  }
  mesaSelect.addEventListener('change', updateBtnCancelarToken);
  updateBtnCancelarToken();
});

// Agregar estilos CSS para el modal de resumen
const style = document.createElement('style');
style.textContent = `
  .swal-wide {
    width: 90% !important;
    max-width: 600px !important;
  }
  
  .swal2-html-container {
    max-height: 400px;
    overflow-y: auto;
  }
  
  @media (max-width: 768px) {
    .swal-wide {
      width: 95% !important;
      margin: 10px !important;
    }
  }
`;
document.head.appendChild(style);