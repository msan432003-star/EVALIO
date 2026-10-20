<?php
/* engine.php - free keyword-matching chatbot engine (no AI service, no API key, no database). */

function cb_normalize($s) {
    $s = mb_strtolower((string)$s, 'UTF-8');
    $s = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $s);
    $s = preg_replace('/\s+/u', ' ', $s);
    return trim($s);
}

/* very light stemming: exams -> exam, results -> result (words ending in "ss" are kept) */
function cb_stem($w) {
    if (mb_strlen($w, 'UTF-8') > 3 && substr($w, -1) === 's' && substr($w, -2) !== 'ss') { return substr($w, 0, -1); }
    return $w;
}

function cb_tokens($normalized) {
    $out = array();
    if ($normalized === '') { return $out; }
    foreach (explode(' ', $normalized) as $w) { $out[] = cb_stem($w); }
    return $out;
}

/* does the keyword (one or more words) appear as consecutive tokens? */
function cb_has_phrase($tokens, $kwTokens) {
    $n = count($kwTokens); $m = count($tokens);
    if ($n === 0 || $n > $m) { return false; }
    for ($i = 0; $i <= $m - $n; $i++) {
        $ok = true;
        for ($j = 0; $j < $n; $j++) { if ($tokens[$i + $j] !== $kwTokens[$j]) { $ok = false; break; } }
        if ($ok) { return true; }
    }
    return false;
}

function cb_links($links, $base) {
    $out = array();
    foreach ((array)$links as $l) {
        if (is_array($l) && count($l) === 2) { $out[] = array('label' => $l[0], 'url' => $base . ltrim($l[1], '/')); }
    }
    return $out;
}

/* $kb = array('welcome'=>..., 'fallback'=>..., 'suggest'=>array(...), 'entries'=>array(array('keywords'=>..., 'answer'=>..., 'links'=>...)))
   $base = BASE_URL (links in the KB are relative to it) */
function cb_answer($kb, $message, $base) {
    $tokens = cb_tokens(cb_normalize($message));
    $best = null; $bestScore = 0;
    foreach ($kb['entries'] as $entry) {
        $score = 0;
        foreach ($entry['keywords'] as $kw) {
            $kwTokens = cb_tokens(cb_normalize($kw));
            if (cb_has_phrase($tokens, $kwTokens)) { $score += count($kwTokens) * count($kwTokens); } // longer phrases win
        }
        if (isset($entry['weight'])) { $score *= $entry['weight']; }
        if ($score > $bestScore) { $bestScore = $score; $best = $entry; }
    }
    if ($best !== null) {
        return array('reply' => $best['answer'], 'links' => cb_links(isset($best['links']) ? $best['links'] : array(), $base),
                     'suggestions' => isset($best['suggest']) ? $best['suggest'] : array());
    }

    // small talk (only when nothing else matched)
    $joined = implode(' ', $tokens);
    if (preg_match('/^(hi|hii+|hello|hey|hola|namaste|good morning|good afternoon|good evening|help|start|menu)( .*)?$/', $joined)) {
        return array('reply' => $kb['welcome'], 'links' => array(), 'suggestions' => $kb['suggest']);
    }
    if (preg_match('/\b(thank|thanx|thankyou|thanks|ok thank|great|nice|cool)\b/', $joined)) {
        return array('reply' => 'You are welcome! Ask me anything else about Evalio.', 'links' => array(), 'suggestions' => array());
    }
    if (preg_match('/^(bye|goodbye|see you|exit|quit)( .*)?$/', $joined)) {
        return array('reply' => 'Goodbye! Good luck with your exams.', 'links' => array(), 'suggestions' => array());
    }
    return array('reply' => $kb['fallback'], 'links' => cb_links(isset($kb['fallback_links']) ? $kb['fallback_links'] : array(), $base),
                 'suggestions' => $kb['suggest']);
}