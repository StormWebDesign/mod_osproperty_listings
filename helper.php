<?php
/**
 * Helper for OS Property Listings Module
 */

namespace Joomla\Module\OspropertyListings\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

final class ModOspropertyListingsHelper
{
    public static function getItems($params): array
    {
        /** @var DatabaseInterface $db */
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        $limit = (int) $params->get('count', 6);
        $cats  = $params->get('categories', []);

        if (!is_array($cats)) {
            $cats = empty($cats) ? [] : [$cats];
        }
        $categoriesIds = array_filter(array_map('intval', $cats));

        $query = $db->getQuery(true)
            ->select('p.id, p.pro_name, p.price, p.curr, p.pro_small_desc, p.city')
            ->from('#__osrs_properties AS p')
            ->where('p.approved = 1 AND p.published = 1');

        if ($categoriesIds) {
            $query->join('INNER', '#__osrs_property_categories AS pc ON pc.pro_id = p.id')
                ->where('pc.category_id IN (' . implode(',', $categoriesIds) . ')');
        }

        $query->setLimit($limit);

        $db->setQuery($query);
        $properties = $db->loadObjectList();

        if (!$properties) {
            return [];
        }

        // Fetch additional info
        foreach ($properties as &$property) {
            // Currency
            $property->currency_symbol = '';
            if ($property->curr) {
                $query = $db->getQuery(true)
                    ->select('currency_code, currency_symbol')
                    ->from('#__osrs_currencies')
                    ->where('id = ' . (int) $property->curr);
                $db->setQuery($query);
                $currency = $db->loadObject();
                if ($currency) {
                    $property->currency_symbol = $currency->currency_symbol ?: $currency->currency_code;
                }
            }

            // City
            $property->city_name = '';
            if ($property->city) {
                $query = $db->getQuery(true)
                    ->select('city')
                    ->from('#__osrs_cities')
                    ->where('id = ' . (int) $property->city);
                $db->setQuery($query);
                $property->city_name = (string) $db->loadResult();
            }

            // Image
            $property->image = '';
            $query = $db->getQuery(true)
                ->select('image')
                ->from('#__osrs_photos')
                ->where('pro_id = ' . (int) $property->id)
                ->order('ordering ASC');
            $db->setQuery($query, 0, 1);
            $property->image = (string) $db->loadResult();

            // Link
            $property->link = 'index.php?option=com_osproperty&task=property_details&id=' . $property->id . '&Itemid=2052&lang=en';
        }

        return $properties;
    }
}
