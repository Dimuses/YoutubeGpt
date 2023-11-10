<?php
namespace common\repositories;

use common\models\GeneratedAnswers;
use yii\db\ActiveRecord;

class GeneratedAnswersRepository
{
    /**
     *
     * @return GeneratedAnswers[]|ActiveRecord[]
     */
    public function getAll()
    {
        return GeneratedAnswers::find()->all();
    }
    /**
     *
     * @param int $id
     * @return GeneratedAnswers|null
     */
    public function getById($id)
    {
        return GeneratedAnswers::findOne($id);
    }
    /**
     *
     * @param GeneratedAnswers $generatedAnswers
     * @return bool
     */
    public function save(GeneratedAnswers $generatedAnswers)
    {
        return $generatedAnswers->save();
    }

    public function delete(GeneratedAnswers $generatedAnswers)
    {
        return $generatedAnswers->delete();
    }

    public function deleteAllBy(string $fieldName, $value)
    {
        GeneratedAnswers::deleteAll([$fieldName => $value]);
    }

}
