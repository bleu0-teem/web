/**
 * Enhanced Avatar Renderer
 * Based on Roblox revival rendering patterns from void repository
 * Provides 3D-style avatar rendering with body part system
 */

class AvatarRenderer {
    constructor(canvasId = 'avatarCanvas') {
        this.canvas = document.getElementById(canvasId);
        this.ctx = this.canvas ? this.canvas.getContext('2d') : null;
        this.avatarData = null;
        this.rotation = 0;
        this.scale = 1;
        this.animationFrame = 0;
        this.isAnimating = false;
        
        // Body part dimensions (based on Roblox proportions)
        this.bodyParts = {
            head: { width: 40, height: 40, x: 0, y: -60 },
            torso: { width: 50, height: 60, x: 0, y: -20 },
            leftArm: { width: 15, height: 50, x: -35, y: -15 },
            rightArm: { width: 15, height: 50, x: 35, y: -15 },
            leftLeg: { width: 15, height: 50, x: -15, y: 40 },
            rightLeg: { width: 15, height: 50, x: 15, y: 40 }
        };
        
        // Animation poses
        this.poses = {
            idle: { armRotation: 0, legRotation: 0, headTilt: 0 },
            wave: { armRotation: -45, legRotation: 0, headTilt: 0 },
            dance: { armRotation: 30, legRotation: 15, headTilt: 5 },
            jump: { armRotation: -20, legRotation: -30, headTilt: -5 },
            sit: { armRotation: 45, legRotation: 90, headTilt: 0 },
            run: { armRotation: 25, legRotation: 20, headTilt: 0 }
        };
        
        if (this.canvas) {
            this.setupCanvas();
        }
    }
    
    setupCanvas() {
        // Set canvas size
        // Default logical size (CSS pixels)
        const defaultW = 300;
        const defaultH = 400;

        // Enable high DPI rendering
        const dpr = window.devicePixelRatio || 1;
        const rect = this.canvas.getBoundingClientRect();

        // Use the rect if available, otherwise fall back to defaults
        this.logicalWidth = (rect && rect.width) ? rect.width : defaultW;
        this.logicalHeight = (rect && rect.height) ? rect.height : defaultH;

        // Set backing store size
        this.canvas.width = Math.round(this.logicalWidth * dpr);
        this.canvas.height = Math.round(this.logicalHeight * dpr);

        // Scale drawing operations so coordinates are in logical (CSS) pixels
        this.ctx.setTransform(dpr, 0, 0, dpr, 0, 0);

        // Keep visible size consistent
        this.canvas.style.width = this.logicalWidth + 'px';
        this.canvas.style.height = this.logicalHeight + 'px';
    }
    
    setAvatarData(avatarData) {
        this.avatarData = avatarData;
        this.render();
    }
    
    setRotation(rotation) {
        this.rotation = rotation;
        this.render();
    }
    
    setScale(scale) {
        this.scale = scale;
        this.render();
    }
    
    setPose(poseName) {
        if (this.poses[poseName]) {
            this.currentPose = poseName;
            this.render();
        }
    }
    
    clear() {
        if (!this.ctx) return;
        // Clear logical area
        const w = this.logicalWidth || (this.canvas.width / (window.devicePixelRatio || 1));
        const h = this.logicalHeight || (this.canvas.height / (window.devicePixelRatio || 1));
        this.ctx.clearRect(0, 0, w, h);

        // Add subtle background (in logical pixels)
        this.ctx.fillStyle = 'rgba(240, 240, 240, 0.03)';
        this.ctx.fillRect(0, 0, w, h);
    }
    
    render() {
        if (!this.ctx || !this.avatarData) return;
        
        this.clear();
        
        // Save context state
        this.ctx.save();
        
        // Move to center of logical canvas
        const centerX = (this.logicalWidth || (this.canvas.width / (window.devicePixelRatio || 1))) / 2;
        const centerY = (this.logicalHeight || (this.canvas.height / (window.devicePixelRatio || 1))) / 2;
        this.ctx.translate(centerX, centerY);

        // Apply rotation (around center)
        this.ctx.rotate(this.rotation * Math.PI / 180);

        // Apply scale (in logical pixels)
        this.ctx.scale(this.scale, this.scale);
        
        // Get current pose
        const pose = this.poses[this.currentPose] || this.poses.idle;
        
        // Render body parts in correct order (back to front)
    // Render parts back-to-front
    this.renderBodyPart('rightLeg', pose);
    this.renderBodyPart('leftLeg', pose);
    this.renderBodyPart('torso', pose);
    this.renderBodyPart('rightArm', pose);
    this.renderBodyPart('leftArm', pose);
    this.renderBodyPart('head', pose);
        
        // Render clothing
        this.renderClothing(pose);
        
        // Render face
        this.renderFace(pose);
        
        // Restore context state
        this.ctx.restore();
    }
    
