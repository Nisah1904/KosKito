(function () {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

    function escapeHtml(s) {
        return s.replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    }

    function render(text, detailBase) {
        let html = escapeHtml(text);
        html = html.replace(/\[\[kost:\s*(?:ID\s*)?(\d+)\s*\|([^\]]+)\]\]/gi, (_, id, name) => `<a href="${detailBase}/${id}" class="ai-kost-link">${name}</a>`);
        html = html.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
        html = html.replace(/^#{1,4}\s*(.+)$/gm, '<strong>$1</strong>');
        html = html.replace(/^\s*[-*]\s+(.+)$/gm, '<span class="ai-li">• $1</span>');
        return html.replace(/\n/g, '<br>');
    }

    async function streamSse(url, body, onEvent) {
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'text/event-stream', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify(body),
        });
        if (!res.ok) {
            let msg = 'Gagal menghubungi asisten AI.';
            try { msg = (await res.json()).message || msg; } catch (e) {}
            onEvent({ type: 'error', message: msg });
            return;
        }
        const reader = res.body.getReader();
        const decoder = new TextDecoder();
        let buffer = '';
        while (true) {
            const { value, done } = await reader.read();
            if (done) break;
            buffer += decoder.decode(value, { stream: true });
            const parts = buffer.split('\n\n');
            buffer = parts.pop();
            parts.forEach((p) => {
                const line = p.trim();
                if (line.startsWith('data: ')) onEvent(JSON.parse(line.slice(6)));
            });
        }
    }

    const chat = document.getElementById('aiChat');
    if (chat) {
        const box = document.getElementById('aiMessages');
        const form = document.getElementById('aiForm');
        const input = document.getElementById('aiInput');
        const send = document.getElementById('aiSend');
        const remaining = document.getElementById('aiRemaining');
        const base = chat.dataset.detailBase;
        let conversationId = chat.dataset.conversationId || null;

        box.querySelectorAll('.ai-assistant').forEach((el) => { el.innerHTML = render(el.dataset.raw, base); });
        box.scrollTop = box.scrollHeight;

        const bubble = (role, text) => {
            document.getElementById('aiEmpty')?.remove();
            const el = document.createElement('div');
            el.className = `ai-bubble ai-${role}`;
            el.textContent = text;
            box.appendChild(el);
            box.scrollTop = box.scrollHeight;
            return el;
        };

        document.querySelectorAll('.ai-suggestion').forEach((b) => b.addEventListener('click', () => {
            input.value = b.textContent.trim();
            form.requestSubmit();
        }));

        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); form.requestSubmit(); }
        });

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const message = input.value.trim();
            if (!message || send.disabled) return;
            bubble('user', message);
            input.value = '';
            send.disabled = true;
            const reply = bubble('assistant', '');
            reply.classList.add('is-typing');
            reply.dataset.testid = 'ai-reply-streaming';
            let text = '';
            await streamSse(chat.dataset.sendUrl, { message, conversation_id: conversationId }, (ev) => {
                if (ev.type === 'meta') {
                    conversationId = ev.conversation_id;
                    history.replaceState(null, '', `?c=${conversationId}`);
                } else if (ev.type === 'delta') {
                    text += ev.text;
                    reply.classList.remove('is-typing');
                    reply.innerHTML = render(text, base);
                    box.scrollTop = box.scrollHeight;
                } else if (ev.type === 'error') {
                    reply.classList.remove('is-typing');
                    reply.classList.add('ai-error');
                    reply.textContent = ev.message;
                }
            }).catch(() => { reply.classList.remove('is-typing'); reply.textContent = 'Koneksi terputus. Coba lagi.'; });
            reply.setAttribute('data-testid', 'ai-reply');
            if (remaining) remaining.textContent = Math.max(0, parseInt(remaining.textContent, 10) - 1);
            send.disabled = false;
            input.focus();
        });
    }

    document.querySelectorAll('.ai-explain-button').forEach((btn) => btn.addEventListener('click', async () => {
        const target = document.getElementById(btn.dataset.target);
        btn.disabled = true;
        target.hidden = false;
        target.classList.add('is-typing');
        let text = '';
        let failed = false;
        const params = Object.fromEntries(new URLSearchParams(location.search).entries());
        params.facilities = new URLSearchParams(location.search).getAll('facilities[]');
        await streamSse(btn.dataset.url, params, (ev) => {
            if (ev.type === 'delta') {
                text += ev.text;
                target.classList.remove('is-typing');
                target.innerHTML = render(text, btn.dataset.detailBase);
            } else if (ev.type === 'error') {
                failed = true;
                target.classList.remove('is-typing');
                target.textContent = ev.message;
            }
        }).catch(() => { failed = true; target.classList.remove('is-typing'); target.textContent = 'Koneksi terputus. Coba lagi.'; });
        btn.disabled = false;
        btn.textContent = failed ? '↻ Coba lagi' : '↻ Jelaskan ulang';
    }));
})();
