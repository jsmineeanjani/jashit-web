document.addEventListener('DOMContentLoaded', function () {

    if (typeof window.dashboardChartData === 'undefined') return;

    const { labelMonth, dataMonth, labelYear, dataYear } = window.dashboardChartData;

    function rpFormat(val) {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val);
    }

    // ===== GRAFIK 1: PER BULAN (LINE) =====
    const elMonth = document.getElementById('chartMonth');
    if (elMonth) {
        const ctxMonth = elMonth.getContext('2d');
        const gradMonth = ctxMonth.createLinearGradient(0, 0, 0, 300);
        gradMonth.addColorStop(0, 'rgba(99, 179, 237, 0.25)');
        gradMonth.addColorStop(1, 'rgba(99, 179, 237, 0)');

        new Chart(ctxMonth, {
            type: 'line',
            data: {
                labels: labelMonth,
                datasets: [{
                    label: 'Pendapatan',
                    data: dataMonth,
                    borderColor: '#38bdf8',
                    backgroundColor: gradMonth,
                    borderWidth: 2.5,
                    pointRadius: 4,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#38bdf8',
                    pointBorderWidth: 2,
                    pointHoverRadius: 6,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom',
                        labels: { color: '#94a3b8', font: { size: 11 }, boxWidth: 12, usePointStyle: true, pointStyle: 'line' }
                    },
                    tooltip: {
                        callbacks: {
                            label: ctx => ' ' + rpFormat(ctx.parsed.y)
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: '#f1f5f9', borderDash: [4, 4] },
                        ticks: {
                            color: '#94a3b8',
                            font: { size: 11 },
                            callback: val => val >= 1000000
                                ? 'Rp ' + (val / 1000000).toFixed(1) + 'M'
                                : 'Rp ' + (val / 1000).toFixed(0) + 'K'
                        }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { color: '#94a3b8', font: { size: 11 } }
                    }
                }
            }
        });
    }

    // ===== GRAFIK 2: PER TAHUN (BAR) =====
    const elYear = document.getElementById('chartYear');
    if (elYear) {
        const ctxYear = elYear.getContext('2d');
        new Chart(ctxYear, {
            type: 'bar',
            data: {
                labels: labelYear,
                datasets: [{
                    label: 'total',
                    data: dataYear,
                    backgroundColor: 'rgba(129, 118, 194, 0.75)',
                    borderColor: 'rgba(129, 118, 194, 1)',
                    borderWidth: 1,
                    borderRadius: 6,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom',
                        labels: { color: '#94a3b8', font: { size: 11 }, boxWidth: 12 }
                    },
                    tooltip: {
                        callbacks: {
                            label: ctx => ' ' + rpFormat(ctx.parsed.y)
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: '#f1f5f9', borderDash: [4, 4] },
                        ticks: {
                            color: '#94a3b8',
                            font: { size: 11 },
                            callback: val => val >= 1000000
                                ? 'Rp ' + (val / 1000000).toFixed(1) + 'M'
                                : 'Rp ' + (val / 1000).toFixed(0) + 'K'
                        }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { color: '#94a3b8', font: { size: 11 } }
                    }
                }
            }
        });
    }
});