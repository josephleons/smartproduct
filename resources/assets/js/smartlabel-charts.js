/**
 * Bar / line charts for the dashboard and analytics pages.
 * Each chart container carries its data in a data-chart JSON attribute.
 */
document.addEventListener('DOMContentLoaded', () => {
  const primary = '#0F6680';

  const render = (id, type) => {
    const el = document.getElementById(id);
    if (!el || typeof ApexCharts === 'undefined') return;
    const data = JSON.parse(el.dataset.chart);

    new ApexCharts(el, {
      chart: { type, height: 280, toolbar: { show: false }, fontFamily: 'inherit' },
      series: [{ name: data.name, data: data.series }],
      xaxis: { categories: data.labels, labels: { rotate: -45, hideOverlappingLabels: true } },
      yaxis: { min: 0, forceNiceScale: true, labels: { formatter: v => Math.round(v) } },
      colors: [primary],
      dataLabels: { enabled: false },
      stroke: { curve: 'smooth', width: type === 'line' ? 3 : 0 },
      plotOptions: { bar: { borderRadius: 4, columnWidth: '45%', horizontal: el.dataset.horizontal === 'true' } },
      grid: { borderColor: 'rgba(0,0,0,.06)' }
    }).render();
  };

  render('scansChart', 'bar');
  render('dailyChart', 'line');
  render('branchChart', 'bar');
});
