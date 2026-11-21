-- Add stable fragment IDs to existing profile cards; leave names, roles and masked NIP untouched.

UPDATE #__content SET introtext = REPLACE(introtext, '<article class="roster-featured"><div class="roster-featured-photo"><img src="/images/profil/pegawai/hakim/joko-ciptanto.jpg"', '<article id="org-joko-ciptanto" class="roster-featured"><div class="roster-featured-photo"><img src="/images/profil/pegawai/hakim/joko-ciptanto.jpg"')
WHERE id = 58 AND LOCATE('<article class="roster-featured"><div class="roster-featured-photo"><img src="/images/profil/pegawai/hakim/joko-ciptanto.jpg"', introtext) > 0;

UPDATE #__content SET introtext = REPLACE(introtext, '<article class="roster-card"><div class="roster-photo"><img src="images/profil/pegawai/hakim/salihin.jpg"', '<article id="org-salihin-ardiansyah" class="roster-card"><div class="roster-photo"><img src="images/profil/pegawai/hakim/salihin.jpg"')
WHERE id = 58 AND LOCATE('<article class="roster-card"><div class="roster-photo"><img src="images/profil/pegawai/hakim/salihin.jpg"', introtext) > 0;

UPDATE #__content SET introtext = REPLACE(introtext, '<article class="roster-card"><div class="roster-photo"><img src="images/profil/pegawai/hakim/alfariz.jpg"', '<article id="org-alfariz-maulana-reza" class="roster-card"><div class="roster-photo"><img src="images/profil/pegawai/hakim/alfariz.jpg"')
WHERE id = 58 AND LOCATE('<article class="roster-card"><div class="roster-photo"><img src="images/profil/pegawai/hakim/alfariz.jpg"', introtext) > 0;

UPDATE #__content SET introtext = REPLACE(introtext, '<article class="roster-card"><div class="roster-photo"><img src="images/profil/pegawai/hakim/geraldo.jpeg"', '<article id="org-geraldo-gracelo-mario-situmeang" class="roster-card"><div class="roster-photo"><img src="images/profil/pegawai/hakim/geraldo.jpeg"')
WHERE id = 58 AND LOCATE('<article class="roster-card"><div class="roster-photo"><img src="images/profil/pegawai/hakim/geraldo.jpeg"', introtext) > 0;

UPDATE #__content SET introtext = REPLACE(introtext, '<article class="roster-card"><div class="roster-photo"><img src="images/profil/pegawai/hakim/haditio.png"', '<article id="org-haditio" class="roster-card"><div class="roster-photo"><img src="images/profil/pegawai/hakim/haditio.png"')
WHERE id = 58 AND LOCATE('<article class="roster-card"><div class="roster-photo"><img src="images/profil/pegawai/hakim/haditio.png"', introtext) > 0;

UPDATE #__content SET introtext = REPLACE(introtext, '<article class="roster-card"><div class="roster-photo"><img src="images/profil/pegawai/hakim/swandi.jpeg"', '<article id="org-swandi-hutabarat" class="roster-card"><div class="roster-photo"><img src="images/profil/pegawai/hakim/swandi.jpeg"')
WHERE id = 58 AND LOCATE('<article class="roster-card"><div class="roster-photo"><img src="images/profil/pegawai/hakim/swandi.jpeg"', introtext) > 0;

UPDATE #__content SET introtext = REPLACE(introtext, '<article class="roster-card"><div class="roster-photo"><img src="images/profil/pegawai/hakim/sutriyadi.png"', '<article id="org-sutriyadi" class="roster-card"><div class="roster-photo"><img src="images/profil/pegawai/hakim/sutriyadi.png"')
WHERE id = 58 AND LOCATE('<article class="roster-card"><div class="roster-photo"><img src="images/profil/pegawai/hakim/sutriyadi.png"', introtext) > 0;

UPDATE #__content SET introtext = REPLACE(introtext, '<article class="roster-card"><div class="roster-photo"><img src="images/profil/pegawai/hakim/endro.png"', '<article id="org-endro-basuki-prabowo" class="roster-card"><div class="roster-photo"><img src="images/profil/pegawai/hakim/endro.png"')
WHERE id = 58 AND LOCATE('<article class="roster-card"><div class="roster-photo"><img src="images/profil/pegawai/hakim/endro.png"', introtext) > 0;

