<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_osproperty_listings
 */

defined('_JEXEC') or die;

spl_autoload_register(function ($class) {
    if ($class === 'Joomla\\Module\\OspropertyListings\\Helper\\PropertiesHelper') {
        require __DIR__ . '/src/Helper/PropertiesHelper.php';
    }
});


use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\Module\OspropertyListings\Helper\PropertiesHelper;

// No custom CSS enqueued (UIkit already on site)

$items = PropertiesHelper::getItems($params);

require ModuleHelper::getLayoutPath('mod_osproperty_listings', $params->get('layout', 'default'));
