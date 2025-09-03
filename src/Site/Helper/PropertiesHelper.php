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
    public static function getItems(Registry $params): array
    {
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
        $photosTable = '#__osrs_photos';
        $mapTable    = '#__osrs_property_categories';

        // Column detection
        $propCols  = self::getCols($db, $propsTable);
        $photoCols = self::getCols($db, $photosTable);
        $cityCols  = self::getCols($db, $citiesTable);
        $mapCols   = self::getCols($db, $mapTable);

        $titleCol   = self::firstExisting($propCols, ['pro_name', 'title', 'name']);
        $priceCol   = self::firstExisting($propCols, ['price', 'pro_price', 'price_amount']);
        $descCol    = self::firstExisting($propCols, ['pro_small_desc', 'pro_short_desc', 'short_description', 'description', 'introtext']);
        $cityIdCol  = self::firstExisting($propCols, ['city_id', 'cid', 'cityid']);
        $cityTextCol= self::firstExisting($propCols, ['city', 'town']);

        $publishedCol = self::firstExisting($propCols, ['published', 'state']);
        $approvedCol  = self::firstExisting($propCols, ['approved', 'is_approved']);
        $soldCol      = self::firstExisting($propCols, ['is_sold', 'sold']);

        $q = $db->getQuery(true)
            ->select([
                $db->quoteName('p.id', 'id'),
                $titleCol ? $db->quoteName("p.$titleCol", 'title') : '"" AS title',
                $priceCol ? $db->quoteName("p.$priceCol", 'price') : 'NULL AS price',
                $descCol  ? $db->quoteName("p.$descCol", 'description') : '"" AS description',
            ])
            ->from($db->quoteName($propsTable, 'p'));

        // City
        if ($cityIdCol && !empty($cityCols)) {
            $cityNameCol = self::firstExisting($cityCols, ['city', 'name', 'title']);
            if ($cityNameCol) {
                $q->select($db->quoteName("ci.$cityNameCol", 'city'))
                  ->join('LEFT', $db->quoteName($citiesTable, 'ci') . ' ON ' . $db->quoteName('ci.id') . ' = ' . $db->quoteName("p.$cityIdCol"));
            } elseif ($cityTextCol) {
                $q->select($db->quoteName("p.$cityTextCol", 'city'));
            } else {
                $q->select('"" AS city');
            }
        } elseif ($cityTextCol) {
            $q->select($db->quoteName("p.$cityTextCol", 'city'));
        } else {
            $q->select('"" AS city');
        }

        // Category filter
        if ($categoriesIds && $mapCols) {
            $fkProp = self::firstExisting($mapCols, ['pro_id', 'property_id', 'pid', 'p_id']);
            $fkCat  = self::firstExisting($mapCols, ['category_id', 'cat_id', 'cid']);
            if ($fkProp && $fkCat) {
                $q->join('INNER', $db->quoteName($mapTable, 'pc') . ' ON ' . $db->quoteName('pc.' . $fkProp) . ' = ' . $db->quoteName('p.id'))
                  ->where($db->quoteName('pc.' . $fkCat) . ' IN (' . implode(',', array_map([$db, 'quote'], $categoriesIds)) . ')');
            }
        }

        if ($publishedCol) { $q->where($db->quoteName("p.$publishedCol") . ' = 1'); }
        if ($approvedCol)  { $q->where($db->quoteName("p.$approvedCol")  . ' = 1'); }
        if ($soldCol)      { $q->where('(' . $db->quoteName("p.$soldCol") . ' = 0 OR ' . $db->quoteName("p.$soldCol") . ' IS NULL)'); }

        $q->order($db->quoteName('p.id') . ' DESC');

        $db->setQuery($q, 0, $limit);
        $rows = (array) $db->loadObjectList();

        if (!$rows) {
            return [];
        }

        $imageBase = rtrim((string) $params->get('image_base', 'images/osproperty/properties/'), '/') . '/';
        foreach ($rows as $row) {
            $row->image = self::getPrimaryImage((int) $row->id, $db, $photosTable, $imageBase, $photoCols);
            if (isset($row->description)) {
                $row->description = trim(strip_tags((string) $row->description));
            }
        }

        return $rows;
    }

    private static function getPrimaryImage(int $propertyId, DatabaseInterface $db, string $photosTable, string $base, array $photoCols): ?string
    {
        if (!$photoCols) return null;

        $fk     = self::firstExisting($photoCols, ['pro_id', 'property_id', 'pid', 'p_id']);
        $fileCol= self::firstExisting($photoCols, ['image', 'photo', 'filename', 'file']);
        if (!$fk || !$fileCol) return null;

        $hasDefault  = \in_array('is_default', $photoCols, true);
        $hasOrdering = \in_array('ordering', $photoCols, true);

        $q = $db->getQuery(true)
            ->select($db->quoteName($fileCol, 'file'))
            ->from($db->quoteName($photosTable))
            ->where($db->quoteName($fk) . ' = ' . (int) $propertyId);

        $ordering = [];
        if ($hasDefault)  { $ordering[] = $db->quoteName('is_default') . ' DESC'; }
        if ($hasOrdering) { $ordering[] = $db->quoteName('ordering') . ' ASC'; }
        $ordering[] = $db->quoteName('id') . ' ASC';

        $q->order(implode(', ', $ordering));
        $db->setQuery($q, 0, 1);
        $file = (string) $db->loadResult();

        if ($file === '') return null;

        // If file already contains a '/', treat as a path; else prefix
        if (str_contains($file, '/')) return $file;

        return $base . ltrim($file, '/');
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