UPDATE #__content SET introtext = REPLACE(introtext, '<article class="roster-card"><div class="roster-photo"><img src="images/profil/pegawai/hakim/suriadi.png"', '<article id="org-suriadi" class="roster-card"><div class="roster-photo"><img src="images/profil/pegawai/hakim/suriadi.png"')
WHERE id = 58 AND LOCATE('<article class="roster-card"><div class="roster-photo"><img src="images/profil/pegawai/hakim/suriadi.png"', introtext) > 0;

UPDATE #__content SET introtext = REPLACE(introtext, '<article class="roster-card"><div class="roster-photo"><img src="images/profil/pegawai/hakim/sirodjuddin.png"', '<article id="org-sirodjuddin" class="roster-card"><div class="roster-photo"><img src="images/profil/pegawai/hakim/sirodjuddin.png"')
WHERE id = 58 AND LOCATE('<article class="roster-card"><div class="roster-photo"><img src="images/profil/pegawai/hakim/sirodjuddin.png"', introtext) > 0;

UPDATE #__content SET introtext = REPLACE(introtext, '<article class="roster-card"><div class="roster-photo"><img src="images/profil/pegawai/hakim/halomoan-freddy-sitinjak.png"', '<article id="org-dr-halomoan-freddy-sitinjak-alexandra" class="roster-card"><div class="roster-photo"><img src="images/profil/pegawai/hakim/halomoan-freddy-sitinjak.png"')
WHERE id = 58 AND LOCATE('<article class="roster-card"><div class="roster-photo"><img src="images/profil/pegawai/hakim/halomoan-freddy-sitinjak.png"', introtext) > 0;

UPDATE #__content SET introtext = REPLACE(introtext, '<article class="roster-featured"><div class="roster-featured-photo"><img src="images/profil/pegawai/kepaniteraan/hadry.jpg"', '<article id="org-hadry-b" class="roster-featured"><div class="roster-featured-photo"><img src="images/profil/pegawai/kepaniteraan/hadry.jpg"')
WHERE id = 59 AND LOCATE('<article class="roster-featured"><div class="roster-featured-photo"><img src="images/profil/pegawai/kepaniteraan/hadry.jpg"', introtext) > 0;

UPDATE #__content SET introtext = REPLACE(introtext, '<article class="roster-card"><div class="roster-photo"><img src="images/profil/pegawai/kepaniteraan/jhivo.png"', '<article id="org-jhivo-wilanda" class="roster-card"><div class="roster-photo"><img src="images/profil/pegawai/kepaniteraan/jhivo.png"')
WHERE id = 59 AND LOCATE('<article class="roster-card"><div class="roster-photo"><img src="images/profil/pegawai/kepaniteraan/jhivo.png"', introtext) > 0;

UPDATE #__content SET introtext = REPLACE(introtext, '<article class="roster-card"><div class="roster-photo"><img src="images/profil/pegawai/kepaniteraan/ari.webp"', '<article id="org-ari-putra-utama" class="roster-card"><div class="roster-photo"><img src="images/profil/pegawai/kepaniteraan/ari.webp"')
WHERE id = 59 AND LOCATE('<article class="roster-card"><div class="roster-photo"><img src="images/profil/pegawai/kepaniteraan/ari.webp"', introtext) > 0;

UPDATE #__content SET introtext = REPLACE(introtext, '<article class="roster-card"><div class="roster-photo"><img src="images/profil/pegawai/kepaniteraan/marihod.webp"', '<article id="org-marihod-tua-lubis" class="roster-card"><div class="roster-photo"><img src="images/profil/pegawai/kepaniteraan/marihod.webp"')
WHERE id = 59 AND LOCATE('<article class="roster-card"><div class="roster-photo"><img src="images/profil/pegawai/kepaniteraan/marihod.webp"', introtext) > 0;

