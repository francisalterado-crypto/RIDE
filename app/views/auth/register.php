<?php $pageTitle = 'Register | RIDE'; ?>
<div class="card auth-box">
    <h2>Register</h2>
    <p class="muted" style="margin-bottom:1rem;">Submit your details for review. An administrator will approve your account and assign access (typically as faculty) before you can sign in.</p>
    <form method="post" action="<?= base_url('register') ?>">
        <?= csrf_field() ?>
        <label>First Name</label>
        <input name="first_name" value="<?= old('first_name') ?>" required>
        <label>Last Name</label>
        <input name="last_name" value="<?= old('last_name') ?>" required>
        <label>Email</label>
        <input type="email" name="email" value="<?= old('email') ?>" required>
        <label>Password (min 8 characters)</label>
        <input type="password" name="password" required>
        <label>College</label>
        <select name="college_id" required>
            <option value="">Select college</option>
            <?php foreach ($colleges as $c): ?>
                <option value="<?= (int) $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn">Create Account</button>
    </form>
    <p style="margin-top:1rem;"><a href="<?= base_url('login') ?>">Back to sign in</a></p>
</div>
