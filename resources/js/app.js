require('./bootstrap');

import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import ApexCharts from 'apexcharts';

gsap.registerPlugin(ScrollTrigger);

const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const csrf = () => document.querySelector('meta[name="csrf-token"]').content;

/* ---------- Animated typography ---------- */
// <h1 data-split> → each word slides up from a mask.
function splitText() {
    // Wrap each word of every text node (nested spans keep their styling).
    const wrapWords = (node) => {
        [...node.childNodes].forEach((child) => {
            if (child.nodeType === Node.ELEMENT_NODE) { wrapWords(child); return; }
            if (child.nodeType !== Node.TEXT_NODE || !child.textContent.trim()) return;
            const frag = document.createDocumentFragment();
            child.textContent.split(/(\s+)/).forEach((part) => {
                if (!part) return;
                if (/^\s+$/.test(part)) { frag.appendChild(document.createTextNode(' ')); return; }
                const mask = document.createElement('span');
                mask.className = 'inline-block overflow-hidden align-bottom pb-[0.08em]';
                const word = document.createElement('span');
                word.className = 'split-word inline-block';
                word.textContent = part;
                mask.appendChild(word);
                frag.appendChild(mask);
            });
            child.replaceWith(frag);
        });
    };

    document.querySelectorAll('[data-split]').forEach((el) => {
        wrapWords(el);
        if (reduceMotion) return;
        gsap.from(el.querySelectorAll('.split-word'), { yPercent: 110, duration: 0.9, ease: 'power4.out', stagger: 0.06, delay: 0.1 });
    });
}

/* ---------- Animations ---------- */
function animate() {
    if (reduceMotion) return;

    const intro = document.querySelectorAll('[data-intro]');
    if (intro.length) {
        gsap.from(intro, { y: 28, opacity: 0, duration: 0.8, ease: 'power3.out', stagger: 0.08, delay: 0.15 });
    }

    document.querySelectorAll('[data-reveal]').forEach((el) => {
        gsap.from(el, { y: 40, opacity: 0, duration: 0.9, ease: 'power3.out', scrollTrigger: { trigger: el, start: 'top 88%' } });
    });

    // Staggered children: <div data-stagger> children reveal one after another.
    document.querySelectorAll('[data-stagger]').forEach((el) => {
        gsap.from(el.children, { y: 36, opacity: 0, duration: 0.7, ease: 'power3.out', stagger: 0.08, scrollTrigger: { trigger: el, start: 'top 85%' } });
    });

    // Parallax layers.
    document.querySelectorAll('[data-parallax]').forEach((el) => {
        gsap.to(el, { yPercent: Number(el.dataset.parallax) || -20, ease: 'none', scrollTrigger: { trigger: el.parentElement, scrub: true } });
    });

    // Infinite marquee.
    document.querySelectorAll('[data-marquee]').forEach((el) => {
        gsap.to(el, { xPercent: -50, duration: 30, ease: 'none', repeat: -1 });
    });
}

/* Count-up numbers start when scrolled into view. */
function countUps() {
    document.querySelectorAll('[data-count]').forEach((el) => {
        const target = Number(el.dataset.count) || 0;
        const decimals = Number(el.dataset.decimals || 0);
        const fmt = (v) => v.toLocaleString('en-IN', { minimumFractionDigits: decimals, maximumFractionDigits: decimals });
        if (reduceMotion) { el.textContent = fmt(target); return; }
        const obj = { v: 0 };
        gsap.to(obj, {
            v: target, duration: 1.6, ease: 'power2.out',
            scrollTrigger: { trigger: el, start: 'top 95%' },
            onUpdate: () => { el.textContent = fmt(obj.v); },
        });
    });
}

