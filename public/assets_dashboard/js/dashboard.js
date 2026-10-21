/* 
========================================================================
   CIMOL BOJOT AA - DASHBOARD CORE JAVASCRIPT MODULE
   Berbasis Spark Admin dashboard.js, dengan:
   - Grafik & date picker diganti ke versi Cimol Bojot AA
   - Sidebar overlay, fullscreen toggle, desktop minimize: TETAP dipakai
     dari file aslinya (tidak diubah logikanya)
========================================================================
*/

document.addEventListener('DOMContentLoaded', function () {
    // -----------------------------------------------------------------
    // 1. Mobile Sidebar Toggle & Backdrop Overlay
    // -----------------------------------------------------------------
    const sidebar = document.querySelector('.sidebar-wrapper');
    const toggleBtn = document.querySelector('.sidebar-toggle-btn');

    let overlay = document.createElement('div');
    overlay.className = 'sidebar-overlay';
    document.body.appendChild(overlay);

    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            sidebar.classList.toggle('show');
            overlay.classList.toggle('show', sidebar.classList.contains('show'));
        });

        overlay.addEventListener('click', function () {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
        });
    }

    // -----------------------------------------------------------------
    // 2. Grafik Penjualan 7 Hari Terakhir (Bar Omzet + Line Transaksi)
    // -----------------------------------------------------------------
    const revenueChartEl = document.querySelector('#revenue-chart');
    if (revenueChartEl) {
        const labels = JSON.parse(revenueChartEl.dataset.labels || '[]');
        const omzetData = JSON.parse(revenueChartEl.dataset.omzet || '[]');
        const transaksiData = JSON.parse(revenueChartEl.dataset.transaksi || '[]');

        const rootStyle = getComputedStyle(document.documentElement);
        const colorOmzet = (rootStyle.getPropertyValue('--brand-forest-medium') || '#8C1F1F').trim();
        const colorTransaksi = (rootStyle.getPropertyValue('--brand-lime') || '#FFD166').trim();
        const mutedColor = (rootStyle.getPropertyValue('--text-muted-green') || '#8C7370').trim();

        const revenueChartOptions = {
            chart: {
                type: 'line',
                height: 320,
                toolbar: { show: false },
                zoom: { enabled: false },
                fontFamily: 'Plus Jakarta Sans, sans-serif'
            },
            series: [
                { name: 'Omzet', type: 'column', data: omzetData },
                { name: 'Jumlah Transaksi', type: 'line', data: transaksiData }
            ],
            colors: [colorOmzet, colorTransaksi],
            stroke: { width: [0, 3], curve: 'smooth' },
            states: {
                hover: { filter: { type: 'none' } }
            },
            plotOptions: {
                bar: { horizontal: false, columnWidth: '45%', borderRadius: 6, borderRadiusApplication: 'end' }
            },
            markers: { size: 4, colors: [colorTransaksi], strokeColors: '#FFFFFF', strokeWidth: 2 },
            dataLabels: { enabled: false },
            legend: { show: false }, // legend statis sudah digambar di HTML
            grid: {
                borderColor: '#F1E1DA',
                strokeDashArray: 4,
                yaxis: { lines: { show: true } },
                xaxis: { lines: { show: false } },
                padding: { top: 0, right: 0, bottom: 0, left: 0 }
            },
            xaxis: {
                categories: labels,
                labels: { style: { colors: mutedColor, fontSize: '11px', fontWeight: 500 } },
                axisBorder: { show: false },
                axisTicks: { show: false }
            },
            yaxis: [
                {
                    seriesName: 'Omzet',
                    labels: {
                        style: { colors: mutedColor, fontSize: '11px' },
                        formatter: function (val) {
                            return 'Rp ' + Math.round(val / 1000) + 'rb';
                        }
                    }
                },
                {
                    seriesName: 'Jumlah Transaksi',
                    opposite: true,
                    labels: { style: { colors: mutedColor, fontSize: '11px' } }
                }
            ],
            fill: { opacity: 1 },
            tooltip: {
                shared: true,
                intersect: false,
                theme: 'dark',
                y: {
                    formatter: function (val, opts) {
                        if (opts && opts.seriesIndex === 0) {
                            return 'Rp ' + Number(val).toLocaleString('id-ID');
                        }
                        return val + ' transaksi';
                    }
                }
            }
        };

        const revenueChart = new ApexCharts(revenueChartEl, revenueChartOptions);
        revenueChart.render();
    }

    // -----------------------------------------------------------------
    // 3. Flatpickr Date Range Picker
    // -----------------------------------------------------------------
    const datePickerTrigger = document.querySelector('#date-picker-trigger');
    const selectedRangeText = document.querySelector('#selected-date-range');

    if (datePickerTrigger && selectedRangeText) {
        flatpickr(datePickerTrigger, {
            mode: 'range',
            dateFormat: 'Y-m-d',
            defaultDate: [new Date(), new Date()],
            onValueUpdate: function (selectedDates, dateStr, instance) {
                if (selectedDates.length === 2) {
                    const startStr = instance.formatDate(selectedDates[0], 'j M Y');
                    const endStr = instance.formatDate(selectedDates[1], 'j M Y');
                    selectedRangeText.textContent = `${startStr} - ${endStr}`;
                } else if (selectedDates.length === 1) {
                    selectedRangeText.textContent = instance.formatDate(selectedDates[0], 'j M Y');
                }
            }
        });
    }

    // -----------------------------------------------------------------
    // 4. Desktop Sidebar Minimize Interaction
    // -----------------------------------------------------------------
    const desktopToggleBtn = document.querySelector('#desktop-sidebar-toggle');
    if (desktopToggleBtn) {
        desktopToggleBtn.addEventListener('click', function () {
            document.body.classList.toggle('sidebar-minimized');

            const icon = desktopToggleBtn.querySelector('i');
            if (icon) {
                if (document.body.classList.contains('sidebar-minimized')) {
                    icon.className = 'bi bi-chevron-bar-right';
                } else {
                    icon.className = 'bi bi-chevron-bar-left';
                }
            }

            // Trigger resize supaya ApexCharts redraw sesuai lebar baru
            setTimeout(() => {
                window.dispatchEvent(new Event('resize'));
            }, 300);
        });
    }

    // -----------------------------------------------------------------
    // 5. Fullscreen Toggle Interaction
    // -----------------------------------------------------------------
    const fullscreenBtn = document.querySelector('#btn-fullscreen');
    if (fullscreenBtn) {
        fullscreenBtn.addEventListener('click', function () {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().then(() => {
                    updateFullscreenIcon(true);
                }).catch(err => {
                    console.error(`Error attempting to enable fullscreen mode: ${err.message}`);
                });
            } else {
                document.exitFullscreen().then(() => {
                    updateFullscreenIcon(false);
                }).catch(err => {
                    console.error(`Error attempting to exit fullscreen mode: ${err.message}`);
                });
            }
        });

        function updateFullscreenIcon(isFullscreen) {
            const icon = fullscreenBtn.querySelector('i');
            if (icon) {
                icon.className = isFullscreen ? 'bi bi-fullscreen-exit' : 'bi bi-arrows-fullscreen';
            }
        }

        document.addEventListener('fullscreenchange', () => {
            updateFullscreenIcon(!!document.fullscreenElement);
        });
    }
});