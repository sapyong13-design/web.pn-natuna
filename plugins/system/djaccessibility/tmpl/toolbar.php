<?php
/**
 * @package DJ-Accessibility
 * @copyright Copyright (C) DJ-Extensions.com, All rights reserved.
 * @license http://www.gnu.org/licenses GNU/GPL
 * @author url: http://dj-extensions.com
 * @author email faktycznie@gmail.com
 */

use Joomla\CMS\Language\Text;

defined('_JEXEC') or die;

$style = ' djacc--' . DJAcc::getParam('style', 'dark');
$positionParam = DJAcc::getParam('position', 'sticky');
$alignParam = DJAcc::getParam('align_toolbar', 'top center');
$align = ( 'custom' == $positionParam ) ? '' : ' djacc--' . str_replace(' ', '-', $alignParam);
$custom_links = (array) DJAcc::getParam('custom_links');
?>
<section class="djacc djacc-container djacc-toolbar djacc--hidden<?php echo $style . $align; ?>">
	<div class="djacc__panel">
		<ul class="djacc__list">
			<li class="djacc__item djacc__item--label"><?php echo Text::_('PLG_DJACCESSIBILITY_LABEL_CONTRAST'); ?></li>
			<li class="djacc__item djacc__item--contrast">
				<button class="djacc__btn djacc__btn--invert-colors" aria-label="<?php echo Text::_('PLG_DJACCESSIBILITY_INVERT_COLORS'); ?>">
					<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24">
						<g fill="none" stroke="#fff" stroke-width="2">
							<circle cx="12" cy="12" r="12" stroke="none"/>
							<circle cx="12" cy="12" r="11" fill="none"/>
						</g>
						<path d="M0,12A12,12,0,0,1,12,0V24A12,12,0,0,1,0,12Z" fill="#fff"/>
					</svg>
					<span class="djacc__title"><?php echo Text::_('PLG_DJACCESSIBILITY_INVERT_COLORS'); ?></span>
				</button>
			</li>
			<li class="djacc__item djacc__item--contrast">
				<button class="djacc__btn djacc__btn--monochrome" aria-label="<?php echo Text::_('PLG_DJACCESSIBILITY_MONOCHROME'); ?>">
					<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24">
						<g fill="none" stroke="#fff" stroke-width="2">
							<circle cx="12" cy="12" r="12" stroke="none"/>
							<circle cx="12" cy="12" r="11" fill="none"/>
						</g>
						<line y2="21" transform="translate(12 1.5)" fill="none" stroke="#fff" stroke-linecap="round" stroke-width="2"/>
						<path d="M5.853,7.267a12.041,12.041,0,0,1,1.625-1.2l6.3,6.3v2.829Z" transform="translate(-0.778 -4.278)" fill="#fff"/>
						<path d="M3.2,6.333A12.006,12.006,0,0,1,4.314,4.622l9.464,9.464v2.829Z" transform="translate(-0.778)" fill="#fff"/>
						<path d="M1.823,10.959a11.953,11.953,0,0,1,.45-2.378l11.506,11.5v2.829Z" transform="translate(-0.778)" fill="#fff"/>
					</svg>
					<span class="djacc__title"><?php echo Text::_('PLG_DJACCESSIBILITY_MONOCHROME'); ?></span>
				</button>
			</li>
			<li class="djacc__item djacc__item--contrast">
				<button class="djacc__btn djacc__btn--dark-contrast" aria-label="<?php echo Text::_('PLG_DJACCESSIBILITY_DARK_CONTRAST'); ?>">
					<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24">
						<path d="M12,27A12,12,0,0,1,9.638,3.232a10,10,0,0,0,14.13,14.13A12,12,0,0,1,12,27Z" transform="translate(0 -3.232)" fill="#fff"/>
					</svg>
					<span class="djacc__title"><?php echo Text::_('PLG_DJACCESSIBILITY_DARK_CONTRAST'); ?></span>
				</button>
			</li>
			<li class="djacc__item djacc__item--contrast">
				<button class="djacc__btn djacc__btn--light-contrast" aria-label="<?php echo Text::_('PLG_DJACCESSIBILITY_LIGHT_CONTRAST'); ?>">
					<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 32 32">
						<g transform="translate(7 7)" fill="none" stroke="#fff" stroke-width="2">
							<circle cx="9" cy="9" r="9" stroke="none"/>
							<circle cx="9" cy="9" r="8" fill="none"/>
						</g>
						<rect width="2" height="5" rx="1" transform="translate(15)" fill="#fff"/>
						<rect width="2" height="5" rx="1" transform="translate(26.607 3.979) rotate(45)" fill="#fff"/>
						<rect width="2" height="5" rx="1" transform="translate(32 15) rotate(90)" fill="#fff"/>
						<rect width="2" height="5" rx="1" transform="translate(28.021 26.607) rotate(135)" fill="#fff"/>
						<rect width="2" height="5" rx="1" transform="translate(15 27)" fill="#fff"/>
						<rect width="2" height="5" rx="1" transform="translate(7.515 23.071) rotate(45)" fill="#fff"/>
						<rect width="2" height="5" rx="1" transform="translate(5 15) rotate(90)" fill="#fff"/>
						<rect width="2" height="5" rx="1" transform="translate(8.929 7.515) rotate(135)" fill="#fff"/>
					</svg>
					<span class="djacc__title"><?php echo Text::_('PLG_DJACCESSIBILITY_LIGHT_CONTRAST'); ?></span>
				</button>
			</li>
			
			<li class="djacc__item djacc__item--contrast">
				<button class="djacc__btn djacc__btn--low-saturation" aria-label="<?php echo Text::_('PLG_DJACCESSIBILITY_LOW_SATURATION'); ?>">
					<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24">
						<g fill="none" stroke="#fff" stroke-width="2">
							<circle cx="12" cy="12" r="12" stroke="none"/>
							<circle cx="12" cy="12" r="11" fill="none"/>
						</g>
						<path d="M0,12A12,12,0,0,1,6,1.6V22.394A12,12,0,0,1,0,12Z" transform="translate(0 24) rotate(-90)" fill="#fff"/>
					</svg>
					<span class="djacc__title"><?php echo Text::_('PLG_DJACCESSIBILITY_LOW_SATURATION'); ?></span>
				</button>
			</li>
			<li class="djacc__item djacc__item--contrast">
				<button class="djacc__btn djacc__btn--high-saturation" aria-label="<?php echo Text::_('PLG_DJACCESSIBILITY_HIGH_SATURATION'); ?>">
					<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24">
						<g fill="none" stroke="#fff" stroke-width="2">
							<circle cx="12" cy="12" r="12" stroke="none"/>
							<circle cx="12" cy="12" r="11" fill="none"/>
						</g>
						<path d="M0,12A12.006,12.006,0,0,1,17,1.088V22.911A12.006,12.006,0,0,1,0,12Z" transform="translate(0 24) rotate(-90)" fill="#fff"/>
					</svg>
					<span class="djacc__title"><?php echo Text::_('PLG_DJACCESSIBILITY_HIGH_SATURATION'); ?></span>
				</button>
			</li>
			<li class="djacc__item djacc__item--label"><?php echo Text::_('PLG_DJACCESSIBILITY_LABEL_TEXT'); ?></li>
			<li class="djacc__item">
				<button class="djacc__btn djacc__btn--highlight-links" aria-label="<?php echo Text::_('PLG_DJACCESSIBILITY_HIGHLIGHT_LINKS'); ?>">
					<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24">
						<rect width="24" height="24" fill="none"/>
						<path d="M3.535,21.92a5.005,5.005,0,0,1,0-7.071L6.364,12.02a1,1,0,0,1,1.415,1.413L4.95,16.263a3,3,0,0,0,4.243,4.243l2.828-2.828h0a1,1,0,1,1,1.414,1.415L10.607,21.92a5,5,0,0,1-7.072,0Zm2.829-2.828a1,1,0,0,1,0-1.415L17.678,6.364a1,1,0,1,1,1.415,1.414L7.779,19.092a1,1,0,0,1-1.415,0Zm11.314-5.657a1,1,0,0,1,0-1.413l2.829-2.829A3,3,0,1,0,16.263,4.95L13.436,7.777h0a1,1,0,0,1-1.414-1.414l2.828-2.829a5,5,0,1,1,7.071,7.071l-2.828,2.828a1,1,0,0,1-1.415,0Z" transform="translate(-0.728 -0.728)" fill="#fff"/>
					</svg>
					<span class="djacc__title"><?php echo Text::_('PLG_DJACCESSIBILITY_HIGHLIGHT_LINKS'); ?></span>
				</button>
			</li>
			<li class="djacc__item">
				<button class="djacc__btn djacc__btn--highlight-titles" aria-label="<?php echo Text::_('PLG_DJACCESSIBILITY_HIGHLIGHT_TITLES'); ?>">
					<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24">
						<rect width="2" height="14" rx="1" transform="translate(5 5)" fill="#fff"/>
						<rect width="2" height="14" rx="1" transform="translate(10 5)" fill="#fff"/>
						<rect width="2" height="14" rx="1" transform="translate(17 5)" fill="#fff"/>
						<rect width="2" height="7" rx="1" transform="translate(12 11) rotate(90)" fill="#fff"/>
						<rect width="2" height="5" rx="1" transform="translate(19 5) rotate(90)" fill="#fff"/>
						<g fill="none" stroke="#fff" stroke-width="2">
							<rect width="24" height="24" rx="4" stroke="none"/>
							<rect x="1" y="1" width="22" height="22" rx="3" fill="none"/>
						</g>
					</svg>
					<span class="djacc__title"><?php echo Text::_('PLG_DJACCESSIBILITY_HIGHLIGHT_TITLES'); ?></span>
				</button>
			</li>
			<li class="djacc__item">
				<button class="djacc__btn djacc__btn--screen-reader" aria-label="<?php echo Text::_('PLG_DJACCESSIBILITY_SCREEN_READER'); ?>">
					<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24">
						<g fill="none" stroke="#fff" stroke-width="2">
							<circle cx="12" cy="12" r="12" stroke="none"/>
							<circle cx="12" cy="12" r="11" fill="none"/>
						</g>
						<path d="M2907.964,170h1.91l1.369-2.584,2.951,8.363,2.5-11.585L2919,170h2.132" transform="translate(-2902.548 -158)" fill="none" stroke="#fff" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
					</svg>
					<span class="djacc__title"><?php echo Text::_('PLG_DJACCESSIBILITY_SCREEN_READER'); ?></span>
				</button>
			</li>
			<li class="djacc__item">
				<button class="djacc__btn djacc__btn--read-mode" aria-label="<?php echo Text::_('PLG_DJACCESSIBILITY_READ_MODE'); ?>" data-label="<?php echo Text::_('PLG_DJACCESSIBILITY_READ_MODE_DISABLE'); ?>">
					<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24">
						<g fill="none" stroke="#fff" stroke-width="2">
							<rect width="24" height="24" rx="4" stroke="none"/>
							<rect x="1" y="1" width="22" height="22" rx="3" fill="none"/>
						</g>
						<rect width="14" height="2" rx="1" transform="translate(5 7)" fill="#fff"/>
						<rect width="14" height="2" rx="1" transform="translate(5 11)" fill="#fff"/>
						<rect width="7" height="2" rx="1" transform="translate(5 15)" fill="#fff"/>
					</svg>
					<span class="djacc__title"><?php echo Text::_('PLG_DJACCESSIBILITY_READ_MODE'); ?></span>
				</button>
			</li>
			<li class="djacc__item djacc__item--label"><?php echo Text::_('PLG_DJACCESSIBILITY_LABEL_SCALING'); ?></li>
			<li class="djacc__item">
				<span class="djacc__arrows djacc__arrows--zoom">
					<button class="djacc__dec" aria-label="<?php echo Text::_('PLG_DJACCESSIBILITY_ZOOM_DEC'); ?>">
						<svg xmlns="http://www.w3.org/2000/svg" width="10" height="2" viewBox="0 0 10 2">
							<g transform="translate(1 1)">
								<line x1="8" fill="none" stroke="#fff" stroke-linecap="round" stroke-width="2"/>
							</g>
						</svg>
					</button>
					<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16">
						<g transform="translate(-843 -150)">
							<g transform="translate(843 150)" fill="none" stroke="#404042" stroke-width="2">
								<rect width="16" height="16" rx="4" stroke="none"/>
								<rect x="1" y="1" width="14" height="14" rx="3" fill="none"/>
							</g>
							<rect width="10" height="2" rx="1" transform="translate(846 157)" fill="#404042"/>
							<path d="M2.829-2029.464l-2.121-2.121a1,1,0,0,1-.289-.793,1,1,0,0,1,.289-.793l2.121-2.121a1,1,0,0,1,1.414,0,1,1,0,0,1,0,1.414l-1.5,1.5,1.5,1.5a1,1,0,0,1,0,1.415,1,1,0,0,1-.707.293A1,1,0,0,1,2.829-2029.464Z" transform="translate(845.586 2190.378)" fill="#404042"/>
							<path d="M2.829-2029.464l-2.121-2.121a1,1,0,0,1-.289-.793,1,1,0,0,1,.289-.793l2.121-2.121a1,1,0,0,1,1.414,0,1,1,0,0,1,0,1.414l-1.5,1.5,1.5,1.5a1,1,0,0,1,0,1.415,1,1,0,0,1-.707.293A1,1,0,0,1,2.829-2029.464Z" transform="translate(856.414 -1874.379) rotate(180)" fill="#404042"/>
						</g>
					</svg>
					<span class="djacc__title"><?php echo Text::_('PLG_DJACCESSIBILITY_ZOOM'); ?> <span class="djacc__size">100<span class="djacc__percent">%</span></span></span>
					<button class="djacc__inc" aria-label="<?php echo Text::_('PLG_DJACCESSIBILITY_ZOOM_INC'); ?>">
						<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 10 10">
							<g transform="translate(1 1)">
								<line y2="8" transform="translate(4)" fill="none" stroke="#fff" stroke-linecap="round" stroke-width="2"/>
								<line x1="8" transform="translate(0 4)" fill="none" stroke="#fff" stroke-linecap="round" stroke-width="2"/>
							</g>
						</svg>
					</button>
				</span>
			</li>
			<li class="djacc__item">
				<span class="djacc__arrows djacc__arrows--font-size">
					<button class="djacc__dec" aria-label="<?php echo Text::_('PLG_DJACCESSIBILITY_FONT_DEC'); ?>">
						<svg xmlns="http://www.w3.org/2000/svg" width="10" height="2" viewBox="0 0 10 2">
							<g transform="translate(1 1)">
								<line x1="8" fill="none" stroke="#fff" stroke-linecap="round" stroke-width="2"/>
							</g>
						</svg>
					</button>
					<svg xmlns="http://www.w3.org/2000/svg" width="20" height="21" viewBox="0 0 20 21">
						<text id="Aa" transform="translate(0 17)" fill="#404042" font-size="16" font-family="Roboto-Medium, Roboto" font-weight="500"><tspan x="0" y="0">Aa</tspan></text>
					</svg>
					<span class="djacc__title"><?php echo Text::_('PLG_DJACCESSIBILITY_FONT_SIZE'); ?> <span class="djacc__size">100<span class="djacc__percent">%</span></span></span>
					<button class="djacc__inc" aria-label="<?php echo Text::_('PLG_DJACCESSIBILITY_FONT_INC'); ?>">
						<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 10 10">
							<g transform="translate(1 1)">
								<line y2="8" transform="translate(4)" fill="none" stroke="#fff" stroke-linecap="round" stroke-width="2"/>
								<line x1="8" transform="translate(0 4)" fill="none" stroke="#fff" stroke-linecap="round" stroke-width="2"/>
							</g>
						</svg>
					</button>
				</span>
			</li>
			<li class="djacc__item">
				<span class="djacc__arrows djacc__arrows--line-height">
					<button class="djacc__dec" aria-label="<?php echo Text::_('PLG_DJACCESSIBILITY_LINE_DEC'); ?>">
						<svg xmlns="http://www.w3.org/2000/svg" width="10" height="2" viewBox="0 0 10 2">
							<g transform="translate(1 1)">
								<line x1="8" fill="none" stroke="#fff" stroke-linecap="round" stroke-width="2"/>
							</g>
						</svg>
					</button>
					<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16">
						<g transform="translate(-1178 -62)">
							<g>
								<rect width="10" height="2" rx="1" transform="translate(1184 66)" fill="#404042"/>
								<rect width="10" height="2" rx="1" transform="translate(1184 69)" fill="#404042"/>
								<rect width="5" height="2" rx="1" transform="translate(1184 72)" fill="#404042"/>
								<g transform="translate(1178 2098)" fill="#fff">
									<path d="M 5.000400066375732 -2020.499633789062 L 0.9998999834060669 -2020.499633789062 C 0.7242499589920044 -2020.499633789062 0.4999999701976776 -2020.724365234375 0.4999999701976776 -2021.00048828125 C 0.4999999701976776 -2021.276123046875 0.7242499589920044 -2021.500366210938 0.9998999834060669 -2021.500366210938 L 1.999799966812134 -2021.500366210938 L 2.499799966812134 -2021.500366210938 L 2.499799966812134 -2022.000366210938 L 2.499799966812134 -2034 L 2.499799966812134 -2034.5 L 1.999799966812134 -2034.5 L 0.9998999834060669 -2034.5 C 0.7242499589920044 -2034.5 0.4999999701976776 -2034.724243164062 0.4999999701976776 -2035 C 0.4999999701976776 -2035.275634765625 0.7242499589920044 -2035.499877929688 0.9998999834060669 -2035.499877929688 L 1.999799966812134 -2035.499877929688 L 3.999599933624268 -2035.499877929688 L 5.000400066375732 -2035.499877929688 C 5.276050090789795 -2035.499877929688 5.50029993057251 -2035.275634765625 5.50029993057251 -2035 C 5.50029993057251 -2034.724243164062 5.276050090789795 -2034.5 5.000400066375732 -2034.5 L 3.999599933624268 -2034.5 L 3.499599933624268 -2034.5 L 3.499599933624268 -2034 L 3.499599933624268 -2022.000366210938 L 3.499599933624268 -2021.500366210938 L 3.999599933624268 -2021.500366210938 L 5.000400066375732 -2021.500366210938 C 5.276050090789795 -2021.500366210938 5.50029993057251 -2021.276123046875 5.50029993057251 -2021.00048828125 C 5.50029993057251 -2020.724365234375 5.276050090789795 -2020.499633789062 5.000400066375732 -2020.499633789062 Z" stroke="none"/>
									<path d="M 5.000400066375732 -2020.999633789062 L 5.000400066375732 -2021.000366210938 L 0.9998999834060669 -2020.999633789062 L 5.000400066375732 -2020.999633789062 M 5.000400066375732 -2019.999633789062 L 0.9998999834060669 -2019.999633789062 C 0.4472999572753906 -2019.999633789062 -3.471374654395731e-08 -2020.447875976562 -3.471374654395731e-08 -2021.00048828125 C -3.471374654395731e-08 -2021.552124023438 0.4472999572753906 -2022.000366210938 0.9998999834060669 -2022.000366210938 L 1.999799966812134 -2022.000366210938 L 1.999799966812134 -2034 L 0.9998999834060669 -2034 C 0.4472999572753906 -2034 -3.471374654395731e-08 -2034.447387695312 -3.471374654395731e-08 -2035 C -3.471374654395731e-08 -2035.552490234375 0.4472999572753906 -2035.999877929688 0.9998999834060669 -2035.999877929688 L 5.000400066375732 -2035.999877929688 C 5.55210018157959 -2035.999877929688 6.00029993057251 -2035.552490234375 6.00029993057251 -2035 C 6.00029993057251 -2034.447387695312 5.55210018157959 -2034 5.000400066375732 -2034 L 3.999599933624268 -2034 L 3.999599933624268 -2022.000366210938 L 5.000400066375732 -2022.000366210938 C 5.55210018157959 -2022.000366210938 6.00029993057251 -2021.552124023438 6.00029993057251 -2021.00048828125 C 6.00029993057251 -2020.447875976562 5.55210018157959 -2019.999633789062 5.000400066375732 -2019.999633789062 Z" stroke="none" fill="#404042"/>
								</g>
							</g>
						</g>
					</svg>
					<span class="djacc__title"><?php echo Text::_('PLG_DJACCESSIBILITY_LINE_HEIGHT'); ?> <span class="djacc__size">100<span class="djacc__percent">%</span></span></span>
					<button class="djacc__inc" aria-label="<?php echo Text::_('PLG_DJACCESSIBILITY_LINE_INC'); ?>">
						<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 10 10">
							<g transform="translate(1 1)">
								<line y2="8" transform="translate(4)" fill="none" stroke="#fff" stroke-linecap="round" stroke-width="2"/>
								<line x1="8" transform="translate(0 4)" fill="none" stroke="#fff" stroke-linecap="round" stroke-width="2"/>
							</g>
						</svg>
					</button>
				</span>
			</li>
			<li class="djacc__item">
				<span class="djacc__arrows djacc__arrows--letter-spacing">
					<button class="djacc__dec" aria-label="<?php echo Text::_('PLG_DJACCESSIBILITY_LETTER_DEC'); ?>">
						<svg xmlns="http://www.w3.org/2000/svg" width="10" height="2" viewBox="0 0 10 2">
							<g transform="translate(1 1)">
								<line x1="8" fill="none" stroke="#fff" stroke-linecap="round" stroke-width="2"/>
							</g>
						</svg>
					</button>
					<svg xmlns="http://www.w3.org/2000/svg" width="16" height="14" viewBox="0 0 16 14">
						<g transform="translate(-1209 -64)">
							<rect width="10" height="2" rx="1" transform="translate(1212 64)" fill="#404042"/>
							<rect width="10" height="2" rx="1" transform="translate(1212 67)" fill="#404042"/>
							<rect width="5" height="2" rx="1" transform="translate(1212 70)" fill="#404042"/>
							<g transform="translate(3245 78) rotate(-90)" fill="#fff">
								<path d="M 5.000400066375732 -2020.499633789062 L 0.9998999834060669 -2020.499633789062 C 0.7242499589920044 -2020.499633789062 0.4999999701976776 -2020.724365234375 0.4999999701976776 -2021.00048828125 C 0.4999999701976776 -2021.276123046875 0.7242499589920044 -2021.500366210938 0.9998999834060669 -2021.500366210938 L 1.999799966812134 -2021.500366210938 L 2.499799966812134 -2021.500366210938 L 2.499799966812134 -2022.000366210938 L 2.499799966812134 -2034 L 2.499799966812134 -2034.5 L 1.999799966812134 -2034.5 L 0.9998999834060669 -2034.5 C 0.7242499589920044 -2034.5 0.4999999701976776 -2034.724243164062 0.4999999701976776 -2035 C 0.4999999701976776 -2035.275634765625 0.7242499589920044 -2035.499877929688 0.9998999834060669 -2035.499877929688 L 1.999799966812134 -2035.499877929688 L 3.999599933624268 -2035.499877929688 L 5.000400066375732 -2035.499877929688 C 5.276050090789795 -2035.499877929688 5.50029993057251 -2035.275634765625 5.50029993057251 -2035 C 5.50029993057251 -2034.724243164062 5.276050090789795 -2034.5 5.000400066375732 -2034.5 L 3.999599933624268 -2034.5 L 3.499599933624268 -2034.5 L 3.499599933624268 -2034 L 3.499599933624268 -2022.000366210938 L 3.499599933624268 -2021.500366210938 L 3.999599933624268 -2021.500366210938 L 5.000400066375732 -2021.500366210938 C 5.276050090789795 -2021.500366210938 5.50029993057251 -2021.276123046875 5.50029993057251 -2021.00048828125 C 5.50029993057251 -2020.724365234375 5.276050090789795 -2020.499633789062 5.000400066375732 -2020.499633789062 Z" stroke="none"/>
								<path d="M 5.000400066375732 -2020.999633789062 L 5.000400066375732 -2021.000366210938 L 0.9998999834060669 -2020.999633789062 L 5.000400066375732 -2020.999633789062 M 5.000400066375732 -2019.999633789062 L 0.9998999834060669 -2019.999633789062 C 0.4472999572753906 -2019.999633789062 -3.471374654395731e-08 -2020.447875976562 -3.471374654395731e-08 -2021.00048828125 C -3.471374654395731e-08 -2021.552124023438 0.4472999572753906 -2022.000366210938 0.9998999834060669 -2022.000366210938 L 1.999799966812134 -2022.000366210938 L 1.999799966812134 -2034 L 0.9998999834060669 -2034 C 0.4472999572753906 -2034 -3.471374654395731e-08 -2034.447387695312 -3.471374654395731e-08 -2035 C -3.471374654395731e-08 -2035.552490234375 0.4472999572753906 -2035.999877929688 0.9998999834060669 -2035.999877929688 L 5.000400066375732 -2035.999877929688 C 5.55210018157959 -2035.999877929688 6.00029993057251 -2035.552490234375 6.00029993057251 -2035 C 6.00029993057251 -2034.447387695312 5.55210018157959 -2034 5.000400066375732 -2034 L 3.999599933624268 -2034 L 3.999599933624268 -2022.000366210938 L 5.000400066375732 -2022.000366210938 C 5.55210018157959 -2022.000366210938 6.00029993057251 -2021.552124023438 6.00029993057251 -2021.00048828125 C 6.00029993057251 -2020.447875976562 5.55210018157959 -2019.999633789062 5.000400066375732 -2019.999633789062 Z" stroke="none" fill="#404042"/>
							</g>
						</g>
					</svg>
					<span class="djacc__title"><?php echo Text::_('PLG_DJACCESSIBILITY_LETTER_SPACING'); ?> <span class="djacc__size">100<span class="djacc__percent">%</span></span></span>
					<button class="djacc__inc" aria-label="<?php echo Text::_('PLG_DJACCESSIBILITY_LETTER_INC'); ?>">
						<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 10 10">
							<g transform="translate(1 1)">
								<line y2="8" transform="translate(4)" fill="none" stroke="#fff" stroke-linecap="round" stroke-width="2"/>
								<line x1="8" transform="translate(0 4)" fill="none" stroke="#fff" stroke-linecap="round" stroke-width="2"/>
							</g>
						</svg>
					</button>
				</span>
			</li>
			<li class="djacc__item">
				<button class="djacc__reset" aria-label="<?php echo Text::_('PLG_DJACCESSIBILITY_RESET'); ?>">
					<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 18 18">
						<path d="M9,18a.75.75,0,0,1,0-1.5,7.5,7.5,0,1,0,0-15A7.531,7.531,0,0,0,2.507,5.25H3.75a.75.75,0,0,1,0,1.5h-3A.75.75,0,0,1,0,6V3A.75.75,0,0,1,1.5,3V4.019A9.089,9.089,0,0,1,2.636,2.636,9,9,0,0,1,15.364,15.365,8.94,8.94,0,0,1,9,18Z" fill="#fff"/>
					</svg>
					<span class="djacc__title"><?php echo Text::_('PLG_DJACCESSIBILITY_RESET'); ?></span>
				</button>
			</li>
			<?php foreach($custom_links as $key => $link) {
			if( empty($link->url) || empty($link->name) ) continue;
			?>
			<li class="djacc__item djacc__custom-links">
				<a href="<?php echo $link->url ?>" <?php echo $link->target ? 'target="' . $link->target . '"' : ''; ?> class="djacc__btn djacc__btn--custom-link" aria-label="<?php echo $link->name ?>">
					<?php if( !empty($link->image) ) { ?>
						<img src="<?php echo $link->image ?>" alt="" loading="lazy">
						<span class="djacc__title"><?php echo $link->name ?></span>
					<?php } else { ?>
						<span class="djacc__custom-title"><?php echo $link->name ?></span>
						<?php } ?>
				</a>
			</li>
			<?php } ?>
		</ul>
	</div>
</section>