/* ---------- Interactive pricing / tilt cards ---------- */
function tiltCards() {
    if (reduceMotion || window.matchMedia('(hover: none)').matches) return;
    document.querySelectorAll('[data-tilt]').forEach((card) => {
        card.style.transformStyle = 'preserve-3d';
        card.addEventListener('pointermove', (e) => {
            const r = card.getBoundingClientRect();
            const x = (e.clientX - r.left) / r.width - 0.5;
            const y = (e.clientY - r.top) / r.height - 0.5;
            gsap.to(card, { rotateY: x * 10, rotateX: -y * 10, transformPerspective: 900, duration: 0.4, ease: 'power2.out' });
            card.style.setProperty('--mx', `${(x + 0.5) * 100}%`);
            card.style.setProperty('--my', `${(y + 0.5) * 100}%`);
        });
        card.addEventListener('pointerleave', () => gsap.to(card, { rotateY: 0, rotateX: 0, duration: 0.6, ease: 'power3.out' }));
    });
}

/* ---------- Charts ---------- */
const chartBase = {
    chart: { toolbar: { show: false }, foreColor: '#94a3b8', fontFamily: 'Inter, sans-serif', zoom: { enabled: false } },
    grid: { borderColor: '#262E3B', strokeDashArray: 4 },
    tooltip: { theme: 'dark' },
    dataLabels: { enabled: false },
    legend: { labels: { colors: '#cbd5e1' } },
};
const palette = ['#FFD400', '#DF6826', '#38bdf8', '#f472b6'];

function charts() {
    document.querySelectorAll('[data-chart]').forEach((el) => {
        const data = JSON.parse(el.dataset.series || '{}');
        const type = el.dataset.chart;
        const animations = { enabled: !reduceMotion };

        if (type === 'growth') {
            new ApexCharts(el, {
                ...chartBase,
                chart: { ...chartBase.chart, type: 'area', height: 260, animations },
                series: [{ name: 'New sign-ups', data: data.values || [] }],
                xaxis: { categories: data.labels || [], axisBorder: { show: false }, axisTicks: { show: false } },
                yaxis: { min: 0, forceNiceScale: true, labels: { formatter: (v) => Math.round(v) } },
                colors: ['#FFD400'],
                stroke: { curve: 'smooth', width: 2 },
                fill: { type: 'gradient', gradient: { opacityFrom: 0.35, opacityTo: 0, stops: [0, 100] } },
            }).render();
            return;
        }

        // Generic line/area: { labels, series: [{name, data}], unit, target }
        const unit = data.unit ? ` ${data.unit}` : '';
        const annotations = data.target ? { yaxis: [{ y: data.target, borderColor: '#DF6826', strokeDashArray: 6, label: { text: `Target ${data.target}${unit}`, style: { background: '#DF6826', color: '#080B11' } } }] } : {};
        new ApexCharts(el, {
            ...chartBase,
            chart: { ...chartBase.chart, type: type === 'area' ? 'area' : 'line', height: Number(el.dataset.height) || 280, animations },
            series: data.series || [],
            xaxis: { categories: data.labels || [], axisBorder: { show: false }, axisTicks: { show: false } },
            yaxis: { forceNiceScale: true, labels: { formatter: (v) => (v == null ? '' : `${Math.round(v * 10) / 10}${unit}`) } },
            colors: palette,
            stroke: { curve: 'smooth', width: 2.5 },
            markers: { size: 3, strokeWidth: 0 },
            fill: type === 'area' ? { type: 'gradient', gradient: { opacityFrom: 0.3, opacityTo: 0, stops: [0, 100] } } : { opacity: 1 },
            annotations,
            noData: { text: 'No data yet', style: { color: '#64748b' } },
        }).render();
    });
}

