// Currency Management System
class CurrencyManager {
    constructor() {
        this.currencies = this.loadCurrencies();
        this.transactions = this.loadTransactions();
        this.lastDailyBonus = this.loadLastDailyBonus();
        
        // Settings integration
        this.notificationsEnabled = true;
        this.dailyBonusReminder = true;
        this.transactionHistoryEnabled = true;
        this.soundEffectsEnabled = true;
        
        this.initializeCurrencySystem();
    }

    // Load currencies from localStorage
    loadCurrencies() {
        const saved = localStorage.getItem('blue16_currencies');
        if (saved) {
            return JSON.parse(saved);
        }
        
        // Default currency values
        return {
            credits: 100, // Starting credits
            bucks: 50     // Starting bucks
        };
    }

    // Load transaction history
    loadTransactions() {
        const saved = localStorage.getItem('blue16_transactions');
        return saved ? JSON.parse(saved) : [];
    }

    // Load last daily bonus timestamp
    loadLastDailyBonus() {
        const saved = localStorage.getItem('blue16_last_daily_bonus');
        return saved ? new Date(saved) : null;
    }

    // Save currencies to localStorage
    saveCurrencies() {
        localStorage.setItem('blue16_currencies', JSON.stringify(this.currencies));
        this.updateCurrencyDisplay();
    }

    // Save transaction history
    saveTransactions() {
        localStorage.setItem('blue16_transactions', JSON.stringify(this.transactions));
    }

    // Save last daily bonus timestamp
    saveLastDailyBonus() {
        localStorage.setItem('blue16_last_daily_bonus', this.lastDailyBonus.toISOString());
    }

    // Initialize currency system
    initializeCurrencySystem() {
        this.createCurrencyDisplay();
        this.updateCurrencyDisplay();
        this.checkDailyBonus();
        this.bindCurrencyEvents();
    }

    // Create currency display in header
    createCurrencyDisplay() {
        const headerRight = document.querySelector('.header-right');
        if (!headerRight) return;

        // Check if currency display already exists
        if (document.querySelector('.currency-display')) return;

        const currencyDisplay = document.createElement('div');
        currencyDisplay.className = 'currency-display';
        currencyDisplay.innerHTML = `
            <div class="currency-item" data-currency="credits">
                <div class="currency-icon credits-icon">
                    <span class="iconify" data-icon="mdi:coin"></span>
                    <span class="currency-letter">C</span>
                </div>
                <span class="currency-amount" id="credits-amount">${this.currencies.credits}</span>
            </div>
            <div class="currency-item" data-currency="bucks">
                <div class="currency-icon bucks-icon">
                    <span class="iconify" data-icon="mdi:cash"></span>
                </div>
                <span class="currency-amount" id="bucks-amount">${this.currencies.bucks}</span>
            </div>
        `;

        // Insert before notifications
        const notifications = headerRight.querySelector('.notifications');
        if (notifications) {
            headerRight.insertBefore(currencyDisplay, notifications);
        } else {
            headerRight.appendChild(currencyDisplay);
        }
    }

    // Update currency display
    updateCurrencyDisplay() {
        const creditsElement = document.getElementById('credits-amount');
        const bucksElement = document.getElementById('bucks-amount');
        
        if (creditsElement) {
            creditsElement.textContent = this.currencies.credits.toLocaleString();
        }
        if (bucksElement) {
            bucksElement.textContent = this.currencies.bucks.toLocaleString();
        }
    }

    // Check and award daily bonus
    checkDailyBonus() {
        const now = new Date();
        const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
        
        if (!this.lastDailyBonus || this.lastDailyBonus < today) {
            this.awardDailyBonus();
        } else if (this.dailyBonusReminder) {
            // Show reminder if bonus already claimed today
            const hoursUntilBonus = 24 - (now - this.lastDailyBonus) / (1000 * 60 * 60);
            if (hoursUntilBonus > 0 && hoursUntilBonus < 24) {
                this.showNotification(`Daily bonus available in ${Math.ceil(hoursUntilBonus)} hours`, 'info');
            }
        }
    }

    // Award daily bonus
    awardDailyBonus() {
        const bonusAmount = 10;
        this.addCurrency('credits', bonusAmount, 'Daily Login Bonus');
        this.lastDailyBonus = new Date();
        this.saveLastDailyBonus();
        
        // Show notification
        this.showNotification(`Daily Bonus! +${bonusAmount} Credits`, 'success');
    }

    // Add currency (general method)
    addCurrency(type, amount, reason = '') {
        if (!this.currencies.hasOwnProperty(type)) {
            console.error(`Invalid currency type: ${type}`);
            return false;
        }

        const oldAmount = this.currencies[type];
        this.currencies[type] = Math.max(0, this.currencies[type] + amount);
        
        // Record transaction
        this.recordTransaction(type, amount, reason, oldAmount, this.currencies[type]);
        
        this.saveCurrencies();
        this.saveTransactions();
        
        // Animate the change
        this.animateCurrencyChange(type, amount);
        
        return true;
    }

    // Remove currency
    removeCurrency(type, amount, reason = '') {
        return this.addCurrency(type, -amount, reason);
    }

    // Record transaction
    recordTransaction(type, amount, reason, oldAmount, newAmount) {
        const transaction = {
            id: Date.now(),
            type: type,
            amount: amount,
            reason: reason,
            oldAmount: oldAmount,
            newAmount: newAmount,
            timestamp: new Date().toISOString()
        };
        
        this.transactions.unshift(transaction); // Add to beginning
        
        // Keep only last 100 transactions
        if (this.transactions.length > 100) {
            this.transactions = this.transactions.slice(0, 100);
        }
    }

