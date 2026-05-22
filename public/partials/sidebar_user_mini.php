<?php
$sidebarUserName = (string) ($sidebarUserName ?? ($user['name'] ?? 'Usuario'));
$sidebarUserSubtitle = (string) ($sidebarUserSubtitle ?? 'Ver perfil');
$sidebarUserInitial = mb_strtoupper(mb_substr($sidebarUserName !== '' ? $sidebarUserName : 'U', 0, 1));
$sidebarUserDisplayName = function_exists('shortText') ? shortText($sidebarUserName, 18) : $sidebarUserName;
$sidebarUserEscape = static fn(string $value): string => function_exists('e')
    ? e($value)
    : htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
?>
<section class="lq-user-mini">
    <div class="mini-avatar"><?= $sidebarUserInitial ?></div>
    <div>
        <strong><?= $sidebarUserEscape($sidebarUserDisplayName) ?></strong>
        <small><?= $sidebarUserEscape($sidebarUserSubtitle) ?></small>
    </div>
    <span>⌄</span>
</section>
