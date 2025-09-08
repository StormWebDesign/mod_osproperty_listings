<?php
defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Module\OspropertyListings\Helper\PropertiesHelper;
use Joomla\Database\DatabaseInterface;

/** @var array $items */
/** @var Joomla\Registry\Registry $params */

$cols = max(1, (int) $params->get('columns', 3));
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
    <pre><?php echo htmlspecialchars(print_r($item, true), ENT_QUOTES, 'UTF-8'); ?></pre>
    <div>
      <article class="jl-card jl-card-default jl-card-small jl-overflow-hidden">
        <?php if (!empty($item->image)) : ?>
          <?php echo $item->id; ?>
          <div class="jl-cover-container">
            <img
              src="<?php echo Uri::root() . 'images/osproperty/properties/' . (int)$item->id . '/medium/' . htmlspecialchars($item->image, ENT_QUOTES, 'UTF-8'); ?>"
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
              <?php
              $fmt = new \NumberFormatter('en_GB', \NumberFormatter::CURRENCY);
              $currencyCode = 'GBP';
              if (!empty($item->currency_id)) {
                $currencyCode = PropertiesHelper::resolveCurrencyCode(
                  Factory::getContainer()->get(DatabaseInterface::class),
                  $item->currency_id
                );
              }
              echo $fmt->formatCurrency((float) $item->price, $currencyCode);
              ?>
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
            if (mb_strlen($item->description) > 160) {
              $short .= '…';
            }
          ?>
            <p class="jl-margin-remove-top"><?php echo htmlspecialchars($short, ENT_QUOTES, 'UTF-8'); ?></p>
          <?php endif; ?>

          <?php
          // Build a safe, non-SEF fallback route to OS Property detail
          // (Most OS Property installs accept: task=property_details&id=ID)
          $detailUrl = Route::_('index.php?option=com_osproperty&task=property_details&id=' . (int) $item->id);
          ?>
          <div class="jl-margin-small-top">
            <a class="jl-button jl-button-primary jl-button-small" href="<?php echo $detailUrl; ?>">
              <?php echo Text::_('JREADMORE'); ?>
            </a>
          </div>
        </div>
      </article>
    </div>
  <?php endforeach; ?>
</div>