<?php
/**
 * Default template for OS Property Listings
 */

defined('_JEXEC') or die;

use Joomla\CMS\Router\Route;

// Get column count from params
$columns = (int) $params->get('columns', 3);

$image_location = 'images/osproperty/properties'; // Base image location

?>

<div class="uk-grid-small uk-child-width-1-<?php echo $columns; ?>@s uk-grid-match" uk-grid>
    <?php foreach ($items as $item): ?>
        <div>
            <div class="uk-card uk-card-default uk-card-hover uk-card-small">
                <?php if ($item->image): ?>
                    <div class="uk-card-media-top">
                        <img src="<?php echo $image_location . $item->id . '/medium/' . $item->image; ?>"
                             alt="<?php echo htmlspecialchars($item->pro_name, ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                <?php endif; ?>
                <div class="uk-card-body">
                    <h3 class="uk-card-title">
                        <?php echo htmlspecialchars($item->pro_name, ENT_QUOTES, 'UTF-8'); ?>
                    </h3>
                    <p>
                        <strong><?php echo $item->currency_symbol . number_format($item->price); ?></strong>
                    </p>
                    <p>
                        <?php echo htmlspecialchars(mb_strimwidth($item->pro_small_desc, 0, 60, '...'), ENT_QUOTES, 'UTF-8'); ?>
                    </p>
                    <p>
                        <span uk-icon="location"></span>
                        <?php echo htmlspecialchars($item->city_name, ENT_QUOTES, 'UTF-8'); ?>
                    </p>
                </div>
                <div class="uk-card-footer">
                    <a href="<?php echo Route::_($item->link); ?>" class="uk-button uk-button-text">Read More</a>
                </div>
<?php echo $image_location . $p_id . '/medium/' . $item->image; ?>
                <p><small>Property ID: <?php echo (int) $item->id; ?></small></p>


            </div>
        </div>
    <?php endforeach; ?>
</div>
