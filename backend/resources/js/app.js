import './bootstrap';
import { Html5QrcodeScanner } from 'html5-qrcode';
import { Chart } from 'chart.js/auto';

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

    Alpine.data('progressChart', (measurements) => ({
        chart: null,

        init() {
            this.chart = new Chart(this.$refs.canvas.getContext('2d'), {
                type: 'line',
                data: {
                    labels: measurements.map((m) => m.recorded_at),
                    datasets: [
                        {
                            label: 'Weight (kg)',
                            data: measurements.map((m) => m.weight_kg),
                            borderColor: '#2563eb',
                            tension: 0.3,
                        },
                        {
                            label: 'Body fat (%)',
                            data: measurements.map((m) => m.body_fat_percentage),
                            borderColor: '#dc2626',
                            tension: 0.3,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    scales: { y: { beginAtZero: false } },
                },
            });
        },

        destroy() {
            this.chart?.destroy();
        },
    }));
});
