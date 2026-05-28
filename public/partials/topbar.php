<?php
$topbarEscape = static fn(string $value): string => function_exists('e')
    ? e($value)
    : htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

$topbarUserName = (string) ($user['name'] ?? 'Usuario');
$topbarUserInitial = mb_strtoupper(mb_substr($topbarUserName !== '' ? $topbarUserName : 'U', 0, 1));
$topbarDisplayName = function_exists('shortText')
    ? shortText($topbarUserName, 12)
    : mb_substr($topbarUserName, 0, 12);

$topbarSearchPlaceholder = isset($topbarSearchPlaceholder)
    ? (string) $topbarSearchPlaceholder
    : 'Buscar en LifeQuest...';

$topbarPoints = isset($points)
    ? (int) $points
    : (int) ($user['points'] ?? 0);

$topbarXpCurrent = isset($xpCurrent)
    ? (int) $xpCurrent
    : (int) ($user['xp'] ?? 0);

$topbarLevel = isset($level)
    ? max(1, (int) $level)
    : max(1, (int) ($user['level'] ?? 1));

$xpPerLevel = 1000;
$xpCurrentLevel = $topbarXpCurrent % $xpPerLevel;
$topbarXpPercent = isset($xpPercent)
    ? max(0, min(100, (int) $xpPercent))
    : max(0, min(100, (int) round(($xpCurrentLevel / max(1, $xpPerLevel)) * 100)));

$topbarGems = isset($gems)
    ? max(0, (int) $gems)
    : max(0, intdiv($topbarPoints, 20));

$topbarShowHp = isset($topbarShowHp)
    ? (bool) $topbarShowHp
    : (
        (defined('FEATURE_HP_SYSTEM') ? (bool) FEATURE_HP_SYSTEM : false)
        && (
            (isset($maxHp) && (int) $maxHp > 0)
            || (isset($user['max_hp']) && (int) ($user['max_hp'] ?? 0) > 0)
        )
    );

$topbarHp = isset($hp)
    ? max(0, (int) $hp)
    : max(0, (int) ($user['hp'] ?? 0));

$topbarMaxHp = isset($maxHp)
    ? max(0, (int) $maxHp)
    : max(0, (int) ($user['max_hp'] ?? 0));
?>
<header class="lq-topbar">
    <button class="icon-btn" type="button" aria-label="Abrir navegación">☰</button>
    <div class="search-box">
        <span>
            <svg id="Search" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="11.2481" cy="10.7887" r="8.03854" stroke="#7b86a3" stroke-width="1.5" stroke-linecap="square"></circle>
            <path d="M16.7369 16.7083L21.2904 21.2499" stroke="#7b86a3" stroke-width="1.5" stroke-linecap="square"></path>
            </svg>
        </span>
        <input type="search" placeholder="<?= $topbarEscape($topbarSearchPlaceholder) ?>" disabled>
        <kbd>⌘ K</kbd>
    </div>
    <div class="top-stats">
        <div class="xp-pill">
            <span>✦</span>
            <strong><?= number_format($topbarXpCurrent, 0, ',', '.') ?> XP</strong>
            <div class="mini-progress"><i style="width: <?= $topbarXpPercent ?>%"></i></div>
            <small>Nivel <?= $topbarLevel ?></small>
        </div>

        <div class="currency-pill coin"><span>🪙</span><strong><?= number_format($topbarPoints, 0, ',', '.') ?></strong></div>
        <div class="currency-pill gem"><span>💎</span><strong><?= $topbarGems ?></strong></div>

        <?php if ($topbarShowHp): ?>
            <div class="currency-pill hp"><span>❤️</span><strong><?= number_format($topbarHp, 0, ',', '.') ?>/<?= number_format($topbarMaxHp, 0, ',', '.') ?></strong></div>
        <?php endif; ?>

        <button class="notify-pill" type="button" aria-label="Notificaciones">🔔</button>

        <div class="profile-pill">
            <div class="mini-avatar image-like"><?= $topbarUserInitial ?></div>
            <strong>¡Hola, <?= $topbarEscape($topbarDisplayName) ?>! 👋</strong>
        </div>
    </div>
</header>
