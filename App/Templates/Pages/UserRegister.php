<section>
    <h1><?= htmlspecialchars((string) ($headline ?? 'Create account'), ENT_QUOTES, 'UTF-8') ?></h1>
    <p><?= htmlspecialchars((string) ($summary ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
    <form method="post" action="/users/register">
        <p><label>Name<br><input type="text" name="name" required></label></p>
        <p><label>Email<br><input type="email" name="email" required></label></p>
        <p><label>Password<br><input type="password" name="password" required></label></p>
        <p><label>Confirm Password<br><input type="password" name="password_confirmation" required></label></p>
        <p><label><input type="checkbox" name="remember" value="1"> Remember me after registration</label></p>
        <p><button type="submit">Create account</button></p>
    </form>
    <p><a href="/users/login">Already have an account? Sign in.</a></p>
</section>
