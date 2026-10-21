/*
========================================================================
   CIMOL BOJOT AA - HALAMAN LAPORAN PENJUALAN
   - Grafik (ApexCharts): donut Per Ukuran, pie Metode Pembayaran,
     line Tren Penjualan. Warnanya ambil dari variabel CSS tema supaya
     otomatis senada kalau warna brand diganti di cimol-theme.css.
   - Date picker Custom (flatpickr), pola sama kayak filter tanggal di
     Riwayat Transaksi.
   - Export Excel (SheetJS/xlsx) & Export PDF (jsPDF + autotable),
     library dimuat on-demand dari CDN persis kayak pola "Simpan PDF"
     struk di Riwayat Transaksi - supaya tidak nambah beban load kalau
     tombolnya tidak dipakai.
========================================================================
*/

document.addEventListener('DOMContentLoaded', function () {
    const dataEl = document.getElementById('data-laporan');
    const dataLaporan = dataEl ? JSON.parse(dataEl.textContent) : null;

    const rootStyle = getComputedStyle(document.documentElement);
    const warnaUtama = (rootStyle.getPropertyValue('--brand-forest-medium') || '#8C1F1F').trim();
    const warnaAksen = (rootStyle.getPropertyValue('--brand-lime') || '#FFD166').trim();
    const warnaTerang = (rootStyle.getPropertyValue('--brand-forest-light') || '#B23A3A').trim();
    const warnaGelap = (rootStyle.getPropertyValue('--brand-forest-dark') || '#5C1212').trim();
    const warnaMuted = (rootStyle.getPropertyValue('--text-muted-green') || '#8C7370').trim();
    const palet = [warnaUtama, warnaAksen, warnaTerang, warnaGelap];

    // -----------------------------------------------------------------
    // 1. Donut Chart: Penjualan per Ukuran
    // -----------------------------------------------------------------
    const chartUkuranEl = document.querySelector('#chart-ukuran');
    if (chartUkuranEl) {
        const labels = JSON.parse(chartUkuranEl.dataset.labels || '[]');
        const series = JSON.parse(chartUkuranEl.dataset.series || '[]');
        const total = series.reduce(function (a, b) { return a + b; }, 0);

        new ApexCharts(chartUkuranEl, {
            chart: { type: 'donut', height: 220, fontFamily: 'Plus Jakarta Sans, sans-serif' },
            series: series,
            labels: labels,
            colors: palet,
            legend: { show: false },
            dataLabels: { enabled: false },
            stroke: { width: 2, colors: ['#FFF8F3'] },
            plotOptions: {
                pie: {
                    donut: {
                        size: '68%',
                        labels: {
                            show: true,
                            total: {
                                show: true,
                                label: 'Total',
                                color: warnaMuted,
                                formatter: function () { return total + ''; },
                            },
                            value: { color: '#2B1414', fontWeight: 700 },
                        },
                    },
                },
            },
            tooltip: { y: { formatter: function (val) { return val + ' porsi'; } } },
        }).render();
    }

    // -----------------------------------------------------------------
    // 2. Pie Chart: Metode Pembayaran
    // -----------------------------------------------------------------
    const chartMetodeEl = document.querySelector('#chart-metode');
    if (chartMetodeEl) {
        const labels = JSON.parse(chartMetodeEl.dataset.labels || '[]');
        const series = JSON.parse(chartMetodeEl.dataset.series || '[]');

        new ApexCharts(chartMetodeEl, {
            chart: { type: 'pie', height: 220, fontFamily: 'Plus Jakarta Sans, sans-serif' },
            series: series,
            labels: labels,
            colors: palet,
            legend: { show: false },
            dataLabels: {
                enabled: true,
                formatter: function (val) { return Math.round(val) + '%'; },
                style: { fontSize: '11px', fontWeight: 700 },
            },
            stroke: { width: 2, colors: ['#FFF8F3'] },
            tooltip: { y: { formatter: function (val) { return val + ' transaksi'; } } },
        }).render();
    }

    // -----------------------------------------------------------------
    // 3. Line Chart: Tren Penjualan
    // -----------------------------------------------------------------
    const chartTrenEl = document.querySelector('#chart-tren');
    if (chartTrenEl) {
        const labels = JSON.parse(chartTrenEl.dataset.labels || '[]');
        const omzet = JSON.parse(chartTrenEl.dataset.omzet || '[]');

        new ApexCharts(chartTrenEl, {
            chart: { type: 'area', height: 300, toolbar: { show: false }, zoom: { enabled: false }, fontFamily: 'Plus Jakarta Sans, sans-serif' },
            series: [{ name: 'Omzet', data: omzet }],
            colors: [warnaUtama],
            stroke: { width: 3, curve: 'smooth' },
            fill: {
                type: 'gradient',
                gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.05, stops: [0, 90, 100] },
            },
            markers: { size: 4, colors: [warnaUtama], strokeColors: '#FFFFFF', strokeWidth: 2 },
            dataLabels: { enabled: false },
            grid: {
                borderColor: '#F1E1DA',
                strokeDashArray: 4,
                yaxis: { lines: { show: true } },
                xaxis: { lines: { show: false } },
            },
            xaxis: {
                categories: labels,
                labels: { style: { colors: warnaMuted, fontSize: '11px', fontWeight: 500 } },
                axisBorder: { show: false },
                axisTicks: { show: false },
            },
            yaxis: {
                labels: {
                    style: { colors: warnaMuted, fontSize: '11px' },
                    formatter: function (val) { return 'Rp ' + Math.round(val / 1000) + 'rb'; },
                },
            },
            tooltip: {
                y: { formatter: function (val) { return 'Rp ' + Number(val).toLocaleString('id-ID'); } },
            },
        }).render();
    }

    // -----------------------------------------------------------------
    // 4. Flatpickr untuk filter tanggal Custom
    // -----------------------------------------------------------------
    if (document.getElementById('filter-tanggal-mulai')) {
        flatpickr('#filter-tanggal-mulai', { dateFormat: 'Y-m-d', altInput: true, altFormat: 'j M Y', allowInput: true });
    }
    if (document.getElementById('filter-tanggal-akhir')) {
        flatpickr('#filter-tanggal-akhir', { dateFormat: 'Y-m-d', altInput: true, altFormat: 'j M Y', allowInput: true });
    }

    // -----------------------------------------------------------------
    // 5. Export Excel (SheetJS / xlsx), dimuat on-demand dari CDN
    // -----------------------------------------------------------------
    let xlsxPromise = null;
    function muatXlsx() {
        if (window.XLSX) return Promise.resolve();
        if (xlsxPromise) return xlsxPromise;

        xlsxPromise = new Promise(function (resolve, reject) {
            const script = document.createElement('script');
            script.src = 'https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js';
            script.onload = resolve;
            script.onerror = reject;
            document.head.appendChild(script);
        });

        return xlsxPromise;
    }

    function formatRupiah(angka) {
        return 'Rp ' + Number(angka).toLocaleString('id-ID');
    }

    function siapkanSheetData(trx) {
        const rows = [];
        rows.push(['LAPORAN PENJUALAN - ' + trx.toko.toUpperCase()]);
        rows.push(['Periode: ' + trx.tanggal_mulai + ' - ' + trx.tanggal_akhir + '  |  Jenis: ' + trx.periode]);
        rows.push(['Tanggal Export: ' + trx.tanggal_export]);
        rows.push([]);
        rows.push(['Total Omzet', 'Jumlah Transaksi', 'Rata-rata per Transaksi']);
        rows.push([formatRupiah(trx.total_omzet), trx.total_transaksi, formatRupiah(trx.rata_rata)]);
        rows.push([]);

        rows.push(['Penjualan per Varian Menu']);
        rows.push(['No', 'Nama Menu', 'Jumlah Terjual', 'Total Omzet', 'Persentase']);
        trx.per_varian.forEach(function (item, i) {
            rows.push([i + 1, item.nama, item.qty, formatRupiah(item.omzet), item.persentase + '%']);
        });
        rows.push([]);

        rows.push(['Penjualan per Ukuran']);
        rows.push(['Ukuran', 'Jumlah Terjual', 'Persentase']);
        trx.per_ukuran.forEach(function (item) {
            rows.push([item.nama, item.qty, item.persentase + '%']);
        });
        rows.push([]);

        rows.push(['Metode Pembayaran']);
        rows.push(['Metode', 'Jumlah', 'Persentase']);
        trx.metode_bayar.forEach(function (item) {
            rows.push([item.nama, item.jumlah, item.persentase + '%']);
        });

        return rows;
    }

    function exportExcel() {
        if (!dataLaporan) return;
        const btn = document.getElementById('btn-export-excel');
        const teksAsli = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Menyiapkan...';

        muatXlsx()
            .then(function () {
                const rows = siapkanSheetData(dataLaporan);
                const ws = XLSX.utils.aoa_to_sheet(rows);
                ws['!cols'] = [{ wch: 26 }, { wch: 20 }, { wch: 18 }, { wch: 16 }, { wch: 12 }];
                ws['!merges'] = [
                    { s: { r: 0, c: 0 }, e: { r: 0, c: 4 } },
                    { s: { r: 1, c: 0 }, e: { r: 1, c: 4 } },
                    { s: { r: 2, c: 0 }, e: { r: 2, c: 4 } },
                ];

                const wb = XLSX.utils.book_new();
                XLSX.utils.book_append_sheet(wb, ws, 'Laporan Penjualan');

                const namaFile = 'Laporan-Penjualan-' + dataLaporan.tanggal_mulai.replace(/ /g, '') + '-' + dataLaporan.tanggal_akhir.replace(/ /g, '') + '.xlsx';
                XLSX.writeFile(wb, namaFile);
            })
            .catch(function () {
                alert('Gagal membuat file Excel. Pastikan koneksi internet aktif lalu coba lagi.');
            })
            .finally(function () {
                btn.disabled = false;
                btn.innerHTML = teksAsli;
            });
    }

    // -----------------------------------------------------------------
    // 6. Export PDF (jsPDF + autotable), dimuat on-demand dari CDN
    // -----------------------------------------------------------------
    let jsPdfPromise = null;
    function muatJsPdf() {
        if (window.jspdf && window.jspdf.jsPDF && window.jspdf.jsPDF.API && window.jspdf.jsPDF.API.autoTable) {
            return Promise.resolve();
        }
        if (jsPdfPromise) return jsPdfPromise;

        jsPdfPromise = new Promise(function (resolve, reject) {
            const s1 = document.createElement('script');
            s1.src = 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js';
            s1.onload = function () {
                const s2 = document.createElement('script');
                s2.src = 'https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js';
                s2.onload = resolve;
                s2.onerror = reject;
                document.head.appendChild(s2);
            };
            s1.onerror = reject;
            document.head.appendChild(s1);
        });

        return jsPdfPromise;
    }

    function exportPdf() {
        if (!dataLaporan) return;
        const btn = document.getElementById('btn-export-pdf');
        const teksAsli = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Menyiapkan...';

        muatJsPdf()
            .then(function () {
                const trx = dataLaporan;
                const { jsPDF } = window.jspdf;
                const doc = new jsPDF({ unit: 'mm', format: 'a4' });
                const lebarHalaman = doc.internal.pageSize.getWidth();
                const merahUtama = [140, 31, 31];
                const emas = [255, 209, 102];

                // Header
                doc.setFillColor(merahUtama[0], merahUtama[1], merahUtama[2]);
                doc.rect(0, 0, lebarHalaman, 28, 'F');
                doc.setTextColor(255, 255, 255);
                doc.setFontSize(16);
                doc.setFont(undefined, 'bold');
                doc.text('LAPORAN PENJUALAN', 14, 13);
                doc.setFontSize(11);
                doc.setFont(undefined, 'normal');
                doc.text(trx.toko, 14, 20);
                doc.setFontSize(8.5);
                doc.text('Periode : ' + trx.tanggal_mulai + ' - ' + trx.tanggal_akhir, lebarHalaman - 14, 11, { align: 'right' });
                doc.text('Jenis   : ' + trx.periode, lebarHalaman - 14, 16, { align: 'right' });
                doc.text('Export  : ' + trx.tanggal_export, lebarHalaman - 14, 21, { align: 'right' });

                // Kartu ringkasan
                let y = 36;
                const kartu = [
                    ['Total Omzet', formatRupiah(trx.total_omzet)],
                    ['Jumlah Transaksi', String(trx.total_transaksi)],
                    ['Rata-rata per Transaksi', formatRupiah(trx.rata_rata)],
                ];
                const lebarKartu = (lebarHalaman - 28 - 12) / 3;
                kartu.forEach(function (k, i) {
                    const x = 14 + i * (lebarKartu + 6);
                    doc.setDrawColor(230, 210, 200);
                    doc.setFillColor(251, 243, 239);
                    doc.roundedRect(x, y, lebarKartu, 20, 2, 2, 'FD');
                    doc.setTextColor(140, 115, 112);
                    doc.setFontSize(8);
                    doc.setFont(undefined, 'normal');
                    doc.text(k[0], x + 4, y + 7);
                    doc.setTextColor(43, 20, 20);
                    doc.setFontSize(12);
                    doc.setFont(undefined, 'bold');
                    doc.text(k[1], x + 4, y + 15);
                });

                y += 28;

                doc.setTextColor(43, 20, 20);
                doc.setFontSize(11);
                doc.setFont(undefined, 'bold');
                doc.text('Penjualan per Varian Menu', 14, y);

                doc.autoTable({
                    startY: y + 3,
                    head: [['No', 'Nama Menu', 'Jumlah Terjual', 'Total Omzet', '%']],
                    body: trx.per_varian.map(function (item, i) {
                        return [i + 1, item.nama, item.qty, formatRupiah(item.omzet), item.persentase + '%'];
                    }),
                    theme: 'striped',
                    headStyles: { fillColor: merahUtama, textColor: 255, fontSize: 9 },
                    bodyStyles: { fontSize: 8.5 },
                    margin: { left: 14, right: 14 },
                });

                y = doc.lastAutoTable.finalY + 10;
                if (y > 250) { doc.addPage(); y = 16; }

                const yTabelKedua = y;
                doc.setFontSize(11);
                doc.setFont(undefined, 'bold');
                doc.text('Penjualan per Ukuran', 14, yTabelKedua);
                doc.autoTable({
                    startY: yTabelKedua + 3,
                    head: [['Ukuran', 'Jumlah Terjual', '%']],
                    body: trx.per_ukuran.map(function (item) { return [item.nama, item.qty, item.persentase + '%']; }),
                    theme: 'striped',
                    headStyles: { fillColor: merahUtama, textColor: 255, fontSize: 9 },
                    bodyStyles: { fontSize: 8.5 },
                    margin: { left: 14, right: (lebarHalaman / 2) },
                    tableWidth: (lebarHalaman / 2) - 20,
                });
                const finalYKiri = doc.lastAutoTable.finalY;

                doc.setFontSize(11);
                doc.setFont(undefined, 'bold');
                doc.text('Metode Pembayaran', (lebarHalaman / 2) + 4, yTabelKedua);
                doc.autoTable({
                    startY: yTabelKedua + 3,
                    head: [['Metode', 'Jumlah', '%']],
                    body: trx.metode_bayar.map(function (item) { return [item.nama, item.jumlah, item.persentase + '%']; }),
                    theme: 'striped',
                    headStyles: { fillColor: merahUtama, textColor: 255, fontSize: 9 },
                    bodyStyles: { fontSize: 8.5 },
                    margin: { left: (lebarHalaman / 2) + 4, right: 14 },
                    tableWidth: (lebarHalaman / 2) - 20,
                });
                const finalYKanan = doc.lastAutoTable.finalY;

                const yFooter = Math.max(finalYKiri, finalYKanan) + 12;
                doc.setDrawColor(230, 210, 200);
                doc.line(14, yFooter, lebarHalaman - 14, yFooter);
                doc.setTextColor(140, 115, 112);
                doc.setFontSize(8);
                doc.setFont(undefined, 'italic');
                doc.text(trx.toko + ' - Lezat, Gurih, Bikin Nagih!', lebarHalaman / 2, yFooter + 6, { align: 'center' });

                doc.save('Laporan-Penjualan-' + trx.tanggal_mulai.replace(/ /g, '') + '-' + trx.tanggal_akhir.replace(/ /g, '') + '.pdf');
            })
            .catch(function () {
                alert('Gagal membuat file PDF. Pastikan koneksi internet aktif lalu coba lagi.');
            })
            .finally(function () {
                btn.disabled = false;
                btn.innerHTML = teksAsli;
            });
    }

    const btnExcel = document.getElementById('btn-export-excel');
    if (btnExcel) btnExcel.addEventListener('click', exportExcel);

    const btnPdf = document.getElementById('btn-export-pdf');
    if (btnPdf) btnPdf.addEventListener('click', exportPdf);
});
