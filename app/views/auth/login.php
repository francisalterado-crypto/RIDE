<?php
$pageTitle = 'Sign In | RIDE Hub';
// Read the flash here: the layout runs after this view, so it would be too late to show it.
$loginError = flash('error');
$loginSuccess = flash('success');
?>
<style>
  /* Same look as the portal (app.css tokens): navy sidebar colours, white rounded cards, teal buttons. */
  body.auth-standalone {
    --primary: #0f2b3d; --primary-dark: #0a1e2c; --accent: #2dd4bf; --accent-hover: #14b8a6;
    --bg: #f4f7fc; --text: #1a2c3e; --muted: #5b6e8c; --border: #e2edf2; --input-border: #cfdbe4; --danger: #b91c1c;
    margin: 0;
    min-height: 100vh;
    display: grid;
    place-items: center;
    padding: 1.5rem 1rem;
    font-family: Inter, "Segoe UI", system-ui, sans-serif;
    font-size: 15px;
    line-height: 1.5;
    color: var(--text);
    background: var(--bg);
  }
  body.auth-standalone * { box-sizing: border-box; }
  .login-box { width: 100%; max-width: 400px; background: #fff; border: 1px solid var(--border); border-radius: 16px; overflow: hidden; box-shadow: 0 10px 24px rgba(15, 43, 61, 0.1); }
  .login-brand { display: flex; align-items: center; gap: 12px; padding: 1.4rem 1.75rem; background: linear-gradient(180deg, var(--primary) 0%, var(--primary-dark) 100%); color: #e0f2fe; }
  .login-brand-icon { width: 40px; height: 40px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; background: #fff; border-radius: 12px; overflow: hidden; }
  .login-brand-icon img { width: 100%; height: 100%; object-fit: contain; display: block; }
  .login-brand strong { display: block; font-size: 1.4rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.1; color: #fff; }
  .login-brand small { display: block; font-size: 0.7rem; opacity: 0.7; }
  .login-body { padding: 1.75rem; }
  .login-box h1 { margin: 0 0 0.25rem; font-size: 1.3rem; font-weight: 700; color: var(--primary); }
  .login-box .login-sub { margin: 0 0 1.4rem; font-size: 0.85rem; color: var(--muted); }
  .login-field { margin-bottom: 1rem; }
  .login-field label { display: block; margin-bottom: 0.35rem; font-size: 0.82rem; font-weight: 600; color: var(--text); }
  .login-field input { width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--input-border); border-radius: 8px; font: inherit; color: inherit; background: #fff; transition: border-color 0.15s, box-shadow 0.15s; }
  .login-field input:focus { outline: none; border-color: var(--accent-hover); box-shadow: 0 0 0 3px rgba(45, 212, 191, 0.22); }
  .login-field input[aria-invalid="true"] { border-color: var(--danger); }
  .login-field-error { margin-top: 0.3rem; font-size: 0.75rem; color: var(--danger); }
  .login-alert { margin-bottom: 1.2rem; padding: 0.7rem 0.9rem; border-radius: 8px; font-size: 0.85rem; background: #fee2e2; color: #991b1b; }
  .login-alert--ok { background: #f0fdfa; color: #0f766e; }
  .login-submit { width: 100%; margin-top: 0.4rem; padding: 0.7rem 1rem; border: 0; border-radius: 999px; background: var(--accent); color: #0c2e3b; font: inherit; font-weight: 600; cursor: pointer; transition: background 0.2s, color 0.2s; }
  .login-submit:hover { background: var(--accent-hover); color: #fff; }
  .login-submit:disabled { opacity: 0.7; cursor: wait; }
  .login-links { display: flex; justify-content: space-between; gap: 1rem; margin-top: 1.4rem; padding-top: 1.1rem; border-top: 1px solid var(--border); font-size: 0.82rem; }
  .login-links a { color: var(--primary); font-weight: 600; text-decoration: none; }
  .login-links a:hover { color: var(--accent-hover); }
  .login-box :focus-visible { outline: 2px solid var(--accent-hover); outline-offset: 2px; }
</style>

<main class="login-box">
  <div class="login-brand">
    <span class="login-brand-icon"><img src="<?= base_url('assets/images/ride-logo.png') ?>" alt="" width="40" height="40"></span>
    <span>
      <strong>RIDE Hub</strong>
      <small>Information Management System for RIDE</small>
    </span>
  </div>

  <div class="login-body">

  <h1>Sign in</h1>
  <p class="login-sub">Use your RIDE Hub account.</p>

  <?php if ($loginError): ?>
    <div class="login-alert" role="alert"><?= htmlspecialchars($loginError) ?></div>
  <?php elseif ($loginSuccess): ?>
    <div class="login-alert login-alert--ok" role="status"><?= htmlspecialchars($loginSuccess) ?></div>
  <?php endif; ?>

  <form id="signinForm" method="post" action="<?= base_url('login') ?>" novalidate>
    <?= csrf_field() ?>
    <div class="login-field">
      <label for="email">Email address</label>
      <input type="email" id="email" name="email" required autocomplete="email" value="<?= old('email') ?>" autofocus>
      <div class="login-field-error" id="emailError" hidden></div>
    </div>
    <div class="login-field">
      <label for="password">Password</label>
      <input type="password" id="password" name="password" required autocomplete="current-password">
      <div class="login-field-error" id="passwordError" hidden></div>
    </div>
    <button type="submit" class="login-submit" id="signInButton">Sign in</button>
  </form>

  <div class="login-links">
    <a href="<?= base_url('register') ?>">Register as Faculty</a>
    <a href="<?= base_url('') ?>">RIDE website</a>
  </div>
  </div>
</main>

<script>
  (function () {
    var form = document.getElementById('signinForm');
    var button = document.getElementById('signInButton');
    var fields = {
      email: [document.getElementById('email'), document.getElementById('emailError')],
      password: [document.getElementById('password'), document.getElementById('passwordError')]
    };
    function setError(name, message) {
      var input = fields[name][0], box = fields[name][1];
      box.textContent = message;
      box.hidden = !message;
      input.setAttribute('aria-invalid', message ? 'true' : 'false');
    }
    form.addEventListener('submit', function (event) {
      var email = fields.email[0].value.trim();
      var emailError = !email ? 'Email address is required.' : (/^[^\s@]+@([^\s@]+\.)+[^\s@]+$/.test(email) ? '' : 'Enter a valid email address.');
      var passwordError = fields.password[0].value ? '' : 'Password is required.';
      setError('email', emailError);
      setError('password', passwordError);
      if (emailError || passwordError) {
        event.preventDefault();
        (emailError ? fields.email[0] : fields.password[0]).focus();
        return;
      }
      button.disabled = true;
      button.textContent = 'Signing in...';
    });
  })();
</script>
