/*
 * 3D showcase for the gym website: a chrome dumbbell with bumper plates in front of a cast-iron
 * 20 kg plate. Studio lighting (environment reflections, warm key, orange rim), soft contact
 * shadow, idle float, pointer parallax and drag-to-spin with inertia. Renders only while visible.
 */
import * as THREE from 'three';
import { RoomEnvironment } from 'three/examples/jsm/environments/RoomEnvironment.js';

const ACCENT = 0xff4d12;

export default function gym3d(canvas, { finePointer = true } = {}) {
    const renderer = new THREE.WebGLRenderer({ canvas, antialias: true, alpha: true, powerPreference: 'high-performance' });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, finePointer ? 2 : 1.5));
    renderer.outputColorSpace = THREE.SRGBColorSpace;
    renderer.toneMapping = THREE.ACESFilmicToneMapping;
    renderer.toneMappingExposure = 1.05;
    renderer.shadowMap.enabled = true;
    renderer.shadowMap.type = THREE.PCFSoftShadowMap;

    const scene = new THREE.Scene();
    const pmrem = new THREE.PMREMGenerator(renderer);
    scene.environment = pmrem.fromScene(new RoomEnvironment(renderer), 0.04).texture;
    pmrem.dispose();

    const camera = new THREE.PerspectiveCamera(32, 1, 0.1, 50);
    camera.position.set(0, 0.5, 9);

    /* Lights */
    scene.add(new THREE.HemisphereLight(0xffffff, 0x0b0b0c, 0.6));
    const key = new THREE.SpotLight(0xfff1e6, 60, 30, Math.PI / 6, 0.5, 1.6);
    key.position.set(-4, 6, 6);
    key.castShadow = true;
    key.shadow.mapSize.set(1024, 1024);
    key.shadow.bias = -0.0005;
    scene.add(key);
    const rim = new THREE.PointLight(ACCENT, 45, 20, 1.8);
    rim.position.set(4, 1.5, -3);
    scene.add(rim);
    const fill = new THREE.PointLight(0x7aa2ff, 6, 20, 2);
    fill.position.set(-5, -2, 2);
    scene.add(fill);
    const front = new THREE.DirectionalLight(0xffffff, 0.9); // soft front light so flat plate faces read
    front.position.set(1, 2, 8);
    scene.add(front);

    /* Materials */
    const chrome = new THREE.MeshStandardMaterial({ color: 0xdedede, metalness: 1, roughness: 0.18, bumpMap: knurlTexture(), bumpScale: 0.6 });
    const steel = new THREE.MeshStandardMaterial({ color: 0xb8b8b8, metalness: 1, roughness: 0.3 });
    const rubber = new THREE.MeshStandardMaterial({ color: 0x141416, metalness: 0.05, roughness: 0.75 });
    const orange = new THREE.MeshStandardMaterial({ color: ACCENT, metalness: 0.2, roughness: 0.45, emissive: ACCENT, emissiveIntensity: 0.08 });
    const castIron = new THREE.MeshStandardMaterial({ color: 0x3a3a40, metalness: 0.75, roughness: 0.38 });

    const showcase = new THREE.Group();
    showcase.scale.setScalar(1.3);
    scene.add(showcase);

    /* Dumbbell */
    const dumbbell = new THREE.Group();
    const bar = new THREE.Mesh(new THREE.CylinderGeometry(0.09, 0.09, 2.1, 32), chrome);
    bar.rotation.z = Math.PI / 2;
    dumbbell.add(bar);
    [-1, 1].forEach((side) => {
        const collar = new THREE.Mesh(new THREE.CylinderGeometry(0.17, 0.17, 0.12, 32), steel);
        collar.rotation.z = Math.PI / 2;
        collar.position.x = side * 0.62;
        dumbbell.add(collar);
        [[0.78, 0.62, 0.2], [0.98, 0.5, 0.16]].forEach(([x, r, w], i) => {
            const plate = bumperPlate(r, w, rubber, i === 0 ? orange : rubber);
            plate.rotation.z = side * Math.PI / 2;
            plate.position.x = side * x;
            dumbbell.add(plate);
        });
        const cap = new THREE.Mesh(new THREE.CylinderGeometry(0.12, 0.12, 0.08, 32), steel);
        cap.rotation.z = Math.PI / 2;
        cap.position.x = side * 1.1;
        dumbbell.add(cap);
    });
    dumbbell.traverse((o) => { if (o.isMesh) { o.castShadow = true; o.receiveShadow = true; } });
    dumbbell.position.set(0, -0.15, 1.1);
    dumbbell.rotation.set(0.35, -0.5, 0.15);
    showcase.add(dumbbell);

    /* Cast-iron 20 kg plate behind */
    const plate = ironPlate(castIron);
    plate.position.set(0.55, 0.45, -1.2);
    plate.rotation.set(0.1, -0.2, 0);
    plate.traverse((o) => { if (o.isMesh) { o.castShadow = true; o.receiveShadow = true; } });
    showcase.add(plate);

    /* Contact shadow */
    const ground = new THREE.Mesh(new THREE.PlaneGeometry(14, 14), new THREE.ShadowMaterial({ opacity: 0.45 }));
    ground.rotation.x = -Math.PI / 2;
    ground.position.y = -2.2;
    ground.receiveShadow = true;
    scene.add(ground);

    /* Interaction: pointer parallax + drag to spin with inertia */
    const pointer = { x: 0, y: 0 };
    let dragging = false;
    let lastX = 0;
    let spin = 0;
    let spinVel = 0.0025;
    const onMove = (e) => {
        const r = canvas.getBoundingClientRect();
        pointer.x = ((e.clientX - r.left) / r.width) * 2 - 1;
        pointer.y = ((e.clientY - r.top) / r.height) * 2 - 1;
        if (dragging) { spinVel = (e.clientX - lastX) * 0.0009; lastX = e.clientX; }
    };
    window.addEventListener('pointermove', onMove, { passive: true });
    canvas.addEventListener('pointerdown', (e) => { dragging = true; lastX = e.clientX; });
    window.addEventListener('pointerup', () => { dragging = false; });

    /* Size */
    const resize = () => {
        const { width, height } = canvas.getBoundingClientRect();
        if (!width || !height) return;
        renderer.setSize(width, height, false);
        camera.aspect = width / height;
        camera.position.z = width < 520 ? 8.4 : 7.2;
        camera.updateProjectionMatrix();
    };
    new ResizeObserver(resize).observe(canvas);
    resize();

    /* Loop — only while on screen */
    let running = false;
    let raf = 0;
    const clock = new THREE.Clock();
    const tick = () => {
        const t = clock.getElapsedTime();
        if (!dragging) spinVel += (0.0025 - spinVel) * 0.02; // ease back to idle spin
        spin += spinVel;
        dumbbell.rotation.y = -0.5 + spin;
        dumbbell.position.y = -0.15 + Math.sin(t * 1.2) * 0.08;
        plate.rotation.z = spin * 0.35;
        plate.position.y = 0.45 + Math.sin(t * 0.9 + 1) * 0.05;
        showcase.rotation.y += (pointer.x * 0.35 - showcase.rotation.y) * 0.05;
        showcase.rotation.x += (pointer.y * 0.18 - showcase.rotation.x) * 0.05;
        camera.position.x += (pointer.x * 0.4 - camera.position.x) * 0.03;
        camera.lookAt(0, 0, 0);
        rim.intensity = 40 + Math.sin(t * 2) * 6;
        renderer.render(scene, camera);
        raf = requestAnimationFrame(tick);
    };
    let visible = false;
    const start = () => { if (!running && visible && !document.hidden) { running = true; clock.start(); tick(); } };
    const stop = () => { running = false; cancelAnimationFrame(raf); };
    document.addEventListener('visibilitychange', () => (document.hidden ? stop() : start()));
    canvas.classList.add('is-ready');
    renderer.render(scene, camera); // first frame straight away

    // The page tells us when the canvas is on screen (site.js uses ScrollTrigger for this).
    return {
        setVisible(v) { visible = v; if (v) start(); else stop(); },
    };
}

