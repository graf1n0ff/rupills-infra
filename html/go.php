<?php
/**
 * Referrer-hiding redirect script.
 * Usage: /go.php?s=1        (numeric shortcode)
 *    or: /go.php?url=https://example.com  (legacy, direct URL)
 * Strips the Referer header so the destination site cannot see the source.
 */

$_shortcodes = [
    1 => 'https://exchange.mercuryo.io/?currency=BTC&type=buy',
    3 => 'https://buy.moonpay.com/',
    4 => 'https://paybis.com/buy-bitcoin/',
];

$url = '';
if (isset($_GET['s']) && isset($_shortcodes[(int)$_GET['s']])) {
    $url = $_shortcodes[(int)$_GET['s']];
} elseif (isset($_GET['url'])) {
    $url = trim($_GET['url']);
}

if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
    http_response_code(400);
    exit('Invalid URL');
}

$scheme = parse_url($url, PHP_URL_SCHEME);
if (!in_array($scheme, ['http', 'https'], true)) {
    http_response_code(400);
    exit('Invalid URL scheme');
}

header('Referrer-Policy: no-referrer');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
?>
<!DOCTYPE html>
<html>
<head>
<meta name="referrer" content="no-referrer">
<meta http-equiv="refresh" content="0;url=<?php echo htmlspecialchars($url, ENT_QUOTES, 'UTF-8'); ?>">
<title>Redirecting...</title>
</head>
<body>
<script>window.location.replace(<?php echo json_encode($url); ?>);</script>
<noscript><a href="<?php echo htmlspecialchars($url, ENT_QUOTES, 'UTF-8'); ?>">Click here to continue</a></noscript>
</body>
</html>
