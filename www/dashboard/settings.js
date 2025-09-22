// Settings Management System
class SettingsManager {
    constructor() {
        this.settings = this.loadSettings();
        this.initializeSettings();
        this.bindEvents();
    }

    // Load settings from localStorage
    loadSettings() {
        const defaultSettings = {
            // Appearance Settings
            theme: 'dark',
            accentColor: '#4fc3f7',
            fontSize: 'medium',
            animations: true,
            backgroundEffects: true,
            reduceMotion: false,
            sidebarPosition: 'left',
            compactMode: false,
            
            // Account Settings
            username: 'kitty',
            email: 'kitty@example.com',
            displayName: 'kitty',
            interfaceLanguage: 'english (us)',
            timeZone: 'utc-5:00 (est)',
            dateFormat: 'mm/dd/yyyy',
            timeFormat: '12-hour',
            
            // Privacy Settings
            profileVisibility: 'friends of friends',
            onlineStatus: true,
            activityStatus: true,
            dataCollection: false,
            marketingEmails: false,
            
            // Notification Settings
            emailNotifications: true,
            pushNotifications: true,
            inAppNotifications: true,
            soundEffects: true,
            desktopNotifications: false,
            
            // Gaming Settings
            gameNotifications: true,
            friendRequests: true,
            partyInvites: true,
            voiceChat: true,
            autoJoinVoice: false,
            streamingQuality: 'high',
            crossPlatformPlay: true,
            
            // Advanced Settings
            developerMode: false,
            betaFeatures: false,
            telemetry: false,
            autoUpdates: true,
            cacheSize: 'medium',
            bandwidthLimit: 'unlimited',
            
            // Currency Settings
            currencyNotifications: true,
            dailyBonusReminder: true,
            transactionHistory: true,
            currencySoundEffects: true
        };

        try {
            const saved = localStorage.getItem('dashboardSettings');
            return saved ? { ...defaultSettings, ...JSON.parse(saved) } : defaultSettings;
        } catch (error) {
            console.warn('Failed to load settings:', error);
            return defaultSettings;
        }
    }

    // Save settings to localStorage
    saveSettings() {
        try {
            localStorage.setItem('dashboardSettings', JSON.stringify(this.settings));
            this.showNotification('Settings saved successfully!', 'success');
        } catch (error) {
            console.error('Failed to save settings:', error);
            this.showNotification('Failed to save settings', 'error');
        }
    }

    // Initialize all settings
    initializeSettings() {
        this.initializeAppearanceSettings();
        this.initializeAccountSettings();
        this.initializePrivacySettings();
        this.initializeNotificationSettings();
        this.initializeGamingSettings();
        this.initializeAdvancedSettings();
        this.initializeCurrencySettings();
        this.initializeSettingsNavigation();
    }

    // Initialize Settings Navigation
    initializeSettingsNavigation() {
        const navButtons = document.querySelectorAll('.settings-nav-btn');
        const categories = document.querySelectorAll('.settings-category');

        navButtons.forEach(button => {
            button.addEventListener('click', () => {
                const targetCategory = button.getAttribute('data-category');
                
                // Update active nav button
                navButtons.forEach(btn => btn.classList.remove('active'));
                button.classList.add('active');
                
                // Show corresponding category
                categories.forEach(category => {
                    if (category.id === `${targetCategory}-settings`) {
                        category.classList.add('active');
                    } else {
                        category.classList.remove('active');
                    }
                });
            });
        });
    }

