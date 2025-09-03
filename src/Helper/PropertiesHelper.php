<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_osproperty_listings
 */

namespace Joomla\Module\OspropertyListings\Site\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\Registry\Registry;

final class PropertiesHelper
{
    /**
     * Fetch properties per params, with resilient schema detection.
     *
     * Returns an array of stdClass:
     *  - id, title, price, description, city, image
     */
    public static function getItems(Registry $params): array
    {
        /** @var DatabaseInterface $db */
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        $limit   = (int) $params->get('count', 6);
        $columns = max(1, (int) $params->get('columns', 3));
        $cats    = $params->get('categories', []);
        if (!\is_array($cats)) {
            $cats = empty($cats) ? [] : [$cats];
        }

        $propsTable    = '#__osrs_properties';
        $citiesTable   = '#__osrs_cities';
        $photosTable   = '#__osrs_photos';
        $mapTable      = '#__osrs_property_categories'; // property <-> category
        $categoriesIds = array_filter(array_map('intval', $cats));

        // Column detection helpers
        $propCols  = self::getCols($db, $propsTable);
        $photoCols = self::getCols($db, $photosTable);
        $cityCols  = self::getCols($db, $citiesTable);
        $mapCols   = self::getCols($db, $mapTable);

        $titleCol = self::firstExisting($propCols, ['pro_name', 'title', 'name']);
        $priceCol = self::firstExisting($propCols, ['price', 'pro_price', 'price_amount']);
        $descCol  = self::firstExisting($propCols, ['pro_small_desc', 'pro_short_desc', 'short_description', 'description', 'introtext']);
        $cityIdCol = self::firstExisting($propCols, ['city_id', 'cid', 'cityId']);
        $cityTextCol = self::firstExisting($propCols, ['city', 'town']); // fallback if no city_id

        $publishedCol = self::firstExisting($propCols, ['published', 'state']);
        $approvedCol  = self::firstExisting($propCols, ['approved', 'is_approved']);
        $soldCol      = self::firstExisting($propCols, ['is_sold', 'sold']);

        // Build main query
        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('p.id', 'id'),
                $titleCol ? $db->quoteName("p.$titleCol", 'title') : '"" AS title',
                $priceCol ? $db->quoteName("p.$priceCol", 'price') : 'NULL AS price',
                $descCol ? $db->quoteName("p.$descCol", 'description') : '"" AS description',
            ])
            ->from($db->quoteName($propsTable, 'p'));

        // City join or direct column
        if ($cityIdCol && !empty($cityCols)) {
            $cityNameCol = self::firstExisting($cityCols, ['city', 'name', 'title']);
            if ($cityNameCol) {
                $query->select($db->quoteName("ci.$cityNameCol", 'city'))
                      ->join('LEFT', $db->quoteName($citiesTable, 'ci') . ' ON ' . $db->quoteName("ci.id") . ' = ' . $db->quoteName("p.$cityIdCol"));
            } elseif ($cityTextCol) {
                $query->select($db->quoteName("p.$cityTextCol", 'city'));
            } else {
                $query->select('"" AS city');
            }
        } elseif ($cityTextCol) {
            $query->select($db->quoteName("p.$cityTextCol", 'city'));
        } else {
            $query->select('"" AS city');
        }

        // Category filter (if any)
        if ($categoriesIds && $mapCols) {
            $fkProp = self::firstExisting($mapCols, ['pro_id', 'property_id', 'pid', 'p_id']);
            $fkCat  = self::firstExisting($mapCols, ['category_id', 'cat_id', 'cid']);
            if ($fkProp && $fkCat) {
                $query->join('INNER', $db->quoteName($mapTable, 'pc') . ' ON ' . $db->quoteName('pc.' . $fkProp) . ' = ' . $db->quoteName('p.id'))
                      ->where($db->quoteName('pc.' . $fkCat) . ' IN (' . implode(',', array_map([$db, 'quote'], $categoriesIds)) . ')');
            }
        }

        // Only live listings (if columns exist)
        if ($publishedCol) {
            $query->where($db->quoteName("p.$publishedCol") . ' = 1');
        }
        if ($approvedCol) {
            $query->where($db->quoteName("p.$approvedCol") . ' = 1');
        }
        if ($soldCol) {
            // Don't show sold if column exists and == 1 means sold
            $query->where('(' . $db->quoteName("p.$soldCol") . ' = 0 OR ' . $db->quoteName("p.$soldCol") . ' IS NULL)');
        }

        // Order newest first (fallback to id desc)
        $query->order($db->quoteName('p.id') . ' DESC');

        $db->setQuery($query, 0, $limit);
        $rows = (array) $db->loadObjectList();

        if (!$rows) {
            return [];
        }

        // Enrich with primary image per property (avoid brittle joins due to schema drift)
        $imageBase = rtrim((string) $params->get('image_base', 'images/osproperty/properties/'), '/') . '/';
        foreach ($rows as $row) {
            $row->image = self::getPrimaryImage((int) $row->id, $db, $photosTable, $imageBase, $photoCols);
            // Safety: trim description
            if (isset($row->description)) {
                $row->description = trim(strip_tags((string) $row->description));
            }
        }

        return $rows;
    }

    /**
     * Try to resolve the main image from #__osrs_photos with common columns:
     * - FK: pro_id | property_id | pid
     * - File: image | photo | filename
     * - Prefer is_default desc, then ordering asc, then id asc
     */
    private static function getPrimaryImage(int $propertyId, DatabaseInterface $db, string $photosTable, string $base, array $photoCols): ?string
    {
        if (!$photoCols) {
            return null;
        }

        $fk = self::firstExisting($photoCols, ['pro_id', 'property_id', 'pid', 'p_id']);
        $fileCol = self::firstExisting($photoCols, ['image', 'photo', 'filename', 'file']);

        if (!$fk || !$fileCol) {
            return null;
        }

        $hasDefault  = \in_array('is_default', $photoCols, true);
        $hasOrdering = \in_array('ordering', $photoCols, true);

        $q = $db->getQuery(true)
            ->select($db->quoteName($fileCol, 'file'))
            ->from($db->quoteName($photosTable))
            ->where($db->quoteName($fk) . ' = ' . (int) $propertyId);

        $ordering = [];
        if ($hasDefault) {
            $ordering[] = $db->quoteName('is_default') . ' DESC';
        }
        if ($hasOrdering) {
            $ordering[] = $db->quoteName('ordering') . ' ASC';
        }
        $ordering[] = $db->quoteName('id') . ' ASC';

        $q->order(implode(', ', $ordering));
        $db->setQuery($q, 0, 1);
        $file = (string) $db->loadResult();

        if ($file === '') {
            return null;
        }

        // If stored path is already absolute/relative, use as-is; else prefix base.
        if (str_contains($file, '/')) {
            return $file;
        }
        return $base . ltrim($file, '/');
    }

    /** Return list of column names for a table (lowercased) */
    private static function getCols(DatabaseInterface $db, string $table): array
    {
        try {
            $cols = $db->getTableColumns($table, false);
            return array_map(static fn($c) => strtolower($c), array_keys($cols ?? []));
        } catch (\Throwable $e) {
            return [];
        }
    }

    /** Return first candidate that exists in $available (case-insensitive) */
    private static function firstExisting(array $available, array $candidates): ?string
    {
        $availableLut = array_flip($available);
        foreach ($candidates as $c) {
            $cLower = strtolower($c);
            if (isset($availableLut[$cLower])) {
                return $cLower;
            }
        }
        return null;
    }
}
