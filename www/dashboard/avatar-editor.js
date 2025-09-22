class AvatarEditor {
    constructor() {
        this.avatarData = this.loadAvatarData();
        this.currentTab = 'body';
        this.previewRotation = 0;
        this.isDragging = false;
        this.lastMouseX = 0;
        
        // Initialize enhanced renderer
        this.renderer = new AvatarRenderer('avatarCanvas');
        this.renderer.setAvatarData(this.avatarData);
        this.renderer.currentPose = this.avatarData.animations.currentPose || 'idle';
        
        // Initialize thumbnail service
        if (typeof AvatarThumbnailService !== 'undefined') {
            window.avatarThumbnailService = new AvatarThumbnailService();
        }
        
        this.initializeAvatarEditor();
        this.bindEvents();
    }

    // Load avatar data from localStorage
    loadAvatarData() {
        const saved = localStorage.getItem('avatar_data');
        return saved ? JSON.parse(saved) : this.getDefaultAvatarData();
    }

    // Get default avatar data
    getDefaultAvatarData() {
        return {
            body: {
                headColor: '#FDBCB4',
                torsoColor: '#0066CC',
                leftArmColor: '#FDBCB4',
                rightArmColor: '#FDBCB4',
                leftLegColor: '#0066CC',
                rightLegColor: '#0066CC'
            },
            clothing: {
                shirt: null,
                pants: null,
                accessories: []
            },
            face: {
                type: 'smile',
                eyes: 'normal',
                eyebrows: 'normal'
            },
            animations: {
                currentPose: 'idle'
            }
        };
    }

    // Save avatar data to localStorage
    saveAvatarData() {
        localStorage.setItem('avatar_data', JSON.stringify(this.avatarData));
    }

    // Initialize avatar editor
    initializeAvatarEditor() {
        this.createAvatarEditorModal();
        this.updateAvatarPreview();
    }

    // Create avatar editor modal
    createAvatarEditorModal() {
        const modal = document.createElement('div');
        modal.className = 'avatar-editor-modal';
        modal.innerHTML = `
            <div class="avatar-editor-container">
                <div class="avatar-editor-header">
                    <h2>Avatar Editor</h2>
                    <button class="close-btn" id="closeAvatarEditor">
                        <span class="iconify" data-icon="mdi:close"></span>
                    </button>
                </div>

                <div class="avatar-editor-content">
                    <!-- Avatar Preview -->
                    <div class="avatar-preview-section">
                        <div class="avatar-preview-container">
                            <canvas id="avatarCanvas" width="300" height="400"></canvas>
                            <div class="preview-controls">
                                <button class="preview-btn" id="rotateLeftBtn">
                                    <span class="iconify" data-icon="mdi:rotate-left"></span>
                                </button>
                                <button class="preview-btn" id="rotateRightBtn">
                                    <span class="iconify" data-icon="mdi:rotate-right"></span>
                                </button>
                                <button class="preview-btn" id="resetPoseBtn">
                                    <span class="iconify" data-icon="mdi:refresh"></span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Customization Panel -->
                    <div class="customization-panel">
                        <!-- Tabs -->
                        <div class="avatar-tabs">
                            <button class="avatar-tab active" data-tab="body">
                                <span class="iconify" data-icon="mdi:account"></span>
                                Body
                            </button>
                            <button class="avatar-tab" data-tab="clothing">
                                <span class="iconify" data-icon="mdi:tshirt-crew"></span>
                                Clothing
                            </button>
                            <button class="avatar-tab" data-tab="face">
                                <span class="iconify" data-icon="mdi:emoticon"></span>
                                Face
                            </button>
                            <button class="avatar-tab" data-tab="animations">
                                <span class="iconify" data-icon="mdi:motion-play"></span>
                                Animations
                            </button>
                        </div>

                        <!-- Tab Content -->
                        <div class="tab-content">
                            <!-- Body Tab -->
                            <div class="tab-pane active" id="body-tab">
                                <div class="customization-group">
                                    <h4>Body Colors</h4>
                                    <div class="color-options">
                                        <div class="color-option">
                                            <label>Head</label>
                                            <input type="color" id="headColor" value="${this.avatarData.body.headColor}">
                                        </div>
                                        <div class="color-option">
                                            <label>Torso</label>
                                            <input type="color" id="torsoColor" value="${this.avatarData.body.torsoColor}">
                                        </div>
                                        <div class="color-option">
                                            <label>Left Arm</label>
                                            <input type="color" id="leftArmColor" value="${this.avatarData.body.leftArmColor}">
                                        </div>
                                        <div class="color-option">
                                            <label>Right Arm</label>
                                            <input type="color" id="rightArmColor" value="${this.avatarData.body.rightArmColor}">
                                        </div>
                                        <div class="color-option">
                                            <label>Left Leg</label>
                                            <input type="color" id="leftLegColor" value="${this.avatarData.body.leftLegColor}">
                                        </div>
                                        <div class="color-option">
                                            <label>Right Leg</label>
                                            <input type="color" id="rightLegColor" value="${this.avatarData.body.rightLegColor}">
                                        </div>
                                    </div>
                                </div>

                                <div class="customization-group">
                                    <h4>Preset Colors</h4>
                                    <div class="preset-colors">
                                        <button class="preset-color-btn" data-preset="default" style="background: linear-gradient(45deg, #FDBCB4, #0066CC)">Default</button>
                                        <button class="preset-color-btn" data-preset="red" style="background: linear-gradient(45deg, #FF6B6B, #CC0000)">Red</button>
                                        <button class="preset-color-btn" data-preset="blue" style="background: linear-gradient(45deg, #4ECDC4, #0066FF)">Blue</button>
                                        <button class="preset-color-btn" data-preset="green" style="background: linear-gradient(45deg, #95E1D3, #00AA00)">Green</button>
                                        <button class="preset-color-btn" data-preset="purple" style="background: linear-gradient(45deg, #C7CEEA, #8B5CF6)">Purple</button>
                                        <button class="preset-color-btn" data-preset="orange" style="background: linear-gradient(45deg, #FFD93D, #FF8C00)">Orange</button>
                                    </div>
                                </div>
                            </div>

                            <!-- Clothing Tab -->
                            <div class="tab-pane" id="clothing-tab">
                                <div class="customization-group">
                                    <h4>Shirts</h4>
                                    <div class="clothing-grid">
                                        <div class="clothing-item ${this.avatarData.clothing.shirt === 'classic' ? 'selected' : ''}" data-item="classic" data-type="shirt">
                                            <div class="clothing-preview" style="background: #FF6B6B;">
                                                <span class="iconify" data-icon="mdi:tshirt-crew"></span>
                                            </div>
                                            <span>Classic Shirt</span>
                                            <span class="price">50 Credits</span>
                                        </div>
                                        <div class="clothing-item ${this.avatarData.clothing.shirt === 'striped' ? 'selected' : ''}" data-item="striped" data-type="shirt">
                                            <div class="clothing-preview" style="background: linear-gradient(45deg, #4ECDC4, #44A08D);">
                                                <span class="iconify" data-icon="mdi:tshirt-crew"></span>
                                            </div>
                                            <span>Striped Shirt</span>
                                            <span class="price">75 Credits</span>
                                        </div>
                                        <div class="clothing-item ${this.avatarData.clothing.shirt === 'hoodie' ? 'selected' : ''}" data-item="hoodie" data-type="shirt">
                                            <div class="clothing-preview" style="background: #8B5CF6;">
                                                <span class="iconify" data-icon="mdi:hoodie"></span>
                                            </div>
                                            <span>Hoodie</span>
                                            <span class="price">100 Credits</span>
                                        </div>
                                        <div class="clothing-item ${this.avatarData.clothing.shirt === 'jacket' ? 'selected' : ''}" data-item="jacket" data-type="shirt">
                                            <div class="clothing-preview" style="background: #2D3748;">
                                                <span class="iconify" data-icon="mdi:jacket-athlete"></span>
                                            </div>
                                            <span>Jacket</span>
                                            <span class="price">150 Credits</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="customization-group">
                                    <h4>Pants</h4>
                                    <div class="clothing-grid">
                                        <div class="clothing-item ${this.avatarData.clothing.pants === 'classic' ? 'selected' : ''}" data-item="classic" data-type="pants">
                                            <div class="clothing-preview" style="background: #4A5568;">
                                                <span class="iconify" data-icon="mdi:human"></span>
                                            </div>
                                            <span>Classic Pants</span>
                                            <span class="price">50 Credits</span>
                                        </div>
                                        <div class="clothing-item ${this.avatarData.clothing.pants === 'jeans' ? 'selected' : ''}" data-item="jeans" data-type="pants">
                                            <div class="clothing-preview" style="background: #1A365D;">
                                                <span class="iconify" data-icon="mdi:human"></span>
                                            </div>
                                            <span>Jeans</span>
                                            <span class="price">75 Credits</span>
                                        </div>
                                        <div class="clothing-item ${this.avatarData.clothing.pants === 'shorts' ? 'selected' : ''}" data-item="shorts" data-type="pants">
                                            <div class="clothing-preview" style="background: #2B6CB0;">
                                                <span class="iconify" data-icon="mdi:human"></span>
                                            </div>
                                            <span>Shorts</span>
                                            <span class="price">40 Credits</span>
                                        </div>
                                        <div class="clothing-item ${this.avatarData.clothing.pants === 'cargo' ? 'selected' : ''}" data-item="cargo" data-type="pants">
                                            <div class="clothing-preview" style="background: #744210;">
                                                <span class="iconify" data-icon="mdi:human"></span>
                                            </div>
                                            <span>Cargo Pants</span>
                                            <span class="price">100 Credits</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="customization-group">
                                    <h4>Accessories</h4>
                                    <div class="clothing-grid">
                                        <div class="clothing-item ${this.avatarData.clothing.accessories.includes('hat') ? 'selected' : ''}" data-item="hat" data-type="accessory">
                                            <div class="clothing-preview" style="background: #DC2626;">
                                                <span class="iconify" data-icon="mdi:hat-fedora"></span>
                                            </div>
                                            <span>Fedora Hat</span>
                                            <span class="price">25 Credits</span>
                                        </div>
                                        <div class="clothing-item ${this.avatarData.clothing.accessories.includes('glasses') ? 'selected' : ''}" data-item="glasses" data-type="accessory">
                                            <div class="clothing-preview" style="background: #1F2937;">
                                                <span class="iconify" data-icon="mdi:glasses"></span>
                                            </div>
                                            <span>Glasses</span>
                                            <span class="price">30 Credits</span>
                                        </div>
                                        <div class="clothing-item ${this.avatarData.clothing.accessories.includes('watch') ? 'selected' : ''}" data-item="watch" data-type="accessory">
                                            <div class="clothing-preview" style="background: #92400E;">
                                                <span class="iconify" data-icon="mdi:watch"></span>
                                            </div>
                                            <span>Watch</span>
                                            <span class="price">35 Credits</span>
                                        </div>
                                        <div class="clothing-item ${this.avatarData.clothing.accessories.includes('backpack') ? 'selected' : ''}" data-item="backpack" data-type="accessory">
                                            <div class="clothing-preview" style="background: #059669;">
                                                <span class="iconify" data-icon="mdi:backpack"></span>
                                            </div>
                                            <span>Backpack</span>
                                            <span class="price">60 Credits</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Face Tab -->
                            <div class="tab-pane" id="face-tab">
                                <div class="customization-group">
                                    <h4>Face Type</h4>
                                    <div class="face-options">
                                        <div class="face-option ${this.avatarData.face.type === 'smile' ? 'selected' : ''}" data-face="smile">
                                            <div class="face-preview">😊</div>
                                            <span>Smile</span>
                                        </div>
                                        <div class="face-option ${this.avatarData.face.type === 'neutral' ? 'selected' : ''}" data-face="neutral">
                                            <div class="face-preview">😐</div>
                                            <span>Neutral</span>
                                        </div>
                                        <div class="face-option ${this.avatarData.face.type === 'cool' ? 'selected' : ''}" data-face="cool">
                                            <div class="face-preview">😎</div>
                                            <span>Cool</span>
                                        </div>
                                        <div class="face-option ${this.avatarData.face.type === 'wink' ? 'selected' : ''}" data-face="wink">
                                            <div class="face-preview">😉</div>
                                            <span>Wink</span>
                                        </div>
                                        <div class="face-option ${this.avatarData.face.type === 'laugh' ? 'selected' : ''}" data-face="laugh">
                                            <div class="face-preview">😄</div>
                                            <span>Laugh</span>
                                        </div>
                                        <div class="face-option ${this.avatarData.face.type === 'surprised' ? 'selected' : ''}" data-face="surprised">
                                            <div class="face-preview">😮</div>
                                            <span>Surprised</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="customization-group">
                                    <h4>Eyes</h4>
                                    <div class="face-options">
                                        <div class="face-option ${this.avatarData.face.eyes === 'normal' ? 'selected' : ''}" data-eyes="normal">
                                            <div class="face-preview">👁️</div>
                                            <span>Normal</span>
                                        </div>
                                        <div class="face-option ${this.avatarData.face.eyes === 'wide' ? 'selected' : ''}" data-eyes="wide">
                                            <div class="face-preview">👀</div>
                                            <span>Wide</span>
                                        </div>
                                        <div class="face-option ${this.avatarData.face.eyes === 'closed' ? 'selected' : ''}" data-eyes="closed">
                                            <div class="face-preview">😌</div>
                                            <span>Closed</span>
                                        </div>
                                        <div class="face-option ${this.avatarData.face.eyes === 'wink' ? 'selected' : ''}" data-eyes="wink">
                                            <div class="face-preview">😏</div>
                                            <span>Wink</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="customization-group">
                                    <h4>Eyebrows</h4>
                                    <div class="face-options">
                                        <div class="face-option ${this.avatarData.face.eyebrows === 'normal' ? 'selected' : ''}" data-eyebrows="normal">
                                            <div class="face-preview">〰️</div>
                                            <span>Normal</span>
                                        </div>
                                        <div class="face-option ${this.avatarData.face.eyebrows === 'raised' ? 'selected' : ''}" data-eyebrows="raised">
                                            <div class="face-preview">⤴️</div>
                                            <span>Raised</span>
                                        </div>
                                        <div class="face-option ${this.avatarData.face.eyebrows === 'furrowed' ? 'selected' : ''}" data-eyebrows="furrowed">
                                            <div class="face-preview">⤵️</div>
                                            <span>Furrowed</span>
                                        </div>
                                        <div class="face-option ${this.avatarData.face.eyebrows === 'angled' ? 'selected' : ''}" data-eyebrows="angled">
                                            <div class="face-preview">📐</div>
                                            <span>Angled</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Animations Tab -->
                            <div class="tab-pane" id="animations-tab">
                                <div class="customization-group">
                                    <h4>Poses</h4>
                                    <div class="animation-options">
                                        <div class="animation-option ${this.avatarData.animations.currentPose === 'idle' ? 'selected' : ''}" data-pose="idle">
                                            <div class="animation-preview">
                                                <span class="iconify" data-icon="mdi:human"></span>
                                            </div>
                                            <span>Idle</span>
                                        </div>
                                        <div class="animation-option ${this.avatarData.animations.currentPose === 'wave' ? 'selected' : ''}" data-pose="wave">
                                            <div class="animation-preview">
                                                <span class="iconify" data-icon="mdi:hand-wave"></span>
                                            </div>
                                            <span>Wave</span>
                                        </div>
                                        <div class="animation-option ${this.avatarData.animations.currentPose === 'dance' ? 'selected' : ''}" data-pose="dance">
                                            <div class="animation-preview">
                                                <span class="iconify" data-icon="mdi:music-note"></span>
                                            </div>
                                            <span>Dance</span>
                                        </div>
                                        <div class="animation-option ${this.avatarData.animations.currentPose === 'jump' ? 'selected' : ''}" data-pose="jump">
                                            <div class="animation-preview">
                                                <span class="iconify" data-icon="mdi:arrow-up-bold"></span>
                                            </div>
                                            <span>Jump</span>
                                        </div>
                                        <div class="animation-option ${this.avatarData.animations.currentPose === 'sit' ? 'selected' : ''}" data-pose="sit">
                                            <div class="animation-preview">
                                                <span class="iconify" data-icon="mdi:chair-rolling"></span>
                                            </div>
                                            <span>Sit</span>
                                        </div>
                                        <div class="animation-option ${this.avatarData.animations.currentPose === 'run' ? 'selected' : ''}" data-pose="run">
                                            <div class="animation-preview">
                                                <span class="iconify" data-icon="mdi:run"></span>
                                            </div>
                                            <span>Run</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="avatar-editor-footer">
                    <button class="btn btn-secondary" id="resetAvatarBtn">Reset to Default</button>
                    <button class="btn btn-info" id="thumbnailBtn">Thumbnails</button>
                    <button class="btn btn-primary" id="saveAvatarBtn">Save Avatar</button>
                </div>
            </div>
        `;

        document.body.appendChild(modal);
        this.modal = modal;
        this.canvas = document.getElementById('avatarCanvas');
        this.ctx = this.canvas.getContext('2d');
    }

    // Bind events
    bindEvents() {
        // Close button
        document.getElementById('closeAvatarEditor').addEventListener('click', () => {
            this.closeAvatarEditor();
        });

        // Tab switching
        document.querySelectorAll('.avatar-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                this.switchTab(tab.dataset.tab);
            });
        });

        // Color inputs
        ['headColor', 'torsoColor', 'leftArmColor', 'rightArmColor', 'leftLegColor', 'rightLegColor'].forEach(colorId => {
            const input = document.getElementById(colorId);
            input.addEventListener('input', (e) => {
                const bodyPart = colorId.replace('Color', '');
                this.avatarData.body[bodyPart] = e.target.value;
                this.updateAvatarPreview();
            });
        });

        // Preset colors
        document.querySelectorAll('.preset-color-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                this.applyPresetColors(btn.dataset.preset);
            });
        });

        // Clothing items
        document.querySelectorAll('.clothing-item').forEach(item => {
            item.addEventListener('click', () => {
                this.selectClothingItem(item.dataset.item, item.dataset.type);
            });
        });

        // Face options
        document.querySelectorAll('.face-option').forEach(option => {
            option.addEventListener('click', () => {
                if (option.dataset.face) {
                    this.avatarData.face.type = option.dataset.face;
                } else if (option.dataset.eyes) {
                    this.avatarData.face.eyes = option.dataset.eyes;
                } else if (option.dataset.eyebrows) {
                    this.avatarData.face.eyebrows = option.dataset.eyebrows;
                }
                this.updateFaceSelection();
                this.updateAvatarPreview();
            });
        });

        // Animation options
        document.querySelectorAll('.animation-option').forEach(option => {
            option.addEventListener('click', () => {
                this.avatarData.animations.currentPose = option.dataset.pose;
                this.updateAnimationSelection();
                this.updateAvatarPreview();
            });
        });

        // Preview controls
        document.getElementById('rotateLeftBtn').addEventListener('click', () => {
            this.previewRotation -= 15;
            this.updateAvatarPreview();
        });

        document.getElementById('rotateRightBtn').addEventListener('click', () => {
            this.previewRotation += 15;
            this.updateAvatarPreview();
        });

        document.getElementById('resetPoseBtn').addEventListener('click', () => {
            this.previewRotation = 0;
            this.updateAvatarPreview();
        });

        // Canvas drag rotation
        this.canvas.addEventListener('mousedown', (e) => {
            this.isDragging = true;
            this.lastMouseX = e.clientX;
        });

        document.addEventListener('mousemove', (e) => {
            if (this.isDragging) {
                const deltaX = e.clientX - this.lastMouseX;
                this.previewRotation += deltaX * 0.5;
                this.lastMouseX = e.clientX;
                this.updateAvatarPreview();
            }
        });

        document.addEventListener('mouseup', () => {
            this.isDragging = false;
        });

        // Save and reset buttons
        document.getElementById('saveAvatarBtn').addEventListener('click', () => {
            this.saveAvatar();
        });

        document.getElementById('resetAvatarBtn').addEventListener('click', () => {
            if (confirm('Are you sure you want to reset your avatar to default?')) {
                this.resetAvatar();
            }
        });

        // Thumbnail button
        const thumbnailBtn = document.getElementById('thumbnailBtn');
        if (thumbnailBtn) {
            thumbnailBtn.addEventListener('click', () => {
                this.showThumbnailOptions();
            });
        }
    }

    // Switch tab
    switchTab(tabName) {
        this.currentTab = tabName;
        
        // Update tab buttons
        document.querySelectorAll('.avatar-tab').forEach(tab => {
            tab.classList.remove('active');
        });
        document.querySelector(`[data-tab="${tabName}"]`).classList.add('active');

        // Update tab content
        document.querySelectorAll('.tab-pane').forEach(pane => {
            pane.classList.remove('active');
        });
        document.getElementById(`${tabName}-tab`).classList.add('active');
    }

    // Apply preset colors
    applyPresetColors(preset) {
        const presets = {
            default: {
                headColor: '#FDBCB4',
                torsoColor: '#0066CC',
                leftArmColor: '#FDBCB4',
                rightArmColor: '#FDBCB4',
                leftLegColor: '#0066CC',
                rightLegColor: '#0066CC'
            },
            red: {
                headColor: '#FF6B6B',
                torsoColor: '#CC0000',
                leftArmColor: '#FF6B6B',
                rightArmColor: '#FF6B6B',
                leftLegColor: '#CC0000',
                rightLegColor: '#CC0000'
            },
            blue: {
                headColor: '#4ECDC4',
                torsoColor: '#0066FF',
                leftArmColor: '#4ECDC4',
                rightArmColor: '#4ECDC4',
                leftLegColor: '#0066FF',
                rightLegColor: '#0066FF'
            },
            green: {
                headColor: '#95E1D3',
                torsoColor: '#00AA00',
                leftArmColor: '#95E1D3',
                rightArmColor: '#95E1D3',
                leftLegColor: '#00AA00',
                rightLegColor: '#00AA00'
            },
            purple: {
                headColor: '#C7CEEA',
                torsoColor: '#8B5CF6',
                leftArmColor: '#C7CEEA',
                rightArmColor: '#C7CEEA',
                leftLegColor: '#8B5CF6',
                rightLegColor: '#8B5CF6'
            },
            orange: {
                headColor: '#FFD93D',
                torsoColor: '#FF8C00',
                leftArmColor: '#FFD93D',
                rightArmColor: '#FFD93D',
                leftLegColor: '#FF8C00',
                rightLegColor: '#FF8C00'
            }
        };

        if (presets[preset]) {
            Object.assign(this.avatarData.body, presets[preset]);
            
            // Update color inputs
            Object.keys(presets[preset]).forEach(key => {
                const input = document.getElementById(key);
                if (input) {
                    input.value = presets[preset][key];
                }
            });

            this.updateAvatarPreview();
        }
    }

    // Select clothing item
    selectClothingItem(item, type) {
        const prices = {
            classic: 50,
            striped: 75,
            hoodie: 100,
            jacket: 150,
            jeans: 75,
            shorts: 40,
            cargo: 100,
            hat: 25,
            glasses: 30,
            watch: 35,
            backpack: 60
        };

        const price = prices[item] || 0;

        if (window.currencyManager && window.currencyManager.canAfford('credits', price)) {
            if (type === 'shirt') {
                this.avatarData.clothing.shirt = item;
            } else if (type === 'pants') {
                this.avatarData.clothing.pants = item;
            } else if (type === 'accessory') {
                const index = this.avatarData.clothing.accessories.indexOf(item);
                if (index > -1) {
                    this.avatarData.clothing.accessories.splice(index, 1);
                } else {
                    this.avatarData.clothing.accessories.push(item);
                }
            }

            // Deduct currency
            if (price > 0 && window.currencyManager) {
                window.currencyManager.addCurrency('credits', -price, `Avatar ${type}: ${item}`);
            }

            this.updateClothingSelection();
            this.updateAvatarPreview();
        } else if (price > 0) {
            alert(`You need ${price} Credits to purchase this item!`);
        }
    }

    // Update clothing selection
    updateClothingSelection() {
        document.querySelectorAll('.clothing-item').forEach(item => {
            const itemType = item.dataset.type;
            const itemName = item.dataset.item;

            if (itemType === 'shirt') {
                item.classList.toggle('selected', this.avatarData.clothing.shirt === itemName);
            } else if (itemType === 'pants') {
                item.classList.toggle('selected', this.avatarData.clothing.pants === itemName);
            } else if (itemType === 'accessory') {
                item.classList.toggle('selected', this.avatarData.clothing.accessories.includes(itemName));
            }
        });
    }

    // Update face selection
    updateFaceSelection() {
        document.querySelectorAll('.face-option').forEach(option => {
            if (option.dataset.face) {
                option.classList.toggle('selected', this.avatarData.face.type === option.dataset.face);
            } else if (option.dataset.eyes) {
                option.classList.toggle('selected', this.avatarData.face.eyes === option.dataset.eyes);
            } else if (option.dataset.eyebrows) {
                option.classList.toggle('selected', this.avatarData.face.eyebrows === option.dataset.eyebrows);
            }
        });
    }

    // Update animation selection
    updateAnimationSelection() {
        document.querySelectorAll('.animation-option').forEach(option => {
            option.classList.toggle('selected', this.avatarData.animations.currentPose === option.dataset.pose);
        });
    }

    // Update avatar preview
    updateAvatarPreview() {
        if (this.renderer) {
            this.renderer.setRotation(this.previewRotation);
            this.renderer.setAvatarData(this.avatarData);
        }
    }

    // Draw avatar
    drawAvatar(ctx) {
        const centerX = this.canvas.width / 2;
        const pose = this.avatarData.animations.currentPose;

        // Body dimensions
        const headSize = 40;
        const torsoWidth = 30;
        const torsoHeight = 50;
        const armWidth = 12;
        const armHeight = 40;
        const legWidth = 15;
        const legHeight = 45;

        // Calculate positions based on pose
        let headY = 80;
        let torsoY = headY + headSize;
        let armY = torsoY + 10;
        let legY = torsoY + torsoHeight;

        // Apply pose transformations
        if (pose === 'wave') {
            // Raise right arm for waving
            this.drawLimb(ctx, centerX + torsoWidth/2 + 5, armY - 20, armWidth, armHeight - 10, this.avatarData.body.rightArmColor, 'rightArm');
        } else if (pose === 'jump') {
            // Move everything up for jump
            headY -= 20;
            torsoY -= 20;
            armY -= 20;
            legY -= 20;
        } else if (pose === 'sit') {
            // Adjust for sitting position
            legY += 20;
            legHeight -= 20;
        }

        // Draw legs
        this.drawLimb(ctx, centerX - legWidth/2 - 5, legY, legWidth, legHeight, this.avatarData.body.leftLegColor, 'leftLeg');
        this.drawLimb(ctx, centerX + legWidth/2 + 5, legY, legWidth, legHeight, this.avatarData.body.rightLegColor, 'rightLeg');

        // Draw torso
        this.drawTorso(ctx, centerX - torsoWidth/2, torsoY, torsoWidth, torsoHeight, this.avatarData.body.torsoColor);

        // Draw arms
        if (pose !== 'wave') {
            this.drawLimb(ctx, centerX - torsoWidth/2 - 5, armY, armWidth, armHeight, this.avatarData.body.leftArmColor, 'leftArm');
            this.drawLimb(ctx, centerX + torsoWidth/2 + 5, armY, armWidth, armHeight, this.avatarData.body.rightArmColor, 'rightArm');
        }

        // Draw head
        this.drawHead(ctx, centerX - headSize/2, headY - headSize, headSize, headSize, this.avatarData.body.headColor);

        // Draw clothing
        this.drawClothing(ctx, centerX, torsoY, torsoWidth, torsoHeight);

        // Draw accessories
        this.drawAccessories(ctx, centerX, headY - headSize, headSize);

        // Draw face
        this.drawFace(ctx, centerX, headY - headSize/2, headSize);
    }

    // Draw head
    drawHead(ctx, x, y, width, height, color) {
        ctx.fillStyle = color;
        ctx.fillRect(x, y, width, height);
        
        // Add subtle border
        ctx.strokeStyle = '#000';
        ctx.lineWidth = 1;
        ctx.strokeRect(x, y, width, height);
    }

    // Draw torso
    drawTorso(ctx, x, y, width, height, color) {
        ctx.fillStyle = color;
        ctx.fillRect(x, y, width, height);
        
        // Add subtle border
        ctx.strokeStyle = '#000';
        ctx.lineWidth = 1;
        ctx.strokeRect(x, y, width, height);
    }

    // Draw limb (arm or leg)
    drawLimb(ctx, x, y, width, height, color, type) {
        ctx.fillStyle = color;
        ctx.fillRect(x, y, width, height);
        
        // Add subtle border
        ctx.strokeStyle = '#000';
        ctx.lineWidth = 1;
        ctx.strokeRect(x, y, width, height);
    }

    // Draw clothing
    drawClothing(ctx, centerX, torsoY, torsoWidth, torsoHeight) {
        // Draw shirt
        if (this.avatarData.clothing.shirt) {
            ctx.fillStyle = this.getShirtColor();
            ctx.fillRect(centerX - torsoWidth/2 - 2, torsoY, torsoWidth + 4, torsoHeight - 10);
        }

        // Draw pants
        if (this.avatarData.clothing.pants) {
            ctx.fillStyle = this.getPantsColor();
            ctx.fillRect(centerX - torsoWidth/2 - 2, torsoY + torsoHeight - 15, torsoWidth + 4, 25);
        }
    }

    // Get shirt color based on selected shirt
    getShirtColor() {
        const shirtColors = {
            classic: '#FF6B6B',
            striped: '#4ECDC4',
            hoodie: '#8B5CF6',
            jacket: '#2D3748'
        };
        return shirtColors[this.avatarData.clothing.shirt] || 'transparent';
    }

    // Get pants color based on selected pants
    getPantsColor() {
        const pantsColors = {
            classic: '#4A5568',
            jeans: '#1A365D',
            shorts: '#2B6CB0',
            cargo: '#744210'
        };
        return pantsColors[this.avatarData.clothing.pants] || 'transparent';
    }

    // Draw accessories
    drawAccessories(ctx, centerX, headY, headSize) {
        // Draw hat
        if (this.avatarData.clothing.accessories.includes('hat')) {
            ctx.fillStyle = '#DC2626';
            ctx.fillRect(centerX - headSize/2 - 5, headY - 10, headSize + 10, 8);
        }

        // Draw glasses
        if (this.avatarData.clothing.accessories.includes('glasses')) {
            ctx.strokeStyle = '#1F2937';
            ctx.lineWidth = 2;
            ctx.beginPath();
            ctx.arc(centerX - 8, headY + headSize/2 - 5, 6, 0, 2 * Math.PI);
            ctx.arc(centerX + 8, headY + headSize/2 - 5, 6, 0, 2 * Math.PI);
            ctx.stroke();
            
            // Bridge
            ctx.beginPath();
            ctx.moveTo(centerX - 2, headY + headSize/2 - 5);
            ctx.lineTo(centerX + 2, headY + headSize/2 - 5);
            ctx.stroke();
        }
    }

    // Draw face
    drawFace(ctx, centerX, centerY, headSize) {
        const faceType = this.avatarData.face.type;
        const eyesType = this.avatarData.face.eyes;

        // Draw eyes
        ctx.fillStyle = '#000';
        if (eyesType === 'normal') {
            ctx.fillRect(centerX - 12, centerY - 5, 4, 4);
            ctx.fillRect(centerX + 8, centerY - 5, 4, 4);
        } else if (eyesType === 'wide') {
            ctx.fillRect(centerX - 14, centerY - 6, 6, 6);
            ctx.fillRect(centerX + 8, centerY - 6, 6, 6);
        } else if (eyesType === 'closed') {
            ctx.strokeStyle = '#000';
            ctx.lineWidth = 2;
            ctx.beginPath();
            ctx.moveTo(centerX - 12, centerY - 3);
            ctx.lineTo(centerX - 8, centerY - 3);
            ctx.moveTo(centerX + 8, centerY - 3);
            ctx.lineTo(centerX + 12, centerY - 3);
            ctx.stroke();
        } else if (eyesType === 'wink') {
            ctx.fillRect(centerX - 12, centerY - 5, 4, 4);
            ctx.strokeStyle = '#000';
            ctx.lineWidth = 2;
            ctx.beginPath();
            ctx.moveTo(centerX + 8, centerY - 3);
            ctx.lineTo(centerX + 12, centerY - 3);
            ctx.stroke();
        }

        // Draw mouth based on face type
        ctx.strokeStyle = '#000';
        ctx.lineWidth = 2;
        ctx.beginPath();
        
        switch (faceType) {
            case 'smile':
                ctx.arc(centerX, centerY + 5, 8, 0, Math.PI);
                break;
            case 'neutral':
                ctx.moveTo(centerX - 8, centerY + 8);
                ctx.lineTo(centerX + 8, centerY + 8);
                break;
            case 'cool':
                ctx.moveTo(centerX - 8, centerY + 8);
                ctx.lineTo(centerX + 8, centerY + 8);
                // Add sunglasses
                ctx.fillStyle = '#000';
                ctx.fillRect(centerX - 15, centerY - 8, 12, 8);
                ctx.fillRect(centerX + 3, centerY - 8, 12, 8);
                break;
            case 'wink':
                ctx.arc(centerX, centerY + 5, 8, 0, Math.PI);
                break;
            case 'laugh':
                ctx.arc(centerX, centerY + 8, 12, 0, Math.PI);
                break;
            case 'surprised':
                ctx.arc(centerX, centerY + 10, 6, 0, 2 * Math.PI);
                break;
        }
        
        ctx.stroke();
    }

    // Save avatar
    saveAvatar() {
        this.saveAvatarData();
        
        // Update profile avatar
        const profileAvatar = document.getElementById('profileAvatar');
        if (profileAvatar) {
            profileAvatar.src = this.canvas.toDataURL('image/png');
        }

        // Show success message
        if (window.currencyManager) {
            window.currencyManager.showNotification('Avatar saved successfully!', 'success');
        }

        this.closeAvatarEditor();
    }

    async saveAvatarToProfile() {
        try {
            let thumbnail;
            
            if (window.avatarThumbnailService) {
                // Use enhanced thumbnail service
                thumbnail = await window.avatarThumbnailService.generateThumbnail(
                    this.avatarData, 
                    'profile', 
                    'png'
                );
                
                // Pregenerate additional thumbnails for better performance
                await window.avatarThumbnailService.pregenerateThumbnails(this.avatarData);
            } else if (this.renderer) {
                // Fallback to renderer thumbnail generation
                thumbnail = this.renderer.generateThumbnail(100, 100);
            } else {
                // Fallback to basic canvas
                const canvas = document.getElementById('avatarCanvas');
                if (!canvas) return;
                thumbnail = canvas.toDataURL('image/png');
            }
            
            // Update profile avatar
            const profileAvatar = document.getElementById('profileAvatar');
            if (profileAvatar) {
                profileAvatar.src = thumbnail;
            }
            
            // Save to localStorage
            localStorage.setItem('profile_avatar', thumbnail);
            
            // Export avatar data with thumbnails for future use
            if (window.avatarThumbnailService) {
                const exportData = await window.avatarThumbnailService.exportAvatarWithThumbnails(this.avatarData);
                localStorage.setItem('avatar_export_data', JSON.stringify(exportData));
            }
            
            this.showNotification('Avatar saved to profile!', 'success');
        } catch (error) {
            console.error('Failed to save avatar to profile:', error);
            this.showNotification('Failed to save avatar', 'error');
        }
    }

    // Reset avatar
    resetAvatar() {
        this.avatarData = this.getDefaultAvatarData();
        this.saveAvatarData();
        
        // Reset UI
        this.resetUI();
        this.updateAvatarPreview();
    }

    // Reset UI to default values
    resetUI() {
        // Reset color inputs
        Object.keys(this.avatarData.body).forEach(key => {
            const input = document.getElementById(key);
            if (input) {
                input.value = this.avatarData.body[key];
            }
        });

        // Reset selections
        this.updateClothingSelection();
        this.updateFaceSelection();
        this.updateAnimationSelection();
    }

    // Open avatar editor
    openAvatarEditor() {
        this.modal.style.display = 'flex';
        this.updateAvatarPreview();
        
        // Update selections
        this.updateClothingSelection();
        this.updateFaceSelection();
        this.updateAnimationSelection();
    }

    // Close avatar editor
    closeAvatarEditor() {
        this.modal.style.display = 'none';
    }

    // Handle pose selection
    handlePoseSelection(pose) {
        this.avatarData.animations.currentPose = pose;
        this.saveAvatarData();
        
        if (this.renderer) {
            this.renderer.setPose(pose);
        }
        
        // Show notification
        this.showNotification('Pose updated to ' + pose, 'success');
    }

    // Draw avatar with pose (legacy method - now handled by AvatarRenderer)
    drawAvatar(ctx, pose) {
        // This method is now handled by the AvatarRenderer class
        // Kept for backward compatibility
        if (this.renderer) {
            this.renderer.render();
        }
    }

    showNotification(message, type = 'info') {
        if (window.currencyManager) {
            window.currencyManager.showNotification(message, type);
        } else {
            // Fallback notification
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.textContent = message;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 3000);
        }
    }

    // Show thumbnail generation options
    showThumbnailOptions() {
        if (!window.avatarThumbnailService) {
            this.showNotification('Thumbnail service not available', 'error');
            return;
        }

        const modal = document.createElement('div');
        modal.className = 'modal-overlay';
        modal.innerHTML = `
            <div class="modal-content thumbnail-modal">
                <div class="modal-header">
                    <h2>Avatar Thumbnails</h2>
                    <button class="modal-close" onclick="this.closest('.modal-overlay').remove()">×</button>
                </div>
                <div class="modal-body">
                    <div class="thumbnail-grid">
                        <div class="thumbnail-option" data-type="headshot">
                            <div class="thumbnail-preview"></div>
                            <h3>Headshot</h3>
                            <p>150x150 - Profile picture</p>
                        </div>
                        <div class="thumbnail-option" data-type="bust">
                            <div class="thumbnail-preview"></div>
                            <h3>Bust</h3>
                            <p>180x200 - Upper body</p>
                        </div>
                        <div class="thumbnail-option" data-type="fullbody">
                            <div class="thumbnail-preview"></div>
                            <h3>Full Body</h3>
                            <p>300x400 - Complete avatar</p>
                        </div>
                        <div class="thumbnail-option" data-type="profile">
                            <div class="thumbnail-preview"></div>
                            <h3>Profile</h3>
                            <p>100x100 - Small profile</p>
                        </div>
                    </div>
                    <div class="thumbnail-actions">
                        <button class="btn btn-primary" onclick="window.avatarEditor.generateAllThumbnails()">
                            Generate All Thumbnails
                        </button>
                        <button class="btn btn-secondary" onclick="window.avatarEditor.clearThumbnailCache()">
                            Clear Cache
                        </button>
                        <button class="btn btn-success" onclick="window.avatarEditor.exportThumbnails()">
                            Export Thumbnails
                        </button>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(modal);
        
        // Load thumbnail previews
        this.loadThumbnailPreviews();
        
        // Add click handlers for thumbnail options
        modal.querySelectorAll('.thumbnail-option').forEach(option => {
            option.addEventListener('click', () => {
                const type = option.dataset.type;
                this.downloadThumbnail(type);
            });
        });
    }

    // Load thumbnail previews
    async loadThumbnailPreviews() {
        if (!window.avatarThumbnailService) return;

        const types = ['headshot', 'bust', 'fullbody', 'profile'];
        
        for (const type of types) {
            const preview = document.querySelector(`[data-type="${type}"] .thumbnail-preview`);
            if (preview) {
                try {
                    const thumbnail = await window.avatarThumbnailService.generateThumbnail(
                        this.avatarData, 
                        type, 
                        'png'
                    );
                    preview.style.backgroundImage = `url(${thumbnail})`;
                    preview.style.backgroundSize = 'cover';
                    preview.style.backgroundPosition = 'center';
                } catch (error) {
                    console.error(`Failed to load ${type} thumbnail:`, error);
                    preview.textContent = 'Failed to load';
                }
            }
        }
    }

    // Generate all thumbnails
    async generateAllThumbnails() {
        if (!window.avatarThumbnailService) return;

        this.showNotification('Generating thumbnails...', 'info');
        
        try {
            await window.avatarThumbnailService.pregenerateThumbnails(this.avatarData);
            this.showNotification('All thumbnails generated successfully!', 'success');
            this.loadThumbnailPreviews(); // Refresh previews
        } catch (error) {
            console.error('Failed to generate thumbnails:', error);
            this.showNotification('Failed to generate thumbnails', 'error');
        }
    }

    // Clear thumbnail cache
    clearThumbnailCache() {
        if (!window.avatarThumbnailService) return;

        window.avatarThumbnailService.clearCache();
        this.showNotification('Thumbnail cache cleared', 'success');
        
        // Clear preview backgrounds
        document.querySelectorAll('.thumbnail-preview').forEach(preview => {
            preview.style.backgroundImage = '';
            preview.textContent = '';
        });
    }

    // Export thumbnails
    async exportThumbnails() {
        if (!window.avatarThumbnailService) return;

        try {
            const exportData = await window.avatarThumbnailService.exportAvatarWithThumbnails(this.avatarData);
            const blob = new Blob([JSON.stringify(exportData, null, 2)], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            
            const a = document.createElement('a');
            a.href = url;
            a.download = `avatar_thumbnails_${Date.now()}.json`;
            a.click();
            
            URL.revokeObjectURL(url);
            this.showNotification('Thumbnails exported successfully!', 'success');
        } catch (error) {
            console.error('Failed to export thumbnails:', error);
            this.showNotification('Failed to export thumbnails', 'error');
        }
    }

    // Download single thumbnail
    async downloadThumbnail(type) {
        if (!window.avatarThumbnailService) return;

        try {
            const thumbnail = await window.avatarThumbnailService.generateThumbnail(
                this.avatarData, 
                type, 
                'png'
            );
            
            const a = document.createElement('a');
            a.href = thumbnail;
            a.download = `avatar_${type}_${Date.now()}.png`;
            a.click();
            
            this.showNotification(`${type} thumbnail downloaded!`, 'success');
        } catch (error) {
            console.error(`Failed to download ${type} thumbnail:`, error);
            this.showNotification(`Failed to download ${type} thumbnail`, 'error');
        }
    }
}

// Initialize avatar editor when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.avatarEditor = new AvatarEditor();
    
    // Bind avatar edit button
    const editAvatarBtn = document.getElementById('editAvatarBtn');
    if (editAvatarBtn) {
        editAvatarBtn.addEventListener('click', () => {
            window.avatarEditor.openAvatarEditor();
        });
    }
});
