/* chatbot.js - floating chat widget. Talks to chatbot/api.php. No libraries. Text is always inserted with textContent (no HTML injection). */
(function () {
    'use strict';
    var cfg = window.EVALIO_CHATBOT;
    var root = document.getElementById('cb-root');
    if (!cfg || !root) { return; }

    var busy = false, started = false;

    function el(tag, cls, text) {
        var n = document.createElement(tag);
        if (cls) { n.className = cls; }
        if (text !== undefined) { n.textContent = text; }
        return n;
    }

    // ---- build UI ----
    var fab = el('button', 'cb-fab');
    fab.type = 'button';
    fab.setAttribute('aria-label', 'Open chat assistant');
    fab.setAttribute('aria-expanded', 'false');
    fab.setAttribute('aria-controls', 'cb-panel');
    fab.innerHTML = '<i class="fa fa-comments" aria-hidden="true"></i>';

    var panel = el('div', 'cb-panel');
    panel.id = 'cb-panel';
    panel.setAttribute('role', 'dialog');
    panel.setAttribute('aria-label', 'Evalio chat assistant');

    var head = el('div', 'cb-head');
    var ttl = el('div');
    ttl.appendChild(el('p', 'cb-title', 'Evalio Assistant'));
    ttl.appendChild(el('span', 'cb-sub', 'Ask me about exams, fees, results'));
    var closeBtn = el('button', 'cb-close', '\u00D7');
    closeBtn.type = 'button';
    closeBtn.setAttribute('aria-label', 'Close chat');
    head.appendChild(ttl); head.appendChild(closeBtn);

    var log = el('div', 'cb-log');
    log.setAttribute('role', 'log');
    log.setAttribute('aria-live', 'polite');

    var form = el('form', 'cb-form');
    form.setAttribute('autocomplete', 'off');
    var input = el('input', 'cb-input');
    input.type = 'text'; input.maxLength = 200; input.placeholder = 'Type your question...';
    input.setAttribute('aria-label', 'Your question');
    var send = el('button', 'cb-send');
    send.type = 'submit'; send.setAttribute('aria-label', 'Send');
    send.innerHTML = '<i class="fa fa-paper-plane" aria-hidden="true"></i>';
    form.appendChild(input); form.appendChild(send);

    panel.appendChild(head); panel.appendChild(log); panel.appendChild(form);
    root.appendChild(panel); root.appendChild(fab);

    // ---- helpers ----
    function scrollDown() { log.scrollTop = log.scrollHeight; }
    function addMsg(text, who) { var m = el('div', 'cb-msg ' + (who === 'user' ? 'cb-user' : 'cb-bot'), text); log.appendChild(m); scrollDown(); return m; }

    function clearExtras() {
        var old = log.querySelectorAll('.cb-chips, .cb-links');
        for (var i = 0; i < old.length; i++) { old[i].parentNode.removeChild(old[i]); }
    }

    function addLinks(links) {
        if (!links || !links.length) { return; }
        var box = el('div', 'cb-links');
        links.forEach(function (l) {
            var a = el('a', 'cb-link', l.label);
            a.href = l.url;                      // same-site URLs built on the server
            box.appendChild(a);
        });
        log.appendChild(box);
    }

    function addChips(list) {
        if (!list || !list.length) { return; }
        var box = el('div', 'cb-chips');
        list.forEach(function (q) {
            var b = el('button', 'cb-chip', q);
            b.type = 'button';
            b.addEventListener('click', function () { ask(q); });
            box.appendChild(b);
        });
        log.appendChild(box);
    }

    function typing() {
        var t = el('div', 'cb-typing');
        t.setAttribute('aria-label', 'Assistant is typing');
        t.innerHTML = '<i></i><i></i><i></i>';
        log.appendChild(t); scrollDown();
        return t;
    }

    function setBusy(v) { busy = v; send.disabled = v; }

    function ask(text) {
        text = (text || '').replace(/^\s+|\s+$/g, '');
        if (!text || busy) { return; }
        clearExtras();
        addMsg(text, 'user');
        input.value = '';
        setBusy(true);
        var t = typing();

        var fd = new FormData();
        fd.append('message', text);
        fd.append('profile', cfg.profile);
        fd.append('csrf_token', cfg.token);

        fetch(cfg.api, { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function (r) {
                return r.text().then(function (raw) {
                    try {
                        return JSON.parse(raw);
                    } catch (err) {
                        console.error('Chatbot API JSON parse failed. Status:', r.status, 'Raw response:', raw, err);
                        return { reply: 'Sorry, something went wrong. Please try again.', links: [], suggestions: [] };
                    }
                });
            })
            .then(function (d) {
                t.parentNode && t.parentNode.removeChild(t);
                addMsg(d.reply || 'Sorry, something went wrong.', 'bot');
                addLinks(d.links); addChips(d.suggestions);
                scrollDown();
            })
            .catch(function (err) {
                console.error('Chatbot network error:', err);
                t.parentNode && t.parentNode.removeChild(t);
                addMsg('Sorry, I could not reach the server. Please try again.', 'bot');
            })
            .then(function () { setBusy(false); input.focus(); });
    }

    function open() {
        panel.classList.add('cb-open');
        fab.setAttribute('aria-expanded', 'true');
        fab.setAttribute('aria-label', 'Close chat assistant');
        if (!started) { started = true; greet(); }       // welcome message + suggested questions
        setTimeout(function () { input.focus(); }, 50);
    }
    function close() {
        panel.classList.remove('cb-open');
        fab.setAttribute('aria-expanded', 'false');
        fab.setAttribute('aria-label', 'Open chat assistant');
        fab.focus();
    }

    function greet() {
        setBusy(true);
        var fd = new FormData();
        fd.append('message', 'hi'); fd.append('profile', cfg.profile); fd.append('csrf_token', cfg.token);
        fetch(cfg.api, { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function (r) {
                return r.text().then(function (raw) {
                    try {
                        return JSON.parse(raw);
                    } catch (err) {
                        console.error('Chatbot greet JSON parse failed. Status:', r.status, 'Raw response:', raw, err);
                        return null;
                    }
                });
            })
            .then(function (d) {
                if (d && d.reply) {
                    addMsg(d.reply, 'bot');
                    addChips(d.suggestions);
                } else {
                    addMsg('Hi! Ask me about exams, fees, results or certificates.', 'bot');
                }
            })
            .catch(function (err) {
                console.error('Chatbot greet network error:', err);
                addMsg('Hi! Ask me about exams, fees, results or certificates.', 'bot');
            })
            .then(function () { setBusy(false); });
    }

    fab.addEventListener('click', function () { panel.classList.contains('cb-open') ? close() : open(); });
    closeBtn.addEventListener('click', close);
    form.addEventListener('submit', function (e) { e.preventDefault(); ask(input.value); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && panel.classList.contains('cb-open')) { close(); } });
})();