UPDATE #__content SET introtext = REPLACE(introtext, '<article class="roster-card"><div class="roster-photo"><img src="images/profil/pegawai/kesekretariatan/juprizal.webp"', '<article id="org-juprizal" class="roster-card"><div class="roster-photo"><img src="images/profil/pegawai/kesekretariatan/juprizal.webp"')
WHERE id = 59 AND LOCATE('<article class="roster-card"><div class="roster-photo"><img src="images/profil/pegawai/kesekretariatan/juprizal.webp"', introtext) > 0;

UPDATE #__content SET introtext = REPLACE(introtext, '<article class="roster-card"><div class="roster-photo"><img src="images/profil/pegawai/kesekretariatan/asturi.png"', '<article id="org-asturi-periyadi" class="roster-card"><div class="roster-photo"><img src="images/profil/pegawai/kesekretariatan/asturi.png"')
WHERE id = 59 AND LOCATE('<article class="roster-card"><div class="roster-photo"><img src="images/profil/pegawai/kesekretariatan/asturi.png"', introtext) > 0;

UPDATE #__content SET introtext = REPLACE(introtext, '<article class="roster-card"><div class="roster-photo"><img src="images/profil/pegawai/kesekretariatan/dion.webp"', '<article id="org-dion-boy-ardita" class="roster-card"><div class="roster-photo"><img src="images/profil/pegawai/kesekretariatan/dion.webp"')
WHERE id = 59 AND LOCATE('<article class="roster-card"><div class="roster-photo"><img src="images/profil/pegawai/kesekretariatan/dion.webp"', introtext) > 0;

UPDATE #__content SET introtext = REPLACE(introtext, '<article class="roster-featured"><div class="roster-featured-photo"><img src="images/profil/pegawai/kesekretariatan/mario.webp"', '<article id="org-mario-tyson-nadapdap" class="roster-featured"><div class="roster-featured-photo"><img src="images/profil/pegawai/kesekretariatan/mario.webp"')
WHERE id = 60 AND LOCATE('<article class="roster-featured"><div class="roster-featured-photo"><img src="images/profil/pegawai/kesekretariatan/mario.webp"', introtext) > 0;

UPDATE #__content SET introtext = REPLACE(introtext, '<article class="roster-card"><div class="roster-photo"><img src="images/profil/pegawai/kesekretariatan/candra.webp"', '<article id="org-candra-firmansyah" class="roster-card"><div class="roster-photo"><img src="images/profil/pegawai/kesekretariatan/candra.webp"')
WHERE id = 60 AND LOCATE('<article class="roster-card"><div class="roster-photo"><img src="images/profil/pegawai/kesekretariatan/candra.webp"', introtext) > 0;

UPDATE #__content SET introtext = REPLACE(introtext, '<article class="roster-card"><div class="roster-photo"><img src="images/profil/pegawai/kesekretariatan/frans.jpg"', '<article id="org-frans-alberto-siregar" class="roster-card"><div class="roster-photo"><img src="images/profil/pegawai/kesekretariatan/frans.jpg"')
WHERE id = 60 AND LOCATE('<article class="roster-card"><div class="roster-photo"><img src="images/profil/pegawai/kesekretariatan/frans.jpg"', introtext) > 0;

UPDATE #__content SET introtext = REPLACE(introtext, '<article class="roster-card"><div class="roster-photo"><img src="images/profil/pegawai/kesekretariatan/faris.webp"', '<article id="org-muhammad-faris-akbar" class="roster-card"><div class="roster-photo"><img src="images/profil/pegawai/kesekretariatan/faris.webp"')
WHERE id = 60 AND LOCATE('<article class="roster-card"><div class="roster-photo"><img src="images/profil/pegawai/kesekretariatan/faris.webp"', introtext) > 0;

UPDATE #__content SET introtext = REPLACE(introtext, '<article class="roster-card"><div class="roster-photo"><img src="/images/profil/pegawai/pppk/bait.png"', '<article id="org-bait" class="roster-card"><div class="roster-photo"><img src="/images/profil/pegawai/pppk/bait.png"')
WHERE id = 114 AND LOCATE('<article class="roster-card"><div class="roster-photo"><img src="/images/profil/pegawai/pppk/bait.png"', introtext) > 0;

