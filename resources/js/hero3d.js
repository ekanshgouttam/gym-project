// Lazy-loaded Three.js hero: a wireframe "energy core" inside orbiting rings and a particle field.
import * as THREE from 'three';

export default function mountHero(canvas) {
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const renderer = new THREE.WebGLRenderer({ canvas, antialias: true, alpha: true, powerPreference: 'high-performance' });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));

    const scene = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(45, 1, 0.1, 100);
    camera.position.set(0, 0, 9);

    const volt = new THREE.Color('#FFD400');
    const group = new THREE.Group();
    scene.add(group);

    // Core: icosahedron wireframe + inner solid glow.
    const core = new THREE.Mesh(
        new THREE.IcosahedronGeometry(1.6, 1),
        new THREE.MeshBasicMaterial({ color: volt, wireframe: true, transparent: true, opacity: 0.55 })
    );
    const inner = new THREE.Mesh(
        new THREE.IcosahedronGeometry(1.05, 3),
        new THREE.MeshBasicMaterial({ color: volt, transparent: true, opacity: 0.08 })
    );
    group.add(core, inner);

    // Orbiting rings.
    const rings = [2.4, 3.1, 3.8].map((r, i) => {
        const ring = new THREE.Mesh(
            new THREE.TorusGeometry(r, 0.008 + i * 0.004, 8, 160),
            new THREE.MeshBasicMaterial({ color: i === 1 ? 0xffffff : volt, transparent: true, opacity: 0.35 - i * 0.08 })
        );
        ring.rotation.x = Math.PI / 2 + (i - 1) * 0.5;
        ring.rotation.y = i * 0.4;
        group.add(ring);
        return ring;
    });

    // Particle field.
    const count = window.innerWidth < 768 ? 700 : 1600;
    const positions = new Float32Array(count * 3);
    for (let i = 0; i < count; i++) {
        const r = 4 + Math.random() * 9;
        const theta = Math.random() * Math.PI * 2;
        const phi = Math.acos(2 * Math.random() - 1);
        positions[i * 3] = r * Math.sin(phi) * Math.cos(theta);
        positions[i * 3 + 1] = r * Math.sin(phi) * Math.sin(theta);
        positions[i * 3 + 2] = r * Math.cos(phi) - 4;
    }
    const particlesGeo = new THREE.BufferGeometry();
    particlesGeo.setAttribute('position', new THREE.BufferAttribute(positions, 3));
    const particles = new THREE.Points(particlesGeo, new THREE.PointsMaterial({ color: 0xcfd8e3, size: 0.03, transparent: true, opacity: 0.6 }));
    scene.add(particles);

    function resize() {
        const { clientWidth: w, clientHeight: h } = canvas;
        if (!w || !h) return;
        renderer.setSize(w, h, false);
        camera.aspect = w / h;
        // Keep the core framed on narrow screens.
        camera.position.z = w < 640 ? 12 : 9;
        camera.updateProjectionMatrix();
    }
    resize();
    window.addEventListener('resize', resize);

    const pointer = { x: 0, y: 0 };
    window.addEventListener('pointermove', (e) => {
        pointer.x = (e.clientX / window.innerWidth) * 2 - 1;
        pointer.y = (e.clientY / window.innerHeight) * 2 - 1;
    }, { passive: true });

    let visible = true;
    new IntersectionObserver(([entry]) => { visible = entry.isIntersecting; }).observe(canvas);

    const clock = new THREE.Clock();
    function frame() {
        requestAnimationFrame(frame);
        if (!visible || document.hidden) return;
        const t = clock.getElapsedTime();

        core.rotation.y = t * 0.25;
        core.rotation.x = t * 0.12;
        inner.scale.setScalar(1 + Math.sin(t * 2) * 0.06); // "heartbeat"
        rings.forEach((ring, i) => { ring.rotation.z = t * (0.15 + i * 0.07) * (i % 2 ? -1 : 1); });
        particles.rotation.y = t * 0.02;

        group.rotation.y += (pointer.x * 0.35 - group.rotation.y) * 0.04;
        group.rotation.x += (pointer.y * 0.2 - group.rotation.x) * 0.04;

        renderer.render(scene, camera);
    }

    if (reduceMotion) {
        renderer.render(scene, camera);
    } else {
        frame();
    }
}
