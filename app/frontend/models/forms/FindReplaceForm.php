<?php
declare(strict_types=1);

namespace frontend\models\forms;

use yii\base\Model;

class FindReplaceForm extends Model
{
    public $searchText;
    public $replaceText;

    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            [['searchText'], 'required'],
            [['searchText', 'replaceText'], 'string', 'max' => 255],
        ];
    }

    /**
     * @return array customized attribute labels
     */
    public function attributeLabels()
    {
        return [
            'searchText' => 'Текст для поиска',
            'replaceText' => 'Текст для замены',
        ];
    }
}