UPDATE #__content SET introtext = REPLACE(introtext, '<article class="roster-card"><div class="roster-photo"><img src="/images/profil/pegawai/pppk/ratih.png"', '<article id="org-rati-pusita" class="roster-card"><div class="roster-photo"><img src="/images/profil/pegawai/pppk/ratih.png"')
WHERE id = 114 AND LOCATE('<article class="roster-card"><div class="roster-photo"><img src="/images/profil/pegawai/pppk/ratih.png"', introtext) > 0;

UPDATE #__content SET introtext = REPLACE(introtext, '<article class="roster-card"><div class="roster-photo"><img src="/images/profil/pegawai/pppk/ria.webp"', '<article id="org-ria-angelina-sitompul" class="roster-card"><div class="roster-photo"><img src="/images/profil/pegawai/pppk/ria.webp"')
WHERE id = 114 AND LOCATE('<article class="roster-card"><div class="roster-photo"><img src="/images/profil/pegawai/pppk/ria.webp"', introtext) > 0;

UPDATE #__content SET introtext = REPLACE(introtext, '<article class="roster-card"><div class="roster-photo"><img src="/images/profil/pegawai/pppk/yuningsih.webp"', '<article id="org-yuningsih" class="roster-card"><div class="roster-photo"><img src="/images/profil/pegawai/pppk/yuningsih.webp"')
WHERE id = 114 AND LOCATE('<article class="roster-card"><div class="roster-photo"><img src="/images/profil/pegawai/pppk/yuningsih.webp"', introtext) > 0;

UPDATE #__content SET introtext = REPLACE(introtext, '<article class="roster-card"><div class="roster-photo"><img src="/images/profil/pegawai/pppk/noki.webp"', '<article id="org-noki-suryatno" class="roster-card"><div class="roster-photo"><img src="/images/profil/pegawai/pppk/noki.webp"')
WHERE id = 114 AND LOCATE('<article class="roster-card"><div class="roster-photo"><img src="/images/profil/pegawai/pppk/noki.webp"', introtext) > 0;

UPDATE #__content SET introtext = REPLACE(introtext, '<article class="roster-card"><div class="roster-photo"><img src="/images/profil/pegawai/pppk/riko.png"', '<article id="org-riko-gustianto" class="roster-card"><div class="roster-photo"><img src="/images/profil/pegawai/pppk/riko.png"')
WHERE id = 114 AND LOCATE('<article class="roster-card"><div class="roster-photo"><img src="/images/profil/pegawai/pppk/riko.png"', introtext) > 0;

UPDATE #__content SET introtext = REPLACE(introtext, '<article class="roster-card"><div class="roster-photo"><img src="/images/profil/pegawai/pppk/kartina.png"', '<article id="org-kartina" class="roster-card"><div class="roster-photo"><img src="/images/profil/pegawai/pppk/kartina.png"')
WHERE id = 114 AND LOCATE('<article class="roster-card"><div class="roster-photo"><img src="/images/profil/pegawai/pppk/kartina.png"', introtext) > 0;

UPDATE #__content SET introtext = REPLACE(introtext, '<article class="roster-card"><div class="roster-photo"><img src="/images/profil/pegawai/pppk/kusnaidi.webp"', '<article id="org-kusnaidi" class="roster-card"><div class="roster-photo"><img src="/images/profil/pegawai/pppk/kusnaidi.webp"')
WHERE id = 114 AND LOCATE('<article class="roster-card"><div class="roster-photo"><img src="/images/profil/pegawai/pppk/kusnaidi.webp"', introtext) > 0;

UPDATE #__content SET introtext = REPLACE(introtext, '<article class="roster-card"><div class="roster-photo"><img src="/images/profil/pegawai/pppk/ardiansyah.webp"', '<article id="org-ardiansyah" class="roster-card"><div class="roster-photo"><img src="/images/profil/pegawai/pppk/ardiansyah.webp"')
WHERE id = 114 AND LOCATE('<article class="roster-card"><div class="roster-photo"><img src="/images/profil/pegawai/pppk/ardiansyah.webp"', introtext) > 0;
