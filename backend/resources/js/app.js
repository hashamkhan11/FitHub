import './bootstrap';
import { Html5QrcodeScanner } from 'html5-qrcode';
import { Chart } from 'chart.js/auto';

Chart.defaults.font.family = '"IBM Plex Mono", ui-monospace, Consolas, monospace';
Chart.defaults.font.size = 11;
Chart.defaults.color = '#5B6472';

document.addEventListener('alpine:init', () => {
    Alpine.data('qrScanner', () => ({
        scanner: null,

        init() {
            this.scanner = new Html5QrcodeScanner('qr-reader', { fps: 10, qrbox: 250 }, false);

            this.scanner.render((decodedText) => {
                this.scanner.pause(true);

                this.$wire.checkIn(decodedText).then(() => {
                    setTimeout(() => this.scanner.resume(), 2000);
                });
            });
        },
    }));

    Alpine.data('barChart', (labels, data, label, color, wholeNumbers = false) => ({
        chart: null,

        init() {
            const yTicks = { color: '#5B6472' };

            if (wholeNumbers) {
                // Counts of people (check-ins) can't be fractional — force integer-only
                // gridlines instead of letting Chart.js pick "nice" steps like 2.5.
                const maxValue = Math.max(1, ...data);
                yTicks.stepSize = Math.max(1, Math.ceil(maxValue / 5));
                yTicks.precision = 0;
            }

            this.chart = new Chart(this.$refs.canvas.getContext('2d'), {
                type: 'bar',
                data: {
                    labels,
                    datasets: [{ label, data, backgroundColor: color, borderRadius: 2, maxBarThickness: 28 }],
                },
                options: {
                    responsive: true,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, ticks: yTicks, grid: { color: '#E7E8E2' }, border: { display: false } },
                        x: { grid: { display: false }, border: { display: false } },
                    },
                },
            });
        },

        destroy() {
            this.chart?.destroy();
        },
    }));
});
