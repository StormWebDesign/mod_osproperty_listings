<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_osproperty_listings
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

/** @var array $items */
/** @var Joomla\Registry\Registry $params */

$cols = max(1, (int) $params->get('columns', 3));

/**
 * Map our "columns" param to UIkit child-width classes for M (>=960px) and XL
 * 1 => jl-child-width-1-1
 * 2 => jl-child-width-1-2@m
 * 3 => jl-child-width-1-3@m
 * 4 => jl-child-width-1-4@m
 */
$childWidth = match ($cols) {
    1 => 'jl-child-width-1-1',
    2 => 'jl-child-width-1-2@m',
    4 => 'jl-child-width-1-4@m',
    default => 'jl-child-width-1-3@m',
};

if (empty($items)) : ?>
  <div class="jl-text-meta"><?php echo Text::_('MOD_OSPROPERTY_LISTINGS_NONE'); ?></div>
  <?php return;
endif;
?>

<div class="jl-grid-small <?php echo $childWidth; ?>" jl-grid>
  <?php foreach ($items as $item) : ?>
    <div>
      <article class="jl-card jl-card-default jl-card-small jl-overflow-hidden">
        <?php if (!empty($item->image)) : ?>
          <div class="jl-cover-container">
            <img src="<?php echo htmlspecialchars($item->image, ENT_QUOTES, 'UTF-8'); ?>"
                 alt="<?php echo htmlspecialchars($item->title ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                 jl-cover>
            <canvas width="800" height="600"></canvas>
          </div>
        <?php endif; ?>

        <div class="jl-card-body">
          <?php if (!empty($item->title)) : ?>
            <h3 class="jl-card-title jl-margin-small-bottom">
              <?php echo htmlspecialchars($item->title, ENT_QUOTES, 'UTF-8'); ?>
            </h3>
          <?php endif; ?>

          <?php if (isset($item->price) && $item->price !== null && $item->price !== '') : ?>
            <div class="jl-text-bold jl-margin-xsmall-bottom">
              <?php echo htmlspecialchars((string) $item->price, ENT_QUOTES, 'UTF-8'); ?>
            </div>
          <?php endif; ?>

          <?php if (!empty($item->city)) : ?>
            <div class="jl-text-meta jl-margin-xsmall-bottom">
              <span aria-hidden="true">📍</span>
              <?php echo htmlspecialchars($item->city, ENT_QUOTES, 'UTF-8'); ?>
            </div>
          <?php endif; ?>

          <?php if (!empty($item->description)) :
            $short = mb_substr($item->description, 0, 160);
            if (mb_strlen($item->description) > 160) { $short .= '…'; }
          ?>
            <p class="jl-margin-remove-top"><?php echo htmlspecialchars($short, ENT_QUOTES, 'UTF-8'); ?></p>
          <?php endif; ?>
        </div>
      </article>
    </div>
  <?php endforeach; ?>
</div>