    renderBodyPart(partName, pose) {
        if (!this.avatarData.body) return;
        
        const part = this.bodyParts[partName];
        const color = this.avatarData.body[partName + 'Color'] || '#CCCCCC';
        // Draw each part around its own pivot so rotations look natural
        this.ctx.save();

        // Translate to the part's local origin
        this.ctx.translate(part.x, part.y);

        // Apply pose transformations around the part center
        if (partName.includes('Arm')) {
            this.ctx.rotate((pose.armRotation || 0) * Math.PI / 180);
        } else if (partName.includes('Leg')) {
            this.ctx.rotate((pose.legRotation || 0) * Math.PI / 180);
        } else if (partName === 'head') {
            this.ctx.rotate((pose.headTilt || 0) * Math.PI / 180);
        }

        // Draw body part centered at local origin
        this.draw3DRect(0, 0, part.width, part.height, color);

        this.ctx.restore();
    }
    
    draw3DRect(x, y, width, height, color) {
        // Main body
        this.ctx.fillStyle = color;
        this.ctx.fillRect(x - width/2, y - height/2, width, height);
        
        // Highlight (3D effect)
        const highlightColor = this.lightenColor(color, 20);
        this.ctx.fillStyle = highlightColor;
        this.ctx.fillRect(x - width/2, y - height/2, width * 0.3, height);
        
        // Shadow (3D effect)
        const shadowColor = this.darkenColor(color, 20);
        this.ctx.fillStyle = shadowColor;
        this.ctx.fillRect(x + width/2 - width * 0.3, y - height/2, width * 0.3, height);
        
        // Outline
        this.ctx.strokeStyle = this.darkenColor(color, 40);
        this.ctx.lineWidth = 1;
        this.ctx.strokeRect(x - width/2, y - height/2, width, height);
    }
    
    renderClothing(pose) {
        if (!this.avatarData.clothing) return;
        
        // Render shirt
        if (this.avatarData.clothing.shirt) {
            this.renderShirt(String(this.avatarData.clothing.shirt).toLowerCase(), pose);
        }
        
        // Render pants
        if (this.avatarData.clothing.pants) {
            this.renderPants(String(this.avatarData.clothing.pants).toLowerCase(), pose);
        }
        
        // Render accessories
        if (this.avatarData.clothing.accessories) {
            this.avatarData.clothing.accessories.forEach(accessory => {
                this.renderAccessory(String(accessory).toLowerCase(), pose);
            });
        }
    }
    
    renderShirt(shirtType, pose) {
        const shirtColors = {
            'classic': '#FF6B6B',
            'striped': '#4ECDC4',
            'hoodie': '#8B5CF6',
            'jacket': '#2D3748'
        };

        const color = shirtColors[shirtType] || '#CCCCCC';
        const torso = this.bodyParts.torso;
        
        this.ctx.save();
        this.ctx.rotate(pose.armRotation * Math.PI / 180);
        
        // Draw shirt over torso
        this.draw3DRect(
            torso.x, 
            torso.y, 
            torso.width + 4, 
            torso.height + 2, 
            color
        );
        
        // Add shirt details
        if (shirtType === 'striped') {
            this.drawStripes(torso.x, torso.y, torso.width + 4, torso.height + 2);
        } else if (shirtType === 'hoodie') {
            this.drawHoodieDetails(torso.x, torso.y, torso.width + 4, torso.height + 2);
        }
        
        this.ctx.restore();
    }
    
    renderPants(pantsType, pose) {
        const pantsColors = {
            'classic': '#4A5568',
            'jeans': '#1A365D',
            'shorts': '#2B6CB0',
            'cargo': '#744210'
        };

        const color = pantsColors[pantsType] || '#CCCCCC';
        
        this.ctx.save();
        this.ctx.rotate(pose.legRotation * Math.PI / 180);
        
        // Draw pants on legs
        const leftLeg = this.bodyParts.leftLeg;
        const rightLeg = this.bodyParts.rightLeg;
        
        this.draw3DRect(
            leftLeg.x, 
            leftLeg.y + 10, 
            leftLeg.width + 2, 
            leftLeg.height - 10, 
            color
        );
        
        this.draw3DRect(
            rightLeg.x, 
            rightLeg.y + 10, 
            rightLeg.width + 2, 
            rightLeg.height - 10, 
            color
        );
        
        this.ctx.restore();
    }
    
    renderAccessory(accessoryType, pose) {
        const head = this.bodyParts.head;
        
        this.ctx.save();
        this.ctx.rotate(pose.headTilt * Math.PI / 180);
        
        switch (accessoryType) {
            case 'hat':
                this.drawHat(head.x, head.y - head.height/2 - 5);
                break;
            case 'glasses':
                this.drawGlasses(head.x, head.y);
                break;
            case 'watch':
                this.drawWatch(this.bodyParts.rightArm.x, this.bodyParts.rightArm.y);
                break;
            case 'backpack':
                this.drawBackpack(this.bodyParts.torso.x, this.bodyParts.torso.y);
                break;
        }
        
        this.ctx.restore();
    }
    