/** Rubber bumper plate: lathe profile with rounded edges and a steel hub. */
function bumperPlate(radius, width, rubber, ring) {
    const g = new THREE.Group();
    const h = width / 2;
    const profile = [
        [0.1, -h], [radius - 0.04, -h], [radius, -h + 0.04], [radius, h - 0.04], [radius - 0.04, h], [0.1, h],
    ].map(([x, y]) => new THREE.Vector2(x, y));
    g.add(new THREE.Mesh(new THREE.LatheGeometry(profile, 64), rubber));
    const stripe = new THREE.Mesh(new THREE.CylinderGeometry(radius + 0.004, radius + 0.004, width * 0.28, 64, 1, true), ring);
    g.add(stripe);
    return g;
}

/** Cast-iron 20 kg plate with a raised rim, recessed face, hub and engraved label. */
function ironPlate(iron) {
    const g = new THREE.Group();
    const r = 1.55;
    const profile = [
        [0.16, -0.16], [0.42, -0.16], [0.46, -0.11], [r - 0.2, -0.09], [r - 0.14, -0.16], [r - 0.02, -0.16], [r, -0.13],
        [r, 0.13], [r - 0.02, 0.16], [r - 0.14, 0.16], [r - 0.2, 0.09], [0.46, 0.11], [0.42, 0.16], [0.16, 0.16],
    ].map(([x, y]) => new THREE.Vector2(x, y));
    const body = new THREE.Mesh(new THREE.LatheGeometry(profile, 96), iron);
    body.rotation.x = Math.PI / 2;
    g.add(body);

    const hub = new THREE.Mesh(new THREE.CylinderGeometry(0.2, 0.2, 0.34, 48, 1, true), new THREE.MeshStandardMaterial({ color: 0xc9c9c9, metalness: 1, roughness: 0.25, side: THREE.DoubleSide }));
    hub.rotation.x = Math.PI / 2;
    g.add(hub);

    const label = new THREE.Mesh(
        new THREE.RingGeometry(0.62, r - 0.24, 96),
        new THREE.MeshStandardMaterial({ map: labelTexture(), transparent: true, metalness: 0.4, roughness: 0.5 }),
    );
    label.position.z = 0.118; // just proud of the sloped face, still inside the raised rim (0.16)
    g.add(label);
    return g;
}

