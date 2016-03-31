<?php
namespace Billing\Shell\Task;

use Bake\Shell\Task\BakeTask;
use Cake\Core\Configure;
use Cake\I18n\Time;
use Cake\Utility\Inflector;

/**
 * Class BillingTask
 * @package Billing\Shell\Task
 * @property \Bake\Shell\Task\BakeTemplateTask $BakeTemplate
 * @property \Bake\Shell\Task\ModelTask $Model
 */
class BillingTask extends BakeTask
{
    /**
     * Tasks to be loaded by this Task
     *
     * @var array
     */
    public $tasks = [
            'Bake.BakeTemplate',
            'Bake.Model'
    ];

    /**
     * The display name of the bake extension.
     *
     * @var string
     */
    public $name = 'Billing';

    /**
     * The migrations template file for the bake extension.
     * Format [pluginName].[templateFileName]
     *
     * @var string
     */
    public $template = 'Billing.billing';

    /**
     * The generated filename for the migration without leading path.
     *
     * @param string $migrationClassName The migration class name.
     * @return string
     */
    public function fileName($migrationClassName)
    {
        $currentTime = new Time();
        return sprintf('%s_%s.php', $currentTime->format('Ymd'), $migrationClassName);
    }

    /**
     * The path to save the migrations directory.
     *
     * @return string
     */
    public function getPath()
    {
        return CONFIG . DS . 'Migrations' . DS;
    }

    /**
     * Entry point for BillingTask
     *
     * @param null|string $name The users input.
     * @return bool
     */
    public function main($name = null)
    {
        parent::main();
        $name = $this->_getName($name);

        if (empty($name)) {
            $this->out('Pick a model to add billing functionality. Choose from the following:');
            $tables = $this->_getTables();
            foreach ($tables as $table) {
                $this->out('- ' . $this->_getModel($table));
            }
            return true;
        }

        $baseModelName = $this->_getModel($name);
        $baseTableName = $this->_getTable($name);

        if (!$this->_isTablePresent($baseTableName)) {
            $this->out('Base Table does not exist: ' . $baseTableName);
            return true;
        }

        $this->bake($baseModelName);
        $this->out('Next run ./cake migrations migrate');
        return true;
    }

    /**
     * Get data to pass through to template.
     *
     * @param string $baseModelName The model name to attach billable code to.
     * @return array
     */
    public function templateData($baseModelName)
    {
        $baseTableName = $this->_getTable($baseModelName);
        $isUuid = $this->_getPrimaryKeyDataType($baseModelName, $baseTableName) == 'uuid';
        $joiningFieldName = Inflector::singularize($baseTableName) . '_id';
        $subscriptionsTableName = Inflector::singularize($baseTableName) . '_' . 'subscriptions';
        return [
            'baseTableName' => $baseTableName,
            'className' => 'Create' . $this->_getModel($subscriptionsTableName),
            'isUuid' => $isUuid,
            'joiningFieldName' => $joiningFieldName,
            'subscriptionTableName' => $subscriptionsTableName
        ];
    }

    /**
     * Generate the migrations required for the plugin.
     *
     * @param string $baseModelName The model name to attach billable code to.
     * @return string
     */
    public function bake($baseModelName)
    {
        $templateData = $this->templateData($baseModelName);
        $this->BakeTemplate->set($templateData);
        $contents = $this->BakeTemplate->generate($this->template);

        $filename = $this->getPath() . $this->fileName($templateData['className']);
        $this->createFile($filename, $contents);
        $emptyFile = $this->getPath() . 'empty';
        $this->_deleteEmptyFile($emptyFile);
        return $contents;
    }

    /**
     * Gets the option parser instance and configures it.
     *
     * @return \Cake\Console\ConsoleOptionParser
     */
    public function getOptionParser()
    {
        $parser = parent::getOptionParser();
        $parser->description(
            sprintf('Bake a %s class file.', $this->name)
        )->addArgument('name', [
                'help' => sprintf(
                    'Name of the %s to bake. Can use Plugin.name to bake %s files into plugins.',
                    $this->name,
                    $this->name
                )
        ])->addOption('no-test', [
                'boolean' => true,
                'help' => 'Do not generate a test skeleton.'
        ]);

        return $parser;
    }

    /**
     * Convert input into a valid table name.
     *
     * @param string $name
     * @return string
     */
    protected function _getTable($name)
    {
        return Inflector::underscore(Inflector::pluralize($name));
    }

    /**
     * Convert input into a valid model name.
     *
     * @param string $name
     * @return string
     */
    protected function _getModel($name)
    {
        return Inflector::camelize(Inflector::singularize($name));
    }

    /**
     * Outputs a list of user created models from the database.
     * Currently relies on ModelTask code.
     *
     * @return array
     */
    protected function _getTables()
    {
        $this->Model->connection = $this->connection;
        return $this->Model->listUnskipped();
    }

    /**
     * Check if a table is present.
     *
     * @param string $name
     * @return bool
     */
    protected function _isTablePresent($name)
    {
        $tableName = $this->_getTable($name);
        return in_array($tableName, $this->_getTables());
    }

    /**
     * Get primary key data type.
     *
     * @param string $modelName
     * @param string $tableName
     * @return string
     */
    protected function _getPrimaryKeyDataType($modelName, $tableName)
    {
        $this->Model->connection = $this->connection;
        $tableObject = $this->Model->getTableObject($modelName, $tableName);
        $propertySchema = $this->Model->getEntityPropertySchema($tableObject);
        $primaryKey = $this->Model->getPrimaryKey($tableObject);
        return $propertySchema[$primaryKey[0]]['type'];
    }
}
