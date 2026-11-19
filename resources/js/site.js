/*
 * Gym website motion system (resources/views/site).
 * GSAP 3.13 + ScrollTrigger, ScrollSmoother and SplitText. Desktop gets smooth scrolling,
 * a custom cursor and magnetic buttons; touch devices get native scrolling and lighter effects;
 * prefers-reduced-motion gets static content.
 */
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { ScrollSmoother } from 'gsap/ScrollSmoother';
import { SplitText } from 'gsap/SplitText';

gsap.registerPlugin(ScrollTrigger, ScrollSmoother, SplitText);

const root = document.documentElement;
// "?nomotion" shows the reduced-motion version (handy for accessibility and layout checks).
const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches || new URLSearchParams(location.search).has('nomotion');
if (reduce) root.classList.add('reduce-motion');
const finePointer = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
const $ = (s, el = document) => el.querySelector(s);
const $$ = (s, el = document) => [...el.querySelectorAll(s)];
const lowPower = () => {
    const c = navigator.connection || {};
    return c.saveData || /2g/.test(c.effectiveType || '') || (navigator.hardwareConcurrency || 8) < 4 || (navigator.deviceMemory || 8) < 4;
};
let smoother = null;

/* ---------- Preloader → intro ---------- */
function preloader() {
    const loader = $('[data-loader]');
    const done = () => { root.classList.add('loaded'); intro(); };
    let seen = false;
    try { seen = sessionStorage.getItem('site-intro') === '1'; sessionStorage.setItem('site-intro', '1'); } catch (e) { /* private mode */ }
    if (!loader || reduce || seen) { done(); return; }

    const count = $('[data-loader-count]', loader);
    const bar = $('[data-loader-bar]', loader);
    const progress = { v: 0 };
    const pageReady = new Promise((resolve) => (document.readyState === 'complete' ? resolve() : window.addEventListener('load', resolve, { once: true })));
    const minTime = new Promise((resolve) => setTimeout(resolve, 1100));

    gsap.to(progress, { v: 90, duration: 1.1, ease: 'power2.out', onUpdate: () => { count.textContent = Math.round(progress.v); bar.style.transform = `scaleX(${progress.v / 100})`; } });
    Promise.race([Promise.all([pageReady, minTime]), new Promise((r) => setTimeout(r, 3500))]).then(() => {
        gsap.timeline({ onComplete: done })
            .to(progress, { v: 100, duration: 0.35, ease: 'power1.inOut', onUpdate: () => { count.textContent = Math.round(progress.v); bar.style.transform = `scaleX(${progress.v / 100})`; } })
            .to(loader, { yPercent: -100, duration: 0.9, ease: 'expo.inOut' }, '+=0.1');
    });
}

function intro() {
    heroVideo();
    if (reduce) return;
    const hero = $('[data-hero]');
    if (!hero) return;
    const h1 = $('h1[data-split]', hero);
    const tl = gsap.timeline({ defaults: { ease: 'expo.out' } });
    tl.from('[data-hero-poster]', { scale: 1.18, duration: 2.4, ease: 'power3.out' }, 0);
    if (h1) {
        SplitText.create(h1, { type: 'lines', mask: 'lines', linesClass: 's-split-line', aria: 'none', onSplit(self) { tl.from(self.lines, { yPercent: 110, duration: 1.3, stagger: 0.12 }, 0.15); } });
        h1.dataset.splitDone = '1';
    }
    tl.from('[data-hero-fade]', { y: 30, opacity: 0, duration: 1.1, stagger: 0.08 }, 0.6);
    gsap.to('[data-scroll-dot]', { y: 14, opacity: 0, duration: 1.4, repeat: -1, ease: 'power2.in' });
}

/* ---------- Hero video: lazy, correct size, paused off-screen ---------- */
function heroVideo() {
    const v = $('[data-hero-video]');
    if (!v || reduce || (navigator.connection || {}).saveData) return;
    v.src = window.innerWidth < 768 ? v.dataset.srcMobile : v.dataset.src;
    v.addEventListener('playing', () => v.classList.add('!opacity-100'), { once: true });
    v.play().catch(() => {});
    ScrollTrigger.create({ trigger: '[data-hero]', start: 'top top', end: 'bottom top', onLeave: () => v.pause(), onEnterBack: () => v.play().catch(() => {}) });
}

