<?php

require_once dirname(__FILE__) . '/../bootstrap.php';
require_once 'PHP/Callback.php';

class PHP_CallbackTest extends UnitTestCase 
{
	public function testThrowsExceptionOnNonValidCallback() {
		$callback = $this;
		try {
			$obj = new PHP_Callback($callback);
			$this->fail('Exception not caught');
		} catch (PHP_Callback_Exception $e) {
			$this->assertEqual('Non-valid callback provided', $e->getMessage());
		} catch(Exception $e) {
			$this->fail('Wrong exception caught?');
		}
	}

	public function returnHelloWorld() {
		return 'Hello World!';
	}

	public function testExecuteReturnsCallbacksResult() {
		$callback = array($this, 'returnHelloWorld');
		$obj = new PHP_Callback($callback);
		
		$this->assertEqual($this->returnHelloWorld(), $obj->execute());
	}

	public function testExecuteTakesAVariableNumberOfParameters() {
		$callback = new PHP_Callback('strtolower');

		$this->assertEqual(
			strtolower($this->returnHelloWorld()),
			$callback->execute($this->returnHelloWorld())
		);
	}

	/**
	 * @todo determine if tests after this point are necessary or feature bloat?
	 */
	public function testAllowsSettingOfParametersToUseInExecute() {
		$callback = new PHP_Callback('strtolower');
		$callback->addParameter($this->returnHelloWorld());

		$this->assertEqual(
			strtolower($this->returnHelloWorld()),
			$callback->execute()
		);
		$this->assertEqual(
			strtolower($this->returnHelloWorld()),
			$callback->execute()
		);
	}

	public function testParametersProvidedToExecuteReceivePriority() {
		$callback = new PHP_Callback('strtolower');
		$callback->addParameter($this->returnHelloWorld());
		
		$this->assertNotEqual(
			strtolower($this->returnHelloWorld()),
			$callback->execute(rand(1, 1000))
		);

		$this->assertEqual(
			strtolower($this->returnHelloWorld()),
			$callback->execute()
		);
	}

	public function testCanResetParameters() {
		$callback = new PHP_Callback('strtolower');
		$callback->addParameter($this->returnHelloWorld());
		
		$this->assertEqual(
			strtolower($this->returnHelloWorld()),
			$callback->execute()
		);

		$holaWorld = 'Hola World!';
		$callback->resetParameters();
		$callback->addParameter($holaWorld);

		$this->assertEqual(
			strtolower($holaWorld),
			$callback->execute()
		);
	}

	public function testCanAcceptTwoParametersForObjectCallback() {
		try {
			$callback = new PHP_Callback($this, 'returnHelloWorld');
			$this->assertEqual(
				$this->returnHelloWorld(),
				$callback->execute()
			);
		} catch (PHP_Callback_Exception $e) {
			$this->fail('Unexpected non-valid callback exception caught');
		} catch (Exception $e) {
			// this should never run
			$this->fail('Unknown exception caught?');
		}
	}

	public function testExceptionsCauseAreTheArgumentsProvidedToConstruct() {
		try {
			$callback = new PHP_Callback($this);
			$this->fail('Exception not caught');
		} catch (PHP_Callback_Exception $e) {
			$this->assertEqual(
				array($this),
				$e->getCause()
			);
		}
	}

	public function testParametersCanBeAddedByReference() {
		$callback = new PHP_Callback('strtolower');
		$hello = $this->returnHelloWorld();
		$callback->addParameterByRef($hello);
		$hello = 'Hola World!';
		
		$this->assertEqual(
			strtolower($hello),
			$callback->execute()
		);
	}

	public function testCanBeSerializedThenUnserializedForReuse() {
		$callback = new PHP_Callback($this, 'returnHelloWorld');
		$serialized = serialize($callback);
		$unserialized = unserialize($serialized);
		$this->assertEqual(
			$this->returnHelloWorld(),
			$unserialized->execute()
		);
	}