/* ---------- UI helpers ---------- */
function ui() {
    document.querySelectorAll('[data-toggle]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const target = document.querySelector(btn.dataset.toggle);
            if (!target) return;
            const open = target.classList.toggle('hidden') === false;
            btn.setAttribute('aria-expanded', String(open));
        });
    });

    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (e) => { if (!window.confirm(form.dataset.confirm)) e.preventDefault(); });
    });

    document.querySelectorAll('[data-flash]').forEach((el) => {
        setTimeout(() => {
            if (reduceMotion) { el.remove(); return; }
            gsap.to(el, { opacity: 0, y: -10, duration: 0.4, onComplete: () => el.remove() });
        }, 6000);
    });

    // Image inputs with live preview: <input type=file data-preview="#img">
    document.querySelectorAll('input[type=file][data-preview]').forEach((input) => {
        input.addEventListener('change', () => {
            const img = document.querySelector(input.dataset.preview);
            const file = input.files && input.files[0];
            if (!img || !file) return;
            img.src = URL.createObjectURL(file);
            img.classList.remove('hidden');
            img.parentElement.querySelector('[data-preview-placeholder]')?.classList.add('hidden');
        });
    });

    // Before/after comparison slider.
    document.querySelectorAll('[data-compare]').forEach((wrap) => {
        const range = wrap.querySelector('input[type=range]');
        const top = wrap.querySelector('[data-compare-top]');
        const handle = wrap.querySelector('[data-compare-handle]');
        const set = () => {
            top.style.clipPath = `inset(0 ${100 - range.value}% 0 0)`;
            handle.style.left = `${range.value}%`;
        };
        range.addEventListener('input', set);
        set();
    });

    // Submit buttons show a busy state.
    document.querySelectorAll('form[data-busy]').forEach((form) => {
        form.addEventListener('submit', () => {
            const btn = form.querySelector('[type=submit]');
            if (btn) { btn.disabled = true; btn.dataset.label = btn.textContent; btn.textContent = form.dataset.busy; }
        });
    });
}

/* ---------- Razorpay checkout ---------- */
function payments() {
    document.querySelectorAll('[data-pay]').forEach((btn) => {
        btn.addEventListener('click', async () => {
            const err = document.querySelector('[data-pay-error]');
            btn.disabled = true;
            try {
                if (!window.Razorpay) throw new Error('Payment window failed to load. Check your connection and try again.');
                const res = await fetch(btn.dataset.pay, { method: 'POST', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf() } });
                const order = await res.json();
                if (!res.ok) throw new Error(order.message || 'Could not start payment.');

                const rzp = new window.Razorpay({
                    key: order.key,
                    order_id: order.order_id,
                    amount: order.amount,
                    currency: order.currency,
                    name: order.name,
                    description: order.description,
                    prefill: order.prefill,
                    theme: { color: '#FFD400' },
                    handler: async (response) => {
                        const v = await fetch(btn.dataset.verify, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf() },
                            body: JSON.stringify(response),
                        });
                        const out = await v.json();
                        if (v.ok) window.location = out.redirect;
                        else if (err) err.textContent = out.message;
                    },
                    modal: { ondismiss: () => { btn.disabled = false; } },
                });
                rzp.on('payment.failed', (r) => { if (err) err.textContent = r.error.description; });
                rzp.open();
            } catch (e) {
                if (err) err.textContent = e.message;
                btn.disabled = false;
            }
        });
    });
}