/* ---------- Smooth scroll, anchors, header, menu ---------- */
function smoothScroll() {
    if (reduce || !finePointer) return;
    smoother = ScrollSmoother.create({ wrapper: '#smooth-wrapper', content: '#smooth-content', smooth: 1.1, effects: true, smoothTouch: false });
}

function goTo(target) {
    const el = typeof target === 'string' ? $(target) : target;
    if (!el) return;
    if (smoother) smoother.scrollTo(el, true, 'top top');
    else el.scrollIntoView({ behavior: reduce ? 'auto' : 'smooth', block: 'start' });
}

function anchors() {
    document.addEventListener('click', (e) => {
        const a = e.target.closest('a[href^="#"]');
        if (!a || a.getAttribute('href').length < 2) return;
        const target = $(a.getAttribute('href'));
        if (!target) return;
        e.preventDefault();
        closeMenu();
        goTo(target);
        history.replaceState(null, '', a.getAttribute('href'));
        if (a.dataset.interest) {
            const select = $('[data-interest-select]');
            if (select) select.value = a.dataset.interest;
        }
    });
    // Arriving with a hash (e.g. after a form redirect): jump there once layout is ready.
    if (location.hash && $(location.hash)) setTimeout(() => goTo(location.hash), 120);
}

function header() {
    const h = $('[data-header]');
    if (!h) return;
    ScrollTrigger.create({
        start: 0, end: 'max',
        onUpdate(self) {
            const y = self.scroll();
            h.classList.toggle('is-solid', y > 60);
            h.classList.toggle('is-hidden', !root.classList.contains('menu-open') && self.direction === 1 && y > 700);
        },
    });
    $$('[data-nav]').forEach((link) => {
        const section = document.getElementById(link.dataset.nav);
        if (!section) { link.remove(); return; }
        ScrollTrigger.create({ trigger: section, start: 'top 45%', end: 'bottom 45%', onToggle: (self) => link.classList.toggle('is-active', self.isActive) });
    });
}

let menuTl;
function menu() {
    const btn = $('[data-menu-toggle]');
    const panel = $('[data-menu]');
    if (!btn || !panel) return;
    btn.addEventListener('click', () => (root.classList.contains('menu-open') ? closeMenu() : openMenu()));
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeMenu(); });
}
function openMenu() {
    const btn = $('[data-menu-toggle]');
    const panel = $('[data-menu]');
    root.classList.add('menu-open');
    panel.classList.remove('hidden');
    panel.classList.add('flex');
    btn.setAttribute('aria-expanded', 'true');
    btn.setAttribute('aria-label', 'Close menu');
    $('[data-menu-line="1"]').style.transform = 'translateY(3px) rotate(45deg)';
    $('[data-menu-line="2"]').style.transform = 'translateY(3px) rotate(-45deg)';
    if (smoother) smoother.paused(true);
    document.body.style.overflow = 'hidden';
    if (!reduce) {
        menuTl = gsap.timeline()
            .from(panel, { clipPath: 'inset(0 0 100% 0)', duration: 0.7, ease: 'expo.inOut' })
            .from($$('[data-menu-word]', panel), { yPercent: 110, duration: 0.8, stagger: 0.05, ease: 'expo.out' }, '-=0.25')
            .from($$('[data-menu-link]', panel).slice(-1), { opacity: 0, y: 20, duration: 0.5 }, '-=0.5');
    }
    $('a', panel).focus({ preventScroll: true });
}
function closeMenu() {
    if (!root.classList.contains('menu-open')) return;
    const btn = $('[data-menu-toggle]');
    const panel = $('[data-menu]');
    root.classList.remove('menu-open');
    if (menuTl) menuTl.progress(1).kill();
    panel.classList.add('hidden');
    panel.classList.remove('flex');
    btn.setAttribute('aria-expanded', 'false');
    btn.setAttribute('aria-label', 'Open menu');
    $('[data-menu-line="1"]').style.transform = '';
    $('[data-menu-line="2"]').style.transform = '';
    if (smoother) smoother.paused(false);
    document.body.style.overflow = '';
}