    // Initialize Appearance Settings
    initializeAppearanceSettings() {
        // Theme selection
        const themeOptions = document.querySelectorAll('.theme-option');
        themeOptions.forEach(option => {
            option.addEventListener('click', () => {
                const theme = option.getAttribute('data-theme');
                this.settings.theme = theme;
                this.applyTheme(theme);
                this.saveSettings();
                
                // Update active theme
                themeOptions.forEach(opt => opt.classList.remove('active'));
                option.classList.add('active');
            });
        });

        // Apply saved theme
        this.applyTheme(this.settings.theme);
        this.updateActiveTheme();

        // Accent color
        const colorPicker = document.querySelector('.color-picker');
        if (colorPicker) {
            colorPicker.value = this.settings.accentColor;
            colorPicker.addEventListener('change', (e) => {
                this.settings.accentColor = e.target.value;
                this.applyAccentColor(e.target.value);
                this.saveSettings();
            });
        }

        // Font size
        const fontSizeSelect = Array.from(document.querySelectorAll('.setting-item'))
            .find(item => item.querySelector('h6')?.textContent.toLowerCase().includes('font size'))
            ?.querySelector('.form-select');
        if (fontSizeSelect) {
            fontSizeSelect.value = this.settings.fontSize;
            fontSizeSelect.addEventListener('change', (e) => {
                this.settings.fontSize = e.target.value;
                this.applyFontSize(e.target.value);
                this.saveSettings();
            });
        }

        // Interface effects
        this.initializeToggle('animations', 'animations', () => this.applyAnimations());
        this.initializeToggle('background effects', 'backgroundEffects', () => this.applyBackgroundEffects());
        this.initializeToggle('reduce motion', 'reduceMotion', () => this.applyReduceMotion());

        // Layout preferences
        const sidebarSelect = Array.from(document.querySelectorAll('.setting-item'))
            .find(item => item.querySelector('h6')?.textContent.toLowerCase().includes('sidebar position'))
            ?.querySelector('.form-select');
        if (sidebarSelect) {
            sidebarSelect.value = this.settings.sidebarPosition;
            sidebarSelect.addEventListener('change', (e) => {
                this.settings.sidebarPosition = e.target.value;
                this.applySidebarPosition(e.target.value);
                this.saveSettings();
            });
        }

        this.initializeToggle('compact mode', 'compactMode', () => this.applyCompactMode());
    }

    // Initialize Account Settings
    initializeAccountSettings() {
        // Display name
        const displayNameInput = Array.from(document.querySelectorAll('.setting-item'))
            .find(item => item.querySelector('h6')?.textContent.toLowerCase().includes('display name'))
            ?.querySelector('.form-input');
        if (displayNameInput) {
            displayNameInput.value = this.settings.displayName;
            displayNameInput.addEventListener('input', (e) => {
                this.settings.displayName = e.target.value;
                this.saveSettings();
            });
        }

        // Language and region
        const languageSelect = Array.from(document.querySelectorAll('.setting-item'))
            .find(item => item.querySelector('h6')?.textContent.toLowerCase().includes('language'))
            ?.querySelector('.form-select');
        if (languageSelect) {
            languageSelect.value = this.settings.interfaceLanguage;
            languageSelect.addEventListener('change', (e) => {
                this.settings.interfaceLanguage = e.target.value;
                this.saveSettings();
            });
        }

        const timezoneSelect = Array.from(document.querySelectorAll('.setting-item'))
            .find(item => item.querySelector('h6')?.textContent.toLowerCase().includes('time zone'))
            ?.querySelector('.form-select');
        if (timezoneSelect) {
            timezoneSelect.value = this.settings.timeZone;
            timezoneSelect.addEventListener('change', (e) => {
                this.settings.timeZone = e.target.value;
                this.saveSettings();
            });
        }

        const dateFormatSelect = Array.from(document.querySelectorAll('.setting-item'))
            .find(item => item.querySelector('h6')?.textContent.toLowerCase().includes('date format'))
            ?.querySelector('.form-select');
        if (dateFormatSelect) {
            dateFormatSelect.value = this.settings.dateFormat;
            dateFormatSelect.addEventListener('change', (e) => {
                this.settings.dateFormat = e.target.value;
                this.applyDateFormat(e.target.value);
                this.saveSettings();
            });
        }

        const timeFormatSelect = Array.from(document.querySelectorAll('.setting-item'))
            .find(item => item.querySelector('h6')?.textContent.toLowerCase().includes('time format'))
            ?.querySelector('.form-select');
        if (timeFormatSelect) {
            timeFormatSelect.value = this.settings.timeFormat;
            timeFormatSelect.addEventListener('change', (e) => {
                this.settings.timeFormat = e.target.value;
                this.applyTimeFormat(e.target.value);
                this.saveSettings();
            });
        }

        // Account management buttons
        this.initializeAccountManagementButtons();
    }

    // Initialize Privacy Settings
    initializePrivacySettings() {
        // Profile privacy
        const visibilitySelect = Array.from(document.querySelectorAll('#privacy-settings .setting-item'))
            .find(item => item.querySelector('h6')?.textContent.toLowerCase().includes('profile visibility'))
            ?.querySelector('.form-select');
        if (visibilitySelect) {
            visibilitySelect.value = this.settings.profileVisibility;
            visibilitySelect.addEventListener('change', (e) => {
                this.settings.profileVisibility = e.target.value;
                this.saveSettings();
            });
        }

        // Privacy toggles
        this.initializePrivacyToggle('show online status', 'onlineStatus');
        this.initializePrivacyToggle('activity sharing', 'activityStatus');
        this.initializePrivacyToggle('usage analytics', 'dataCollection');
        this.initializePrivacyToggle('personalized content', 'marketingEmails');
    }

