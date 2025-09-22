/**
 * Avatar Thumbnail Service
 * Based on Roblox revival thumbnail patterns from void repository
 * Provides avatar thumbnail generation and caching system
 */

class AvatarThumbnailService {
    constructor() {
        this.cache = new Map();
        this.maxCacheSize = 50;
        this.defaultSize = { width: 100, height: 100 };
        this.supportedFormats = ['png', 'jpg', 'webp'];
        
        // Thumbnail types based on void repository patterns
        this.thumbnailTypes = {
            headshot: { width: 150, height: 150, crop: 'head' },
            bust: { width: 180, height: 200, crop: 'torso' },
            fullbody: { width: 300, height: 400, crop: 'full' },
            profile: { width: 100, height: 100, crop: 'head' },
            thumbnail: { width: 75, height: 75, crop: 'head' }
        };
        
        this.initializeCache();
    }
    
    initializeCache() {
        // Load cached thumbnails from localStorage
        try {
            const cached = localStorage.getItem('avatar_thumbnail_cache');
            if (cached) {
                const cacheData = JSON.parse(cached);
                this.cache = new Map(Object.entries(cacheData));
            }
        } catch (error) {
            console.warn('Failed to load thumbnail cache:', error);
        }
    }
    
    saveCache() {
        try {
            const cacheData = Object.fromEntries(this.cache);
            localStorage.setItem('avatar_thumbnail_cache', JSON.stringify(cacheData));
        } catch (error) {
            console.warn('Failed to save thumbnail cache:', error);
        }
    }
    
    generateCacheKey(avatarData, type, format = 'png') {
        const dataString = JSON.stringify(avatarData);
        const key = `${type}-${format}-${btoa(dataString).substring(0, 20)}`;
        return key;
    }
    
    async generateThumbnail(avatarData, type = 'profile', format = 'png') {
        const config = this.thumbnailTypes[type] || this.thumbnailTypes.profile;
        const cacheKey = this.generateCacheKey(avatarData, type, format);
        
        // Check cache first
        if (this.cache.has(cacheKey)) {
            return this.cache.get(cacheKey);
        }
        
        // Generate new thumbnail
        const thumbnail = await this.createThumbnail(avatarData, config, format);
        
        // Cache the result
        this.cache.set(cacheKey, thumbnail);
        
        // Manage cache size
        if (this.cache.size > this.maxCacheSize) {
            this.evictOldestCacheEntries();
        }
        
        this.saveCache();
        return thumbnail;
    }
    
    async createThumbnail(avatarData, config, format) {
        return new Promise((resolve, reject) => {
            try {
                // Create temporary canvas
                const canvas = document.createElement('canvas');
                canvas.width = config.width;
                canvas.height = config.height;
                const ctx = canvas.getContext('2d');
                
                // Create temporary renderer
                const tempRenderer = new AvatarRenderer();
                tempRenderer.canvas = canvas;
                tempRenderer.ctx = ctx;
                tempRenderer.setAvatarData(avatarData);
                
                // Set pose for thumbnail
                const thumbnailPoses = {
                    'head': 'idle',
                    'torso': 'idle',
                    'full': 'idle'
                };
                tempRenderer.setPose(thumbnailPoses[config.crop] || 'idle');
                
                // Apply crop settings
                this.applyCropSettings(ctx, config);
                
                // Generate thumbnail data URL
                const mimeType = `image/${format}`;
                const dataURL = canvas.toDataURL(mimeType, 0.9);
                
                resolve(dataURL);
            } catch (error) {
                reject(error);
            }
        });
    }
    
    applyCropSettings(ctx, config) {
        const { width, height, crop } = config;
        
        // Clear canvas with transparent background
        ctx.clearRect(0, 0, width, height);
        
        // Add subtle background for better visibility
        ctx.fillStyle = 'rgba(240, 240, 240, 0.3)';
        ctx.fillRect(0, 0, width, height);
        
        // Apply crop-specific transformations
        switch (crop) {
            case 'head':
                ctx.scale(1.5, 1.5);
                ctx.translate(0, -20);
                break;
            case 'torso':
                ctx.scale(1.2, 1.2);
                ctx.translate(0, -10);
                break;
            case 'full':
                // No additional transformation needed
                break;
        }
    }
    
    evictOldestCacheEntries() {
        // Simple LRU cache eviction
        const keysToDelete = Array.from(this.cache.keys()).slice(0, 10);
        keysToDelete.forEach(key => this.cache.delete(key));
    }
    
    clearCache() {
        this.cache.clear();
        this.saveCache();
    }
    