/* ---------- Cursor & magnetic buttons (fine pointers only) ---------- */
function cursor() {
    const ring = $('[data-cursor]');
    const dot = $('[data-cursor-dot]');
    if (!finePointer || reduce || !ring) return;
    root.classList.add('has-cursor');
    const label = $('[data-cursor-label]', ring);
    const rx = gsap.quickTo(ring, 'x', { duration: 0.45, ease: 'power3' });
    const ry = gsap.quickTo(ring, 'y', { duration: 0.45, ease: 'power3' });
    const dx = gsap.quickTo(dot, 'x', { duration: 0.08 });
    const dy = gsap.quickTo(dot, 'y', { duration: 0.08 });
    window.addEventListener('pointermove', (e) => {
        if (!root.classList.contains('cursor-moved')) { // first move: jump into place, then follow smoothly
            gsap.set([ring, dot], { x: e.clientX, y: e.clientY });
            root.classList.add('cursor-moved');
        }
        rx(e.clientX); ry(e.clientY); dx(e.clientX); dy(e.clientY);
    });
    document.addEventListener('pointerover', (e) => {
        const textEl = e.target.closest('[data-cursor-text]');
        const hoverEl = e.target.closest('a, button, input, select, textarea, label');
        ring.classList.toggle('is-label', !!textEl);
        ring.classList.toggle('is-hover', !textEl && !!hoverEl);
        label.textContent = textEl ? textEl.dataset.cursorText : '';
    });
    document.addEventListener('pointerleave', () => gsap.to([ring, dot], { opacity: 0, duration: 0.2 }));
    document.addEventListener('pointerenter', () => gsap.to([ring, dot], { opacity: 1, duration: 0.2 }));
}

function magnetic() {
    if (!finePointer || reduce) return;
    $$('[data-magnetic]').forEach((el) => {
        const strength = parseFloat(el.dataset.magnetic) || 0.35;
        const xTo = gsap.quickTo(el, 'x', { duration: 0.6, ease: 'elastic.out(1, 0.4)' });
        const yTo = gsap.quickTo(el, 'y', { duration: 0.6, ease: 'elastic.out(1, 0.4)' });
        el.addEventListener('pointermove', (e) => {
            const r = el.getBoundingClientRect();
            xTo((e.clientX - r.left - r.width / 2) * strength);
            yTo((e.clientY - r.top - r.height / 2) * strength);
        });
        el.addEventListener('pointerleave', () => { xTo(0); yTo(0); });
    });
}

/* ---------- Scroll reveals ---------- */
function reveals() {
    if (reduce) return;
    $$('[data-split]').forEach((el) => {
        if (el.dataset.splitDone) return;
        SplitText.create(el, {
            type: 'lines', mask: 'lines', linesClass: 's-split-line', aria: 'auto', autoSplit: true,
            onSplit: (self) => gsap.from(self.lines, { yPercent: 110, duration: 1.2, ease: 'expo.out', stagger: 0.1, scrollTrigger: { trigger: el, start: 'top 85%', once: true } }),
        });
    });
    ScrollTrigger.batch('[data-reveal-up]', {
        start: 'top 90%', once: true,
        onEnter: (els) => gsap.to(els, { opacity: 1, y: 0, duration: 1, ease: 'expo.out', stagger: 0.08, overwrite: true }),
    });
    $$('[data-reveal-clip]').forEach((el) => {
        const img = $('img, video', el);
        const tl = gsap.timeline({ scrollTrigger: { trigger: el, start: 'top 88%', once: true } });
        tl.to(el, { clipPath: 'inset(0% 0 0 0)', duration: 1.4, ease: 'expo.inOut' });
        if (img) tl.from(img, { scale: 1.35, duration: 1.8, ease: 'expo.out' }, 0);
    });
}

function counters() {
    $$('[data-count]').forEach((el) => {
        const target = Number(el.dataset.count) || 0;
        const fmt = (v) => Math.round(v).toLocaleString('en-IN');
        if (reduce) { el.textContent = fmt(target); return; }
        el.textContent = '0';
        const o = { v: 0 };
        gsap.to(o, { v: target, duration: 2, ease: 'power3.out', onUpdate: () => { el.textContent = fmt(o.v); }, scrollTrigger: { trigger: el, start: 'top 92%', once: true } });
    });
}

