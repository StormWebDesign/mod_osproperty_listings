<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_osproperty_listings
 */

defined('_JEXEC') or die;

use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Factory;

require_once __DIR__ . '/helper.php';

$items = ModOspropertyListingsHelper::getItems($params);

require ModuleHelper::getLayoutPath('mod_osproperty_listings', $params->get('layout', 'default'));
