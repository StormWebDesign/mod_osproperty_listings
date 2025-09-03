<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_osproperty_listings
 */

defined('_JEXEC') or die;

/** @var array $items */
/** @var Joomla\Registry\Registry $params */

$cols = max(1, (int) $params->get('columns', 3));
$colsClass = 'cols-' . $cols;

if (empty($items)) : ?>
	<div class="mod-osproperty-listings empty">
		<p class="muted"><?php echo JText::_('MOD_OSPROPERTY_LISTINGS_NONE'); ?></p>
	</div>
	<?php return;
endif;
?>

<div class="mod-osproperty-listings <?php echo $colsClass; ?>">
	<?php foreach ($items as $item) : ?>
		<article class="property-card">
			<?php if (!empty($item->image)) : ?>
				<div class="property-image">
					<img src="<?php echo htmlspecialchars($item->image, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($item->title ?? '', ENT_QUOTES, 'UTF-8'); ?>">
				</div>
			<?php endif; ?>

			<div class="property-body">
				<?php if (!empty($item->title)) : ?>
					<h3 class="property-title"><?php echo htmlspecialchars($item->title, ENT_QUOTES, 'UTF-8'); ?></h3>
				<?php endif; ?>

				<?php if (isset($item->price) && $item->price !== null && $item->price !== '') : ?>
					<div class="property-price">
						<?php
						// Display raw price; you can format/currency-symbolise here if needed.
						echo htmlspecialchars((string) $item->price, ENT_QUOTES, 'UTF-8');
						?>
					</div>
				<?php endif; ?>

				<?php if (!empty($item->city)) : ?>
					<div class="property-location">
						<span class="icon-location" aria-hidden="true">📍</span>
						<?php echo htmlspecialchars($item->city, ENT_QUOTES, 'UTF-8'); ?>
					</div>
				<?php endif; ?>

				<?php if (!empty($item->description)) : ?>
					<p class="property-desc">
						<?php
						$short = mb_substr($item->description, 0, 160);
						echo htmlspecialchars($short . (mb_strlen($item->description) > 160 ? '…' : ''), ENT_QUOTES, 'UTF-8');
						?>
					</p>
				<?php endif; ?>
			</div>
		</article>
	<?php endforeach; ?>
</div>
