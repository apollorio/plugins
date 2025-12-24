/**
 * Line Graph Implementation - TODO 98
 * Simple, lightweight line graph without external libraries
 * Apollo style with Motion.dev animations
 * 
 * @package Apollo_Events_Manager
 * @version 0.1.0
 */

(function() {
    'use strict';
    
    /**
     * Create line graph
     * @param {string} containerId - Container element ID
     * @param {Array} data - Data points [{date: 'YYYY-MM-DD', value: number}]
     * @param {Object} options - Graph options
     */
    window.apolloLineGraph = function(containerId, data, options = {}) {
        const container = document.getElementById(containerId);
        if (!container || !data || data.length === 0) return;
        
        const defaults = {
            width: container.offsetWidth || 600,
            height: options.height || 300,
            strokeColor: options.strokeColor || 'var(--vermelho, #fd5c02)',
            fillColor: options.fillColor || 'rgba(253, 92, 2, 0.1)',
            strokeWidth: options.strokeWidth || 3,
            showDots: options.showDots !== false,
            showGrid: options.showGrid !== false,
            animate: options.animate !== false,
            yAxisLabel: options.yAxisLabel || '',
            xAxisLabel: options.xAxisLabel || '',
        };
        
        // Create SVG
        const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        svg.setAttribute('width', '100%');
        svg.setAttribute('height', defaults.height);
        svg.setAttribute('viewBox', `0 0 ${defaults.width} ${defaults.height}`);
        svg.style.overflow = 'visible';
        
        // Padding
        const padding = { top: 20, right: 30, bottom: 40, left: 50 };
        const graphWidth = defaults.width - padding.left - padding.right;
        const graphHeight = defaults.height - padding.top - padding.bottom;
        
        // Find min/max
        const values = data.map(d => d.value);
        const minValue = Math.min(...values);
        const maxValue = Math.max(...values);
        const valueRange = maxValue - minValue || 1;
        
        // Scale functions
        const scaleX = (index) => padding.left + (index / (data.length - 1)) * graphWidth;
        const scaleY = (value) => padding.top + graphHeight - ((value - minValue) / valueRange) * graphHeight;
        
        // Grid lines (if enabled)
        if (defaults.showGrid) {
            const gridGroup = document.createElementNS('http://www.w3.org/2000/svg', 'g');
            gridGroup.setAttribute('class', 'grid-lines');
            gridGroup.style.stroke = 'var(--border-color, #e0e2e4)';
            gridGroup.style.strokeWidth = '1';
            gridGroup.style.opacity = '0.3';
            
            for (let i = 0; i <= 5; i++) {
                const y = padding.top + (graphHeight / 5) * i;
                const line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
                line.setAttribute('x1', padding.left);
                line.setAttribute('y1', y);
                line.setAttribute('x2', padding.left + graphWidth);
                line.setAttribute('y2', y);
                gridGroup.appendChild(line);
            }
            svg.appendChild(gridGroup);
        }
        
        // Build path
        let pathD = '';
        let areaD = '';
        
        data.forEach((point, index) => {
            const x = scaleX(index);
            const y = scaleY(point.value);
            
            if (index === 0) {
                pathD += `M ${x} ${y}`;
                areaD += `M ${x} ${padding.top + graphHeight} L ${x} ${y}`;
            } else {
                pathD += ` L ${x} ${y}`;
                areaD += ` L ${x} ${y}`;
            }
        });
        
        // Close area path
        const lastX = scaleX(data.length - 1);
        areaD += ` L ${lastX} ${padding.top + graphHeight} Z`;
        
        // Area (fill)
        const area = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        area.setAttribute('d', areaD);
        area.style.fill = defaults.fillColor;
        area.style.stroke = 'none';
        svg.appendChild(area);
        
        // Line
        const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        path.setAttribute('d', pathD);
        path.style.fill = 'none';
        path.style.stroke = defaults.strokeColor;
        path.style.strokeWidth = defaults.strokeWidth;
        path.style.strokeLinecap = 'round';
        path.style.strokeLinejoin = 'round';
        
        // Animation
        if (defaults.animate) {
            const length = path.getTotalLength();
            path.style.strokeDasharray = length;
            path.style.strokeDashoffset = length;
            
            // Animate with CSS transition
            setTimeout(() => {
                path.style.transition = 'stroke-dashoffset 1.5s cubic-bezier(0.25, 0.8, 0.25, 1)';
                path.style.strokeDashoffset = '0';
            }, 100);
        }
        
        svg.appendChild(path);
        
        // Dots (if enabled)
        if (defaults.showDots) {
            data.forEach((point, index) => {
                const x = scaleX(index);
                const y = scaleY(point.value);
                
                // Outer circle (white bg)
                const outerCircle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
                outerCircle.setAttribute('cx', x);
                outerCircle.setAttribute('cy', y);
                outerCircle.setAttribute('r', 6);
                outerCircle.style.fill = 'var(--bg-main, #fff)';
                outerCircle.style.stroke = defaults.strokeColor;
                outerCircle.style.strokeWidth = 2;
                
                // Inner circle
                const innerCircle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
                innerCircle.setAttribute('cx', x);
                innerCircle.setAttribute('cy', y);
                innerCircle.setAttribute('r', 3);
                innerCircle.style.fill = defaults.strokeColor;
                
                // Tooltip on hover
                const tooltip = document.createElement('div');
                tooltip.className = 'graph-tooltip';
                tooltip.style.cssText = 'position:absolute;display:none;background:var(--bg-main);border:1px solid var(--border-color);padding:8px 12px;border-radius:8px;font-size:0.85rem;box-shadow:0 4px 12px rgba(0,0,0,0.1);z-index:1000;pointer-events:none;';
                tooltip.innerHTML = `<strong>${point.date}</strong><br>${point.value} visualizações`;
                container.appendChild(tooltip);
                
                outerCircle.addEventListener('mouseenter', (e) => {
                    const rect = container.getBoundingClientRect();
                    tooltip.style.display = 'block';
                    tooltip.style.left = (x - rect.left - tooltip.offsetWidth / 2) + 'px';
                    tooltip.style.top = (y - rect.top - tooltip.offsetHeight - 10) + 'px';
                });
                
                outerCircle.addEventListener('mouseleave', () => {
                    tooltip.style.display = 'none';
                });
                
                svg.appendChild(outerCircle);
                svg.appendChild(innerCircle);
                
                // Animate dots
                if (defaults.animate) {
                    outerCircle.style.opacity = '0';
                    outerCircle.style.transform = 'scale(0)';
                    outerCircle.style.transformOrigin = 'center';
                    outerCircle.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                    
                    setTimeout(() => {
                        outerCircle.style.opacity = '1';
                        outerCircle.style.transform = 'scale(1)';
                    }, 1000 + (index * 100));
                }
            });
        }
        
        // Clear and append
        container.innerHTML = '';
        container.appendChild(svg);
        container.style.position = 'relative';
        
        return svg;
    };
    
    /**
     * Helper: Format data for graph
     * @param {Object} dailyData - Daily views object {date: {page, popup, total}}
     * @returns {Array} Formatted data for graph
     */
    window.apolloFormatGraphData = function(dailyData, type = 'total') {
        if (!dailyData || typeof dailyData !== 'object') return [];
        
        const formatted = [];
        Object.keys(dailyData).forEach(date => {
            const value = dailyData[date][type] || 0;
            formatted.push({
                date: formatDate(date),
                value: value
            });
        });
        
        return formatted.sort((a, b) => new Date(a.date) - new Date(b.date));
    };
    
    function formatDate(dateStr) {
        const d = new Date(dateStr);
        const day = d.getDate();
        const months = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
        return `${day} ${months[d.getMonth()]}`;
    }
    
})();

