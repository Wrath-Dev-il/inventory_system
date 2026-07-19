document.addEventListener('DOMContentLoaded', function () {
    const tabs = document.querySelectorAll('[data-stock-tab]');
    const panels = document.querySelectorAll('[data-stock-panel]');

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            const target = tab.getAttribute('data-stock-tab');

            tabs.forEach(function (item) {
                const active = item === tab;
                item.classList.toggle('is-active', active);
                item.setAttribute('aria-selected', active ? 'true' : 'false');
            });

            panels.forEach(function (panel) {
                const active = panel.getAttribute('data-stock-panel') === target;
                panel.classList.toggle('is-active', active);
                panel.hidden = !active;
            });
        });
    });

    document.querySelectorAll('[data-stock-search-form]').forEach(function (form) {
        let searchTimer = null;
        const inputs = form.querySelectorAll('input[type="search"]');

        inputs.forEach(function (input) {
            input.addEventListener('input', function () {
                window.clearTimeout(searchTimer);

                searchTimer = window.setTimeout(function () {
                    form.requestSubmit();
                }, 320);
            });
        });
    });

    const chartDataElement = document.getElementById('qtyDistributionData');
    const chartCanvas = document.getElementById('qtyDistributionChart');
    const emptyState = document.getElementById('qtyDistributionEmptyState');
    const fallbackDonut = document.getElementById('qtyDistributionFallback');

    if (!chartDataElement || !chartCanvas) {
        return;
    }

    const palette = [
        '#071a3d',
        '#facc15',
        '#6c0622',
        '#2563eb',
        '#16a34a',
        '#f97316',
        '#7c3aed',
        '#0f766e',
        '#dc2626',
        '#64748b',
    ];

    function formatQty(value) {
        return Number(value || 0).toLocaleString(undefined, {
            maximumFractionDigits: 2,
        });
    }

    function renderFallbackDonut(labels, values, total) {
        if (!fallbackDonut) {
            return;
        }

        const graphic = fallbackDonut.querySelector('[data-brand-donut-graphic]');
        const totalElement = fallbackDonut.querySelector('[data-brand-donut-total]');
        const legend = fallbackDonut.querySelector('[data-brand-donut-legend]');
        let currentAngle = 0;

        const slices = values.map(function (value, index) {
            const start = currentAngle;
            const end = start + ((value / total) * 360);
            currentAngle = end;

            return `${palette[index % palette.length]} ${start}deg ${end}deg`;
        });

        if (graphic) {
            graphic.style.background = `conic-gradient(${slices.join(', ')})`;
        }

        if (totalElement) {
            totalElement.textContent = formatQty(total);
        }

        if (legend) {
            legend.replaceChildren();

            labels.forEach(function (label, index) {
                const value = values[index] || 0;
                const percent = total > 0 ? ((value / total) * 100).toFixed(2) : '0.00';
                const item = document.createElement('div');
                const swatch = document.createElement('span');
                const name = document.createElement('span');
                const quantity = document.createElement('strong');
                const percentage = document.createElement('em');

                item.className = 'brand-donut__legend-item';
                swatch.className = 'brand-donut__swatch';
                swatch.style.background = palette[index % palette.length];
                name.className = 'brand-donut__name';
                name.textContent = label;
                quantity.textContent = formatQty(value);
                percentage.textContent = `${percent}%`;

                item.append(swatch, name, quantity, percentage);
                legend.appendChild(item);
            });
        }

        fallbackDonut.hidden = false;
        chartCanvas.hidden = true;
    }

    function initChart() {
        let payload = { labels: [], values: [], total: 0 };

        try {
            payload = JSON.parse(chartDataElement.textContent || '{}');
        } catch (error) {
            payload = { labels: [], values: [] };
        }

        const labels = Array.isArray(payload.labels) ? payload.labels : [];
        const values = Array.isArray(payload.values) ? payload.values.map(Number) : [];
        const total = values.reduce(function (sum, value) {
            return sum + value;
        }, 0);

        if (total <= 0) {
            if (emptyState) {
                emptyState.hidden = false;
            }

            chartCanvas.hidden = true;
            return;
        }

        if (emptyState) {
            emptyState.hidden = true;
        }

        if (!window.Chart) {
            renderFallbackDonut(labels, values, total);
            return;
        }

        chartCanvas.hidden = true;
        if (fallbackDonut) {
            fallbackDonut.hidden = false;
        }

        if (chartCanvas._adminQtyChart) {
            chartCanvas._adminQtyChart.destroy();
        }

        const centerTotalPlugin = {
            id: 'adminQtyCenterTotal',
            afterDraw: function (chart) {
                const ctx = chart.ctx;
                const centerX = (chart.chartArea.left + chart.chartArea.right) / 2;
                const centerY = (chart.chartArea.top + chart.chartArea.bottom) / 2;

                ctx.save();
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillStyle = '#667085';
                ctx.font = '700 12px Arial, Helvetica, sans-serif';
                ctx.fillText('Total QTY', centerX, centerY - 10);
                ctx.fillStyle = '#111827';
                ctx.font = '900 18px Arial, Helvetica, sans-serif';
                ctx.fillText(formatQty(total), centerX, centerY + 12);
                ctx.restore();
            },
        };

        try {
            chartCanvas._adminQtyChart = new window.Chart(chartCanvas, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: values,
                        backgroundColor: values.map(function (_, index) {
                            return palette[index % palette.length];
                        }),
                        borderColor: '#ffffff',
                        borderWidth: 2,
                        hoverOffset: 6,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '64%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 12,
                                color: '#475467',
                                font: {
                                    weight: '700',
                                },
                            },
                        },
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    const value = Number(context.parsed || 0);
                                    const percentage = total > 0 ? ((value / total) * 100).toFixed(2) : '0.00';

                                    return [
                                        context.label,
                                        'Available QTY: ' + value.toLocaleString(),
                                        percentage + '% of Total QTY',
                                    ];
                                },
                            },
                        },
                    },
                },
                plugins: [centerTotalPlugin],
            });

            chartCanvas.hidden = false;
            if (fallbackDonut) {
                fallbackDonut.hidden = true;
            }
        } catch (error) {
            renderFallbackDonut(labels, values, total);
        }
    }

    if (window.Chart) {
        initChart();
    } else {
        window.addEventListener('load', initChart, { once: true });
    }
});
