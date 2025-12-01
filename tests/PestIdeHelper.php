<?php

namespace {
    use Closure;

    if (false) {
        class PestExpectation {
            public $not;
            public function toBe($value) { return $this; }
            public function toBeTrue() { return $this; }
            public function toBeFalse() { return $this; }
            public function toBeString() { return $this; }
            public function toBeBool() { return $this; }
            public function toBeInstanceOf($class) { return $this; }
            public function toHaveCount($count) { return $this; }
            public function toHaveKey($key) { return $this; }
        }

        /**
         * @param string $description
         * @param Closure|null $closure
         * @return mixed
         */
        function test(string $description, ?Closure $closure = null) {
            return null;
        }

        /**
         * @param string $description
         * @param Closure|null $closure
         * @return mixed
         */
        function it(string $description, ?Closure $closure = null) {
            return null;
        }

        /**
         * @param mixed $value
         * @return PestExpectation|mixed
         */
        function expect($value = null) {
            return new PestExpectation();
        }

        /**
         * @param string ...$class
         * @return mixed
         */
        function uses(...$class) {
            return null;
        }
        
        /**
         * @param Closure|null $closure
         * @return mixed
         */
        function beforeEach(?Closure $closure = null) {
            return null;
        }
    }
}
