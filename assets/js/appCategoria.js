const categoriaModal = new bootstrap.Modal(document.querySelector('#categoriaModal'));
const frmCategoria = document.querySelector('#categoriaForm'); // ID correcto del form
const crearCategoriaBtn = document.querySelector('#crearCategoriaBtn');
const categoriasTableBody = document.querySelector('#categoriasTableBody');
let categoriaId = document.querySelector("#categoriaId"); // let para poder reasignar
let nombreCategoria = document.querySelector('#nombreCategoria');
let opcion = '';
document.addEventListener('DOMContentLoaded', () => {

    const tablaCategoria = $('#tablaCategorias').DataTable({
        responsive: {
            details: {
                renderer: function (api, rowIdx, columns) {
                    let data = columns
                        .filter(col => col.hidden)
                        .map(col => {
                            return `<tr>
                                        <td class="text-end fw-bold">${col.title}</td>
                                        <td>${col.data}</td>
                                    </tr>`;
                        })
                        .join('');
                    return data ? $('<table class="table table-sm table-bordered mb-0 w-100"/>').append(data) : false;
                }
            }
        },
        columnDefs: [
            { responsivePriority: 1, targets: -1 }, 
            { responsivePriority: 2, targets: 1 }, 
            { responsivePriority: 5, targets: 4 }, 
            { responsivePriority: 6, targets: 0 }, 
        ],
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
        }
    });

    function cargarCategorias() {
        fetch('../../controllers/admin/categorias.php?action=getAllCategorias')
            .then(response => response.json())
            .then(data => {
                categoriasTableBody.innerHTML = "";
                if (data.success && data.data.length > 0) {
                    data.data.forEach(categoria => {
                        let fila = document.createElement('tr');
                        let celdaId = document.createElement('td');
                        let celdaNombre = document.createElement('td');
                        let celdaEstado = document.createElement('td');
                        let celdaAcciones = document.createElement('td');

                        celdaId.textContent = categoria.idcategorias;
                        celdaNombre.textContent = categoria.nombre_categoria;
                        celdaEstado.textContent = categoria.nombreEstado;

                        const btnEditar = document.createElement('button');
                        btnEditar.classList.add('btn', 'btn-sm', 'btn-warning', 'btnEditar');
                        btnEditar.innerHTML = '<i class="fas fa-edit"></i>';

                        const btnEliminar = document.createElement('button');
                        btnEliminar.classList.add('btn', 'btn-sm', 'btn-danger', 'btnEliminar');
                        btnEliminar.innerHTML = '<i class="fas fa-trash"></i>';

                        celdaAcciones.appendChild(btnEditar);
                        celdaAcciones.appendChild(btnEliminar);

                        fila.appendChild(celdaId);
                        fila.appendChild(celdaNombre);
                        fila.appendChild(celdaEstado);
                        fila.appendChild(celdaAcciones);

                        categoriasTableBody.appendChild(fila);
                    });
                }
            });
    }

    if (crearCategoriaBtn) {
        crearCategoriaBtn.addEventListener('click', () => {
            opcion = "crear";
            categoriaModal.show();
        });
    }

    if (categoriasTableBody) {
        categoriasTableBody.addEventListener('click', (e) => {
            if (e.target.closest(".btnEditar")) {
                const fila = e.target.closest("tr");
                categoriaId.value = fila.children[0].textContent;
                nombreCategoria.value = fila.children[1].textContent;
                opcion = "editar";
                categoriaModal.show();
            } else if (e.target.closest('.btnEliminar')) {
                const fila = e.target.closest("tr");
                const idEliminar = fila.children[0].textContent;

                Swal.fire({
                    title: "¿Seguro de eliminar la categoría " + idEliminar + "?",
                    text: "¡No se puede recuperar!",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonText: "Sí, eliminar",
                    cancelButtonText: "Cancelar"
                }).then((result) => {
                    if (result.isConfirmed) {
                        const formData = new FormData();
                        formData.append("categoriaId", idEliminar);
                        fetch("../../controllers/admin/categorias.php?action=inhabilitar_categoria", {
                            method: "POST",
                            body: formData,
                        })
                        .then((response) => response.json())
                        .then((response) => {
                            if (response.success) {
                                Swal.fire('Eliminado!', 'La categoría ha sido eliminada.', 'success');
                                cargarCategorias();
                            } else {
                                Swal.fire('Error!', data.message || 'No se pudo eliminar la categoría.', 'error');
                            }
                        });
                    }
                });
            }
        });
    }

    if (frmCategoria) {
        frmCategoria.addEventListener('submit', (e) => {
            e.preventDefault();
            const formData = new FormData();
            formData.append("nombre_categoria", nombreCategoria.value);

            if (opcion === "crear") {
                fetch('../../controllers/admin/categorias.php?action=crear_categoria', {
                    method: "POST",
                    body: formData
                })
                    .then((response) => response.json())
                    .then((data) => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Categoría creada exitosamente',
                                showConfirmButton: true,
                                timer: 1500
                            });
                            categoriaModal.hide();
                            cargarCategorias();
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error al crear categoría',
                                text: data.message
                            });
                        }
                    });
            } else if (opcion === "editar") {
                formData.append("categoriaId", categoriaId.value);
                formData.append("nombre_categoria",nombreCategoria.value);
                fetch('../../controllers/admin/categorias.php?action=editar_categoria', {
                    method: "POST",
                    body: formData
                })
                    .then((response) => response.json())
                    .then((data) => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Categoría editada exitosamente',
                                showConfirmButton: true,
                                timer: 1500
                            });
                            categoriaModal.hide();
                            cargarCategorias();
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error al editar categoría',
                                text: data.message
                            });
                        }
                    });
            }
        });
    }

    // Hacer que el botón "Guardar" dispare el submit del form
    const saveCategoria = document.querySelector('#saveCategoria');
    if (saveCategoria && frmCategoria) {
        saveCategoria.addEventListener('click', () => {
            frmCategoria.requestSubmit();
        });
    }

    cargarCategorias();
});
