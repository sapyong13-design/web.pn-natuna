-- Add per-page sizing classes after documentary-content migration; homepage gallery remains untouched.
UPDATE #__content
SET introtext = REPLACE(introtext, 'class="facility-documentary"', 'class="facility-documentary facility-documentary--disability"'), modified = NOW()
WHERE id = 15
  AND alias = 'layanan-disabilitas'
  AND state = 1
  AND SHA2(introtext, 256) = '1ccf9f902cfc1471c7e709d1068d9fc659ea0f092f92b85b83ec20cbe065bb8b';

UPDATE #__content
SET introtext = REPLACE(introtext, 'class="facility-documentary"', 'class="facility-documentary facility-documentary--posbakum"'), modified = NOW()
WHERE id = 16
  AND alias = 'pos-bantuan-hukum'
  AND state = 1
  AND SHA2(introtext, 256) = '00ece113d8a369b0961a4c9f9dfe6ff9332f5a2808740ec67bfaedd340b0ac76';
