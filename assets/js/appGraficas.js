// Plugin para mostrar 'Sin datos' cuando no hay datos
const noDataPlugin = {
    id: 'noData',
    afterDraw: (chart) => {
        const data = chart.data.datasets[0].data;
        if (!data || data.length === 0 || data.every(v => v === 0)) {
            const ctx = chart.ctx;
            const width = chart.width;
            const height = chart.height;
            ctx.save();
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.font = '18px sans-serif';
            ctx.fillStyle = '#888';
            ctx.fillText('Sin datos', width / 2, height / 2);
            ctx.restore();
        }
    }
};
Chart.register(noDataPlugin);

document.addEventListener('DOMContentLoaded', function() {
    // Gráfica de Barras: Ventas por Categoría
    const ventasCategoriaCanvas = document.getElementById('ventasCategoriaChart');
    let ventasCategoriaChart;
    if (ventasCategoriaCanvas) {
        const ventasCategoriaCtx = ventasCategoriaCanvas.getContext('2d');
        ventasCategoriaChart = new Chart(ventasCategoriaCtx, {
            type: 'bar',
            data: {
                labels: [],
                datasets: [{
                    label: 'Ventas ($)',
                    data: [],
                    backgroundColor: [
                        'rgba(139, 94, 60, 0.7)',
                        'rgba(75, 192, 192, 0.7)',
                        'rgba(255, 206, 86, 0.7)',
                        'rgba(153, 102, 255, 0.7)'
                    ],
                    borderColor: [
                        'rgba(139, 94, 60, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(153, 102, 255, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
    function showSwalError(msg) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: msg || 'Ocurrió un error al cargar los datos.',
            confirmButtonText: 'Aceptar'
        });
    }
    function loadVentasPorCategoria() {
        if (!ventasCategoriaChart) return;
        fetch('../../controllers/admin/graficas.php?action=get_ventas_por_categoria')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.length > 0) {
                    const labels = data.data.map(item => item.categoria);
                    const values = data.data.map(item => parseFloat(item.total_ventas));
                    ventasCategoriaChart.data.labels = labels;
                    ventasCategoriaChart.data.datasets[0].data = values;
                    ventasCategoriaChart.update();
                } else {
                    ventasCategoriaChart.data.labels = [];
                    ventasCategoriaChart.data.datasets[0].data = [];
                    ventasCategoriaChart.update();
                }
            })
            .catch(error => showSwalError('Error al cargar Ventas por Categoría.'));
    }

    // Gráfica de Pastel: Productos más Vendidos
    const productosVendidosCanvas = document.getElementById('productosVendidosChart');
    let productosVendidosChart;
    if (productosVendidosCanvas) {
        const productosVendidosCtx = productosVendidosCanvas.getContext('2d');
        productosVendidosChart = new Chart(productosVendidosCtx, {
            type: 'pie',
            data: {
                labels: [],
                datasets: [{
                    label: 'Cantidad Vendida',
                    data: [],
                    backgroundColor: [
                        'rgba(139, 94, 60, 0.7)',
                        'rgba(54, 162, 235, 0.7)',
                        'rgba(255, 99, 132, 0.7)',
                        'rgba(75, 192, 192, 0.7)',
                        'rgba(255, 206, 86, 0.7)'
                    ],
                    borderColor: [
                        'rgba(139, 94, 60, 1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 99, 132, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(255, 206, 86, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: false,
                        text: 'Productos más Vendidos'
                    }
                }
            }
        });
    }
    function loadProductosMasVendidos() {
        if (!productosVendidosChart) return;
        fetch('../../controllers/admin/graficas.php?action=get_productos_mas_vendidos')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.length > 0) {
                    const labels = data.data.map(item => item.producto);
                    const values = data.data.map(item => parseFloat(item.cantidad_vendida));
                    productosVendidosChart.data.labels = labels;
                    productosVendidosChart.data.datasets[0].data = values;
                    productosVendidosChart.update();
                } else {
                    productosVendidosChart.data.labels = [];
                    productosVendidosChart.data.datasets[0].data = [];
                    productosVendidosChart.update();
                }
            })
            .catch(error => showSwalError('Error al cargar Productos más Vendidos.'));
    }

    // Gráfica de Barras: Ingresos por Empleado
    const ingresosEmpleadoCanvas = document.getElementById('ingresosEmpleadoChart');
    let ingresosEmpleadoChart;
    if (ingresosEmpleadoCanvas) {
        const ingresosEmpleadoCtx = ingresosEmpleadoCanvas.getContext('2d');
        ingresosEmpleadoChart = new Chart(ingresosEmpleadoCtx, {
            type: 'bar',
            data: {
                labels: [],
                datasets: [{
                    label: 'Ingresos ($)',
                    data: [],
                    backgroundColor: 'rgba(54, 162, 235, 0.7)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: false,
                        text: 'Ingresos por Empleado'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
    function loadIngresosPorEmpleado() {
        if (!ingresosEmpleadoChart) return;
        fetch('../../controllers/admin/graficas.php?action=get_mesas_por_empleado')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.length > 0) {
                    const labels = data.data.map(item => item.usuarios);
                    const values = data.data.map(item => parseFloat(item.total_ingresos));
                    ingresosEmpleadoChart.data.labels = labels;
                    ingresosEmpleadoChart.data.datasets[0].data = values;
                    ingresosEmpleadoChart.update();
                } else {
                    ingresosEmpleadoChart.data.labels = [];
                    ingresosEmpleadoChart.data.datasets[0].data = [];
                    ingresosEmpleadoChart.update();
                }
            })
            .catch(error => showSwalError('Error al cargar Ingresos por Empleado.'));
    }

    // Gráfica de Barras: Mesas atendidas por Empleado
    const mesasEmpleadoCanvas = document.getElementById('mesasEmpleadoChart');
    let mesasEmpleadoChart;
    if (mesasEmpleadoCanvas) {
        const mesasEmpleadoCtx = mesasEmpleadoCanvas.getContext('2d');
        mesasEmpleadoChart = new Chart(mesasEmpleadoCtx, {
            type: 'bar',
            data: {
                labels: [],
                datasets: [{
                    label: 'Cantidad de Mesas Atendidas',
                    data: [],
                    backgroundColor: 'rgba(255, 159, 64, 0.7)',
                    borderColor: 'rgba(255, 159, 64, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: false,
                        text: 'Mesas atendidas por Empleado'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
    function loadMesasPorEmpleado() {
        if (!mesasEmpleadoChart) return;
        fetch('../../controllers/admin/graficas.php?action=get_cantidad_mesas_por_empleado')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.length > 0) {
                    const labels = data.data.map(item => item.usuario);
                    const values = data.data.map(item => parseInt(item.cantidad_mesas));
                    mesasEmpleadoChart.data.labels = labels;
                    mesasEmpleadoChart.data.datasets[0].data = values;
                    mesasEmpleadoChart.update();
                } else {
                    mesasEmpleadoChart.data.labels = [];
                    mesasEmpleadoChart.data.datasets[0].data = [];
                    mesasEmpleadoChart.update();
                }
            })
            .catch(error => showSwalError('Error al cargar Mesas atendidas por Empleado.'));
    }

    // Cargar todas las gráficas al cargar la página
    loadVentasPorCategoria();
    loadProductosMasVendidos();
    loadIngresosPorEmpleado();
    loadMesasPorEmpleado();
});
