/**
 * Funções JavaScript Globais
 * Controles e interações do sistema
 */

document.addEventListener('DOMContentLoaded', function() {
    // Atualiza a hora da última atualização a cada minuto
    setInterval(updateLastRefreshTime, 60000);
    
    // Inicializa tooltips
    initTooltips();
    
    // Configura eventos dos controles
    setupControls();
});

/**
 * Atualiza o horário da última atualização no footer
 */
function updateLastRefreshTime() {
    const now = new Date();
    const timeString = now.toLocaleTimeString('pt-BR');
    const footerElements = document.querySelectorAll('.footer p:last-child');
    
    footerElements.forEach(el => {
        el.textContent = `Última atualização: ${timeString}`;
    });
}

/**
 * Inicializa tooltips para elementos com data-tooltip
 */
function initTooltips() {
    const tooltipElements = document.querySelectorAll('[data-tooltip]');
    
    tooltipElements.forEach(el => {
        const tooltipText = el.getAttribute('data-tooltip');
        const tooltip = document.createElement('div');
        tooltip.className = 'tooltip';
        tooltip.textContent = tooltipText;
        
        el.appendChild(tooltip);
        
        el.addEventListener('mouseenter', function() {
            tooltip.style.visibility = 'visible';
            tooltip.style.opacity = '1';
        });
        
        el.addEventListener('mouseleave', function() {
            tooltip.style.visibility = 'hidden';
            tooltip.style.opacity = '0';
        });
    });
}

/**
 * Configura eventos para controles interativos
 */
function setupControls() {
    // Alterna entre dark/light mode
    const darkModeToggle = document.getElementById('darkModeToggle');
    if (darkModeToggle) {
        darkModeToggle.addEventListener('click', function() {
            document.body.classList.toggle('dark-mode');
            localStorage.setItem('darkMode', document.body.classList.contains('dark-mode'));
        });
        
        // Carrega preferência salva
        if (localStorage.getItem('darkMode') === 'true') {
            document.body.classList.add('dark-mode');
        }
    }
    
    // Atualiza resultados periodicamente
    if (document.querySelector('.results-grid')) {
        setInterval(fetchLatestResults, 30000); // Atualiza a cada 30 segundos
    }
}

/**
 * Busca os últimos resultados via AJAX
 */
function fetchLatestResults() {
    fetch('process.php?action=get_latest_results')
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                updateResultsGrid(data.data);
            }
        });
}

/**
 * Atualiza a grade de resultados com novos dados
 */
function updateResultsGrid(results) {
    const grid = document.querySelector('.results-grid');
    if (!grid) return;
    
    // Implementação simplificada - na prática seria mais complexo
    grid.innerHTML = results.map(result => {
        return `
            <div class="result-card ${result.winner}">
                <div class="left-card">
                    <span class="card-value">${result.left_value}</span>
                    <span class="card-suit">${getSuitSymbol(result.left_suit)}</span>
                </div>
                <div class="vs">VS</div>
                <div class="right-card">
                    <span class="card-value">${result.right_value}</span>
                    <span class="card-suit">${getSuitSymbol(result.right_suit)}</span>
                </div>
                <div class="winner-badge">
                    ${result.winner.toUpperCase()}
                </div>
                ${result.prediction !== 'none' ? `
                <div class="prediction ${result.prediction === result.winner ? 'correct' : 'incorrect'}">
                    IA: ${result.prediction.toUpperCase()} 
                    (${Math.round(result.confidence * 100)}%)
                </div>
                ` : ''}
            </div>
        `;
    }).join('');
}

/**
 * Retorna o símbolo do naipe para exibição
 */
function getSuitSymbol(suit) {
    const suits = {
        'hearts': '♥',
        'diamonds': '♦',
        'clubs': '♣',
        'spades': '♠'
    };
    return suits[suit] || suit;
}

/**
 * Mostra uma notificação para o usuário
 */
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.classList.add('fade-out');
        setTimeout(() => notification.remove(), 500);
    }, 3000);
}

// Estilos dinâmicos para notificações
const notificationStyles = document.createElement('style');
notificationStyles.textContent = `
.notification {
    position: fixed;
    bottom: 20px;
    right: 20px;
    padding: 15px 20px;
    border-radius: 4px;
    color: white;
    box-shadow: 0 3px 10px rgba(0,0,0,0.2);
    z-index: 1000;
    transition: all 0.3s ease;
}
.notification-info {
    background-color: #2196F3;
}
.notification-success {
    background-color: #4CAF50;
}
.notification-warning {
    background-color: #FFC107;
    color: #333;
}
.notification-error {
    background-color: #F44336;
}
.notification.fade-out {
    opacity: 0;
    transform: translateY(20px);
}
`;
document.head.appendChild(notificationStyles);