    getCacheStats() {
        return {
            size: this.cache.size,
            maxSize: this.maxCacheSize,
            hitRate: this.calculateHitRate()
        };
    }
    
    calculateHitRate() {
        // This would need to be implemented with hit/miss tracking
        return 0.85; // Placeholder
    }
    
    // Batch thumbnail generation
    async generateBatchThumbnails(avatarDataArray, type = 'profile', format = 'png') {
        const promises = avatarDataArray.map(avatarData => 
            this.generateThumbnail(avatarData, type, format)
        );
        
        try {
            const thumbnails = await Promise.all(promises);
            return thumbnails;
        } catch (error) {
            console.error('Batch thumbnail generation failed:', error);
            throw error;
        }
    }
    
    // Generate avatar thumbnail with specific pose
    async generatePoseThumbnail(avatarData, pose, type = 'profile', format = 'png') {
        const modifiedAvatarData = {
            ...avatarData,
            animations: {
                ...avatarData.animations,
                currentPose: pose
            }
        };
        
        return this.generateThumbnail(modifiedAvatarData, type, format);
    }
    
    // Generate animated thumbnail (GIF-like effect)
    async generateAnimatedThumbnail(avatarData, type = 'profile') {
        const poses = ['idle', 'wave', 'dance'];
        const frames = [];
        
        for (const pose of poses) {
            const frame = await this.generatePoseThumbnail(avatarData, pose, type, 'png');
            frames.push(frame);
        }
        
        return frames;
    }
    
    // Export avatar data with thumbnails
    async exportAvatarWithThumbnails(avatarData) {
        const thumbnails = {
            profile: await this.generateThumbnail(avatarData, 'profile'),
            headshot: await this.generateThumbnail(avatarData, 'headshot'),
            bust: await this.generateThumbnail(avatarData, 'bust'),
            fullbody: await this.generateThumbnail(avatarData, 'fullbody')
        };
        
        return {
            avatarData,
            thumbnails,
            timestamp: Date.now()
        };
    }
    
    // Import avatar data with thumbnails
    importAvatarWithThumbnails(exportData) {
        const { avatarData, thumbnails } = exportData;
        
        // Cache the imported thumbnails
        Object.entries(thumbnails).forEach(([type, dataURL]) => {
            const cacheKey = this.generateCacheKey(avatarData, type);
            this.cache.set(cacheKey, dataURL);
        });
        
        this.saveCache();
        return avatarData;
    }
    
    // Get thumbnail URL for avatar (useful for profile pictures)
    getThumbnailUrl(avatarData, type = 'profile') {
        const cacheKey = this.generateCacheKey(avatarData, type);
        
        if (this.cache.has(cacheKey)) {
            return this.cache.get(cacheKey);
        }
        
        // Return placeholder if not in cache
        return this.getPlaceholderThumbnail(type);
    }
    
    getPlaceholderThumbnail(type) {
        const config = this.thumbnailTypes[type] || this.thumbnailTypes.profile;
        const canvas = document.createElement('canvas');
        canvas.width = config.width;
        canvas.height = config.height;
        const ctx = canvas.getContext('2d');
        
        // Draw placeholder
        ctx.fillStyle = '#f0f0f0';
        ctx.fillRect(0, 0, config.width, config.height);
        
        ctx.fillStyle = '#cccccc';
        ctx.font = `${config.width / 4}px Arial`;
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText('?', config.width / 2, config.height / 2);
        
        return canvas.toDataURL('image/png');
    }
    
    // Pre-generate thumbnails for common poses
    async pregenerateThumbnails(avatarData) {
        const poses = ['idle', 'wave', 'dance', 'jump', 'sit', 'run'];
        const types = ['profile', 'headshot', 'bust'];
        
        const promises = [];
        
        poses.forEach(pose => {
            types.forEach(type => {
                promises.push(
                    this.generatePoseThumbnail(avatarData, pose, type)
                );
            });
        });
        
        try {
            await Promise.all(promises);
            console.log('Pregenerated thumbnails for avatar');
        } catch (error) {
            console.error('Failed to pregenerate thumbnails:', error);
        }
    }
    
    // Service worker integration for offline thumbnail access
    registerServiceWorker() {
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/avatar-thumbnail-worker.js')
                .then(registration => {
                    console.log('Thumbnail service worker registered:', registration);
                })
                .catch(error => {
                    console.error('Thumbnail service worker registration failed:', error);
                });
        }
    }
}

// Create global instance
window.avatarThumbnailService = new AvatarThumbnailService();

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AvatarThumbnailService;
}