/* ---------- Programs: pinned horizontal scroll (desktop), swipe rail (touch) ---------- */
function programs() {
    const section = $('[data-programs]');
    if (!section) return;
    const track = $('[data-programs-track]', section);
    const scroller = $('[data-programs-scroller]', section);
    const bar = $('[data-programs-progress]', section);
    const mm = gsap.matchMedia();
    mm.add('(min-width: 1024px) and (prefers-reduced-motion: no-preference)', () => {
        const distance = () => track.scrollWidth - window.innerWidth;
        gsap.to(track, {
            x: () => -distance(), ease: 'none',
            scrollTrigger: { trigger: section, pin: '[data-programs-pin]', start: 'top top', end: () => '+=' + distance(), scrub: 0.8, invalidateOnRefresh: true, anticipatePin: 1 },
        });
    });
    if (scroller && bar) {
        scroller.addEventListener('scroll', () => {
            const p = scroller.scrollLeft / Math.max(1, scroller.scrollWidth - scroller.clientWidth);
            bar.style.width = `${Math.max(12, p * 100)}%`;
        }, { passive: true });
    }
}

/* ---------- Marquees: speed follows scroll velocity ---------- */
function marquees() {
    const tweens = [];
    $$('[data-marquee]').forEach((el) => tweens.push(gsap.to(el, { xPercent: -50, duration: 32, ease: 'none', repeat: -1 })));
    $$('[data-rail]').forEach((rail) => {
        const list = $('ul', rail);
        const dir = rail.dataset.rail === 'right' ? 1 : -1;
        if (dir === 1) gsap.set(list, { xPercent: -50 });
        const t = gsap.to(list, { xPercent: dir === 1 ? 0 : -50, duration: 46, ease: 'none', repeat: -1 });
        tweens.push(t);
        rail.addEventListener('pointerenter', () => gsap.to(t, { timeScale: 0, duration: 0.6 }));
        rail.addEventListener('pointerleave', () => gsap.to(t, { timeScale: 1, duration: 0.6 }));
    });
    if (reduce) { tweens.forEach((t) => t.pause()); return; }
    ScrollTrigger.create({
        onUpdate(self) {
            const boost = 1 + Math.min(4, Math.abs(self.getVelocity()) / 600);
            tweens.forEach((t) => { if (t.timeScale() > 0.05) gsap.to(t, { timeScale: boost, duration: 0.2, overwrite: true, onComplete: () => gsap.to(t, { timeScale: 1, duration: 1.2 }) }); });
        },
    });
}

/* ---------- Pricing spotlight, before/after compare ---------- */
function spotlight() {
    $$('[data-spotlight]').forEach((card) => card.addEventListener('pointermove', (e) => {
        const r = card.getBoundingClientRect();
        card.style.setProperty('--mx', `${e.clientX - r.left}px`);
        card.style.setProperty('--my', `${e.clientY - r.top}px`);
    }));
}

function compare() {
    $$('[data-compare]').forEach((el) => {
        const range = $('input[type=range]', el);
        const after = $('[data-compare-after]', el);
        const handle = $('[data-compare-handle]', el);
        const set = (v) => { after.style.clipPath = `inset(0 0 0 ${v}%)`; handle.style.left = `${v}%`; };
        range.addEventListener('input', () => set(range.value));
        set(range.value);
        if (!reduce) {
            const o = { v: 82 };
            gsap.to(o, { v: 38, duration: 1.8, ease: 'power3.inOut', onUpdate: () => { range.value = o.v; set(o.v); }, scrollTrigger: { trigger: el, start: 'top 70%', once: true } });
        }
    });
}

