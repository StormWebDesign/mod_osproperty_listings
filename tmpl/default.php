<?php
/**
 * Default template for OS Property Listings
 */

defined('_JEXEC') or die;

use Joomla\CMS\Router\Route;

// Get column count from params
$columns = (int) $params->get('columns', 3);

$image_location = 'images/osproperty/properties/'; // Base image location

?>

<div class="jl-grid-small jl-child-width-1-<?php echo $columns; ?>@s jl-grid-match" jl-grid>
    <?php foreach ($items as $item): ?>
        <div>
            <div class="jl-card jl-card-default jl-card-hover jl-card-small">
                <?php if ($item->image): ?>
                    <div class="jl-card-media-top">
                        <img src="<?php echo $image_location . $item->id . '/medium/' . $item->image; ?>"
                             alt="<?php echo htmlspecialchars($item->pro_name, ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                <?php endif; ?>
                <div class="jl-card-body">
                    <h3 class="jl-card-title">
                        <?php echo htmlspecialchars($item->pro_name, ENT_QUOTES, 'UTF-8'); ?>
                    </h3>
                    <p>
                        <strong><?php echo $item->currency_symbol . number_format($item->price); ?></strong>
                    </p>
                    <p>
                        <?php echo htmlspecialchars(mb_strimwidth($item->pro_small_desc, 0, 60, '...'), ENT_QUOTES, 'UTF-8'); ?>
                    </p>
                    <p>
                        <span jl-icon="location"></span>
                        <?php echo htmlspecialchars($item->city_name, ENT_QUOTES, 'UTF-8'); ?>
                    </p>
                </div>
                <div class="jl-card-footer">
                    <a href="<?php echo Route::_($item->link); ?>" class="jl-button jl-button-text">Read More</a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
