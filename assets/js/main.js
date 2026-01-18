document.addEventListener('DOMContentLoaded', function() {
    initTheme();
    initMobileMenu();
    initCharts();
});

function initTheme() {
    const themeToggle = document.querySelector('.theme-toggle');
    const savedTheme = localStorage.getItem('theme') || 'light';
    
    if (savedTheme === 'dark') {
        document.body.classList.add('dark-mode');
    }
    
    if (themeToggle) {
        themeToggle.addEventListener('click', function() {
            document.body.classList.toggle('dark-mode');
            const currentTheme = document.body.classList.contains('dark-mode') ? 'dark' : 'light';
            localStorage.setItem('theme', currentTheme);
        });
    }
}

function initMobileMenu() {
    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    const navMenu = document.querySelector('.nav-menu');
    
    if (mobileMenuBtn && navMenu) {
        mobileMenuBtn.addEventListener('click', function() {
            navMenu.classList.toggle('active');
        });
    }
}

function initCharts() {
    const chartElements = document.querySelectorAll('[data-chart]');
    
    chartElements.forEach(function(element) {
        const chartType = element.dataset.chart;
        const chartData = JSON.parse(element.dataset.chartData || '{}');
        
        if (chartType === 'pie') {
            createPieChart(element, chartData);
        } else if (chartType === 'bar') {
            createBarChart(element, chartData);
        } else if (chartType === 'line') {
            createLineChart(element, chartData);
        }
    });
}

function createPieChart(element, data) {
    const canvas = document.createElement('canvas');
    element.appendChild(canvas);
    
    new Chart(canvas, {
        type: 'pie',
        data: {
            labels: data.labels || [],
            datasets: [{
                data: data.values || [],
                backgroundColor: data.colors || ['#4caf50', '#ff9800', '#f44336', '#2196f3']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}

function createBarChart(element, data) {
    const canvas = document.createElement('canvas');
    element.appendChild(canvas);
    
    new Chart(canvas, {
        type: 'bar',
        data: {
            labels: data.labels || [],
            datasets: [{
                label: data.label || 'Value',
                data: data.values || [],
                backgroundColor: data.colors || ['#4caf50']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

function createLineChart(element, data) {
    const canvas = document.createElement('canvas');
    element.appendChild(canvas);
    
    new Chart(canvas, {
        type: 'line',
        data: {
            labels: data.labels || [],
            datasets: [{
                label: data.label || 'Value',
                data: data.values || [],
                borderColor: '#4caf50',
                backgroundColor: 'rgba(76, 175, 80, 0.1)',
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.textContent = message;
    
    const container = document.querySelector('.container') || document.body;
    container.insertBefore(alertDiv, container.firstChild);
    
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}

function showLoading(btn) {
    const originalText = btn.textContent;
    btn.innerHTML = '<span class="loading"></span> Loading...';
    btn.disabled = true;
    
    return function hideLoading() {
        btn.textContent = originalText;
        btn.disabled = false;
    };
}

function setActiveNav() {
    const currentPage = window.location.pathname;
    const navLinks = document.querySelectorAll('.sidebar-menu a, .bottom-nav a');
    
    navLinks.forEach(link => {
        if (link.getAttribute('href') === currentPage) {
            link.classList.add('active');
        } else {
            link.classList.remove('active');
        }
    });
}

setActiveNav();