/* ---------- Gallery: hover-play video, lightbox ---------- */
function gallery() {
    const box = $('[data-lightbox]');
    const stage = $('[data-lightbox-stage]', box);
    let lastFocus = null;
    const close = () => {
        box.hidden = true;
        stage.innerHTML = '';
        if (smoother) smoother.paused(false);
        document.body.style.overflow = '';
        if (lastFocus) lastFocus.focus({ preventScroll: true });
    };
    $$('[data-gallery-item]').forEach((item) => {
        const v = $('video[data-hover-video]', item);
        if (v && finePointer && !reduce) {
            item.addEventListener('pointerenter', () => { if (!v.src) v.src = v.dataset.src; v.play().catch(() => {}); });
            item.addEventListener('pointerleave', () => v.pause());
        }
        item.addEventListener('click', () => {
            lastFocus = item;
            const media = item.dataset.type === 'video' ? document.createElement('video') : document.createElement('img');
            media.src = item.dataset.src;
            media.className = 'mx-auto max-h-[85vh] w-auto rounded-2xl object-contain';
            if (item.dataset.type === 'video') { Object.assign(media, { controls: true, autoplay: true, playsInline: true }); }
            else { media.alt = item.dataset.alt; }
            stage.replaceChildren(media);
            box.hidden = false;
            if (smoother) smoother.paused(true);
            document.body.style.overflow = 'hidden';
            if (!reduce) gsap.from(media, { scale: 0.92, opacity: 0, duration: 0.6, ease: 'expo.out' });
            $('[data-lightbox-close]', box).focus();
        });
    });
    $('[data-lightbox-close]', box).addEventListener('click', close);
    box.addEventListener('click', (e) => { if (e.target === box) close(); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !box.hidden) close(); });
}

/* ---------- Back to top, page-leave curtain ---------- */
function toTop() {
    const btn = $('[data-to-top]');
    if (!btn) return;
    ScrollTrigger.create({ start: 900, end: 'max', onToggle: (self) => { btn.classList.toggle('opacity-0', !self.isActive); btn.classList.toggle('translate-y-24', !self.isActive); } });
    btn.addEventListener('click', () => (smoother ? smoother.scrollTo(0, true) : window.scrollTo({ top: 0, behavior: reduce ? 'auto' : 'smooth' })));
}

function pageLinks() {
    const curtain = $('[data-curtain]');
    if (!curtain || reduce) return;
    $$('[data-page-link]').forEach((a) => a.addEventListener('click', (e) => {
        if (e.metaKey || e.ctrlKey || e.shiftKey || a.target === '_blank') return;
        e.preventDefault();
        gsap.to(curtain, { scaleY: 1, duration: 0.6, ease: 'expo.inOut', onComplete: () => { window.location.href = a.href; } });
    }));
    // Coming back with the browser's back button: hide the curtain again.
    window.addEventListener('pageshow', () => gsap.set(curtain, { scaleY: 0 }));
}

/* ---------- 3D showcase: loaded only when near the viewport ---------- */
function forge() {
    const canvas = $('[data-forge-3d]');
    if (!canvas) return;
    const fallback = $('[data-forge-fallback]');
    const showFallback = () => { canvas.remove(); fallback.classList.remove('hidden'); };
    const webgl = (() => { try { const c = document.createElement('canvas'); return !!(window.WebGLRenderingContext && (c.getContext('webgl2') || c.getContext('webgl'))); } catch (e) { return false; } })();
    if (reduce || !webgl || lowPower()) { showFallback(); return; }
    // Download Three.js only when the section is ~one screen away; render only while it's on screen.
    ScrollTrigger.create({
        trigger: canvas, start: 'top bottom+=600', once: true,
        onEnter: () => import(/* webpackChunkName: "gym3d" */ './gym3d')
            .then((m) => {
                const scene = m.default(canvas, { finePointer });
                ScrollTrigger.create({ trigger: canvas, start: 'top bottom', end: 'bottom top', onToggle: (self) => scene.setVisible(self.isActive) });
                ScrollTrigger.refresh();
            })
            .catch(showFallback),
    });
}

/* ---------- Boot ---------- */
document.addEventListener('DOMContentLoaded', () => {
    root.classList.add('motion');
    smoothScroll();
    anchors();
    header();
    menu();
    cursor();
    magnetic();
    reveals();
    counters();
    programs();
    marquees();
    spotlight();
    compare();
    gallery();
    toTop();
    pageLinks();
    forge();
    preloader();
    // Fonts change line breaks: re-measure split text and pins once they load.
    if (document.fonts && document.fonts.ready) document.fonts.ready.then(() => ScrollTrigger.refresh());
});