    // Initialize Notification Settings
    initializeNotificationSettings() {
        this.initializeNotificationToggle('browser notifications', 'pushNotifications');
        this.initializeNotificationToggle('sound alerts', 'soundEffects');
        this.initializeNotificationToggle('desktop notifications', 'desktopNotifications');
        this.initializeNotificationToggle('important updates', 'emailNotifications');
        this.initializeNotificationToggle('friend activity', 'inAppNotifications');
    }

    // Initialize Gaming Settings
    initializeGamingSettings() {
        this.initializeGamingToggle('voice chat', 'voiceChat');
        this.initializeGamingToggle('text chat', 'friendRequests');
        this.initializeGamingToggle('chat filter', 'partyInvites');
        this.initializeGamingToggle('show online friends', 'gameNotifications');
        this.initializeGamingToggle('friend invitations', 'autoJoinVoice');
        this.initializeGamingToggle('auto-join friends', 'crossPlatformPlay');

        // Streaming quality
        const qualitySelect = Array.from(document.querySelectorAll('#gaming-settings .setting-item'))
            .find(item => item.querySelector('h6')?.textContent.toLowerCase().includes('streaming quality'))
            ?.querySelector('.form-select');
        if (qualitySelect) {
            qualitySelect.value = this.settings.streamingQuality;
            qualitySelect.addEventListener('change', (e) => {
                this.settings.streamingQuality = e.target.value;
                this.saveSettings();
            });
        }
    }

    // Initialize Advanced Settings
    initializeAdvancedSettings() {
        this.initializeAdvancedToggle('hardware acceleration', 'autoUpdates');
        this.initializeAdvancedToggle('background processes', 'telemetry');
        this.initializeAdvancedToggle('developer mode', 'developerMode');
        this.initializeAdvancedToggle('console logging', 'betaFeatures');

        // Cache size
        const cacheSelect = Array.from(document.querySelectorAll('#advanced-settings .setting-item'))
            .find(item => item.querySelector('h6')?.textContent.toLowerCase().includes('cache size'))
            ?.querySelector('.form-select');
        if (cacheSelect) {
            cacheSelect.value = this.settings.cacheSize;
            cacheSelect.addEventListener('change', (e) => {
                this.settings.cacheSize = e.target.value;
                this.saveSettings();
            });
        }

        // Bandwidth limit
        const bandwidthSelect = Array.from(document.querySelectorAll('#advanced-settings .setting-item'))
            .find(item => item.querySelector('h6')?.textContent.toLowerCase().includes('bandwidth limit'))
            ?.querySelector('.form-select');
        if (bandwidthSelect) {
            bandwidthSelect.value = this.settings.bandwidthLimit;
            bandwidthSelect.addEventListener('change', (e) => {
                this.settings.bandwidthLimit = e.target.value;
                this.saveSettings();
            });
        }
    }

    // Helper methods for toggle initialization
    initializeToggle(settingLabel, settingKey, callback) {
        const toggle = Array.from(document.querySelectorAll('.setting-item'))
            .find(item => item.querySelector('h6')?.textContent.toLowerCase().includes(settingLabel.toLowerCase()))
            ?.querySelector('.switch input');
        if (toggle) {
            toggle.checked = this.settings[settingKey];
            toggle.addEventListener('change', (e) => {
                this.settings[settingKey] = e.target.checked;
                if (callback) callback();
                this.saveSettings();
            });
        }
    }

    initializePrivacyToggle(settingLabel, settingKey) {
        const toggle = Array.from(document.querySelectorAll('#privacy-settings .setting-item'))
            .find(item => item.querySelector('h6')?.textContent.toLowerCase().includes(settingLabel.toLowerCase()))
            ?.querySelector('.switch input');
        if (toggle) {
            toggle.checked = this.settings[settingKey];
            toggle.addEventListener('change', (e) => {
                this.settings[settingKey] = e.target.checked;
                this.saveSettings();
            });
        }
    }

    initializeNotificationToggle(settingLabel, settingKey) {
        const toggle = Array.from(document.querySelectorAll('#notifications-settings .setting-item'))
            .find(item => item.querySelector('h6')?.textContent.toLowerCase().includes(settingLabel.toLowerCase()))
            ?.querySelector('.switch input');
        if (toggle) {
            toggle.checked = this.settings[settingKey];
            toggle.addEventListener('change', (e) => {
                this.settings[settingKey] = e.target.checked;
                this.saveSettings();
            });
        }
    }

