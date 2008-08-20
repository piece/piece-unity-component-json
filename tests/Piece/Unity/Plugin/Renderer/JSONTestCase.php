<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * PHP versions 4 and 5
 *
 * Copyright (c) 2006 Chihiro Sakatoku <csakatoku@users.sourceforge.net>,
 *               2006-2008 KUBO Atsuhiro <iteman@users.sourceforge.net>,
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @package    Piece_Unity
 * @subpackage Piece_Unity_Component_JSON
 * @copyright  2006 Chihiro Sakatoku <csakatoku@users.sourceforge.net>
 * @copyright  2006-2008 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    SVN: $Id$
 * @since      File available since Release 1.0.0
 */

require_once realpath(dirname(__FILE__) . '/../../../../prepare.php');
require_once 'PHPUnit.php';
require_once 'HTML/AJAX/JSON.php';
require_once 'Piece/Unity/Context.php';
require_once 'Piece/Unity/Config.php';
require_once 'Piece/Unity/Error.php';
require_once 'Piece/Unity/Plugin/Factory.php';
require_once 'Piece/Unity/Plugin/Renderer/JSON.php';

// {{{ Piece_Unity_Plugin_Renderer_JSONTestCase

/**
 * Some tests for Piece_Unity_Plugin_Renderer_JSON.
 *
 * @package    Piece_Unity
 * @subpackage Piece_Unity_Component_JSON
 * @copyright  2006 Chihiro Sakatoku <csakatoku@users.sourceforge.net>
 * @copyright  2006-2008 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @since      Class available since Release 1.0.0
 */
class Piece_Unity_Plugin_Renderer_JSONTestCase extends PHPUnit_TestCase
{

    // {{{ properties

    /**#@+
     * @access public
     */

    /**#@-*/

    /**#@+
     * @access private
     */

    /**#@-*/

    /**#@+
     * @access public
     */

    function setUp()
    {
        Piece_Unity_Error::pushCallback(create_function('$error', 'var_dump($error); return ' . PEAR_ERRORSTACK_DIE . ';'));
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    function tearDown()
    {
        unset($_SERVER['REQUEST_METHOD']);
        Piece_Unity_Context::clear();
        Piece_Unity_Plugin_Factory::clearInstances();
        Piece_Unity_Error::clearErrors();
        Piece_Unity_Error::popCallback();
    }

    function jsonEncode($value)
    {
        if (extension_loaded('json')) {
            return json_encode($value);
        } else {
            $encoder = &new HTML_AJAX_JSON();
            return $encoder->encode($value);
        }
    }

    function jsonDecode($json)
    {
        if (extension_loaded('json')) {
            return json_decode($json);
        } else {
            $encoder = &new HTML_AJAX_JSON();
            return $encoder->decode($json);
        }
    }

    function testEncodeWithPHPJSON()
    {
        $context = &Piece_Unity_Context::singleton();
        $viewElement = &$context->getViewElement();
        $viewElement->setElement('content', 'hello world');
        $viewElement->setElement('__eventNameKey', 'foo');
        $viewElement->setElement('__scriptName', 'bar');
        $viewElement->setElement('__basePath', 'baz');
        $config = &new Piece_Unity_Config();
        $context->setConfiguration($config);
        $renderer = &Piece_Unity_Plugin_Factory::factory('Renderer_JSON');

        ob_start();
        $renderer->invoke();
        $json = ob_get_contents();
        ob_end_clean();

        $result = $this->jsonDecode($json);

        $this->assertEquals('hello world', $result->content);
        $this->assertNotNull($result->__eventNameKey);
        $this->assertNotNull($result->__scriptName);
        $this->assertNotNull($result->__basePath);
    }
    
    function testEncodeWithHTMLAJAX()
    {
        $context = &Piece_Unity_Context::singleton();
        $viewElement = &$context->getViewElement();
        $viewElement->setElement('content', 'hello world');
        $viewElement->setElement('__eventNameKey', 'foo');
        $viewElement->setElement('__scriptName', 'bar');
        $viewElement->setElement('__basePath', 'baz');
        $config = &new Piece_Unity_Config();
        $config->setConfiguration('Renderer_JSON', 'useHTMLAJAX', true);
        $context = &Piece_Unity_Context::singleton();
        $context->setConfiguration($config);
        $renderer = &Piece_Unity_Plugin_Factory::factory('Renderer_JSON');

        ob_start();
        $renderer->invoke();
        $json = ob_get_contents();
        ob_end_clean();

        $result = $this->jsonDecode($json);

        $this->assertEquals('hello world', $result->content);
    }

    function testEncodeFailure()
    {
        Piece_Unity_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));
        $context = &Piece_Unity_Context::singleton();

