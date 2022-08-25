<?php

namespace console\controllers;

use yii\console\Controller;
use Yii;
use frontend\models\Table01;
use frontend\models\Table02;
use frontend\models\Table03;
use frontend\models\Table04;
use frontend\models\Table05;
use frontend\models\Table06;
use frontend\models\Table07;
use frontend\models\Table08;
use frontend\models\Table09;
use common\models\User;

/**
 * Export Template and Reference Data
 */
class ExportController extends Controller
{
    /**
     * Export Template
    */
    public function actionTemplate($trz_id)
    {
        $trz_id = (integer) $trz_id;
        $this->genDeleteTemplate($trz_id);
        echo PHP_EOL;
        $this->genInsertTemplate($trz_id);
        echo PHP_EOL;
    }

    // Generate Delete Statements
    protected function genDeleteTemplate($trz_id)
    {
        $this->genDelete('Table03', 'trz_id', $trz_id);
        $this->genDelete('Table02', 'trz_id', $trz_id);
        $this->genDelete('Table01', 'id', $trz_id);
    }

    // Generate Inserts
    protected function genInsertTemplate($trz_id)
    {
        $this->genInsert(Table01::find()->where(['id' => $trz_id])->one());
        $this->genInsert(Table03::find()->where(['trz_id' => $trz_id, 'archive_sw' => 'N'])->orderBy(['id' => SORT_ASC])->all());
        $this->genInsert(Table02::find()->where(['trz_id' => $trz_id, 'archive_sw' => 'N'])->orderBy(['id' => SORT_ASC])->all());
    }

    /**
     * Archive Old templates
    */
    public function actionArchiveOldTemplates()
    {
        $Table01s = Table01::find()->where(['archive_sw' => 'Y'])->all();
        if (empty($Table01s)) {
            echo "No archived Templates found!", "\n";
            return false;
        }
        foreach ($Table01s as $Table01) {
            $this->genArchive('Table01' , 'id', $Table01->id);
        }
        echo PHP_EOL;
    }

    /**
     * Export All Active Templates
    */
    public function actionAllTemplates()
    {
        $Table01s = Table01::find()->where(['archive_sw' => 'N'])->all();
        if (empty($Table01s)) {
            echo "No active Templates found!", "\n";
            return false;
        }
        foreach ($Table01s as $Table01) {
            echo '        // Deleting template: (' . $Table01->id . ') - ' . $Table01->trzname . PHP_EOL;
            $this->genDeleteTemplate($Table01->id);
        }
        echo PHP_EOL;
        foreach ($Table01s as $Table01) {
            echo '        // Inserting template: (' . $Table01->id . ') - ' . $Table01->trzname . PHP_EOL;
            $this->genInsertTemplate($Table01->id);
        }
        echo PHP_EOL;
    }

    /**
     * Export User
    */
    public function actionUser($id)
    {
        $id = (integer) $id;

        // Generate Deletes
        $this->genDelete('user', 'id', $id);

        // Generate Inserts
        $this->genInsert(User::find()->where(['id' => $id])->one());
    }

    /**
     * Export Reference
    */
    public function actionReference($refm_id)
    {
        $refm_id = (integer) $refm_id;

        // Generate Deletes
        $this->genDelete('Table04', 'refm_id', $refm_id);
        $this->genDelete('Table05', 'id', $refm_id);

        // Generate Inserts
        $this->genInsert(Table05::find()->where(['id' => $refm_id])->one());
        $this->genInsert(Table04::find()->where(['refm_id' => $refm_id])->orderBy(['id' => SORT_ASC])->all());
    }

    /**
     * Export All Reference
    */
    public function actionReferenceAll()
    {
        // Generate Deletes
        echo $this->genDeleteAll('Table04');
        echo $this->genDeleteAll('Table05');

        // Generate Inserts
        $this->genInsert(Table05::find()->orderBy(['id' => SORT_ASC])->all());
        $this->genInsert(Table04::find()->orderBy(['refm_id' => SORT_ASC, 'rsort' => SORT_ASC, 'id' => SORT_ASC])->all());
    }

    /**
     * Export Def. Fields Reference
    */
    public function actionRefdf($fm_id)
    {
        $fm_id = (integer) $fm_id;
        // Generate Deletes
        $this->genDelete('Table06', 'fm_id', $fm_id);
        $this->genDelete('Table07', 'id', $fm_id);

        // Generate Inserts
        $this->genInsert(Table07::find()->where(['id' => $fm_id])->one());
        $this->genInsert(Table06::find()->orderBy(['forder' => SORT_ASC, 'id' => SORT_ASC])->all());
    }

    /**
     * Export All Ref States
    */
    public function actionTable08All()
    {
        // Generate Deletes
        echo $this->genDeleteAll('Table08');
        // Generate Inserts
        $this->genInsert(Table08::find()->orderBy(['id' => SORT_ASC])->all());
    }

    /**
     * Export All Products
    */
    public function actionProductAll()
    {
        // Generate Deletes
        echo $this->genDeleteAll('Table09');
        // Generate Inserts
        $this->genInsert(Table09::find()->orderBy(['id' => SORT_ASC])->all());
    }

    /**
     * Checks if Object or Object Array and calls Insert for each Model
    */
    protected function genInsert($models)
    {
        if (is_array($models)) {
            foreach ($models as $model) {
                $this->genInsert1($model);
            }
        } else {
            $this->genInsert1($models);
        }
    }

    /**
     * Formats the insert for a given Model
    */
    protected function genInsert1($model)
    {
        $output = preg_replace("/^/m", "        ",
                  '$this->' .
                  "insert('" . $model->tableName() . "'," . PHP_EOL . '  ' . var_export($model->attributes, true) . ");") .
                  PHP_EOL;
        echo $output;
    }


    /**
     * Generates/Formats the Delete statement given Table, Column and Column Value (where condition)
    */
    protected function genDelete($tableName, $columnName, $columnValue)
    {
        $output = preg_replace("/^/m", "        ",
                  '$this->' .
                  "delete('" . $tableName . "', '" . $columnName . " = :vcolumn', [':vcolumn' => " . $columnValue . "]);") .
                  PHP_EOL;
        echo $output;
    }

    /**
     * Generates/Formats the Update/Archive statement given Table, Column and Column Value (where condition)
    */
    protected function genArchive($tableName, $columnName, $columnValue)
    {
        $output = preg_replace("/^/m", "        ",
                  '$this->' .
                  "update('" . $tableName . "', ['archive_sw' => 'Y'], '" . $columnName . " = :vcolumn', [':vcolumn' => " . $columnValue . "]);") .
                  PHP_EOL;
        echo $output;
    }

    /**
     * Generates/Formats the Delete statement to delete all rows for a Table
    */
    protected function genDeleteAll($tableName)
    {
        $output = preg_replace("/^/m", "        ",
                  '$this->' .
                  "delete('" . $tableName . "');") .
                  PHP_EOL;
        echo $output;
    }

}
