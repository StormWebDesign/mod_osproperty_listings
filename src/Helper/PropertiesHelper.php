<?php

namespace Joomla\Module\OspropertyListings\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\Registry\Registry;

final class PropertiesHelper
{
    public static function getItems(Registry $params): array
    {
        Factory::getApplication()->enqueueMessage('DEBUG: PropertiesHelper reached');

        /** @var DatabaseInterface $db */
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        $limit = (int) $params->get('count', 6);
        $cats  = $params->get('categories', []);
        if (!\is_array($cats)) {
            $cats = empty($cats) ? [] : [$cats];
        }
        $categoriesIds = array_filter(array_map('intval', $cats));

        $propsTable  = '#__osrs_properties';
        $citiesTable = '#__osrs_cities';
        $mapTable    = '#__osrs_property_categories';

        // Column detection
        $propCols  = self::getCols($db, $propsTable);
        $cityCols  = self::getCols($db, $citiesTable);
        $mapCols   = self::getCols($db, $mapTable);
        $titleCol     = self::firstExisting($propCols, ['pro_name', 'title', 'name']);
        $priceCol     = self::firstExisting($propCols, ['price', 'pro_price', 'price_amount']);
        $descCol      = self::firstExisting($propCols, ['pro_small_desc', 'pro_short_desc', 'short_description', 'description', 'introtext']);
        $propCityKey  = self::firstExisting($propCols, ['city_id', 'city', 'cid', 'cityid']);

        $publishedCol = self::firstExisting($propCols, ['published', 'state']);
        $approvedCol  = self::firstExisting($propCols, ['approved', 'is_approved']);
        $soldCol      = self::firstExisting($propCols, ['is_sold', 'sold']);

        $currCol   = self::firstExisting($propCols, ['curr', 'currency_id', 'currency']);

        $q = $db->getQuery(true)
            ->select([
                $db->quoteName('p.id', 'id'),
                $titleCol ? $db->quoteName("p.$titleCol", 'title') : '"" AS title',
                $priceCol ? $db->quoteName("p.$priceCol", 'price') : 'NULL AS price',
                $descCol  ? $db->quoteName("p.$descCol", 'description') : '"" AS description',
            ])
            ->from($db->quoteName($propsTable, 'p'));

        $q->select($currCol ? $db->quoteName("p.$currCol", 'currency_id') : 'NULL AS currency_id');

        // --- City resolution ---
        if ($propCityKey) {
            $cityNameCol = self::firstExisting($cityCols, ['city', 'name', 'title']);
            if (!empty($cityCols) && $cityNameCol) {
                $coalesce = 'COALESCE(' . $db->quoteName("ci.$cityNameCol") . ', ' . $db->quoteName("p.$propCityKey") . ') AS ' . $db->quoteName('city');
                $q->select($coalesce)
                    ->join(
                        'LEFT',
                        $db->quoteName($citiesTable, 'ci') . ' ON ' . $db->quoteName('ci.id') . ' = ' . $db->quoteName("p.$propCityKey")
                    );
            } else {
                $q->select($db->quoteName("p.$propCityKey", 'city'));
            }
        } else {
            $q->select('"" AS city');
        }
        // --- end City resolution ---

        // Category filter
        if ($categoriesIds && $mapCols) {
            $fkProp = self::firstExisting($mapCols, ['pro_id', 'property_id', 'pid', 'p_id']);
            $fkCat  = self::firstExisting($mapCols, ['category_id', 'cat_id', 'cid']);
            if ($fkProp && $fkCat) {
                $q->join('INNER', $db->quoteName($mapTable, 'pc') . ' ON ' . $db->quoteName('pc.' . $fkProp) . ' = ' . $db->quoteName('p.id'))
                    ->where($db->quoteName('pc.' . $fkCat) . ' IN (' . implode(',', array_map([$db, 'quote'], $categoriesIds)) . ')');
            }
        }

        if ($publishedCol) {
            $q->where($db->quoteName("p.$publishedCol") . ' = 1');
        }
        if ($approvedCol) {
            $q->where($db->quoteName("p.$approvedCol")  . ' = 1');
        }
        if ($soldCol) {
            $q->where('(' . $db->quoteName("p.$soldCol") . ' = 0 OR ' . $db->quoteName("p.$soldCol") . ' IS NULL)');
        }

        $q->order($db->quoteName('p.id') . ' DESC');

        $db->setQuery($q, 0, $limit);
        $rows = (array) $db->loadObjectList();
        if (!$rows) return [];

        // Simplified: no image handling
        foreach ($rows as $row) {
            $row->image = null;
            if (isset($row->description)) {
                $row->description = trim(strip_tags((string) $row->description));
            }
        }

        return $rows;
    }

    private static function getCols(DatabaseInterface $db, string $table): array
    {
        try {
            $cols = $db->getTableColumns($table, false);
            return array_map(static fn($c) => strtolower($c), array_keys($cols ?? []));
        } catch (\Throwable $e) {
            return [];
        }
    }

    private static function firstExisting(array $available, array $candidates): ?string
    {
        $lut = array_flip($available);
        foreach ($candidates as $c) {
            $c = strtolower($c);
            if (isset($lut[$c])) return $c;
        }
        return null;
    }
}
