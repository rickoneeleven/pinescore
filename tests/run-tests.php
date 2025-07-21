#!/usr/bin/env php
<?php

// Check if we're running with PHP 7.0+ (required for anonymous classes)
if (version_compare(PHP_VERSION, '7.0.0', '<')) {
    // Try to find and use PHP 7.4 if available
    $php74_path = '/usr/bin/php7.4';
    
    if (file_exists($php74_path) && is_executable($php74_path)) {
        // Re-execute this script with PHP 7.4
        $args = $_SERVER['argv'];
        array_unshift($args, $php74_path);
        
        // Execute with PHP 7.4 and exit with the same code
        passthru(implode(' ', array_map('escapeshellarg', $args)), $exitCode);
        exit($exitCode);
    } else {
        // PHP 7.4 not found, show error
        echo "Error: PHP 7.0 or higher is required (current version: " . PHP_VERSION . ")\n";
        echo "Anonymous class syntax requires PHP 7.0+\n";
        exit(1);
    }
}

require_once __DIR__ . '/bootstrap.php';

class TestRunner
{
    private $totalPassed = 0;
    private $totalFailed = 0;
    private $testFiles = [];
    private $results = [];
    
    public function run()
    {
        echo "\n========================================\n";
        echo "         Test Runner Starting\n";
        echo "========================================\n";
        
        $this->discoverTests(__DIR__);
        
        if (empty($this->testFiles)) {
            echo "\nNo test files found!\n";
            return 1;
        }
        
        echo "\nDiscovered " . count($this->testFiles) . " test file(s)\n";
        echo "========================================\n";
        
        foreach ($this->testFiles as $file) {
            $this->runTestFile($file);
        }
        
        $this->printSummary();
        
        return $this->totalFailed > 0 ? 1 : 0;
    }
    
    private function discoverTests($directory)
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && 
                preg_match('/Test\.php$/', $file->getFilename()) && 
                !preg_match('/bootstrap\.php$/', $file->getFilename())) {
                $this->testFiles[] = $file->getPathname();
            }
        }
        
        sort($this->testFiles);
    }
    
    private function runTestFile($file)
    {
        $relativePath = str_replace(__DIR__ . '/', '', $file);
        echo "\nRunning: {$relativePath}\n";
        
        require_once $file;
        
        $classes = get_declared_classes();
        $testClasses = [];
        
        foreach ($classes as $class) {
            $reflection = new ReflectionClass($class);
            if ($reflection->getFileName() === $file && 
                ($reflection->isSubclassOf('TestCase') || 
                 $reflection->isSubclassOf('PHPUnit\Framework\TestCase'))) {
                $testClasses[] = $class;
            }
        }
        
        if (empty($testClasses)) {
            echo "  ⚠ No test classes found in file\n";
            return;
        }
        
        foreach ($testClasses as $className) {
            try {
                $testInstance = new $className();
                
                if (method_exists($testInstance, 'run')) {
                    $results = $testInstance->run();
                    
                    if (is_array($results)) {
                        foreach ($results as $result) {
                            $this->results[] = $result;
                            if ($result['status'] === 'PASS') {
                                $this->totalPassed++;
                            } else {
                                $this->totalFailed++;
                            }
                        }
                    }
                } else {
                    echo "  ⚠ Test class {$className} has no run() method\n";
                }
                
            } catch (Exception $e) {
                echo "  ✗ Error instantiating {$className}: " . $e->getMessage() . "\n";
                $this->totalFailed++;
                $this->results[] = [
                    'test' => $className,
                    'status' => 'FAIL',
                    'error' => 'Failed to instantiate: ' . $e->getMessage()
                ];
            }
        }
    }
    
    private function printSummary()
    {
        echo "\n========================================\n";
        echo "              Test Summary\n";
        echo "========================================\n";
        
        $total = $this->totalPassed + $this->totalFailed;
        
        echo "\nTotal Tests: {$total}\n";
        echo "Passed: {$this->totalPassed}\n";
        echo "Failed: {$this->totalFailed}\n";
        
        if ($this->totalFailed > 0) {
            echo "\n❌ FAILED TESTS:\n";
            echo "----------------\n";
            
            foreach ($this->results as $result) {
                if ($result['status'] === 'FAIL') {
                    echo "  • {$result['class']}::{$result['test']}\n";
                    if (isset($result['error'])) {
                        echo "    Error: {$result['error']}\n";
                    }
                }
            }
        }
        
        echo "\n========================================\n";
        
        if ($this->totalFailed === 0) {
            echo "✅ All tests passed!\n";
        } else {
            echo "❌ Some tests failed!\n";
        }
        
        echo "========================================\n\n";
    }
}

$runner = new TestRunner();
exit($runner->run());