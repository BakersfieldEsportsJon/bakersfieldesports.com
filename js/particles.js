/**
 * Lightweight Particle Effect
 * Bakersfield eSports - 2025
 */

(function() {
    'use strict';

    class ParticleSystem {
        constructor(canvasId, options = {}) {
            this.canvas = document.getElementById(canvasId);
            if (!this.canvas) return;

            this.ctx = this.canvas.getContext('2d');
            this.particles = [];

            // Configuration
            this.config = {
                particleCount: options.particleCount || 50,
                particleColor: options.particleColor || '#EC194D',
                particleSize: options.particleSize || 2,
                particleSpeed: options.particleSpeed || 0.5,
                lineColor: options.lineColor || 'rgba(236, 25, 77, 0.2)',
                lineDistance: options.lineDistance || 150,
                enableLines: options.enableLines !== false,
                enableGlow: options.enableGlow !== false
            };

            this.mouse = {
                x: null,
                y: null,
                radius: 150
            };

            this.init();
        }

        init() {
            this.resize();
            this.createParticles();
            this.setupEventListeners();
            this.animate();
        }

        resize() {
            const parent = this.canvas.parentElement;
            this.canvas.width = parent.offsetWidth;
            this.canvas.height = parent.offsetHeight;
        }

        setupEventListeners() {
            window.addEventListener('resize', () => this.resize());

            this.canvas.addEventListener('mousemove', (e) => {
                const rect = this.canvas.getBoundingClientRect();
                this.mouse.x = e.clientX - rect.left;
                this.mouse.y = e.clientY - rect.top;
            });

            this.canvas.addEventListener('mouseleave', () => {
                this.mouse.x = null;
                this.mouse.y = null;
            });
        }

        createParticles() {
            this.particles = [];
            for (let i = 0; i < this.config.particleCount; i++) {
                this.particles.push(new Particle(this));
            }
        }

        connectParticles() {
            if (!this.config.enableLines) return;

            for (let i = 0; i < this.particles.length; i++) {
                for (let j = i + 1; j < this.particles.length; j++) {
                    const dx = this.particles[i].x - this.particles[j].x;
                    const dy = this.particles[i].y - this.particles[j].y;
                    const distance = Math.sqrt(dx * dx + dy * dy);

                    if (distance < this.config.lineDistance) {
                        const opacity = 1 - (distance / this.config.lineDistance);
                        this.ctx.strokeStyle = this.config.lineColor.replace('0.2', opacity * 0.2);
                        this.ctx.lineWidth = 0.5;
                        this.ctx.beginPath();
                        this.ctx.moveTo(this.particles[i].x, this.particles[i].y);
                        this.ctx.lineTo(this.particles[j].x, this.particles[j].y);
                        this.ctx.stroke();
                    }
                }
            }
        }

        animate() {
            this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);

            // Update and draw particles
            this.particles.forEach(particle => {
                particle.update();
                particle.draw();
            });

            // Connect particles
            this.connectParticles();

            requestAnimationFrame(() => this.animate());
        }
    }

    class Particle {
        constructor(system) {
            this.system = system;
            this.reset();
            this.x = Math.random() * system.canvas.width;
            this.y = Math.random() * system.canvas.height;
        }

        reset() {
            this.size = Math.random() * this.system.config.particleSize + 1;
            this.speedX = (Math.random() - 0.5) * this.system.config.particleSpeed;
            this.speedY = (Math.random() - 0.5) * this.system.config.particleSpeed;
            this.opacity = Math.random() * 0.5 + 0.3;
        }

        update() {
            // Mouse interaction
            if (this.system.mouse.x !== null) {
                const dx = this.system.mouse.x - this.x;
                const dy = this.system.mouse.y - this.y;
                const distance = Math.sqrt(dx * dx + dy * dy);

                if (distance < this.system.mouse.radius) {
                    const force = (this.system.mouse.radius - distance) / this.system.mouse.radius;
                    const angle = Math.atan2(dy, dx);
                    this.x -= Math.cos(angle) * force * 5;
                    this.y -= Math.sin(angle) * force * 5;
                }
            }

            // Move particle
            this.x += this.speedX;
            this.y += this.speedY;

            // Bounce off edges
            if (this.x < 0 || this.x > this.system.canvas.width) {
                this.speedX *= -1;
            }
            if (this.y < 0 || this.y > this.system.canvas.height) {
                this.speedY *= -1;
            }

            // Keep within bounds
            this.x = Math.max(0, Math.min(this.system.canvas.width, this.x));
            this.y = Math.max(0, Math.min(this.system.canvas.height, this.y));
        }

        draw() {
            const ctx = this.system.ctx;

            // Glow effect
            if (this.system.config.enableGlow) {
                ctx.shadowBlur = 15;
                ctx.shadowColor = this.system.config.particleColor;
            }

            ctx.fillStyle = this.system.config.particleColor;
            ctx.globalAlpha = this.opacity;
            ctx.beginPath();
            ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
            ctx.fill();

            ctx.shadowBlur = 0;
            ctx.globalAlpha = 1;
        }
    }

    // ============================================
    // AUTO-INITIALIZE
    // ============================================

    function initParticles() {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', runInit);
        } else {
            runInit();
        }
    }

    function runInit() {
        // Initialize particles for stats section
        const statsCanvas = document.getElementById('particles-canvas');
        if (statsCanvas) {
            console.log('✨ Initializing particles...');
            new ParticleSystem('particles-canvas', {
                particleCount: window.innerWidth < 768 ? 30 : 50,
                particleColor: '#EC194D',
                particleSpeed: 0.3,
                enableGlow: true
            });
            console.log('✅ Particles initialized!');
        }

        // Initialize particles for hero if canvas exists
        const heroCanvas = document.getElementById('hero-particles-canvas');
        if (heroCanvas) {
            new ParticleSystem('hero-particles-canvas', {
                particleCount: window.innerWidth < 768 ? 40 : 80,
                particleColor: '#EC194D',
                particleSpeed: 0.4,
                lineDistance: 120,
                enableGlow: true
            });
        }
    }

    // Start initialization
    initParticles();

    // Export to window
    window.ParticleSystem = ParticleSystem;

})();
