<?php
/**
 * SPIKE BB-CODE & HTML HELPER - Aldhran Freeshard
 * Version: 2.0.0 - CKEditor Compatibility Mode
 */

function parseBBCode($text) {
    // INFO: Wir entfernen htmlspecialchars am Anfang, da der CKEditor 
    // bereits valides HTML liefert. Würden wir es behalten, würden Tags 
    // wie <strong> als Text (&lt;strong&gt;) angezeigt.

    $search = [
        '/\[b\](.*?)\[\/b\]/is',
        '/\[i\](.*?)\[\/i\]/is',
        '/\[u\](.*?)\[\/u\]/is',
        '/\[img\](.*?\.(?:jpg|jpeg|png|gif|webp))\[\/img\]/is',
        '/\[url\](?:https?:\/\/)?(.*?)\[\/url\]/is',
        '/\[url=(?:https?:\/\/)?(.*?)\](.*?)\[\/url\]/is',
        '/\[quote\](.*?)\[\/quote\]/is',
        '/\[quote=(.*?)\](.*?)\[\/quote\]/is'
    ];

    $replace = [
        '<strong>$1</strong>',
        '<em>$1</em>',
        '<u>$1</u>',
        '<img src="$1" style="max-width:100%; border:1px solid #333; margin:10px 0;" alt="User Image">',
        '<a href="http://$1" target="_blank" style="color:var(--glow-blue);">$1</a>',
        '<a href="http://$1" target="_blank" style="color:var(--glow-blue);">$2</a>',
        '<div style="background:rgba(255,255,255,0.03); border-left:3px solid #555; padding:15px; margin:10px 0; font-style:italic; color:#888;">$1</div>',
        '<div style="background:rgba(255,255,255,0.03); border-left:3px solid var(--glow-gold); padding:15px; margin:10px 0;">
            <strong style="color:var(--glow-gold); display:block; margin-bottom:5px; font-size:0.8em; text-transform:uppercase;">$1 wrote:</strong>
            <span style="font-style:italic; color:#888;">$2</span>
         </div>'
    ];

    // BBCode-Tags in HTML umwandeln (für Abwärtskompatibilität alter Posts)
    $text = preg_replace($search, $replace, $text);

    // INTELLIGENTES LINE-BREAKING:
    // Der CKEditor nutzt <p> und <br> Tags. Wenn wir zusätzlich nl2br() 
    // nutzen, entstehen riesige Lücken. Wir wenden nl2br nur an, wenn 
    // KEIN HTML-Umbruch gefunden wurde.
    if (strpos($text, '<p>') === false && strpos($text, '<br') === false) {
        return nl2br($text);
    }

    return $text;
}
?>