    initializeGamingToggle(settingLabel, settingKey) {
        const toggle = Array.from(document.querySelectorAll('#gaming-settings .setting-item'))
            .find(item => item.querySelector('h6')?.textContent.toLowerCase().includes(settingLabel.toLowerCase()))
            ?.querySelector('.switch input');
        if (toggle) {
            toggle.checked = this.settings[settingKey];
            toggle.addEventListener('change', (e) => {
                this.settings[settingKey] = e.target.checked;
                this.saveSettings();
            });
        }
    }

    initializeAdvancedToggle(settingLabel, settingKey) {
        const toggle = Array.from(document.querySelectorAll('#advanced-settings .setting-item'))
            .find(item => item.querySelector('h6')?.textContent.toLowerCase().includes(settingLabel.toLowerCase()))
            ?.querySelector('.switch input');
        if (toggle) {
            toggle.checked = this.settings[settingKey];
            toggle.addEventListener('change', (e) => {
                this.settings[settingKey] = e.target.checked;
                this.saveSettings();
            });
        }
    }

    // Initialize account management buttons
    initializeAccountManagementButtons() {
        // Change username button
        const changeUsernameBtn = Array.from(document.querySelectorAll('#account-settings .setting-item'))
            .find(item => item.querySelector('h6')?.textContent.toLowerCase().includes('username'))
            ?.querySelector('.btn-secondary');
        if (changeUsernameBtn) {
            changeUsernameBtn.addEventListener('click', () => {
                this.showUsernameChangeModal();
            });
        }

        // Change email button
        const changeEmailBtn = Array.from(document.querySelectorAll('#account-settings .setting-item'))
            .find(item => item.querySelector('h6')?.textContent.toLowerCase().includes('email'))
            ?.querySelector('.btn-secondary');
        if (changeEmailBtn) {
            changeEmailBtn.addEventListener('click', () => {
                this.showEmailChangeModal();
            });
        }

        // Manage subscription button
        const manageSubBtn = Array.from(document.querySelectorAll('#account-settings .setting-item'))
            .find(item => item.querySelector('h6')?.textContent.toLowerCase().includes('subscription'))
            ?.querySelector('.btn-secondary');
        if (manageSubBtn) {
            manageSubBtn.addEventListener('click', () => {
                this.showSubscriptionModal();
            });
        }

        // Delete account button
        const deleteAccountBtn = Array.from(document.querySelectorAll('#account-settings .setting-item'))
            .find(item => item.querySelector('h6')?.textContent.toLowerCase().includes('delete account'))
            ?.querySelector('.btn-danger');
        if (deleteAccountBtn) {
            deleteAccountBtn.addEventListener('click', () => {
                this.showDeleteAccountConfirmation();
            });
        }
    }

    // Apply theme
    applyTheme(theme) {
        const body = document.body;
        body.className = ''; // Remove all theme classes
        
        switch(theme) {
            case 'dark':
                body.classList.add('theme-dark');
                break;
            case 'light':
                body.classList.add('theme-light');
                break;
            case 'ocean':
                body.classList.add('theme-ocean');
                break;
            case 'forest':
                body.classList.add('theme-forest');
                break;
            case 'sunset':
                body.classList.add('theme-sunset');
                break;
            case 'galaxy':
                body.classList.add('theme-galaxy');
                break;
        }
    }

    // Update active theme button
    updateActiveTheme() {
        const themeOptions = document.querySelectorAll('.theme-option');
        themeOptions.forEach(option => {
            if (option.getAttribute('data-theme') === this.settings.theme) {
                option.classList.add('active');
            } else {
                option.classList.remove('active');
            }
        });
    }

    // Apply accent color
    applyAccentColor(color) {
        document.documentElement.style.setProperty('--accent-color', color);
    }

    // Apply font size
    applyFontSize(size) {
        document.documentElement.style.setProperty('--font-size-multiplier', this.getFontSizeMultiplier(size));
    }

    getFontSizeMultiplier(size) {
        switch(size) {
            case 'small': return '0.875';
            case 'medium': return '1';
            case 'large': return '1.125';
            case 'extra large': return '1.25';
            default: return '1';
        }
    }

    // Apply animations
    applyAnimations() {
        if (this.settings.animations) {
            document.body.classList.remove('no-animations');
        } else {
            document.body.classList.add('no-animations');
        }
    }

    // Apply background effects
    applyBackgroundEffects() {
        if (this.settings.backgroundEffects) {
            document.body.classList.add('background-effects');
        } else {
            document.body.classList.remove('background-effects');
        }
    }

