<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ModuleHelper;

// Manually require the helper to avoid PSR-4 mapping issues
require_once __DIR__ . '/src/Helper/PropertiesHelper.php';

use Joomla\Module\OspropertyListings\Helper\PropertiesHelper;

$items = PropertiesHelper::getItems($params);

require ModuleHelper::getLayoutPath('mod_osproperty_listings', $params->get('layout', 'default'));
