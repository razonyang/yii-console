<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace Yiisoft\Yii\Console\Tests\Controllers;

use yii\base\Module;
use Yiisoft\Yii\Console\Controllers\HelpController;
use yii\helpers\Console;
use yii\tests\TestCase;

/**
 * Unit test for [[\Yiisoft\Yii\Console\Controllers\HelpController]].
 * @see HelpController
 * @group console
 */
class HelpControllerTest extends TestCase
{
    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->mockApplication();
    }

    /**
     * Creates controller instance.
     * @return BufferedHelpController
     */
    protected function createController()
    {
        $module = $this->getMockBuilder(Module::class)
            ->setConstructorArgs(['console', $this->app])
            ->setMethods(['fake'])
            ->getMock();
        return new BufferedHelpController('help', $module);
    }

    /**
     * Emulates running controller action.
     * @param string $actionID id of action to be run.
     * @param array $actionParams action arguments.
     * @return string command output.
     */
    protected function runControllerAction($actionID, $actionParams = [])
    {
        $controller = $this->createController();
        $action = $controller->createAction($actionID);
        $action->runWithParams($actionParams);
        return $controller->flushStdOutBuffer();
    }

    public function testModuleControllersList()
    {
        $this->mockApplication([
            'modules' => [
                'magic' => \yii\tests\data\modules\magic\Module::class,
            ],
        ]);
        $result = Console::stripAnsiFormat($this->runControllerAction('list'));
        $this->assertEqualsWithoutLE(<<<'STRING'
asset
asset/compress
asset/template
cache
cache/clear
cache/clear-all
cache/clear-schema
cache/index
fixture
fixture/load
fixture/unload
help
help/index
help/list
help/list-action-options
help/usage
magic/e-tag
magic/e-tag/delete
magic/e-tag/list-e-tags
magic/subFolder/sub
magic/subFolder/sub/test
message
message/config
message/config-template
message/extract
migrate
migrate/create
migrate/down
migrate/fresh
migrate/history
migrate/mark
migrate/new
migrate/redo
migrate/to
migrate/up
serve
serve/index

STRING
            , $result);
    }

    public function testActionList()
    {
        $this->mockApplication([
            'controllerMap' => [
                'migrate' => 'Yiisoft\Yii\Console\Controllers\MigrateController',
                'cache' => 'Yiisoft\Yii\Console\Controllers\CacheController',
            ],
        ]);
        $result = Console::stripAnsiFormat($this->runControllerAction('list'));
        $this->assertEqualsWithoutLE(<<<'STRING'
asset
asset/compress
asset/template
cache
cache/clear
cache/clear-all
cache/clear-schema
cache/index
fixture
fixture/load
fixture/unload
help
help/index
help/list
help/list-action-options
help/usage
message
message/config
message/config-template
message/extract
migrate
migrate/create
migrate/down
migrate/fresh
migrate/history
migrate/mark
migrate/new
migrate/redo
migrate/to
migrate/up
serve
serve/index

STRING
        , $result);
    }

    public function testActionListActionOptions()
    {
        $this->mockApplication([
            'controllerMap' => [
                'migrate' => 'Yiisoft\Yii\Console\Controllers\MigrateController',
                'cache' => 'Yiisoft\Yii\Console\Controllers\CacheController',
            ],
        ]);
        $result = Console::stripAnsiFormat($this->runControllerAction('list-action-options', ['action' => 'help/list-action-options']));
        $this->assertEqualsWithoutLE(<<<'STRING'
action:route to action

--interactive: whether to run the command interactively.
--color: whether to enable ANSI color in the output.If not set, ANSI color will only be enabled for terminals that support it.
--help: whether to display help information about current command.

STRING
        , $result);
    }

    public function testActionUsage()
    {
        $this->mockApplication([
            'controllerMap' => [
                'migrate' => 'Yiisoft\Yii\Console\Controllers\MigrateController',
                'cache' => 'Yiisoft\Yii\Console\Controllers\CacheController',
            ],
        ]);
        $result = Console::stripAnsiFormat($this->runControllerAction('usage', ['action' => 'help/list-action-options']));
        $this->assertEqualsWithoutLE(<<<'STRING'
bootstrap.php help/list-action-options <action>

STRING
            , $result);
    }

    public function testActionIndex()
    {
        $result = Console::stripAnsiFormat($this->runControllerAction('index'));
        $this->assertContains('This is Yii version ', $result);
        $this->assertContains('The following commands are available:', $result);
        $this->assertContains('To see the help of each command, enter:', $result);
        $this->assertContains('bootstrap.php help', $result);
    }

    public function testActionIndexWithHelpCommand()
    {
        $result = Console::stripAnsiFormat($this->runControllerAction('index', ['command' => 'help/index']));
        $this->assertContains('Displays available commands or the detailed information', $result);
        $this->assertContains('bootstrap.php help [command] [...options...]', $result);
        $this->assertContains('--appconfig: string', $result);
        $this->assertContains('- command: string', $result);
        $this->assertContains('--color: boolean, 0 or 1', $result);
        $this->assertContains('--help, -h: boolean, 0 or 1', $result);
        $this->assertContains('--interactive: boolean, 0 or 1 (defaults to 1)', $result);
    }

    public function testActionIndexWithServeCommand()
    {
        $result = Console::stripAnsiFormat($this->runControllerAction('index', ['command' => 'serve']));
        $this->assertContains('Runs PHP built-in web server', $result);
        $this->assertContains('bootstrap.php serve [address] [...options...]', $result);
        $this->assertContains('- address: string (defaults to \'localhost\')', $result);
        $this->assertContains('--appconfig: string', $result);
        $this->assertContains('--color: boolean, 0 or 1', $result);
        $this->assertContains('--docroot, -t: string (defaults to \'public\')', $result);
        $this->assertContains('--help, -h: boolean, 0 or 1', $result);
        $this->assertContains('--interactive: boolean, 0 or 1 (defaults to 1)', $result);
        $this->assertContains('--port, -p: int (defaults to 8080)', $result);
        $this->assertContains('--router, -r: string', $result);
    }
}


class BufferedHelpController extends HelpController
{
    use StdOutBufferControllerTrait;
}