    renderFace(pose) {
        if (!this.avatarData.face) return;
        
        const head = this.bodyParts.head;
        
        this.ctx.save();
        this.ctx.rotate(pose.headTilt * Math.PI / 180);
        
        // Draw face features
        this.drawEyes(head.x, head.y, this.avatarData.face.eyes);
        this.drawEyebrows(head.x, head.y, this.avatarData.face.eyebrows);
        this.drawMouth(head.x, head.y, this.avatarData.face.type);
        
        this.ctx.restore();
    }
    
    drawEyes(x, y, eyeType) {
        const eyeY = y - 5;
        const eyeSpacing = 12;
        
        this.ctx.fillStyle = '#000000';
        
        switch (eyeType) {
            case 'normal':
                this.ctx.fillRect(x - eyeSpacing, eyeY, 4, 4);
                this.ctx.fillRect(x + eyeSpacing - 4, eyeY, 4, 4);
                break;
            case 'wide':
                this.ctx.fillRect(x - eyeSpacing - 2, eyeY, 6, 6);
                this.ctx.fillRect(x + eyeSpacing - 6, eyeY, 6, 6);
                break;
            case 'closed':
                this.ctx.fillRect(x - eyeSpacing, eyeY, 4, 1);
                this.ctx.fillRect(x + eyeSpacing - 4, eyeY, 4, 1);
                break;
            case 'wink':
                this.ctx.fillRect(x - eyeSpacing, eyeY, 4, 1);
                this.ctx.fillRect(x + eyeSpacing - 4, eyeY, 4, 4);
                break;
        }
    }
    
    drawEyebrows(x, y, eyebrowType) {
        const browY = y - 15;
        const browSpacing = 12;
        
        this.ctx.strokeStyle = '#000000';
        this.ctx.lineWidth = 2;
        
        switch (eyebrowType) {
            case 'normal':
                this.ctx.beginPath();
                this.ctx.moveTo(x - browSpacing, browY);
                this.ctx.lineTo(x - browSpacing + 6, browY);
                this.ctx.moveTo(x + browSpacing, browY);
                this.ctx.lineTo(x + browSpacing - 6, browY);
                this.ctx.stroke();
                break;
            case 'raised':
                this.ctx.beginPath();
                this.ctx.moveTo(x - browSpacing, browY + 2);
                this.ctx.lineTo(x - browSpacing + 6, browY);
                this.ctx.moveTo(x + browSpacing, browY + 2);
                this.ctx.lineTo(x + browSpacing - 6, browY);
                this.ctx.stroke();
                break;
            case 'furrowed':
                this.ctx.beginPath();
                this.ctx.moveTo(x - browSpacing, browY);
                this.ctx.lineTo(x - browSpacing + 6, browY + 2);
                this.ctx.moveTo(x + browSpacing, browY);
                this.ctx.lineTo(x + browSpacing - 6, browY + 2);
                this.ctx.stroke();
                break;
            case 'angled':
                this.ctx.beginPath();
                this.ctx.moveTo(x - browSpacing, browY + 2);
                this.ctx.lineTo(x - browSpacing + 6, browY - 2);
                this.ctx.moveTo(x + browSpacing, browY + 2);
                this.ctx.lineTo(x + browSpacing - 6, browY - 2);
                this.ctx.stroke();
                break;
        }
    }
    
    drawMouth(x, y, mouthType) {
        const mouthY = y + 10;
        
        this.ctx.strokeStyle = '#000000';
        this.ctx.lineWidth = 2;
        
        switch (mouthType) {
            case 'smile':
                this.ctx.beginPath();
                this.ctx.arc(x, mouthY - 2, 6, 0, Math.PI);
                this.ctx.stroke();
                break;
            case 'neutral':
                this.ctx.beginPath();
                this.ctx.moveTo(x - 6, mouthY);
                this.ctx.lineTo(x + 6, mouthY);
                this.ctx.stroke();
                break;
            case 'cool':
                this.ctx.beginPath();
                this.ctx.moveTo(x - 6, mouthY);
                this.ctx.lineTo(x + 6, mouthY - 2);
                this.ctx.stroke();
                break;
            case 'wink':
                this.ctx.beginPath();
                this.ctx.arc(x, mouthY - 2, 4, 0, Math.PI);
                this.ctx.stroke();
                break;
            case 'laugh':
                this.ctx.beginPath();
                this.ctx.arc(x, mouthY - 2, 8, 0, Math.PI);
                this.ctx.stroke();
                break;
            case 'surprised':
                this.ctx.beginPath();
                this.ctx.arc(x, mouthY, 3, 0, 2 * Math.PI);
                this.ctx.stroke();
                break;
        }
    }
    
