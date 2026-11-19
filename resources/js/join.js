// QR registration: mobile OTP step, then unlock the rest of the form.
export default function mountJoin(root) {
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const phone = root.querySelector('#phone');
    const sendBtn = root.querySelector('[data-otp-send]');
    const codeWrap = root.querySelector('[data-otp-code]');
    const code = root.querySelector('#otp');
    const verifyBtn = root.querySelector('[data-otp-verify]');
    const msg = root.querySelector('[data-otp-msg]');
    const details = root.querySelector('[data-join-details]');
    const verifiedBadge = root.querySelector('[data-otp-verified]');
    let cooldownTimer = null;

    const say = (text, isError = false) => {
        msg.textContent = text;
        msg.className = 'mt-2 text-sm ' + (isError ? 'text-rose-400' : 'text-volt-300');
    };

    async function post(url, body) {
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify(body),
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
            const first = data.errors ? Object.values(data.errors)[0][0] : data.message;
            throw new Error(res.status === 429 ? 'Too many attempts. Please wait a few minutes.' : (first || 'Something went wrong.'));
        }
        return data;
    }

    function cooldown(seconds) {
        clearInterval(cooldownTimer);
        let left = seconds;
        sendBtn.disabled = true;
        sendBtn.textContent = `Resend in ${left}s`;
        cooldownTimer = setInterval(() => {
            left -= 1;
            if (left <= 0) {
                clearInterval(cooldownTimer);
                sendBtn.disabled = false;
                sendBtn.textContent = 'Resend code';
            } else {
                sendBtn.textContent = `Resend in ${left}s`;
            }
        }, 1000);
    }

    function unlock(verifiedPhone) {
        phone.readOnly = true;
        sendBtn.classList.add('hidden');
        codeWrap.classList.add('hidden');
        verifiedBadge.classList.remove('hidden');
        details.classList.remove('hidden');
        details.querySelectorAll('input, select, textarea, button').forEach((el) => { el.disabled = false; });
        if (verifiedPhone) phone.value = verifiedPhone.replace(/^\+91/, ''); // "+91" is shown as a prefix
        msg.textContent = '';
    }

    sendBtn?.addEventListener('click', async () => {
        sendBtn.disabled = true;
        try {
            const data = await post(root.dataset.sendUrl, { phone: phone.value });
            codeWrap.classList.remove('hidden');
            say(data.message);
            code.focus();
            cooldown(data.resend_after || 30);
        } catch (e) {
            say(e.message, true);
            sendBtn.disabled = false;
        }
    });

    verifyBtn?.addEventListener('click', async () => {
        verifyBtn.disabled = true;
        try {
            const data = await post(root.dataset.verifyUrl, { phone: phone.value, code: code.value });
            unlock(data.phone);
        } catch (e) {
            say(e.message, true);
        } finally {
            verifyBtn.disabled = false;
        }
    });

    code?.addEventListener('input', () => {
        code.value = code.value.replace(/\D/g, '').slice(0, 6);
        if (code.value.length === 6) verifyBtn.click();
    });

    if (root.dataset.verified === '1') unlock();

    // Plan picker: highlight the selected card.
    root.querySelectorAll('input[name="membership_plan_id"]').forEach((radio) => {
        radio.addEventListener('change', () => {
            root.querySelectorAll('[data-plan-card]').forEach((c) => c.classList.remove('ring-2', 'ring-volt-400'));
            radio.closest('[data-plan-card]').classList.add('ring-2', 'ring-volt-400');
        });
        if (radio.checked) radio.closest('[data-plan-card]').classList.add('ring-2', 'ring-volt-400');
    });
}
