<?php

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

/** @var Joomla\CMS\Document\HtmlDocument $this */
$app = Factory::getApplication();
require_once JPATH_ROOT . '/includes/pn-csp.php';
pnNatunaRegisterCsp($app);
$siteName = htmlspecialchars((string) $app->get('sitename', 'Pengadilan Negeri Natuna'), ENT_QUOTES, 'UTF-8');
$displayMode = (int) $app->get('display_offline_message', 1);
$message = '';
if ($displayMode === 1) {
    $message = trim((string) $app->get('offline_message'));
} elseif ($displayMode === 2) {
    $message = Text::_('JOFFLINE_MESSAGE');
}
if ($message === '') {
    $message = 'Layanan sedang dalam pemeliharaan. Silakan kembali beberapa saat lagi.';
}
$offlineImage = trim((string) $app->get('offline_image'));
if ($offlineImage !== '') {
    $offlineImage = strtok($offlineImage, '#');
    if (!preg_match('#^(?:https?:)?//#i', $offlineImage)) {
        $offlineImage = Uri::root() . ltrim($offlineImage, '/');
    }
}
$logo = Uri::root() . 'images/brand/logo-pn-natuna.webp';
?>
<!doctype html>
<html lang="id-ID" dir="<?php echo $this->direction; ?>">
<head>
  <jdoc:include type="metas" />
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <meta name="theme-color" content="#781b18">
  <link rel="icon" type="image/png" href="<?php echo Uri::root(); ?>images/brand/favicon-32.png">
  <link rel="preload" href="<?php echo Uri::root(); ?>templates/pn_natuna_2026/fonts/plus-jakarta-sans-var.woff2" as="font" type="font/woff2" crossorigin>
  <style>
    @font-face{font-family:"Plus Jakarta Sans";src:url("<?php echo Uri::root(); ?>templates/pn_natuna_2026/fonts/plus-jakarta-sans-var.woff2") format("woff2");font-weight:400 800;font-display:swap}
    :root{color-scheme:light;--maroon:#781b18;--ink:#18232b;--muted:#59656d;--paper:#fffdf9;--line:#e3d6c8;--gold:#c9a44d}
    *{box-sizing:border-box}
    html,body{min-height:100%;margin:0}
    body{display:grid;place-items:center;padding:24px;background:#f5efe6;color:var(--ink);font-family:"Plus Jakarta Sans",system-ui,sans-serif}
    body::before{position:fixed;inset:0;z-index:-1;background:linear-gradient(135deg,rgba(120,27,24,.08),transparent 45%),radial-gradient(circle at 85% 15%,rgba(201,164,77,.14),transparent 32%);content:""}
    .maintenance{width:min(1060px,100%);overflow:hidden;border:1px solid var(--line);border-radius:22px;background:var(--paper);box-shadow:0 22px 65px rgba(58,35,25,.14)}
    .maintenance__brand{display:flex;align-items:center;gap:14px;padding:20px 28px;border-bottom:1px solid var(--line)}
    .maintenance__brand img{width:52px;height:52px;object-fit:contain}
    .maintenance__brand span{display:grid;gap:2px}
    .maintenance__brand strong{font-size:clamp(1rem,2vw,1.2rem)}
    .maintenance__brand small{color:var(--muted);font-size:.8rem}
    .maintenance__content{display:grid;grid-template-columns:minmax(0,1fr) minmax(320px,.82fr);min-height:480px}
    .maintenance__copy{display:grid;align-content:center;gap:20px;padding:clamp(32px,6vw,72px)}
    .maintenance__status{display:inline-flex;width:max-content;align-items:center;gap:9px;color:var(--maroon);font-size:.8rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase}
    .maintenance__status::before{width:10px;height:10px;border-radius:50%;background:var(--gold);box-shadow:0 0 0 6px rgba(201,164,77,.18);content:""}
    h1{max-width:14ch;margin:0;font-size:clamp(2.1rem,5vw,4.4rem);line-height:1.02;letter-spacing:-.04em}
    .maintenance__message{max-width:60ch;margin:0;color:var(--muted);font-size:clamp(1rem,1.6vw,1.12rem);line-height:1.75}
    .maintenance__message br{display:block}
    .maintenance__contact{display:flex;flex-wrap:wrap;gap:10px 18px;padding-top:8px;border-top:1px solid var(--line);color:var(--muted);font-size:.88rem}
    .maintenance__contact a{color:var(--maroon);font-weight:700;text-decoration:none}
    .maintenance__visual{min-height:360px;background:var(--maroon)}
    .maintenance__visual img{display:block;width:100%;height:100%;min-height:480px;object-fit:cover}
    .maintenance__visual--default{display:grid;place-items:center;padding:42px;background:linear-gradient(145deg,#64161a,#8b2721);color:#f4dda0;text-align:center}
    .maintenance__visual--default img{width:min(210px,65%);height:auto;min-height:0;filter:drop-shadow(0 12px 18px rgba(0,0,0,.22))}
    .maintenance__visual--default strong{display:block;margin-top:24px;font-size:1.1rem;line-height:1.5}
    @media(max-width:760px){body{padding:14px}.maintenance{border-radius:16px}.maintenance__brand{padding:16px 20px}.maintenance__content{grid-template-columns:1fr}.maintenance__copy{padding:36px 24px}.maintenance__visual{order:-1;min-height:220px}.maintenance__visual img{min-height:220px;max-height:300px}.maintenance__visual--default{display:none}h1{max-width:16ch}}
  </style>
</head>
<body>
  <main class="maintenance" aria-labelledby="maintenance-title">
    <header class="maintenance__brand">
      <img src="<?php echo htmlspecialchars($logo, ENT_QUOTES, 'UTF-8'); ?>" alt="" width="52" height="52">
      <span><strong><?php echo $siteName; ?></strong><small>Mahkamah Agung Republik Indonesia</small></span>
    </header>
    <div class="maintenance__content">
      <section class="maintenance__copy">
        <span class="maintenance__status">Pemeliharaan sistem</span>
        <h1 id="maintenance-title">Kami segera kembali.</h1>
        <div class="maintenance__message"><?php echo $message; ?></div>
        <div class="maintenance__contact">
          <span>Informasi layanan: <a href="tel:+627733211203">0773-3211203</a></span>
          <span><a href="https://wa.me/6281261256661">WhatsApp PN Natuna</a></span>
        </div>
      </section>
      <?php if ($offlineImage !== '') : ?>
        <figure class="maintenance__visual"><img src="<?php echo htmlspecialchars($offlineImage, ENT_QUOTES, 'UTF-8'); ?>" alt="Ilustrasi pemeliharaan layanan <?php echo $siteName; ?>"></figure>
      <?php else : ?>
        <div class="maintenance__visual maintenance__visual--default" aria-hidden="true"><div><img src="<?php echo htmlspecialchars($logo, ENT_QUOTES, 'UTF-8'); ?>" alt=""><strong>Pengadilan Negeri Natuna<br>Kelas II</strong></div></div>
      <?php endif; ?>
    </div>
  </main>
</body>
</html>
