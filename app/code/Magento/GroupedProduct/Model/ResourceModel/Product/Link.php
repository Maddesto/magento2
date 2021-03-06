<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GroupedProduct\Model\ResourceModel\Product;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Model\Entity\MetadataPool;

/**
 * Class Link
 */
class Link extends \Magento\Catalog\Model\ResourceModel\Product\Link
{
    const LINK_TYPE_GROUPED = 3;

    /**
     * @var MetadataPool
     */
    protected $metadataPool;

    /**
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param MetadataPool $metadataPool
     * @param null $connectionName
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        MetadataPool $metadataPool,
        $connectionName = null
    ) {
        $this->metadataPool = $metadataPool;
        parent::__construct(
            $context,
            $connectionName
        );
    }

    /**
     * Retrieve Required children ids
     * Return grouped array, ex array(
     *   group => array(ids)
     * )
     *
     * @param int $parentId
     * @param int $typeId
     * @return array
     */
    public function getChildrenIds($parentId, $typeId)
    {
        $connection = $this->getConnection();
        $childrenIds = [];
        $bind = [':product_id' => (int)$parentId, ':link_type_id' => (int)$typeId];
        $select = $connection->select()->from(
            ['l' => $this->getMainTable()],
            ['linked_product_id']
        )->join(
            ['cpe' => $this->getTable('catalog_product_entity')],
            sprintf(
                'cpe.%s = l.product_id',
                $this->metadataPool->getMetadata(ProductInterface::class)->getLinkField()
            )
        )->where(
            'cpe.entity_id = :product_id'
        )->where(
            'link_type_id = :link_type_id'
        );

        $select->join(
            ['e' => $this->getTable('catalog_product_entity')],
            'e.entity_id = l.linked_product_id AND e.required_options = 0',
            []
        );

        $childrenIds[$typeId] = [];
        $result = $connection->fetchAll($select, $bind);
        foreach ($result as $row) {
            $childrenIds[$typeId][$row['linked_product_id']] = $row['linked_product_id'];
        }

        return $childrenIds;
    }
}