    drawHat(x, y) {
        this.ctx.fillStyle = '#FF0000';
        this.ctx.fillRect(x - 20, y - 5, 40, 8);
        this.ctx.fillRect(x - 15, y - 15, 30, 10);
    }
    
    drawGlasses(x, y) {
        this.ctx.strokeStyle = '#000000';
        this.ctx.lineWidth = 2;
        this.ctx.strokeRect(x - 15, y - 5, 10, 10);
        this.ctx.strokeRect(x + 5, y - 5, 10, 10);
        this.ctx.beginPath();
        this.ctx.moveTo(x - 5, y);
        this.ctx.lineTo(x + 5, y);
        this.ctx.stroke();
    }
    
    drawWatch(x, y) {
        this.ctx.fillStyle = '#FFD700';
        this.ctx.fillRect(x - 5, y + 10, 10, 8);
        this.ctx.strokeStyle = '#000000';
        this.ctx.lineWidth = 1;
        this.ctx.strokeRect(x - 5, y + 10, 10, 8);
    }
    
    drawBackpack(x, y) {
        this.ctx.fillStyle = '#8B4513';
        this.ctx.fillRect(x - 8, y - 20, 16, 25);
        this.ctx.strokeStyle = '#000000';
        this.ctx.lineWidth = 1;
        this.ctx.strokeRect(x - 8, y - 20, 16, 25);
    }
    
    drawStripes(x, y, width, height) {
        this.ctx.strokeStyle = '#FFFFFF';
        this.ctx.lineWidth = 2;
        
        for (let i = 0; i < height; i += 8) {
            this.ctx.beginPath();
            this.ctx.moveTo(x - width/2, y - height/2 + i);
            this.ctx.lineTo(x + width/2, y - height/2 + i);
            this.ctx.stroke();
        }
    }
    
    drawHoodieDetails(x, y, width, height) {
        // Draw hood
        this.ctx.fillStyle = '#333333';
        this.ctx.fillRect(x - width/2 - 2, y - height/2 - 8, width + 4, 10);
        
        // Draw pocket
        this.ctx.fillRect(x - 10, y + 5, 20, 8);
    }
    
    lightenColor(color, percent) {
        const num = parseInt(color.replace("#", ""), 16);
        const amt = Math.round(2.55 * percent);
        const R = (num >> 16) + amt;
        const G = (num >> 8 & 0x00FF) + amt;
        const B = (num & 0x0000FF) + amt;
        return "#" + (0x1000000 + (R < 255 ? R < 1 ? 0 : R : 255) * 0x10000 +
            (G < 255 ? G < 1 ? 0 : G : 255) * 0x100 +
            (B < 255 ? B < 1 ? 0 : B : 255)).toString(16).slice(1);
    }
    
    darkenColor(color, percent) {
        return this.lightenColor(color, -percent);
    }
    
    generateThumbnail(width = 100, height = 100) {
        if (!this.canvas) return null;
        
        // Create temporary canvas for thumbnail
        const tempCanvas = document.createElement('canvas');
        tempCanvas.width = width;
        tempCanvas.height = height;
        const tempCtx = tempCanvas.getContext('2d');
        
        // Copy and scale current avatar
        tempCtx.drawImage(this.canvas, 0, 0, width, height);
        
        return tempCanvas.toDataURL('image/png');
    }
    
    startAnimation() {
        if (this.isAnimating) return;
        
        this.isAnimating = true;
        this.animationFrame = 0;
        this.animate();
    }
    
    stopAnimation() {
        this.isAnimating = false;
    }
    
    animate() {
        if (!this.isAnimating) return;
        
        this.animationFrame++;
        
        // Simple breathing animation
        const breathe = Math.sin(this.animationFrame * 0.1) * 2;
        this.scale = 1 + breathe * 0.05;
        
        this.render();
        
        requestAnimationFrame(() => this.animate());
    }
    
    exportAvatarData() {
        return {
            avatarData: this.avatarData,
            rotation: this.rotation,
            scale: this.scale,
            currentPose: this.currentPose,
            thumbnail: this.generateThumbnail()
        };
    }
    
    importAvatarData(data) {
        if (data.avatarData) {
            this.setAvatarData(data.avatarData);
        }
        if (data.rotation !== undefined) {
            this.setRotation(data.rotation);
        }
        if (data.scale !== undefined) {
            this.setScale(data.scale);
        }
        if (data.currentPose) {
            this.setPose(data.currentPose);
        }
    }
}

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AvatarRenderer;
} else if (typeof window !== 'undefined') {
    window.AvatarRenderer = AvatarRenderer;
}
