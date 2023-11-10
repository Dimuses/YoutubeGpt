<?php
namespace common\repositories;

use common\models\Assistant;
use yii\db\ActiveRecord;

class AssistantRepository
{
    /**
     *
     * @return Assistant[]|ActiveRecord[]
     */
    public function getAll()
    {
        return Assistant::find()->all();
    }

    /**
     *
     * @param int $id
     * @return Assistant|null
     */
    public function getById($id)
    {
        return Assistant::findOne($id);
    }

    /**
     *
     * @param Assistant $assistant
     * @return bool
     */
    public function save(Assistant $assistant)
    {
        return $assistant->save();
    }

    public function delete(Assistant $assistant)
    {
        return $assistant->delete();
    }

    public function getAllCreatedByAdmin(): array
    {
        return Assistant::find()
            ->where(['created_by_admin' => 1])
            ->all();
    }
}
