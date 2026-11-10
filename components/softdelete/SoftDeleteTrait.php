<?php

namespace app\components\softdelete;

/**
 * ใช้กับ ActiveRecord ทุกตัว: ลบ = ตั้ง flagDel = 1 (ไม่ลบแถวจริง) และซ่อนแถวที่ลบแล้วจากทุก query
 *
 * - $model->delete() และ Model::deleteAll() → soft delete
 * - Model::find()->withDeleted() → เห็นแถวที่ลบแล้วด้วย
 * - $model->restore() → นำกลับมา (ใช้ภายในระบบ เช่น เพิ่มสมาชิกเดิมกลับเข้าทีม)
 */
trait SoftDeleteTrait
{
    public static function find()
    {
        return new SoftDeleteQuery(static::class);
    }

    /**
     * @return int|false จำนวนแถวที่ถูกตั้ง flag (1) หรือ false ถ้าถูกยกเลิกโดย beforeDelete()
     */
    public function delete()
    {
        if (!$this->beforeDelete()) {
            return false;
        }
        $n = $this->markDeleted(1);
        $this->afterDelete();
        return $n;
    }

    public function softDelete(): bool
    {
        return (bool)$this->delete();
    }

    public function restore(): bool
    {
        return (bool)$this->markDeleted(0);
    }

    public function isDeleted(): bool
    {
        return (bool)$this->getAttribute('flagDel');
    }

    public static function deleteAll($condition = null, $params = [])
    {
        $attrs = ['flagDel' => 1];
        if (static::getTableSchema()->getColumn('updated_at')) {
            $attrs['updated_at'] = time();
        }
        return static::updateAll($attrs, $condition ?? '', $params);
    }

    private function markDeleted(int $flag): int
    {
        $attrs = ['flagDel' => $flag];
        if ($this->hasAttribute('updated_at')) {
            $attrs['updated_at'] = time();
        }
        return $this->updateAttributes($attrs);
    }
}
