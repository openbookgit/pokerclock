/**
 * Poker Clock Sound Synthesizer & Ambient Fire Particle Engine
 * Zero external audio file dependency (uses Web Audio API + Web Speech API)
 */

const PokerAudio = (function () {
    let ctx = null;

    function getContext() {
        if (!ctx) {
            const AudioCtx = window.AudioContext || window.webkitAudioContext;
            if (AudioCtx) {
                ctx = new AudioCtx();
            }
        }
        if (ctx && ctx.state === 'suspended') {
            ctx.resume().catch(() => {});
        }
        return ctx;
    }

    // Unlock audio context on first user click/key
    document.addEventListener('click', () => getContext(), { once: false });
    document.addEventListener('keydown', () => getContext(), { once: false });

    function playTone(freq, type, duration, gainVal = 0.25, delay = 0) {
        const audio = getContext();
        if (!audio) return;

        const startTime = audio.currentTime + delay;
        const osc = audio.createOscillator();
        const gain = audio.createGain();

        osc.type = type;
        osc.frequency.setValueAtTime(freq, startTime);

        gain.gain.setValueAtTime(0.0001, startTime);
        gain.gain.exponentialRampToValueAtTime(gainVal, startTime + 0.02);
        gain.gain.exponentialRampToValueAtTime(0.0001, startTime + duration);

        osc.connect(gain);
        gain.connect(audio.destination);

        osc.start(startTime);
        osc.stop(startTime + duration + 0.05);
    }

    return {
        // Short countdown beep (5, 4, 3, 2, 1)
        playTick: function () {
            playTone(880, 'sine', 0.14, 0.22, 0);
        },

        // 60-second warning double chime
        playWarning: function () {
            playTone(659.25, 'triangle', 0.25, 0.25, 0);
            playTone(880.0, 'triangle', 0.35, 0.28, 0.22);
        },

        // Level up rich chord chime / gong
        playLevelUp: function () {
            playTone(523.25, 'triangle', 0.9, 0.32, 0);    // C5
            playTone(659.25, 'triangle', 0.9, 0.28, 0.12); // E5
            playTone(783.99, 'triangle', 1.1, 0.30, 0.24); // G5
            playTone(1046.50, 'sine', 1.4, 0.35, 0.36);    // C6
        },

        // Optional TTS Voice Broadcast
        speak: function (text) {
            if (!('speechSynthesis' in window)) return;
            try {
                window.speechSynthesis.cancel();
                const utter = new SpeechSynthesisUtterance(text);
                utter.lang = 'zh-TW';
                utter.rate = 1.0;
                utter.pitch = 1.0;
                window.speechSynthesis.speak(utter);
            } catch (e) {
                console.warn('TTS error:', e);
            }
        }
    };
})();

/**
 * Ambient Fire / Glowing Embers Canvas Engine ("火焰 : 開")
 */
const FireEffect = (function () {
    let canvas = null;
    let ctx = null;
    let enabled = true;
    let animId = null;
    let particles = [];
    let mode = 'gold'; // 'gold', 'cyan', 'red', 'emerald', 'dual'

    function createParticle(w, h, randomY = false) {
        const x = Math.random() * w;
        const y = randomY ? Math.random() * h : h + 10;
        let hue = 45; // Gold
        if (mode === 'cyan') {
            hue = 185 + Math.random() * 15;
        } else if (mode === 'red') {
            hue = 350 + Math.random() * 15;
        } else if (mode === 'emerald') {
            hue = 140 + Math.random() * 20;
        } else if (mode === 'dual') {
            hue = (x < w / 2) ? (42 + Math.random() * 12) : (185 + Math.random() * 15);
        } else {
            hue = 38 + Math.random() * 16;
        }

        return {
            x: x,
            y: y,
            vx: (Math.random() - 0.5) * 0.7,
            vy: -(0.5 + Math.random() * 1.4),
            size: 1.5 + Math.random() * 3.2,
            alpha: 0.2 + Math.random() * 0.65,
            hue: hue,
            life: 0,
            maxLife: 120 + Math.random() * 180
        };
    }

    function resize() {
        if (!canvas) return;
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;
    }

    function render() {
        if (!ctx || !canvas) return;
        ctx.clearRect(0, 0, canvas.width, canvas.height);

        if (enabled) {
            while (particles.length < 55) {
                particles.push(createParticle(canvas.width, canvas.height, false));
            }

            for (let i = particles.length - 1; i >= 0; i--) {
                const p = particles[i];
                p.x += p.vx + Math.sin(p.life * 0.04) * 0.3;
                p.y += p.vy;
                p.life++;

                const fade = 1 - (p.life / p.maxLife);
                if (p.y < -20 || fade <= 0) {
                    particles.splice(i, 1);
                    continue;
                }

                ctx.save();
                ctx.beginPath();
                const grad = ctx.createRadialGradient(p.x, p.y, 0, p.x, p.y, p.size * 3);
                grad.addColorStop(0, `hsla(${p.hue}, 100%, 85%, ${p.alpha * fade})`);
                grad.addColorStop(0.4, `hsla(${p.hue}, 100%, 55%, ${p.alpha * fade * 0.6})`);
                grad.addColorStop(1, `hsla(${p.hue}, 100%, 50%, 0)`);
                ctx.fillStyle = grad;
                ctx.arc(p.x, p.y, p.size * 3, 0, Math.PI * 2);
                ctx.fill();
                ctx.restore();
            }
        }

        animId = requestAnimationFrame(render);
    }

    return {
        init: function (canvasId, initialTheme = 'gold') {
            canvas = document.getElementById(canvasId);
            if (!canvas) return;
            ctx = canvas.getContext('2d');
            mode = initialTheme;
            resize();
            window.addEventListener('resize', resize);
            particles = [];
            for (let i = 0; i < 45; i++) {
                particles.push(createParticle(canvas.width, canvas.height, true));
            }
            if (!animId) {
                render();
            }
        },
        setEnabled: function (val) {
            enabled = !!val;
            if (!enabled && ctx && canvas) {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
            }
        },
        setTheme: function (newTheme) {
            if (mode !== newTheme) {
                mode = newTheme;
            }
        }
    };
})();
