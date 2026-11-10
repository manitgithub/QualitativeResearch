<?php

namespace app\components\softdelete;

use yii\db\ActiveQuery;

/**
 * ActiveQuery ที่ซ่อนแถวที่ถูกลบ (flagDel = 1) ให้อัตโนมัติ
 *
 * ครอบคลุม find(), findOne(), relation, validator (unique / exist) และ subquery
 * ใช้ withDeleted() เมื่อจำเป็นต้องเห็นแถวที่ลบแล้วด้วย
 *
 * หมายเหตุ: ตารางที่ถูก joinWith() ไม่ถูกกรองอัตโนมัติ — ต้องใส่เงื่อนไข alias.flagDel = 0 เอง
 */
class SoftDeleteQuery extends ActiveQuery
{
    private bool $withDeleted = false;
    private bool $scopeApplied = false;

    public function withDeleted(bool $value = true): static
    {
        $this->withDeleted = $value;
        return $this;
    }

    public function prepare($builder)
    {
        if (!$this->withDeleted && !$this->scopeApplied) {
            [, $alias] = $this->getTableNameAndAlias();
            $this->andWhere([$alias . '.flagDel' => 0]);
            $this->scopeApplied = true;
        }
        return parent::prepare($builder);
    }
}
