<p>Welcome to SocialRSS!</p>
<p>Your account details are below. Please keep them safe:</p>
<table style="border-collapse:collapse;">
    <tr>
        <th align="left">Username:</th>
        <td><?= htmlspecialchars($username) ?></td>
    </tr>
    <tr>
        <th align="left">Password:</th>
        <td><?= htmlspecialchars($password) ?></td>
    </tr>
</table>
<p style="margin-top:20px;">
    <a href="<?= DOMAIN ?>/login" style="display:inline-block;background:#0366d6;color:#ffffff;padding:10px 20px;text-decoration:none;border-radius:4px;">Log in to your account</a>
</p>
<p>After logging in, please change your password from your profile settings.</p>
