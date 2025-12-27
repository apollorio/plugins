<?php
/**
 * PHPUnit Stubs for PHPStan
 * Basic stubs for PHPUnit classes and methods
 */

namespace PHPUnit\Framework {
    class TestCase {
        protected function setUp(): void {}
        protected function tearDown(): void {}
        public function assertEquals($expected, $actual, string $message = ''): void {}
        public function assertNotEmpty($actual, string $message = ''): void {}
        public function assertTrue($condition, string $message = ''): void {}
        public function assertFalse($condition, string $message = ''): void {}
        public function assertNull($actual, string $message = ''): void {}
        public function assertNotNull($actual, string $message = ''): void {}
        public function assertInstanceOf(string $expected, $actual, string $message = ''): void {}
        public function assertCount(int $expectedCount, $haystack, string $message = ''): void {}
        public function assertContains($needle, $haystack, string $message = ''): void {}
        public function assertStringContainsString(string $needle, string $haystack, string $message = ''): void {}
        public function assertArrayHasKey($key, array $array, string $message = ''): void {}
        public function expectException(string $exception): void {}
        public function expectExceptionMessage(string $message): void {}
        public function getMockBuilder(string $className): MockBuilder {}
        public function createMock(string $className): MockObject {}
        public function assertThat($value, Constraint $constraint, string $message = ''): void {}
    }

    class MockBuilder {
        public function getMock(): MockObject {}
        public function setMethods(array $methods): self {}
        public function disableOriginalConstructor(): self {}
        public function disableOriginalClone(): self {}
        public function disableArgumentCloning(): self {}
    }

    interface MockObject {}

    abstract class Constraint {}
}
