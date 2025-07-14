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

// Función para generar un color rgba aleatorio
function randomColor(alpha = 0.7) {
    const r = Math.floor(Math.random() * 256);
    const g = Math.floor(Math.random() * 256);
    const b = Math.floor(Math.random() * 256);
    return `rgba(${r}, ${g}, ${b}, ${alpha})`;
}

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
                    const bgColors = values.map(() => randomColor(0.7));
                    const borderColors = bgColors.map(c => c.replace('0.7', '1'));
                    ventasCategoriaChart.data.labels = labels;
                    ventasCategoriaChart.data.datasets[0].data = values;
                    ventasCategoriaChart.data.datasets[0].backgroundColor = bgColors;
                    ventasCategoriaChart.data.datasets[0].borderColor = borderColors;
                    ventasCategoriaChart.update();
                } else {
                    ventasCategoriaChart.data.labels = [];
                    ventasCategoriaChart.data.datasets[0].data = [];
                    ventasCategoriaChart.data.datasets[0].backgroundColor = [];
                    ventasCategoriaChart.data.datasets[0].borderColor = [];
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
            type: 'bar',
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
                    const bgColors = values.map(() => randomColor(0.7));
                    const borderColors = bgColors.map(c => c.replace('0.7', '1'));
                    productosVendidosChart.data.labels = labels;
                    productosVendidosChart.data.datasets[0].data = values;
                    productosVendidosChart.data.datasets[0].backgroundColor = bgColors;
                    productosVendidosChart.data.datasets[0].borderColor = borderColors;
                    productosVendidosChart.update();
                } else {
                    productosVendidosChart.data.labels = [];
                    productosVendidosChart.data.datasets[0].data = [];
                    productosVendidosChart.data.datasets[0].backgroundColor = [];
                    productosVendidosChart.data.datasets[0].borderColor = [];
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
                    const bgColors = values.map(() => randomColor(0.7));
                    const borderColors = bgColors.map(c => c.replace('0.7', '1'));
                    ingresosEmpleadoChart.data.labels = labels;
                    ingresosEmpleadoChart.data.datasets[0].data = values;
                    ingresosEmpleadoChart.data.datasets[0].backgroundColor = bgColors;
                    ingresosEmpleadoChart.data.datasets[0].borderColor = borderColors;
                    ingresosEmpleadoChart.update();
                } else {
                    ingresosEmpleadoChart.data.labels = [];
                    ingresosEmpleadoChart.data.datasets[0].data = [];
                    ingresosEmpleadoChart.data.datasets[0].backgroundColor = [];
                    ingresosEmpleadoChart.data.datasets[0].borderColor = [];
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
                    const bgColors = values.map(() => randomColor(0.7));
                    const borderColors = bgColors.map(c => c.replace('0.7', '1'));
                    mesasEmpleadoChart.data.labels = labels;
                    mesasEmpleadoChart.data.datasets[0].data = values;
                    mesasEmpleadoChart.data.datasets[0].backgroundColor = bgColors;
                    mesasEmpleadoChart.data.datasets[0].borderColor = borderColors;
                    mesasEmpleadoChart.update();
                } else {
                    mesasEmpleadoChart.data.labels = [];
                    mesasEmpleadoChart.data.datasets[0].data = [];
                    mesasEmpleadoChart.data.datasets[0].backgroundColor = [];
                    mesasEmpleadoChart.data.datasets[0].borderColor = [];
                    mesasEmpleadoChart.update();
                }
            })
            .catch(error => showSwalError('Error al cargar Mesas atendidas por Empleado.'));
    }

    // Gráfica de Barras: Recaudo por Mes
    const recaudoMesCanvas = document.getElementById('recaudoMesChart');
    let recaudoMesChart;
    if (recaudoMesCanvas) {
        const recaudoMesCtx = recaudoMesCanvas.getContext('2d');
        recaudoMesChart = new Chart(recaudoMesCtx, {
            type: 'bar',
            data: {
                labels: [],
                datasets: [{
                    label: 'Recaudo ($)',
                    data: [],
                    backgroundColor: [],
                    borderColor: [],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: false,
                        text: 'Recaudo por Mes'
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
    function loadRecaudoPorMes() {
        if (!recaudoMesChart) return;
        fetch('../../controllers/admin/graficas.php?action=get_recaudo_por_mes')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.length > 0) {
                    const labels = data.data.map(item => item.mes);
                    const values = data.data.map(item => parseFloat(item.total_recaudo));
                    const bgColors = values.map(() => randomColor(0.7));
                    const borderColors = bgColors.map(c => c.replace('0.7', '1'));
                    recaudoMesChart.data.labels = labels;
                    recaudoMesChart.data.datasets[0].data = values;
                    recaudoMesChart.data.datasets[0].backgroundColor = bgColors;
                    recaudoMesChart.data.datasets[0].borderColor = borderColors;
                    recaudoMesChart.update();
                } else {
                    recaudoMesChart.data.labels = [];
                    recaudoMesChart.data.datasets[0].data = [];
                    recaudoMesChart.data.datasets[0].backgroundColor = [];
                    recaudoMesChart.data.datasets[0].borderColor = [];
                    recaudoMesChart.update();
                }
            })
            .catch(error => showSwalError('Error al cargar Recaudo por Mes.'));
    }

    // Cargar todas las gráficas al cargar la página
    loadVentasPorCategoria();
    loadRecaudoPorMes();
    loadProductosMasVendidos();
    loadIngresosPorEmpleado();
    loadMesasPorEmpleado();
});