/* ---------- Form errors: outline fields that have an error, and bring the first one into view ---------- */
function formErrors() {
    const errors = [...document.querySelectorAll('.field-error')];
    errors.forEach((msg) => {
        // The field is the nearest input/select/textarea before the message, in the same block.
        let field = null;
        for (let node = msg.previousElementSibling; node && !field; node = node.previousElementSibling) {
            field = node.matches('input, select, textarea') ? node : node.querySelector('input:not([type=hidden]), select, textarea');
        }
        if (!field) field = msg.parentElement.querySelector('input:not([type=hidden]), select, textarea');
        if (!field) return;
        field.setAttribute('aria-invalid', 'true');
        if (field.type === 'checkbox' || field.type === 'radio' || field.type === 'file') {
            (field.closest('label') || field.parentElement).classList.add('rounded-lg', 'ring-2', 'ring-rose-500/70', 'ring-offset-2', 'ring-offset-ink-950');
        } else {
            field.classList.add('!border-rose-500', '!ring-rose-500/40');
        }
        if (msg.id === '') msg.id = `err-${Math.random().toString(36).slice(2)}`;
        field.setAttribute('aria-describedby', msg.id);
    });

    const first = document.querySelector('[aria-invalid="true"]');
    if (first) {
        // Let entrance animations settle, then scroll the first problem to the middle of the screen.
        setTimeout(() => {
            first.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'center' });
            if (first.type !== 'file') first.focus({ preventScroll: true });
        }, 450);
    }

    // Size check as soon as a file is chosen: <input type=file data-max-mb="10">.
    document.querySelectorAll('input[type=file][data-max-mb]').forEach((input) => {
        input.addEventListener('change', () => {
            const file = input.files[0];
            const max = Number(input.dataset.maxMb);
            const accept = (input.accept || '').split(',').map((t) => t.trim()).filter(Boolean);
            let problem = '';
            if (file && file.size > max * 1024 * 1024) problem = `This file is ${(file.size / 1048576).toFixed(1)} MB. Please choose one under ${max} MB.`;
            else if (file && accept.length && !accept.includes(file.type)) problem = 'Please choose a JPG, PNG, WebP or GIF image.';
            let note = input.parentElement.querySelector('[data-file-problem]');
            if (!note) {
                note = document.createElement('p');
                note.className = 'field-error';
                note.setAttribute('data-file-problem', '');
                note.setAttribute('role', 'alert');
                input.insertAdjacentElement('afterend', note);
            }
            note.textContent = problem;
            note.hidden = !problem;
            if (problem) input.value = '';
        });
    });
}

/* ---------- AI job status polling: <div data-poll="/status-url"> reloads when no longer pending ---------- */
function polling() {
    document.querySelectorAll('[data-poll]').forEach((el) => {
        let tries = 0;
        const tick = async () => {
            tries += 1;
            const res = await fetch(el.dataset.poll, { headers: { Accept: 'application/json' } }).catch(() => null);
            const data = res && res.ok ? await res.json() : null;
            if (data && data.status !== 'pending') { window.location.reload(); return; }
            if (tries < 75) setTimeout(tick, 4000);
        };
        setTimeout(tick, 4000);
    });
}

/* ---------- Scan eligibility countdown: <div data-countdown="ISO date"> with [data-unit] children ---------- */
function countdowns() {
    document.querySelectorAll('[data-countdown]').forEach((el) => {
        const target = new Date(el.dataset.countdown).getTime();
        const units = Object.fromEntries([...el.querySelectorAll('[data-unit]')].map((u) => [u.dataset.unit, u]));
        const pad = (n) => String(n).padStart(2, '0');
        const render = () => {
            const left = Math.max(0, Math.floor((target - Date.now()) / 1000));
            if (units.days) units.days.textContent = Math.floor(left / 86400);
            if (units.hours) units.hours.textContent = pad(Math.floor((left % 86400) / 3600));
            if (units.minutes) units.minutes.textContent = pad(Math.floor((left % 3600) / 60));
            if (units.seconds) units.seconds.textContent = pad(left % 60);
            if (left === 0) {
                clearInterval(timer);
                // The server decides eligibility; reload to get the unlocked form.
                setTimeout(() => window.location.reload(), 1500);
            }
        };
        const timer = setInterval(render, 1000);
        render();
    });
}

