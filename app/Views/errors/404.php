<section class="section">
  <div class="container" style="max-width:640px;text-align:center">
    <span class="eyebrow" style="justify-content:center">404</span>
    <h1 style="font-size:2.4rem;margin-bottom:8px">That page isn't here</h1>
    <p class="muted">The address may have changed, or the link may be out of date. Try the shop, or tell us what you were looking for.</p>
    <div class="flex" style="justify-content:center;flex-wrap:wrap">
      <a class="btn btn-primary" href="<?= url('/shop') ?>">Shop products</a>
      <a class="btn btn-outline" href="<?= url('/') ?>">Go home</a>
      <a class="btn btn-ghost" href="<?= url('/contact') ?>">Contact us</a>
    </div>
    <form action="<?= url('/search') ?>" method="get" class="flex mt-3" style="justify-content:center;max-width:420px;margin-left:auto;margin-right:auto">
      <label class="sr-only" for="err-search">Search products</label>
      <input class="input" id="err-search" name="q" placeholder="Search products" style="flex:1">
      <button class="btn btn-dark" type="submit">Search</button>
    </form>
  </div>
</section>