    // Animate currency change
    animateCurrencyChange(type, amount) {
        const currencyElement = document.querySelector(`[data-currency="${type}"] .currency-amount`);
        if (!currencyElement) return;

        currencyElement.style.transform = 'scale(1.2)';
        currencyElement.style.color = amount > 0 ? '#4caf50' : '#f44336';
        
        setTimeout(() => {
            currencyElement.style.transform = 'scale(1)';
            currencyElement.style.color = '';
        }, 500);
    }

    // Show notification
    showNotification(message, type = 'info') {
        // Check if notifications are enabled
        if (!this.notificationsEnabled) return;
        
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `currency-notification ${type}`;
        notification.textContent = message;
        
        // Add to page
        document.body.appendChild(notification);
        
        // Animate in
        setTimeout(() => {
            notification.style.opacity = '1';
            notification.style.transform = 'translateY(0)';
        }, 10);
        
        // Remove after 3 seconds
        setTimeout(() => {
            notification.style.opacity = '0';
            notification.style.transform = 'translateY(-20px)';
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 300);
        }, 3000);
    }

    // Award currency for joining game
    awardGameJoinBonus() {
        const bonusAmount = 10;
        this.addCurrency('credits', bonusAmount, 'Game Join Bonus');
        this.showNotification(`+${bonusAmount} Credits for joining game!`, 'success');
    }

    // Get transaction history
    getTransactionHistory(limit = 10) {
        if (!this.transactionHistoryEnabled) {
            return [];
        }
        return this.transactions.slice(0, limit);
    }

    // Get currency balance
    getBalance(type) {
        return this.currencies[type] || 0;
    }

    // Check if user can afford purchase
    canAfford(type, amount) {
        return this.getBalance(type) >= amount;
    }

    // Make purchase
    makePurchase(type, amount, itemName = '') {
        if (!this.canAfford(type, amount)) {
            this.showNotification(`Not enough ${type}!`, 'error');
            return false;
        }

        const reason = itemName ? `Purchased: ${itemName}` : 'Purchase';
        this.removeCurrency(type, amount, reason);
        this.showNotification(`Purchased: ${itemName}`, 'success');
        return true;
    }

    // Bind currency events
    bindCurrencyEvents() {
        // Add click handlers for currency display
        document.querySelectorAll('.currency-item').forEach(item => {
            item.addEventListener('click', () => {
                const currencyType = item.dataset.currency;
                this.showCurrencyDetails(currencyType);
            });
        });
    }

    // Show currency details modal
    showCurrencyDetails(type) {
        // Create modal if it doesn't exist
        let modal = document.getElementById('currency-modal');
        if (!modal) {
            modal = this.createCurrencyModal();
        }

        // Update modal content
        const balance = this.getBalance(type);
        const recentTransactions = this.getTransactionHistory().filter(t => t.type === type);
        
        modal.querySelector('.modal-currency-type').textContent = type.charAt(0).toUpperCase() + type.slice(1);
        modal.querySelector('.modal-currency-balance').textContent = balance.toLocaleString();
        
        // Update transaction list
        const transactionList = modal.querySelector('.transaction-list');
        transactionList.innerHTML = '';
        
        recentTransactions.forEach(transaction => {
            const transactionItem = document.createElement('div');
            transactionItem.className = 'transaction-item';
            transactionItem.innerHTML = `
                <div class="transaction-reason">${transaction.reason}</div>
                <div class="transaction-amount ${transaction.amount > 0 ? 'positive' : 'negative'}">
                    ${transaction.amount > 0 ? '+' : ''}${transaction.amount}
                </div>
                <div class="transaction-time">${this.formatTime(transaction.timestamp)}</div>
            `;
            transactionList.appendChild(transactionItem);
        });

        // Show modal
        modal.style.display = 'block';
    }

    // Create currency modal
    createCurrencyModal() {
        const modal = document.createElement('div');
        modal.id = 'currency-modal';
        modal.className = 'currency-modal';
        modal.innerHTML = `
            <div class="modal-content">
                <div class="modal-header">
                    <h3><span class="modal-currency-type"></span> Balance</h3>
                    <button class="modal-close">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="currency-balance-display">
                        <span class="modal-currency-balance"></span>
                    </div>
                    <div class="transaction-history">
                        <h4>Recent Transactions</h4>
                        <div class="transaction-list"></div>
                    </div>
                </div>
            </div>
        `;

        // Add close functionality
        modal.querySelector('.modal-close').addEventListener('click', () => {
            modal.style.display = 'none';
        });

        // Close on outside click
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.style.display = 'none';
            }
        });

        document.body.appendChild(modal);
        return modal;
    }

    // Format timestamp
    formatTime(timestamp) {
        const date = new Date(timestamp);
        const now = new Date();
        const diff = now - date;
        
        if (diff < 60000) return 'Just now';
        if (diff < 3600000) return `${Math.floor(diff / 60000)}m ago`;
        if (diff < 86400000) return `${Math.floor(diff / 3600000)}h ago`;
        return date.toLocaleDateString();
    }

    // Reset currencies (for testing)
    resetCurrencies() {
        this.currencies = { credits: 100, bucks: 50 };
        this.transactions = [];
        this.lastDailyBonus = null;
        this.saveCurrencies();
        this.saveTransactions();
        localStorage.removeItem('blue16_last_daily_bonus');
    }
}

// Initialize currency system when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Only initialize if we're on the dashboard
    if (document.querySelector('.dashboard-layout')) {
        window.currencyManager = new CurrencyManager();
    }
});
