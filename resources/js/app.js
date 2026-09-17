import * as echarts from 'echarts';
import 'flowbite';
import '../css/app.css';

window.echarts = echarts;

function setChartState(elementId, state) {
    const shell = document.querySelector(`[data-chart="${elementId}"]`);
    if (!shell) return;
    shell.dataset.state = state;
}

function makeChart(elementId, options, hasData) {
    const element = document.getElementById(elementId);
    if (!element) return;
    if (!hasData) {
        setChartState(elementId, 'empty');
        return;
    }

    setChartState(elementId, 'ready');
    const chart = echarts.init(element);
    chart.setOption(options);
    const resize = () => chart.resize();
    window.addEventListener('resize', resize, { passive: true });
    if (window.ResizeObserver) new ResizeObserver(resize).observe(element);
}

function initDashboardCharts() {
    const data = window.IRISChartData;
    if (!data) return;

    setChartState('trendChart', 'loading');
    setChartState('collegeChart', 'loading');
    data.breakdownSections.forEach((_, index) => setChartState(`breakdownChart${index}`, 'loading'));

    makeChart('trendChart', {
        animationDuration: 650,
        grid: { left: 42, right: 18, top: 18, bottom: 34 },
        tooltip: {
            trigger: 'axis',
            valueFormatter: (value, index) => `Rank: ${data.trendDisplay[index] ?? '—'}`,
        },
        xAxis: { type: 'category', data: data.trendYears, boundaryGap: false },
        yAxis: { type: 'value', inverse: true, name: 'Lower = better', nameLocation: 'middle', nameGap: 30 },
        series: [{
            name: 'Global Rank',
            type: 'line',
            data: data.trendRanks,
            smooth: true,
            connectNulls: true,
            symbolSize: 8,
            lineStyle: { width: 3, color: '#1e40af' },
            itemStyle: { color: '#1e40af' },
            areaStyle: { color: 'rgba(59, 130, 246, 0.12)' },
        }],
    }, data.trendRanks.some((value) => value !== null));

    makeChart('collegeChart', {
        animationDuration: 650,
        tooltip: { trigger: 'item', formatter: '{b}<br/><strong>{c}%</strong>' },
        series: [{
            type: 'pie',
            radius: ['48%', '76%'],
            center: ['50%', '52%'],
            avoidLabelOverlap: true,
            itemStyle: { borderColor: '#fff', borderWidth: 3 },
            label: { formatter: '{b}: {d}%', color: '#334155' },
            data: data.collegeLabels.map((name, index) => ({ name, value: data.collegeValues[index] })),
        }],
    }, data.collegeValues.length > 0);

    data.breakdownSections.forEach((section, index) => {
        makeChart(`breakdownChart${index}`, {
            animationDuration: 550,
            grid: { left: 92, right: 22, top: 12, bottom: 20 },
            tooltip: { trigger: 'axis', axisPointer: { type: 'shadow' } },
            xAxis: { type: 'value', inverse: true, name: 'Lower = better' },
            yAxis: { type: 'category', data: section.labels },
            series: [{
                type: 'bar',
                data: section.values,
                barMaxWidth: 18,
                itemStyle: { color: '#3b82f6', borderRadius: [0, 3, 3, 0] },
            }],
        }, section.values.some((value) => value !== null));
    });
}

document.addEventListener('DOMContentLoaded', initDashboardCharts);
