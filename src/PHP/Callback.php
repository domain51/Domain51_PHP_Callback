<?php
/**
 * This file contains the {@link PHP_Callback} class
 *
 * PHP Version 5
 *
 * LICENSE: This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 *
 * @category PHP
 * @package PHP_Callback
 * @author Travis Swicegood <development [at] domain51 [dot] com>
 * @copyright 2007 Domain51
 * @license http://www.gnu.org/licenses/lgpl.html LGPL
 * @version @@VERSION@@
 *
 */

/**
 * Load exception
 * @ignore
 */
require_once 'PHP/Callback/Exception.php';

/**
 * A Value object representing the callback pseudo-type
 *
 * This object provides a wrapper around the callback pseudo-type within PHP.
 * Validation that it is a valid callback happens at instantiation, so calling
 * {@link execute()} will fire a valid callback within PHP.
 *
 * This can be used for type-hinting to insure a valid callback is received by
 * another method:
 *
 * <code>
 *     function dependsOnValidCallback(PHP_Callback $callback) { ... }
 * </code>
 *
 * It can also be wrapped to provide validation of parameters of a given
 * callback:
 *
 * <code>
 *     class PHP_Callback_WithArguments
 *     {
 *         private $callback = null;
 *         private $string = '';
 *         
 *         public function __construct($callback, $foo) 
 *         {
 *             if (!is_string($foo)) {
 *                 throw new PHP_Callback_Exception('Parameter should be a string');
 *             }
 *
 *             $this->_callback = new PHP_Callback($callback);
 *             $this->string = $foo;
 *         }
 *         
 *         public function execute() 
 *         {
 *             return $this->_callback->execute($this->string);
 *         }
 *     }
 * </code>
 *
 *
 * @category PHP
 * @package PHP_Callback
 * @author Travis Swicegood <development [at] domain51 [dot] com>
 * @version Release: @@VERSION@@
 * @copyright 2007 Domain51
 * @license http://www.gnu.org/licenses/lgpl.html LGPL
 * @since Class available since v0.0.1
 *
 */
class PHP_Callback 
{
    private $parameters = array();

    /**
     * @internal Underscore used to avoid conflicts when an outside user requests
     *           the 'callback' property.
     */
    private $_callback = null;

    /**
     * Supported signatures:
     *  PHP_Callback('function');
     *  PHP_Callback(array($obj, 'method'));
     *  PHP_Callback(array('Object', 'method'));
     *  PHP_Callback($obj, 'method');
     *  PHP_Callback('Object', 'method');
     *
     * @throws PHP_Callback_Exception On invalid callback
     */
    public function __construct($callback, $p2 = null) 
    {
        if (!is_null($p2)) {
            $callback = array($callback, $p2);
        }
        if (!is_callable($callback)) {
            $args = func_get_args();
            throw new PHP_Callback_Exception('Non-valid callback provided', $args);
        }

        $this->_callback = $callback;
    }

    /**
     * Provides read-only access to callback property
     *
     * This returns null if anything other than "callback" is provided via $key.
     *
     * @param string $key
     * @return mixed
     */
    public function __get($key)
    {
        if ($key == 'callback') {
            return $this->_callback;
        }
    }

    /**
     * Protects read-only callback property
     *
     * If $key is "callback", this will throw a {@link PHP_Callback_Exception},
     * otherwise this method does nothing.
     *
     * @param string $key
     * @param mixed $value
     */
    public function __set($key, $value)
    {
        if ($key == 'callback') {
            throw new PHP_Callback_Exception(
                'Attempted to change read-only callback property', 
                array($key, $value)
            );
        }
    }

    /**
     * Execute this callback
     *
     * Any parameters that are specified are passed to the callback.
     * Parameters passed via execute() are given preference over parameters
     * from {@link addParameter()} and/or {@link addParameterByRef()}.
     *
     * @see addParameter(), addParameterByRef()
     * @return mixed
     */
    public function execute() 
    {
        $params = func_num_args() > 0 ? func_get_args() : $this->parameters;
        return call_user_func_array($this->_callback, $params);
    }

    /**
     * Adds a parameter to be used by {@link execute()}.
     *
     * @param mixed
     *
     * @see addParameterByRef(), resetParameters()
     */
    public function addParameter($parameter) 
    {
        $this->parameters[] = $parameter;
    }

    /**
     * Adds a parameter to be used by {@link execute()} as a reference
     *
     * @param mixed
     *
     * @see addParamter(), resetParameter()
     */
    public function addParameterByRef(&$parameter)
    {
        $this->parameters[] =& $parameter;
    }

    /**
     * Resets all parameters that have been added via {@link addParameter()}
     *
     * @see addParameter(), addParameterByRef()
     */
    public function resetParameters() 
    {
        $this->parameters = array();
    }

    /**
     * Returns true if the callback provided matches the callback that this represents
     *
     * Supported signatures:
     *  PHP_Callback->is('function');
     *  PHP_Callback->is(array($obj, 'method'));
     *  PHP_Callback->is(array('Object', 'method'));
     *  PHP_Callback->is($object, 'method');
     *  PHP_Callback->is('Class', 'method');
     *
     * @return bool
     */
    public function is($callback, $p2 = null)
    {
        if (!is_null($p2)) {
            $callback = array($callback, $p2);
        }
        return $this->_callback == $callback;
    }

    /**
     * Returns true if this callback implements the provided class/interface
     *
     * If this callback represents a function call, this will always return false.
     * If this callback represents a static method call, this will assume $implements 
     * should be the string equivalent of the class portion of the callback.  If this 
     * callback represents a non-static callback, the rules of that apply to PHP's 
     * instanceof apply.
     *
     * @param mixed $implements
     * @return bool
     */
    public function doesImplement($implements)
    {
        if (!is_array($this->_callback)) {
            return false;
        }

        return is_object($this->_callback[0]) ?
            $this->_callback[0] instanceof $implements :
            $this->_callback[0] == $implements;
    }

    /**
     * Returns true if this callback is a function
     *
     * @return bool
     */
    public function isFunction()
    {
        return is_string($this->_callback);
    }

    /**
     * Returns true if this callback is a static method callback
     *
     * @return bool
     */
    public function isStatic()
    {
        return $this->isObject() && is_string($this->_callback[0]);
    }

    /**
     * Returns true if this callback is an object, whether static, or instantiated
     *
     * @return bool
     */
    public function isObject()
    {
        return is_array($this->_callback);
    }
}

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

