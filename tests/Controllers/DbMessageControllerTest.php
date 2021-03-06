<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace Yiisoft\Yii\Console\Tests\Controllers;

use yii\helpers\Yii;
use Yiisoft\Arrays\ArrayHelper;
use Yiisoft\Yii\Console\ExitCode;
use Yiisoft\Db\Connection;

/**
 * Tests that [[\Yiisoft\Yii\Console\Controllers\MessageController]] works as expected with DB message format.
 *
 * @group db
 * @group mysql
 */
class DbMessageControllerTest extends BaseMessageControllerTest
{
    protected static $driverName = 'mysql';
    protected static $database;

    /**
     * @var Connection
     */
    protected static $db;

    protected static function runConsoleAction($route, $params = [])
    {
        if (Yii::getApp() === null) {
            Yii::getContainer()->setAll([
                'app' => [
                    '__class' => \Yiisoft\Yii\Console\Application::class,
                    'id' => 'Migrator',
                    'basePath' => '@yii/tests',
                    'controllerMap' => [
                        'migrate' => EchoMigrateController::class,
                    ],
                ],
                'db' => static::getConnection(),
            ]);
        }

        ob_start();
        $result = Yii::getApp()->runAction($route, $params);
        echo 'Result is ' . $result;
        if ($result !== ExitCode::OK) {
            ob_end_flush();
        } else {
            ob_end_clean();
        }
    }

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        $databases = static::getParam('databases');
        static::$database = $databases[static::$driverName];
        $pdo_database = 'pdo_' . static::$driverName;

        Yii::getContainer()->set('db', static::getConnection());

        if (!extension_loaded('pdo') || !extension_loaded($pdo_database)) {
            static::markTestSkipped('pdo and ' . $pdo_database . ' extension are required.');
        }

        static::runConsoleAction('migrate/up', ['migrationPath' => '@yii/i18n/migrations/', 'interactive' => false]);
    }

    public static function tearDownAfterClass()
    {
        static::runConsoleAction('migrate/down', ['migrationPath' => '@yii/i18n/migrations/', 'interactive' => false]);
        if (static::$db) {
            static::$db->close();
        }
        Yii::getContainer()->set('app', null);
        parent::tearDownAfterClass();
    }

    public function tearDown()
    {
        parent::tearDown();
        Yii::getContainer()->set('app', null);
    }

    /**
     * @throws \Yiisoft\Db\Exception
     * @throws \yii\base\InvalidConfigException
     * @return \Yiisoft\Db\Connection
     */
    public static function getConnection()
    {
        if (static::$db == null) {
            $db = new Connection();
            $db->dsn = static::$database['dsn'];
            if (isset(static::$database['username'])) {
                $db->username = static::$database['username'];
                $db->password = static::$database['password'];
            }
            if (isset(static::$database['attributes'])) {
                $db->attributes = static::$database['attributes'];
            }
            if (!$db->isActive) {
                $db->open();
            }
            static::$db = $db;
        }

        return static::$db;
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultConfig()
    {
        return [
            'format' => 'db',
            'languages' => [$this->language],
            'sourcePath' => $this->sourcePath,
            'overwrite' => true,
            'db' => static::$db,
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function saveMessages($messages, $category)
    {
        static::$db->createCommand()->checkIntegrity(false, '', 'message')->execute();
        static::$db->createCommand()->truncateTable('message')->execute();
        static::$db->createCommand()->truncateTable('source_message')->execute();
        static::$db->createCommand()->checkIntegrity(true, '', 'message')->execute();
        foreach ($messages as $source => $translation) {
            $lastPk = static::$db->schema->insert('source_message', [
                'category' => $category,
                'message' => $source,
            ]);
            static::$db->createCommand()->insert('message', [
                'id' => $lastPk['id'],
                'language' => $this->language,
                'translation' => $translation,
            ])->execute();
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function loadMessages($category)
    {
        return ArrayHelper::map((new \Yiisoft\Db\Query())
            ->select(['message' => 't1.message', 'translation' => 't2.translation'])
            ->from(['t1' => 'source_message', 't2' => 'message'])
            ->where([
                't1.id' => new \Yiisoft\Db\Expression('[[t2.id]]'),
                't1.category' => $category,
                't2.language' => $this->language,
            ])->all(static::$db), 'message', 'translation');
    }
    
    /**
     * @Depends testMerge
     */
    public function testMarkObsoleteMessages()
    {
        $category = 'category';

        $obsoleteMessage = 'obsolete message';
        $obsoleteTranslation = 'obsolete translation';
        $this->saveMessages([$obsoleteMessage => $obsoleteTranslation], $category);

        $sourceFileContent = "Yii::t('{$category}', 'any new message');";
        $this->createSourceFile($sourceFileContent);

        $this->saveConfigFile($this->getConfig(['removeUnused' => false]));
        $out = $this->runMessageControllerAction('extract', [$this->configFileName]);

        $obsoleteMessage = '@@obsolete message@@';

        $messages = $this->loadMessages($category);

        $this->assertArrayHasKey($obsoleteMessage, $messages, "Obsolete message should not be removed. Command output:\n\n" . $out);
        $this->assertEquals($obsoleteTranslation, $messages[$obsoleteMessage], "Obsolete message was not marked properly. Command output:\n\n" . $out);
    }
}
