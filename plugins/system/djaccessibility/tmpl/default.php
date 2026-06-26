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
$alignParam = DJAcc::getParam('align_popup', 'top right');
$directionParam = DJAcc::getParam('direction', 'top left');
$align = ( 'custom' == $positionParam ) ? ' djacc--' . str_replace(' ', '-', $directionParam) : ' djacc--' . str_replace(' ', '-', $alignParam);
$customBtn = DJAcc::getParam('image', false);
$custom_links = (array) DJAcc::getParam('custom_links');
?>
<section class="djacc djacc-container djacc-popup djacc--hidden<?php echo $style . $align; ?>">
	<?php if( $customBtn ) { ?>
		<button class="djacc__openbtn djacc__openbtn--custom" aria-label="<?php echo Text::_('PLG_DJACCESSIBILITY_OPEN_BUTTON'); ?>" title="<?php echo Text::_('PLG_DJACCESSIBILITY_OPEN_BUTTON'); ?>">
			<img src="<?php echo $customBtn; ?>" alt="<?php echo Text::_('PLG_DJACCESSIBILITY_OPEN_BUTTON'); ?>">
		</button>
	<?php } else { ?>
		<button class="djacc__openbtn djacc__openbtn--default" aria-label="<?php echo Text::_('PLG_DJACCESSIBILITY_OPEN_BUTTON'); ?>" title="<?php echo Text::_('PLG_DJACCESSIBILITY_OPEN_BUTTON'); ?>">
			<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 48 48">
				<path d="M1480.443,27.01l-3.891-7.51-3.89,7.51a1,1,0,0,1-.89.54,1.073,1.073,0,0,1-.46-.11,1,1,0,0,1-.43-1.35l4.67-9V10.21l-8.81-2.34a1,1,0,1,1,.51-1.93l9.3,2.47,9.3-2.47a1,1,0,0,1,.509,1.93l-8.81,2.34V17.09l4.66,9a1,1,0,1,1-1.769.92ZM1473.583,3a3,3,0,1,1,3,3A3,3,0,0,1,1473.583,3Zm2,0a1,1,0,1,0,1-1A1,1,0,0,0,1475.583,3Z" transform="translate(-1453 10.217)" fill="#fff"/>
			</svg>
		</button>
	<?php } ?>
	<div class="djacc__panel">
		<div class="djacc__header">
			<p class="djacc__title"><?php echo Text::_('PLG_DJACCESSIBILITY_TITLE'); ?></p>
			<button class="djacc__reset" aria-label="<?php echo Text::_('PLG_DJACCESSIBILITY_RESET'); ?>" title="<?php echo Text::_('PLG_DJACCESSIBILITY_RESET'); ?>">
				<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18">
					<path d="M9,18a.75.75,0,0,1,0-1.5,7.5,7.5,0,1,0,0-15A7.531,7.531,0,0,0,2.507,5.25H3.75a.75.75,0,0,1,0,1.5h-3A.75.75,0,0,1,0,6V3A.75.75,0,0,1,1.5,3V4.019A9.089,9.089,0,0,1,2.636,2.636,9,9,0,0,1,15.364,15.365,8.94,8.94,0,0,1,9,18Z" fill="#fff"/>
				</svg>
			</button>
			<button class="djacc__close" aria-label="<?php echo Text::_('PLG_DJACCESSIBILITY_CLOSE'); ?>" title="<?php echo Text::_('PLG_DJACCESSIBILITY_CLOSE'); ?>">
				<svg xmlns="http://www.w3.org/2000/svg" width="14.828" height="14.828" viewBox="0 0 14.828 14.828">
					<g transform="translate(-1842.883 -1004.883)">
						<line x2="12" y2="12" transform="translate(1844.297 1006.297)" fill="none" stroke="#fff" stroke-linecap="round" stroke-width="2"/>
						<line x1="12" y2="12" transform="translate(1844.297 1006.297)" fill="none" stroke="#fff" stroke-linecap="round" stroke-width="2"/>
					</g>
				</svg>
			</button>
		</div>
		<ul class="djacc__list">
			<li class="djacc__item djacc__item--contrast">
				<button class="djacc__btn djacc__btn--invert-colors" title="<?php echo Text::_('PLG_DJACCESSIBILITY_INVERT_COLORS'); ?>">
					<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
						<g fill="none" stroke="#fff" stroke-width="2">
							<circle cx="12" cy="12" r="12" stroke="none"/>
							<circle cx="12" cy="12" r="11" fill="none"/>
						</g>
						<path d="M0,12A12,12,0,0,1,12,0V24A12,12,0,0,1,0,12Z" fill="#fff"/>
					</svg>
					<span class="djacc_btn-label"><?php echo Text::_('PLG_DJACCESSIBILITY_INVERT_COLORS'); ?></span>
				</button>
			</li>
			<li class="djacc__item djacc__item--contrast">
				<button class="djacc__btn djacc__btn--monochrome" title="<?php echo Text::_('PLG_DJACCESSIBILITY_MONOCHROME'); ?>">
					<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
						<g fill="none" stroke="#fff" stroke-width="2">
							<circle cx="12" cy="12" r="12" stroke="none"/>
							<circle cx="12" cy="12" r="11" fill="none"/>
						</g>
						<line y2="21" transform="translate(12 1.5)" fill="none" stroke="#fff" stroke-linecap="round" stroke-width="2"/>
						<path d="M5.853,7.267a12.041,12.041,0,0,1,1.625-1.2l6.3,6.3v2.829Z" transform="translate(-0.778 -4.278)" fill="#fff"/>
						<path d="M3.2,6.333A12.006,12.006,0,0,1,4.314,4.622l9.464,9.464v2.829Z" transform="translate(-0.778)" fill="#fff"/>
						<path d="M1.823,10.959a11.953,11.953,0,0,1,.45-2.378l11.506,11.5v2.829Z" transform="translate(-0.778)" fill="#fff"/>
					</svg>
					<span class="djacc_btn-label"><?php echo Text::_('PLG_DJACCESSIBILITY_MONOCHROME'); ?></span>
				</button>
			</li>
			<li class="djacc__item djacc__item--contrast">
				<button class="djacc__btn djacc__btn--dark-contrast" title="<?php echo Text::_('PLG_DJACCESSIBILITY_DARK_CONTRAST'); ?>">
					<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
						<path d="M12,27A12,12,0,0,1,9.638,3.232a10,10,0,0,0,14.13,14.13A12,12,0,0,1,12,27Z" transform="translate(0 -3.232)" fill="#fff"/>
					</svg>
					<span class="djacc_btn-label"><?php echo Text::_('PLG_DJACCESSIBILITY_DARK_CONTRAST'); ?></span>
				</button>
			</li>
			<li class="djacc__item djacc__item--contrast">
				<button class="djacc__btn djacc__btn--light-contrast" title="<?php echo Text::_('PLG_DJACCESSIBILITY_LIGHT_CONTRAST'); ?>">
					<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 32 32">
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
					<span class="djacc_btn-label"><?php echo Text::_('PLG_DJACCESSIBILITY_LIGHT_CONTRAST'); ?></span>
				</button>
			</li>
			
			<li class="djacc__item djacc__item--contrast">
				<button class="djacc__btn djacc__btn--low-saturation" title="<?php echo Text::_('PLG_DJACCESSIBILITY_LOW_SATURATION'); ?>">
					<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
						<g fill="none" stroke="#fff" stroke-width="2">
							<circle cx="12" cy="12" r="12" stroke="none"/>
							<circle cx="12" cy="12" r="11" fill="none"/>
						</g>
						<path d="M0,12A12,12,0,0,1,6,1.6V22.394A12,12,0,0,1,0,12Z" transform="translate(0 24) rotate(-90)" fill="#fff"/>
					</svg>
					<span class="djacc_btn-label"><?php echo Text::_('PLG_DJACCESSIBILITY_LOW_SATURATION'); ?></span>
				</button>
			</li>
			<li class="djacc__item djacc__item--contrast">
				<button class="djacc__btn djacc__btn--high-saturation" title="<?php echo Text::_('PLG_DJACCESSIBILITY_HIGH_SATURATION'); ?>">
					<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
						<g fill="none" stroke="#fff" stroke-width="2">
							<circle cx="12" cy="12" r="12" stroke="none"/>
							<circle cx="12" cy="12" r="11" fill="none"/>
						</g>
						<path d="M0,12A12.006,12.006,0,0,1,17,1.088V22.911A12.006,12.006,0,0,1,0,12Z" transform="translate(0 24) rotate(-90)" fill="#fff"/>
					</svg>
					<span class="djacc_btn-label"><?php echo Text::_('PLG_DJACCESSIBILITY_HIGH_SATURATION'); ?></span>
				</button>
			</li>
			<li class="djacc__item">
				<button class="djacc__btn djacc__btn--highlight-links" title="<?php echo Text::_('PLG_DJACCESSIBILITY_HIGHLIGHT_LINKS'); ?>">
					<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
						<rect width="24" height="24" fill="none"/>
						<path d="M3.535,21.92a5.005,5.005,0,0,1,0-7.071L6.364,12.02a1,1,0,0,1,1.415,1.413L4.95,16.263a3,3,0,0,0,4.243,4.243l2.828-2.828h0a1,1,0,1,1,1.414,1.415L10.607,21.92a5,5,0,0,1-7.072,0Zm2.829-2.828a1,1,0,0,1,0-1.415L17.678,6.364a1,1,0,1,1,1.415,1.414L7.779,19.092a1,1,0,0,1-1.415,0Zm11.314-5.657a1,1,0,0,1,0-1.413l2.829-2.829A3,3,0,1,0,16.263,4.95L13.436,7.777h0a1,1,0,0,1-1.414-1.414l2.828-2.829a5,5,0,1,1,7.071,7.071l-2.828,2.828a1,1,0,0,1-1.415,0Z" transform="translate(-0.728 -0.728)" fill="#fff"/>
					</svg>
					<span class="djacc_btn-label"><?php echo Text::_('PLG_DJACCESSIBILITY_HIGHLIGHT_LINKS'); ?></span>
				</button>
			</li>
			<li class="djacc__item">
				<button class="djacc__btn djacc__btn--highlight-titles" title="<?php echo Text::_('PLG_DJACCESSIBILITY_HIGHLIGHT_TITLES'); ?>">
					<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
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
					<span class="djacc_btn-label"><?php echo Text::_('PLG_DJACCESSIBILITY_HIGHLIGHT_TITLES'); ?></span>
				</button>
			</li>
			<li class="djacc__item">
				<button class="djacc__btn djacc__btn--screen-reader" title="<?php echo Text::_('PLG_DJACCESSIBILITY_SCREEN_READER'); ?>">
					<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
						<g fill="none" stroke="#fff" stroke-width="2">
							<circle cx="12" cy="12" r="12" stroke="none"/>
							<circle cx="12" cy="12" r="11" fill="none"/>
						</g>
						<path d="M2907.964,170h1.91l1.369-2.584,2.951,8.363,2.5-11.585L2919,170h2.132" transform="translate(-2902.548 -158)" fill="none" stroke="#fff" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
					</svg>
					<span class="djacc_btn-label"><?php echo Text::_('PLG_DJACCESSIBILITY_SCREEN_READER'); ?></span>
				</button>
			</li>
			<li class="djacc__item">
				<button class="djacc__btn djacc__btn--read-mode" title="<?php echo Text::_('PLG_DJACCESSIBILITY_READ_MODE'); ?>" data-label="<?php echo Text::_('PLG_DJACCESSIBILITY_READ_MODE_DISABLE'); ?>">
					<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
						<g fill="none" stroke="#fff" stroke-width="2">
							<rect width="24" height="24" rx="4" stroke="none"/>
							<rect x="1" y="1" width="22" height="22" rx="3" fill="none"/>
						</g>
						<rect width="14" height="2" rx="1" transform="translate(5 7)" fill="#fff"/>
						<rect width="14" height="2" rx="1" transform="translate(5 11)" fill="#fff"/>
						<rect width="7" height="2" rx="1" transform="translate(5 15)" fill="#fff"/>
					</svg>
					<span class="djacc_btn-label"><?php echo Text::_('PLG_DJACCESSIBILITY_READ_MODE'); ?></span>
				</button>
			</li>
			<li class="djacc__item djacc__item--full">
				<span class="djacc__arrows djacc__arrows--zoom">
					<span class="djacc__label"><?php echo Text::_('PLG_DJACCESSIBILITY_ZOOM'); ?></span>
					<span class="djacc__bar"></span>
					<span class="djacc__size">100<span class="djacc__percent">%</span></span>
					<button class="djacc__dec" aria-label="<?php echo Text::_('PLG_DJACCESSIBILITY_ZOOM_DEC'); ?>" title="<?php echo Text::_('PLG_DJACCESSIBILITY_ZOOM_DEC'); ?>">
						<svg xmlns="http://www.w3.org/2000/svg" width="10" height="2" viewBox="0 0 10 2">
							<g transform="translate(1 1)">
								<line x1="8" fill="none" stroke="#fff" stroke-linecap="round" stroke-width="2"/>
							</g>
						</svg>
					</button>
					<button class="djacc__inc" aria-label="<?php echo Text::_('PLG_DJACCESSIBILITY_ZOOM_INC'); ?>" title="<?php echo Text::_('PLG_DJACCESSIBILITY_ZOOM_INC'); ?>">
						<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 10 10">
							<g transform="translate(1 1)">
								<line y2="8" transform="translate(4)" fill="none" stroke="#fff" stroke-linecap="round" stroke-width="2"/>
								<line x1="8" transform="translate(0 4)" fill="none" stroke="#fff" stroke-linecap="round" stroke-width="2"/>
							</g>
						</svg>
					</button>
				</span>
			</li>
			<li class="djacc__item djacc__item--full">
				<span class="djacc__arrows djacc__arrows--font-size">
					<span class="djacc__label"><?php echo Text::_('PLG_DJACCESSIBILITY_FONT_SIZE'); ?></span>
					<span class="djacc__bar"></span>
					<span class="djacc__size">100<span class="djacc__percent">%</span></span>
					<button class="djacc__dec" aria-label="<?php echo Text::_('PLG_DJACCESSIBILITY_FONT_DEC'); ?>" title="<?php echo Text::_('PLG_DJACCESSIBILITY_FONT_DEC'); ?>">
						<svg xmlns="http://www.w3.org/2000/svg" width="10" height="2" viewBox="0 0 10 2">
							<g transform="translate(1 1)">
								<line x1="8" fill="none" stroke="#fff" stroke-linecap="round" stroke-width="2"/>
							</g>
						</svg>
					</button>
					<button class="djacc__inc" aria-label="<?php echo Text::_('PLG_DJACCESSIBILITY_FONT_INC'); ?>" title="<?php echo Text::_('PLG_DJACCESSIBILITY_FONT_INC'); ?>">
						<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 10 10">
							<g transform="translate(1 1)">
								<line y2="8" transform="translate(4)" fill="none" stroke="#fff" stroke-linecap="round" stroke-width="2"/>
								<line x1="8" transform="translate(0 4)" fill="none" stroke="#fff" stroke-linecap="round" stroke-width="2"/>
							</g>
						</svg>
					</button>
				</span>
			</li>
			<li class="djacc__item djacc__item--full">
				<span class="djacc__arrows djacc__arrows--line-height">
					<span class="djacc__label"><?php echo Text::_('PLG_DJACCESSIBILITY_LINE_HEIGHT'); ?></span>
					<span class="djacc__bar"></span>
					<span class="djacc__size">100<span class="djacc__percent">%</span></span>
					<button class="djacc__dec" aria-label="<?php echo Text::_('PLG_DJACCESSIBILITY_LINE_DEC'); ?>" title="<?php echo Text::_('PLG_DJACCESSIBILITY_LINE_DEC'); ?>">
						<svg xmlns="http://www.w3.org/2000/svg" width="10" height="2" viewBox="0 0 10 2">
							<g transform="translate(1 1)">
								<line x1="8" fill="none" stroke="#fff" stroke-linecap="round" stroke-width="2"/>
							</g>
						</svg>
					</button>
					<button class="djacc__inc" aria-label="<?php echo Text::_('PLG_DJACCESSIBILITY_LINE_INC'); ?>" title="<?php echo Text::_('PLG_DJACCESSIBILITY_LINE_INC'); ?>">
						<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 10 10">
							<g transform="translate(1 1)">
								<line y2="8" transform="translate(4)" fill="none" stroke="#fff" stroke-linecap="round" stroke-width="2"/>
								<line x1="8" transform="translate(0 4)" fill="none" stroke="#fff" stroke-linecap="round" stroke-width="2"/>
							</g>
						</svg>
					</button>
				</span>
			</li>
			<li class="djacc__item djacc__item--full">
				<span class="djacc__arrows djacc__arrows--letter-spacing">
					<span class="djacc__label"><?php echo Text::_('PLG_DJACCESSIBILITY_LETTER_SPACING'); ?></span>
					<span class="djacc__bar"></span>
					<span class="djacc__size">100<span class="djacc__percent">%</span></span>
					<button class="djacc__dec" aria-label="<?php echo Text::_('PLG_DJACCESSIBILITY_LETTER_DEC'); ?>" title="<?php echo Text::_('PLG_DJACCESSIBILITY_LETTER_DEC'); ?>">
						<svg xmlns="http://www.w3.org/2000/svg" width="10" height="2" viewBox="0 0 10 2">
							<g transform="translate(1 1)">
								<line x1="8" fill="none" stroke="#fff" stroke-linecap="round" stroke-width="2"/>
							</g>
						</svg>
					</button>
					<button class="djacc__inc" aria-label="<?php echo Text::_('PLG_DJACCESSIBILITY_LETTER_INC'); ?>" title="<?php echo Text::_('PLG_DJACCESSIBILITY_LETTER_INC'); ?>">
						<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 10 10">
							<g transform="translate(1 1)">
								<line y2="8" transform="translate(4)" fill="none" stroke="#fff" stroke-linecap="round" stroke-width="2"/>
								<line x1="8" transform="translate(0 4)" fill="none" stroke="#fff" stroke-linecap="round" stroke-width="2"/>
							</g>
						</svg>
					</button>
				</span>
			</li>
			<?php foreach($custom_links as $key => $link) {
				if( empty($link->url) || empty($link->name) ) continue;
			?>
			<li class="djacc__item djacc__custom-links">
				<a href="<?php echo $link->url ?>" <?php echo $link->target ? 'target="' . $link->target . '"' : ''; ?> class="djacc__btn djacc__btn--custom-link" title="<?php echo $link->name ?>">
					<?php if( !empty($link->image) ) { ?>
						<img src="<?php echo $link->image ?>" alt="" loading="lazy">
					<?php } ?>
					<span class="djacc_btn-label"><?php echo $link->name ?></span>
				</a>
			</li>
			<?php } ?>
		</ul>
		<?php if( ! DJAcc::pluginType() ): ?>
		<div class="djacc__footer">
			<a href="https://dj-extensions.com" class="djacc__footer-logo" aria-label="DJ-Extensions.com logo">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 38.92 9.19"><path d="m6.84 1.2c-.12-.18-1.88-1.2-2.08-1.2s-1.96 1.02-2.08 1.2-.14 2.18 0 2.41 1.84 1.2 2.08 1.2 1.96-1 2.08-1.2.14-2.2 0-2.41zm-.69 2.02c-.42.33-.89.6-1.39.8-.5-.2-.97-.47-1.39-.8-.09-.53-.09-1.07 0-1.61.43-.32.9-.59 1.39-.8.49.21.96.48 1.39.8.09.53.09 1.07 0 1.59z" fill="#f39236"/><path d="m4.26 5.58c-.12-.18-1.88-1.2-2.08-1.2s-1.96 1.02-2.08 1.2-.14 2.17 0 2.41c.13.23 1.84 1.2 2.08 1.2s1.96-1 2.08-1.2.14-2.21 0-2.41zm-.69 2.02c-.42.33-.89.6-1.39.8-.5-.2-.97-.47-1.39-.8-.09-.53-.09-1.08 0-1.61.43-.32.9-.59 1.39-.8.49.21.96.48 1.39.8.09.53.09 1.07 0 1.59z" fill="#1dabe1"/><path d="m9.43 5.58c-.13-.18-1.88-1.2-2.09-1.2s-1.96 1.02-2.08 1.2-.13 2.18 0 2.41 1.84 1.2 2.08 1.2 1.97-1 2.09-1.2.14-2.21 0-2.41zm-.69 2.02c-.42.33-.89.61-1.39.8-.5-.2-.97-.47-1.39-.8-.09-.53-.09-1.08 0-1.61.43-.32.9-.59 1.39-.8.49.21.96.48 1.39.8.09.53.09 1.07 0 1.59z" fill="#89c059"/><path d="m12.97 6.39c-.21 0-.41-.05-.58-.17-.16-.11-.29-.27-.38-.45-.09-.2-.14-.42-.13-.65 0-.22.04-.44.13-.65.08-.18.21-.34.38-.45s.38-.17.58-.17.4.05.57.17c.16.11.28.27.35.45.08.21.12.43.12.65s-.04.44-.12.65c-.07.18-.2.34-.35.45-.17.12-.37.17-.58.17zm.07-.42c.13 0 .26-.03.37-.11.1-.08.17-.18.21-.3.05-.14.07-.29.07-.44s-.02-.3-.07-.44c-.04-.12-.11-.22-.21-.3-.11-.07-.23-.11-.36-.11-.14 0-.27.03-.38.11-.1.08-.18.18-.22.3-.05.14-.07.28-.07.43s.02.29.07.43c.04.12.12.23.22.3.11.08.24.12.37.11zm.65.35v-1.73h-.06v-1.47h.47v3.2zm.68 1.07v-.44h.12c.1 0 .2-.02.27-.09.06-.08.09-.17.09-.27v-2.67h.47v2.86c.01.17-.05.33-.16.45-.13.11-.29.17-.46.16h-.32zm.48-3.86v-.45h.47v.45zm2.17 2.86c-.22 0-.44-.05-.63-.16-.18-.1-.32-.26-.42-.44-.1-.2-.16-.43-.15-.65 0-.24.04-.47.15-.68.09-.19.23-.34.41-.45.19-.11.4-.16.62-.16s.44.05.63.17c.17.12.31.29.38.48.09.24.12.49.1.74h-.46v-.17c.01-.22-.04-.43-.16-.62-.12-.14-.29-.21-.47-.2-.2-.01-.39.07-.52.22-.13.19-.19.41-.18.64-.01.22.05.43.18.61.13.15.31.23.51.22.13 0 .26-.03.38-.1.11-.07.19-.17.25-.28l.45.15c-.09.21-.23.38-.42.5s-.41.18-.63.18zm-.86-1.14v-.36h1.71v.36zm2.09 1.07.9-1.21-.88-1.19h.55l.6.82.59-.82h.55l-.88 1.19.9 1.21h-.55l-.61-.85-.62.85zm4.07 0c-.15.03-.3.04-.44.04-.13 0-.27-.03-.39-.08-.11-.05-.2-.14-.26-.25-.05-.09-.08-.2-.08-.3s0-.22 0-.35v-2.13h.47v2.1.25c0 .06.02.12.05.18.05.09.15.15.25.16.14.01.27 0 .41-.02v.39zm-1.64-2.03v-.37h1.64v.37zm3.1 2.09c-.22 0-.44-.05-.63-.16-.18-.1-.32-.26-.42-.44-.1-.2-.16-.43-.15-.65 0-.24.04-.47.15-.68.09-.19.23-.34.41-.45.19-.11.4-.16.62-.16s.44.05.62.17.31.29.39.48c.09.24.13.49.1.74h-.47v-.17c.01-.22-.04-.43-.16-.62-.12-.14-.29-.21-.47-.2-.2-.01-.39.07-.52.22-.13.19-.19.41-.18.64-.01.22.05.43.18.61.13.15.31.23.51.22.13 0 .26-.03.38-.1.11-.07.19-.17.25-.28l.46.15c-.09.21-.23.38-.42.5s-.41.18-.63.18zm-.86-1.14v-.36h1.71v.36zm4.06 1.07v-1.18c0-.1 0-.19-.02-.29-.01-.1-.04-.19-.09-.28-.04-.08-.11-.15-.18-.21-.09-.06-.2-.08-.31-.08-.08 0-.16.01-.24.04-.07.03-.14.07-.19.13-.06.07-.11.15-.13.24-.03.12-.05.24-.05.36l-.29-.11c0-.2.04-.4.12-.58.08-.16.2-.3.35-.39.17-.1.36-.15.55-.14.14 0 .29.02.42.08.11.05.2.12.28.21.07.08.12.18.16.28s.06.2.08.3c.01.09.02.17.02.26v1.33h-.47zm-1.69 0v-2.39h.42v.69h.05v1.71h-.47zm3.66.07c-.25.01-.5-.06-.71-.19-.18-.13-.3-.32-.34-.54l.48-.07c.03.12.1.23.21.29.12.08.26.11.4.11.12 0 .24-.02.34-.09.08-.06.13-.15.12-.24 0-.05-.01-.1-.04-.15-.05-.05-.11-.09-.18-.11-.09-.03-.23-.08-.42-.13-.17-.04-.33-.1-.49-.17-.1-.05-.19-.12-.26-.21-.05-.09-.08-.19-.08-.3 0-.14.04-.27.12-.38s.2-.2.33-.25c.16-.06.32-.09.49-.09s.33.03.49.09c.14.05.26.14.35.25s.14.24.16.37l-.48.09c-.01-.11-.07-.21-.16-.27-.11-.07-.23-.11-.36-.11-.12-.01-.24.01-.34.07-.08.04-.13.13-.13.22 0 .05.02.1.05.13.06.05.12.09.19.11.1.03.24.08.43.12.17.04.33.1.48.17.1.05.19.13.25.22.05.1.08.21.08.32 0 .22-.09.43-.26.56-.21.15-.46.22-.72.2zm1.51-2.86v-.45h.47v.45zm0 2.8v-2.4h.47v2.4zm2.17.07c-.22 0-.44-.05-.62-.16s-.32-.26-.41-.45c-.1-.21-.15-.43-.14-.66 0-.23.05-.46.15-.66.09-.18.23-.34.41-.44.19-.11.4-.16.62-.16s.44.05.63.16c.18.11.32.26.41.45.1.2.15.43.14.66 0 .23-.04.46-.14.66-.09.19-.23.34-.41.45-.19.11-.4.17-.62.16zm0-.44c.2.01.39-.07.51-.23.12-.18.18-.39.17-.6.01-.21-.05-.43-.17-.6-.12-.15-.32-.24-.51-.22-.14 0-.27.03-.38.11-.1.07-.18.17-.22.29-.05.14-.08.28-.07.43-.01.22.05.43.17.6.12.15.31.24.51.23zm3.35.37v-1.18c0-.1 0-.19-.02-.29-.01-.1-.04-.19-.09-.28-.04-.08-.11-.15-.18-.21-.09-.06-.2-.08-.31-.08-.08 0-.16.01-.24.04-.07.03-.14.07-.19.13-.06.07-.11.15-.13.24-.03.12-.05.24-.05.36l-.29-.11c0-.2.04-.4.12-.58.08-.16.2-.3.35-.39.17-.1.36-.15.55-.14.14 0 .29.02.42.08.11.05.2.12.28.21.07.08.12.18.16.28s.06.2.08.3c.01.09.02.17.02.26v1.33h-.47zm-1.69 0v-2.4h.42v.69h.05v1.71zm3.66.07c-.25.01-.5-.06-.71-.19-.18-.13-.3-.32-.34-.54l.48-.07c.03.12.1.23.21.29.12.08.26.11.4.11.12 0 .24-.02.34-.09.08-.06.13-.15.12-.24 0-.05-.01-.1-.04-.15-.05-.05-.11-.09-.18-.11-.09-.03-.23-.08-.42-.13-.17-.04-.33-.1-.49-.17-.1-.05-.19-.12-.26-.21-.05-.09-.08-.19-.08-.3 0-.14.04-.27.12-.38s.2-.2.33-.25c.16-.06.32-.09.49-.09s.33.03.49.09c.14.05.26.14.35.25s.14.24.16.37l-.48.09c-.01-.11-.07-.21-.16-.27-.11-.07-.23-.11-.36-.11-.12-.01-.24.01-.34.07-.08.04-.13.13-.13.22 0 .05.02.1.05.13.06.05.12.09.19.11.1.03.24.08.43.12.17.04.33.1.48.17.1.05.19.13.25.22.05.1.08.21.08.32 0 .22-.09.43-.26.56-.21.15-.46.22-.71.2z" class="djname" /></svg>
			</a>
			<div class="djacc__footer-links">
				<a class="djacc__footer-link" href="https://dj-extensions.com/yootheme/dj-accessibility"><?= Text::_('PLG_DJACCESSIBILITY_COPY') ?></a> <?= Text::_('PLG_DJACCESSIBILITY_COPY_BY') ?> DJ-Extensions.com
			</div>
		</div>
		<?php endif ?>
	</div>
</section>