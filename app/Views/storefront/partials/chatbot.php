<?php if (\App\Core\Settings::bool('chat.enabled', true)): ?>
<div class="tm-chat" data-chat-widget>
  <button class="chat-launch" type="button" aria-controls="tm-chat-panel" aria-expanded="false" data-chat-toggle><?= icon('message') ?> <span>Ask Thunder Mounts</span></button>
  <section class="chat-panel" id="tm-chat-panel" role="dialog" aria-labelledby="chat-title" hidden>
    <div class="chat-heading">
      <div><span class="chat-kicker">A better garage starts here</span><h2 id="chat-title">Thunder Mounts Assistant</h2></div>
      <button type="button" class="chat-close" aria-label="Close assistant" data-chat-close>&times;</button>
    </div>
    <p class="chat-disclosure">Answers from our website<?= setting('chat.provider', 'website') !== 'website' ? ', with optional AI assistance' : '' ?>. Confirm fitment with our team. Do not share payment or personal details.<?= setting('chat.provider', 'website') !== 'website' ? ' Questions and matching public content may be sent to our configured AI provider.' : '' ?></p>
    <div class="chat-messages" role="log" aria-live="polite" aria-relevant="additions" data-chat-messages>
      <div class="chat-message chat-assistant">Hi! I can help you find mount sizes, installation guidance, powder coating services, and company information.</div>
    </div>
    <div class="chat-prompts">
      <button type="button" data-chat-prompt="What powder coating services do you offer?">Powder coating</button>
      <button type="button" data-chat-prompt="How do I choose the right mount size?">Find my size</button>
      <button type="button" data-chat-prompt="Where can I find mounting instructions?">Instructions</button>
    </div>
    <form class="chat-form" data-chat-form>
      <label class="sr-only" for="chat-question">Ask about Thunder Mounts</label>
      <input id="chat-question" class="input" name="message" maxlength="600" minlength="2" required placeholder="How can we help?" autocomplete="off">
      <button class="btn btn-primary" type="submit">Send</button>
    </form>
    <div class="chat-status" role="status" data-chat-status></div>
    <div class="chat-bottom"><button type="button" data-chat-clear>Clear chat</button><a href="<?= url('/contact') ?>">Talk to our team ↗</a></div>
  </section>
</div>
<?php endif; ?>