/* ---------- Marketing site helpers ---------- */
function marketing() {
    // Monthly / yearly pricing switch: <div data-billing-toggle> with <button data-billing="monthly|yearly">.
    document.querySelectorAll('[data-billing-toggle]').forEach((toggle) => {
        const buttons = toggle.querySelectorAll('[data-billing]');
        const set = (cycle) => {
            buttons.forEach((b) => {
                const on = b.dataset.billing === cycle;
                b.setAttribute('aria-pressed', on ? 'true' : 'false');
                b.classList.toggle('bg-volt-400', on);
                b.classList.toggle('text-ink-950', on);
                b.classList.toggle('text-slate-300', !on);
            });
            document.querySelectorAll('[data-price], [data-price-note]').forEach((el) => {
                el.textContent = el.dataset[cycle];
                if (!reduceMotion && el.hasAttribute('data-price')) gsap.fromTo(el, { y: -8, opacity: 0 }, { y: 0, opacity: 1, duration: 0.35, ease: 'power2.out' });
            });
        };
        buttons.forEach((b) => b.addEventListener('click', () => set(b.dataset.billing)));
        set('monthly');
    });

    // Tabs: <div data-tabs> with [data-tab="x"] buttons and [data-panel="x"] panels.
    document.querySelectorAll('[data-tabs]').forEach((root) => {
        const tabs = root.querySelectorAll('[data-tab]');
        const show = (name) => {
            tabs.forEach((t) => {
                const on = t.dataset.tab === name;
                t.setAttribute('aria-selected', on ? 'true' : 'false');
                t.classList.toggle('bg-volt-400', on);
                t.classList.toggle('text-ink-950', on);
                t.classList.toggle('text-slate-300', !on);
            });
            root.querySelectorAll('[data-panel]').forEach((p) => {
                const on = p.dataset.panel === name;
                p.hidden = !on;
                if (on && !reduceMotion) gsap.from(p.children, { y: 24, opacity: 0, duration: 0.5, stagger: 0.06, ease: 'power3.out' });
            });
        };
        tabs.forEach((t) => t.addEventListener('click', () => show(t.dataset.tab)));
        show(tabs[0].dataset.tab);
    });

    // A vertical line that fills as the section scrolls past: <div data-progress-line>.
    if (!reduceMotion) {
        document.querySelectorAll('[data-progress-line]').forEach((el) => {
            gsap.fromTo(el, { scaleY: 0 }, { scaleY: 1, ease: 'none', transformOrigin: 'top', scrollTrigger: { trigger: el.parentElement, start: 'top 70%', end: 'bottom 60%', scrub: true } });
        });
    }

    // Before/after demo slider: <div data-ba> containing <input type=range> and [data-ba-after] layer.
    document.querySelectorAll('[data-ba]').forEach((el) => {
        const range = el.querySelector('input[type=range]');
        const after = el.querySelector('[data-ba-after]');
        const handle = el.querySelector('[data-ba-handle]');
        const set = (v) => {
            after.style.clipPath = `inset(0 0 0 ${v}%)`;
            if (handle) handle.style.left = `${v}%`;
        };
        range.addEventListener('input', () => set(range.value));
        set(range.value);
        if (!reduceMotion) {
            const o = { v: 85 };
            gsap.to(o, { v: 35, duration: 1.6, ease: 'power2.inOut', scrollTrigger: { trigger: el, start: 'top 75%' }, onUpdate: () => { range.value = o.v; set(o.v); } });
        }
    });

    // Bars in mock dashboards grow when scrolled into view: <span data-bar="64">.
    document.querySelectorAll('[data-bar]').forEach((el) => {
        const h = `${el.dataset.bar}%`;
        if (reduceMotion) { el.style.height = h; return; }
        gsap.fromTo(el, { height: '4%' }, { height: h, duration: 1.1, ease: 'power3.out', scrollTrigger: { trigger: el, start: 'top 95%' } });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    formErrors();
    marketing();
    ui();
    splitText();
    animate();
    countUps();
    tiltCards();
    charts();
    payments();
    polling();
    countdowns();

    const hero = document.querySelector('[data-hero-3d]');
    if (hero) {
        import(/* webpackChunkName: "hero3d" */ './hero3d').then((m) => m.default(hero)).catch(() => hero.remove());
    }
    const join = document.querySelector('[data-join]');
    if (join) {
        import(/* webpackChunkName: "join" */ './join').then((m) => m.default(join));
    }
});
