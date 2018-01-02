<?php

namespace Aegir\Provision\Robo;

use Aegir\Provision\Common\ProvisionAwareTrait;
use Robo\Collection\Collection;
use Robo\Exception\TaskExitException;
use Robo\Result;

class ProvisionCollection extends Collection {
    
    use ProvisionAwareTrait;
    
    
    /**
     * Run our tasks, and roll back if necessary.
     *
     * @return \Robo\Result
     */
    public function run()
    {
        $this->disableProgressIndicator();
        $result = $this->runWithoutCompletion();
        $this->complete();
        return $result;
    }
    
    /**
     * @return \Robo\Result
     */
    private function runWithoutCompletion()
    {
        $result = Result::success($this);
        
        if (empty($this->taskList)) {
            return $result;
        }
        
        if ($result->wasSuccessful()) {
            foreach ($this->taskList as $name => $taskGroup) {
                
                /** @var \Aegir\Provision\Task $task */
                $task = $this->getConfig()->get($name);
    
                if ($this->getProvision()->getOutput()->isVerbose()) {
                    $this->getProvision()->io()->customLite('STARTED ' . $name, '○');
                }

                // Show starting message.
                if (strpos($name, 'logging.') !== 0) {
                    $start_message = !empty($task->start)? $task->start: $name;
                    $this->getProvision()->io()->customLite($start_message , '☐');
                }

                // If being run interactively, pause momentarily to let user read start message, and replace start message with success or fail.
                if (strpos($name, 'logging.') !== 0 && $this->getProvision()->getInput()->isInteractive()) {
                    sleep(1);
                }

                // ROBO
                $taskList = $taskGroup->getTaskList();
                $result = $this->runTaskList($name, $taskList, $result);
                
                // END ROBO
    
                if ($this->getProvision()->getInput()->isInteractive()) {
    
                    // Erase lines
                    $lines = 1;
                    $this->getProvision()->getOutput()->write(["\x0D"]);
                    $this->getProvision()->getOutput()->write(["\x1B[2K"]);
                    if ($lines > 0) {
                        $this->getProvision()->getOutput()->write(
                          str_repeat("\x1B[1A\x1B[2K", $lines)
                        );
                    }
                }
        
                if (!$result->wasSuccessful()) {

                    // Override output with failure() message.
                    if (!empty($task->failure)) {
                        $failure_message = $task->failure;
                    }
                    
                    
                    if ($this->getProvision()->getOutput()->isVerbose()) {
                        $this->getProvision()->io()->errorLite('<options=bold>FAILED </> ' . $name);
                    }
                    else {
                        $this->getProvision()->io()->errorLite($failure_message);
                        // If task failed and there is getMessage, it is the exception message.
                        if (!empty($result->getMessage())) {
                            $this->getProvision()->io()->customLite($result->getMessage(), '   - ');
                        }
                    }
                    $this->fail();
                    return $result;
                }
                else {
                    
                    // Skip the logging tasks.
                    if (strpos($name, 'logging.') === 0) {
                        continue;
                    }
    
                    if (!empty($task->success)) {
                        $name = $task->success;
                    }
                    if ($this->getProvision()->getOutput()->isVerbose()) {
                        $this->getProvision()->io()->successLite('<fg=green>SUCCESS</> '.$name);
                    }
                    else {
                        $this->getProvision()->io()->successLite($name);
                    }
                }
            }
            $this->taskList = [];
        }
        $result['time'] = $this->getExecutionTime();
        
        return $result;
    }
    
    /**
     * {@inheritdoc}
     *
     * An exact copy of Collection::runTaskList(), because it is private and we need access.
     */
    private function runTaskList($name, array $taskList, Result $result)
    {
        try {
            foreach ($taskList as $taskName => $task) {
                $taskResult = $this->runSubtask($task);
                // If the current task returns an error code, then stop
                // execution and signal a rollback.
                if (!$taskResult->wasSuccessful()) {
                    return $taskResult;
                }
                // We accumulate our results into a field so that tasks that
                // have a reference to the collection may examine and modify
                // the incremental results, if they wish.
                $key = Result::isUnnamed($taskName) ? $name : $taskName;
                $result->accumulate($key, $taskResult);
                // The result message will be the message of the last task executed.
                $result->setMessage($taskResult->getMessage());
            }
        } catch (TaskExitException $exitException) {
            $this->fail();
            throw $exitException;
        } catch (\Exception $e) {
            // Tasks typically should not throw, but if one does, we will
            // convert it into an error and roll back.
            return Result::fromException($task, $e, $result->getData());
        }
        return $result;
    }
}