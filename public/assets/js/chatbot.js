(function () {
  'use strict';
  var widget = document.querySelector('[data-chat-widget]');
  if (!widget) return;
  var toggle = widget.querySelector('[data-chat-toggle]');
  var panel = widget.querySelector('.chat-panel');
  var input = widget.querySelector('input');
  var form = widget.querySelector('form');
  var log = widget.querySelector('[data-chat-messages]');
  var status = widget.querySelector('[data-chat-status]');
  var busy = false;
  var controller;
  var initial = log.innerHTML;
  function open(value) {
    panel.hidden = !value;
    toggle.setAttribute('aria-expanded', String(value));
    if (value) input.focus(); else toggle.focus();
  }
  toggle.addEventListener('click', function () { open(panel.hidden); });
  widget.querySelector('[data-chat-close]').addEventListener('click', function () { open(false); });
  panel.addEventListener('keydown', function (event) { if (event.key === 'Escape') { event.preventDefault(); open(false); } });
  function message(text, who) {
    var node = document.createElement('div');
    node.className = 'chat-message chat-' + who;
    node.textContent = text;
    log.appendChild(node);
    // Keep the DOM bounded. Conversation is never saved to localStorage or sent as history.
    while (log.children.length > 24) log.firstElementChild.remove();
    log.scrollTop = log.scrollHeight;
    return node;
  }
  async function send(question) {
    question = question.trim();
    if (busy || question.length < 2 || question.length > 600) return;
    busy = true;
    form.querySelector('button').disabled = true;
    widget.querySelectorAll('[data-chat-prompt]').forEach(function (button) { button.disabled = true; });
    message(question, 'user');
    input.value = '';
    status.textContent = 'Looking through our website…';
    controller = new AbortController();
    var timeout = window.setTimeout(function () { controller.abort(); }, 25000);
    try {
      var body = new URLSearchParams({message: question});
      var response = await fetch((window.TM.base || '/').replace(/\/$/, '') + '/chat/message', {
        method: 'POST', headers: {'Accept': 'application/json', 'X-CSRF-Token': window.TM.csrf},
        body: body, signal: controller.signal, credentials: 'same-origin'
      });
      var data = await response.json();
      if (!response.ok) throw new Error(data.error || 'Unable to answer right now. Please try again.');
      var node = message(data.answer, 'assistant');
      if (data.notice) { var note = document.createElement('small'); note.className = 'chat-note'; note.textContent = data.notice; node.prepend(note); }
      if (Array.isArray(data.sources)) {
        var sources = document.createElement('div'); sources.className = 'chat-sources';
        data.sources.forEach(function (source) {
          // Only allow same-origin website citations, never arbitrary provider-generated links.
          var target = new URL(source.url, window.location.origin);
          if (target.origin !== window.location.origin) return;
          var link = document.createElement('a'); link.href = target.href; link.textContent = source.title + ' ↗'; sources.appendChild(link);
        });
        node.appendChild(sources);
      }
      status.textContent = data.mode === 'ai' ? 'AI-assisted answer · check the linked pages' : 'Website content · check the linked pages';
    } catch (error) {
      message(error.name === 'AbortError' ? 'This is taking longer than expected. Please try again or contact our team.' : error.message, 'assistant');
      status.textContent = 'Could not complete your request.';
    } finally {
      window.clearTimeout(timeout); busy = false;
      form.querySelector('button').disabled = false;
      widget.querySelectorAll('[data-chat-prompt]').forEach(function (button) { button.disabled = false; });
      log.scrollTop = log.scrollHeight;
    }
  }
  form.addEventListener('submit', function (event) { event.preventDefault(); if (form.reportValidity()) send(input.value); });
  widget.querySelectorAll('[data-chat-prompt]').forEach(function (button) { button.addEventListener('click', function () { send(button.dataset.chatPrompt); }); });
  widget.querySelector('[data-chat-clear]').addEventListener('click', function () {
    if (busy) { status.textContent = 'Please wait for the current answer before clearing.'; return; }
    log.innerHTML = initial; input.value = ''; status.textContent = ''; input.focus();
  });
})();
