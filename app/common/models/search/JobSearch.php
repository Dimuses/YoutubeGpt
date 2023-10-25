<?php

namespace common\models\search;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\QueueJobs;

/**
 * JobSearch represents the model behind the search form of `common\models\QueueJobs`.
 */
class JobSearch extends QueueJobs
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'user_id', 'created_at', 'started_at', 'completed_at'], 'integer'],
            [['job_id', 'type', 'status', 'error_message'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = QueueJobs::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'id' => $this->id,
            'user_id' => $this->user_id,
            'created_at' => $this->created_at,
            'started_at' => $this->started_at,
            'completed_at' => $this->completed_at,
        ]);

        $query->andFilterWhere(['like', 'job_id', $this->job_id])
            ->andFilterWhere(['like', 'type', $this->type])
            ->andFilterWhere(['like', 'status', $this->status])
            ->andFilterWhere(['like', 'error_message', $this->error_message]);

        return $dataProvider;
    }
}