    // Apply reduce motion
    applyReduceMotion() {
        if (this.settings.reduceMotion) {
            document.body.classList.add('reduce-motion');
        } else {
            document.body.classList.remove('reduce-motion');
        }
    }

    // Apply sidebar position
    applySidebarPosition(position) {
        const layout = document.querySelector('.dashboard-layout');
        if (layout) {
            layout.classList.remove('sidebar-left', 'sidebar-right');
            layout.classList.add(`sidebar-${position}`);
        }
    }

    // Apply compact mode
    applyCompactMode() {
        if (this.settings.compactMode) {
            document.body.classList.add('compact-mode');
        } else {
            document.body.classList.remove('compact-mode');
        }
    }

    // Apply date format
    applyDateFormat(format) {
        // This would update date displays throughout the app
        console.log('Date format changed to:', format);
    }

    // Apply time format
    applyTimeFormat(format) {
        // This would update time displays throughout the app
        console.log('Time format changed to:', format);
    }

    // Show notification
    showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.textContent = message;
        
        // Add to page
        document.body.appendChild(notification);
        
        // Remove after 3 seconds
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }

    // Show username change modal
    showUsernameChangeModal() {
        const newUsername = prompt('Enter new username:', this.settings.username);
        if (newUsername && newUsername !== this.settings.username) {
            this.settings.username = newUsername;
            this.saveSettings();
            this.showNotification('Username updated successfully!', 'success');
        }
    }

    // Show email change modal
    showEmailChangeModal() {
        const newEmail = prompt('Enter new email:', this.settings.email);
        if (newEmail && newEmail !== this.settings.email) {
            this.settings.email = newEmail;
            this.saveSettings();
            this.showNotification('Email updated successfully!', 'success');
        }
    }

    // Show subscription modal
    showSubscriptionModal() {
        this.showNotification('Subscription management coming soon!', 'info');
    }

    // Show delete account confirmation
    showDeleteAccountConfirmation() {
        if (confirm('Are you sure you want to delete your account? This action cannot be undone.')) {
            if (confirm('Please confirm again: This will permanently delete all your data.')) {
                this.showNotification('Account deletion initiated. You will receive a confirmation email.', 'warning');
            }
        }
    }

    // Initialize Currency Settings
    initializeCurrencySettings() {
        // Currency notifications toggle
        this.initializeToggle('currencyNotifications', 'Currency Notifications', (value) => {
            if (window.currencyManager) {
                window.currencyManager.notificationsEnabled = value;
            }
        });

        // Daily bonus reminder toggle
        this.initializeToggle('dailyBonusReminder', 'Daily Bonus Reminder', (value) => {
            if (window.currencyManager) {
                window.currencyManager.dailyBonusReminder = value;
            }
        });

        // Transaction history toggle
        this.initializeToggle('transactionHistory', 'Transaction History', (value) => {
            if (window.currencyManager) {
                window.currencyManager.transactionHistoryEnabled = value;
            }
        });

        // Currency sound effects toggle
        this.initializeToggle('currencySoundEffects', 'Currency Sound Effects', (value) => {
            if (window.currencyManager) {
                window.currencyManager.soundEffectsEnabled = value;
            }
        });

        // Add currency reset button functionality
        const resetCurrencyBtn = document.getElementById('resetCurrencyBtn');
        if (resetCurrencyBtn) {
            resetCurrencyBtn.addEventListener('click', () => {
                if (confirm('Are you sure you want to reset all currency data? This action cannot be undone.')) {
                    if (window.currencyManager) {
                        window.currencyManager.resetCurrencies();
                        this.showNotification('Currency data reset successfully!', 'success');
                    }
                }
            });
        }

        // Add currency export button functionality
        const exportCurrencyBtn = document.getElementById('exportCurrencyBtn');
        if (exportCurrencyBtn) {
            exportCurrencyBtn.addEventListener('click', () => {
                if (window.currencyManager) {
                    const data = {
                        currencies: window.currencyManager.currencies,
                        transactions: window.currencyManager.transactions,
                        lastDailyBonus: window.currencyManager.lastDailyBonus
                    };
                    
                    const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = 'currency-data.json';
                    a.click();
                    URL.revokeObjectURL(url);
                    
                    this.showNotification('Currency data exported successfully!', 'success');
                }
            });
        }
    }

    // Bind events
    bindEvents() {
        // Save settings on page unload
        window.addEventListener('beforeunload', () => {
            this.saveSettings();
        });
    }
}

// Initialize settings when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Check if we're on the settings page
    if (document.getElementById('settings-section')) {
        window.settingsManager = new SettingsManager();
    }
});

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = SettingsManager;
}
