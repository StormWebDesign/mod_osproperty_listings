<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\Module\OspropertyListings\Helper\PropertiesHelper;

$items = PropertiesHelper::getItems($params);

require ModuleHelper::getLayoutPath('mod_osproperty_listings', $params->get('layout', 'default'));
