<?php
/** Focused source contract for Indonesian voice selection and startup welcome. */
$source = (string) file_get_contents(dirname(__DIR__) . '/templates/pn_natuna_2026/js/template.js');
$failures = [];
$expect = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) $failures[] = $message;
};

$expect(str_contains($source, "const welcomeText = 'Selamat datang di Pengadilan Negeri Natuna Kelas II.'"), 'Startup welcome text is missing.');
$expect(str_contains($source, 'welcomeInFlight'), 'Welcome must guard against duplicate utterances while start is pending.');
$expect(str_contains($source, "synth.addEventListener('voiceschanged', loadVoices)"), 'Delayed Edge voices must trigger voice selection.');
$expect(str_contains($source, "storageGet('pnNatunaVoiceName')"), 'Saved Indonesian voice selection must be restored.');
$expect(str_contains($source, "storageSet('pnNatunaVoiceName', voiceKey(selectedVoice))"), 'Selected Indonesian voice must persist.');
$expect(str_contains($source, '(?:gadis|andika)') && str_contains($source, 'voiceIdentity(voice)'), 'Edge voice identity must accept Gadis or Andika from name or voiceURI.');
$expect(str_contains($source, "utterance.lang = 'id-ID'"), 'Every utterance must use id-ID locale.');
$expect(str_contains($source, 'bindInteractionFallback'), 'Autoplay-blocked speech needs a first-interaction fallback.');
$expect(str_contains($source, 'if (!welcomeSpoken) announceWelcome();'), 'Enabled startup must announce the welcome.');

if ($failures) {
    fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
    exit(1);
}
echo "voice reader contract: ok\n";
