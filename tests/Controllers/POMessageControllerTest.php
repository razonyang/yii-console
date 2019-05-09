<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace Yiisoft\Yii\Console\Tests\Controllers;

use yii\helpers\Yii;
use yii\helpers\FileHelper;
use yii\i18n\GettextPoFile;

/**
 * Tests that [[\Yiisoft\Yii\Console\Controllers\MessageController]] works as expected with PO message format.
 */
class POMessageControllerTest extends BaseMessageControllerTest
{
    protected $messagePath;
    protected $catalog = 'messages';

    public function setUp()
    {
        parent::setUp();

        $this->messagePath = Yii::getAlias('@yii/tests/runtime/test_messages');
        FileHelper::createDirectory($this->messagePath, 0777);
    }

    public function tearDown()
    {
        parent::tearDown();
        FileHelper::removeDirectory($this->messagePath);
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultConfig()
    {
        return [
            'format' => 'po',
            'languages' => [$this->language],
            'sourcePath' => $this->sourcePath,
            'messagePath' => $this->messagePath,
            'overwrite' => true,
        ];
    }

    /**
     * @return string message file path
     */
    protected function getMessageFilePath()
    {
        return $this->messagePath . '/' . $this->language . '/' . $this->catalog . '.po';
    }

    /**
     * {@inheritdoc}
     */
    protected function saveMessages($messages, $category)
    {
        $messageFilePath = $this->getMessageFilePath();
        FileHelper::createDirectory(dirname($messageFilePath), 0777);
        $gettext = new GettextPoFile();

        $data = [];
        foreach ($messages as $message => $translation) {
            $data[$category . chr(4) . $message] = $translation;
        }

        $gettext->save($messageFilePath, $data);
    }

    /**
     * {@inheritdoc}
     */
    protected function loadMessages($category)
    {
        $messageFilePath = $this->getMessageFilePath();
        if (!file_exists($messageFilePath)) {
            return [];
        }

        $gettext = new GettextPoFile();
        return $gettext->load($messageFilePath, $category);
    }
}