// funcion para cargar productos al seleccionar una categoria de productos
document.addEventListener("DOMContentLoaded", function () {
  const select = document.querySelector("#categoriaSelect");
  const contenedor = document.querySelector("#productosContainer");

  select.addEventListener("change", function () {
    const idcategorias = select.value;

    if (!idcategorias) {
      contenedor.innerHTML = "";
      return;
    }

    fetch("../controllers/cargar_productos.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: "idcategoria=" + encodeURIComponent(idcategorias),
    })
      .then((response) => response.text())
      .then((data) => {
        contenedor.innerHTML = data;
      })
      .catch((error) => {
        contenedor.innerHTML = "<p>Error al cargar productos.</p>";
        console.error(error);
      });
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

// funcion para agregar productos al pedido
function abrirModal(button, id_producto, nombre_producto) {
  const card = button.closest(".card");
  const inputCantidad = card.querySelector("input[type='number']");
  const cantidad = parseInt(inputCantidad.value);

  if (!cantidad || cantidad <= 0) {
    Swal.fire({
      icon: 'warning',
      title: 'Cantidad inválida',
      text: 'Por favor, ingrese una cantidad válida.',
      confirmButtonText: 'Entendido'
    });
    return;
  }
  document.getElementById("productoSeleccionado").value = id_producto;
  document.getElementById("comentarioInput").value = "";
  new bootstrap.Modal(document.getElementById("observacionModal")).show();
}


function actualizarLista() {
  const lista = document.getElementById("pedidoLista");
  lista.innerHTML = "";
  pedido.forEach((item, index) => {
    lista.innerHTML += `
      <li class="list-group-item d-flex justify-content-between align-items-center">
        ${item.nombre} (${item.comentario || "sin obs."}) x${item.cantidad}
        <div>
          <button class="btn btn-sm btn-secondary" onclick="cambiarCantidad(${index}, -1)">-</button>
          <button class="btn btn-sm btn-secondary" onclick="cambiarCantidad(${index}, 1)">+</button>
          <button class="btn btn-sm btn-danger" onclick="eliminarProducto(${index})">x</button>
        </div>
      </li>`;
  });
}

function cambiarCantidad(index, delta) {
  pedido[index].cantidad += delta;
  if (pedido[index].cantidad <= 0) eliminarProducto(index);
  else actualizarLista();
}

function eliminarProducto(index) {
  pedido.splice(index, 1);
  actualizarLista();
}

/* function confirmarPedido() {
  const mesa = document.getElementById("mesaSelect").value;
  const pedidosActivos = document.getElementById("pedidosActivos");
  pedidosActivos.innerHTML += <li class="list-group-item">${mesa}: ${pedido.length} productos</li>;
  pedido = [];
  actualizarLista();
  // Aquí se podría marcar la mesa como ocupada en la DB
} */

function showSwalError(msg) {
    Swal.fire({
        icon: 'error',
        title: 'Error',
        text: msg || 'Ocurrió un error.',
        confirmButtonText: 'Aceptar'
    });
}