function labelTexture() {
    const c = document.createElement('canvas');
    c.width = c.height = 1024;
    const ctx = c.getContext('2d');
    ctx.translate(512, 512);
    ctx.fillStyle = '#ff4d12';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.font = '700 150px Anton, Impact, sans-serif';
    ctx.fillText('20 KG', 0, -300);
    ctx.font = '600 56px Inter, Arial, sans-serif';
    ctx.fillStyle = 'rgba(255,255,255,.55)';
    ctx.save();
    ctx.rotate(Math.PI);
    ctx.fillText('CALIBRATED · 44 LB', 0, -300);
    ctx.restore();
    const tex = new THREE.CanvasTexture(c);
    tex.colorSpace = THREE.SRGBColorSpace;
    tex.anisotropy = 4;
    return tex;
}

/** Diamond knurl bump texture for the dumbbell handle. */
function knurlTexture() {
    const c = document.createElement('canvas');
    c.width = c.height = 128;
    const ctx = c.getContext('2d');
    ctx.fillStyle = '#808080';
    ctx.fillRect(0, 0, 128, 128);
    ctx.strokeStyle = '#ffffff';
    ctx.lineWidth = 3;
    for (let i = -128; i < 256; i += 16) {
        ctx.beginPath(); ctx.moveTo(i, 0); ctx.lineTo(i + 128, 128); ctx.stroke();
        ctx.beginPath(); ctx.moveTo(i + 128, 0); ctx.lineTo(i, 128); ctx.stroke();
    }
    const tex = new THREE.CanvasTexture(c);
    tex.wrapS = tex.wrapT = THREE.RepeatWrapping;
    tex.repeat.set(1, 8);
    return tex;
}
