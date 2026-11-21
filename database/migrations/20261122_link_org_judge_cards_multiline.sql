-- Add public profile anchors to judge cards with whitespace between HTML tags.
-- Match by portrait path; leave already linked cards untouched.

SET @org_judge_card = '<article class="roster-card">[[:space:]]*<div class="roster-photo">[[:space:]]*<img src="images/profil/pegawai/hakim/salihin[.]jpg"';
UPDATE #__content
SET introtext = REGEXP_REPLACE(introtext, @org_judge_card, '<article id="org-salihin-ardiansyah" class="roster-card"><div class="roster-photo"><img src="images/profil/pegawai/hakim/salihin.jpg"')
WHERE id = 58 AND LOCATE('id="org-salihin-ardiansyah"', introtext) = 0
  AND introtext REGEXP @org_judge_card;

SET @org_judge_card = '<article class="roster-card">[[:space:]]*<div class="roster-photo">[[:space:]]*<img src="images/profil/pegawai/hakim/alfariz[.]jpg"';
UPDATE #__content
SET introtext = REGEXP_REPLACE(introtext, @org_judge_card, '<article id="org-alfariz-maulana-reza" class="roster-card"><div class="roster-photo"><img src="images/profil/pegawai/hakim/alfariz.jpg"')
WHERE id = 58 AND LOCATE('id="org-alfariz-maulana-reza"', introtext) = 0
  AND introtext REGEXP @org_judge_card;

SET @org_judge_card = '<article class="roster-card">[[:space:]]*<div class="roster-photo">[[:space:]]*<img src="images/profil/pegawai/hakim/geraldo[.]jpeg"';
UPDATE #__content
SET introtext = REGEXP_REPLACE(introtext, @org_judge_card, '<article id="org-geraldo-gracelo-mario-situmeang" class="roster-card"><div class="roster-photo"><img src="images/profil/pegawai/hakim/geraldo.jpeg"')
WHERE id = 58 AND LOCATE('id="org-geraldo-gracelo-mario-situmeang"', introtext) = 0
  AND introtext REGEXP @org_judge_card;

SET @org_judge_card = '<article class="roster-card">[[:space:]]*<div class="roster-photo">[[:space:]]*<img src="images/profil/pegawai/hakim/haditio[.]png"';
UPDATE #__content
SET introtext = REGEXP_REPLACE(introtext, @org_judge_card, '<article id="org-haditio" class="roster-card"><div class="roster-photo"><img src="images/profil/pegawai/hakim/haditio.png"')
WHERE id = 58 AND LOCATE('id="org-haditio"', introtext) = 0
  AND introtext REGEXP @org_judge_card;

SET @org_judge_card = '<article class="roster-card">[[:space:]]*<div class="roster-photo">[[:space:]]*<img src="images/profil/pegawai/hakim/swandi[.]jpeg"';
UPDATE #__content
SET introtext = REGEXP_REPLACE(introtext, @org_judge_card, '<article id="org-swandi-hutabarat" class="roster-card"><div class="roster-photo"><img src="images/profil/pegawai/hakim/swandi.jpeg"')
WHERE id = 58 AND LOCATE('id="org-swandi-hutabarat"', introtext) = 0
  AND introtext REGEXP @org_judge_card;

SET @org_judge_card = '<article class="roster-card">[[:space:]]*<div class="roster-photo">[[:space:]]*<img src="images/profil/pegawai/hakim/sutriyadi[.]png"';
UPDATE #__content
SET introtext = REGEXP_REPLACE(introtext, @org_judge_card, '<article id="org-sutriyadi" class="roster-card"><div class="roster-photo"><img src="images/profil/pegawai/hakim/sutriyadi.png"')
WHERE id = 58 AND LOCATE('id="org-sutriyadi"', introtext) = 0
  AND introtext REGEXP @org_judge_card;

SET @org_judge_card = '<article class="roster-card">[[:space:]]*<div class="roster-photo">[[:space:]]*<img src="images/profil/pegawai/hakim/endro[.]png"';
UPDATE #__content
SET introtext = REGEXP_REPLACE(introtext, @org_judge_card, '<article id="org-endro-basuki-prabowo" class="roster-card"><div class="roster-photo"><img src="images/profil/pegawai/hakim/endro.png"')
WHERE id = 58 AND LOCATE('id="org-endro-basuki-prabowo"', introtext) = 0
  AND introtext REGEXP @org_judge_card;

SET @org_judge_card = '<article class="roster-card">[[:space:]]*<div class="roster-photo">[[:space:]]*<img src="images/profil/pegawai/hakim/suriadi[.]png"';
UPDATE #__content
SET introtext = REGEXP_REPLACE(introtext, @org_judge_card, '<article id="org-suriadi" class="roster-card"><div class="roster-photo"><img src="images/profil/pegawai/hakim/suriadi.png"')
WHERE id = 58 AND LOCATE('id="org-suriadi"', introtext) = 0
  AND introtext REGEXP @org_judge_card;

SET @org_judge_card = '<article class="roster-card">[[:space:]]*<div class="roster-photo">[[:space:]]*<img src="images/profil/pegawai/hakim/sirodjuddin[.]png"';
UPDATE #__content
SET introtext = REGEXP_REPLACE(introtext, @org_judge_card, '<article id="org-sirodjuddin" class="roster-card"><div class="roster-photo"><img src="images/profil/pegawai/hakim/sirodjuddin.png"')
WHERE id = 58 AND LOCATE('id="org-sirodjuddin"', introtext) = 0
  AND introtext REGEXP @org_judge_card;

SET @org_judge_card = '<article class="roster-card">[[:space:]]*<div class="roster-photo">[[:space:]]*<img src="images/profil/pegawai/hakim/halomoan-freddy-sitinjak[.]png"';
UPDATE #__content
SET introtext = REGEXP_REPLACE(introtext, @org_judge_card, '<article id="org-dr-halomoan-freddy-sitinjak-alexandra" class="roster-card"><div class="roster-photo"><img src="images/profil/pegawai/hakim/halomoan-freddy-sitinjak.png"')
WHERE id = 58 AND LOCATE('id="org-dr-halomoan-freddy-sitinjak-alexandra"', introtext) = 0
  AND introtext REGEXP @org_judge_card;