    public function testCallbackAnswersWhatItIsWithTrueOrFalseTakingParametersAsConstruct() {
        $func = (bool)rand(0, 1) ? 'strtolower' : 'strtoupper';

        $callback = new PHP_Callback($func);
        if ($func == 'strtoupper') {
            $this->assertTrue($callback->is('strtoupper'));
            $this->assertFalse($callback->is('strtolower'));
        } else {
            $this->assertFalse($callback->is('strtoupper'));
            $this->assertTrue($callback->is('strtolower'));
        }

        unset($callback);

        $callback = new PHP_Callback(array($this, 'returnHelloWorld'));
        $this->assertTrue($callback->is(array($this, 'returnHelloWorld')));
        $this->assertTrue($callback->is($this, 'returnHelloWorld'));
        $this->assertFalse($callback->is(__CLASS__, 'returnHelloWorld'));

        unset($callback);

        $callback = new PHP_Callback(__CLASS__, 'returnHelloWorld');
        $this->assertTrue($callback->is(__CLASS__, 'returnHelloWorld'));
        $this->assertTrue($callback->is(array(__CLASS__, 'returnHelloWorld')));
        $this->assertFalse($callback->is($this, 'returnHelloWorld'));
    }

    public function testCallbackAnswerDoesImplementDependAsAnInstanceof() {
        $callback = new PHP_Callback('strtolower');
        $this->assertFalse($callback->doesImplement(__CLASS__));

        unset($callback);

        $callback = new PHP_Callback($this, 'returnHelloWorld');
        $this->assertTrue($callback->doesImplement(__CLASS__));
        $this->assertTrue($callback->doesImplement('UnitTestCase'));
        $this->assertFalse($callback->doesImplement('PDO'));
        $this->assertTrue($callback->doesImplement($this));
        

        unset($callback);

        $callback = new PHP_Callback(__CLASS__, 'returnHelloWorld');
        $this->assertTrue($callback->doesImplement(__CLASS__));
    }

    public function testAnswersIfThisIsAFunction() {
        $callback = new PHP_Callback('strtolower');
        $this->assertTrue($callback->isFunction());

        unset($callback);

        $callback = new PHP_Callback($this, 'returnHelloWorld');
        $this->assertFalse($callback->isFunction());
    }

    public function testAnswersIfThisIsAStaticCallback() {
        $callback = new PHP_Callback(__CLASS__, 'returnHelloWorld');
        $this->assertTrue($callback->isStatic());

        unset($callback);

        $callback = new PHP_Callback($this, 'returnHelloWorld');
        $this->assertFalse($callback->isStatic());

        unset($callback);

        $callback = new PHP_Callback('strtolower');
        $this->assertFalse($callback->isStatic());
    }

    public function testAnswersIfThisIsAnObjectCallback() {
        $callback = new PHP_Callback(__CLASS__, 'returnHelloWorld');
        $this->assertTrue($callback->isObject());

        unset($callback);

        $callback = new PHP_Callback($this, 'returnHelloWorld');
        $this->assertTrue($callback->isObject());

        unset($callback);

        $callback = new PHP_Callback('strtolower');
        $this->assertFalse($callback->isObject());
    }

    public function testProvidesAReadOnlyCallbackProperty() {
        $func = rand(0, 1) ? 'strtolower' : 'strtoupper';
        $callback = new PHP_Callback($func);
        $this->assertEqual(
            $func,
            $callback->callback
        );

        $this->assertNull(
            $callback->unknownProperty,
            "Insure that __get only returns on the callback property"
        );

        try {
            $callback->callback = 'strtoupper';
            $this->fail('Exception not caught');
        } catch (PHP_Callback_Exception $e) {
            $this->pass('Exception caught');
        }

        try {
            $callback->unknownProperty = 'Random string';
            $this->pass('Exception not thrown');

            $this->assertNull(
                $callback->unknownProperty,
                'Unknown property correctly not set'
            );
        } catch (PHP_Callback_Exception $e) {
            $this->fail('Unexpected exception caught');
        }
    }
}
