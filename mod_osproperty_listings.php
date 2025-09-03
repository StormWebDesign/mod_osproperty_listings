<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_osproperty_listings
 */

defined('_JEXEC') or die;

use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Factory;
use Joomla\Module\OspropertyListings\Site\Helper\PropertiesHelper;

// Load CSS (installed into /media/mod_osproperty_listings/css/style.css)
$wa = Factory::getApplication()->getDocument()->getWebAssetManager();
$wa->registerAndUseStyle(
    'mod_osproperty_listings.styles',
    'media/mod_osproperty_listings/css/style.css',
    [],
    ['defer' => false]
);

$items = PropertiesHelper::getItems($params);

require ModuleHelper::getLayoutPath('mod_osproperty_listings', $params->get('layout', 'default'));
