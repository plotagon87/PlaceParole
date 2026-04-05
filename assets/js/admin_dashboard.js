/**
 * assets/js/admin_dashboard.js
 * 
 * Dashboard JS: Chart rendering, widget polling, widget configuration
 */

// ========== DASHBOARD APP STATE ==========
function dashboardApp() {
    return {
        widgets: {},
        loading: false,
        lastPollTime: Date.now(),
        
        // Initialize widget visibility from server
        async initWidgets() {
            try {
                const response = await fetch('/PlaceParole/modules/admin/dashboard_data.php?widget=config', {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!response.ok) return;
                
                const data = await response.json();
                if (data.success && data.data) {
                    this.widgets = data.data.reduce((acc, w) => {
                        acc[w.widget_id] = w.is_visible;
                        acc[w.widget_id + '_order'] = w.sort_order;
                        return acc;
                    }, {});
                }
            } catch (e) {
                console.error('Failed to load widget config:', e);
            }
        },
        
        // Check if widget is visible
        widgetVisible(widgetId) {
            if (this.widgets[widgetId] === undefined) return true;
            return this.widgets[widgetId];
        },
        
        // Toggle widget visibility
        toggleWidget(widgetId) {
            this.widgets[widgetId] = !this.widgets[widgetId];
            this.saveWidgetConfig();
        },
        
        // Save widget config to server
        async saveWidgetConfig() {
            try {
                const widgets = Object.keys(this.widgets)
                    .filter(k => !k.endsWith('_order'))
                    .map(id => ({
                        id: id,
                        visible: this.widgets[id],
                        order: this.widgets[id + '_order'] || 0
                    }));
                
                const response = await fetch('/PlaceParole/modules/admin/dashboard_widget_save.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ widgets })
                });
                
                const data = await response.json();
                if (!data.success) {
                    alert(window.translations['widget_save_error']);
                }
            } catch (e) {
                console.error('Failed to save widget config:', e);
            }
        },
        
        // Reset widgets to defaults
        resetWidgets() {
            const defaults = {
                'metrics_row': true,
                'complaint_donut': true,
                'sla_alert': true,
                'top_markets': true,
                'growth_chart': true,
                'activity_feed': true,
                'health_pill': true
            };
            
            this.widgets = {};
            Object.entries(defaults).forEach(([id, visible], idx) => {
                this.widgets[id] = visible;
                this.widgets[id + '_order'] = idx;
            });
            
            this.saveWidgetConfig();
        },
        
        // Start polling for updates
        startPolling() {
            setInterval(() => this.pollMetrics(), 60000);
            setInterval(() => this.pollActivityFeed(), 30000);
            setInterval(() => this.pollCharts(), 60000);
        },
        
        // Poll and update metric cards
        async pollMetrics() {
            try {
                const response = await fetch('/PlaceParole/modules/admin/dashboard_data.php?widget=metrics', {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!response.ok) return;
                
                const data = await response.json();
                if (data.success && data.data) {
                    updateMetricCards(data.data);
                }
            } catch (e) {
                console.error('Metric poll failed:', e);
            }
        },
        
        // Poll and update complaint donut
        async pollCharts() {
            try {
                const response = await fetch('/PlaceParole/modules/admin/dashboard_data.php?widget=complaints', {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!response.ok) return;
                
                const data = await response.json();
                if (data.success && data.data) {
                    redrawComplaintDonut(data.data);
                }
            } catch (e) {
                console.error('Chart poll failed:', e);
            }
        },
        
        // Poll and append new activity feed entries
        async pollActivityFeed() {
            try {
                const response = await fetch('/PlaceParole/modules/admin/dashboard_data.php?widget=activity', {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!response.ok) return;
                
                const data = await response.json();
                if (data.success && data.data) {
                    updateActivityFeed(data.data);
                }
            } catch (e) {
                console.error('Activity feed poll failed:', e);
            }
        }
    };
}

// ========== UPDATE METRIC CARDS ==========
function updateMetricCards(metrics) {
    const cards = {
        'total_sellers': document.getElementById('metric-sellers'),
        'active_managers': document.getElementById('metric-managers'),
        'open_complaints': document.getElementById('metric-complaints'),
        'announcements_sent': document.getElementById('metric-announcements')
    };
    
    for (const [key, element] of Object.entries(cards)) {
        if (element && metrics[key] !== undefined) {
            element.textContent = metrics[key].toLocaleString();
        }
    }
}

// ========== RENDER COMPLAINT DONUT CHART ==========
function renderComplaintDonut() {
    const canvas = document.getElementById('complaint-donut-canvas');
    if (!canvas) return;
    
    fetchDonutData().then(data => {
        if (!data || !data.total) {
            canvas.parentElement.innerHTML = '<p class="text-gray-500 text-center py-8">' + window.translations['data_no_complaints'] + '</p>';
            return;
        }
        drawDonutChart(canvas, data);
    });
}

async function fetchDonutData() {
    try {
        const response = await fetch('/PlaceParole/modules/admin/dashboard_data.php?widget=complaints', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        if (!response.ok) return null;
        const data = await response.json();
        return data.success ? data.data : null;
    } catch (e) {
        console.error('Failed to fetch donut data:', e);
        return null;
    }
}

function drawDonutChart(canvas, data) {
    const ctx = canvas.getContext('2d');
    const width = canvas.width;
    const height = canvas.height;
    const centerX = width / 2;
    const centerY = height / 2;
    const radius = Math.min(width, height) / 2 - 20;
    const innerRadius = radius * 0.6;
    
    ctx.clearRect(0, 0, width, height);
    
    const segments = [
        { label: 'Pending', value: data.pending, count: data.pending, color: '#ef4444' },
        { label: 'In Review', value: data.in_review, count: data.in_review, color: '#f59e0b' },
        { label: 'Resolved', value: data.resolved, count: data.resolved, color: '#10b981' }
    ];
    
    const total = data.total;
    let startAngle = -Math.PI / 2;
    
    segments.forEach((seg, idx) => {
        const sliceAngle = (seg.value / total) * Math.PI * 2;
        
        ctx.beginPath();
        ctx.arc(centerX, centerY, radius, startAngle, startAngle + sliceAngle);
        ctx.arc(centerX, centerY, innerRadius, startAngle + sliceAngle, startAngle, true);
        ctx.fillStyle = seg.color;
        ctx.fill();
        ctx.strokeStyle = '#fff';
        ctx.lineWidth = 2;
        ctx.stroke();
        
        const labelAngle = startAngle + sliceAngle / 2;
        const labelRadius = (radius + innerRadius) / 2;
        const labelX = centerX + Math.cos(labelAngle) * labelRadius;
        const labelY = centerY + Math.sin(labelAngle) * labelRadius;
        
        ctx.font = 'bold 14px system-ui';
        ctx.fillStyle = '#fff';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        
        const pct = Math.round((seg.value / total) * 100);
        const text = `${seg.count}\n(${pct}%)`;
        
        ctx.fillStyle = 'rgba(0,0,0,0.3)';
        ctx.globalAlpha = 0.7;
        ctx.beginPath();
        ctx.arc(labelX, labelY, 22, 0, Math.PI * 2);
        ctx.fill();
        ctx.globalAlpha = 1;
        
        ctx.fillStyle = '#fff';
        text.split('\n').forEach((line, i) => {
            ctx.fillText(line, labelX, labelY - 5 + i * 12);
        });
        
        startAngle += sliceAngle;
    });
    
    ctx.beginPath();
    ctx.arc(centerX, centerY, innerRadius, 0, Math.PI * 2);
    ctx.fillStyle = '#fff';
    ctx.fill();
    ctx.strokeStyle = '#e5e7eb';
    ctx.lineWidth = 2;
    ctx.stroke();
    
    const legendY = height - 30;
    let legendX = 20;
    
    segments.forEach(seg => {
        ctx.fillStyle = seg.color;
        ctx.fillRect(legendX, legendY, 12, 12);
        
        ctx.fillStyle = '#374151';
        ctx.font = '12px system-ui';
        ctx.textAlign = 'left';
        ctx.fillText(`${seg.label}: ${seg.count}`, legendX + 18, legendY + 10);
        
        legendX += 150;
    });
}

function redrawComplaintDonut(data) {
    const canvas = document.getElementById('complaint-donut-canvas');
    if (canvas) {
        drawDonutChart(canvas, data);
    }
}

// ========== RENDER GROWTH CHART ==========
function renderGrowthChart() {
    const canvas = document.getElementById('growth-chart-canvas');
    if (!canvas) return;
    
    fetchGrowthData().then(data => {
        if (!data || !Array.isArray(data) || data.length === 0) {
            canvas.parentElement.innerHTML = '<p class="text-gray-500 text-center py-8">' + window.translations['data_no_growth'] + '</p>';
            return;
        }
        drawGrowthChart(canvas, data);
    });
}

async function fetchGrowthData() {
    try {
        const response = await fetch('/PlaceParole/modules/admin/dashboard_data.php?widget=growth', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        if (!response.ok) return null;
        const data = await response.json();
        return data.success ? data.data : null;
    } catch (e) {
        console.error('Failed to fetch growth data:', e);
        return null;
    }
}

function drawGrowthChart(canvas, data) {
    const ctx = canvas.getContext('2d');
    const width = canvas.width;
    const height = canvas.height;
    const padding = { top: 20, right: 20, bottom: 40, left: 50 };
    const chartWidth = width - padding.left - padding.right;
    const chartHeight = height - padding.top - padding.bottom;
    
    ctx.clearRect(0, 0, width, height);
    
    const maxUsers = Math.max(...data.map(d => d.new_users)) || 1;
    const maxComplaints = Math.max(...data.map(d => d.new_complaints)) || 1;
    const max = Math.max(maxUsers, maxComplaints) * 1.1;
    
    ctx.strokeStyle = '#e5e7eb';
    ctx.font = '11px system-ui';
    ctx.fillStyle = '#9ca3af';
    ctx.textAlign = 'right';
    
    for (let i = 0; i <= 5; i++) {
        const y = padding.top + (chartHeight * i) / 5;
        const val = Math.round((max * (5 - i)) / 5);
        
        ctx.beginPath();
        ctx.moveTo(padding.left, y);
        ctx.lineTo(width - padding.right, y);
        ctx.stroke();
        
        ctx.fillText(val, padding.left - 10, y + 4);
    }
    
    const barWidth = chartWidth / (data.length * 2.5);
    const barSpacing = barWidth * 1.5;
    
    data.forEach((point, idx) => {
        const x = padding.left + (idx + 0.5) * (chartWidth / data.length);
        
        const userHeight = (point.new_users / max) * chartHeight;
        const complaintHeight = (point.new_complaints / max) * chartHeight;
        
        ctx.fillStyle = '#16a34a';
        ctx.fillRect(x - barWidth - 5, padding.top + chartHeight - userHeight, barWidth, userHeight);
        
        ctx.fillStyle = '#ea580c';
        ctx.fillRect(x + 5, padding.top + chartHeight - complaintHeight, barWidth, complaintHeight);
        
        ctx.save();
        ctx.translate(x, padding.top + chartHeight + 10);
        ctx.rotate(-Math.PI / 6);
        ctx.textAlign = 'right';
        ctx.font = '11px system-ui';
        ctx.fillStyle = '#6b7280';
        ctx.fillText(point.month, 0, 0);
        ctx.restore();
    });
    
    ctx.strokeStyle = '#374151';
    ctx.lineWidth = 2;
    ctx.beginPath();
    ctx.moveTo(padding.left, padding.top);
    ctx.lineTo(padding.left, padding.top + chartHeight);
    ctx.lineTo(width - padding.right, padding.top + chartHeight);
    ctx.stroke();
    
    const legendY = height - 15;
    ctx.fillStyle = '#16a34a';
    ctx.fillRect(padding.left, legendY, 12, 12);
    ctx.fillStyle = '#374151';
    ctx.font = '12px system-ui';
    ctx.textAlign = 'left';
    ctx.fillText('New Users', padding.left + 18, legendY + 10);
    
    ctx.fillStyle = '#ea580c';
    ctx.fillRect(padding.left + 150, legendY, 12, 12);
    ctx.fillStyle = '#374151';
    ctx.fillText('New Complaints', padding.left + 168, legendY + 10);
}

// ========== UPDATE ACTIVITY FEED ==========
function updateActivityFeed(activities) {
    const feedContainer = document.getElementById('activity-feed-list');
    if (!feedContainer) return;
    
    const existing = new Set(Array.from(feedContainer.querySelectorAll('[data-activity-id]')).map(el => el.dataset.activityId));
    
    activities.forEach(activity => {
        if (!existing.has(String(activity.id))) {
            const entry = createActivityEntryHTML(activity);
            feedContainer.insertAdjacentHTML('afterbegin', entry);
            existing.add(String(activity.id));
        }
    });
    
    const entries = feedContainer.querySelectorAll('[data-activity-id]');
    for (let i = entries.length - 1; i >= 20; i--) {
        entries[i].remove();
    }
}

function createActivityEntryHTML(activity) {
    const now = new Date();
    const actTime = new Date(activity.created_at);
    const diff = Math.floor((now - actTime) / 1000);
    
    let timeAgo = '';
    if (diff < 60) timeAgo = 'just now';
    else if (diff < 3600) timeAgo = Math.floor(diff / 60) + 'm ago';
    else if (diff < 86400) timeAgo = Math.floor(diff / 3600) + 'h ago';
    else timeAgo = Math.floor(diff / 86400) + 'd ago';
    
    const initials = (activity.actor_name || 'System').split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
    
    return `
        <div class="flex gap-3 py-3 border-b border-gray-100 activity-entry" data-activity-id="${activity.id}">
            <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center text-sm font-bold text-green-700 flex-shrink-0">
                ${initials}
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm">
                    <strong>${htmlEscape(activity.actor_name || 'System')}</strong>
                    <span class="text-gray-600">${htmlEscape(formatActionDescription(activity.action_type))}</span>
                </p>
                <p class="text-xs text-gray-500 mt-0.5">${timeAgo}</p>
            </div>
        </div>
    `;
}

function formatActionDescription(actionType) {
    const descriptions = {
        'user_created': 'created a user account',
        'user_updated': 'updated a user account',
        'user_deactivated': 'deactivated a user',
        'user_reactivated': 'reactivated a user',
        'login': 'logged in',
        'logout': 'logged out',
        'complaint_status_changed': 'updated complaint status',
        'system_error': 'triggered a system error'
    };
    
    return descriptions[actionType] || 'performed an action';
}

function htmlEscape(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}

// ========== INIT ON PAGE LOAD ==========
document.addEventListener('DOMContentLoaded', function() {
    renderComplaintDonut();
    renderGrowthChart();
});
