<?php
declare(strict_types=1);

namespace backend\controllers;

use common\models\Assistant;

class AssistantController extends \common\controllers\AssistantController
{

    public function actionCreate()
    {
        $model = new Assistant();

        if ($this->request->isPost) {
            if ($model->load($this->request->post()) && $model->save()) {
                return $this->redirect(['view', 'id' => $model->id]);
            }
        } else {
            $model->loadDefaultValues();
        }
        return $this->render('create', [
            'model' => $model,
            'assistants' => $this->assistantRepository->getAll()
        ]);
    }

}