        /*
         * test a view element which contains circular references.
         */
        $obj = &new stdClass();
        $obj->favorite = 'Sake';
        $value = array(&$obj);
        $obj->self = &$value;
        $viewElement = &$context->getViewElement();
        $viewElement->setElement('content', $value);

        $config = &new Piece_Unity_Config();
        $config->setConfiguration('Renderer_JSON', 'include', array());
        $config->setConfiguration('Renderer_JSON', 'exclude', array());
        $context = &Piece_Unity_Context::singleton();
        $context->setConfiguration($config);
        $renderer = &Piece_Unity_Plugin_Factory::factory('Renderer_JSON');

        ob_start();
        $renderer->invoke();
        $json = ob_get_contents();
        ob_end_clean();

        $this->assertTrue(Piece_Unity_Error::hasErrors('exception'));

        $error = Piece_Unity_Error::pop();

        $this->assertEquals(PIECE_UNITY_ERROR_UNEXPECTED_VALUE, $error['code']);

        Piece_Unity_Error::popCallback();
    }

    function testExclude()
    {
        $context = &Piece_Unity_Context::singleton();
        $viewElement = &$context->getViewElement();
        $viewElement->setElement('content', 'hello world');
        $viewElement->setElement('spam', 'spamspamspam');
        $config = &new Piece_Unity_Config();
        $config->setConfiguration('Renderer_JSON', 'include', array());
        $config->setConfiguration('Renderer_JSON', 'exclude', array('spam'));
        $context = &Piece_Unity_Context::singleton();
        $context->setConfiguration($config);
        $renderer = &Piece_Unity_Plugin_Factory::factory('Renderer_JSON');

        ob_start();
        $renderer->invoke();
        $json = ob_get_contents();
        ob_end_clean();

        $result = $this->jsonDecode($json);

        $this->assertEquals('hello world', $result->content);

        $vars = get_object_vars($result);

        $this->assertTrue(array_key_exists('content', $vars));
        $this->assertFalse(array_key_exists('spam', $vars));
    }

    function testInclude()
    {
        $context = &Piece_Unity_Context::singleton();
        $viewElement = &$context->getViewElement();
        $viewElement->setElement('_content', 'hello world');
        $config = &new Piece_Unity_Config();
        $config->setConfiguration('Renderer_JSON', 'include', array('_content'));
        $context = &Piece_Unity_Context::singleton();
        $context->setConfiguration($config);
        $renderer = &Piece_Unity_Plugin_Factory::factory('Renderer_JSON');

        ob_start();
        $renderer->invoke();
        $json = ob_get_contents();
        ob_end_clean();

        $result = $this->jsonDecode($json);

        $this->assertEquals('hello world', $result->_content);
    }

    function testContentType()
    {
        $context = &Piece_Unity_Context::singleton();
        $viewElement = &$context->getViewElement();
        $viewElement->setElement('content', 'hello world');
        $config = &new Piece_Unity_Config();
        $config->setConfiguration('Renderer_JSON', 'contentType', 'text/json');
        $config->setConfiguration('Renderer_JSON', 'include', array());
        $config->setConfiguration('Renderer_JSON', 'exclude', array());
        $context = &Piece_Unity_Context::singleton();
        $context->setConfiguration($config);
        $renderer = &Piece_Unity_Plugin_Factory::factory('Renderer_JSON');

        ob_start();
        $renderer->invoke();
        $json = ob_get_contents();
        ob_end_clean();

        $this->assertEquals('text/json', $renderer->_contentType);
    }

    function testJSONP()
    {
        $_GET['callback'] = 'callback';
        $context = &Piece_Unity_Context::singleton();
        $viewElement = &$context->getViewElement();
        $viewElement->setElement('content', 'hello world');
        $config = &new Piece_Unity_Config();
        $config->setConfiguration('Renderer_JSON', 'contentType', 'text/javascript');
        $config->setConfiguration('Renderer_JSON', 'include', array());
        $config->setConfiguration('Renderer_JSON', 'exclude', array());
        $config->setConfiguration('Renderer_JSON', 'useJSONP', true);
        $config->setConfiguration('Renderer_JSON', 'callbackKey', 'callback');
        $context = &Piece_Unity_Context::singleton();
        $context->setConfiguration($config);
        $renderer = &Piece_Unity_Plugin_Factory::factory('Renderer_JSON');

        ob_start();
        $renderer->invoke();
        $json = ob_get_contents();
        ob_end_clean();

        $this->assertEquals('callback({"content":"hello world"});', $json);
        $this->assertEquals('text/javascript', $renderer->_contentType);
        
        unset($_GET['callback']);
    }

    function testDetectCicularReferenceInArray()
    {
        Piece_Unity_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));
        $context = &Piece_Unity_Context::singleton();
        $b = array(false, 2, 3.0, '4');
        $a = array(1, 2, 'spam', &$b);
        $b[] = &$a;
        $viewElement = &$context->getViewElement();
        $viewElement->setElement('foo', $a);
        $config = &new Piece_Unity_Config();
        $config->setConfiguration('Renderer_JSON', 'contentType', 'text/javascript');
        $config->setConfiguration('Renderer_JSON', 'include', array());
        $config->setConfiguration('Renderer_JSON', 'exclude', array());
        $config->setConfiguration('Renderer_JSON', 'useJSONP', true);
        $config->setConfiguration('Renderer_JSON', 'callbackKey', 'callback');
        $context = &Piece_Unity_Context::singleton();
        $context->setConfiguration($config);
        $renderer = &Piece_Unity_Plugin_Factory::factory('Renderer_JSON');
        $renderer->invoke();

        $this->assertTrue(Piece_Unity_Error::hasErrors('exception'));

        $error = Piece_Unity_Error::pop();

        $this->assertEquals(PIECE_UNITY_ERROR_UNEXPECTED_VALUE, $error['code']);
        $this->assertEquals(strtolower('_visitArray'),
                            strtolower($error['context']['function'])
                            );

        Piece_Unity_Error::popCallback();
    }
    
    function testDetectCicularReferenceInObject()
    {
        Piece_Unity_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));
        $context = &Piece_Unity_Context::singleton();
        $foo = &new stdClass();
        $bar = &new stdClass();
        $baz = &new stdClass();
        $foo->bar = &$bar;
        $bar->baz = &$baz;
        $baz->foo = &$foo;
        $viewElement = &$context->getViewElement();
        $viewElement->setElementByRef('foo', $foo);
        $config = &new Piece_Unity_Config();
        $config->setConfiguration('Renderer_JSON', 'contentType', 'text/javascript');
        $config->setConfiguration('Renderer_JSON', 'include', array());
        $config->setConfiguration('Renderer_JSON', 'exclude', array());
        $config->setConfiguration('Renderer_JSON', 'useJSONP', true);
        $config->setConfiguration('Renderer_JSON', 'callbackKey', 'callback');
        $context = &Piece_Unity_Context::singleton();
        $context->setConfiguration($config);
        $renderer = &Piece_Unity_Plugin_Factory::factory('Renderer_JSON');
        $renderer->invoke();

        $this->assertTrue(Piece_Unity_Error::hasErrors('exception'));

        $error = Piece_Unity_Error::pop();

        $this->assertEquals(PIECE_UNITY_ERROR_UNEXPECTED_VALUE, $error['code']);
        $this->assertEquals(strtolower('_visitObject'),
                            strtolower($error['context']['function'])
                            );
        Piece_Unity_Error::popCallback();
    }

    /**#@-*/

    /**#@+
     * @access private
     */

    /**#@-*/

    // }}}
}

// }}}

/*
 * Local Variables:
 * mode: php
 * coding: iso-8859-1
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * indent-tabs-mode: nil
 * End:
 */
